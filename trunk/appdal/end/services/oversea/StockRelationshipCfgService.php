<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/14
 * Time: 19:59
 */

class StockRelationshipCfgService
{
    public function __construct()
    {
        $this->_ci =& get_instance();
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
        $this->_ci->load->model('Oversea_sku_cfg_part_model', 'm_part', false, 'oversea');//todo:要改

        //在rediss中保存 动态汇总数据的key键
        $summary_key =  'stock_approve_first_all_summary_oversea_'.$active_user->staff_code;//todo:要改

        //循环执行次数计数器
        $nums = 0;

        //进程pid
        $pid = getmypid();

        //记录进程执行的开始执行的时间及内存占用信息
        $fetch_before_execute_time = microtime(true);//获取数据执行前的时间
        $fetch_before_execute_memory = memory_get_usage(true);//获取数据执行前的内存
        $fetch_after_execute_time = 0;
        $fetch_after_execute_memory= 0;
        log_message('INFO', sprintf('海外备货关系配置全量审批,pid:%d 重建需求列表， 起始内存：%s, 起始时间：%s', $pid, ($fetch_before_execute_memory/1024/1024).'M', $fetch_before_execute_time.'s'));
        $flag = true;
        do{
            $nums++;
            if($nums > 1){
                $fetch_before_execute_time = $fetch_after_execute_time;
                $fetch_before_execute_memory = $fetch_after_execute_memory;
            }

            $approve_data  =  $this->_ci->m_part->get_can_approve_data(300);//获取可审批的数据//todo:要改
            $counter = count($approve_data);
            if(empty($approve_data)){
                $flag = false;
                $state = 3; //审批结束
                $update_result = 1;
                $this->set_approve_process_summary($summary_key,$update_result, $counter,$state);//设置统计信息
                log_message('INFO','海外备货关系配置所有待审批数据已审批完毕');
            }else{
                $update_result = $this->_ci->m_part->batch_update_approve_status($approve_data,$approve_result,$nums);//todo:要改
                $update_result =  $update_result ? 1 : -1;
                $state = 2;//执行中
                $this->set_approve_process_summary($summary_key,$update_result,$counter,$state);//设置统计信息
            }
            $fetch_after_execute_time = microtime(true);
            $fetch_after_execute_memory = memory_get_usage(true);
            log_message('INFO', sprintf('海外备货关系配置全量审批,pid:%d，第%d次取值审批， 消耗内存：%s, 总内存：%s, 消耗时间:%s, 审批执行结果:%s', $pid, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s',$update_result?'成功':'失败'));
            //todo:测试
            //$summary_info = $this->get_approve_process_summary($approve_result,$query);////获取统计数据
            //log_message('INFO', sprintf("pid: {$pid} 海外备货关系配置全量审批统计summary".json_encode($summary_info)));

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
        log_message('INFO', sprintf('海外备货关系配置全量审核统计key： %s eval执行结果: %s, eval执行状态：%s', $summary_key, $result ? '成功' : '失败', $result));
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
        $value = $this->_ci->rediss->command('hget stock_approve_query_pool '.$query_key);//todo 要改
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
        $summary_key =  'stock_approve_first_all_summary_oversea_'.$staff_code;//todo:要改
        $hash_node = $this->_ci->rediss->command('hgetall '.$summary_key);

        log_message('INFO', sprintf('海外备货关系配置全量审批统计sumary_key:%s, hash_node取值：%s', $summary_key, json_encode($hash_node)));
        if ($hash_node) {
            for ($i=0, $j=count($hash_node); $i <= $j - 2; $i += 2)
            {
                $summary[$hash_node[$i]] = $hash_node[$i+1];
            }
        }
        log_message('INFO', sprintf('海外备货关系配置全量审批统计summary'.json_encode($summary)));
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
            // todo：讨论是否要做  加入个人审核日志 加入此表： yibai_oversea_stock_log

            if ($result)
            {
                $command = "eval \"redis.call('del', KEYS[1]);redis.call('hdel', 'stock_approve_query_pool', KEYS[2]);return 'SUCC';\" 2 %s %s";// todo 要改
                $command = sprintf($command,$finish_params['summary_key'], $finish_params['query_key']);
                $result = $result && $this->_ci->rediss->eval_command($command);
                log_message('INFO', sprintf('海外备货关系配置全量审批执行结束，清理缓存, 状态：%s, 参数：%s', $result, json_encode($finish_params)));
            }
            return $result;
        } catch (\Throwable $e) {
            log_message('ERROR', '海外备货关系配置全量审批执行clear_approve_cache失败，异常：'.$e->getMessage());
            return false;
        }
    }



}
