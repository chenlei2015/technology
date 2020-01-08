<?php

/**
 * 审核
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-21
 * @link
 * @throw
 */
class OverseaApprove
{

    /**
     * 选择的gid
     * @var unknown
     */
    private $gids;

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
    private $_report;

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
     * 更新跟踪列表
     *
     * @var unknown
     */
    private $_batch_track_update;

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
     * common db handle
     *
     * @var unknown
     */
    private $_other_db;

    /**
     *
     * @var unknown
     */
    private $_model;


    private $_todo_count;

    /**
     * 审核层级
     *
     * @var unknown
     */
    private $_level;

    /**
     *
     * @return FbaApproveFirst
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        return $this;
    }

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
     *
     * @param unknown $db
     */
    public function set_model($model)
    {
        $this->_model = $model;
        //other_db
        $this->_other_db = $this->_model->getDatabase();
        return $this;
    }

    /**
     * 设置勾选的gid
     * @param unknown $gids
     */
    public function set_selected_gids($gids)
    {
        $this->gids = (array)$gids;
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
        $this->_report = [
                'total' => count($this->gids),
                'ignore' => count($this->gids) - $this->_todo_count,
                'succ' => 0,
                'fail' => 0,
                'msg' => '选择了%d条记录，处理成功%d条, 处理失败%d条，无需处理%d条',
                //-1 没有执行，默认 -2 执行但失败， >0 汇总执行sku的数量
                'addup' => -1,
        ];
    }

    protected function _get_next_state($approve_result, $is_plan_approve = null)
    {
        switch ($this->_level)
        {
            case '一':
                if ($approve_result == APPROVAL_RESULT_PASS)
                {
                    $next_state = intval($is_plan_approve) == NEED_PLAN_APPROVAL_NO ? APPROVAL_STATE_SUCCESS : APPROVAL_STATE_SECOND;
                }
                else
                {
                    $next_state = APPROVAL_STATE_FAIL;
                }
                break;
            case '二':
                $next_state = $approve_result == APPROVAL_RESULT_PASS ? APPROVAL_STATE_SUCCESS : APPROVAL_STATE_FAIL;
                break;
        }
        return $next_state;
    }

    /**
     * 接收代做列表
     *
     * @param unknown $todo
     */
    public function recive($todo)
    {
        //初始化报告
        $this->init_report($todo);
        if (empty($todo))
        {
            $this->_finish_report(true);
            return $this;
        }

        //分离参数
        $time = time();
        $active_user = get_active_user();
        $this->_ci->load->classes('oversea/classes/PrState');
        $this->_batch_update = $this->_batch_log = $this->_addups = [];
        foreach ($todo as $key => $row)
        {
            $next_state = $this->_get_next_state($this->_result, $row['is_plan_approve'] ?? NULL);
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
            ];
            //记录日志
            $this->_batch_log[] = [
                    'gid' => $row['gid'],
                    'context' => sprintf('站点列表%s执行汇总操作', $row['pr_sn'])
            ];
            //汇总
            if ($next_state == APPROVAL_STATE_SUCCESS)
            {
                $this->_addups[] = $row;
                $update_params['is_addup'] = PR_IS_ADDUP_YES;
            }

            $where = [
                    'gid'           => $row['gid'],
                    'approve_state' => $row['approve_state'],
                    'is_addup'      => $row['is_addup']
            ];

            $this->_batch_update[] = ['where' => $where, 'update' => $update_params];
        }
        //并发情况下，这里的insert会导致重复问题
        if (!empty($this->_addups))
        {
            $this->_ci->load->service('oversea/PrTrackService');
            list($this->_batch_track_insert, $this->_batch_track_log_insert, $this->_batch_track_update) = $this->_ci->prtrackservice->create_track_list($this->_addups);
        }

        $todo = null;
        unset($todo);

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
        $log_context = sprintf('FBA批量%s级审核选择：%s', $this->_level,  is_array($this->gids) ? implode(',', $this->gids) : '没有选择gid');
        $this->_ci->systemlogservice->send([], $module, $log_context . $this->_report['msg']);
    }

    /**
     * 海外审核跨库
     *
     * 事务开始：
     * stock为主事务（所有stock的更新必须先行）：
     * 汇总日志，汇总列表(stock)
     * 跟踪记录插入，跟踪更新， 跟踪日志(stock)
     * 站点列表日志(stock)
     * 站点列表更新(common)
     *
     * 事务结束；
     */
    public function update_approve_succ()
    {
        if ($this->_todo_count == 0)
        {
            return true;
        }
        try
        {
            $this->_ci->load->service('oversea/PrLogService');
            $this->_ci->load->service('oversea/PrTrackLogService');

            $this->_ci->load->model('Oversea_pr_list_log_model', 'oversea_pr_list_log', false, 'oversea');
            $my_db = $this->_ci->oversea_pr_list_log->getDatabase();

            //stock事务开启
            $my_db->trans_start();

            $update_sum_sn = [];
            if (!empty($this->_addups))
            {
                //更新汇总
                $this->_ci->load->service('oversea/PrSummaryService');
                $report['addup'] = $this->_ci->prsummaryservice->summary($this->_addups);
                $update_sum_sn = $this->_ci->OverseaSummary->get_addup_map_sum_sn();
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

            if (!empty($this->_batch_track_update))
            {
                //更新跟踪列表
                $this->_ci->prtrackservice->update_batch($this->_batch_track_update, 'pr_sn');
            }

            //批量发送日志
            if (!empty($this->_batch_log))
            {
                $this->_ci->prlogservice->multi_send($this->_batch_log);
            }

            //批量更新状态
            /*if (!$this->_model->batch_update_compatible($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('执行站点列表审核操作，批量更新失败'), 500);
            }*/

            //common库事务开始
            $this->_other_db->trans_start();

            //批量更新状态
            $affect_rows = $this->_other_db->update_batch_more_where($this->_model->getTable(), $this->_batch_update, 'gid');
            if ($affect_rows != count($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('更新海外站点列表失败，预期更新%d条记录，实际更新%d条，选择的记录已经被其他用户修改，请筛选后后重试', count($this->_batch_update), $affect_rows), 500);
            }

            $this->_other_db->trans_complete();

            if ($this->_other_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行站点列表审核操作，更新common库事务提交成功，但状态检测为false'), 500);
            }

            $my_db->trans_complete();

            if ($my_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行站点列表审核操作，事务提交成功，但状态检测为false'), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行站点列表审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行站点列表审核操作，批量更新失败'), 500);
        }
        catch (\Throwable $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行站点列表审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行站点列表审核操作，批量更新失败'), 500);
        }
    }

    /**
     * 审核失败， 全部操作只有发送日志、更新状态、发送系统日志三个操作
     * @deprecated v1.1.2 需求已无审核失败操作
     */
    protected function update_approve_fail()
    {
        if ($this->_todo_count == 0)
        {
            return true;
        }

        try
        {

            $this->_ci->load->service('oversea/PrLogService');

            $this->_other_db->trans_start();

            //批量发送日志
            $this->_ci->prlogservice->multi_send($this->_batch_log);

            //批量更新状态
            if (!$this->_model->batch_update_compatible($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，批量更新失败', $this->_level), 500);
            }

            $this->_other_db->trans_complete();

            if ($this->_other_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，事务提交成功，但状态检测为false', $this->_level), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_report['fail'] = $this->_todo_count;
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

    /**
     * 清空
     */
    public function clean()
    {
        $this->gids = $this->_result = $this->_addups = $this->_batch_log = [];
        $this->_batch_track_log_insert = $this->_batch_update = $this->_batch_track_update = [];
    }

    private function _finish_report($pass = true)
    {
        $pass ? $this->_report['succ'] = $this->_todo_count : $this->_report['fail'] = $this->_todo_count;
        $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
    }



}
