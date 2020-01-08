<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 免登陆服务
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class Common extends MX_Controller {

    private $_server_module = 'crontab/Common/';

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

    public function rebuild_process()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($result);
    }

    public function sync_fba_inventory()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($result);
    }


}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */