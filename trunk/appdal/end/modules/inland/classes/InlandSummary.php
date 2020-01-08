<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractSummary.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 汇总
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-21
 * @link
 * @throw
 */
class InlandSummary extends AbstractSummary implements Rpcable
{

    use Rpc_imples;

    /**
     * rpc 执行的模块名
     *
     * @var string
     */
    private $_rpc_module = 'inland';

    /**
     *
     * {@inheritDoc}
     * @see AbstractSummary::set_by_default()
     */
    public function set_manual_by_default($model)
    {
        $col_to_method = [
                'require_qty'         => ['method' => 'sum', 'alias' => 'total_required_qty', 'name' => '总需求数量'],
                'weight_sale_pcs'     => ['method' => 'sum', 'alias' => 'weight_sale_pcs', 'name' => '加权日均销量'],
                'max_stock_qty'       => ['method' => 'sum', 'alias' => 'max_stock_qty', 'name' => '加权日均销量'],
        ];
        $group_by_cols = 'sku, is_refund_tax, purchase_warehouse_id';
        return $this->set_list_models($model)->set_summary_col_rule($col_to_method)->set_group_by($group_by_cols);
    }

    /**
     * 对指定的列表进行汇总生成汇总数据, 增加了是否退税，采购仓库两个维度
     * 国内特殊备货无过期时间，如果新创建则以当天为准，如果是更新，不计入
     *
     * @param unknown $should_addup
     */
    protected function summary_should_addup($should_addup)
    {
        $sku_name = [];
        foreach ($should_addup as $key => $info)
        {
            //名称映射
            $sku_name[$info['sku']] = ['sku_name' => $info['sku_name']];

            //记录汇总
            if (!isset($summary_should_addup[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']]))
            {
                $tmp = [
                        'gids' => $info['gid'],
                        'sku'  => $info['sku'],
                ];
                foreach ($this->_summary_rule as $col => $cfg)
                {
                    $tmp[$cfg['alias']] = $info[$col];
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
                        $summary_should_addup[$info['sku']][$info['is_refund_tax']][$info['purchase_warehouse_id']][$cfg['alias']] += $info[$col];
                        break;
                }
            }
        }
        $this->_sku_name_map = $sku_name;
        return $summary_should_addup;
    }

    /**
     * v1.1.0 增加自动情况
     * 获取需要汇总的列表
     *
     * @override
     * {@inheritDoc}
     * @see AbstractSummary::get_ready_addup()
     */
    protected function get_ready_addup($should_addup)
    {
        return $this->_summary_type == AbstractSummary::SUMMARY_MANUAL ? $this->summary_should_addup($should_addup) : parent::get_ready_addup([]);

        /*if ($this->is_rpc($this->_rpc_module))
        {
            return $this->summary_should_addup($should_addup);
        }
        return parent::get_ready_addup($should_addup);*/
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
     * 重写build逻辑， 因v1.1.0 的国内特殊无期望日期。
     * v1.1.1 国内需求自动审核生成汇总单的时候需要追加前一天上传的国内手工单汇总记录
     * 国内手动汇总日期就写入到前一天中
     *
     * {@inheritDoc}
     * @see AbstractSummary::build()
     */
    protected function build($should_addup_rows, $sku_map_name)
    {
        //取sku
        $skus = array_keys($should_addup_rows);

        //取汇总时间
        $created_date = $this->_summary_type == AbstractSummary::SUMMARY_MANUAL ? date('Y-m-d', strtotime('+1 days')) :  date('Y-m-d');

        //根据gid取sku的名字，没有进行分组，是因为sku中文名可能变化
        $db           = $this->_summary_model->getDatabase();

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
                    if ($is_add)
                    {
                        $info['gid'] = $this->_summary_model->gen_id();
                        $info['sum_sn'] = $this->general_sum_sn($this->_scene);
                        $info['sku_name'] = mb_substr($sku_map_name[$sku]['sku_name'], 0, 255);
                        $info['created_date'] = $created_date;
                        $info['created_at'] = $time;
                        $info['updated_at'] = $time;
                        $info['is_refund_tax'] = $is_refund_tax;
                        $info['purchase_warehouse_id'] = $purchase_warehouse_id;
                        //重写以支持expect_exhaust_date的统计逻辑
                        if ($this->_summary_type == AbstractSummary::SUMMARY_MANUAL)
                        {
                            $info['earliest_exhaust_date'] = $now_date;
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

                        $this->_addup_map_sum_sn[$sku][$is_refund_tax][$purchase_warehouse_id] = $existed_row['sum_sn'];

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
                        $params['created_at'] = $time;
                        $params['updated_at'] = $time;
                        //重写以支持expect_exhaust_date的统计逻辑
                        if ($this->_summary_type == AbstractSummary::SUMMARY_MANUAL)
                        {
                            $non_summary_cols = ['expect_exhaust_date' => 'earliest_exhaust_date'];
                            foreach ($non_summary_cols as $col => $sum_col)
                            {
                                $params[$col] = $existed_row[$sum_col];
                            }
                        }
                        $update[] = ['where' => $where, 'update' => $this->_summary_model->fetch_table_cols($params)];
                        $one_log['context'] = sprintf('更新：%s', implode(',', $log_item));

                        $log[] = $one_log;
                    }

                    $gid_update = array_merge($gid_update, explode(',', $info['gids']));
                }
            }
        }
        return ['insert' => $insert, 'update' => $update, 'gid' => $gid_update, 'sku' => $sku_map_name, 'log' => $log];
    }

    /**
     * 执行更新,
     * v1.1.0 增加国内特殊汇总时，无期望时间时默认为当天， 汇总条件是没有设置这个值，所以这里需要对这个值单独处理
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
                    $message = sprintf('更新汇总列表失败，预期更新%d条记录，实际更新%d条， 选择的记录已经被其他用户修改，请筛选后后重试', count($replace_data['update']), $affect_rows);
                    log_message('ERROR', $message.',数据：%s', json_encode($replace_data['update']));
                    throw new \RuntimeException($message, 500);
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

}