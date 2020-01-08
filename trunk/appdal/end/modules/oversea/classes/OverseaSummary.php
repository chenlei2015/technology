<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractSummary.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * fba统计
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-21
 * @link
 */
class OverseaSummary extends AbstractSummary implements Rpcable
{

    use Rpc_imples;

    protected static $s_unassign_sku_key = 'OVERSEA_UNASSIGN_SUMSN_';

    /**
     * rpc 执行的模块名
     *
     * @var string
     */
    private $_rpc_module = 'oversea';

    /**
     * 需要汇总的记录的sku具体有哪些收到影响
     *
     * @var array
     */
    private $_affected_summary_row = [];

    /**
     * 对指定的列表进行汇总生成汇总数据, 增加了是否退税，采购仓库两个维度
     *
     * v1.1.2
     * 汇总需求数量<0的需求单，其需求数量会当做0来处理， 如果计算得知需求汇总需求为0，则不创建对应的汇总单。
     *
     * v1.1.2
     * 因为是叠加汇总，所以每次都是针对涉及到的sku重新汇总，不在对$should_addup进行汇总然后累加
     *
     * v1.2.0
     * 增加是否精品字段
     *
     * @param array $should_addup 勾选或查找需要汇总的站点记录
     *
     * 获取需要汇总的列表, 针对涉及到sku重新执行统计，然后根据sku统计情况进行插入或者更新
     *
     * @override
     * {@inheritDoc}
     * @see AbstractSummary::get_ready_addup()
     */
    protected function get_ready_addup($should_addup)
    {
        $refer_skus = array_unique(array_column($should_addup, 'sku'));
        //生成sku与名字,是否精品的列表
        foreach ($should_addup as $info)
        {
            $this->_sku_name_map[$info['sku']] = ['sku_name' => $info['sku_name'], 'is_boutique' => $info['is_boutique']];
        }
        //记录实际受影响的summary_row
        $this->general_affected_summary_row($should_addup);

        $today_start = mktime(0, 0, 0, intval(date('m')), intval(date('d')), intval(date('Y')));
        $today_end = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('Y')));

        //去掉 require_qty > 0, over_pr_list
        /*$summary_sql = sprintf(
            'SELECT %s, %s, GROUP_CONCAT(gid) as gids FROM %s WHERE sku in (%s) and is_trigger_pr = %d and expired = %d and created_at >= %d and created_at <= %d  GROUP BY  %s ORDER BY NULL ',
            $this->_group_by_cols,
            $this->_summary_rule_sql,
            $this->_list_model->getTable(),
            array_where_in($refer_skus),
            TRIGGER_PR_YES,
            FBA_PR_EXPIRED_NO,
            $today_start,
            $today_end,
            $this->_group_by_cols
            );*/

        //优化
        $summary_sql = sprintf(
            'SELECT %s, %s, GROUP_CONCAT(gid) as gids FROM '.
            '(select gid, %s FROM %s WHERE sku in (%s) and is_trigger_pr = %d and expired = %d and created_at >= %d and created_at <= %d ) derived '.
            'GROUP BY %s ORDER BY NULL ',
            $this->_group_by_cols,
            $this->_summary_rule_sql,
            $this->_group_by_cols . ',' . implode(',', array_keys($this->_summary_rule)),
            $this->_list_model->getTable(),
            array_where_in($refer_skus),
            TRIGGER_PR_YES,
            FBA_PR_EXPIRED_NO,
            $today_start,
            $today_end,
            $this->_group_by_cols
        );

        $db = $this->_list_model->getDatabase();
        $rows = $db->query($summary_sql)->result_array();
        $dimension_rows = $this->group_by_dimension($rows);
        $rows = NULL;
        unset($rows);
        return $dimension_rows;
    }

    /**
     * 记录选择的记录会影响到汇总的哪些记录
     *
     * @param unknown $should_addup
     */
    protected function general_affected_summary_row($should_addup)
    {
        foreach ($should_addup as $key => $info)
        {
            $this->_affected_summary_row[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']] = 1;
        }
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
     * 生成执行参数
     *
     * @param unknown $should_addup_rows
     * @throws \InvalidArgumentException
     * @return unknown
     */
    protected function build($should_addup_rows, $sku_map_name)
    {
        //取sku
        $skus = array_keys($should_addup_rows);

        //取汇总时间
        $created_date = date('Y-m-d');

        //根据gid取sku的名字，没有进行分组，是因为sku中文名可能变化
        $db           = $this->_summary_model->getDatabase();

        //取未汇总的跟踪列表的最早日期
        //$unassign_track_list = $this->get_unassign_track_list();

        //取当天已经汇总的信息
        $summary_rows = $this->group_by_dimension($this->_summary_model->get_sku_summary_rows_by_date($skus, $created_date));
        $time         = time();
        $now_date     = date('Y-m-d');
        $insert       = $update = $gid_update = $log = [];
        $login_info   = get_active_user()->get_user_info();
        $user_name    = is_system_call() ? $login_info['user_name'] : $login_info['oa_info']['userName'];
        $uid          = is_system_call() ? $login_info['uid'] : $login_info['oa_info']['userNumber'];

        foreach ($should_addup_rows as $sku => $refund_tax_info)
        {
            foreach ($refund_tax_info as $is_refund_tax => $warehouse_info)
            {
                foreach ($warehouse_info as $purchase_warehouse_id => $info)
                {
                    //过滤掉不受影响的汇总记录
                    if (!isset($this->_affected_summary_row[$sku][$is_refund_tax][$purchase_warehouse_id]))
                    {
                        continue;
                    }

                    $is_add = isset($summary_rows[$sku][$is_refund_tax][$purchase_warehouse_id]) ? false : true;

                    if ($is_add)
                    {
                        $info['gid']                   = $this->_summary_model->gen_id();
                        $info['sum_sn']                = $this->general_sum_sn($this->_scene);
                        $info['sku_name']              = mb_substr($sku_map_name[$sku]['sku_name'], 0, 255);
                        $info['is_boutique']           = $sku_map_name[$sku]['is_boutique'];
                        $info['created_date']          = $now_date;
                        $info['created_at']            = $time;
                        $info['updated_at']            = $time;
                        $info['is_refund_tax']         = $is_refund_tax;
                        $info['purchase_warehouse_id'] = $purchase_warehouse_id;
                        //$info['earliest_exhaust_date'] = min($unassign_track_list[$track_hash_key] ?? '9999-99-99', $info['earliest_exhaust_date']);
                        $insert[] = $this->_summary_model->fetch_table_cols($info);
                        $log[] = [
                                'gid' => $info['gid'],
                                'uid' => $uid,
                                'user_name' => $user_name,
                                'context' => $this->_general_add_log_context($info),
                                'created_at' => date('Y-m-d H:i:s')
                        ];
                        //记录生成的备货单用于回写跟踪单号
                        $this->_addup_map_sum_sn[$sku][$is_refund_tax][$purchase_warehouse_id] = $info['sum_sn'];
                    }
                    else
                    {
                        $existed_row = $summary_rows[$sku][$is_refund_tax][$purchase_warehouse_id];

                        //v1.2.0 已经审核就不在汇总
                        if ($existed_row['approve_state'] != OVERSEA_SUMMARY_APPROVAL_STATE_FIRST)
                        {
                            continue;
                        }

                        //$existed_row['earliest_exhaust_date'] = min($unassign_track_list[$track_hash_key] ?? '9999-99-99', $existed_row['earliest_exhaust_date']);
                        $this->_addup_map_sum_sn[$sku][$is_refund_tax][$purchase_warehouse_id] = $existed_row['sum_sn'];

                        //update
                        $where = $params = $one_log = [];
                        $where['gid'] = $existed_row['gid'];
                        $one_log = [
                                'gid' => $where['gid'],
                                'uid' => $login_info['oa_info']['userNumber'],
                                'user_name' => $login_info['oa_info']['userName'],
                                'created_at' => date('Y-m-d H:i:s')
                        ];

                        //累加变为直接赋值
                        $log_item = [];
                        foreach ($this->_summary_rule as $list_col => $cfg)
                        {
                            if (!array_key_exists($cfg['alias'], $existed_row))
                            {
                                throw new \InvalidArgumentException(sprintf('别名名称:%s必须与统计表字段一致', $cfg['alias']), 412);
                            }
                            $col = $cfg['alias'];

                            //叠加变为直接赋值
                            $params[$col] = $info[$col];

                            /*switch ($cfg['method'])
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
                            }*/
                            $log_item[] = $cfg['name'].' '.$existed_row[$col].'->'.$params[$col];
                            $where[$col] = $existed_row[$col];
                        }

                        $params['created_at'] = $time;
                        $params['updated_at'] = $time;
                        $update[] = ['where' => $where, 'update' => $this->_summary_model->fetch_table_cols($params)];
                        $one_log['context'] = sprintf('更新：%s', implode(',', $log_item));

                        $log[] = $one_log;
                    }

                    $gid_update = array_merge($gid_update, explode(',', $info['gids']));
                }
            }
        }

        return ['insert' => &$insert, 'update' => &$update, 'gid' => &$gid_update, 'sku' => &$sku_map_name, 'log' => &$log];
    }

    /**
     * 执行更新
     */
    protected function update($replace_data)
    {
        try
        {
            $db = $this->_summary_model->getDatabase();

            $report_affect_row = 0;

            if (!empty($replace_data['update']))
            {
                /*foreach ($replace_data['update'] as $key => $infos)
                 {
                 $affect_rows = $db->update($this->_summary_model->getTable(), $infos['params'], $infos['where']);
                 if ($affect_rows == 0)
                 {
                 $message = '更新汇总列表失败，返回影响行数0';
                 log_message('ERROR', sprintf('更新汇总列表失败，返回影响行数0, 数据：%s', json_encode($replace_data['update'])));
                 throw new \RuntimeException($message, 500);
                 }
                 $report_affect_row += $affect_rows;
                 }*/

                //sprintv1.1.2 逐行update变更为multi_update执行
                $affect_rows = $db->update_batch_more_where($this->_summary_model->getTable(), $replace_data['update'], 'gid');
                if ($affect_rows != count($replace_data['update']))
                {
                    $message = sprintf('更新海外汇总列表失败，预期更新%d条记录，实际更新%d条， 选择的记录已经被其他用户修改，请筛选后后重试', count($replace_data['update']), $affect_rows);
                    log_message('ERROR', $message.',数据：%s', json_encode($replace_data['update']));
                    throw new \RuntimeException($message, 500);
                }
            }

            if (!empty($replace_data['insert']))
            {
                $succ_rows = $db->insert_batch($this->_summary_model->getTable(), $replace_data['insert']);
                if ($succ_rows != count($replace_data['insert']))
                {
                    $message = '批量插入汇总列表失败，返回影响行数与记录数不符合';
                    log_message('ERROR', sprintf('批量插入汇总列表失败，返回影响行数%d, 预期为：%d, 数据：%s',$succ_rows, count($replace_data['insert']),  json_encode($replace_data['insert'])));
                    throw new \RuntimeException($message, 500);
                }
                $report_affect_row += $succ_rows;
            }

            if (!empty($replace_data['log']))
            {
                $succ_rows = $db->insert_batch($this->_summary_log_model->getTable(), $replace_data['log']);
                if ($succ_rows == 0)
                {
                    $message = '批量插入汇总日志失败，返回影响行数0';
                    log_message('ERROR', sprintf('批量插入汇总日志失败，返回影响行数0, 数据：%s', json_encode($replace_data['log'])));
                    throw new \RuntimeException($message, 500);
                }
            }

            return $report_affect_row;
        }
        catch (\Exception $e)
        {
            log_message('ERROR', sprintf('执行汇总数据库操作抛出异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException('执行汇总数据库操作失败，请重试', 500);
        }
        catch(\Throwable $e)
        {
            log_message('ERROR', sprintf('执行汇总数据库操作抛出异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException('执行汇总数据库操作失败，请重试', 500);
        }
    }

    /**
     * 重新写入
     * @param unknown $track_list
     */
    protected function set_unassign_track_list($track_list)
    {
        $today = date('Y-m-d');
        $today_end = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('Y')));
        $remain_seconds = $today_end - time();
        $key = self::$s_unassign_sku_key.$today;

        $ci = CI::$APP;
        $ci->load->library('rediss');
        return $ci->rediss->setData($key, $track_list, $remain_seconds);
    }

    /**
     * 未赋值的列表, 因汇总需求数量<0，导致没有创建汇总单号，但创建了跟踪列表。
     * 在后来又达到了汇总条件，将汇总单号回写到跟踪列表
     *
     * @return array|unknown
     */
    protected function get_unassign_track_list()
    {
        $today = date('Y-m-d');
        $today_start = mktime(0, 0, 0, intval(date('m')), intval(date('d')), intval(date('Y')));
        $today_end = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('Y')));
        $remain_seconds = $today_end - time();
        $key = self::$s_unassign_sku_key.$today;

        $ci = CI::$APP;
        $ci->load->library('rediss');
        $unassign_list = $ci->rediss->getData($key);
        if ($unassign_list !== null)
        {
            return is_string($unassign_list) && $unassign_list == 'NONE' ? [] : (array)$unassign_list;
        }

        $ci->load->model('Oversea_pr_track_list_model', 'oversea_track_list_model', false, 'oversea');

        $group_sql = sprintf(
            'SELECT sku, is_refund_tax, purchase_warehouse_id, min(expect_exhaust_date) as earliest_exhaust_date '.
            ' FROM %s WHERE created_at > %d AND created_at < %d AND sum_sn = "" '.
            ' GROUP BY sku, is_refund_tax, purchase_warehouse_id',
            $ci->oversea_track_list_model->getTable(),
            $today_start,
            $today_end
            );

        $rows = $ci->oversea_track_list_model->getDatabase()->query($group_sql)->result_array();
        $cache_rows = [];
        if (!empty($rows))
        {
            foreach ($rows as $row)
            {
                $earliest_exhaust_date = $row['earliest_exhaust_date'];
                unset($row['earliest_exhaust_date']);
                $cache_rows[implode('_', $row)] = $earliest_exhaust_date;
            }
        }
        if (empty($cache_rows))
        {
            $cache_rows = 'NONE';
        }
        $ci->rediss->setData($key, $cache_rows, $remain_seconds);
        $rows = NULL;
        unset($rows);
        return $cache_rows;
    }

}