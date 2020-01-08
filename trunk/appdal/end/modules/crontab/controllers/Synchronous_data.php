<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 同步测试库数据
 * @author W02278
 * @name Synchronous_data Class
 */
class Synchronous_data extends MX_Controller {

    /**
     * @var CI_DB_query_builder
     */
    private $_db_test_common;

    /**
     * @var CI_DB_query_builder
     */
    private $_db_dev_common;

    /**
     * @var Mysql_sync
     */
    private $model;

    private $devConf;
    private $testConf;

    /**
     * 海外平台需求列表
     * @var string
     */
    private $table_oversea = 'yibai_oversea_platform_list';

    /**
     * FBA需求列表
     * @var string
     */
    private $table_fba = 'yibai_fba_pr_list';

    /**
     * 国内需求列表
     * @var string
     */
    private $table_inland = 'yibai_inland_pr_list';



    /**
     * 海外平台需求列表-表字段(如表字段有更新,请及时更新下面字段)
     * @var string
     */
    private $oversea_platform_attr = '`gid`,`pr_sn`,`created_at`,`sku`,`station_code`,`platform_code`,`sku_name`,`sku_state`,`logistics_id`,`available_qty`,`oversea_up_qty`,`oversea_ship_qty`,`sc_day`,`avg_deliver_day`,
        `z`,`safe_stock_pcs`,`weight_sale_pcs`,`pre_day`,`point_pcs`,`supply_day`,`purchase_qty`,`expect_exhaust_date`,`bd`,`require_qty`,`approve_state`,`approved_uid`,`approved_at`,`remark`,
        `expired`,`updated_at`,`updated_uid`,`check_attr_state`,`check_attr_uid`,`check_attr_time`,`is_addup`,`stocked_qty`,`is_refund_tax`,`purchase_warehouse_id`,`is_boutique`
        ,`fixed_amount`,`product_status`,`can_ship`,`min_order_qty`,`supplier_code`,`moq_qty`';

    /**
     * 国内需求列表-表字段(如表字段有更新,请及时更新下面字段)
     * @var string
     */
    private $inland_pr_attr = '`gid`,`pr_sn`,`sku`,`sku_name`,`is_refund_tax`,`purchase_warehouse_id`,`sku_state`,`product_status`,
        `stock_up_type`,`debt_qty`,`pr_qty`,`ship_qty`,`available_qty`,`purchase_price`,`accumulated_order_days`,`accumulated_sale_qty`,`sale_qty_order`,`weight_sale_pcs`,
        `sale_sd_pcs`,`deliver_sd_day`,`supply_wa_day`,`buffer_pcs`,`purchase_cycle_day`,`ship_timeliness_day`,`pre_day`,`sc_day`,`z`,`safe_stock_pcs`,`point_pcs`,
        `supply_day`,`expect_exhaust_date`,`require_qty`,`stocked_qty`,`is_trigger_pr`,`created_at`,`remark`,`expired`,`updated_at`,`is_addup`,`updated_uid`,`approve_state`,`fixed_amount`,`version`,`approved_at`,`approved_uid`,
        `sales_factor`,`exhaust_factor`,`warehouse_age_factor`,`supply_factor`,`actual_bs`,`actual_safe_stock`,`require_qty_second`,`ext_plan_rebuild_vars`,`bd`,`expand_factor`,`is_stop_clear_warehouse`,`supplier_code`,`moq_qty`,`max_sp`,`designates`';

    /**
     * FBA需求列表-表字段(如表字段有更新,请及时更新下面字段)
     * @var string
     */
    private $fba_pr_attr = '`gid`,`pr_sn`,`created_at`,`sale_group`,`salesman`,`account_id`,`account_name`,`account_num`,`sku`,`seller_sku`,`fnsku`,`asin`,`station_code`,
        `sku_name`,`sku_state`,`product_status`,`logistics_id`,`available_qty`,`exchange_up_qty`,`oversea_ship_qty`,`sc_day`,`deviate_28_pcs`,`avg_weight_sale_pcs`,`avg_deliver_day`,`sale_sd_pcs`,`z`,`safe_stock_pcs`,`weight_sale_pcs`,`pre_day`,`point_pcs`,`supply_day`,`purchase_qty`,`expect_exhaust_date`,
        `bd`,`require_qty`,`total_supply_day`,`stocked_qty`,`is_trigger_pr`,`is_plan_approve`,`approve_state`,`approved_at`,`approved_uid`,`remark`,`expired`,`updated_uid`,`updated_at`,`check_attr_state`,`check_attr_uid`,`check_attr_time`,`is_addup`,`is_refund_tax`,
        `purchase_warehouse_id`,`ext_logistics_info`,`ext_trigger_info`,`trigger_mode`,`is_lost_active_trigger`,`is_boutique`,`country_code`,`listing_state`,`exhausted_days`,`fixed_amount`,`is_accelerate_sale`,`expand_factor`,`is_first_sale`,`deny_approve_reason`,`avg_inventory_age`,`inventory_turns_days`,`inventory_health`,`accelerate_sale_end_time`,`product_category_id`,`category_cn_name`,
        `purchase_price`,`supplier_code`,`version`,`max_sp`,`is_contraband`,`designates`,`moq_qty`,`provider_status`,`sales_factor`,`exhaust_factor`,`warehouse_age_factor`,`supply_factor`,`actual_bs`,`actual_safe_stock`,`require_qty_second`,`ext_plan_rebuild_vars`,`min_order_qty`,`lost_python_cfg`';

    /**
     * 插入条数限制
     * @var int
     */
    private $limitInsert = 500;
    private $limitSelect = 100000;
    public function __construct()
    {
        $this->_db_test_common = $this->load->database('test_common', TRUE);//计划系统测试数据库common
        $this->_db_dev_common = $this->load->database('common', TRUE);//计划系统开发数据库common

        $this->_db_dev_common->db_debug = false;

//测试
//        $this->_db_test_common = $this->load->database('yibai_plan_test', TRUE);//计划系统测试数据库common
//        $this->_db_dev_common = $this->load->database('yibai_plan_dev', TRUE);//计划系统开发数据库common

        $this->load->model('Mysql_sync', 'm_fba', false, 'crontab');
        $this->model = $this->m_fba;
//        $this->init();

        parent::__construct();
    }

    protected function init()
    {
        @list($devHost, $devPort) = explode(':', $this->_db_dev_common->hostname);
        @list($testHost, $testPort) = explode(':', $this->_db_test_common->hostname);
        $devPort == null && $devPort = 3306;
        $testPort == null && $testPort = 3306;

        $this->devConf = [
            'host' => $devHost,
            'port' => $devPort,
            'user' => $this->_db_dev_common->username,
            'psw' => $this->_db_dev_common->password,
            'db' => $this->_db_dev_common->database,
        ];
        $this->testConf = [
            'host' => $testHost,
            'port' => $testPort,
            'user' => $this->_db_test_common->username,
            'psw' => $this->_db_test_common->password,
            'db' => $this->_db_test_common->database,
        ];
    }

//    public function run()
//    {
////        $rse = $this->model->sync($this->testConf, $this->devConf);
////        var_dump($rse);
//    }

    public function run()
    {
        var_dump($_SERVER);
        die;
    }

    /**
     * 更新测试环境 yibai_fba_pr_list， yibai_oversea_platform_list, yibai_inland_pr_list 到开发环境
     * @author W02278
     * CreateTime: 2019/12/24 19:04
     */
    public function pr_or_platform_list()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        ini_set('date.timezone','Asia/Shanghai');

        try {
            $startTime = strtotime(date('Y-m-d'));
            $endTime = strtotime(date('Y-m-d 23:59:59'));
            //刷表 yibai_oversea_platform_list
            $testOverseaDataSql = "select {$this->oversea_platform_attr} from `{$this->table_oversea}` where `created_at` > $startTime and `created_at` < $endTime";
            //刷表 yibai_fba_pr_list
            $testFbaDataSql = "select {$this->fba_pr_attr} from `{$this->table_fba}` where `created_at` > $startTime and `created_at` < $endTime";
            //刷表 yibai_inland_pr_list
            $testInlandDataSql = "select {$this->inland_pr_attr} from `{$this->table_inland}` where `created_at` > $startTime and `created_at` < $endTime";

            $this->createSql($this->table_oversea, $this->oversea_platform_attr, $testOverseaDataSql);
            $this->createSql($this->table_fba, $this->fba_pr_attr, $testFbaDataSql);
            $this->createSql($this->table_inland, $this->inland_pr_attr, $testInlandDataSql);
            $code = 200;
        } catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\mysqli_sql_exception $exception)
        {
            $code = 500;
            $errorMsg = $exception->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
        }
        exit;

    }

    /**
     * 公共组装sql方法
     * @param string $table
     * @param string $attr
     * @param string $testOverseaDataSql
     * @author W02278
     * CreateTime: 2019/12/24 19:05
     */
    private function createSql ($table, $attr, $testDataSql)
    {
        try {
            $page = 0;
            $offset = $page * $this->limitSelect;
            $testData = $this->_db_test_common->query($testDataSql . " limit $offset,{$this->limitSelect}")->result_array();
            while ($testData) {
                $count = count($testData);
                if ($count) {
                    $i = 0;
                    $sqlValues = '';

                    foreach ($testData as &$value) {
                        if ($sqlValues) {
                            $sqlValues .= ',';
                        }
                        $sqlValues .= '(';
                        foreach ($value as $item) {
                            $sqlValues .= "'{$item}',";
                        }
                        $sqlValues = substr($sqlValues, 0, strlen($sqlValues) - 1) . ")";

                        $i ++;
                        if ($i >= $this->limitInsert) {
                            $this->insert($table, $attr, $sqlValues);
                            $sqlValues = '';
                        }

                        unset($value);
                    }
                    unset($testData);

                    if ($sqlValues) {
                        $sqlValues = substr($sqlValues, 0, strlen($sqlValues) - 1) . ")";
                        $this->insert($table, $attr, $sqlValues);
                    }


                    unset($sqlValues);
                    if ($count == $this->limitSelect) {
                        $page++;
                        $offset = $page * $this->limitSelect;
                        $testData = $this->_db_test_common->query($testDataSql . " limit $offset,{$this->limitSelect}")->result_array();
                    } else {
                        $testData = null;
                    }
                }
            }

        } catch (\Exception $exception) {
            echo $exception->getMessage() . PHP_EOL;
        } finally {
            return true;
        }



    }

    /**
     * 插入数据表公共方法
     * @param $table
     * @param $attr
     * @param $sqlValues
     * @author W02278
     * CreateTime: 2019/12/24 19:06
     */
    private function insert($table, $attr, $sqlValues)
    {
        try {
            $insertSql = "insert into `{$table}` ({$attr}) values {$sqlValues}";
            $res = $this->_db_dev_common->query($insertSql);
        } catch (\PDOException $exception) {
            echo $exception->getCode();
            echo $exception->getMessage() . PHP_EOL;
        } finally {
            return true;
        }

    }


    private function yieldArr(&$arr)
    {
        foreach ($arr as $k => $item) {
            yield $k => $item;
        }
    }



}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */