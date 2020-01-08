<?php

/**
 *  FBA发货周期从erp获取
 */
class Delivery_cycle extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->stock   = $this->load->database('stock', TRUE);//计划系统数据库
        $this->system  = $this->load->database('yibai_system', TRUE);//ERP系统数据库
        $this->_debug = $this->input->post_get('debug') ?? '';
    }

    private function show_log($str)
    {
        if (!empty($this->_debug)) {
            echo $str;
        }
    }

    /**
     * 发货周期从erp拉取yibai_system.yibai_amazon_fba_replenishment_control，group_id为61的销售组对应weeks为2,4,0则发货周期 = 7 / 3，向上取整,
     * 没匹配到的默认为3
     */
    public function fba_delivery_cycle()
    {
        try {
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $starttime = explode(' ', microtime());
            //查询上个步骤是否执行完成
            $this->load->helper('crontab');
            check_script_state(FBA_CHECK_PAN_EU);
            //优先使用备份表,不存在则使用实时表
            $yibai_amazon_fba_replenishment_control = 'yibai_amazon_fba_replenishment_control_' . date('d');
            if (!$this->system->table_exists($yibai_amazon_fba_replenishment_control)) {
                $yibai_amazon_fba_replenishment_control = 'yibai_amazon_fba_replenishment_control';
            }

            //erp 获取所有的组id对应weeks
            $erp_result = $this->system->select('group_id,weeks')->order_by('group_id asc')->get($yibai_amazon_fba_replenishment_control)->result_array();
            $erp_result = array_column($erp_result, NULL, 'group_id');
            //二维数组下标:group_id

            //计划系统(查询销售小组id和记录id 分批查询)
            $total = $this->stock->select('*')->from('yibai_fba_logistics_list')->count_all_results();
            $start = 0;
            $once  = 10000;
            $count = 0;
            $batch = ceil($total / $once);//向上取整
            //分批处理
            for ($i = 0; $i < $batch; $i++) {
                $sql        = "SELECT id,sale_group_id,delivery_cycle FROM yibai_fba_logistics_list LIMIT $start,$once";
                $group_info = $this->stock->query_unbuffer($sql)->result_array();

                foreach ($group_info as $key => &$item) {
                    if (isset($erp_result[$item['sale_group_id']])) {
                        $weeks_string       = $erp_result[$item['sale_group_id']]['weeks'];//0,1,2,3
                        $s                  = substr_count($weeks_string, ',');//逗号出现次数
                        $s                  += 1;
                        $new_delivery_cycle = ceil(7 / $s);
                    } else {
                        //没匹配到erp的默认为3
                        $new_delivery_cycle = 3;
                    }
                    if ($new_delivery_cycle != $item['delivery_cycle']) {
                        $item['delivery_cycle'] = $new_delivery_cycle;
                        unset($item['sale_group_id']);
                    } else {
                        unset($group_info[$key]);
                    }
                }
                if (!empty($group_info)) {
                    $this->stock->trans_start();
                    $result = $this->stock->update_batch('yibai_fba_logistics_list', $group_info, 'id');
                    $this->stock->trans_complete();
                    $count  += $result;
                }
                $start += 10000;
            }
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            //执行结束,记录状态
            $message = sprintf('执行成功,耗时:%s,更新记录:%s', $thistime, $count);
            $code    = 200;
        }catch (\Throwable $e)
        {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[FBA_DELIVERY_CYCLE];

            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_DELIVERY_CYCLE;
            record_script_state($params);
            exit;
        }
        exit;
    }

}