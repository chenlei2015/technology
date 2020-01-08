<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/8/9
 * Time: 13:41
 */
class Sync_product_status extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
        $this->stock   = $this->load->database('stock', TRUE);//计划系统数据库
        $this->tag1    = 0;
    }

    /**
     *
     */
    public function sync_fba_logistics()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $this->load->helper('crontab');
            $last_step = FBA_CHECK_PAN_EU;
            $step      = FBA_SKU_STATE_B;
            //查询上个步骤是否执行完成
            check_script_state($last_step);

            $number = 0;
            $total  = $this->stock->select('*')->from('yibai_fba_logistics_list')->count_all_results();
            if (empty($total)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }
            //优先使用备份表,不存在则使用实时表
            $yibai_product = 'yibai_product_' . date('d');
            if (!$this->product->table_exists($yibai_product)) {
                $yibai_product = 'yibai_product';
            }
            $sql         = "SELECT sku,product_status FROM $yibai_product";
            $product_map = $this->product->query($sql)->result_array();
            $product_map = array_column($product_map, 'product_status', 'sku');

            if (empty($product_map)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                $params            = FBA_PLAN_SCRIPT[$step];
                $params['message'] = $message;
                $params['step']    = $step;
                record_script_state($params);
                echo '查询sku为空';
                exit;
            }

            $sql = "SELECT sku,sku_state,product_status,id FROM yibai_fba_logistics_list LIMIT ";

            $start    = 1;//第N页减一
            $pageSize = 5000;
            $page     = ceil($total / $pageSize);
            for ($i = $start; $i <= $page; $i++) {
                $_sql     = $sql . (($i - 1) * $pageSize) . ',' . $pageSize;
                $sku_info = $this->stock->query($_sql)->result_array();
                if (empty($sku_info)) {
                    continue;
                }
                $created_at = date('Y-m-d H:i:s');
                foreach ($sku_info as $key => $item) {
                    if (isset($product_map[$item['sku']])) {//不同的才进行update
                        $product_status         = $product_map[$item['sku']]??'';
                        $product_status_name    = INLAND_SKU_ALL_STATE[$product_status]['name']??'';
                        $erp_product_state      = INLAND_SKU_ALL_STATE[$product_status]['listing_state']??5;
                        $erp_product_state_name = SKU_STATE[$erp_product_state]['name']??'未知';
                        if ($item['sku_state'] != $erp_product_state || $item['product_status'] != $product_status) {
                            $update_info[] = [
                                'sku'            => $item['sku'],
                                'sku_state'      => $erp_product_state,
                                'product_status' => $product_status,
                            ];
                            $log_info[]    = [
                                'log_id'     => $item['id'],
                                'op_zh_name' => 'system',
                                'context'    => sprintf('修改计划系统SKU状态:%s,ERP系统SKU状态:%s', $erp_product_state_name, $product_status_name),
                                'created_at' => $created_at,
                            ];
                            $number++;
                        }
                    }
                }
            }
            unset($product_map);
            if (empty($update_info)) {
                $message = sprintf('文件: %s 方法: %s 执行成功,无需更新', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 200);
            }
            if ($this->update_fba_logistics($update_info, $log_info)) {
                $this->load->model('Common_backup_log', 'm_log', false, 'log');
                $p = [
                    'database' => 'yibai_plan_stock',
                    'table'    => 'yibai_fba_logistics_list',
                    'date'     => date('Y-m-d'),
                ];
                if (empty($this->m_log->check_is_exist($p))) {
                    $this->m_log->add($p);
                }
            }

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s,需更新的记录:%s', $thistime, $number);
            log_message('error', $message);
            $code = 200;

        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[$step];

            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = $step;
            record_script_state($params);
            $this->load->model('Common_backup_log', 'm_log', false, 'log');
            $p = [
                'database' => 'yibai_plan_stock',
                'table'    => 'yibai_fba_logistics_list',
                'date'     => date('Y-m-d'),
            ];
            if (empty($this->m_log->check_is_exist($p))) {
                $this->m_log->add($p);
            }
            exit;
        }
        exit;

    }

    private function update_fba_logistics($batch_params, $log_info)
    {

        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {

            $this->stock->trans_start();
            $this->stock->update_batch('yibai_fba_logistics_list', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_fba_logistics_list_log', $log_info[$key]);            //记录日志
            $this->stock->trans_complete();
            if ($this->stock->trans_status() === false) {
                $error_info['update'][] = $item;
                $error_info['insert'][] = $log_info[$key];
            } else {
                unset($batch_params[$key]);
                unset($log_info[$key]);
            }
        }
        if (!empty($error_info)) {
            $this->update_fba_logistics($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('同步FBA物流属性配置表产品状态,已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }

    /**
     * 同步FBA备货关系配置表
     * 字段:计划系统的SKU状态,erp系统的sku状态,品类
     * 增量拉取配置表后,执行该任务
     */
    public function sync_fba_stock()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $this->load->helper('crontab');
            $last_step = FBA_STOCK_CFG_LIST;
            $step      = FBA_SKU_STATE_A;
            //查询上个步骤是否执行完成
            check_script_state($last_step);
            //优先使用备份表,不存在则使用实时表
            $yibai_product = 'yibai_product_' . date('d');
            if (!$this->product->table_exists($yibai_product)) {
                $yibai_product = 'yibai_product';
            }
            $number = 0;
            $total  = $this->stock->select('*')->from('yibai_fba_sku_cfg')->count_all_results();
            if (empty($total)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }
            //品类的名称
            $sql      = "SELECT id,category_cn_name FROM yibai_product_category";
            $cate_map = $this->product->query($sql)->result_array();
            $cate_map = array_column($cate_map, 'category_cn_name', 'id');

            $sql         = "SELECT sku,product_status,product_category_id FROM $yibai_product";
            $product_map = $this->product->query($sql)->result_array();
            $product_map = array_column($product_map, NULL, 'sku');

            if (empty($product_map)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,查询结果为空,%s', __FILE__, __METHOD__, $sql);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }

            $sql = "SELECT sku,sku_state,product_status,product_category_id,category_cn_name,id FROM yibai_fba_sku_cfg LIMIT ";

            $start    = 1;//第N页减一
            $pageSize = 5000;
            $page     = ceil($total / $pageSize);
            for ($i = $start; $i <= $page; $i++) {
                $_sql     = $sql . (($i - 1) * $pageSize) . ',' . $pageSize;
                $sku_info = $this->stock->query($_sql)->result_array();
                if (empty($sku_info)) {
                    continue;
                }
                $created_at = date('Y-m-d H:i:s');
                foreach ($sku_info as $key => $item) {
                    if (isset($product_map[$item['sku']])) {//不同的才进行update
                        $product_status      = $product_map[$item['sku']]['product_status'];//erp系统的sku状态
                        $product_status_name = INLAND_SKU_ALL_STATE[$product_status]['name']??'';
                        $erp_sku_state       = INLAND_SKU_ALL_STATE[$product_status]['listing_state']??5;//erp系统映射成计划系统的sku状态
                        $erp_sku_state_name  = SKU_STATE[$erp_sku_state]['name']??'未知';
                        $product_category_id = $product_map[$item['sku']]['product_category_id'];
                        $category_cn_name    = $cate_map[$product_category_id]??'';
                        if ($item['product_status'] != $product_status || $item['sku_state'] != $erp_sku_state || $item['product_category_id'] != $product_category_id || $item['category_cn_name'] != $category_cn_name) {
                            $update_info[] = [
                                'sku'                 => $item['sku'],
                                'sku_state'           => $erp_sku_state,
                                'product_status'      => $product_status,
                                'product_category_id' => $product_category_id,
                                'category_cn_name'    => $category_cn_name,
                            ];
                            $log_info[]    = [
                                'sku_id'     => $item['id'],
                                'op_zh_name' => 'system',
                                'context'    => sprintf('修改计划系统SKU状态:%s,ERP系统SKU状态:%s,品类:%s', $erp_sku_state_name, $product_status_name, $category_cn_name),
                                'created_at' => $created_at,
                            ];
                            $number++;
                        }
                    }
                }
            }
            unset($product_map);
            if (empty($update_info)) {
                $message = sprintf('文件: %s 方法: %s 执行成功,无需更新', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 200);
            }
            if ($this->update_fba_stock($update_info, $log_info)) {
                $this->load->model('Common_backup_log', 'm_log', false, 'log');
                $p = [
                    'database' => 'yibai_plan_stock',
                    'table'    => 'yibai_fba_sku_cfg',
                    'date'     => date('Y-m-d'),
                ];
                if (empty($this->m_log->check_is_exist($p))) {
                    $this->m_log->add($p);
                }
            };

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s,需更新的记录:%s', $thistime, $number);
            log_message('error', $message);
            $code = 200;
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[$step];

            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = $step;
            record_script_state($params);
            $this->load->model('Common_backup_log', 'm_log', false, 'log');
            $p = [
                'database' => 'yibai_plan_stock',
                'table'    => 'yibai_fba_sku_cfg',
                'date'     => date('Y-m-d'),
            ];
            if (empty($this->m_log->check_is_exist($p))) {
                $this->m_log->add($p);
            }
            exit;
        }
        exit;

    }

    public function update_fba_stock($batch_params, $log_info)
    {

        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {

            $this->stock->trans_start();
            $this->stock->update_batch('yibai_fba_sku_cfg', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_fba_stock_log', $log_info[$key]);            //记录日志
            $this->stock->trans_complete();
            if ($this->stock->trans_status() === false) {
                $error_info['update'][] = $item;
                $error_info['insert'][] = $log_info[$key];
            } else {
                unset($batch_params[$key]);
                unset($log_info[$key]);
            }
        }
        if (!empty($error_info)) {
            $this->update_fba_stock($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }


    /**
     *
     */
    public function sync_oversea_logistics()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $this->load->helper('crontab');
            $last_step = OVERSEA_SKU_STATE_A;
            $step      = OVERSEA_SKU_STATE_B;
//            check_script_state(OVERSEA_SKU_STATE_A);
            $number = 0;
            $total  = $this->stock->select('*')->from('yibai_oversea_logistics_list')->count_all_results();
            if (empty($total)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }
            //优先使用备份表,不存在则使用实时表
            $yibai_product = 'yibai_product_' . date('d');
            if (!$this->product->table_exists($yibai_product)) {
                $yibai_product = 'yibai_product';
            }
            $sql         = "SELECT sku,product_status FROM $yibai_product";
            $product_map = $this->product->query($sql)->result_array();
            $product_map = array_column($product_map, 'product_status', 'sku');

            if (empty($product_map)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }

            $sql = "SELECT sku,sku_state,id FROM yibai_oversea_logistics_list LIMIT ";

            $start    = 1;//第N页减一
            $pageSize = 5000;
            $page     = ceil($total / $pageSize);
            for ($i = $start; $i <= $page; $i++) {
                $_sql     = $sql . (($i - 1) * $pageSize) . ',' . $pageSize;
                $sku_info = $this->stock->query($_sql)->result_array();
                if (empty($sku_info)) {
                    continue;
                }
                $created_at = date('Y-m-d H:i:s');
                foreach ($sku_info as $key => $item) {
                    if (isset($product_map[$item['sku']])) {//不同的才进行update
                        $erp_product_state      = INLAND_SKU_ALL_STATE[$product_map[$item['sku']]]['listing_state']??5;
                        $erp_product_state_name = SKU_STATE[$erp_product_state]['name']??'未知';
                        if ($item['sku_state'] != $erp_product_state) {
                            $update_info[] = [
                                'sku'       => $item['sku'],
                                'sku_state' => $erp_product_state,
                            ];
                            $log_info[]    = [
                                'log_id'     => $item['id'],
                                'op_zh_name' => 'system',
                                'context'    => '修改产品状态为' . $erp_product_state_name,
                                'created_at' => $created_at,
                            ];
                            $number++;
                        }
                    }
                }
            }
            unset($product_map);
            if (empty($update_info)) {
                $message = sprintf('文件: %s 方法: %s 执行成功,无需更新', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 200);
            }
            $this->update_logistics_sku_state($update_info, $log_info);

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s,需更新的记录:%s', $thistime, $number);
            echo $message;

            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = OVERSEA_PLAN_SCRIPT[$step];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = $step;
            record_script_state($params);
            exit;
        }
    }

    public function update_logistics_sku_state($batch_params, $log_info)
    {

        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {

            $this->stock->trans_start();
            $this->stock->update_batch('yibai_oversea_logistics_list', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_oversea_logistics_list_log', $log_info[$key]);            //记录日志
            $this->stock->trans_complete();
            if ($this->stock->trans_status() === false) {
                $error_info['update'][] = $item;
                $error_info['insert'][] = $log_info[$key];
            } else {
                unset($batch_params[$key]);
                unset($log_info[$key]);
            }
        }
        if (!empty($error_info)) {
            $this->update_logistics_sku_state($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('同步物流属性配置表产品状态,已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }

    /**
     *
     */
    public function sync_oversea_stock()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $this->load->helper('crontab');
//            check_script_state(OVERSEA_UPDATE_WAREHOUSE);
            $last_step = OVERSEA_UPDATE_WAREHOUSE;
            $step      = OVERSEA_SKU_STATE_A;
            //优先使用备份表,不存在则使用实时表
            $yibai_product = 'yibai_product_' . date('d');
            if (!$this->product->table_exists($yibai_product)) {
                $yibai_product = 'yibai_product';
            }
            $number = 0;
            $total  = $this->stock->select('*')->from('yibai_oversea_sku_cfg_main')->count_all_results();
            if (empty($total)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }
            $sql         = "SELECT sku,product_status FROM $yibai_product";
            $product_map = $this->product->query($sql)->result_array();
            $product_map = array_column($product_map, 'product_status', 'sku');

            if (empty($product_map)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }

            $sql = "SELECT sku,sku_state,gid FROM yibai_oversea_sku_cfg_main LIMIT ";

            $start    = 1;//第N页减一
            $pageSize = 5000;
            $page     = ceil($total / $pageSize);
            for ($i = $start; $i <= $page; $i++) {
                $_sql     = $sql . (($i - 1) * $pageSize) . ',' . $pageSize;
                $sku_info = $this->stock->query($_sql)->result_array();
                if (empty($sku_info)) {
                    continue;
                }
                $created_at = date('Y-m-d H:i:s');
                foreach ($sku_info as $key => $item) {
                    if (isset($product_map[$item['sku']])) {//不同的才进行update
                        $erp_product_state      = INLAND_SKU_ALL_STATE[$product_map[$item['sku']]]['listing_state']??5;
                        $erp_product_state_name = SKU_STATE[$erp_product_state]['name']??'未知';
                        if ($item['sku_state'] != $erp_product_state) {
                            $update_info[] = [
                                'sku'       => $item['sku'],
                                'sku_state' => $erp_product_state,
                            ];
                            $log_info[]    = [
                                'gid'        => $item['gid'],
                                'op_zh_name' => 'system',
                                'context'    => '修改产品状态为' . $erp_product_state_name,
                                'created_at' => $created_at,
                            ];
                            $number++;
                        }
                    }
                }
            }
            unset($product_map);
            if (empty($update_info)) {
                $message = sprintf('文件: %s 方法: %s 执行成功,无需更新', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 200);
            }
            $this->update_sku_state($update_info, $log_info);

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s,需更新的记录:%s', $thistime, $number);
            echo $message;
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = OVERSEA_PLAN_SCRIPT[$step];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = $step;
            record_script_state($params);
            exit;
        }
    }

    public function update_sku_state($batch_params, $log_info)
    {

        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {

            $this->stock->trans_start();
            $this->stock->update_batch('yibai_oversea_sku_cfg_main', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_oversea_stock_log', $log_info[$key]);            //记录日志
            $this->stock->trans_complete();
            if ($this->stock->trans_status() === false) {
                $error_info['update'][] = $item;
                $error_info['insert'][] = $log_info[$key];
            } else {
                unset($batch_params[$key]);
                unset($log_info[$key]);
            }
        }
        if (!empty($error_info)) {
            $this->update_sku_state($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }

    /**
     * 同步国内备货关系配置表sku状态,产品状态,货源状态
     * 增量拉取配置表后,执行该任务
     */
    public function sync_inland_stock()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
//            $this->load->helper('crontab');
//            check_script_state(OVERSEA_UPDATE_WAREHOUSE);
            //优先使用备份表,不存在则使用实时表
            $yibai_product = 'yibai_product_' . date('d');
            if (!$this->product->table_exists($yibai_product)) {
                $yibai_product = 'yibai_product';
            }
            $number = 0;
            $total  = $this->stock->select('*')->from('yibai_inland_sku_cfg')->count_all_results();
            if (empty($total)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                echo '查询sku为空';
                exit;
            }
            $sql         = "SELECT sku,product_status,provider_status FROM $yibai_product";
            $product_map = $this->product->query($sql)->result_array();
            $product_map = array_column($product_map, NULL, 'sku');

            if (empty($product_map)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                echo '查询sku为空';
                exit;
            }

            $sql = "SELECT sku,sku_state,product_status,provider_status,gid FROM yibai_inland_sku_cfg LIMIT ";

            $start    = 1;//第N页减一
            $pageSize = 3000;
            $page     = ceil($total / $pageSize);
            for ($i = $start; $i <= $page; $i++) {
                $_sql     = $sql . (($i - 1) * $pageSize) . ',' . $pageSize;
                $sku_info = $this->stock->query($_sql)->result_array();
                if (empty($sku_info)) {
                    continue;
                }
                $created_at = date('Y-m-d H:i:s');
                foreach ($sku_info as $key => $item) {
                    if (isset($product_map[$item['sku']])) {//不同的才进行update
                        $erp_product_status      = $product_map[$item['sku']]['product_status'];
                        $erp_product_status_name = INLAND_SKU_ALL_STATE[$erp_product_status]['name']??'-';
                        $erp_sku_state           = INLAND_SKU_ALL_STATE[$erp_product_status]['listing_state']??5;
                        $erp_sku_state_name      = SKU_STATE[$erp_sku_state]['name']??'未知';
                        $provider_status         = $product_map[$item['sku']]['provider_status']??'';
                        $provider_status_name    = PROVIDER_STATUS[$provider_status]['name'];
                        if ($item['product_status'] != $erp_product_status || $item['provider_status'] != $provider_status) {
                            $update_info[] = [
                                'sku'             => $item['sku'],
                                'sku_state'       => $erp_sku_state,
                                'product_status'  => $erp_product_status,
                                'provider_status' => $provider_status,
                            ];
                            $log_info[]    = [
                                'gid'        => $item['gid'],
                                'user_name'  => 'system',
                                'context'    => sprintf('修改产品状态:%s,SKU状态:%s,货源状态:%s', $erp_product_status_name, $erp_sku_state_name, $provider_status_name),
                                'created_at' => $created_at,
                            ];
                            $number++;
                        }
                    }
                }
            }
            unset($product_map);
            if (empty($update_info)) {
                $message = sprintf('文件: %s 方法: %s 执行成功,无需更新', __FILE__, __METHOD__);
                log_message('error', $message);
//                $params['message'] = $message;
//                record_script_state($params);
                exit;
            }
            $this->update_inland_state($update_info, $log_info);

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s,需更新的记录:%s', $thistime, $number);
            log_message('error', $message);
            $code = 200;
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
        }
        exit;

    }

    public function update_inland_state($batch_params, $log_info)
    {

        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {

            $this->stock->trans_start();
            $this->stock->update_batch('yibai_inland_sku_cfg', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_inland_sku_cfg_log', $log_info[$key]);            //记录日志
            $this->stock->trans_complete();
            if ($this->stock->trans_status() === false) {
                $error_info['update'][] = $item;
                $error_info['insert'][] = $log_info[$key];
            } else {
                unset($batch_params[$key]);
                unset($log_info[$key]);
            }
        }
        if (!empty($error_info)) {
            $this->update_inland_state($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }
}