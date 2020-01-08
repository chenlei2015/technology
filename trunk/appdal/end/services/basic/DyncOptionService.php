<?php

/**
 * 该服务处理级联更新数据，场景如下拉。
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2018-03-09
 * @link
 */
class DyncOptionService
{
    private $_ci;
    
    public function __construct()
    {
        $this->_ci =& get_instance();
    }

    /**
     * 根据分组获取账号列表
     *
     * @param unknown $gid
     * @return array
     */
    public function get_dync_fba_accounts($gid)
    {
        $this->_ci->load->model('Fba_amazon_account_model', 'amazon_account', false, 'fba');
        return $this->_ci->amazon_account->get_accounts_by_group($gid);
    }
    
    /**
     * 根据搜索姓名获取用户列表
     *
     * @param unknown $user_name
     * @return array|array
     */
    public function get_dync_oa_user($user_name)
    {
        $cache_full_list = 'OA_FULL_LIST';
        $seach_params = [
                'pageSize' => 5000,
                'isDel' => 0,
                'userName' => $user_name
        ];
        $list = RPC_CALL('YB_J1_004', $seach_params);
        if (!$list)
        {
            return [];
        }
        $options = [];
        foreach ($list['data']['records'] as $row)
        {
            $options[$row['userNumber']] = $row['userNumber'] . ' '.$row['userName'];
        }
        if ($user_name == '')
        {
            $this->_ci->load->library('rediss');
            $this->_ci->rediss->setData($cache_full_list, $options, 60 * 30);
        }
        return $options;
    }
    
    /**
     * 根据搜索姓名获取管理员列表
     *
     * @param unknown $user_name
     * @return array|array
     */
    public function get_dync_manager_list($user_name)
    {
        $cache_full_list = 'MANAGER_FULL_LIST';
        
        $this->_ci->load->model('Fba_manager_account_model', 'manager_account', false, 'fba');
        $options = $this->_ci->manager_account->get_dync_manager_by_name($user_name);
        
        if ($user_name == '')
        {
            $this->_ci->load->library('rediss');
            $this->_ci->rediss->setData($cache_full_list, $options, 60 * 30);
        }
        return $options;
    }
    
    /**
     * 根据搜索姓名获取海外仓管理员列表
     *
     * @param unknown $user_name
     * @return array|array
     */
    public function get_dync_oversea_manager_list($user_name)
    {
        $cache_full_list = 'MANAGER_OVERSEA_FULL_LIST';
        
        $this->_ci->load->model('Oversea_manager_staff_model', 'm_oversea_staff', false, 'oversea');
        //$this->_ci->load->model('Oversea_manager_account_model', 'm_oversea_manager', false, 'oversea');
        $options = $this->_ci->m_oversea_staff->get_dync_manager_by_name($user_name);
        
        if ($user_name == '')
        {
            $this->_ci->load->library('rediss');
            $this->_ci->rediss->setData($cache_full_list, $options, 60 * 30);
        }
        return $options;
    }
    
    public function get_dync_shipment_oversea_manager_list($user_name)
    {
        $cache_full_list = 'MANAGER_SHIPMENT_OVERSEA_FULL_LIST';
        
        $this->_ci->load->model('Shipment_oversea_manager_account_model', 'm_shipment_oversea_manager', false, 'shipment');
        $options = $this->_ci->m_shipment_oversea_manager->get_dync_manager_by_name($user_name);
        
        if ($user_name == '')
        {
            $this->_ci->load->library('rediss');
            $this->_ci->rediss->setData($cache_full_list, $options, 60 * 30);
        }
        return $options;
    }
    

}