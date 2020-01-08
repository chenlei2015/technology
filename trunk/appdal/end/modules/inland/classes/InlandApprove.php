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
class InlandApprove
{

    /**
     * 自动确认
     *
     * @var integer
     */
    const INLAND_APPROVE_AUTOMATIC = 1;

    /**
     * 手动确认
     *
     * @var integer
     */
    const INLAND_APPROVE_MANUAL = 2;

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
    private $_other_db;

    /**
     *
     * @var unknown
     */
    private $_model;

    /**
     * 代做
     * @var integer
     */
    private $_todo_count = 0;

    /**
     * 选择的gid
     * @var unknown
     */
    private $_gids = [];

    /**
     * 设置审核结果
     *
     * @var array
     */
    private $_result = [];

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
     *
     * @param unknown $db
     */
    public function set_model($model)
    {
        $this->_model = $model;
        //other_db
        $this->_common_db = $this->_model->getDatabase();
        return $this;
    }

    /**
     * 设置勾选的gid
     * @param unknown $gids
     */
    public function set_selected_gids($gids)
    {
        $this->_gids = (array)$gids;
        return $this;
    }

    /**
     * 初始化report
     *
     * @param unknown $todo
     */
    protected function init_report()
    {
        $this->_report = [
                'total' => count($this->_gids),
                'ignore' => (count($this->_gids) - $this->_todo_count),
                'succ' => 0,
                'fail' => 0,
                'msg' => '选择了%d条记录，处理成功%d条, 处理失败%d条，无需处理%d条',
                'addup' => -1,
        ];
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
     * 接收代做列表
     *
     * @param unknown $todo
     */
    public function recive($todo)
    {
        //初始化报告
        $this->_todo_count = count($todo);
        $this->init_report();
        if (empty($todo))
        {
            $this->_finish_report(true);
            return $this;
        }

        //分离参数
        $time = time();
        $active_user = get_active_user();
        $this->_ci->load->classes('inland/classes/PrSpecialState');

        foreach ($todo as $key => $row)
        {
            $next_state = $this->_get_next_state($this->_result);
            $can_jump = $this->_ci->PrSpecialState->from($row['approve_state'])->go($next_state)->can_jump();

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
                    'context' => sprintf('审核需求单：%s 审核%s', $row['pr_sn'], $next_state == SPECIAL_CHECK_STATE_FAIL ? '失败' : '通过')
            ];
            //汇总
            if ($next_state == SPECIAL_CHECK_STATE_SUCCESS)
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
        if (!empty($this->_addups))
        {
            //生成跟踪
            $this->_ci->load->service('inland/PrTrackService');
            list($this->_batch_track_insert, $this->_batch_track_log_insert) = $this->_ci->prtrackservice->create_track_list($this->_addups);
            //手动模式下的期望日期给当天
            foreach ($this->_batch_track_insert as $key => &$val)
            {
                $val['expect_exhaust_date'] = date('Y-m-d');
            }
        }

        return $this;
    }

    /**
     * 自动审核， 状态正常情况下一定的审核成功，不符合条件的需求数据不处理。
     *
     * @param unknown $pr_gids ['sku' => [..gids...]
     * @param array sku, is_refund_tax, purchase_warehouse_id对应汇总单号的数组
     * @return InlandApprove
     */
    protected function recive_automatic($pr_gids, $update_sum_sn)
    {
        $this->_ci->load->model('Inland_pr_list_model', 'm_inland_pr_list', false, 'inland');
        $this->_ci->load->model('Inland_pr_track_list_model', 'm_inland_pr_track', false, 'inland');

        $this->_gids       = $pr_gids;
        $time              = time();
        $user_info         = get_active_user()->get_user_info();
        $todo              = $this->_model->get_automatic_pr_by_gids($this->_gids);
        $this->_todo_count = count($todo);
        $this->init_report();

        foreach ($todo as $key => $row)
        {
            if ($row['is_trigger_pr'] != TRIGGER_PR_YES)
            {
                $this->_report['ignore'] ++;
            }
            //pr更新参数
            /*$this->_batch_update[SPECIAL_CHECK_STATE_SUCCESS][] = [
                    'gid'           => $row['gid'],
                    'updated_at'    => $time,
                    'is_addup'      => PR_IS_ADDUP_YES,
                    'approve_state' => SPECIAL_CHECK_STATE_SUCCESS
            ];*/
            $this->_batch_update[] = [
                    'where' => [
                            'gid'           => $row['gid'],
                            'is_addup'      => PR_IS_ADDUP_NO
                    ],
                    'update' => [
                            'updated_at'    => $time,
                            'is_addup'      => PR_IS_ADDUP_YES,
                            'approve_state' => SPECIAL_CHECK_STATE_SUCCESS
                    ]
            ];
            //记录日志
            $this->_batch_log[] = [
                    'gid' => $row['gid'],
                    'context' => sprintf('自动审核需求单：%s 审核成功', $row['pr_sn'])
            ];

            $track_gid = $this->_ci->m_inland_pr_track->gen_id();
            //创建跟踪
            $this->_batch_track_insert[] = [
                    'gid' => $track_gid,
                    'pr_sn' => $row['pr_sn'],
                    'sku' => $row['sku'],
                    'is_refund_tax' => $row['is_refund_tax'],
                    'purchase_warehouse_id' => $row['purchase_warehouse_id'],
                    'sku_name' => $row['sku_name'],
                    'require_qty' => $row['require_qty'],
                    'expect_exhaust_date' => $row['expect_exhaust_date'],
                    'stocked_qty' => $row['stocked_qty'],
                    'created_at' => $time,
                    'sum_sn' => $update_sum_sn[$row['sku']][$row['is_refund_tax']][$row['purchase_warehouse_id']]
            ];
            //创建跟踪日志
            $this->_batch_track_log_insert[] = [
                    'gid' => $track_gid,
                    'uid' => $user_info['uid'],
                    'user_name' => $user_info['user_name'],
                    'context' => sprintf('自动创建需求跟踪记录')
            ];
        }

        $todo = NULL;
        unset($todo);

        $this->_report['addup'] = $this->_report['total'] - $this->_report['ignore'];
        $this->_todo_count = $this->_report['addup'];

        return $this;
    }

    /**
     * 获取审核之后的下一个状态
     *
     * @param unknown $approve_result
     * @return string
     */
    protected function _get_next_state($approve_result)
    {
        return $approve_result == APPROVAL_RESULT_PASS ? SPECIAL_CHECK_STATE_SUCCESS : SPECIAL_CHECK_STATE_FAIL;
    }

    /**
     * 执行
     */
    public function run($approve_type = InlandApprove::INLAND_APPROVE_AUTOMATIC)
    {
        if ($approve_type == InlandApprove::INLAND_APPROVE_AUTOMATIC)
        {
            $this->_automatic_clean();
            return $this->_result == APPROVAL_RESULT_PASS ? $this->update_approve_automatic() : $this->update_approve_automatic_fail();
        }
        else
        {
            return $this->update_approve_manual();
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
        $log_context = sprintf('国内特殊批量审核选择：%s', is_array($this->_gids) ? implode(',', $this->_gids) : '没有选择gid');
        $this->_ci->systemlogservice->send([], $module, $log_context . $this->_report['msg']);
    }

    public function set_approve_process_summary($level, $summary_key, $counter, $result)
    {
        property_exists($this->_ci, 'rediss') OR $this->_ci->load->library('Rediss');
        $command = "eval \"redis.call('hincrby', KEYS[1], 'nums', 1); if(tonumber(KEYS[2]) == 1) then redis.call('hincrby', KEYS[1], 'processed', KEYS[3]); return 'SUCC'; else redis.call('hincrby', KEYS[1], 'undisposed', KEYS[3]); end; \" 3 %s %d %d";
        $result = $this->_ci->rediss->eval_command(sprintf($command, $summary_key, $result, $counter));
        log_message('INFO', sprintf('审核统计key： %s 执行结果: %s, eval执行状态：%s', $summary_key, $result ? '成功' : '失败', $result));
        return $result;
    }

    /**
     * 一键审核失败，这里一次更新完，不在分批
     *
     */
    protected function update_approve_automatic_fail()
    {
        $active_user = get_active_user();

        $uid = $active_user->staff_code;
        $username = $active_user->user_name;

        $staff_code = $active_user->staff_code;
        //固定1级
        $level = 1;

        //汇总信息
        $summary_key = 'approve_first_all_summary_inland_'.$staff_code;

        //固定审核失败
        $result = 2;

        list($min_gid, $max_gid) = $this->_model->get_top_bottom_gid();

        property_exists($this->_ci, 'm_inland_pr_list_log') OR $this->_ci->load->model('Inland_pr_list_log_model', 'm_inland_pr_list_log', false, 'inland');

        $commonDB = $this->_common_db;
        $stockDB = $this->_ci->m_inland_pr_list_log->getDatabase();

        try {

            //$stockDB->trans_start(true);

            //插入日志
            $insert_log_sql = sprintf(
                'INSERT INTO yibai_plan_stock.yibai_inland_pr_list_log (gid, uid, user_name, context)
                SELECT gid, "%s", "%s", CONCAT("一键审核需求单", pr_sn, " 审核失败")
                FROM yibai_plan_common.yibai_inland_pr_list
                WHERE approve_state = %d
                AND expired = %d
                AND gid >= "%s" and gid <= "%s"',
                $uid, $username,
                INLAND_APPROVAL_STATE_INIT, FBA_PR_EXPIRED_NO,
                $min_gid, $max_gid
                );

            $result = $stockDB->query($insert_log_sql);
            if (!$result) {
                log_message('ERROR', '国内自动审核插入日志失败');
                //$stockDB->rollback();
                return false;
            }

            $affectRows = $stockDB->affected_rows();

            $this->set_approve_process_summary($level, $summary_key, ceil($affectRows/2), $result);

            //$stockDB->trans_start(true);

            $now = time();

            $update_list_sql = sprintf('
            update yibai_inland_pr_list
            set approve_state = %d, approved_at = %d, approved_uid = "%s", updated_uid = "%s", updated_at = %d
            WHERE approve_state = %d
            AND expired = %d
            AND is_trigger_pr = %d
            AND gid >= "%s" and gid <= "%s"',
                INLAND_APPROVAL_STATE_FAIL, $now, $uid, $uid, $now,
                INLAND_APPROVAL_STATE_INIT, FBA_PR_EXPIRED_NO, TRIGGER_PR_YES,
                $min_gid, $max_gid
                );

            $result = $commonDB->query($update_list_sql);
            if ($result === false) {
                log_message('ERROR', '国内自动审核更新失败');
            }
            $affectRows = $commonDB->affected_rows();

            $this->set_approve_process_summary($level, $summary_key, $affectRows, $result);

            return true;

        } catch (\Throwable $e) {
            log_message('ERROR', '国内自动审核失败操作抛出异常：'.$e->getMessage());
            return false;
        }

    }

    /**
     * 批量分块进行汇总， 然后创建跟踪列表， 写入各自日志， 最后回写列表
     *
     * 要做到幂等。 一次分块是一个事务。
     *
     */
    protected function update_approve_automatic()
    {
        try
        {
            $staff_code = get_active_user()->staff_code;
            //固定1级
            $level = 1;

            //汇总信息
            $summary_key = 'approve_first_all_summary_inland_'.$staff_code;

            //固定审核成功
            $result = 1;

            $this->_ci->load->service('inland/PrTrackService');
            $this->_ci->load->service('inland/PrLogService');
            $this->_ci->load->service('inland/PrTrackLogService');
            $this->_ci->load->service('inland/PrSummaryService');

            $this->_ci->load->model('Inland_pr_list_log_model', 'm_inland_pr_list_log', false, 'inland');
            $my_db = $this->_ci->m_inland_pr_list_log->getDatabase();

            $my_db->trans_start();

            $report['addup'] = $this->_ci->prsummaryservice->automatic_summary();

            //已无需要汇总的记录
            if ($report['addup'] === -1)
            {
                $this->init_report();
                $this->_finish_report();
                $my_db->trans_complete();
                return true;
            }

            $counter = $report['addup'];

            //写入进度信息
            $this->set_approve_process_summary($level, $summary_key, $counter, $result ? 1 : -1);

            //创建跟踪信息
            $this->recive_automatic($this->_ci->InlandSummary->get_addup_map_automatic_gids(), $this->_ci->InlandSummary->get_addup_map_sum_sn());

            if (!empty($this->_batch_track_insert))
            {
                //创建跟踪
                $this->_ci->prtrackservice->insert_batch($this->_batch_track_insert);
            }

            if (!empty($this->_batch_track_log_insert))
            {
                //创建跟踪日志
                $this->_ci->prtracklogservice->insert_batch($this->_batch_track_log_insert);
            }

            //批量发送日志
            if (!empty($this->_batch_log))
            {
                $this->_ci->prlogservice->multi_send($this->_batch_log);
            }

            //批量更新状态
            /*if (!$this->_model->batch_update_compatible($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('执行审核操作，批量更新失败'), 500);
            }*/

            $affect_rows = $this->_common_db->update_batch_more_where($this->_model->getTable(), $this->_batch_update, 'gid');
            if ($affect_rows != count($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('执行审核操作，批量更新失败，预期更新%d条记录，实际更新%d条，选择的记录已经被其他用户修改，请筛选后后重试', count($this->_batch_update), $affect_rows), 500);
            }

            $my_db->trans_complete();

            if ($my_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行审核操作，事务提交成功，但状态检测为false'), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行审核操作，批量更新失败'), 500);
        }
        catch (\Throwable $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行审核操作，批量更新失败'), 500);
        }
    }

    /**
     * 审核失败， 全部操作只有发送日志、更新状态、发送系统日志三个操作
     */
    protected function update_approve_manual()
    {
        if ($this->_todo_count == 0)
        {
            return true;
        }
        if ($this->_result == APPROVAL_RESULT_PASS)
        {
            return $this->update_approve_manual_succ();
        }
        else
        {
            return $this->update_approve_manual_fail();
        }
    }

    protected function update_approve_manual_succ()
    {
        try
        {
            $this->_ci->load->service('inland/PrSpecialLogService');
            $this->_ci->load->service('inland/PrTrackLogService');

            $this->_ci->load->model('Inland_special_pr_list_log_model', 'm_inland_special_list_log', false, 'inland');
            $my_db = $this->_ci->m_inland_special_list_log->getDatabase();

            $my_db->trans_start();

            if (!empty($this->_addups))
            {
                //更新汇总
                $this->_ci->load->service('inland/PrSummaryService');
                $report['addup'] = $this->_ci->prsummaryservice->manual_summary($this->_addups);
                $update_sum_sn = $this->_ci->InlandSummary->get_addup_map_sum_sn();
            }

            if (!empty($this->_batch_track_insert))
            {
                //根据回传信息更新汇总单
                if (!empty($update_sum_sn))
                {
                    foreach ($this->_batch_track_insert as $key => &$row)
                    {
                        $row['sum_sn'] = $update_sum_sn[$row['sku']][$row['is_refund_tax']][$row['purchase_warehouse_id']];
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

            //批量发送日志
            if (!empty($this->_batch_log))
            {
                $this->_ci->prspeciallogservice->multi_send($this->_batch_log);
            }

            //批量更新状态
            /*if (!$this->_model->batch_update_compatible($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('执行审核操作，批量更新国内需求列表失败'), 500);
            }
            */
            $affect_rows = $this->_common_db->update_batch_more_where($this->_model->getTable(), $this->_batch_update, 'gid');
            if ($affect_rows != count($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('执行审核操作，批量更新国内需求列表失败，预期更新%d条记录，实际更新%d条，选择的记录已经被其他用户修改，请筛选后后重试', count($this->_batch_update), $affect_rows), 500);
            }

            $my_db->trans_complete();

            if ($my_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行审核操作，事务提交成功，但状态检测为false'), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行审核操作，批量更新失败'), 500);
        }
        catch (\Throwable $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行审核操作，批量更新失败'), 500);
        }
    }

    /**
     * 审核失败， 全部操作只有发送日志、更新状态、发送系统日志三个操作
     */
    protected function update_approve_manual_fail()
    {
        try
        {
            $this->_ci->load->service('inland/PrSpecialLogService');

            $this->_common_db->trans_start();

            //批量发送日志
            $this->_ci->prspeciallogservice->multi_send($this->_batch_log);

            //批量更新状态
            if (!$this->_model->batch_update_compatible($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('执行审核操作，批量更新失败'), 500);
            }

            $this->_common_db->trans_complete();

            if ($this->_common_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行审核操作，事务提交成功，但状态检测为false'), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_report['fail'] = $this->_todo_count;
            $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
            log_message('ERROR', sprintf('执行审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行审核操作，批量更新失败'), 500);
        }
        catch (\Throwable $e)
        {

            $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
            log_message('ERROR', sprintf('执行审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行审核操作，批量更新失败'), 500);
        }
    }


    private function _finish_report($pass = true)
    {
        $pass ? $this->_report['succ'] = $this->_todo_count : $this->_report['fail'] = $this->_todo_count;
        $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
    }

    private function _automatic_clean()
    {
        $this->_batch_track_insert = [];
        $this->_batch_track_log_insert = [];
        $this->_batch_log = [];
        $this->_batch_update = [];
        $this->_addups = [];
        $this->_gids = [];
        $this->_todo_count = 0;
        $this->init_report();
    }
}