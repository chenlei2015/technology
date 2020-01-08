<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractSummary.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 海外平台汇总为站点列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-21
 * @link
 * @throw
 */
class OverseaPlatformSummary extends AbstractSummary implements Rpcable
{

    use Rpc_imples;

    protected static $s_unassign_sku_key = 'OVERSEA_PLATFORM_SUMSN_';

    /**
     * rpc 执行的模块名
     *
     * @var string
     */
    private $_rpc_module = 'oversea';

    /**
     * 选择的gid
     *
     * @var unknown
     */
    private $_gids;

    /**
     * 平台记录对应哪条站点记录
     *
     * @var unknown
     */
    private $_platform_map_station_gid;

    /**
     * 要删除的跟踪需求单号
     *
     * @var unknown
     */
    private $_delete_track_pr_sn = [];

    /**
     * v1.1.2 汇总逻辑不同于以往，主要反映
     *
     * @return number|boolean  -1 未执行，没有有效的记录 否则返回执行成功数量>0
     */
    public function run($should_addup = [])
    {
        $should_addup_rows = $this->summary_should_addup($should_addup);
        $build_data = $this->build($should_addup_rows, $this->general_sku_name_map());
        $should_addup_rows = NULL;
        unset($should_addup_rows);
        return $this->update($build_data);
    }

    /**
     * 设置默认, 注意alias必须是统计表对应的统计字段
     *
     */
    public function set_by_default($model)
    {
        $col_to_method = [
                'require_qty'  => ['method' => 'sum', 'alias' => 'platform_require_qty', 'name' => '平台毛需求汇总(pcs)'],
                'purchase_qty' => ['method' => 'sum', 'alias' => 'purchase_qty','name' => '站点订购数量(pcs)'],
                'bd'           => ['method' => 'sum', 'alias' => 'bd','name' => 'BD(pcs)'],
                'weight_sale_pcs' => ['method' => 'sum', 'alias' => 'weight_sale_pcs','name' => '站点加权销量平均值(pcs)'],

        ];
        $group_by_cols = 'sku, station_code, is_refund_tax';
        return $this->set_list_models($model)->set_summary_col_rule($col_to_method)->set_group_by($group_by_cols);
    }

    /**
     * 生成执行参数, 更新platform，  更新station， 记录一条更新日志
     *
     * @param unknown $should_addup_rows
     * @throws \InvalidArgumentException
     * @return unknown
     */
    protected function build($should_addup_rows, $sku_map_name)
    {


        //取sku
        //$skus = array_keys($should_addup_rows);

        //取汇总时间
        //$created_date = date('Y-m-d');

        //$db           = $this->_summary_model->getDatabase();

        //取未汇总的跟踪列表的最早日期
        //$unassign_track_list = $this->get_unassign_track_list();

        //取对应
        $rows = $this->_list_model->get_map_station_list($this->_gids);
        $summary_rows = $this->group_by_dimension($rows);

        CI::$APP->load->model('Oversea_pr_data', 'oversea_pr_data', false, 'oversea');
        $skus = array_unique(array_column($rows, 'sku'));
        //离线版数据库yibai_oversea_pr_data取 海外仓可用库存 + 海外仓待上架数 + 海外仓国际在途数量
        $stock_rows = CI::$APP->oversea_pr_data->get_skus_qty_list($skus);
        $stock_rows = $this->group_by_stock_rows($stock_rows);

//var_dump($rows, $summary_rows, $stock_rows);
//die;
        $time         = time();
        $now_date     = date('Y-m-d');
        $update = $gid_update = $log = [];
        $login_info   = get_active_user()->get_user_info();
        $user_name    = is_system_call() ? $login_info['user_name'] : $login_info['oa_info']['userName'];
        $uid          = is_system_call() ? $login_info['uid'] : $login_info['oa_info']['userNumber'];
        $this->_delete_track_pr_sn = [];

        foreach ($should_addup_rows as $sku => $station_info)
        {
            foreach ($station_info as $station_code => $refund_tax_info)
            {
                foreach ($refund_tax_info as $is_refund_tax => $info)
                {
                    //对应站点记录
                    $existed_row = $summary_rows[$sku][$station_code][$is_refund_tax] ?? [];
                    if (empty($existed_row)) {
                        //MRP未将站点列表生成
                        log_message('ERROR', sprintf('海外仓平台审核汇总，MRP未生成对应的站点列表，平台信息: sku:%s, station_code:%s, is_refund_tax:%d, info:%s', $sku, $station_code, $is_refund_tax, json_encode($info)));
                        continue;
                    }

                    //update
                    $where = $params = $one_log = [];
                    $where['gid'] = $existed_row['gid'];
                    $one_log = [
                            'gid' => $where['gid'],
                            'uid' => $uid,
                            'user_name' => $user_name,
                            'created_at' => date('Y-m-d H:i:s')
                    ];

                    $log_item = [];
                    foreach ($this->_summary_rule as $list_col => $cfg)
                    {
                        if (!array_key_exists($cfg['alias'], $existed_row))
                        {
                            throw new \InvalidArgumentException(sprintf('别名名称:%s必须与统计表字段一致', $cfg['alias']), 412);
                        }
                        $col = $cfg['alias'];
                        switch ($cfg['method'])
                        {
                            case 'min':
                                $params[$col] = min($info[$col], $existed_row[$col]);
                                break;
                            case 'sum':
                                $params[$col] = $info[$col] + $existed_row[$col];
                                break;
                            default:
                                throw new \InvalidArgumentException(sprintf('暂不支持的汇总方法'), 412);
                                break;
                        }
                        $log_item[] = $cfg['name'].' '.$existed_row[$col].'->'.$params[$col];
                        $where[$col] = $existed_row[$col];
                    }

                    $where['approve_state'] = $existed_row['approve_state'];

                    // 站点列表是更新还是第一次
                    if ($existed_row['display'] == OVERSEA_STATION_DISPLAY_NO && $existed_row['updated_at'] == 0)
                    {
                        $params['created_at'] = $time;
                    }
                    else
                    {
                        $params['created_at'] = $existed_row['created_at'];
                    }

                    $params['is_addup'] = PR_IS_ADDUP_NO;

                    //更新
                    $params['updated_at'] = $time;
                    $params['updated_uid'] = $uid;

                    //最后一次审核人
                    $params['approve_state'] = APPROVAL_STATE_SECOND;
                    $params['approved_uid'] = $uid;
                    $params['approved_at'] = $time;

                    //获取站点的基础数据
                    $params['safe_stock_pcs'] = $existed_row['safe_stock_pcs'];
                    $params['available_qty'] = $existed_row['available_qty'];
                    $params['oversea_up_qty'] = $existed_row['oversea_up_qty'];
                    $params['oversea_ship_qty'] = $existed_row['oversea_ship_qty'];
                    $params['stocked_qty'] = $existed_row['stocked_qty'];

                    /**
                     * v1.1.2 新增
                     *
                     *   3、是否触发需求：    php计算  = 海外仓可用库存 + 海外仓待上架数 + 海外仓国际在途数量 + 已备货（新增需求） >= 订购点 ？ 否 ： 是。
                     *   5、订购点：           php计算 = (平台毛需求汇总 + 安全库存。)
                     *   6、可支撑天数：       php计算=（海外仓可用库存 + 海外仓待上架数 + 海外仓国际在途数量）/ 站点加权销量平均值
                     */
                    $params['point_pcs'] = $this->recalc_point_cs($params);
                    $params['supply_day'] = $this->recalc_supply_day($params);
                    $params['is_trigger_pr'] = $this->recalc_trigger_pr($params);
                    $params['display'] = OVERSEA_STATION_DISPLAY_YES;

                    /**
                     * v1.1.2 新增
                     *
                     * 记录需求有原触发变更为不触发的站点需求单， 在跟踪列表中删除这些单号
                     *
                     */
                    if ($existed_row['is_trigger_pr'] == TRIGGER_PR_YES && $params['is_trigger_pr'] == TRIGGER_PR_NO)
                    {
                        $this->_delete_track_pr_sn[] = $existed_row['pr_sn'];
                    }

                    /**
                     * v1.4.0 订购点 + 站点订购数量 * 扩销系数 -（海外仓可用库存 + 海外仓待上架数 + 海外仓国际在途数量）
                     * v1.2.0 增加需求数量 = 订购点 + 站点订购数量-（海外仓可用+海外仓上架+海外仓在途）
                     */
                    $params['require_qty'] = $this->recalc_require_qty($params, $existed_row, $stock_rows);

                    //$params['gid'] = $existed_row['gid'];
                    //增加where条件限制
                    $update[] = ['where' => $where, 'update' => $this->_summary_model->fetch_table_cols($params)];
                    $one_log['context'] = sprintf('更新：%s', implode(',', $log_item));

                    $log[] = $one_log;

                    $gid_update = array_merge($gid_update, explode(',', $info['gids']));
                }
            }
        }
        return ['update' => &$update, 'gid' => &$gid_update, 'sku' => &$sku_map_name, 'log' => &$log];
    }

    //扩销系数
    protected function getExpandFactor($num)
    {
        $configFile = APPPATH . 'upload/oversea_cfg.php';//引入文件
        if (file_exists($configFile)) {
            require $configFile;
            $config = compact(['sales_amount_cfg', 'exhaust_cfg', 'in_warehouse_age_cfg', 'supply_day_cfg',  'sale_category_cfg']);
            foreach ($config['sale_category_cfg'] as $item) {
                //
                if ($item[1] == 'max') {
                    if ($num >= $item[0]) {
                        return $item[4];
                    }
                } else {
                    if ($num >= $item[0] && $num < $item[1]) {
                        return $item[4];
                    }
                }

            }
        } else {
            throw new Exception("配置文件不存在", 404);
        }


    }

    /**
     * 获取需要删除的汇总单号
     *
     * @return array
     */
    public function get_delete_track_pr_sn()
    {
        return $this->_delete_track_pr_sn;
    }

    /**
     * 重新计算订购点
     *
     * @param unknown $row
     * @return number
     */
    protected function recalc_point_cs($row)
    {
        return $row['platform_require_qty'] + $row['safe_stock_pcs'];
    }

    /**
     * 重新计算供货天数
     * v1.1.1
     * 1. 当库存、销量都为0，支撑天数=0
     * 2. 当库存大于0 销量为0，支撑天数=10000
     */
    protected function recalc_supply_day($row)
    {
        $stocked = intval($row['available_qty'] + $row['oversea_up_qty'] + $row['oversea_ship_qty']);
        if (bccomp($row['weight_sale_pcs'], 0, 2) == 0)
        {
            return $stocked > 0 ? 10000 : 0;
        }
        return ceil(($row['available_qty'] + $row['oversea_up_qty'] + $row['oversea_ship_qty']) / $row['weight_sale_pcs']);
    }

    /**
     * 海外仓可用库存 + 海外仓待上架数 + 海外仓国际在途数量 + 已备货（新增需求） >= 订购点 ？ 否 ： 是。
     * 必须在计算recalc_point_cs之后
     *
     * @param unknown $row
     */
    protected function recalc_trigger_pr($row)
    {
        $stock_pcs = $row['available_qty'] + $row['oversea_up_qty'] + $row['oversea_ship_qty'] + $row['stocked_qty'];
        return $stock_pcs >= $row['point_pcs'] ? TRIGGER_PR_NO : TRIGGER_PR_YES;
    }

    /**
     * v1.4.0 订购点 + 站点订购数量 * 扩销系数 -（海外仓可用库存 + 海外仓待上架数 + 海外仓国际在途数量）
     * v1.2.0 增加需求数量 = 订购点 + 站点订购数量-（海外仓可用+海外仓上架+海外仓在途）
     * v1.2.1 去除负值为0
     *
     * @param array $row 勾选汇总站点
     * @param array $station_row 站点数据
     * @return number
     */
    protected function recalc_require_qty($row, $station_row , $stock_rows)
    {
//        return $row['point_pcs'] + $row['purchase_qty'] - $station_row['available_qty'] - $station_row['oversea_up_qty'] - $station_row['oversea_ship_qty'];
        $expand_factor = $this->getExpandFactor($row['weight_sale_pcs']);
        return $row['point_pcs'] + ($row['purchase_qty'] * $expand_factor) - $stock_rows[$station_row['sku']][$station_row['station_code']]['available_qty'] - $stock_rows[$station_row['sku']][$station_row['station_code']]['oversea_up_qty'] - $stock_rows[$station_row['sku']][$station_row['station_code']]['oversea_ship_qty'];
    }

    protected function group_by_dimension($rows)
    {
        if (empty($rows))
        {
            return [];
        }
        $dimension = [];
        foreach ($rows as $row)
        {
            $dimension[$row['sku']][$row['station_code']][$row['is_refund_tax']] = $row;
        }
        return $dimension;
    }

    protected function group_by_stock_rows($rows)
    {
        if (empty($rows))
        {
            return [];
        }
        $dimension = [];
        foreach ($rows as $row)
        {
            $dimension[$row['sku']][$row['station_code']] = $row;
        }
        return $dimension;
    }

    /**
     * 汇总维度： sku、站点、是否退税
     *
     * v1.1.2
     *
     * 平台加权销量平均值（取配置表）                    -> 站点加权销量平均值 （即使没有通过，也需要累加）
     *
	 * 平台毛需求（平台加权销量平均值 * 备货提前期 + BD）  -> 站点需求 （累加， 即时确定，初始化全部计算）
	 * 平台订购数量                                     -> 站点订购数量 （累加）
     *
     * @param unknown $should_addup
     */
    protected function summary_should_addup($should_addup)
    {
        $sku_name = $summary_should_addup = [];
        foreach ($should_addup as $info)
        {
            //名称映射
            $sku_name[$info['sku']] = $info['sku_name'];
            $this->_gids[] = $info['gid'];

            //记录汇总
            if (!isset($summary_should_addup[$info['sku']][$info['station_code']][$info['is_refund_tax']]))
            {
                $tmp = [
                        'gids' => $info['gid'],
                        'sku'  => $info['sku'],
                ];
                foreach ($this->_summary_rule as $col => $cfg)
                {
                    $tmp[$cfg['alias']] = $col == 'require_qty' ? max(intval($info[$col]), 0) : $info[$col];
                }
                $summary_should_addup[$info['sku']][$info['station_code']][$info['is_refund_tax']] = $tmp;
                continue;
            }
            //设置了进行汇总
            foreach ($this->_summary_rule as $col => $cfg)
            {
                switch ($cfg['method'])
                {
                    case 'min':
                        $summary_should_addup[$info['sku']][$info['station_code']][$info['is_refund_tax']][$cfg['alias']] = min($info[$col], $summary_should_addup[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']][$cfg['alias']]);
                        break;
                    case 'sum':
                        $summary_should_addup[$info['sku']][$info['station_code']][$info['is_refund_tax']][$cfg['alias']] += ($info[$col]);
                        break;
                }
            }
        }
        $this->_sku_name_map = $sku_name;

        return $summary_should_addup;
    }

    /**
     * 生成sku的映射
     *
     * {@inheritDoc}
     * @see AbstractSummary::general_sku_name_map()
     */
    protected function general_sku_name_map()
    {
        //统一使用选择数据
        return $this->_sku_name_map;
    }

    /**
     * 执行更新
     */
    protected function update($replace_data)
    {
        try
        {
            $report_affect_row = 0;

            if (!empty($replace_data['log']))
            {
                $db = $this->_summary_log_model->getDatabase();
                $succ_rows = $db->insert_batch($this->_summary_log_model->getTable(), $replace_data['log']);
                if ($succ_rows == 0)
                {
                    $message = '批量插入汇总日志失败，返回影响行数0';
                    log_message('ERROR', sprintf('批量插入海外站点日志失败，返回影响行数0, 数据：%s', json_encode($replace_data['log'])));
                    throw new \RuntimeException($message, 500);
                }
            }

            if (!empty($replace_data['update']))
            {
                $db = $this->_summary_model->getDatabase();

                //>sprintv1.1.2 逐行update
                /*foreach ($replace_data['update'] as $key => $infos)
                {
                    $affect_rows = $db->update($this->_summary_model->getTable(), $infos['params'], $infos['where']);
                    if ($affect_rows == 0)
                    {
                        $message = '更新海外站点列表失败，返回影响行数0';
                        log_message('ERROR', sprintf('更新汇总列表失败，返回影响行数0, 数据：%s', json_encode($replace_data['update'])));
                        throw new \RuntimeException($message, 500);
                    }
                    $report_affect_row += $affect_rows;
                }*/

                //sprintv1.1.2 逐行update变更为multi_update执行
                $affect_rows = $db->update_batch_more_where($this->_summary_model->getTable(), $replace_data['update'], 'gid');
                if ($affect_rows != count($replace_data['update']))
                {
                    $message = sprintf('更新海外站点列表失败，预期更新%d条记录，实际更新%d条，选择的记录已经被其他用户修改，请筛选后后重试', count($replace_data['update']), $affect_rows);
                    log_message('ERROR', sprintf('更新海外站点列表失败，%d条记录已经被执行, 数据：%s', $affect_rows, json_encode($replace_data['update'])));
                    throw new \RuntimeException($message, 500);
                }
            }

            return $report_affect_row;
        }
        catch (\Exception $e)
        {
            log_message('ERROR', sprintf('执行更新海外站点数据库操作抛出异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException('执行更新海外站点数据库操作失败，请重试', 500);
        }
        catch(\Throwable $e)
        {
            log_message('ERROR', sprintf('执行更新海外站点数据库操作抛出异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException('执行更新海外站点数据库操作失败，请重试', 500);
        }
    }



}