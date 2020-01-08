<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 海外仓全局规则配置表
 *
 * @author Manson
 * @since 2019-03-05
 */
class Global_rule_cfg extends MY_Controller {

    private $_server_module = 'oversea/Global_rule_cfg/';

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 列表页
     * http://192.168.71.170:1083/oversea/Global_rule_cfg/globalRuleList
     */
    public function globalRuleList()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name,$get);

        http_response($this->rsp_package($result));
    }

    /**
     * 预览详情页
     * http://192.168.71.170:1083/oversea/Global_rule_cfg/getRuleDetails
     */
    public function getRuleDetails()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['station_code']) || empty($get['station_code'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($api_name,$get);

        http_response($this->rsp_package($result));
    }
    /**
     * 全局规则配置修改
     * http://192.168.71.170:1083/oversea/Global_rule_cfg/modifyRule
     */
    public function modifyRule(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(empty($post['user_id'])||empty($post['user_name'])|| empty($post['station_code'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 添加备注
     * http://192.168.71.170:1083/oversea/Global_rule_cfg/addRemark
     */
    public function addRemark()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(empty($post['station_code']) || empty($post['remark']) || empty($post['user_id']) || empty($post['user_name'])){
            $this->response_error('3001');
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 日志列表
     * http://192.168.71.170:1083/oversea/Global_rule_cfg/getGlobalLogList
     */
    public function getGlobalLogList(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if(empty($get['station_code'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $get);
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
/* End of file Global_rule_cfg.php */
/* Location: ./application/modules/oversea/controllers/Global_rule_cfg.php */