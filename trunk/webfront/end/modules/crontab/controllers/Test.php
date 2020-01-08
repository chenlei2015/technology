<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class Test extends MX_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->_curl_request = CurlRequest::getInstance();
        $this->_curl_request->setServer(PLAN_HOST,PLAN_SECRET);
    }

    public function inland_test()
    {
        $url = $this->_curl_request->api_server.'/crontab/Inland/test';
        $result = $this->_curl_request->http_request($url, $this->input->get());
        var_dump($result);exit;
    }

    public function inland_update_sku_cfg_warehouse_id()
    {
        $url = $this->_curl_request->api_server.'/crontab/Inland/update_sku_cfg_warehouse_id';
        $result = $this->_curl_request->http_request($url, $this->input->get());
        var_dump($result);exit;
    }

    public function oversea_test()
    {
        $url = $this->_curl_request->api_server.'/crontab/Oversea/test';
        $result = $this->_curl_request->http_request($url, $this->input->get());
        var_dump($result);exit;
    }

    public function oversea_update_sku_cfg_warehouse_id()
    {
        $url = $this->_curl_request->api_server.'/crontab/Oversea/update_sku_cfg_warehouse_id';
        $result = $this->_curl_request->http_request($url, $this->input->get());
        var_dump($result);exit;
    }
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */