<?php

/**
 * 海外仓 需求汇总服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class PrSummaryService
{
    public static $s_system_log_name = 'OVEASEA-SUMMARY';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_pr_summary_model', 'oversea_summary', false, 'oversea');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }

    /**
     * 添加一条备注, 成功为true，否则抛异常
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];

        $record = ($pk_row = $this->_ci->load->oversea_summary->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }

        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->oversea_summary);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());

        $db = $this->_ci->oversea_summary->getDatabase();

        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('海外仓汇总列表更新备注失败'), 500);
        }
        if (!$this->add_summary_remark($params))
        {
            throw new \RuntimeException(sprintf('海外仓汇总列表插入备注失败'), 500);
        }
        $db->trans_complete();

        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('海外仓汇总添加备注事务提交完成，但检测状态为false'), 500);
        }

        return true;
    }

    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_summary_remark($params)
    {
        $this->_ci->load->model('Oversea_pr_summary_remark_model', 'oversea_pr_summary_remark', false, 'oversea');
        append_login_info($params);
        $insert_params = $this->_ci->oversea_pr_summary_remark->fetch_table_cols($params);
        return $this->_ci->oversea_pr_summary_remark->add($insert_params);
    }

    /**
     * 汇总
     */
    public function summary($should_addup)
    {
        $this->_ci->load->classes('oversea/classes/OverseaSummary');
        $this->_ci->load->model('Oversea_pr_list_model', 'm_oversea_list_model', false, 'oversea');
        $this->_ci->load->model('Oversea_pr_summary_log_model', 'm_oversea_summary_log_model', false, 'oversea');

        return $this->_ci->OverseaSummary
            ->set_by_default($this->_ci->m_oversea_list_model)
            ->set_summary_model($this->_ci->oversea_summary)
            ->set_summary_log_model($this->_ci->m_oversea_summary_log_model)
            ->run($should_addup);
    }

    public function approve($gids, $approve_result, $owner_privileges = [])
    {
        //gids验证
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }

        $todo = $this->_ci->oversea_summary->get_can_approve($gids);
        $this->_ci->load->classes('oversea/classes/OverseaSummaryApprove');

        $this->_ci->OverseaSummaryApprove
        ->set_approve_level('汇总')
        ->set_approve_result($approve_result)
        ->set_model($this->_ci->oversea_summary)
        ->set_selected_gids($gids)
        ->recive($todo);

        $this->_ci->OverseaSummaryApprove->run();
        $this->_ci->OverseaSummaryApprove->send_system_log(self::$s_system_log_name);
        $result =  $this->_ci->OverseaSummaryApprove->report();
        $this->_ci->OverseaSummaryApprove->clean();
        return $result;
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->oversea_summary->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }

    public function get_summary_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Oversea_pr_summary_remark_model', 'oversea_pr_summary_remark', false, 'oversea');
        return $this->_ci->oversea_pr_summary_remark->get($gid, $offset, $limit);
    }

    public function delete_disapear_track_summary($where_track_sn = [])
    {
        return $this->_ci->oversea_summary->delete_alone_summary($where_track_sn);
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
    public function batch_approve_all($approve_result,$query){
        set_time_limit(-1);
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '0');
        $active_user = get_active_user();
        log_message('INFO',"staff_code:{$active_user->staff_code}");
        //$this->_ci->load->model('Oversea_sku_cfg_part_model', 'm_part', false, 'oversea');//todo:要改

        //在rediss中保存 动态汇总数据的key键
        $summary_key =  'summary_approve_first_all_summary_oversea_'.$active_user->staff_code;//todo:要改

        //循环执行次数计数器
        $nums = 0;

        //进程pid
        $pid = getmypid();

        //记录进程执行的开始执行的时间及内存占用信息
        $fetch_before_execute_time = microtime(true);//获取数据执行前的时间
        $fetch_before_execute_memory = memory_get_usage(true);//获取数据执行前的内存
        $fetch_after_execute_time = 0;
        $fetch_after_execute_memory= 0;
        log_message('INFO', sprintf('海外需求汇总全量审批,pid:%d 重建需求列表， 起始内存：%s, 起始时间：%s', $pid, ($fetch_before_execute_memory/1024/1024).'M', $fetch_before_execute_time.'s'));
        $flag = true;
        do{
            $nums++;
            if($nums > 1){
                $fetch_before_execute_time = $fetch_after_execute_time;
                $fetch_before_execute_memory = $fetch_after_execute_memory;
            }


            $approve_data_ids  =  $this->_ci->oversea_summary->get_can_approve_data(300);//获取可审批的数据//todo:要改
            $counter = count($approve_data_ids);
            if(empty($approve_data_ids)){
                $flag = false;
                $state = 3; //审批结束
                $update_result = 1;
                $this->set_approve_process_summary($summary_key,$update_result, $counter,$state);//设置统计信息
                log_message('INFO','海外需求汇总所有待审批数据已审批完毕');
            }else{
                $update_result =  $this->approve($approve_data_ids,$approve_result);
                $update_result =  ($update_result['succ'] > 0) ? 1 : -1;
                $state = 2;//执行中
                $this->set_approve_process_summary($summary_key,$update_result,$counter,$state);//设置统计信息
            }
            $fetch_after_execute_time = microtime(true);
            $fetch_after_execute_memory = memory_get_usage(true);
            log_message('INFO', sprintf('海外需求汇总全量审批,pid:%d，第%d次取值审批， 消耗内存：%s, 总内存：%s, 消耗时间:%s, 审批执行结果:%s', $pid, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s',$update_result?'成功':'失败'));
            //todo:测试
            //$summary_info = $this->get_approve_process_summary($approve_result,$query);////获取统计数据
            //log_message('INFO', sprintf("pid: {$pid} 海外需求汇总全量审批统计summary".json_encode($summary_info)));

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
        log_message('INFO', sprintf('海外需求汇总全量审核统计key： %s eval执行结果: %s, eval执行状态：%s', $summary_key, $result ? '成功' : '失败', $result));
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
        $value = $this->_ci->rediss->command('hget summary_approve_query_pool '.$query_key);//todo 要改
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
        $summary_key =  'summary_approve_first_all_summary_oversea_'.$staff_code;//todo:要改
        $hash_node = $this->_ci->rediss->command('hgetall '.$summary_key);

        log_message('INFO', sprintf('海外需求汇总全量审批统计sumary_key:%s, hash_node取值：%s', $summary_key, json_encode($hash_node)));
        if ($hash_node) {
            for ($i=0, $j=count($hash_node); $i <= $j - 2; $i += 2)
            {
                $summary[$hash_node[$i]] = $hash_node[$i+1];
            }
        }
        log_message('INFO', sprintf('海外需求汇总全量审批统计summary'.json_encode($summary)));
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
                $command = "eval \"redis.call('del', KEYS[1]);redis.call('hdel', 'summary_approve_query_pool', KEYS[2]);return 'SUCC';\" 2 %s %s";// todo 要改
                $command = sprintf($command,$finish_params['summary_key'], $finish_params['query_key']);
                $result = $result && $this->_ci->rediss->eval_command($command);
                log_message('INFO', sprintf('海外需求汇总全量审批执行结束，清理缓存, 状态：%s, 参数：%s', $result, json_encode($finish_params)));
            }
            return $result;
        } catch (\Throwable $e) {
            log_message('ERROR', '海外需求汇总全量审批执行clear_approve_cache失败，异常：'.$e->getMessage());
            return false;
        }
    }
}
