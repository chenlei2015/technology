<?php

/**
 * 统计
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-02
 * @link
 * @throw
 */

abstract class AbstractSummary
{
    /**
     * 根据勾选汇总
     */
    const SUMMARY_MANUAL = 1;

    /**
     * 自动汇总
     *
     * @var integer
     */
    const SUMMARY_AUTOMATIC = 2;

    /**
     * 自动审核操作每次执行500个sku
     *
     * @var integer
     */
    public static $s_automatic_batch_size = 500;

    /**
     * 设置默认汇总类型
     *
     * @var unknown
     */
    protected $_summary_type = AbstractSummary::SUMMARY_MANUAL;

    protected $_summary_rule;

    /**
     * 统计规则
     * @var unknown
     */
    protected $_summary_rule_sql;

    /**
     * 分组列
     * @var unknown
     */
    protected $_group_by_cols;

    /**
     * 生成model
     *
     * @var unknown
     */
    protected $_list_model;

    /**
     * 汇总model
     * @var unknown
     */
    protected $_summary_model;

    /**
     * gid对应的汇总单号
     * @var unknown
     */
    protected $_addup_map_sum_sn;

    /**
     * 自动模式下先汇总，保持汇总关联的需求gid, 根据这个gid创建跟踪列表
     *
     * @var unknown
     */
    protected $_automatic_pr_gids;

    /**
     * 汇总场景 根据model来设定
     *
     * @var unknown
     */
    protected $_scene;

    /**
     * sku名字映射
     *
     * @var unknown
     */
    protected $_sku_name_map = [];

    /**
     * 设置汇总字段和字段的汇总方法
     * col => [method, alias]
     * @param unknown $col_to_method
     */
    public function set_summary_col_rule($col_to_method)
    {
        $this->_summary_rule = $col_to_method;
        $sql_item = [];
        foreach ($col_to_method as $col => $cfg)
        {
            $sql_item[] = sprintf('%s(%s) as %s', $cfg['method'], $col, $cfg['alias']);
        }
        $this->_summary_rule_sql = implode(',', $sql_item);
        return $this;
    }

    /**
     * 设置统计分组字段
     *
     * @param unknown $cols
     */
    public function set_group_by(string $cols)
    {
        $this->_group_by_cols = $cols;
        return $this;
    }

    /**
     * 设置model
     */
    public function set_list_models($model)
    {
        $this->_list_model = $model;
        return $this;
    }

    /**
     * 设置统计model
     *
     * @param unknown $model
     */
    public function set_summary_model($model)
    {
        $this->_summary_model = $model;
        switch (get_class($this->_summary_model))
        {
            case 'Fba_pr_summary_model':
                $this->_scene = 'fba_summary';
                break;
            case 'Oversea_pr_summary_model':
                $this->_scene = 'oversea_summary';
                break;
            case 'Inland_pr_summary_model':
                $this->_scene = 'inland_summary';
                break;
        }

        return $this;
    }

    /**
     * 设置统计日志logmodel
     * @param unknown $model
     * @return AbstractSummary
     */
    public function set_summary_log_model($model)
    {
        $this->_summary_log_model = $model;
        return $this;
    }

    /**
     * 设置默认, 注意alias必须是统计表对应的统计字段
     *
     */
    public function set_by_default($model)
    {
        $col_to_method = [
                'require_qty'         => ['method' => 'sum', 'alias' => 'total_required_qty', 'name' => '总需求数量'],
                'expect_exhaust_date' => ['method' => 'min', 'alias' => 'earliest_exhaust_date','name' => '最早缺货时间'],
        ];
        $group_by_cols = 'sku, is_refund_tax, purchase_warehouse_id';
        return $this->set_list_models($model)->set_summary_col_rule($col_to_method)->set_group_by($group_by_cols);
    }

    /**
     * v1.1.0 新增自动汇总模式
     *
     * @param unknown $type
     * @throws \InvalidArgumentException
     */
    public function set_summary_type($type)
    {
        if (!in_array($type, [self::SUMMARY_AUTOMATIC, self::SUMMARY_MANUAL]))
        {
            throw new \InvalidArgumentException('无效的汇总类型：%d', $type);
        }
        $this->_summary_type = $type;
        if ($this->_summary_type == self::SUMMARY_AUTOMATIC)
        {
            $this->_automatic_clean();
        }
        return $this;
    }

   /**
    * v1.1.0 新增国内自动备货方式
    *
    * @return number|boolean  -1 未执行，没有有效的记录 否则返回执行成功数量>0
    */
    public function run($should_addup = [])
    {
        $should_addup_rows = $this->get_ready_addup($should_addup);
        if (empty($should_addup_rows))
        {
            return -1;
        }
        $sku_map = $this->general_sku_name_map($should_addup_rows);
        $build_data = $this->build($should_addup_rows, $sku_map);
        $should_addup_rows = NULL;
        unset($should_addup_rows);
        return $this->update($build_data);
    }

    /**
     * 获取回传给跟踪列表的记录
     * @return unknown
     */
    public function get_addup_map_sum_sn()
    {
        return $this->_addup_map_sum_sn;
    }

    /**
     * 获取pr中统计的gids列表
     *
     * @return unknown
     */
    public function get_addup_map_automatic_gids()
    {
        return $this->_automatic_pr_gids;
    }

    /**
     * 获取需要汇总的列表，如果启用了rpc模式，那么执行$should_addup指定的记录。
     * 如果本地模式，则从数据库捞取需要汇总的。
     *
     * $this->_group_by_cols 目前只支持一个字段sku，多个字段 key_by会出错
     *
     * @param unknown $should_addup 自动汇总下为空
     * @return array
     */
    protected function get_ready_addup($should_addup)
    {
        $today_start = mktime(0, 0, 0, intval(date('m')), intval(date('d')), intval(date('Y')));
        $today_end = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('Y')));

        $summary_sql = sprintf(
            'SELECT %s, %s, GROUP_CONCAT(gid) as gids FROM %s WHERE require_qty > 0 and is_addup = %d and expired = %d and created_at >= %d and created_at <= %d GROUP BY  %s ORDER BY NULL LIMIT %d',
            $this->_group_by_cols,
            $this->_summary_rule_sql,
            $this->_list_model->getTable(),
            PR_IS_ADDUP_NO,
            FBA_PR_EXPIRED_NO,
            $today_start,
            $today_end,
            $this->_group_by_cols,
            self::$s_automatic_batch_size
        );
        $db = $this->_list_model->getDatabase();
        $rows = $db->query($summary_sql)->result_array();
        if (!empty($rows))
        {
            //采用更加节省内存的方式
            $this->build_automatic_data($rows);
            //$this->build_sku_name_map($rows);
            //$this->build_automatic_pr_gids($rows);
        }
        return $this->group_by_dimension($rows);
    }

    protected function build_automatic_data($rows)
    {
        if (empty($rows))
        {
            $this->_sku_name_map = [];
            return;
        }
        foreach ($rows as $key => $row)
        {
            $arr = explode(',', $row['gids']);
            rsort($arr);
            $gid_arr[] = $arr[0];
            $gid_rows[] = $row['gids'];
        }

        $this->_automatic_pr_gids = explode(',', implode(',', $gid_rows));
        $this->_sku_name_map = $this->_list_model->find_sku_info_by_gids($gid_arr);
        $gid_arr = $gid_rows = NULL;
        unset($gid_arr, $gid_rows);
    }

    /**
     * 生成新增文本
     *
     * @param unknown $info
     * @return string
     */
    protected function _general_add_log_context($info)
    {
        foreach ($this->_summary_rule as $col => $cfg)
        {
            $tmp[] = $cfg['name'].'值='.$info[$cfg['alias']];
        }
        $log_context = sprintf('新增：%s, 汇总信息：%s', $info['sku'], implode(',', $tmp));
        return $log_context;
    }

    /**
     * v1.1.0 自动审核才会使用下面方法，自动模式由子类实现， 这个是group_by_dimison之后的
     *
     * 生成sku的名字映射, 这里主要去最新一个gid的sku名字
     *
     * @deprecated
     * @param unknown $should_addup_rows 汇总的记录
     * @return unknown
     */
    protected function build_sku_name_map($should_addup_rows)
    {
        if (empty($should_addup_rows))
        {
            $this->_sku_name_map = [];
            return;
        }
        foreach (array_column($should_addup_rows, 'gids') as $gid_str)
        {
            $arr = explode(',', $gid_str);
            rsort($arr);
            $gid_arr[] = $arr[0];

        }
        $this->_sku_name_map = $this->_list_model->find_sku_info_by_gids($gid_arr);
        return;
    }

    /**
     * 自动模式中将被统计的gid保存
     * @deprecated
     * @param unknown $should_addup_rows
     */
    protected function build_automatic_pr_gids($should_addup_rows)
    {
        foreach(array_column($should_addup_rows, 'gids', 'sku') as $sku => $gids)
        {
            $gid_arr = explode(',', $gids);
            sort($gid_arr);
            $this->_automatic_pr_gids[$sku] = $gid_arr;
        }
        return;
    }

    /**
     * 返回sku对应的中文名
     *
     * @return unknown
     */
    protected function general_sku_name_map()
    {
        return $this->_sku_name_map;
    }

    /**
     * 生成汇总单号
     */
    protected function general_sum_sn($scene)
    {
        $ci = CI::$APP;
        $ci->load->service('basic/OrderSnPoolService');
        return $ci->ordersnpoolservice->setScene($scene)->pop();
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
            $dimension[$row['sku']][$row['is_refund_tax']][$row['purchase_warehouse_id']] = $row;
        }
        return $dimension;
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
        $unassign_track_list = $this->get_unassign_track_list();

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
                    $is_add = isset($summary_rows[$sku][$is_refund_tax][$purchase_warehouse_id]) ? false : true;
                    $track_hash_key = implode('_', [$sku, $is_refund_tax, $purchase_warehouse_id]);

                    if ($is_add)
                    {
                        $info['gid'] = $this->_summary_model->gen_id();
                        $info['sum_sn'] = $this->general_sum_sn($this->_scene);
                        $info['sku_name'] = mb_substr($sku_map_name[$sku]['sku_name'], 0, 255);
                        $info['created_date'] = $now_date;
                        $info['created_at'] = $time;
                        $info['updated_at'] = $time;
                        $info['is_refund_tax'] = $is_refund_tax;
                        $info['purchase_warehouse_id'] = $purchase_warehouse_id;
                        $info['earliest_exhaust_date'] = min($unassign_track_list[$track_hash_key] ?? '9999-99-99', $info['earliest_exhaust_date']);
                        if (isset($sku_map_name[$sku]['is_boutique']))
                        {
                            $info['is_boutique'] = $sku_map_name[$sku]['is_boutique'];
                        }
                        if (isset($sku_map_name[$sku]['supplier_code']))
                        {
                            $info['supplier_code'] = $sku_map_name[$sku]['supplier_code'];
                        }
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
                        //重设日期
                        $existed_row['earliest_exhaust_date'] = min($unassign_track_list[$track_hash_key] ?? '9999-99-99', $existed_row['earliest_exhaust_date']);

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

                        $params['created_at'] = $time;
                        $params['updated_at'] = $time;
                        $update[] = ['where' => $where, 'update' => $this->_summary_model->fetch_table_cols($params)];
                        $one_log['context'] = sprintf('更新：%s', implode(',', $log_item));

                        $log[] = $one_log;
                    }

                    //未统计的最小值已经被取走更新，删除此值
                    if (isset($unassign_track_list[$track_hash_key]))
                    {
                        unset($unassign_track_list[$track_hash_key]);
                    }

                    $gid_update = array_merge($gid_update, explode(',', $info['gids']));
                }
            }
        }

        //重新写入
        $this->set_unassign_track_list($unassign_track_list);

        return ['insert' => $insert, 'update' => $update, 'gid' => $gid_update, 'sku' => $sku_map_name, 'log' => $log];
    }

    /**
     * 更新列表的is_addup装填
     */
    protected function update_addup_state($db)
    {
        $db->reset_query();
        $db->where_in('gid', $replace_data['gid']);
        $affect_rows = $db->update($this->_list_model->getTable(), ['is_addup' => PR_IS_ADDUP_YES]);
        if ($affect_rows != count($replace_data['gid']))
        {
            $message = '批量更新需求列表失败，返回影响行数'.$affect_rows;
            log_message('ERROR', sprintf('批量插入汇总列表失败，返回影响行数%d,实际应该：%d, 数据：%s', count($replace_data['gid']), $affect_rows, json_encode($replace_data['insert'])));
            throw new \RuntimeException($message, 500);
        }
        return true;
    }

    /**
     * 执行更新
     */
    protected function update($replace_data)
    {
        $db = $this->_summary_model->getDatabase();

        try
        {
            $report_affect_row = 0;

            $db->trans_start();

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

            if (!empty($replace_data['update']))
            {
                foreach ($replace_data['update'] as $key => $infos)
                {
                    $affect_rows = $db->update($this->_summary_model->getTable(), $infos['params'], $infos['where']);
                    if ($affect_rows == 0)
                    {
                        $message = '更新汇总列表失败，返回影响行数0';
                        log_message('ERROR', sprintf('更新汇总列表失败，返回影响行数0, 该行可能因为并发已经被修改，事务回滚。 数据：%s', json_encode($replace_data['update'])));
                        throw new \RuntimeException($message, 500);
                    }
                    $report_affect_row += $affect_rows;
                }
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

            $this->update_addup_state($db);

            $db->trans_complete();

            if ($db->trans_status() === FALSE)
            {
                if (method_exists($this, 'update_rollback'))
                {
                    if (!$this->update_rollback($replace_data['gid']))
                    {
                        log_message('ERROR', sprintf('置汇总事务提交完成，但检测状态为false, 回滚跨库操作出现失败，需要人工介入操作，请将gid列表：%s的is_addup状态设置为未汇总', implode(',', $replace_data['gid'])));
                        throw new \RuntimeException(sprintf('设置汇总事务提交完成，但检测状态为false, 回滚跨库操作出现失败，需要人工介入操作'), 500);
                    }
                }
                throw new \RuntimeException(sprintf('设置汇总事务提交完成，但检测状态为false'), 500);
            }

            $replace_data = NULL;
            unset($replace_data);

            return $report_affect_row;
        }
        catch (\Exception $e)
        {
            log_message('ERROR', sprintf('设置汇总事务抛出异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException('设置用户权限失败，请重试', 500);
        }
        catch(\Throwable $e)
        {
            log_message('ERROR', sprintf('设置汇总事务抛出异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException('设置用户权限失败，请重试', 500);
        }
    }

    /**
     * 重复执行时初始化
     */
    protected function _automatic_clean()
    {
        $this->_addup_map_sum_sn = [];
        $this->_automatic_pr_gids = [];
        $this->_sku_name_map = [];
        return $this;
    }
}