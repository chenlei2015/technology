<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/18
 * Time: 20:19
 */

class Platform_account  Extends MY_Controller
{
    private $_server_module = 'mrp/Platform_account/';
    public function __construct()
    {
        parent::__construct();
    }
    /**
     * 平台账号列表
     */
    public function account_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

}
