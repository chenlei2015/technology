<?php

/**
 * fba全量审核
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-10-05
 * @link
 * @throw
 */
class FbaApproveAll
{

    /**
     * 审核结果
     * @var unknown
     */
    private $_result;

    /**
     * 执行结果
     *
     * @var unknown
     */
    private $_report = [
            'total'  => 0,
            'ignore' => 0,
            'succ'   => 0,
            'fail'   => 0,
            'addup'  => -1,
            'errorMess' => '',
    ];

    /**
     * 汇总记录
     *
     * @var unknown
     */
    private $_addups;

    /**
     * 更新参数， 以不同的next_state为状态
     *
     * @var unknown
     */
    private $_batch_update;

    /**
     * 更新日志
     *
     * @var unknown
     */
    private $_batch_log;

    /**
     * 生成跟踪列表
     *
     * @var unknown
     */
    private $_batch_track_insert;

    /**
     * 生成日志
     * @var unknown
     */
    private $_batch_track_log_insert;

    /**
     *
     * @var unknown
     */
    private $_ci;

    /**
     * db
     *
     * @var unknown
     */
    private $_stock_db;

    /**
     * common db
     * @var unknown
     */
    private $_common_db;

    /**
     *
     * @var unknown
     */
    private $_model;

    /**
     * 审核层级
     *
     * @var unknown
     */
    private $_level;

    /**
     * 权限范围
     *
     * @var unknown
     */
    private $_manager_accounts;

    /**
     * 审核失败勾选的gid对应的sku主被动信息
     * @var unknown
     */
    private $_trigger_approve_fail;

    private $_todo_count = 0;

    /**
     *
     * @return FbaApproveFirst
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        return $this;
    }

    /**
     * 设置审核等级
     *
     * @param string $level
     * @return FbaApproveAll
     */
    public function set_approve_level($level)
    {
        $this->_level = $level;
        return $this;
    }

    /**
     * 设置审核结果
     */
    public function set_approve_result($approve_result)
    {
        $this->_result = $approve_result;
        return $this;
    }


    /**
     * @param Model $model
     * @return FbaApproveAll
     */
    public function set_fba_model($model)
    {
        $this->_model = $model;
        $this->_common_db = $this->_model->getDatabase();
        return $this;
    }

    public function set_manager_accounts($manager_accounts)
    {
        $this->_manager_accounts = $manager_accounts;
        return $this;
    }

    /**
     * 初始化report
     *
     * @param unknown $todo
     */
    protected function init_report($todo)
    {
        $this->_todo_count = count($todo);
        $this->_report['total'] += $this->_todo_count;
    }

    protected function _get_next_state($result, $is_plan_approve = null, $is_first_sale)
    {
        $result = intval($result);
        switch ($this->_level)
        {
            case '一':
                if ($result == APPROVAL_RESULT_PASS)
                {
                    if ($is_first_sale == FBA_FIRST_SALE_YES) {
                        return APPROVAL_STATE_SUCCESS;
                    }
                    $next_state = intval($is_plan_approve) == NEED_PLAN_APPROVAL_NO ? APPROVAL_STATE_SUCCESS : APPROVAL_STATE_SECOND;
                }
                else
                {
                    $next_state = APPROVAL_STATE_FAIL;
                }
                break;
            case '二':
                $next_state = $result == APPROVAL_RESULT_PASS ? APPROVAL_STATE_THREE : APPROVAL_STATE_FAIL;
                break;
            case '三':
                $next_state = $result == APPROVAL_RESULT_PASS ? APPROVAL_STATE_SUCCESS : APPROVAL_STATE_FAIL;
                break;
        }
        return $next_state;
    }

    /**
     * 接收代做列表,分析
     *
     * @param array $todo
     *
     * @return FbaApproveFirst
     */
    public function recive($todo)
    {
        //清空资源
        $this->_automatic_clean();

        $this->init_report($todo);

        if (empty($todo))
        {
            $this->_finish_report(true);
            return $this;
        }

        //分离参数
        $time = time();
        $active_user = get_active_user();
        property_exists($this->_ci, 'PrState') OR $this->_ci->load->classes('fba/classes/PrState');

        if ($this->_result == APPROVAL_RESULT_FAILED)
        {
            $trigger_sku_summary =& $this->_model->get_trigger_sku_summary_info(array_column($todo, 'sku'));
        }
        foreach ($todo as $key => $row)
        {
            $next_state = $this->_get_next_state($this->_result, $row['is_plan_approve'] ?? NULL, $row['is_first_sale']);
            $can_jump = $this->_ci->PrState->from($row['approve_state'])->go($next_state)->can_jump();
            if (!$can_jump)
            {
                $this->_report['fail'] ++;
                continue;
            }

            //汇总更新参数
            $update_params = [
                    'approve_state' => $next_state,
                    'approved_at'   => $time,
                    'approved_uid'  => $active_user->staff_code,
                    'updated_uid'   => $active_user->staff_code,
                    'updated_at'    => $time,
            ];

            //记录日志
            $this->_batch_log[] = [
                    'gid' => $row['gid'],
                    'context' => sprintf('%s级审核需求单：%s 审核%s', $this->_level, $row['pr_sn'], $next_state == APPROVAL_STATE_FAIL ? '失败' : '通过')
            ];

            //生成所需记录
            $row['max_stock_qty'] = $row['min_order_qty'] * $row['weight_sale_pcs'];

            //汇总
            if ($next_state == APPROVAL_STATE_SUCCESS)
            {
                $this->_addups[] = $row;
                $update_params['is_addup'] = PR_IS_ADDUP_YES;
            }
            elseif ($next_state == APPROVAL_STATE_FAIL)
            {
                $update_params['trigger_mode'] = TRIGGER_MODE_NONE;

                //因审核失败触发 主动sku被取消的情况（需要排除本次勾选审核失败的）
                //所有的主动记录都被审核失败
                $active_trigger_gids = $trigger_sku_summary[$row['sku']][TRIGGER_MODE_ACTIVE]['other_gids'] ?? '';
                if ($active_trigger_gids != '')
                {

                    if (($index = array_search($row['gid'], $gid_arr = explode(',', $active_trigger_gids))) !== false)
                    {
                        unset($gid_arr[$index]);
                        $trigger_sku_summary[$row['sku']][$row['trigger_mode']]['other_gids'] = implode(',', $gid_arr);
                        $trigger_sku_summary[$row['sku']][$row['trigger_mode']]['other_num'] -= 1;
                    }
                }
            }

            $where = [
                    'gid'           => $row['gid'],
                    'approve_state' => $row['approve_state'],
                    'is_addup'      => $row['is_addup']
            ];
            $this->_batch_update[] = ['where' => $where, 'update' => $update_params];
        }

        if ($next_state == APPROVAL_STATE_FAIL)
        {
            foreach ($trigger_sku_summary as $sku => $info)
            {
                $history_has_active = ($info[TRIGGER_MODE_ACTIVE]['num'] ?? 0) > 0;
                $all_approve_fail = ($info[TRIGGER_MODE_ACTIVE]['other_num'] ?? 1) == 0;
                if ($history_has_active && $all_approve_fail)
                {
                    $inactive_gids = $info[TRIGGER_MODE_INACTIVE]['gids'] ?? [];
                    if (!empty($inactive_gids))
                    {
                        foreach (explode(',', $inactive_gids) as $gid)
                        {
                            $this->_batch_update[] = [
                                    'where' => [
                                            'gid'           => $row['gid'],
                                    ],
                                    'update' => [
                                            'is_trigger_pr' => TRIGGER_PR_NO,
                                            'trigger_mode'  => TRIGGER_MODE_NONE,
                                            'updated_uid'   => $active_user->staff_code,
                                            'updated_at'   => $time,
                                            'is_lost_active_trigger' => TRIGGER_LOST_ACTIVE_YES,
                                    ]
                            ];

                            $this->_batch_log[] = [
                                    'gid' => $gid,
                                    'context' => sprintf('%s级审核需求单：%s 审核%s，无主动触发需求，被强制设置为不触发', $this->_level, $row['pr_sn'], $next_state == APPROVAL_STATE_FAIL ? '失败' : '通过')
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($this->_addups))
        {
            //生成跟踪
            $this->_ci->load->service('fba/PrTrackService');
            list($this->_batch_track_insert, $this->_batch_track_log_insert) = $this->_ci->prtrackservice->create_track_list($this->_addups);
        }

        if (isset($trigger_sku_summary))
        {
            $trigger_sku_summary = NULL;
            unset($trigger_sku_summary);
        }

        return $this;
    }

    /**
     * 执行
     */
    public function run()
    {
        if ($this->_result == APPROVAL_RESULT_PASS)
        {
            return $this->update_approve_succ();
        }
        else
        {
            return $this->update_approve_fail();
        }
    }

    /**
     * 报告
     * @return unknown
     */
    public function report()
    {
        return $this->_report;
    }

    /**
     * 发送系统日志
     */
    public function send_system_log($module)
    {
        $this->_ci->load->service('basic/SystemLogService');
        $log_context = sprintf('FBA批量%s级审核选择：%s', $this->_level, implode(',', $this->gids));
        $this->_ci->systemlogservice->send([], $module, $log_context . $this->_report['msg']);
    }

    /**
     *
     * @throws \RuntimeException
     * @return boolean
     */
    public function update_approve_succ()
    {
        if ($this->_todo_count == 0)
        {
            return true;
        }

        //pr($this->_batch_track_insert);
        //pr($this->_batch_update);
        //exit;

        try
        {
            property_exists($this->_ci, 'prlogservice') OR $this->_ci->load->service('fba/PrLogService');
            property_exists($this->_ci, 'prtracklogservice') OR $this->_ci->load->service('fba/PrTrackLogService');
            property_exists($this->_ci, 'fba_pr_list_log') OR $this->_ci->load->model('Fba_pr_list_log_model', 'fba_pr_list_log', false, 'fba');

            $this->_stock_db = $this->_ci->fba_pr_list_log->getDatabase();

            $this->_stock_db->trans_start();
            $this->_common_db->trans_start();

            //批量发送日志
            if (!empty($this->_batch_log))
            {
                $this->_ci->prlogservice->multi_send($this->_batch_log);
            }

            $update_sum_sn = [];
            if (!empty($this->_addups))
            {
                //更新汇总
                property_exists($this->_ci, 'prsummaryservice') OR $this->_ci->load->service('fba/PrSummaryService');
                $report['addup'] = $this->_ci->prsummaryservice->summary($this->_addups);
                $update_sum_sn = $this->_ci->FbaSummary->get_addup_map_sum_sn();
            }

            if (!empty($this->_batch_track_insert))
            {
                //根据回传信息更新汇总单
                if (!empty($update_sum_sn))
                {
                     foreach ($this->_batch_track_insert as $key => &$row)
                     {
                         $row['sum_sn'] = $update_sum_sn[$row['sku']][$row['is_refund_tax']][$row['purchase_warehouse_id']] ?? '';
                     }
                }

                //创建跟踪
                $this->_ci->prtrackservice->insert_batch($this->_batch_track_insert);
            }

            if (!empty($this->_batch_track_log_insert))
            {
                //创建跟踪日志
                $this->_ci->prtracklogservice->insert_batch($this->_batch_track_log_insert);
            }

            //批量更新状态
            $affect_rows = $this->_common_db->update_batch_more_where($this->_model->getTable(), $this->_batch_update, 'gid');
            if ($affect_rows != count($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，预期更新%d条记录，实际更新%d条，选择的记录已经被其他用户修改，请筛选后后重试', $this->_level, count($this->_batch_update), $affect_rows), 500);
            }

            $this->_common_db->trans_complete();
            if ($this->_common_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，更新common库事务提交成功，但状态检测为false', $this->_level), 500);
            }

            $this->_stock_db->trans_complete();
            if ($this->_stock_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，事务提交成功，但状态检测为false', $this->_level), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行%s级审核操作，抛出异常： %s', $this->_level, $e->getMessage()));
            throw new \RuntimeException(sprintf('执行%s级审核操作，批量更新失败', $this->_level), 500);
        }
        catch (\Throwable $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行%s级审核操作，抛出异常： %s', $this->_level, $e->getMessage()));
            throw new \RuntimeException(sprintf('执行%s级审核操作，批量更新失败', $this->_level), 500);
        }
    }

    /**
     *
     * 审核失败， 全部操作只有发送日志、更新状态、发送系统日志三个操作
     *
     * v1.1.2
     *
     * 增加审核失败之后，同时检测缺少主动sku 被动的sku转为不触发的情况
     *
     * 1. 更新列表状态 2. 插入日志
     *
     */
    protected function update_approve_fail()
    {
        if ($this->_todo_count == 0)
        {
            return true;
        }

        try
        {
            $this->_ci->load->service('fba/PrLogService');

            $this->_common_db->trans_start();

            //批量更新状态,主被动都在这里
            if (!$this->_common_db->update_batch_more_where($this->_model->getTable(), $this->_batch_update, 'gid'))
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，批量更新失败', $this->_level), 500);
            }

            //批量发送日志
            $this->_ci->prlogservice->multi_send($this->_batch_log);

            $this->_common_db->trans_complete();

            if ($this->_common_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，事务提交成功，但状态检测为false', $this->_level), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_report['fail'] += $this->_todo_count;
            $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
            log_message('ERROR', sprintf('执行%s级审核操作，抛出异常： %s', $this->_level, $e->getMessage()));
            throw new \RuntimeException(sprintf('执行%s级审核操作，批量更新失败', $this->_level), 500);
        }
        catch (\Throwable $e)
        {

            $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
            log_message('ERROR', sprintf('执行%s级审核操作，抛出异常： %s', $this->_level, $e->getMessage()));
            throw new \RuntimeException(sprintf('执行%s级审核操作，批量更新失败', $this->_level), 500);
        }
    }

    private function _finish_report($ignore = true)
    {
        if ($ignore) {
            $this->_report['ignore'] += $this->_todo_count;
        } else {
            $this->_report['succ'] += $this->_todo_count;
        }
        return $this;
    }

    private function _automatic_clean()
    {
        $this->_todo_count = 0;
        $this->_batch_track_insert = [];
        $this->_batch_track_log_insert = [];
        $this->_batch_log = [];
        $this->_batch_update = [];
        $this->_addups = [];
    }


}