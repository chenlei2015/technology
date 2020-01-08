<?php

/**
 * 备货计划服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class PlanService
{
    public static $s_system_log_name = 'PLAN';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Plan_purchase_list_model', 'purchase_pr_list', false, 'plan');
        $this->_ci->load->helper('plan_helper');
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
        
        $record = ($pk_row = $this->_ci->load->purchase_pr_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
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
        $this->_ci->Record->setModel($this->_ci->purchase_pr_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        
        $db = $this->_ci->purchase_pr_list->getDatabase();
        
        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('备货列表更新备注失败'), 500);
        }
        if (!$this->add_list_remark($params))
        {
            throw new \RuntimeException(sprintf('备货列表插入备注失败'), 500);
        }
        $db->trans_complete();
        
        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('备货添加备注事务提交完成，但检测状态为false'), 500);
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
        $this->_ci->load->model('Plan_purchase_list_remark_model', 'purchase_list_remark', false, 'plan');
        append_login_info($params);
        $insert_params = $this->_ci->purchase_list_remark->fetch_table_cols($params);
        return $this->_ci->purchase_list_remark->add($insert_params);
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->purchase_pr_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }
    
    public function get_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Plan_purchase_list_remark_model', 'purchase_list_remark', false, 'plan');
        return $this->_ci->purchase_list_remark->get($gid, $offset, $limit);
    }

    /**
     * 采购回传计划系统数据:（采购系统）需求单过期=》计划系统备货列表过期
     * @param $data_list
     * @return bool
     */
    public function batch_update_state($data_list){
        if (empty($data_list)){
            throw new \InvalidArgumentException(sprintf('参数为空'), 301);
        }

        $res =  $this->_ci->purchase_pr_list->batch_update_state($data_list);
        if (!$res){
            throw new \RuntimeException(sprintf('更新失败'), 500);
        }else{
            return true;
        }
    }
    
    /**
     * 如果数据量过大可能会引发问题
     *
     * @param unknown $batch_params
     */
    public function batch_update_push_stock_quantity($post)
    {
        if (!isset($post['selected']))
        {
            throw new \InvalidArgumentException('上传的文件没有有效的数据', 412);
        }
        
        $finish_report = function($report) {
            $report['msg'] = sprintf('选择了%d条记录，处理成功%d条，处理失败%d条，无需处理%d条', $report['total'], $report['succ'], $report['fail'], $report['ignore']);
            return $report;
        };
        
        $primary_key_index = array_search($post['primary_key'], $post['map']);
        $manual_update     = key_by($post['selected'], $primary_key_index);
        ksort($manual_update);
        unset($post['selected']);
        
        $report = [
                'total' => count($manual_update),
                'succ' => 0,
                'fail' => 0,
                'ignore' => 0,
        ];
        
        $time              = time();
        $batch_todo_params = [];
        $db                = $this->_ci->purchase_pr_list->getDatabase();
        $todo              = $this->_ci->purchase_pr_list->get_manual_rows(array_keys($manual_update));
        $active_login_info = get_active_user()->get_user_info();
        $report['todo']    = count($todo);
        $map_reverse       = array_flip($post['map']);
        
        $this->_ci->load->model('Plan_purchase_list_log_model', 'm_purchase_log', false, 'plan');
        
        //过滤掉数量不变的
        foreach ($todo as $key => $row)
        {
            $csv_push_stock_quantity = $manual_update[$row['pur_sn']][$map_reverse['push_stock_quantity']];
            if ($row['push_stock_quantity'] != $csv_push_stock_quantity)
            {
                $_todo = array_combine($post['map'], $manual_update[$row['pur_sn']]);
                $_todo['updated_at'] = $time;
                $batch_todo_params[] = $_todo;
                $batch_log_params[] = [
                        'gid' => $row['gid'],
                        'uid' => $active_login_info['oa_info']['userNumber'],
                        'user_name' => $active_login_info['oa_info']['userName'],
                        'context' => sprintf('手工更新推送采购备货数量从%d修改到%d', intval($row['push_stock_quantity']), intval($csv_push_stock_quantity)),
                ];
            }
        }
        $report['ignore'] = $report['total'] - count($batch_todo_params);
        
        if (empty($batch_todo_params))
        {
            return $finish_report($report);
        }
        //更新
        try
        {
            $db->trans_start();
            
            $update_count = $db->update_batch($this->_ci->purchase_pr_list->getTable(), $batch_todo_params, 'pur_sn');
            if (!$update_count)
            {
                throw new \RuntimeException(sprintf('上传文件批量更新推送采购数量失败'), 500);
            }
            
            //记录日志
            $update_count = $this->_ci->m_purchase_log->getDatabase()->insert_batch($this->_ci->m_purchase_log->getTable(), $batch_log_params);
            if (!$update_count)
            {
                throw new \RuntimeException(sprintf('上传文件批量更新推送采购数量失败'), 500);
            }
            
            $db->trans_complete();
            
            if ($db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('上传文件批量更新推送采购数量，事务提交成功，但状态检测为false'), 500);
            }
            
            $report['succ'] = count($batch_todo_params);
            
            //发送系统日志
            $log_context = sprintf('传文件批量更新推送采购数量，总共：%d, 成功：%d, 失败：%d, 无需处理：%d', $report['total'], $report['succ'], $report['fail'], $report['ignore']);
            $this->_ci->load->service('basic/SystemLogService');
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context);
            
            return $finish_report($report);
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('上传文件批量更新推送采购数量，提交事务出现异常: %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('上传文件批量更新推送采购数量，提交事务出现异常'), 500);
        }
    }
    
    /**
     * 手动推送数据, 新增需求：
     * 1、页面如果勾选了按照勾选推送。
     * 2、页面没有勾选，但搜索中勾选了推送需求数量并且为Y，那么则批量推送
     *
     * @param unknown $post
     * @throws \InvalidArgumentException
     * @return unknown
     */
    public function manual_push($post)
    {
        $is_batch_push = isset($post['select_push']) && intval($post['select_push']) == PURCHASE_CAN_PUSH_YES ? true : false;
        if (!$is_batch_push && (!isset($post['pur_sn']) || empty($post['pur_sn'])))
        {
            throw new \InvalidArgumentException('请选择需要推送的数据', 412);
        }
        
        $this->_ci->load->model('Plan_purchase_list_model', 'purchase_pr_list', false, 'plan');
        $this->_ci->load->classes('plan/classes/ManualPurchasePush');
        
        try
        {
            $this->_ci->ManualPurchasePush
            ->set_model($this->_ci->purchase_pr_list)
            ->set_push_type($is_batch_push)
            ->set_selected_pur_sn($post['pur_sn'] ?? [])
            ->push();
            $this->_ci->ManualPurchasePush->send_system_log(self::$s_system_log_name);
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', '推送采购数量出现异常：'.$e->getMessage());
        }
        finally {
            return $this->_ci->ManualPurchasePush->report();
        }
    }

    /**
     * 判断截止时间
     *
     * @return boolean
     */
    public function check_edit_push_close_time()
    {
        list($start, $end) = explode('~', PLAN_EDIT_PUSH_CLOSE_TIME);
        $now = date('H:i');
        return $now >= $start && $now <= $end;
    }

    /**
     * 根据调拨单号查询仓库调拨单
     * 接口文档:http://192.168.71.156/web/#/87?page_id=4108
     */
    public function java_warehouse_transfer($params)
    {
        $this->_ci->load->helper('common');
        if (empty($params)) {
            return [];
        }
//            $data = [
//                'order_id' => $params['order_id'],
//            ];

//echo $params = json_encode($params);exit;
        $result = RPC_CALL('YB_ERP_WAREHOUSE_TRANSFER', ['applicationNum' => $params['transfer_apply_sn']]);
//        pr($result);exit;
        if (empty($result) || !isset($result['code'])) {
            log_message('ERROR', '请求地址：/logistics/logisticsAttr/batchGetIsDrawback,无返回结果');

            return [];
        }

        if ($result['code'] == 500 || $result['code'] == 0) {
            log_message('ERROR', sprintf('请求地址：/logistics/logisticsAttr/batchGetIsDrawback,异常：%s', json_encode($result)));

            return [];
        }

        if (isset($result['data']) && !empty($result['data'])) {
            return $result['data'];
        } else {
            log_message('ERROR', sprintf('请求地址：/logistics/logisticsAttr/batchGetIsDrawback,异常：%s', json_encode($result)));

            return [];
        }
    }

}
