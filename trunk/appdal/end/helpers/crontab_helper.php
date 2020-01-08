<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/8/30
 * Time: 9:38
 */

if (!function_exists('send_email')) {
    //发送邮件
    function send_email($subject, $message)
    {
        $ci = CI::$APP;
        $ci->load->library('email');
        $config['protocol']     = 'smtp';
        $config['smtp_host']    = 'smtp.163.com';
        $config['smtp_user']    = 'zmbear888@163.com';
        $config['smtp_pass']    = 'asd123';//去QQ邮箱设置开启smtp
        $config['smtp_port']    = 25;
        $config['smtp_timeout'] = 30;
        $config['mailtype']     = 'text';
        $config['charset']      = 'utf-8';
        $config['wordwrap']     = TRUE;
        $ci->email->initialize($config);
        $ci->email->set_newline("\r\n");
        $config['crlf'] = "\r\n";
        $ci->email->from('zmbear888@163.com', 'Plan');
        $ci->email->to('ofh3990@dingtalk.com');
        $ci->email->subject($subject);
        $ci->email->message($message);
        $ci->email->send();
    }
}

if (!function_exists('record_state')) {
    //记录状态
    function record_script_state($params)
    {
        $ci             = CI::$APP;
        $db_plan_common = $ci->load->database('common', true);
        $business_line  = $params['business_line']??'';
        $database       = $params['database']??'';
        $table          = $params['table']??'';
        $title          = $params['title']??'';
        $step           = $params['step']??'';
        $created_at     = date('Y-m-d H:i:s');
        $message        = $params['message']??'';
        $status         = $params['status']??0;
        $sql            = "INSERT INTO yibai_script_running_condition (`business_line`,`database`,`table`,`title`,`step`,`created_at`,`message`,`status`) VALUES ($business_line,'{$database}','{$table}','{$title}',$step,'{$created_at}','{$message}',$status)";
        $result         = $db_plan_common->query($sql);

        return $result;
    }
}

if (!function_exists('check_state')) {
    //查询状态,如果上一个步骤还未执行完成,需等待
    function check_script_state($step)
    {
        return true;
        //拉取配置表时间段3点到4点之外不进行操作
        if (time() < mktime(03, 00, 00) || time() > mktime(04, 00, 00)) {
            return;
        }
        if (!function_exists('shell_exec')) {
            echo '请在php.ini或者php-fpm中开启appdal的shell_exec的函数';
            return;
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            echo '该操作不能再Window操作系统下执行';

            return;
        }
        //FBA配置表执行时间段
        $start_time = date('Y-m-d') . ' 03:00:00';
        $end_time   = date('Y-m-d') . ' 04:00:00';

        $ci             = CI::$APP;
        $db_plan_common = $ci->load->database('common', true);
        //条件
        $script_info = FBA_PLAN_SCRIPT[$step];
        $condition   = "and step = {$step} ";
        foreach ($script_info as $key => $value) {
            $condition .= "and `{$key}` = '{$value}' ";
        }
        $i = 0;
        while (true) {
            //查询按status升序排序
            $sql    = "SELECT * FROM yibai_script_running_condition WHERE `created_at` BETWEEN '{$start_time}' AND '{$end_time}' " . $condition . ' ORDER BY `status` ASC';
            $result = $db_plan_common->query($sql)->row_array();
            //执行失败,重新执行
            if (!empty($result) && $result['status'] == 2) {
                switch ($step) {
                    case FBA_LOGISTICS_CFG_LIST:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/cron/fba.php';
                        $time = 240;
                        break;
                    case FBA_LOGISTICS_CFG_LIST_UP:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/cron/fba.php sku_map_update';
                        $time = 240;
                        break;
                    case FBA_CHECK_PAN_EU:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab FBA check_is_pan_eu';
                        $time = 60;
                        break;
                    case FBA_STOCK_CFG_LIST:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab FBA erp_sku';
                        $time = 60;
                        break;
                    case FBA_ACCELERATE_SALE_STATE:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab FBA is_accelerate_sale';
                        $time = 60;
                        break;
                    case FBA_PUR_PRODUCT_INFO:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab FBA pur_product_info';
                        $time = 60;
                        break;
                    case FBA_AVG_INVENTORY_AGE:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab FBA avg_inventory_age';
                        $time = 60;
                        break;
                    case FBA_IS_BOUTIQUE:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab FBA update_is_boutique';
                        $time = 60;
                        break;
                    case FBA_DELIVERY_CYCLE:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab Delivery_cycle fba_delivery_cycle';
                        $time = 60;
                        break;
                    case FBA_IS_REFUND_TAX:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab FBA updateDrawback';
                        $time = 60;
                        break;
                    case FBA_SKU_STATE_A:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab Sync_product_status sync_fba_stock';
                        $time = 60;
                        break;
                    case FBA_SKU_STATE_B:
                        $cmd  = '/usr/bin/php /mnt/yibai_cloud/plan/appdal/index.php crontab Sync_product_status sync_fba_logistics';
                        $time = 60;
                }
                shell_exec(sprintf('%s > /dev/null 2>&1 &', $cmd));
                //修改状态为3,重试中
                $sql    = "UPDATE yibai_script_running_condition SET `status` = 3 WHERE id = {$result['id']}";
                $result = $db_plan_common->query($sql);
                if (!isset($time)) {
                    $time = 60;
                }
                sleep($time);
            }
            //执行中,
            if (empty($result) && $i < 20) {
                sleep(30);
                $i++;
            } elseif (!empty($result) && $result['status'] == 1) {//执行成功 退出检查
                break;
            } elseif (!empty($result) && $result['status'] == 3 && $i < 20) {//重试中
                sleep(30);
                $i++;
            } else {
                break;
            }
        };
    }
}