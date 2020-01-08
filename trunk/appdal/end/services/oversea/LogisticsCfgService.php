<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/10/16
 * Time: 14:00
 */
class LogisticsCfgService
{
    public static $s_system_log_name = 'OVERSEA-LOGISTICS-CFG';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->helper('oversea_helper');

        return $this;
    }

    /**
     * 判断sku+站点是否已经存在
     *
     * @param $params
     * @param $error_data
     *
     * @return mixed
     */
    public function check_sku_is_exist($params, $error_data)
    {
        ini_set('memory_limit', '2048M');
        //导入失败的数据
        $this->_ci->load->model('Oversea_logistics_list_model', 'm_logistics', false, 'oversea');
        $result = $this->_ci->m_logistics->getDataByCondition([], 'sku,station_code');
        foreach ($result as $key => $item) {
            $k           = sprintf('%s$%s', $item['sku'], $item['station_code']);
            $sku_map[$k] = 1;
        }
        unset($result);
        foreach ($params as $key => $item) {
            $k = sprintf('%s$%s', $item['sku'], $item['station_code']);
            if (isset($sku_map[$k])) {
                $error_data[] = [
                    'sku'          => $item['sku'],
                    'station_code' => OVERSEA_STATION_CODE[$item['station_code']]['name']??'',
                    'logistics_id' => LOGISTICS_ATTR[$item['logistics_id']]['name']??'',
                ];
                unset($params[$key]);
            }
        }

        if (!empty($error_data)) {
            $this->_ci->load->library('rediss');
            $error_data = json_encode($error_data);
            $k          = sprintf('FBA_LOGISTIC_UPLOAD_ADD_%s', get_active_user()->staff_code);
            $this->_ci->rediss->setData($k, $error_data);
        }

        return $params;
    }

    public function add_sku($params)
    {
        //处理数据
        $insert_data = [];
        //采购仓库 http://192.168.71.156/web/#/73?page_id=2339
        foreach ($params as $key => $item) {
            $countryCode     = STATION_COUNTRY_MAP[$item['station_code']]['code'];
            $transportType   = LOGISTICS_ATTR_MAP[$item['logistics_id']];
            $k               = sprintf('%s_%s', $item['sku'], $transportType);//sku+物流属性
            $insert_data[$k] = $item;
            //中转仓规则入参  sku、businessType、transportType、countryCode
            $api_params[] = [
                'sku'           => $item['sku'],
                'businessType'  => 2,//海外写死为2
                'transportType' => LOGISTICS_ATTR_MAP[$item['logistics_id']],
                'countryCode'   => $countryCode
            ];
        }
//        pr($api_params);exit;
        $result = $this->getWareHouse($api_params);
        unset($api_params);
        if (!empty($result)) {
            foreach ($result as $key => $item) {
                $k                 = sprintf('%s$%s', $item['sku'], $item['transportType']);//sku+物流属性
                $warehouse_map[$k] = WAREHOUSE_CODE[$item['warehouseCode']];
            }
        }
        foreach ($insert_data as $key => &$item) {
            $item['purchase_warehouse_id'] = $warehouse_map[$key]??0;
        }
        unset($warehouse_map);
        //是否退税
        $insert_data = array_column($insert_data, NULL, 'sku');
        $skus        = array_keys($insert_data);
        $result      = $this->getDrawback($skus);
        if (!empty($result)) {
            $isDrawback_map = array_column($result, 'isDrawback', 'sku');
            $result         = '';
        }
        //sku状态,产品名称 ,组织数据
        $uid              = get_active_user()->staff_code;
        $user_name        = get_active_user()->user_name;
        $product_info_map = $this->product_info($skus);
        foreach ($insert_data as $key => $item) {
            $sku_name       = $product_info_map[$item['sku']]['title']??'';
            $product_status = $product_info_map[$item['sku']]['product_status']??'';
            $is_boutique    = $product_info_map[$item['sku']]['is_boutique']??'';
            $sku_state      = INLAND_SKU_ALL_STATE[$product_status]['listing_state']??'';
            $is_refund_tax  = $isDrawback_map[$item['sku']]??'';

            $logistics_insert_data[] = [
                'sku'             => $item['sku'],
                'station_code'    => $item['station_code'],
                'sku_name'        => $sku_name,
                'sku_state'       => $sku_state,
                'logistics_id'    => $item['logistics_id'],
                'details_of_first_way_transportation'    => LOGISTICS_ATTR_TO_DETAILS_OF_FIRST_WAY_TRANSPORTATION[$item['logistics_id']],
                'updated_uid'     => $uid,
                'updated_zh_name' => $user_name,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s'),
                'is_import'       => 1,
            ];

            $gid                    = gen_id(random_int(100, 999));
            $sku_main_insert_data[] = [
                'gid'                   => $gid,
                'sku'                   => $item['sku'],
                'station_code'          => $item['station_code'],
                'sku_state'             => $sku_state,
                'is_boutique'           => $is_boutique,
                'is_refund_tax'         => $is_refund_tax,
                'purchase_warehouse_id' => $item['purchase_warehouse_id'],
                'created_at'            => date('Y-m-d H:i:s'),
                'is_import'             => 1,
            ];

            $sku_part_insert_data[] = [
                'gid'   => $gid,
                'state' => CHECK_STATE_INIT
            ];
            unset($insert_data[$key]);
        }
        $all_insert_data['logistics_insert_data'] = $logistics_insert_data;
        $all_insert_data['sku_main_insert_data']  = $sku_main_insert_data;
        $all_insert_data['sku_part_insert_data']  = $sku_part_insert_data;

        //组织数据 插入数据库,物流属性配置表和备货关系配置表
        $affected_rows = $this->batch_ignore_insert($all_insert_data);

        return $affected_rows;
    }

    /**
     * 插入数据
     * @param $all_insert_data
     *
     * @return mixed
     */
    public function batch_ignore_insert($all_insert_data)
    {
        $db_plan_stock = $this->_ci->load->database('stock', true);
        $db_plan_stock->trans_start();
        $db_plan_stock->insert_ignore_batch('yibai_oversea_sku_cfg_main', $all_insert_data['sku_main_insert_data']);
        $db_plan_stock->insert_ignore_batch('yibai_oversea_sku_cfg_part', $all_insert_data['sku_part_insert_data']);
        $affected_rows = $db_plan_stock->insert_ignore_batch('yibai_oversea_logistics_list', $all_insert_data['logistics_insert_data']);
        $db_plan_stock->trans_complete();
        if ($db_plan_stock->trans_status() === FALSE) {
            throw new \RuntimeException('数据库操作失败', 500);
        } else {
            return $affected_rows;
        }
    }

    /**
     * 处理part多余的数据
     */
    public function del_part_data()
    {
        $db_plan_stock = $this->_ci->load->database('stock', true);
        $db_plan_stock->trans_start();
        $sql = "DELETE from yibai_oversea_sku_cfg_part where gid not in (select gid from yibai_oversea_sku_cfg_main);";
        $db_plan_stock->query($sql);
        $db_plan_stock->trans_complete();
        if ($db_plan_stock->trans_status() === FALSE) {
            throw new \RuntimeException('数据库操作失败', 500);
        }
    }

    /**
     * sku状态,产品名称,是否精品
     */
    public function product_info($skus)
    {
        $result     = [];
        $db_product = $this->_ci->load->database('yibai_product', true);
//        $sql = "SELECT sku,product_status, FROM yibai_product WHERE IN ('{$skus}')"
        $result = $db_product->select('a.sku,a.product_status,a.is_boutique,b.title')
            ->from('yibai_product a')
            ->join('yibai_product_description b', 'a.sku=b.sku', 'left')
            ->where_in('a.sku', $skus)
            ->where('b.language_code', 'Chinese')
            ->get()
            ->result_array();

        if (!empty($result)) {
            $result = array_column($result, NULL, 'sku');
        }

        return $result;
    }

    /**
     * java接口,是否退税
     * http://192.168.71.156/web/#/73?page_id=3649
     */
    public function getDrawback($paramsList = [])
    {
        if (empty($paramsList)) {
            return [];
        }
        $all_skus = array_column($paramsList, 'sku');
        $data     = [
            'sku' => implode(',', $all_skus),
        ];

        $result = RPC_CALL('YB_TMS_IS_DRAWBACK_01', $data);
//        pr($result);exit;

        if (empty($result) || !isset($result['code'])) {
            log_message('ERROR', '请求地址：/logistics/logisticsAttr/batchGetIsDrawback,无返回结果');

            return [];
        }

        if ($result['code'] == 1001 || $result['code'] == 1002) {
            log_message('ERROR', sprintf('请求地址：/logistics/logisticsAttr/batchGetIsDrawback,异常：%s', json_encode($result)));

            return [];
        }
//        pr($result);exit;
        if (isset($result['data']) && !empty($result['data'])) {
            return $result['data'];
        } else {
            log_message('ERROR', sprintf('请求地址：/logistics/logisticsAttr/batchGetIsDrawback,异常：%s', json_encode($result)));

            return [];
        }
    }

    public function getWareHouse($paramsList = [])
    {
        $result = TMS_RPC_CALL('YB_LOGISTICS_01', ['list' => $paramsList]);

        if (!empty($result['code']) && $result['code'] > 0) {
            log_message('ERROR', sprintf('请求仓库地址：logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo，批量获取sku仓库异常：%s,入参:%s', $result['msg'], json_encode(['list' => $paramsList])));

            return [];
        } elseif (!isset($result['data'])) {
            log_message('ERROR', sprintf('请求仓库地址：logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo，批量获取sku仓库异常：%s,入参:%s', $result['msg'], json_encode(['list' => $paramsList])));

            return [];
        }

        return !empty($result['data']) ? $result['data'] : [];
    }

    public function check_is_sku($skus)
    {
        $db_product  = $this->_ci->load->database('yibai_product', true);
        $unknown_sku = [];
        $result      = $db_product->select('*')
            ->from('yibai_product')
            ->where_in('sku', $skus)
            ->get()
            ->result_array();
        if (!empty($result)) {
            $erp_sku = array_column($result, 'sku');
            foreach ($skus as &$sku) {
                if (!in_array($sku, $erp_sku)) {
                    $unknown_sku[] = $sku;
                    unset($sku);
                }
            }
            if (!empty($unknown_sku)) {
                $unknown_sku = implode(',', $unknown_sku);
                throw new \InvalidArgumentException(sprintf('SKU:%s不存在yibai_product', $unknown_sku), 500);
            }
        }
    }

    public function get_undisposed_data()
    {
        $this->_ci->load->library('rediss');
        $result = $this->_ci->rediss->getData(sprintf('FBA_LOGISTIC_UPLOAD_ADD_%s', get_active_user()->staff_code));
        $result = json_decode($result, true);

        return $result;
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
        $this->_ci->load->model('Oversea_logistics_list_model', 'm_logistics', false, 'oversea');

        //在rediss中保存 动态汇总数据的key键
        $summary_key =  'logistics_approve_first_all_summary_oversea_'.$active_user->staff_code;

        //循环执行次数计数器
        $nums = 0;

        //进程pid
        $pid = getmypid();

        //记录进程执行的开始执行的时间及内存占用信息
        $fetch_before_execute_time = microtime(true);//获取数据执行前的时间
        $fetch_before_execute_memory = memory_get_usage(true);//获取数据执行前的内存
        $fetch_after_execute_time = 0;
        $fetch_after_execute_memory= 0;
        log_message('INFO', sprintf('海外物流属性配置全量审批,pid:%d 重建需求列表， 起始内存：%s, 起始时间：%s', $pid, ($fetch_before_execute_memory/1024/1024).'M', $fetch_before_execute_time.'s'));
        $flag = true;
        do{
            $nums++;
            if($nums > 1){
                $fetch_before_execute_time = $fetch_after_execute_time;
                $fetch_before_execute_memory = $fetch_after_execute_memory;
            }

            $approve_data  =  $this->_ci->m_logistics->get_can_approve_data(300);//获取可审批的数据
            //log_message('INFO','审批数据：'.json_encode($approve_data));
            $counter = count($approve_data);
            if(empty($approve_data)){
                $flag = false;
                $state = 3; //审批结束
                $update_result = 1;
                $this->set_approve_process_summary($summary_key,$update_result, $counter,$state);//设置统计信息
                log_message('INFO','海外物流属性配置所有待审批数据已审批完毕');
            }else{
                $update_result = $this->_ci->m_logistics->batch_update_approve_status($approve_data,$approve_result,$nums);
                $update_result =  $update_result ? 1 : -1;
                $state = 2;//执行中
                $this->set_approve_process_summary($summary_key,$update_result,$counter,$state);//设置统计信息
            }
            $fetch_after_execute_time = microtime(true);
            $fetch_after_execute_memory = memory_get_usage(true);
            log_message('INFO', sprintf('海外物流属性配置全量审批,pid:%d，第%d次取值审批， 消耗内存：%s, 总内存：%s, 消耗时间:%s, 审批执行结果:%s', $pid, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s',$update_result?'成功':'失败'));
            //todo:测试
            //$summary_info = $this->get_approve_process_summary($approve_result,$query);////获取统计数据
            //log_message('INFO', sprintf("pid:{$pid}海外物流属性配置全量审批统计summary".json_encode($summary_info)));

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
        log_message('INFO', sprintf('海外物流属性配置全量审核统计key： %s eval执行结果: %s, eval执行状态：%s', $summary_key, $result ? '成功' : '失败', $result));
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
        $value = $this->_ci->rediss->command('hget logistics_approve_query_pool '.$query_key);
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
        $summary_key =  'logistics_approve_first_all_summary_oversea_'.$staff_code;
        $hash_node = $this->_ci->rediss->command('hgetall '.$summary_key);

        log_message('INFO', sprintf('海外物流属性配置全量审批统计sumary_key:%s, hash_node取值：%s', $summary_key, json_encode($hash_node)));
        if ($hash_node) {
            for ($i=0, $j=count($hash_node); $i <= $j - 2; $i += 2)
            {
                $summary[$hash_node[$i]] = $hash_node[$i+1];
            }
        }
        log_message('INFO', sprintf('海外物流属性配置全量审批统计summary'.json_encode($summary)));
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
            property_exists($this->_ci, 'm_logistics_auto_approve_log') OR $this->_ci->load->model('Logistics_auto_approve_log_model', 'm_logistics_auto_approve_log', false, 'oversea');
            $db = $this->_ci->m_logistics_auto_approve_log->getDatabase();
            $result = $db->replace($this->_ci->m_logistics_auto_approve_log->getTable(), $params);
            if ($result)
            {
                $command = "eval \"redis.call('del', KEYS[1]);redis.call('hdel', 'logistics_approve_query_pool', KEYS[2]);return 'SUCC';\" 2 %s %s";
                $command = sprintf($command,$finish_params['summary_key'], $finish_params['query_key']);
                $result = $result && $this->_ci->rediss->eval_command($command);
                log_message('INFO', sprintf('海外物流属性配置全量审批执行结束，清理缓存, 状态：%s, 参数：%s', $result, json_encode($finish_params)));
            }
            return $result;
        } catch (\Throwable $e) {
            log_message('ERROR', '海外物流属性配置全量审批执行clear_approve_cache失败，异常：'.$e->getMessage());
            return false;
        }
    }
}
