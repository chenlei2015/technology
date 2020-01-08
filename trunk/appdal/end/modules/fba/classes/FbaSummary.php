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
 * @throw
 */
class FbaSummary extends AbstractSummary implements Rpcable
{

    use Rpc_imples;

    protected static $s_unassign_sku_key = 'FBA_UNASSIGN_SUMSN_';

    /**
     * rpc 执行的模块名
     *
     * @var string
     */
    private $_rpc_module = 'fba';

    /**
     * 设置默认, 注意alias必须是统计表对应的统计字段
     *
     */
    public function set_by_default($model)
    {
        $col_to_method = [
                'require_qty'         => ['method' => 'sum', 'alias' => 'total_required_qty', 'name' => '总需求数量'],
                'expect_exhaust_date' => ['method' => 'min', 'alias' => 'earliest_exhaust_date','name' => '最早缺货时间'],
                'max_stock_qty'       => ['method' => 'sum', 'alias' => 'max_stock_qty', 'name' => '最大备货量'],
                'weight_sale_pcs'     => ['method' => 'sum', 'alias' => 'weight_sale_pcs', 'name' => '加权日均销量'],

        ];
        $group_by_cols = 'sku, is_refund_tax, purchase_warehouse_id';
        return $this->set_list_models($model)->set_summary_col_rule($col_to_method)->set_group_by($group_by_cols);
    }

    /**
     * 对指定的列表进行汇总生成汇总数据, 增加了是否退税，采购仓库两个维度
     *
     * v1.1.2
     * 汇总需求数量<0的需求单，其需求数量会当做0来处理， 如果计算得知需求汇总需求为0，则不创建对应的汇总单。
     *
     * v1.2.0
     * sku属性增加是否是否精品字段
     *
     * v1.2.2
     * 增加供应商编码
     *
     * @param unknown $should_addup
     */
    protected function summary_should_addup($should_addup)
    {
        $sku_name = [];
        foreach ($should_addup as $key => $info)
        {
            //名称映射
            $sku_name[$info['sku']] = ['sku_name' => $info['sku_name'], 'is_boutique' => $info['is_boutique'], 'supplier_code' => $info['supplier_code']];

            //记录汇总
            if (!isset($summary_should_addup[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']]))
            {
                $tmp = [
                        'gids' => $info['gid'],
                        'sku'  => $info['sku'],
                ];
                foreach ($this->_summary_rule as $col => $cfg)
                {
                    $tmp[$cfg['alias']] = $col == 'require_qty' ? max(intval($info[$col]), 0) : $info[$col];
                }
                $summary_should_addup[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']] = $tmp;
                continue;
            }
            //设置了进行汇总
            foreach ($this->_summary_rule as $col => $cfg)
            {
                switch ($cfg['method'])
                {
                    case 'min':
                        $summary_should_addup[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']][$cfg['alias']] = min($info[$col], $summary_should_addup[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']][$cfg['alias']]);
                        break;
                    case 'sum':
                        $summary_should_addup[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']][$cfg['alias']] += max(intval($info[$col]), 0);
                        break;
                }
            }
        }
        $this->_sku_name_map = $sku_name;

        //记录汇总但本次为0的sku
        $unassign_sku_key = [];
        $unassign_track_list = $this->get_unassign_track_list();

        $total_require_qty = $this->_summary_rule['require_qty']['alias'];
        foreach ($summary_should_addup as $sku => $refund_tax_info)
        {
            foreach ($refund_tax_info as $is_refund_tax => $warehouse_info)
            {
                foreach ($warehouse_info as $purchase_warehouse_id => $info)
                {
                    if ($info[$total_require_qty] == 0)
                    {
                        $unassign_sku_key[] = implode('_', [$sku, $is_refund_tax, $purchase_warehouse_id]);
                        unset($summary_should_addup[$sku][$is_refund_tax][$purchase_warehouse_id]);
                    }
                }
                if (empty($summary_should_addup[$sku][$is_refund_tax]))
                {
                    unset($summary_should_addup[$sku][$is_refund_tax]);
                }
            }
            if (empty($summary_should_addup[$sku]))
            {
                unset($summary_should_addup[$sku]);
            }
        }

        //写入
        $remain_seconds = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('Y'))) - time();
        $this->set_unassign_track_list(array_merge($unassign_track_list, $unassign_sku_key), self::$s_unassign_sku_key.date('Y-m-d'), $remain_seconds);

        return $summary_should_addup;
    }

    /**
     * 获取需要汇总的列表
     *
     * @override
     * {@inheritDoc}
     * @see AbstractSummary::get_ready_addup()
     */
    protected function get_ready_addup($should_addup)
    {
        return $this->summary_should_addup($should_addup);
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
     * 回滚is_addup状态
     * @param unknown $gids
     */
    protected function update_rollback($gids)
    {
        //RPC
        foreach ($replace_data['gid'] as $gid)
        {
            $rpc_params[] = [
                    'gid' => $gid,
                    'is_addup' => PR_IS_ADDUP_NO
            ];
        }
        return RPC_CALL('YB_J3_FBA_002', $rpc_params);
    }

    /**
     * 更新列表的is_addup装填
     */
    protected function update_addup_state($db)
    {
        //RPC
        foreach ($replace_data['gid'] as $gid)
        {
            $rpc_params[] = [
                    'gid' => $gid,
                    'is_addup' => PR_IS_ADDUP_YES
            ];
        }

        $affect_rows = RPC_CALL('YB_J3_FBA_002', $rpc_params);

        if (!$affect_rows)
        {
            $message = '批量更新需求列表失败，返回影响行数'.$affect_rows;
            log_message('ERROR', sprintf('批量插入汇总列表失败，返回影响行数%d,实际应该：%d, 数据：%s', count($replace_data['gid']), $affect_rows, json_encode($replace_data['insert'])));
            throw new \RuntimeException($message, 500);
        }
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
                //sprintv1.1.2 逐行update变更为multi_update执行
                $affect_rows = $db->update_batch_more_where($this->_summary_model->getTable(), $replace_data['update'], 'gid');
                if ($affect_rows != count($replace_data['update']))
                {
                    $message = sprintf('更新汇总列表失败，预期更新%d条记录，实际更新%d条， 选择的记录已经被其他用户修改，请筛选后后重试', count($replace_data['update']), $affect_rows);
                    log_message('ERROR', $message.',数据：'.json_encode($replace_data['update']));
                    throw new \RuntimeException($message, 500);
                }
                $report_affect_row += $affect_rows;
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

            //更新状态交给外围事务

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
     * 未赋值的列表
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
            return is_string($unassign_list) && $unassign_list == 'NONE' ? [] : $unassign_list;
        }

        $ci->load->model('Fba_pr_track_list_model', 'fba_track_list', false, 'fba');

        $group_sql = sprintf(
            'SELECT sku, is_refund_tax, purchase_warehouse_id, min(expect_exhaust_date) as earliest_exhaust_date '.
            ' FROM %s WHERE created_at > %d AND created_at < %d AND sum_sn = "" '.
            ' GROUP BY sku, is_refund_tax, purchase_warehouse_id',
            $ci->fba_track_list->getTable(),
            $today_start,
            $today_end
        );

        $rows = $ci->fba_track_list->getDatabase()->query($group_sql)->result_array();
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
        return $cache_rows == 'NONE' ? [] : $cache_rows;
    }

}