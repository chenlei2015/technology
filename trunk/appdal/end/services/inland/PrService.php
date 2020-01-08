<?php

/**
 * 国内 需求服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class PrService
{
    public static $s_system_log_name = 'INLAND';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_pr_list_model', 'm_inland_pr_list', false, 'inland');
        $this->_ci->load->helper('inland_helper');
        return $this;
    }

    /**
     * 添加一条备注, 成功为true，否则抛异常， 不做权限
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params, $priv_uid = -1)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        $record = $this->_ci->m_inland_pr_list->find_by_pk($gid);
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
        $this->_ci->Record->setModel($this->_ci->m_inland_pr_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        $this->_ci->Record->set('updated_uid', get_active_user()->staff_code);

        $db = $this->_ci->m_inland_pr_list->getDatabase();

        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('国内列表更新备注失败'), 500);
        }
        if (!$this->add_list_remark($params))
        {
            throw new \RuntimeException(sprintf('国内列表插入备注失败'), 500);
        }
        $db->trans_complete();

        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('国内添加备注事务提交完成，但检测状态为false'), 500);
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
        $this->_ci->load->model('Inland_pr_list_remark_model', 'm_inland_pr_list_remark', false, 'inland');
        append_login_info($params);
        $insert_params = $this->_ci->m_inland_pr_list_remark->fetch_table_cols($params);
        return $this->_ci->m_inland_pr_list_remark->add($insert_params);
    }

    /**
     * 获取一键审核执行信息
     *
     * @param int $level
     * @param string $query
     * @throws \Exception
     * @return unknown
     */
    public function get_approve_process_summary($level, $result, $query)
    {
        $approve_query_pool = get_active_user()->staff_code.'.'.$level.'.'.$result;
        property_exists($this->_ci, 'rediss') OR $this->_ci->load->library('Rediss');

        //state: 1 运行中 2 新增进程只查询 3 结束
        $summary = [
                'state' => 1,
                'processed' => 0,
                'nums' => 0
        ];

        //检测是否有已经执行全部执行完成的标记。
        $finish_key = 'approve_finish_'.$approve_query_pool.'.'.date('Ymd');

        $finish_info = $this->_ci->rediss->command('get '.$finish_key);
        if (null !== $finish_info) {
            $info = explode('.', $finish_info);
            $summary = [
                    'state' => 4,
                    'processed' => intval($info[0]),
                    'nums' => intval($info[1] ?? 0),
            ];
            return $summary;
        }

        //运行中
        $value = $this->_ci->rediss->command('hget inland_approve_query_pool '.$approve_query_pool);
        if (!$value) {

            //检验key必须存在，如果不存在，做已经执行处理
            $summary = [
                    'state' => 3,
                    'processed' => 0,
                    'nums' => 0
            ];
            return $summary;
        } elseif ($value != $query) {
            //新起了一个进程，正在运行中，只显示进度
            $summary = [
                    'state' => 2
            ];
        }

        $key_map = [
                1 => 'approve_first_all_summary_inland_',
                2 => 'approve_second_all_summary_inland_',
                3 => 'approve_three_all_summary_inland_',
        ];

        property_exists($this->_ci, 'rediss') OR $this->_ci->load->library('Rediss');
        $staff_code = get_active_user()->staff_code;
        $summary_key = $key_map[$level].$staff_code;

        $hash_node = $this->_ci->rediss->command('hgetall '.$summary_key);
        log_message('INFO', sprintf('查询统计sumary_key:%s, hash_node取值：%s', $summary_key, json_encode($hash_node)));
        if ($hash_node) {
            for ($i=0, $j=count($hash_node); $i <= $j - 2; $i += 2)
            {
                $summary[$hash_node[$i]] = $hash_node[$i+1];
            }
        }
        log_message('INFO', sprintf('查询统计summary'.json_encode($summary)));

        return $summary;
    }

    /**
     * 自动审核, 分批次执行
     *
     * @return unknown
     */
    public function auto_approve($level, $approve_result, $query_value)
    {
        $this->_ci->load->classes('inland/classes/InlandApprove');
        $this->_ci->InlandApprove->set_model($this->_ci->m_inland_pr_list);
        set_time_limit(0);
        ini_set('memory_limit', '1024M');

        $num = 0;
        $total = 0;
        $start_memory = memory_get_usage(true);

        while (true)
        {
            list($mic, $start) = explode(" ", microtime());
            $memory_start = memory_get_usage(true);

            //执行分批写入进度信息
            $this->_ci->InlandApprove->set_approve_result($approve_result)->run($this->_ci->InlandApprove::INLAND_APPROVE_AUTOMATIC);
            $this->_ci->InlandApprove->send_system_log(self::$s_system_log_name);
            $report = $this->_ci->InlandApprove->report();
            list($mic, $end) = explode(" ", microtime());
            $memory_end = memory_get_usage(true);

            log_message('INFO',
                sprintf('国内自动审核: 第%d次执行，耗时：%d秒, 内存消耗：%s, 总消耗内存：%s， 总耗时：%d秒 返回结果：%s ',
                    $num+1,
                    $end - $start,
                    intval(($memory_end - $memory_start) / 1024 / 1024) . 'M',
                    intval($memory_end / 1024 / 1024 ). 'M',
                    $total,
                    json_encode($report))
                );
            $total += ($end - $start);
            if ($report['addup'] === -1)
            {
                break;
            }
            $num ++;

        }

        //处理完成, 正常， 异常reload
        $info = $this->get_approve_process_summary($level, $approve_result, $query_value);

        //汇总信息
        $summary_key = 'approve_first_all_summary_inland_'.get_active_user()->staff_code;

        if (isset($info['state']) && $info['state'] == 1 && $info['processed'] == 0) {
            //异常，重新reload
            $this->_ci->load->model('Inland_auto_approve_log_model', 'm_auto_approve_log', false, 'inland');
            $logs = $this->_ci->m_auto_approve_log->get_today_logs(BUSINESS_LINE_INLAND);
            if (!empty($logs)) {
                foreach ($logs as $log) {
                    if ($log['key'] == $summary_key && $log['result'] == $approve_result && $log['business_line'] == BUSINESS_LINE_INLAND) {
                        $this->_ci->rediss->command('hmset '.$log['summary_key'].' processed '.$log['processed'].' nums '.$log['nums']);
                        $info = ['processed' => $log['processed'], 'nums' => $log['nums']];
                    }
                }
            }
        }

        $query_key = get_active_user()->staff_code.'.'.$level.'.'.$approve_result;
        //注册一个完成key
        $finish_params = [
                'lock_key' => '__padding__',
                'summary_key' => $summary_key,
                'query_key' => $query_key,
                'start_gid_key' => '__padding__',
                'finish_key' => 'approve_finish_'.$query_key.'.'.date('Ymd'),
                'finish_key_expired_time' => strtotime(date('Y-m-d').' 23:59:59') - time(),
                'info' => $info,
                'business_line' => BUSINESS_LINE_INLAND,
                'result' => $approve_result
        ];
        $this->clear_approve_cache($finish_params);

        return [
                'nums' => $num,
                'memory' => intval(($start_memory - $memory_end) / 1024 / 1024) . 'M',
                'time' => $total
        ];;
    }

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
            property_exists($this->_ci, 'm_auto_approve_log') OR $this->_ci->load->model('Inland_auto_approve_log_model', 'm_auto_approve_log', false, 'inland');
            $db = $this->_ci->m_auto_approve_log->getDatabase();
            $result = $db->replace($this->_ci->m_auto_approve_log->getTable(), $params);
            if ($result)
            {
                $command = "eval \"redis.call('del', KEYS[1]); redis.call('del', KEYS[2]);redis.call('del', KEYS[4]); redis.call('hdel', 'inland_approve_query_pool', KEYS[3]); redis.call('set', KEYS[5], KEYS[7], 'EX', KEYS[6]); return 'SUCC';\" 7 %s %s %s %s %s %d %d";
                $command = sprintf($command, $finish_params['lock_key'], $finish_params['summary_key'], $finish_params['query_key'], $finish_params['start_gid_key'], $finish_params['finish_key'], $finish_params['finish_key_expired_time'], ($finish_params['info']['processed'] ?? 0).'.'.($finish_params['info']['nums'] ?? 0));
                $result = $result && $this->_ci->rediss->eval_command($command);
                log_message('INFO', sprintf('执行结束，清理缓存, 状态：%s, 参数：%s', $result, json_encode($finish_params)));
            }
            return $result;
        } catch (\Throwable $e) {
            log_message('ERROR', '执行clear_approve_cache失败，异常：'.$e->getMessage());
            return false;
        }
    }

    /**
     * 检测指定日期是否有未汇总的订单
     *
     * @param unknown $get
     * @return int 需要汇总的
     */
    public function auto_approve_check($get)
    {
        return $this->_ci->m_inland_pr_list->has_automatic_pr($get['date']);
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->m_inland_pr_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }

    public function get_pr_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Inland_pr_list_remark_model', 'm_inland_pr_list_remark', false, 'inland');
        return $this->_ci->m_inland_pr_list_remark->get($gid, $offset, $limit);
    }

    public function expired($params)
    {
        set_time_limit(0);
        return $this->_ci->m_inland_pr_list->update_expired($params['date']);
    }

    public function rebuild_pr()
    {
        $this->_ci->load->classes('inland/classes/InlandRebuildPr');
        $this->_ci->load->model('Inland_pr_list_log_model', 'inland_pr_list_log', false, 'inland');
        $this->_ci->load->model('Inland_activity_list_model', 'm_inland_activity', false, 'inland');

        //获取有效的活动
        $future_valid_activities = $this->_ci->m_inland_activity->get_future_valid_activities();

        //获取不参与运算的sku
        //$this->_ci->load->model('Inland_sales_operation_cfg_model', 'm_disabled_sku', false, 'inland');
        //$disabled_skus = $this->_ci->m_disabled_sku->get_disabled_skus();

        $config_path = APPPATH . 'upload/inland_cfg.php';
        if (file_exists($config_path)) {
            include($config_path);

            if (isset($sales_amount_cfg) && isset($exhaust_cfg) && isset($in_warehouse_age_cfg) &&
                isset($supply_day_cfg) && isset($sale_category_cfg)) {

                    //$amount =  [15 => 2, 30 => 38888];
                    $get_sales_amount_factor = function($amount) use ($sales_amount_cfg) {
                        foreach($sales_amount_cfg as $cfg) {
                            $find = true;
                            foreach($cfg['cfg'] as $step => $set) {
                                if (!$find) continue;
                                $num = $amount[$step];
                                switch ($set[0]) {
                                    case '<=':
                                        $find = $find && $num <= $set[1];
                                        break;
                                    case '<':
                                        $find = $find && $num < $set[1];
                                        break;
                                    case '>=':
                                        $find = $find && $num >= $set[1];
                                        break;
                                    case '<=':
                                        $find = $find && $num <= $set[1];
                                        break;
                                    case '==':
                                        $find = $find && $num == $set[1];
                                        break;
                                }
                            }
                            if ($find) {
                                return $cfg['factor'];
                            }
                        }
                        log_message('ERROR', '销售系数表未匹配任何值, amount='.json_encode($amount).' 配置：'.json_encode($sales_amount_cfg));
                        return false;
                    };


                    $get_exhaust_factor = function($amount) use ($exhaust_cfg) {
                        //exhausted_days 已缺货天数不参与计算，默认值0，  因改值取消 断货天数系数 默认给1， 即不参与计算
                        return 1;

                        //$min_max = [
                        //    'min' => PHP_INT_MIN,
                        //    'max' => PHP_INT_MAX,
                        //];
                        foreach($exhaust_cfg as $cfg) {
                            if (is_string($cfg[0]) && $amount <= $cfg[1]) {
                                return $cfg[2];
                            } elseif (is_string($cfg[1]) && $amount >= $cfg[1]) {
                                return $cfg[2];
                            } elseif ($amount >= $cfg[0] && $amount <= $cfg[1]) {
                                return $cfg[2];
                            }
                        }
                        log_message('ERROR', '断货天数系数未匹配任何值, amount='.$amount.' 配置：'.json_encode($exhaust_cfg));
                        return false;
                    };


                    $get_warehouse_age_factor = function($warehouse_age_cfg) use ($in_warehouse_age_cfg) {
                        //is_warehouse_days_90 是否超90天库龄不参与计算，默认值给2为不超过90天库龄，因该值取消， 库龄配置系数 默认给1 即不参与计算
                        return 1;

                        foreach($in_warehouse_age_cfg as $cfg) {
                            if ($cfg[0] == $warehouse_age_cfg) {
                                return $cfg[1];
                            }
                        }
                        log_message('ERROR', '库龄配置系数未匹配任何值, amount='.$amount.' 配置：'.json_encode($in_warehouse_age_cfg));
                        return false;
                    };

                    $get_supply_factor = function($amount) use ($supply_day_cfg) {
                        foreach($supply_day_cfg as $cfg) {
                            if (is_string($cfg[0]) && $amount <= $cfg[1]) {
                                return $cfg[2];
                            } elseif (is_string($cfg[1]) && $amount >= $cfg[1]) {
                                return $cfg[2];
                            } elseif ($amount >= $cfg[0] && $amount <= $cfg[1]) {
                                return $cfg[2];
                            }
                        }
                        log_message('ERROR', '可售卖天数系数未匹配任何值, amount='.$amount.' 配置：'.json_encode($supply_day_cfg));
                        return false;
                    };

                    //配置增加了max_sp参数
                    $get_z_scday_expand_factor = function($amount) use ($sale_category_cfg) {
                        foreach($sale_category_cfg as $cfg) {
                            if (is_string($cfg[0]) && $amount <= $cfg[1]) {
                                return array_slice($cfg, 2);
                            } elseif (is_string($cfg[1]) && $amount >= $cfg[1]) {
                                return array_slice($cfg, 2);
                            } elseif ($amount >= $cfg[0] && $amount < $cfg[1]) {
                                return array_slice($cfg, 2);
                            }
                        }
                        log_message('ERROR', '销量分类系数未匹配任何值, amount='.$amount.' 配置：'.json_encode($sale_category_cfg));
                        return false;
                    };
                }
                //log_message('INFO', '销售系数表存在, 请检查路径：'.$config_path.' 现在开始无配置下的重建需求列表');
        } else {
            throw new \InvalidArgumentException('销售系数表不存在, 请检查路径：'.$config_path.' 请上传之后重新运行');
            log_message('ERROR', '销售系数表不存在, 请检查路径：'.$config_path.' 现在开始无配置下的重建需求列表');
        }

        //设置前缀
        $prefix = $this->_ci->InlandRebuildPr->get_prefix();
        $diff_varx_py_prefix = $prefix['python'];
        $diff_varx_php_prefix = $prefix['php'];

        $get_php_trans_colname = function($origin_col) use ($diff_varx_php_prefix) {
            return $diff_varx_php_prefix.$origin_col;
        };
        $get_py_trans_colname = function($origin_col) use ($diff_varx_py_prefix) {
            return $diff_varx_py_prefix.$origin_col;
        };

        //现在只有Python一个
        $this->_ci->InlandRebuildPr->enable_python_cfg();

        $use_calc_system = $this->_ci->InlandRebuildPr->get_main_system();

        //解析格式，获得数据
        $this->_ci->InlandRebuildPr
        //前置回调
        ->register_callback('before', function() {})
        //后置回调
        ->register_callback('after', function() {})
        //设置语言文件
        ->set_langage('inland_pr_list')
        //设置更新表model
        ->set_model($this->_ci->m_inland_pr_list)
        //日志model
        ->set_log_model($this->_ci->inland_pr_list_log);

        $this->_ci->InlandRebuildPr
        ->register_columns_recalc_callback(
            //指定备货  如果特殊备货为是，则采购仓库变更为小包仓_虎门；如果特殊备货为否，则采购仓库不做变更。
            'designates',
            function(&$new_row) {
                if ($new_row['designates'] == INLAND_DESIGNATES_YES) {
                    $new_row['purchase_warehouse_id'] = PURCHASE_WAREHOUSE_HM_AA;
                }
                return $new_row['designates'];
            }
            )
        ->register_columns_recalc_callback(
            //在开始备货时间之后，活动开始时间之前，活动量有效；活动开始后，活动量失效
            'bd',
            function($new_row) use ($future_valid_activities) {
                $bd = 0;
                $current_timestamp = time();
                foreach ($future_valid_activities[$new_row['sku']] ?? [] as $rows)
                {
                    foreach ($rows as $act)
                    {
                        if ($current_timestamp > strtotime($act['execute_purcharse_time']) && $current_timestamp < strtotime($act['activity_start_time'])) {
                            $bd += $act['amount'];
                        }
                    }
                }
                return $bd;
            }
            )
        ->register_columns_recalc_callback(
            //扩销系数，这里会将涉及到系数一并处理
            'expand_factor',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname, $get_sales_amount_factor,$get_exhaust_factor,$get_warehouse_age_factor,$get_supply_factor,$get_z_scday_expand_factor) {
                //丢失python配置暂当做0处理
                if (isset($new_row['lost_python_cfg']) && true === $new_row['lost_python_cfg']) {
                    return $new_row['expand_factor'];
                }

                $amount = [SALE_AMOUNT_DAYS_STEP1 => $new_row['sale_amount_15_days'], SALE_AMOUNT_DAYS_STEP2 => $new_row['sale_amount_30_days']];
                $new_row['sales_factor'] = $get_sales_amount_factor($amount);
                $new_row['exhaust_factor'] = $get_exhaust_factor($new_row['exhausted_days']);
                //断货天数的来源目前未确定
                $new_row['warehouse_age_factor'] = $get_warehouse_age_factor($new_row['is_warehouse_days_90']);
                $new_row['supply_factor'] = $get_supply_factor($new_row['supply_day']);
                $z_scday_expand_factor = $get_z_scday_expand_factor($new_row['weight_sale_pcs']);
                $new_row['z'] = $z_scday_expand_factor[0];
                $new_row['sc_day'] = $z_scday_expand_factor[1];
                $new_row['expand_factor'] = $z_scday_expand_factor[2];
                $new_row['min_order_qty'] = $z_scday_expand_factor[3];
                //从配置里面取max_sp
                $new_row['max_sp'] = $z_scday_expand_factor[3];
                //缓存天数
                $new_row['actual_bs'] = round($new_row['bs'] * $new_row['sales_factor'] * $new_row['exhaust_factor'] * $new_row['warehouse_age_factor'] * $new_row['supply_factor']);
                //最大安全天数
                $new_row['actual_safe_stock'] = round($new_row['max_safe_stock_day'] * $new_row['sales_factor'] * $new_row['exhaust_factor'] * $new_row['warehouse_age_factor'] * $new_row['supply_factor']);

                return $new_row['expand_factor'];
            }
            )
        ->register_columns_recalc_callback(
            //备货提前周期=缓冲库存+备货处理周期+权均供货周期+发运时效
            'pre_day',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {
                //丢失python配置
                if (isset($new_row['lost_python_cfg']) && true === $new_row['lost_python_cfg']) {
                    return $new_row['pre_day'];
                }
                return $new_row['sp'] + $new_row['bs'] + $new_row['supply_wa_day'] + $new_row['shipment_time'];
            }
            )
        ->register_columns_recalc_callback(
            //安全库存 = Z*（（备货提前期*销量标准偏差的平方+加权日均销量的平方*交付标准偏差的平方）的平方根）
            'safe_stock_pcs',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {
                if (isset($new_row['lost_python_cfg']) && true === $new_row['lost_python_cfg']) {
                    return $new_row['safe_stock_pcs'];
                }
                $acutal_safe_stock = $new_row['actual_safe_stock'];
                $safe_stock_pcs = ceil(sqrt( $new_row['pre_day'] * pow($new_row['sale_sd_pcs'], 2) + pow($new_row['weight_sale_pcs'], 2) * pow($new_row['deliver_sd_day']), 2 ) );
                $safe_stock_pcs = min($safe_stock_pcs, $acutal_safe_stock);
                return $safe_stock_pcs;
            }
            )
        ->register_columns_recalc_callback(
            //订购点 = （加权日均销量 * 备货提前期）+ 安全库存
            'point_pcs',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {
                //丢失python配置暂当做0处理
                if (isset($new_row['lost_python_cfg']) && true === $new_row['lost_python_cfg']) {
                    return $new_row['point_pcs'];
                }
                return ceil($new_row['weight_sale_pcs'] * $new_row['pre_day']) + $new_row['safe_stock_pcs'];
            }
            )

        ->register_columns_recalc_callback(
            //可支撑天数  = （可用库存 + 待上架数 + 国际在途数量） / 加权日均销量
            //库存为0， 加权销量为0， 支撑为0
            //库存>0, 加权销量为0， 10000
            'supply_day',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname)  {
                //丢失python配置暂当做0处理
                if (isset($new_row['lost_python_cfg']) && true === $new_row['lost_python_cfg']) {
                    return $new_row['supply_day'];
                }

                //python
                $stock = $new_row['available_qty'] + $new_row['ship_qty'];
                if ($stock == 0 && $new_row['weight_sale_pcs'] == 0) {
                    return 0;
                } elseif ($stock > 0 && $new_row['weight_sale_pcs'] == 0) {
                    return 10000;
                }
                return floor( $stock / $new_row['weight_sale_pcs']);
            }
        )
        ->register_columns_recalc_callback(
            //缺货时间=生成时间+可用库存支撑天数
            'expect_exhaust_date',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname)  {
                //丢失python配置暂当做0处理
                if (isset($new_row['lost_python_cfg']) && true === $new_row['lost_python_cfg']) {
                    return $new_row['expect_exhaust_date'];
                }
                //python
                return date('Y-m-d', strtotime(sprintf('+%d days', $new_row['supply_day'])));
            }
        )
        ->register_columns_recalc_callback(
            //触发需求模式
            'require_qty',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname, $get_py_trans_colname, $future_valid_activities) {
                //丢失python配置暂当做0处理
                if (isset($new_row['lost_python_cfg']) && true === $new_row['lost_python_cfg']) {
                    $new_row['is_trigger_pr'] = TRIGGER_PR_NO;
                    $new_row['require_qty_second'] = 0;
                    return 0;
                }
                //停售sku，需求数量=欠货量
                if ($new_row['halt_the_sales'] == INLAND_HALT_SALE_YES ) {
                    return $new_row['debt_qty'];
                }
                //清仓sku
                if ($new_row['is_stop_clear_warehouse'] == STOP_CLEAN_WAREHOUSE_NO) {
                    return $new_row['debt_qty'];
                }
                $once_purchase_amount = $new_row['sc_day'] * $new_row['weight_sale_pcs'];
                // 需求数量 = 扩销系数 * (订购点 + 一次备货天数 - pr -采购在途 - 在库) + 一次修正量 + 活动量 + 欠货量
                // $require_qty = $new_row['expand_factor'] * ($new_row['point_pcs'] + $new_row['sc_day'] - $new_row['pr_qty'] - $new_row['ship_qty'] - $new_row['available_qty']) + $new_row['fixed_amount'] + $new_row['bd'] + $new_row['debt_qty'];
                // 变更版本： 需求数量 = (订购点 + 一次备货天数  + 一次修正量 + 活动量) - (pr + 采购在途 + 在库) * 扩销系数 + 欠货量
                // $require_qty = ($new_row['point_pcs']+$once_purchase_amount+$new_row['fixed_amount']+$new_row['bd']) - ($new_row['pr_qty'] + $new_row['ship_qty'] + $new_row['available_qty']) * $new_row['expand_factor'] + $new_row['debt_qty'];
                // 变更版本2： 需求数量= （订购点+ 加权日均销量 * 一次备货天数 ）*扩销系数 + 活动量+一次修正量 +欠货量。
                $require_qty = ($new_row['point_pcs'] + $once_purchase_amount) * $new_row['expand_factor'] + $new_row['bd'] + $new_row['fixed_amount'] + $new_row['debt_qty'];
                return $require_qty;
            }
        );

        $this->_ci->InlandRebuildPr->run();

        return  $this->_ci->InlandRebuildPr->report;

    }

    public function modify($params = [], $user_id = '', $user_name = '')
    {
        try {
            $now = date('Y-m-d H:i:s');
            if(empty($params['gid']))
            {
                return false;
            }
            $record = ($pk_row = $this->_ci->load->m_inland_pr_list->findByPk($params['gid'])) === null ? [] : $pk_row->toArray();
            if(empty($record))
            {
                return false;
            }
            $db = $this->_ci->m_inland_pr_list->getDatabase();
            $db->trans_start();
            //3.2.存在则更新,更新需求数量，更新时间刷新，状态待审核
            $update_data = [
                'gid' => $params['gid'],
                'fixed_amount' => $params['fixed_amount'],//需求数量
                'updated_at' => $now,//更新时间
                'updated_uid' => $user_id,//更新uid
                'approve_state' => INLAND_APPROVAL_STATE_INIT//审核状态
            ];
            $this->_ci->load->m_inland_pr_list->update_fixed_amount($update_data);

            $insert_log = [
                'gid' => $params['gid'],
                'uid' => $user_id,
                'user_name' => $user_name,
                'context' => "修改成功,原一次修正量:{$record['fixed_amount']},修改后一次修正量:{$params['fixed_amount']}",
            ];
            $this->_ci->load->model('Inland_pr_list_log_model', 'inland_pr_list_log', false, 'inland');
            $this->_ci->inland_pr_list_log->add($insert_log);
            $db->trans_complete();
            if ($db->trans_status() === FALSE) {
                return false;
            } else {
                return true;
            }

        } catch (Exception $e) {
            log_message('ERROR', sprintf("国内需求列表修改异常 %s", $e->getMessage()));
            throw new \RuntimeException(sprintf("国内需求列表修改异常 %s", $e->getMessage()), 500);
        }
    }

    public function import_batch($params = [], $user_id = '', $user_name = '')
    {
        $num = count($params);
        $report = [
            'total'      => $num,
            'processed'  => 0,
            'undisposed' => $num,
            'errorMess'  => ''
        ];
        $batch_update_pr = $line_num = [];
        $this->_ci->load->model('Inland_pr_list_log_model', 'm_inland_log', false, 'inland');

        foreach ($params as $key => $value) {
            $result_data = $this->_ci->m_inland_pr_list->get_info_by_sn($value['pr_sn']);
            if (!empty($result_data) && $result_data['fixed_amount'] != $value['fixed_amount'] && $result_data['approve_state'] !=INLAND_APPROVAL_STATE_SUCCESS) {
                $batch_update_pr[] = [
                    'gid'=> $result_data['gid'],
                    'fixed_amount' => $value['fixed_amount'],//一次修正量
                    'updated_at' => date('Y-m-d H:i:s'),//更新时间
                    'updated_uid' => $user_id,//更新uid
                    'approve_state' => INLAND_APPROVAL_STATE_INIT//审核状态
                ];
            }
            else{
                array_push($line_num,$value['line_num']);
            }
        }

        $db = $this->_ci->m_inland_pr_list->getDatabase();
        try
        {
            $db->trans_start();

            //批量更新
            $update_rows = 0;
            if(!empty($batch_update_pr))
            {
                $update_rows = $db->update_batch($this->_ci->m_inland_pr_list->getTable(), $batch_update_pr, 'gid');
                if (!$update_rows)
                {
                    $report['errorMess'] = '批量导入,更新失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '批量导入，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $update_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];
            $report['line_num'] = json_encode($line_num);

            //释放资源
            $batch_update_pr = null;
            unset($batch_update_pr);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('新品导入批量更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));
            $batch_update_pr = null;
            unset($batch_update_pr);

            $report['errorMess'] = '批量审核抛出异常：'.$e->getMessage();
            return $report;
        }
    }

    /**
     * 重新计算需求数量
     *
     * v1.3
     * 1.停售或清仓sku:
     * 需求数量=欠货量
     * 2.非停售或清仓sku:
     * 需求数量= 订购点+一次备货数量-（PR数量+采购在途+在库）*扩销系数+活动量+一次修正量+欠货量
     *
     * @param Record $record
     * @return number
     */
    protected function recalc_required_qty(Record $record)
    {
        if ($record->is_accelerate_sale == ACCELERATE_SALE_NO) {
            //否，正常， 是否处于7天内，时间戳
            $accelerate_sale_end_time = strtotime($record->accelerate_sale_end_time);
            if (false === $accelerate_sale_end_time) {
                $end_time = 0;
            } else {
                $end_time = $accelerate_sale_end_time + 86400;
            }
            $in_seven_days = $end_time + 86400 * 7 > time();
            if ($in_seven_days) {
                return 0;
            }
        }

        $sale_factor = $record->expand_factor;

        if ($record->trigger_mode == TRIGGER_MODE_INACTIVE)
        {
            return ceil($record->purchase_qty * $sale_factor);
        }
        elseif ($record->trigger_mode == TRIGGER_MODE_ACTIVE)
        {
            return ceil(($record->point_pcs + $record->purchase_qty + $record->bd + $record->fixed_amount - $record->available_qty - $record->exchange_up_qty - $record->oversea_ship_qty) * $sale_factor);
        }
        else
        {
            return ceil(($record->point_pcs + $record->purchase_qty + $record->bd + $record->fixed_amount - $record->available_qty - $record->exchange_up_qty - $record->oversea_ship_qty) * $sale_factor);
        }
    }

    /**
     * 批量审核
     *
     * @param unknown $post
     * @return unknown
     */
    public function batch_approve($post)
    {
        //前端审核状态由原来1,2 变成了 3 4
        $post['result'] = $post['result'] == INLAND_APPROVAL_STATE_SUCCESS ? APPROVAL_RESULT_PASS : APPROVAL_RESULT_FAILED;

        $valid_ids =is_string($post['gid']) ? explode(',', $post['gid']) : $post['gid'];

        $report = [
                'total'      => count($valid_ids),
                'processed'  => 0,
                'undisposed' => count($valid_ids),
                'errorMess'  => ''
        ];

        if (empty($valid_ids) || empty($post['gid'])) {
            $report['errorMess'] = '没有有效的审核记录';
            return $report;
        }

        $todo = $this->_ci->m_inland_pr_list->get_can_approve_for_first($valid_ids, []);
        $this->_ci->load->classes('inland/classes/InlandApprove');

        $this->_ci->InlandApprove
        ->set_model($this->_ci->m_inland_pr_list)
        ->set_approve_result($post['result'])
        ->set_selected_gids($valid_ids)
        ->recive($todo);

        $this->_ci->InlandApprove->run($this->_ci->InlandApprove::INLAND_APPROVE_MANUAL);
        $this->_ci->InlandApprove->send_system_log(self::$s_system_log_name);
        $result = $this->_ci->InlandApprove->report();

        $report['processed'] = $result['succ'];
        $report['undisposed'] = $report['total'] -  $result['succ'];
        $report['errorMess'] = $result['msg'];
        $report['is_addup'] = $result['is_addup'];

        return $report;

    }

    /**
     * 批量审核
     *
     * @param unknown $post
     * @return unknown
     */
    public function batch_approve_zc($post)
    {
        $valid_ids =is_string($post['gid']) ? explode(',', $post['gid']) : $post['gid'];

        if (empty($valid_ids) || empty($post['gid'])) {
            $report['errorMess'] = '没有有效的审核记录';
            return $report;
        }

        $report = [
            'total'      => count($valid_ids),
            'processed'  => 0,
            'undisposed' => count($valid_ids),
            'errorMess'  => ''
        ];

        $approve_state = intval($post['result']);
        $updated_at =  date('Y-m-d H:i:s');

        $batch_update_pr = $batch_insert_log = [];
        foreach ($valid_ids as $id)
        {
            $batch_update_pr[] = [
                'gid' => $id,
                'approve_state' => $approve_state,
                'approved_uid' => $post['user_id'],
                'approved_at' => $updated_at
            ];
            $batch_insert_log[] = [
                'gid' => $id,
                'uid' => $post['user_id'],
                'user_name' => $post['user_name'],
                'context' => '国内需求列表审核'.($approve_state == INLAND_APPROVAL_STATE_FAIL ? '失败' : '成功'),
            ];
        }

        $db = $this->_ci->m_inland_pr_list->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新
            $update_rows = 0;
            if(!empty($batch_update_pr))
            {
                $update_rows =  $db->update_batch($this->_ci->m_inland_pr_list->getTable(), $batch_update_pr, 'gid');
                if (!$update_rows)
                {
                    $report['errorMess'] = '批量审核,更新失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            //插入日志
            $this->_ci->load->model('Inland_pr_list_log_model', 'inland_pr_log', false, 'inland');
            $insert_rows = $this->_ci->inland_pr_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $report['errorMess'] = '批量审核日志插入失败';
                throw new \RuntimeException($report['errorMess']);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '批量审核，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $update_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('批量审核更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            $report['errorMess'] = '批量审核抛出异常：'.$e->getMessage();
            return $report;
        }
    }

    /**
     * 批量审核全部未审核的
     */
    public function batch_approve_all($params = [],$user_id=0,$user_name = '')
    {
        $result = [
            "total"=> 0,
            "processed"=>0,
            "undisposed"=>0
        ];
        $db = $this->_ci->m_inland_pr_list->getDatabase();
        try {
            //1.获取所有未审核的记录数
            $total = $db->get_checking_num();//获取总条数
            //2.审核所有未审核的记录
            $now = date('Y-m-d H:i:s');
            $db->trans_start();
            $update_data = [
                'approved_at' => $now,//更新时间
                'approved_uid' => $params['user_id'],//更新uid
                'approved_zh_name' => $params['user_name'],//更新uid
                'approve_state' => $params['result'],//审核状态
            ];
            $update_count = $db->update_approving_state($update_data);
            //3.添加日志
            if ($update_count > 0) {
                $new_state_name = INLAND_APPROVAL_STATE[$params['result']];
                $insert_log = [
                    'new_id' => 0,
                    'uid' => $user_id,
                    'user_name' => $user_name,
                    'context' => "国内需求列表批量审核{$now}所有未审核的为:{$new_state_name},总审核量为:{$update_count}",
                ];
                $this->_ci->load->model('Inland_pr_list_log_model', 'inland_pr_log', false, 'inland');
                $this->_ci->inland_pr_log->add($insert_log);
            }
            $db->trans_complete();
            $result['total'] = $total;
            $result['processed'] = $update_count;
            $result['undisposed'] = $total - $update_count;
        } catch (Exception $e) {
            log_message('ERROR', sprintf("国内需求列表批量审批全部未审核记录异常 %s", $e->getMessage()));
            throw new \RuntimeException(sprintf("国内需求列表批量审批全部未审核记录异常 %s", $e->getMessage()), 500);
        }
        finally
        {
            return $result;
        }
    }
}
