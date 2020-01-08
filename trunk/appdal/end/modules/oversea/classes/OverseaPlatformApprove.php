<?php

/**
 * 海外平台审核, 审核完之后设置站点需求列表， 生成站点跟踪列表， 生成站点汇总列表。
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-21
 * @link
 * @throw
 */
class OverseaPlatformApprove
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
        return  $approve_result == APPROVAL_RESULT_PASS ? OVERSEA_PLATFORM_APPROVAL_STATE_SUCCESS : OVERSEA_PLATFORM_APPROVAL_STATE_FAIL;
    }

    /**
     * 接收代做列表, 将平台列表审核生成站点列表
     * sprintv1.1.2 增加乐观锁
     *
     * @param array $todo
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
        $next_state = $this->_get_next_state($this->_result);
        $active_user = get_active_user();
        $this->_batch_log = [];
        $this->_batch_update = [];
        foreach ($todo as $key => $row)
        {
            $update_params = [
                    'approve_state' => $next_state,
                    'approved_at'   => $time,
                    'approved_uid'  => $active_user->staff_code,
                    'updated_uid'   => $active_user->staff_code,
            ];

            if ($next_state == APPROVAL_STATE_SUCCESS)
            {
                $update_params['is_addup'] = PR_IS_ADDUP_YES;
            }
            //记录日志
            $this->_batch_log[] = [
                    'gid' => $row['gid'],
                    'context' => sprintf('海外平台列表审核需求单：%s 审核%s', $row['pr_sn'], $next_state == APPROVAL_STATE_FAIL ? '失败' : '通过')
            ];

            $where = [
                'gid'           => $row['gid'],
                'approve_state' => $row['approve_state'],
                'is_addup'      => $row['is_addup']
            ];

            $this->_batch_update[] = ['where' => $where, 'update' => $update_params];
        }
        if ($next_state == APPROVAL_STATE_SUCCESS)
        {
            $this->_addups = &$todo;
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
        $log_context = sprintf('海外平台列表审核选择：%s',  is_array($this->gids) ? implode(',', $this->gids) : '没有选择gid');
        $this->_ci->systemlogservice->send([], $module, $log_context . $this->_report['msg']);
    }

    /**
     * 跨库
     *
     * 事务开始：
     * stock为主事务（所有stock的更新必须先行）：
     *
     *  发送平台日志(stock)
     *  删除跟踪列表(stock）
     *  汇总平台需求： 插入日志(stock)， 更新站点列表(common)
     *  更新平台列表状态(common)
     *
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
            $this->_ci->load->service('oversea/PlatformLogService');
            $this->_ci->load->model('Oversea_platform_list_log_model', 'oversea_platform_list_log', false, 'oversea');

            $delete_track_sn = [];

            $my_db = $this->_ci->oversea_platform_list_log->getDatabase();

            //stock库事务
            $my_db->trans_start();

            //批量发送平台列表日志
            if (!empty($this->_batch_log))
            {
                $this->_ci->platformlogservice->multi_send($this->_batch_log);
            }

            //common库事务
            $this->_other_db->trans_start();

            if (!empty($this->_addups))
            {
                //汇总带有乐观锁
                $this->_ci->load->service('oversea/PlatformSummaryService');
                $report['addup'] = $this->_ci->platformsummaryservice->summary($this->_addups);
                $delete_track_sn = $this->_ci->platformsummaryservice->get_delete_track_pr_sn();
            }

            //删除因触发需求变化无效的跟踪单号
            if (!empty($delete_track_sn))
            {
                $this->_ci->load->model('Oversea_pr_track_list_model', 'oversea_track_list_model', false, 'oversea');
                $delete_count = $this->_ci->oversea_track_list_model->delete_track_by_prsn($delete_track_sn);
                log_message('INFO', sprintf('海外平台审核操作产生%d条变为不触发需求，单号为：%s, 删除跟踪列表数量:%d', count($delete_track_sn), implode(',', $delete_track_sn), $delete_count));
            }

            //批量更新状态
            $affect_rows = $this->_other_db->update_batch_more_where($this->_model->getTable(), $this->_batch_update, 'gid');
            if ($affect_rows != count($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('更新海外平台列表失败，预期更新%d条记录，实际更新%d条，选择的记录已经被其他用户修改，请筛选后后重试', count($this->_batch_update), $affect_rows), 500);
            }

            /*
            if (!$this->_model->batch_update_compatible($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('海外平台列表审核操作，批量更新失败'), 500);
            }*/

            $this->_other_db->trans_complete();
            if ($this->_other_db->trans_status() === false)
            {
               throw new \RuntimeException(sprintf('执行海外平台销售审核操作，common库事务提交成功，但状态检测为false'), 500);
            }
            //common库提交

            $my_db->trans_complete();

            if ($my_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行海外平台销售审核操作，事务提交成功，但状态检测为false'), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行海外平台销售审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行海外平台销售审核操作，批量更新失败'), 500);
        }
        catch (\Throwable $e)
        {
            $this->_finish_report();
            log_message('ERROR', sprintf('执行海外平台销售审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('执行海外平台销售审核操作，批量更新失败'), 500);
        }
    }

    /**
     * 审核失败， 全部操作只有发送日志、更新状态、发送系统日志三个操作
     */
    protected function update_approve_fail()
    {
        if ($this->_todo_count == 0)
        {
            return true;
        }

        try
        {

            $this->_ci->load->service('oversea/PlatformLogService');

            $this->_other_db->trans_start();

            //批量发送日志
            $this->_ci->platformlogservice->multi_send($this->_batch_log);

            //批量更新状态
            /*if (!$this->_model->batch_update_compatible($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('海外平台列表审核操作，批量更新失败'), 500);
            }*/

            //批量更新状态
            file_put_contents(APPPATH.'/cache/2.php',"<?php\n".'return '.var_export($this->_batch_update, true).";\n\n?>",FILE_APPEND);
            $affect_rows = $this->_other_db->update_batch_more_where($this->_model->getTable(), $this->_batch_update, 'gid');
            if ($affect_rows != count($this->_batch_update))
            {
                throw new \RuntimeException(sprintf('更新海外平台列表失败，预期gg更新%d条记录，实际更新%d条，选择的记录已经被其他用户修改，请筛选后后重试', count($this->_batch_update), $affect_rows), 500);
            }

            $this->_other_db->trans_complete();

            if ($this->_other_db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('海外平台列表审核操作，事务提交成功，但状态检测为false'), 500);
            }

            $this->_finish_report();

            return true;
        }
        catch (\Exception $e)
        {
            $this->_report['fail'] = $this->_todo_count;
            $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
            log_message('ERROR', sprintf('海外平台列表审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('海外平台列表审核操作，批量更新失败'), 500);
        }
        catch (\Throwable $e)
        {

            $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
            log_message('ERROR', sprintf('海外平台列表审核操作，抛出异常： %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('海外平台列表审核操作，批量更新失败'), 500);
        }
    }

    /**
     * 清空
     */
    public function clean()
    {
        $this->gids = $this->_result = $this->_addups = $this->_batch_log = [];
        $this->_batch_track_log_insert = $this->_batch_update = [];
    }

    private function _finish_report($pass = true)
    {
        $pass ? $this->_report['succ'] = $this->_todo_count : $this->_report['fail'] = $this->_todo_count;
        $this->_report['msg'] = sprintf($this->_report['msg'], $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
    }



}
