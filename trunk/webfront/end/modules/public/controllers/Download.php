<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 免登陆服务
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class Download extends MX_Controller {
    
    private $_server_module = 'stock/Download_query/';
    
    private $_curl_request;
    
    private $data = ['status' => 0];
    
    /**
     * 
     */
    public function __construct()
    {
        parent::__construct();
        $this->_curl_request = CurlRequest::getInstance();
        $this->_curl_request->setServer(PLAN_HOST,PLAN_SECRET);
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
        if (!isset($get['url']))
        {
            $this->data['errorMess'] = 'url必须填写';
            http_response($this->data);
        }
        //http_request($url, $data=null, $headers = array(),$method = 'POST'){
        $result = $this->_curl_request->cloud_get($api_name, $get);
        
        http_response($result);
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
        http_response($result);
    }
    
    
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */