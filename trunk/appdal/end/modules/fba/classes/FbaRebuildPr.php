<?php

/**
 * fba重计算
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-09-03
 * @link
 * @throw
 */
class FbaRebuildPr
{

    /**
     *
     * @var unknown
     */
    private $ci;

    /**
     *
     * @var Fba_pr_list_model
     */
    private $model;


    /**
     * @var Fba_pr_list_log_model
     */
    private $log_model;

    /**
     * 事务由外部控制
     *
     * @var bool
     */
    protected $tran_start_out = false;

    /**
     * 引入python参数
     *
     * @var string
     */
    protected $enable_python = false;

    /**
     * 前置、后置 回调
     *
     * @var array
     */
    protected $inspect_callback = ['before' => [], 'after' => []];

    /**
     * 列回调
     *
     * @var array
     */
    protected $column_callback = [];

    /**
     * @var array
     */
    protected $langs;

    /**
     * @var User
     */
    protected $active_user;

    /**
     * 执行报表
     *
     * @var array
     */
    public $report = [
            'total'      => 0,
            'processed'  => 0,
            'undisposed' => 0,
            'errorMess'  => ''
    ];


    private $rebuild_cols_set = [
        'variable' => [
                'erpsku' => [
                    'max_lt', //最大供货周期
                    'max_safe_stock', //最大安全库存
                ],
                'sellersku' => [
                    'z',
                    'bs', //缓冲库存天数
                    'sc', //一次备货天数
                    'expand_factor', //扩销系数
                ],
                'list' => [
                    'fixed_amount'
                ]
        ],
        'recalc' => [
            //是否违禁
            'is_contraband',
            //指定备货
            'designates',
            //物流属性
            'logistics_id',
            //最大备货天数
            'max_sp',
            //最大供货周期
            'max_lt',
            //最大安全库存
            'max_safe_stock',
            //备货处理周期
            'sp',
            //listing状态  1运营  2非运营
            'listing_state',
            //delivery_cycle 发货周期
            'ext_trigger_info',
            //上架时效AS
            'as_up',
            //物流时效LS
            'ls',
            //打包时效PT
            'pt',
            //缓冲库存天数
            'bs',
            //一次备货天数 配置修改 sc
            'sc_day',
            //服务z值 sz
            'z',
            //扩销因子
            'expand_factor',
            //首发
            'is_first_sale',
            //是否丢失python配置
            'lost_python_cfg',
            //仓库编码
            'warehouse_code',
            //起订阈值
            'min_order_qty',
             //动销参数
            'is_accelerate_sale',
            //动销时间
            'accelerate_sale_end_time',

            /**计算修订量*/

            //bd
            'bd',
            //一次修正量
            'fixed_amount',
            //备货提前期(day)
            'pre_day',
            //订购点
            'point_pcs',
            //可支撑天数
            'supply_day',
            //订购数量
            'purchase_qty',
            //是否失去主动触发记录
            'is_lost_active_trigger',
            //触发需求模式
            'trigger_mode',
            //需求数量,
            'require_qty',
            //是否触发需求
            'is_trigger_pr',
            //是否计划审核
            'is_plan_approve',
            //健康度
            'inventory_health',
            //拒绝原因
            'deny_approve_reason',
            //安全库存
            'safe_stock_pcs'
        ],
        'python_factor' => [
            'sales_factor'         => '销售系数',
            'exhaust_factor'       => '断货系数',
            'warehouse_age_factor' => '超90库龄系数(1超2没)',
            'supply_factor'        => '可售卖天数',
            'z'                    => '服务Z值',
            'actual_safe_stock'    => '最大安全天数',
            'expand_factor'        => '扩销系数',
            'actual_bs'            => '缓存天数',
        ],
        //python与php的差异配置
        'diff_vars' => [
            'weight_sale_pcs'     => '加权日均销量',
            'sale_sd_pcs'         => '销量标准偏差',
            'sale_amount_15_days' => '15天销量',
            'sale_amount_30_days' => '30天销量',
            'available_qty'       => '可用库存',
            'oversea_ship_qty'    => '国际在途(pcs)',
            'exchange_up_qty'     => '待上架(pcs)',
            'avg_deliver_day'     => '交付标准偏差',
        ],
        'plan_backup_vars' => [

        ],
        'diff_var_prefix' => [
            'python' => '__py_',
            'php'    => '__pl_',
        ],
        //需求主系统
        'main_system' => 'python',
    ];

    /**
     * @var array
     */
    public $batch_update = [];

    /**
     * @var array
     */
    public $batch_insert_log = [];

    /**
     * construct
     */
    public function __construct()
    {
        $this->ci =& get_instance();

        $this->init_env_resource();
    }


    /**
     * 设置使用资源
     *
     * @return CsvReader
     */
    protected function init_env_resource() : FbaRebuildPr
    {
        set_time_limit(-1);

        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '0');

        $this->ci = CI::$APP;

        $this->active_user = get_active_user();

        $this->ci->load->model('Fba_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'fba');
        $this->ci->load->model('Fba_rebuild_backup_sku_model', 'm_rebuild_backup_sku', false, 'fba');
        $this->ci->load->model('Fba_python_cfg_model', 'm_python_cfg', false, 'fba');
        $this->ci->load->model('Fba_new_list_model', 'm_new_list', false, 'fba');
        $this->ci->load->model('Fba_exception_list_model', 'm_exception_list', false, 'fba');

        return $this;
    }

    /**
     * @param Fba_pr_list_model $model
     * @return FbaRebuildPr
     */
    public function set_model($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @param Model $log_model
     * @return CsvWrite
     */
    public function set_log_model($log_model)
    {
        $this->log_model = $log_model;
        return $this;
    }

    /**
     * 设置外部控制事务
     */
    public function enable_out_control_trans()
    {
        $this->tran_start_out = true;
        return $this;
    }

    public function enable_python_cfg()
    {
        $this->enable_python = true;
        $this->rebuild_cols_set['main_system'] = 'python';
        return $this;
    }

    /**
     * 返回前缀
     *
     * @return Array
     */
    public function get_prefix()
    {
        return $this->rebuild_cols_set['diff_var_prefix'];
    }

    public function get_main_system()
    {
        return $this->rebuild_cols_set['main_system'];
    }

    /**
     * 初始化report
     *
     * @param unknown $todo
     */
    protected function init_report()
    {
        $this->_report = [
            'total'      => 0,
            'processed'  => 0,
            'undisposed' => 0,
            'errorMess' => '',
        ];
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
     * 注册前置、后置回调
     *
     * @param string $inspect_point before|after
     * @param array|callable $cb
     * @return CsvWrite
     */
    public function register_callback($inspect_point, $cb, $params = [])
    {
        $this->inspect_callback[$inspect_point][] = ['cb' => $cb, 'params' => $params];

        return $this;
    }

    private function fetch_lock($lock_key, $cache_key)
    {
        log_message('INFO', sprintf('进程：%d 开始获取可读记录锁', getmygid()));

        $counter = 60;
        while ($counter > 0 && !($set_lock = $this->ci->rediss->command(sprintf('setnx %s 1', $lock_key))))
        {
            sleep(1);
            $counter --;
            log_message('INFO', sprintf('进程：%d 获取锁，获取锁失败， 计数器：%d 结果：%s', getmygid(), $counter, strval($set_lock)));
        }
        if ($set_lock) {
            $gid = $this->ci->rediss->command('get '.$cache_key);
            $gid = (string)$gid;
            if ($gid == 'finish') {
                //有人到达终点 或执行捞取工作，自己退出，让先到达的进程将余下事情做完
                log_message('INFO', sprintf('进程：%d 获取锁，但已有进程先到达，开始准备推出', getmygid()));
                return false;
            }
            log_message('INFO', sprintf('进程：%d 获取锁成功，当前最大gid：%s，开始准备获取列表', getmygid(), $gid));
            return $gid;
        }

        log_message('INFO', sprintf('进程：%d 60秒抢锁失败，将退出', getmygid()));
        return false;
    }

    private function register_running_cache($gid, $cache_key)
    {

        //$this->ci->rediss->command('del '.$lock_key);
        //$this->ci->rediss->command('set '.$cache_key.' '.$gid.' EX 60');
        $command = "eval \"local gid = redis.call('get', KEYS[1]); if (gid) then if (type(gid) == 'string' and gid < KEYS[2]) then redis.call('set', KEYS[1], KEYS[2], 'EX', 60); return 'SUCC'; else return 'FAIL'; end; else redis.call('set', KEYS[1], KEYS[2], 'EX', 60); return 'SUCC'; end;\" 2 %s %s";
        $result = $this->ci->rediss->eval_command(sprintf($command, $cache_key, $gid));
        log_message('INFO', sprintf('写入cache key: %s, gid：%s, 状态：%s', $cache_key, $gid, $result));

    }

    private function reset_gid($cache_key)
    {
        $this->ci->rediss->command('del '.$cache_key);
    }

    private function fork_process($key, $cmd)
    {
        $num = intval($this->ci->rediss->getData($key));
        if ($num == 0) {
            shell_exec($cmd);
            $this->ci->rediss->setData($key, 2, 120);
            return true;
        } elseif ($num >= REBUILD_PROCESS_NUMS) {
            return false;
        } else {
            shell_exec($cmd);
            $this->ci->rediss->setData($key, $num + 1, 120);
            return true;
        }
    }

    protected function sampling($lock_key, $pool_key)
    {
        $gid =  $this->ci->rediss->command('rpop '.$pool_key);

        if (!$gid) {
            //完成
            return true;
        }
        return $gid;
    }

    protected function init_rebuild_version($version, $lock_key, $pool_key, $pid)
    {
        $this->ci->rediss->setData($lock_key, $version, 3500);

        if (!$this->ci->rediss->getData($lock_key)) {
            log_message('INFO', sprintf('pid:%d 设置运行锁失败，进程退出', $pid));

            //鎖不存在，異常丢弃
            $this->ci->rediss->command('del '.$pool_key);

            return false;
        }

        //初始化gid
        $gids = $this->model->sampling($this->model->get_top_bottom_gid());

        //每次pop 500ge
        $gid_arr = [];
        foreach ($gids as $key => $val)
        {
            $gid_arr[] = $val['gid'];
            if ($key != 0 && $key % 50 == 0) {
                $this->ci->rediss->command('lpush '.$pool_key.' '.implode(' ', $gid_arr));
                $gid_arr = [];
            }
        }
        if (!empty($gid_arr)) {
            $this->ci->rediss->command('lpush '.$pool_key.' '.implode(' ', $gid_arr));
            $gid_arr = [];
        }

        log_message('INFO', sprintf('pid:%d version:%d 缓存cache:%s 已经设置', $pid, $version, $lock_key));

        return true;
    }

    /**
     * state=2异常， 配置生成成功，但计算第一次就抛出异常
     */
    protected function reset_exception_build($version, $lock_key, $pool_key, $cache_key, $pid)
    {
        //删除运行锁
        $this->ci->rediss->deleteData($lock_key);
        log_message('INFO', sprintf('pid:%d 重建需求版本：%d 重置错误，清理删除运行锁 key： %s', $pid, $version, $lock_key));

        //删除最大gid
        $this->ci->rediss->command('del '.$cache_key);
        log_message('INFO', sprintf('pid:%d 重建需求版本：%d 重置错误， 清理删除最大gid key： %s', $pid, $version, $cache_key));

        //删除pop池
        $this->ci->rediss->command('del '.$pool_key);
        log_message('INFO', sprintf('pid:%d 重建需求版本：%d 重置错误， 清理删除任务池 key： %s', $pid, $version, $pool_key));

        return $this->init_rebuild_version($version, $lock_key, $pool_key, $pid);

    }

    /**
     * state=3异常，执行部分，中间有异常退出,
     *
     * 处理：重置标记
     */
    protected function reset_exception_building($version, $lock_key, $pool_key, $cache_key, $pid)
    {
        return $this->reset_exception_build($version, $lock_key, $pool_key, $cache_key, $pid);
    }

    public function run()
    {
        $this->hook('before');

        $update_time     = time();
        $updated_uid     = $this->active_user->staff_code;
        $updated_name    = $this->active_user->user_name;
        $chunk_size      = 500;
        $pid             = getmypid();

        $this->ci->load->library('Rediss');

        $start_execute_time = microtime(true);
        $start_memory = memory_get_usage(true);

        log_message('INFO', sprintf('pid:%d 重建需求列表时间， 起始内存：%s, 起始时间：%s', $pid, ($start_memory/1024/1024).'M', $start_execute_time.'s'));

        //备份取配置
        list($result, $version_info) = $this->create_rebuild_mvcc_and_backup();

        $step_execute_time = microtime(true);
        $step_execute_memory = memory_get_usage(true);
        log_message('INFO', sprintf('pid:%d version:%d， 备份消耗内存：%s, 总内存：%s, 备份时间消耗时间：%s', $pid, $version_info['version'], (($step_execute_memory - $start_memory)/1024/1024).'M', ($step_execute_memory/1024/1024).'M', ($step_execute_time - $start_execute_time).'s'));

        if (false === $result && is_bool($version_info) && false === $version_info)
        {
            $this->report['errorMess'] = '备份配置失败';
            return false;
        }

        $nums                       = 1;
        $cache_key                  = 'rebuild_max_gid_'.$version_info['version'];
        $lock_key                   = 'rebuild_lock_key_'.$version_info['version'];
        $version                    = $version_info['version'];
        $pool_key                   = 'rebuild_gid_pool_'.$version;
        $offset                     = $version_info['offset'];
        $processed                  = $version_info['processed'];
        $this->report['total']      = intval($version_info['total']);
        $this->report['undisposed'] = $this->report['total'] - $processed;

        $my_max_gid = '';
        $start_gid = false;
        $total_exception_nums = 0;

        $diff_varx_py_prefix = $this->rebuild_cols_set['diff_var_prefix']['python'];
        $diff_varx_php_prefix = $this->rebuild_cols_set['diff_var_prefix']['php'];

        $plan_log_save_col_names = implode(',', $this->rebuild_cols_set['diff_vars']);

        $recalc_cols = array_flip($this->rebuild_cols_set['recalc']);
        $table_cols = array_flip($this->model->get_table_cols());

        $update_cols = array_intersect_key($recalc_cols, $table_cols);



        //设置开启变量
        if (true === $result) {
            $init_rebuild_result = $this->init_rebuild_version($version, $lock_key, $pool_key, $pid);
            if (false === $init_rebuild_result) {
                return false;
            }
        } else {
            //后续加入的流程
            $lock = (bool)$this->ci->rediss->getData($lock_key);
            if (!$lock) {
                if (intval($this->ci->rediss->command('llen '.$pool_key)) > 0) {
                    //重建锁
                    log_message('INFO', sprintf('pid:%d  version:%d 我是后来的进程，运行锁不存在，但队列还在，重建锁 ', $pid, $version));
                    $this->ci->rediss->setData($lock_key, $version, 3600);
                } else {
                    //当前版本异常退出，state=2 重新跑
                    if ($version_info['state'] == REBUILD_CFG_BACKUP_OVER) {
                        log_message('INFO', sprintf('pid:%d 重建需求版本：%d ，state=2 但第一次计算就异常退出， 开始重建', $pid, $version));
                        if (false === $this->reset_exception_build($version, $lock_key, $pool_key, $cache_key, $pid)) {
                            return false;
                        }
                    } elseif ($version_info['state'] == REBUILD_CFG_BUILDING) {
                        //当前版本异常，state=3 已部分跑，重置重新跑
                        log_message('INFO', sprintf('pid:%d 重建需求版本：%d ，state=3 已经审核完：%d， 开始重建', $pid, $version, $version_info['processed']));
                        if (false === $this->reset_exception_building($version, $lock_key, $pool_key, $cache_key, $pid)) {
                            return false;
                        }
                    } else {
                        //锁不存在了， 不进行操作
                        log_message('INFO', sprintf('pid:%d version:%d 我是后来的进程，锁不存在，队列为空， 执行应该已经完成，进程退出 ', $pid, $version));
                        return false;
                    }

                }
            } else {
                log_message('INFO', sprintf('pid:%d version:%d 我是后来进程，锁存在，队列存在，开始获取gid工作', $pid, $version));
            }
        }

        $fetch_before_execute_time = $step_execute_time;
        $fetch_before_execute_memory = $step_execute_memory;

        while ( ($start_gid = $this->sampling($lock_key, $pool_key)) && (true !== $start_gid))
        {
            $recalc_pr = $this->get_rebuild_pr_by_gid($chunk_size, $start_gid);
            if (empty($recalc_pr)) {
                continue;
            }

            $fetch_after_execute_time = microtime(true);
            $fetch_after_execute_memory = memory_get_usage(true);

            log_message('INFO', sprintf('pid:%d version:%d，第%d次取值， 取列表消耗内存：%s, 总内存：%s, 取列表消耗时间:%s', $pid, $version, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s'));
            //log_message('INFO', sprintf('pid:%d,version:%d, 第%d次取值，获取gid：%s',$pid, $version, $nums, implode(',',array_column($recalc_pr, 'gid'))));

            $fetch_before_execute_time = $fetch_after_execute_time;
            $fetch_before_execute_memory = $fetch_after_execute_memory;

            $my_max_gid = $recalc_pr[count($recalc_pr) - 1]['gid'];
            $this->register_running_cache($my_max_gid, $cache_key);

            $batch_update = $batch_insert_log = $aggr_ids = $listing_aggr_ids = $aggr_new_ids = $lost_python_ids = $row = [];

            //开始还原php版本的参数
            foreach ($recalc_pr as $key => &$row)
            {
                $row['py_prefix'] = $diff_varx_py_prefix;
                $row['php_prefix'] = $diff_varx_php_prefix;

                $row['aggr_id'] = md5($row['account_id'].trim($row['account_num']).trim($row['sku']).trim($row['seller_sku']).trim($row['fnsku']).trim($row['asin']));
                $row['aggr_new_id'] = md5(trim($row['account_id']).strtolower($row['station_code']).trim($row['seller_sku']).trim($row['sku']).trim($row['fnsku']).trim($row['asin']));

                $aggr_ids[] = $row['aggr_id'];
                $aggr_new_ids[] = $row['aggr_new_id'];
                //ext_plan_rebuild_vars
                if ($this->enable_python) {

                    $row['py_diff_aggr_id'] = $row['aggr_id'];
                    $listing_aggr_ids[] = $row['py_diff_aggr_id'];

                    //第一次跑需求，将差异值的php全部加上前缀保存
                    if (intval($row['version']) === 0 && empty($row['ext_plan_rebuild_vars'])) {
                        foreach ($this->rebuild_cols_set['diff_vars'] as $col => $val)
                        {
                            if (isset($row[$col])) {
                                $row[$diff_varx_php_prefix.$col] = $row[$col];
                            }
                        }
                    } elseif (intval($row['version']) !== 0 && !empty($row['ext_plan_rebuild_vars'])) {
                        $plan_saved_vars = ($this->ci->prservice)::parse_ext_plan_rebuild_vars($row['ext_plan_rebuild_vars'], $diff_varx_php_prefix);
                        if (!empty($plan_saved_vars)) {
                            foreach ($plan_saved_vars as $ext_col => $ext_val) {
                                $row[$ext_col] = $ext_val;
                            }
                        }
                    }
                }
            }
            //页面修改最新的参数
            $backup_cfg = $this->ci->m_rebuild_backup_sku->get_config_by_aggr_ids($version, $aggr_ids);

            //python覆盖的参数
            if ($this->enable_python)
            {
                $python_cfg = $this->ci->m_python_cfg->get_config_by_aggr_ids($listing_aggr_ids);
            }

            //首发新品检测
            $first_sale_cfg = $this->ci->m_new_list->get_demand_by_ids($aggr_new_ids);

            reset($recalc_pr);
            foreach ($recalc_pr as $key => &$row)
            {
                if ($row['approve_state'] != APPROVAL_STATE_FIRST || $row['version'] == $version) {
                    log_message('INFO', sprintf('pid:%d  version:%d，第%d次取值，该记录:%s不能处理或已处理：状态：%d, 版本：%d。', $pid, $version, $nums, $row['pr_sn'], $row['approve_state'], $version));
                    continue;
                }
                $log_context = [];

                $tmp = [
                        'gid' => $row['gid'],
                        'updated_uid' => $updated_uid,
                        'updated_at' => $update_time,
                        'version' => $version,
                ];

                //新品首发量处理
                if (array_key_exists($row['aggr_new_id'], $first_sale_cfg)) {
                    $first_require_qty = intval($first_sale_cfg[$row['aggr_new_id']] ?? 0);
                    if ($first_require_qty === 0) {
                        $tmp['is_first_sale'] = FBA_FIRST_SALE_NO;
                        $tmp['is_trigger_pr'] = TRIGGER_PR_NO;
                        $tmp['trigger_mode'] = TRIGGER_MODE_NONE;
                        //计算的时候，已经不是首发, 需求为0
                        log_message('INFO', sprintf('pid:%d  version:%d，第%d次取值，需求单号：%s 初始是首发，实时计算时已不是首发，按照非首发开始计算', $pid, $version, $nums, $row['pr_sn']));

                        $tmp['require_qty_second'] = $tmp['require_qty'] = $first_require_qty ;
                        $batch_update[] = $tmp;

                        $batch_insert_log[] = [
                                'gid' => $row['gid'],
                                'uid' => $updated_uid,
                                'user_name' => $updated_name,
                                'context' => sprintf('初始为首发新品，重新计算时已不符合首发新品状态，需求数量设置0，实际：%d', $tmp['require_qty']),
                        ];
                        continue;
                    } else {
                        $tmp['is_first_sale'] = FBA_FIRST_SALE_YES;
                        $tmp['is_trigger_pr'] = TRIGGER_PR_YES;
                        $tmp['sku_state'] = SKU_STATE_UP;
                        $tmp['is_refund_tax'] = $row['is_refund_tax'] == REFUND_TAX_UNKNOWN ?  REFUND_TAX_NO : $row['is_refund_tax'];
                        $tmp['require_qty_second'] = $tmp['require_qty'] = $first_require_qty ;
                        $tmp['inventory_health'] = INVENTORY_HEALTH_WELL;
                        $tmp['deny_approve_reason'] = DENY_APPROVE_NONE;
                        $tmp['trigger_mode'] = TRIGGER_MODE_ACTIVE;
                        $batch_update[] = $tmp;

                        $batch_insert_log[] = [
                                'gid' => $row['gid'],
                                'uid' => $updated_uid,
                                'user_name' => $updated_name,
                                'context' => sprintf('新品首发量为%d', $tmp['require_qty']),
                        ];
                        continue;
                    }
                }

                if (!isset($backup_cfg[$row['aggr_id']]))
                {
                    log_message('ERROR', sprintf('pid:%d version:%d，第%d次取值，无法获取pr_sn:%s计划配置,aggr_id:%s, 参数：%s 值：%s', $pid, $version, $nums, $row['pr_sn'], $row['aggr_id'], 'account_id,account_num,sku,seller_sku,fnsku,asin,station_code,logistics_id', implode(',', [$row['account_id'],trim($row['account_num']),trim($row['sku']), trim($row['seller_sku']),trim($row['fnsku']),trim($row['asin']),$row['station_code'],$row['logistics_id']])));

                    $tmp['is_trigger_pr'] = TRIGGER_PR_NO;
                    $tmp['trigger_mode'] = TRIGGER_MODE_NONE;
                    $tmp['lost_python_cfg'] = 3;
                    $tmp['require_qty_second'] = $tmp['require_qty'] = 0 ;

                    $batch_insert_log[] = [
                            'gid' => $row['gid'],
                            'uid' => $updated_uid,
                            'user_name' => $updated_name,
                            'context' => sprintf('无法获取本地配置，没有重新计算'),
                    ];

                    $batch_update[] = $tmp;

                    continue;
                }
                if ($this->enable_python && !isset($python_cfg[$row['py_diff_aggr_id']]))
                {
                    log_message('ERROR', sprintf('pid:%d version:%d，第%d次取值，无法获取pr_sn:%s PYTHON配置,aggr_id:%s, 参数：%s 值：%s, 标记异常，需求数量为0', $pid, $version, $nums, $row['pr_sn'], $row['aggr_id'], 'account_id,account_num,sku,seller_sku,fnsku,asin,station_code,logistics_id', implode(',', [$row['account_id'],trim($row['account_num']),trim($row['sku']), trim($row['seller_sku']),trim($row['fnsku']),trim($row['asin']),$row['station_code'],$row['logistics_id']])));
                    $log_context[] = '该记录无法找到python配置，标记异常，需求数量为0';
                    $ext_trigger_items = ($this->ci->prservice)::parse_ext_trigger_info($row['ext_trigger_info']);
                    $row['sale_amount_15_days'] = $ext_trigger_items[1] ?? 0;
                    $row['sale_amount_30_days'] = $ext_trigger_items[2] ?? 0;
                    $row['lost_python_cfg'] = 1;

                    $lost_python_ids[] = $row['gid'];

                    //$batch_update[] = $tmp;
                } else {
                    $row['lost_python_cfg'] = 2;
                }

                //保存旧值
                $old_row = $row;

                //覆盖配置
                if ($this->enable_python) {
                    $row = array_merge($row, $backup_cfg[$row['aggr_id']] ?? [], $python_cfg[$row['py_diff_aggr_id']] ?? []);
                } else {
                    $row = array_merge($row, $backup_cfg[$row['aggr_id']]);
                }

                //print_r($row);exit;
                //reset($this->column_callback);
                foreach ($this->column_callback as $cb_col => $cb)
                {
                    $old_val = $old_row[$cb_col];

                    if (is_callable($cb['cb']))
                    {
                        $row[$cb_col] = $cb['cb'] instanceof Closure ?

                        //新row， 旧row， 附加
                        $cb['cb']($row, $cb['params'] ?? []) :

                        call_user_func_array($cb['cb'], array($row, $cb['params'] ?? []));

                    }
                    else
                    {
                        throw new \RuntimeException('重新计算需求列表列：'.$cb_col.'回调无法调用');
                    }
                }

                foreach ($update_cols as $upt_col => $val)
                {
                    if (($old_val = $old_row[$upt_col]) != $row[$upt_col]) {
                        $log_context[] = sprintf('列%s由%s修改为%s', $this->langs[$upt_col] ?? $upt_col, $old_val, $row[$upt_col]);
                    }
                    $tmp[$upt_col] = $row[$upt_col];
                }

                $log_context[] = '【系数配置开始】';
                    foreach ($this->rebuild_cols_set['python_factor'] as $col => $name) {
                        $log_context[] = sprintf('%s的值%s', $name, $row[$col] ?? '丢失');
                        $tmp[$col] = $row[$col];
                    }

                if ($this->enable_python) {
                    $log_context[] = '【差异配置开始】';
                    foreach ($this->rebuild_cols_set['diff_vars'] as $col => $name) {
                        $log_context[] = sprintf('%s的值%s', $name, $row[$col] ?? '丢失');
                        if ($col == 'sale_amount_15_days' || $col == 'sale_amount_30_days' || $col == 'delivery_cycle') {
                            //不需要保存字段
                        } else {
                        $tmp[$col] = $row[$col];
                    }

                    }
                    //将php版本计算的参数存入
                    $php_ext_vars = [];
                    foreach ($this->rebuild_cols_set['diff_vars'] as $col => $cnname)
                    {
                        $php_ext_vars[$col] = $row[$diff_varx_php_prefix.$col];
                    }
                    $diff = $row['require_qty_second']-$row['require_qty'];
                    $diff_require_qty = $diff == 0 ? '一致' : ($diff > 0 ? '+'.$diff : $diff);
                    $log_context[] = '【计划计算值】需求数量：'.$row['require_qty_second'].',差异：'.$diff_require_qty.' '.$plan_log_save_col_names.'依次为：'.implode(',', $php_ext_vars);
                    $tmp['ext_plan_rebuild_vars'] = json_encode($php_ext_vars);
                    $tmp['require_qty_second'] = $row['require_qty_second'];
                } else {
                    $log_context[] = ' 无python配置';
                }

                $batch_update[] = $tmp;

                $batch_insert_log[] = [
                    'gid' => $row['gid'],
                    'uid' => $updated_uid,
                    'user_name' => $updated_name,
                    'context' => mb_substr(implode(',', empty($log_context) ? ['没有任何更新'] : $log_context), 0, 400),
                ];
            }

            $fetch_after_execute_time = microtime(true);
            $fetch_after_execute_memory = memory_get_usage(true);

            log_message('INFO', sprintf('pid:%d  version:%d，第%d次取值， 生成执行数据消耗内存：%s, 总内存：%s, 生成执行数据消耗时间:%s', $pid, $version, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s'));

            $fetch_before_execute_time = $fetch_after_execute_time;
            $fetch_before_execute_memory = $fetch_after_execute_memory;

            if (empty($batch_update)) {
                log_message('ERROR', sprintf('pid:%d version:%d，第%d次取值，数据为空, 选择的数据可能都已审核或已经处理', $pid, $version, $nums));
                continue;
            }

            //更新
            $common_db = $this->model->getDatabase();
            $stock_db = $this->ci->m_rebuild_mvcc->getDatabase();

            try
            {
                $common_db->trans_start();
                $stock_db->trans_start();
                $row_count = count($batch_update);

                //批量更新主记录
                $update_rows = $common_db->update_batch($this->model->getTable(), $batch_update, 'gid');
                if (!$update_rows)
                {
                    $this->report['errorMess'] = '重新需求更新失败';
                    throw new \RuntimeException($this->report['errorMess']);
                }

                //记录差异数量
                if (!empty($lost_python_ids)) {
                    $total_exception_nums += count($lost_python_ids);
                    $save_rows = $this->ci->m_exception_list->save_exception_list($lost_python_ids);
                    log_message('INFO', sprintf('pid:%d version:%d，第%d次取值，异常记录数量：%d 转存成功：%d, 总异常数：%d', $pid, $version, $nums, count($lost_python_ids), $save_rows, $total_exception_nums));
                }

                //插入日志
                $insert_rows = $this->log_model->madd($batch_insert_log);
                if (!$insert_rows)
                {
                    $this->report['errorMess'] = '日志批量插入失败';
                    throw new \RuntimeException($this->report['errorMess']);
                }

                $update_mvcc = [
                        'state' => REBUILD_CFG_BUILDING,
                        'offset' => $offset + $row_count,
                        'processed' => $processed + $row_count,
                ];

                $update_mvcc_sql = sprintf(
                    'update %s set state = %d, offset = offset + %d, processed = processed + %d where version = %d',
                    $this->ci->m_rebuild_mvcc->getTable(),
                    REBUILD_CFG_BUILDING, $row_count, $row_count, $version
                    );

                if (!$stock_db->query($update_mvcc_sql))
                {
                    $this->report['errorMess'] = '更新mvcc配置表失败';
                    throw new \RuntimeException($this->report['errorMess']);
                }
                /*$stock_db->reset_query();
                $stock_db->where('version', $version);
                $update_rows = $stock_db->update($this->ci->m_rebuild_mvcc->getTable(), $update_mvcc);
                if (!$update_rows)
                {
                    $this->report['errorMess'] = '更新mvcc配置表失败';
                    throw new \RuntimeException($this->report['errorMess']);
                }*/

                $common_db->trans_complete();
                $stock_db->trans_complete();

                if ($common_db->trans_status() === false || $stock_db->trans_status() === false)
                {
                    //throw new \RuntimeException(sprintf('Csv批量修改更新，事务提交成功，但状态检测为false'), 500);
                    $this->errorMess = '需求重建，事务提交成功，但状态检测为false';
                    throw new \RuntimeException($this->report['errorMess']);return false;
                }

                $offset += $row_count;
                $processed += $row_count;
                $nums ++;

                $this->report['processed'] += $row_count;
                $this->report['undisposed'] = $this->report['total'] - $this->report['processed'];

                $fetch_after_execute_time = microtime(true);
                $fetch_after_execute_memory = memory_get_usage(true);

                log_message('INFO', sprintf('pid:%d version:%d，第%d次取值， 提交事务数据消耗内存：%s, 总内存：%s, 提交事务数据消耗时间:%s', $pid, $version, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s'));

                $fetch_before_execute_time = $fetch_after_execute_time;
                $fetch_before_execute_memory = $fetch_after_execute_memory;

            }
            catch (\Throwable $e)
            {
                $common_db->trans_rollback();
                $stock_db->trans_rollback();

                log_message('INFO', sprintf('pid:%d 需求重建批量修改更新%s，提交事务出现异常: %s', $pid, json_encode($batch_update), $e->getMessage()));

                $update_batch = $batch_insert_log = null;
                unset($update_batch, $batch_insert_log);

                $this->report['errorMess'] = '需求重建修改更新，抛出异常：'.$e->getMessage();

                //执行完毕,幂等，执行
                $inactive_rows = $this->model->update_rebuld_trigger_unknown_mode($version);
                log_message('INFO', sprintf('pid:%d 重建需求版本：%d 被动模式未确认的更新数量：%d', $pid, $version, $inactive_rows));

                return false;
            }
        }
        //redis队列为空
        if (true === $start_gid) {
            $max_gid = $this->ci->rediss->command('get '.$cache_key);
            if ($max_gid && $max_gid == $my_max_gid) {
                log_message('INFO', sprintf('pid:%d 重建需求版本：%d 执行结束，当前最大gid:%s, 我的最大gid：%s， 我是最后一个进程，执行完成操作', $pid, $version, $max_gid, $my_max_gid));
                //没有最大值
            } else {
                log_message('INFO', sprintf('pid:%d 重建需求版本：%d 执行结束，当前最大gid:%s, 我的最大gid：%s 我不是最后一个进程，不做处理退出', $pid, $version, $max_gid, $my_max_gid));
                return false;
            }
        } else {
            log_message('INFO', sprintf('pid:%d 重建需求版本：%d 执行结束异常分支，起始start_gid: %s 当前最大gid:%s, 我的最大gid：%s 没有获取到需重算记录值，不做处理退出', $pid, $version, $start_gid, $max_gid, $my_max_gid));
            return false;
        }

        //删除最大gid
        $this->ci->rediss->command('del '.$cache_key);
        log_message('INFO', sprintf('pid:%d 重建需求版本：%d 删除最大gid key： %s', $pid, $version, $cache_key));

        //删除运行锁
        $this->ci->rediss->deleteData($lock_key);
        log_message('INFO', sprintf('pid:%d 重建需求版本：%d 删除运行锁 key： %s', $pid, $version, $lock_key));

        //删除pop池
        $this->ci->rediss->command('del '.$pool_key);
        log_message('INFO', sprintf('pid:%d 重建需求版本：%d 删除任务池 key： %s', $pid, $version, $pool_key));

        //执行完毕,幂等，执行
        $inactive_rows = $this->model->update_rebuld_trigger_unknown_mode($version);
        log_message('INFO', sprintf('pid:%d 重建需求版本：%d 被动模式未确认的更新数量：%d', $pid, $version, $inactive_rows));

        //执行完毕, 更新版本号
        $update_mvcc = [
            'state' => REBUILD_CFG_FINISHED,
            'processed' => $this->report['total'],
            'offset' => $this->report['total'],
        ];

        $stock_db = $this->ci->m_rebuild_mvcc->getDatabase();
        $stock_db->where('version', $version);
        $stock_db->update($this->ci->m_rebuild_mvcc->getTable(), $update_mvcc);

        //投递一个计算金额的异步任务
        $this->register_callback('after', function($params)  {
            $ci = CI::$APP;
            property_exists($ci, 'Task') OR $ci->load->classes('basic/classes/Task');
            return $ci->Task->delivery(['fba', 'PR', 'asyc_estimate_rebuild_money'], [$params['version']]);
        }, ['version' => $version]);

        $this->hook('after');

        return true;
    }

    protected function get_config($version, $aggr_ids)
        {
        $configs = $this->ci->m_rebuild_backup_sku->get_config_by_aggr_ids($version, $aggr_ids);
        return $configs;
    }

    protected function sync_newest_config($version, &$recalc_pr)
    {
        $aggr_ids = [];
        foreach ($recalc_pr as $key => &$row)
        {
            $row['aggr_id'] = md5($row['account_id'].trim($row['account_num']).trim($row['seller_sku']).trim($row['fnsku']).trim($row['asin']).$row['station_code'].$row['logistics_id']);
            $aggr_ids[$row['aggr_id']] = true;
        }
        $configs = $this->ci->m_rebuild_backup_sku->get_config_by_aggr_ids($version, array_keys($aggr_ids));
        reset($recalc_pr);
        foreach ($recalc_pr as $key => &$row)
        {
            if (isset($configs[$row['aggr_id']])) {
                $row = array_merge($row, $configs[$row['aggr_id']]);
            } else {
                $row['lost_aggr_id'] = true;
                log_message('ERROR', sprintf('需求gid:%s， 汇总id:%s没有找到配置', $row['gid'], $row['aggr_id']));
            }
        }
        $configs = NULL;
        unset($configs);
        return true;
    }

    protected function get_rebuild_pr_chunk($version, $chunk_size, $start_gid)
    {
        log_message('INFO', sprintf('开始获取列表：版本：%s, limit: %d, 开始gid：%s', $version, $chunk_size, $start_gid));
        return $this->model->get_rebuild_chunk($version, $chunk_size, $start_gid);
    }

    protected function get_rebuild_pr_by_gid($chunk_size, $start_gid)
    {
        log_message('INFO', sprintf('开始获取gid列表：, limit: %d, 开始gid：%s', $chunk_size, $start_gid));
        return $this->model->get_rebuild_gid($start_gid, $chunk_size);
    }


    /**
     * false 失败， 最新版本号， 旧版本执行情况
     * @return bool|int|version_old_row
     */
    protected function create_rebuild_mvcc_and_backup()
    {
        $db = $this->ci->m_rebuild_mvcc->getDatabase();
        $db->trans_start();

        $add_result = $this->ci->m_rebuild_mvcc->add(BUSSINESS_FBA);
        //新增
        if (true === $add_result[0]) {
            $affected_rows = $this->ci->m_rebuild_backup_sku->backup(intval($add_result[1]['version']));
            if ($affected_rows == 0) {
                log_message('ERROR', '备份配置表插入数量'.$affected_rows);
                $db->trans_rollback();
               return [false, false];
            }
        }
        $db->trans_complete();
        return $add_result;
    }

    protected function hook($pos)
    {
        $result = [];
        //前置调用
        if (!empty($this->inspect_callback[$pos])) {
            foreach ($this->inspect_callback[$pos] as $cb)
            {
                if (isset($cb['cb']) && is_callable($cb['cb'])) {
                    $result[] = $cb['cb'] instanceof Closure ?
                    (isset($cb['params']) ?  $cb['cb']($cb['params']) : $cb['cb']()) :
                    call_user_func_array($cb['cb'], $cb['params'] ?? []);

                    log_message('INFO', sprintf('FbaRebuildPr.hook %s调用返回结果：%s', $pos, json_encode($result)));
                }
            }
        }
        return $result;
    }

    protected function recalc()
    {
        //计算各项值
    }

    /**
     * 列回调
     * @param string $column
     * @param callable $cb
     */
    public function register_columns_recalc_callback($column, $cb, $params = [])
    {
        $this->column_callback[$column] = ['cb' => $cb, 'params' => $params];
        return $this;
    }

    /**
     * 语言索引
     *
     * @param string $lang_index
     */
    public function set_langage($lang_index)
    {
        $this->ci->lang->load('common');
        $all_lines = $this->ci->lang->line($lang_index);

        if (!empty($this->rebuild_cols_set['recalc'])) {
            foreach ($all_lines as $line) {
                if (isset($line['field']) && isset($line['label']) && in_array($line['field'], $this->rebuild_cols_set['recalc'])) {
                    $this->langs[$line['field']] = $line['label'];
                }
            }
        }

        foreach ($this->rebuild_cols_set['recalc'] as $col)
        {
            if (!isset($this->langs[$col])) {
                $this->langs[$col] = $col;
            }
        }

        //设置python系数
        foreach ($this->rebuild_cols_set['python_factor'] as $col => $name) {
            $this->langs[$col] = $name;
        }
        //设置python差异参数
        foreach ($this->rebuild_cols_set['diff_vars'] as $col => $name) {
            $this->langs[$this->rebuild_cols_set['diff_var_prefix']['python'].$col] = $name;
        }

        return $this;
    }


}