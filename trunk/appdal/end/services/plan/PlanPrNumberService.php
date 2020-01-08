<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/5/6
 * Time: 16:20
 */
class PlanPrNumberService
{
    public static $s_system_log_name = 'PLAN';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Plan_pr_number_model', 'm_pr_number', false, 'plan');
        $this->_ci->load->helper('plan_helper');
        return $this;
    }

    /**
     * 采购回传计划系统数据:（采购系统）需求单过期=》计划系统备货列表过期
     * @param $data_list
     * @return bool
     */
    public function update_state($data_list){
        if (empty($data_list)){
            throw new \InvalidArgumentException(sprintf('参数为空'), 301);
        }

        $res =  $this->_ci->m_pr_number->update_state($data_list);
        if (!$res){
            throw new \RuntimeException(sprintf('更新失败'), 500);
        }else{
            return true;
        }
    }
}