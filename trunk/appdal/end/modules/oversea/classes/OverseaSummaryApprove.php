<?php

/**
 * 汇总审核
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-21
 * @link
 * @throw
 */
class OverseaSummaryApprove
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
        ];
    }

    protected function _get_next_state($approve_result)
    {
        return $approve_result == APPROVAL_RESULT_PASS ? APPROVAL_STATE_SUCCESS : APPROVAL_STATE_FAIL;
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
        $this->_batch_log = [];
        $this->_batch_update = [];
        foreach ($todo as $key => $row)
        {
            $next_state = $this->_get_next_state($this->_result);
            //汇总更新参数
            $update_params = [
                    'approve_state' => $next_state,
                    'approved_at'   => $time,
                    'approved_uid'  => $active_user->staff_code,
                    'updated_uid'   => $active_user->staff_code,
                    'updated_at'    => $time
            ];
            //记录日志
            $this->_batch_log[] = [
                    'gid' => $row['gid'],
                    'context' => sprintf('汇总列表%s执行审核操作', $row['sum_sn'])
            ];
            $where = [
                    'gid'           => $row['gid'],
                    'approve_state' => $row['approve_state'],
            ];

            $this->_batch_update[] = ['where' => $where, 'update' => $update_params];
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

    public function update_approve_succ()
    {
        if ($this->_todo_count == 0)
        {
            return true;
        }
        try
        {
            $this->_ci->load->service('oversea/PrSummaryLogService');

            $my_db = $this->_model->getDatabase();

            //stock事务开启
            $my_db->trans_start();

            //批量发送日志
            if (!empty($this->_batch_log))
            {
                $this->_ci->prsummarylogservice->multi_send($this->_batch_log);
            }


            //批量更新状态
            $affect_rows = $my_db->update_batch_more_where($this->_model->getTable(), $this->_batch_update, 'gid');
            if ($affect_rows != count($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('更新汇总列表失败，预期更新%d条记录，实际更新%d条，选择的记录已经被其他用户修改，请筛选后后重试', count($this->_batch_update), $affect_rows), 500);
            }

            $my_db->trans_complete();

            if ($my_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行汇总列表审核操作，事务提交成功，但状态检测为false'), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行海外汇总列表审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行海外汇总列表审核操作，批量更新失败'), 500);
        }
        catch (\Throwable $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行海外汇总列表审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行海外汇总列表审核操作，批量更新失败'), 500);
        }
    }

    protected function update_approve_fail()
    {
        $this->update_approve_succ();
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
