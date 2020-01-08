<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/3
 * Time: 16:28
 */
class Usercfg_list_display extends MY_Controller {

    private $_server_module = 'basic/Usercfg_list_display/';

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 编辑显示数据列表页
     * http://192.168.71.170:83/basic/Usercfg_list_display/getList
     */
    public function getList(){
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if(!isset($get['collection']) || empty($get['collection'])){
            $this->response_error('3001');
        }

        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 编辑显示数据列表页
     * http://192.168.71.170:83/basic/Usercfg_list_display/cfg
     */
    public function cfg(){
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(!isset($post['collection']) || empty($post['collection'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 返回错误响应信息
     * @param $code
     * @param $status
     */
    private function response_error($code = 0, $status = 0)
    {
        $this->data['status'] = $status;
        if ($status == 0) {
            $this->data['errorCode'] = $code;
        }
        $this->data['errorMess'] = $this->error_info[$code];
        http_response($this->data);
    }

}