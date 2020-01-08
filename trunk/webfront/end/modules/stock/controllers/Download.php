<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FBA需求列表
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class Download extends MY_Controller {
    
    private $_server_module = 'stock/Download/';
    
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['stock_lang']);
        $this->load->helper('stock_helper');
    }
   
    public function fba()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_download_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }
    
    public function oversea()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_download_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }
    
    public function inland()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_download_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }
    
    /**
     * 免登陆添加
     */
    public function add()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['url']))
        {
            $this->data['errorMess'] = 'url必须填写';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        
        http_response($this->rsp_package($result));
    }
    
    /**
     * 免登陆验证
     */
    public function exists()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['date']))
        {
            $this->data['errorMess'] = '查询日期必须填写';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    
    
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */