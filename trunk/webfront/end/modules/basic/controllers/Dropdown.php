<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Dropdown
 *
 * @author Jason
 * @since 2019-03-09
 */
class Dropdown extends MY_Controller {
    
    private $_server_module = 'basic/Dropdown/';
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function get_dync_fba_accounts()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['group_id']) || !$get['group_id'] || !is_numeric($get['group_id']))
        {
            http_response(['errorMess' => '请选择销售小组']);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    
    public function get_dync_oa_user()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $get['user_name'] = $get['user_name'] ?? '';
        if ($get['user_name'] == '')
        {
            $cache_full_list = 'OA_FULL_LIST';
            $this->load->library('rediss');
            $full_list = $this->rediss->getData($cache_full_list);
            if ($full_list)
            {
                http_response(['status' => 1, 'data_list' => $full_list]);
            }
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    
    public function get_dync_manager_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $get['user_name'] = $get['user_name'] ?? '';
        if ($get['user_name'] == '')
        {
            $cache_full_list = 'MANAGER_FULL_LIST';
            $this->load->library('rediss');
            $full_list = $this->rediss->getData($cache_full_list);
            if ($full_list)
            {
                http_response(['status' => 1, 'data_list' => $full_list]);
            }
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    
    public function get_dync_oversea_manager_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $get['user_name'] = $get['user_name'] ?? '';
        if ($get['user_name'] == '')
        {
            $cache_full_list = 'MANAGER_OVERSEA_FULL_LIST';
            $this->load->library('rediss');
            $full_list = $this->rediss->getData($cache_full_list);
            if ($full_list)
            {
                http_response(['status' => 1, 'data_list' => $full_list]);
            }
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    
    public function get()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['name']) || !$get['name'])
        {
            http_response(['errorMess' => '请选择销售小组']);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    
    public function get_dync_shipment_oversea_manager_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $get['user_name'] = $get['user_name'] ?? '';
        if ($get['user_name'] == '')
        {
            $cache_full_list = 'MANAGER_SHIPMENT_OVERSEA_FULL_LIST';
            $this->load->library('rediss');
            $full_list = $this->rediss->getData($cache_full_list);
            if ($full_list)
            {
                http_response(['status' => 1, 'data_list' => $full_list]);
            }
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    
}
/* End of file Dropdown.php */
/* Location: ./application/modules/basic/controllers/Dropdown.php */