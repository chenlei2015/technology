<?php

/**
 * oversea 平台
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class PlatformService
{
    public static $s_system_log_name = 'OVERSEA';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_platform_list_model', 'm_oversea_platform_list', false, 'oversea');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }

    /**
     * 添加一条备注, 成功为true，否则抛异常
     *
     * @version 1.2.0 增加权限
     *
     * @param array $params
     * @param string|array $owner_privileges 权限值 * 带有所有权限， 数组为有权限范围定义
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params, $owner_privileges = [])
    {
        $gid = $params['gid'];
        $remark = $params['remark'];

        $record = ($pk_row = $this->_ci->load->m_oversea_platform_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        if (!isset($owner_privileges['*']) && !isset($owner_privileges[$record['station_code']][$record['platform_code']]))
        {
            throw new \InvalidArgumentException(sprintf('您没有权限'), 412);
        }
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->m_oversea_platform_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        $this->_ci->Record->set('updated_uid', get_active_user()->staff_code);

        //不同db变成跨库
        $db = $this->_ci->m_oversea_platform_list->getDatabase();

        $db->trans_start();

        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('海外仓平台列表更新备注失败'), 500);
        }
        if (!$this->add_list_remark($params))
        {
            throw new \RuntimeException(sprintf('海外仓平台列表插入备注失败'), 500);
        }

        $db->trans_complete();

        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('海外仓平台添加备注事务提交完成，但检测状态为false'), 500);
        }

        return true;
    }

    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_list_remark($params)
    {
        $this->_ci->load->model('Oversea_platform_list_remark_model', 'oversea_platform_list_remark', false, 'oversea');
        append_login_info($params);
        $insert_params = $this->_ci->oversea_platform_list_remark->fetch_table_cols($params);
        return $this->_ci->oversea_platform_list_remark->add($insert_params);
    }

    /**
     * 重新计算需求数量
     *
     * 平台毛需求 = 平台加权销量平均值 * 备货提前期 + BD
     *
     * @param Record $record
     * @return number
     */
    protected function recalc_required_qty(Record $record)
    {
        return max(0, ceil($record->weight_sale_pcs * $record->pre_day + $record->bd));
    }

    /**
     * 修改pr列表
     * v1.1.2 bd变更
     *
     * 1、FBA、海外仓跑出来的需求都需要审核
     * 2、BD》0，那么一定会触发计划审核
     * 3、汇总sku的，如果需求数量<=0, 那么汇总会当做0来计算汇总
     * 4、备货计划回写的时候，如果需求数量《0，那么一定会回写0
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return boolean 成功返回true， 失败抛出异常
     */
    public function edit_pr_listing($params, $owner_privileges = [])
    {
        $gid = trim($params['gid']);
        unset($params['gid']);

        //gid检测
        $record = $this->_ci->m_oversea_platform_list->find_by_pk($gid);
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID%s', $gid), 412);
        }
		if ($record['expired'] == FBA_PR_EXPIRED_YES)
        {
            throw new \InvalidArgumentException(sprintf('该记录已经过期无法修改'), 412);
        }
        if (!in_array($record['approve_state'], [OVERSEA_PLATFORM_APPROVAL_STATE_FIRST, OVERSEA_PLATFORM_APPROVAL_STATE_FAIL]))
        {
            throw new \RuntimeException(sprintf('当前状态不允许修改BD数量'), 500);
        }
        if (!is_login())
        {
            throw new \RuntimeException(sprintf('请使用一级审核权限账号登录'), 500);
        }
        //权限检测
        $active_user = get_active_user();
        if (!$active_user->is_first_approver(BUSSINESS_OVERSEA))
        {
            throw new \RuntimeException(sprintf('请使用一级审核权限账号进行操作'), 500);
        }
        if (!isset($owner_privileges['*']) && !isset($owner_privileges[$record['station_code']][$record['platform_code']]))
        {
            throw new \InvalidArgumentException(sprintf('您没有权限'), 412);
        }

        //执行修改
        //控制可以被修改的字段
        $can_edit_cols = ['bd'];
        $old_bd = $record['bd'];
        $params = $this->_ci->m_oversea_platform_list->fetch_table_cols($params);
        $can_edit_params = array_intersect_key($params, array_flip($can_edit_cols));

        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->m_oversea_platform_list);
        foreach ($params as $key => $val)
        {
            if (array_key_exists($key, $can_edit_params))
            {
                $this->_ci->Record->set($key, $val);
            }
        }

        $modify_count = $this->_ci->Record->report();
        if ($modify_count == 0)
        {
            throw new \RuntimeException(sprintf('没有产生任何修改，本次操作未执行任何操作'), 500);
        }
        //更新时间
        $this->_ci->Record->set('updated_at', time());
        $this->_ci->Record->set('updated_uid', $active_user->staff_code);
        $this->_ci->Record->set('approve_state', OVERSEA_PLATFORM_APPROVAL_STATE_FIRST);

        //重新计算需求数量
        $this->_ci->Record->set('require_qty', $this->recalc_required_qty($this->_ci->Record));

        $this->_ci->load->service('oversea/PlatformLogService');
        $modify_bd = $this->_ci->Record->get('bd');

        //事务开始
        $db = $this->_ci->m_oversea_platform_list->getDatabase();

        try
        {
            $db->trans_start();

            //记录日志
            $log_context = sprintf('将BD由%s调整为 %s', ($old_bd > 0 ? '+' : '').$old_bd,  $modify_bd > 0 ? '+'.$modify_bd : $modify_bd);
            $this->_ci->platformlogservice->send(['gid' => $gid], $log_context);
            $update_count = $this->_ci->m_oversea_platform_list->update_bd($this->_ci->Record);
            if ($update_count !== 1)
            {
                throw new \RuntimeException(sprintf('未修改海外仓平台需求BD数量，该请求可能已经执行'), 500);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('修改海外仓平台需求BD数量，事务提交成功，但状态检测为false'), 500);
            }

            //发送系统日志
            $this->_ci->load->service('basic/SystemLogService');
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context);

            return true;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('修改海外仓平台需求BD数量，提交事务出现异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException(sprintf('修改海外仓平台需求BD数量，提交事务出现异常'), 500);
        }
    }

    /**
     * 批量修改bd
     *
     * @param array $params <pre>
     *  primary_key,
     *  map,
     *  selected
     *  </pre> 参数这样设置主要是为了减少传输数据大小
     * @param mixed $owner_privileges
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return boolean
     */
    public function batch_edit_pr($params, $owner_privileges = [])
    {
        $report = [
                'total' => 0,
                'processed' => 0,
                'undisposed' => 0,
        ];
        $index_to_cols = array_flip($params['map']);
        $selected = json_decode($params['selected'], true);
        unset($params['selected']);

        $report['total'] = $report['undisposed'] = count($selected);
        if (empty($selected))
        {
            return $report;
        }
        $pr_sns = array_keys($selected);
        //权限检测
        $active_user = get_active_user();
        if (!$active_user->is_first_approver(BUSSINESS_OVERSEA))
        {
            return $report;
        }
        $records = $this->_ci->m_oversea_platform_list->get_can_bd($pr_sns, $owner_privileges);
        if (empty($records))
        {
            return $report;
        }

        //过滤掉bd相同
        foreach ($records as $key => $val)
        {
            if ($val['bd'] == $selected[$val['pr_sn']][$params['map']['bd']])
            {
                unset($records[$key]);
            }
        }
        if (empty($records))
        {
            return $report;
        }

        //需要处理的
        $batch_update_pr = $batch_insert_log = [];
        $can_edit_cols = ['bd'];
        $update_time = time();
        $updated_uid = $active_user->staff_code;
        $updated_name = $active_user->user_name;

        $this->_ci->load->classes('basic/classes/Record');

        reset($records);
        foreach ($records as $val)
        {
            $this->_ci->Record->recive($val)->enable_extra_property();
            foreach ($selected[$val['pr_sn']] as $index => $edit_val)
            {
                $edit = $index_to_cols[$index];
                if (in_array($edit, $can_edit_cols))
                {
                    $this->_ci->Record->set($edit, $edit_val);
                }
            }
            $this->_ci->Record->set('updated_at', $update_time);
            $this->_ci->Record->set('updated_uid', $updated_uid);
            $this->_ci->Record->set('approve_state', OVERSEA_PLATFORM_APPROVAL_STATE_FIRST);

            //重新计算需求数量
            $this->_ci->Record->set('require_qty', $this->recalc_required_qty($this->_ci->Record));

            $old_bd = $val['bd'];
            $modify_bd = $this->_ci->Record->get('bd');

            $update_row = $this->_ci->Record->get();
            unset($update_row['pr_sn']);
            $batch_update_pr[] = $update_row;

            $log_context = sprintf('将BD由%s调整为 %s', ($old_bd > 0 ? '+' : '').$old_bd,  $modify_bd > 0 ? '+'.$modify_bd : $modify_bd);
            $batch_insert_log[] = [
                    'gid' => $val['gid'],
                    'uid' => $updated_uid,
                    'user_name' => $updated_name,
                    'context' => $log_context,
            ];
        }

        //事务开始
        $this->_ci->load->model('Oversea_platform_list_log_model', 'oversea_platform_list_log', false, 'oversea');
        $db = $this->_ci->m_oversea_platform_list->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新
            $update_rows = $db->update_batch($this->_ci->m_oversea_platform_list->getTable(), $batch_update_pr, 'gid');
            if (!$update_rows)
            {
                throw new \RuntimeException(sprintf('批量修改海外平台 BD数量更新列表失败'), 500);
            }

            //插入日志
            $insert_rows = $this->_ci->oversea_platform_list_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                throw new \RuntimeException(sprintf('批量修改海外平台BD数量插入日志失败'), 500);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                //@todo 回滚insert_rows
                throw new \RuntimeException(sprintf('修改海外平台PR需求BD数量，事务提交成功，但状态检测为false'), 500);
            }

            //发送系统日志
            $this->_ci->load->service('basic/SystemLogService');
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, '批量修改BD'.count($records).'记录');

            $report['processed'] = $report['total'];
            $report['undisposed'] = 0;

            return $report;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('修改海外平台需求BD数量，提交事务出现异常: %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('修改海外平台需求BD数量，提交事务出现异常'), 500);
        }
    }

    /**
     * 涉及到跨库事务，事务操作比较长，并且为了复用逻辑，采用了二段操作：
     *
     * 第一段： 平台汇总成站点列表
     * 第二段： 站点列表的跟踪和汇总写入, 因操作的影响是叠加的，意味着汇总每次都要重新生成，不在跟以往只需固定累加站点列表即可。
     *
     * 对于第二段操作有可能失败的情况下，采用下次审核捞取重试的策略。和最后生成采购列表的时候再次执行的策略。
     * 达到最终一致性。
     *
     * @version 1.2.0 增加权限控制
     *
     * @param array $gids
     * @param int $approve_result
     * @param array $owner_privileges
     * @throws \InvalidArgumentException
     * @return unknown
     */
    public function approve_platform($gids, $approve_result, $owner_privileges = [])
    {
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }

        // 第一段
        $todo = $this->_ci->m_oversea_platform_list->get_can_approve($gids, $owner_privileges);
        $this->_ci->load->classes('oversea/classes/OverseaPlatformApprove');

        $this->_ci->OverseaPlatformApprove
            ->set_approve_level('一')
            ->set_approve_result($approve_result)
            ->set_model($this->_ci->m_oversea_platform_list)
            ->set_selected_gids($gids)
            ->recive($todo);

        $this->_ci->OverseaPlatformApprove->run();
        $this->_ci->OverseaPlatformApprove->send_system_log(self::$s_system_log_name);
        $this->_ci->OverseaPlatformApprove->clean();

        $todo = NULL;
        unset($todo);
        if ($approve_result != APPROVAL_RESULT_PASS)
        {
            return $this->_ci->OverseaPlatformApprove->report();
        }

        // 第二段
        try {
            $this->station_general_track_summary($approve_result);
        }
        catch (\Throwable $e)
        {
            //第二段失败，给出提示
            log_message('ERROR', sprintf('海外仓站点列表生成跟踪和汇总失败，原因：%s', $e->getMessage()));
        }
        finally {
            $todo = NULL;
            unset($todo);
            return $this->_ci->OverseaPlatformApprove->report();
        }
    }

    /**
     * 生成站点未生成跟踪和汇总
     *
     * @param int $approve_result
     * @return unknown
     */
    public function station_general_track_summary($approve_result = APPROVAL_RESULT_PASS, $owner_privileges = ['*' => '*'])
    {
        // 第二段
        $this->_ci->load->model('Oversea_pr_list_model', 'oversea_pr_list', false, 'oversea');
        $todo = $this->_ci->oversea_pr_list->get_can_approve_for_second('', $owner_privileges);
        $this->_ci->load->classes('oversea/classes/OverseaApprove');

        $this->_ci->OverseaApprove
            ->set_approve_level('二')
            ->set_approve_result($approve_result)
            ->set_model($this->_ci->oversea_pr_list)
            ->set_selected_gids(array_column($todo, 'gid'))
            ->recive($todo);

        $this->_ci->OverseaApprove->run();
        $this->_ci->OverseaApprove->send_system_log(self::$s_system_log_name);
        log_message('INFO', sprintf('平台审核操作第二阶段执行结果：%s', json_encode($this->_ci->OverseaApprove->report())));

        //删除没有跟踪单的汇总单，这个是幂等的，可以与事务无关。
        $this->_ci->load->service('oversea/PrSummaryService');
        $this->_ci->prsummaryservice->delete_disapear_track_summary();

        return $this->_ci->OverseaApprove->report();
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->m_oversea_platform_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }

    /**
     * 获取备注
     *
     * @param unknown $gid gid
     * @param number $offset 第几页
     * @param number $limit 页记录数量
     * @return array
     */
    public function get_pr_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Oversea_platform_list_remark_model', 'm_oversea_platform_list_remark', false, 'oversea');
        return $this->_ci->m_oversea_platform_list_remark->get($gid, $offset, $limit);
    }

    /**
     * 设置记录过期
     *
     * @crontab 定时任务
     * @param unknown $params
     * @return unknown
     */
    public function expired($params)
    {
        set_time_limit(0);
        return $this->_ci->m_oversea_platform_list->update_expired($params['date']);
    }

    /**
     * 判断操作的截止时间
     *
     * @return true
     * @throws \OutOfRangeException
     */
    public function check_enable_time($operate = '')
    {
        $register_operate = [
                /*'edit_pr_listing',
                 'batch_edit_bd',*/
                'approve'
        ];
        if ($operate == '' || !in_array(strtolower($operate), $register_operate))
        {
            return true;
        }
        list($start, $end) = explode('~', PLAN_EDIT_PUSH_CLOSE_TIME);
        $now = date('H:i');
        $disabled = $now >= $start && $now <= $end;
        if ($disabled)
        {
            throw new \OutOfRangeException('备货列表生成中，请0点后再审核', 500);
        }
        return true;
    }


    /**
     * 全量审批
     */
    public function batch_approve_all($approve_result,$query,$owner_privileges = []){
        set_time_limit(-1);
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '0');
        $active_user = get_active_user();
        //log_message('INFO',"staff_code:{$active_user->staff_code}");
        //在rediss中保存 动态汇总数据的key键
        $summary_key =  'platform_approve_first_all_summary_oversea_'.$active_user->staff_code;//todo:要改

        //循环执行次数计数器
        $nums = 0;

        //进程pid
        $pid = getmypid();

        //记录进程执行的开始执行的时间及内存占用信息
        $fetch_before_execute_time = microtime(true);//获取数据执行前的时间
        $fetch_before_execute_memory = memory_get_usage(true);//获取数据执行前的内存
        $fetch_after_execute_time = 0;
        $fetch_after_execute_memory= 0;
        log_message('INFO', sprintf('海外平台需求全量审批,pid:%d 重建需求列表， 起始内存：%s, 起始时间：%s', $pid, ($fetch_before_execute_memory/1024/1024).'M', $fetch_before_execute_time.'s'));
        $flag = true;
        do{
            $nums++;
            if($nums > 1){
                $fetch_before_execute_time = $fetch_after_execute_time;
                $fetch_before_execute_memory = $fetch_after_execute_memory;
            }

            $approve_data  =  $this->_ci->m_oversea_platform_list->get_can_approve_data($owner_privileges,300);//获取可审批的数据//todo:要改
            //log_message('INFO','审批数据：'.json_encode($approve_data));
            $approve_data_ids = empty($approve_data)?[]:array_column($approve_data,'gid');
            $counter = count($approve_data_ids);
            if(empty($approve_data_ids)){
                $flag = false;
                $state = 3; //审批结束
                $update_result = 1;
                $this->set_approve_process_summary($summary_key,$update_result, $counter,$state);//设置统计信息
                log_message('INFO','海外平台需求所有待审批数据已审批完毕');
            }else{
                $update_result =  $this->approve_platform($approve_data_ids,$approve_result,$owner_privileges);
                $update_result =  ($update_result['succ'] > 0) ? 1 : -1;
                $state = 2;//执行中
                $this->set_approve_process_summary($summary_key,$update_result,$counter,$state);//设置统计信息
            }
            $fetch_after_execute_time = microtime(true);
            $fetch_after_execute_memory = memory_get_usage(true);
            log_message('INFO', sprintf('海外平台需求全量审批,pid:%d，第%d次取值审批， 消耗内存：%s, 总内存：%s, 消耗时间:%s, 审批执行结果:%s', $pid, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s',$update_result?'成功':'失败'));
            //todo:测试
            //$summary_info = $this->get_approve_process_summary($approve_result,$query);////获取统计数据
            //log_message('INFO', sprintf("pid: {$pid} 海外平台需求全量审批统计summary".json_encode($summary_info)));

        }while($flag);

        $summary_info = $this->get_approve_process_summary($approve_result,$query);////获取统计数据
        $query_key = get_active_user()->staff_code.'.'.$approve_result;
        $finish_params = [
            'summary_key' => $summary_key,
            'query_key' => $query_key,
            'info' => $summary_info,
            'business_line' => BUSSINESS_OVERSEA,
            'result' => $approve_result
        ];
        $this->clear_approve_cache($finish_params);//清理缓存并记录审批日志
        return $summary_info;
    }

    /**
     * 全量审批过程当中 动态统计汇总信息
     * @param $summary_key
     * @param $result
     * @param $counter
     * @param $state
     * @return mixed
     */
    public function set_approve_process_summary($summary_key,$result,$counter,$state)
    {
        property_exists($this->_ci, 'rediss') OR $this->_ci->load->library('Rediss');
        $command = "eval \"redis.call('hincrby', KEYS[1], 'nums', 1); redis.call('hset', KEYS[1], 'state', KEYS[4]); if(tonumber(KEYS[2]) == 1) then redis.call('hincrby', KEYS[1], 'processed', KEYS[3]); return 'SUCC'; else redis.call('hincrby', KEYS[1], 'undisposed', KEYS[3]); end; \" 4 %s %d %d %d";
        $result = $this->_ci->rediss->eval_command(sprintf($command, $summary_key, $result, $counter,$state));
        log_message('INFO', sprintf('海外平台需求全量审核统计key： %s eval执行结果: %s, eval执行状态：%s', $summary_key, $result ? '成功' : '失败', $result));
        return $result;
    }

    /**
     * 获取全量审批的汇总信息
     * @param $result
     * @param $query
     * @return array
     */
    public function get_approve_process_summary($result,$query){
        $query_key = get_active_user()->staff_code.'.'.$result;
        property_exists($this->_ci, 'rediss') OR $this->_ci->load->library('Rediss');
        $value = $this->_ci->rediss->command('hget platform_approve_query_pool '.$query_key);//todo 要改
        // state 1 开始审批 2:全量审批中 3 审批结束 4 数据都已审批完毕
        if (!$value) {
            //检验key必须存在，如果不存在，说明所有数据都已审批完毕
            $summary = [
                'state' => 4,
                'processed' => 0,
                'nums' => 0
            ];
            return $summary;
        } elseif ($value != $query) {
            //新起了一个进程，正在运行中，只显示进度
            $summary = [
                'state' => 1,
                'processed' => 0,
                'nums' => 0
            ];
        }
        //获取统计信息
        $staff_code = get_active_user()->staff_code;
        $summary_key =  'platform_approve_first_all_summary_oversea_'.$staff_code;//todo:要改
        $hash_node = $this->_ci->rediss->command('hgetall '.$summary_key);

        log_message('INFO', sprintf('海外平台需求全量审批统计sumary_key:%s, hash_node取值：%s', $summary_key, json_encode($hash_node)));
        if ($hash_node) {
            for ($i=0, $j=count($hash_node); $i <= $j - 2; $i += 2)
            {
                $summary[$hash_node[$i]] = $hash_node[$i+1];
            }
        }
        log_message('INFO', sprintf('海外平台需求全量审批统计summary'.json_encode($summary)));
        return $summary;
    }

    /**
     * 全量审批完毕后 清除缓存在redis中的数据 并记录审批日志
     * @param $finish_params
     * @return bool
     */
    private function clear_approve_cache($finish_params)
    {
        try {
            $params = [
                'key' => $finish_params['summary_key'],
                'processed' => $finish_params['info']['processed'] ?? 0,
                'nums' => $finish_params['info']['nums'] ?? 0,
                'business_line' => $finish_params['business_line'],
                'result' => $finish_params['result'],
            ];
            //加入批量审核日志
            property_exists($this->_ci, 'm_logistics_auto_approve_log') OR $this->_ci->load->model('Logistics_auto_approve_log_model', 'm_logistics_auto_approve_log', false, 'oversea');
            $db = $this->_ci->m_logistics_auto_approve_log->getDatabase();
            $result = $db->replace($this->_ci->m_logistics_auto_approve_log->getTable(), $params);

            if ($result)
            {
                $command = "eval \"redis.call('del', KEYS[1]);redis.call('hdel', 'platform_approve_query_pool', KEYS[2]);return 'SUCC';\" 2 %s %s";// todo 要改
                $command = sprintf($command,$finish_params['summary_key'], $finish_params['query_key']);
                $result = $result && $this->_ci->rediss->eval_command($command);
                log_message('INFO', sprintf('海外平台需求全量审批执行结束，清理缓存, 状态：%s, 参数：%s', $result, json_encode($finish_params)));
            }
            return $result;
        } catch (\Throwable $e) {
            log_message('ERROR', '海外平台需求全量审批执行clear_approve_cache失败，异常：'.$e->getMessage());
            return false;
        }
    }
}
