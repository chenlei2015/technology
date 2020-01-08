<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 汇总服务
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class Inland extends MX_Controller {
    
    private $data = ['status' => 0];
    
    private $_server_module = 'inland/Approve/';
    
    private $_curl_request;
    
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
     * 免登陆执行
     */
    public function auto()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['key']) || $get['key'] != md5(CRON_SECRET_KEY))
        {
            $this->data['errorMess'] = '没有权限';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($result);
    }
    
    /**
     * 免登陆检测
     */
    public function status()
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
    
    /**
     * 国内需求过期审核, 计划任务放置在0点之后，没有传日期，所以更新时间为当前时间的前一天
     */
    public function expired()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['key']) || $get['key'] != md5(CRON_SECRET_KEY))
        {
            $this->data['errorMess'] = '没有权限';
            http_response($this->data);
        }
        if (isset($get['date']) && strtotime($get['date']) === false)
        {
            $this->data['errorMess'] = '日期填写错误';
            http_response($this->data);
        }
        if (isset($get['date']))
        {
            $date = $get['date'];
            $today = date('Y-m-d');
            if ($date > $today)
            {
                $this->data['errorMess'] = '日期不能大于今天';
                http_response($this->data);
            }
        }
        else
        {
            //历史
            $get['date'] = '*';
        }
        
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($result);
    }
    
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */