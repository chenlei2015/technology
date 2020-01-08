<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内全局规则配置
 * @author W02278
 * @name  Domestic Class
 */
class Domestic extends MY_Controller
{

    private $_server_module = 'inland/Domestic/';

    public $validataRes = [
        'status' => 0,
        'message' => '',
    ];

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 全局规则配置列表
     * @author W02278
     * @link http://192.168.71.170:83/inland/Domestic/getInlandCfgList
     * CreateTime: 2019/4/24 17:15
     */
    public function getInlandCfgList()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);

        http_response($this->rsp_package($result));
    }
    
    /**
     * 预览详情页
     * http://192.168.71.170:83/inland/Domestic/getInlandCfgDetail?id=xxx
     */
    public function getInlandCfgDetail()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['id']) || empty($get['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);

        http_response($this->rsp_package($result));
    }

    /**
     * 根据配置id获取日志
     * @throws Exception
     * @author W02278
     * @link http://192.168.71.170:83/inland/Domestic/getLogs?id=xxx
     * CreateTime: 2019/4/24 10:55
     */
    public function getLogs()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['id']) || empty($get['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);

        http_response($this->rsp_package($result));
    }

    /**
     * 根据配置id获取备注
     * @throws Exception
     * @author W02278
     * @link http://192.168.71.170:83/inland/Domestic/getRemarks?id=xxx
     * CreateTime: 2019/4/24 10:55
     */
    public function getRemarks()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['id']) || empty($get['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);

        http_response($this->rsp_package($result));
    }

    /**
     * 全局规则配置修改
     * http://192.168.71.170:83/inland/Domestic/updateCfg
     */
    public function updateCfg()
    {
        if ($this->input->method() == 'post') {
            $api_name = $this->_server_module . strtolower(__FUNCTION__);
            $post = $this->input->post();
            $data = $this->validataCfg($post);
            $result = $this->_curl_request->cloud_post($api_name, $data);
            http_response($this->rsp_package($result));
        }
        $this->data['errorMess'] = 'bad request';
        http_response($this->data);

    }

    /**
     * 国内全局规则配置添加备注
     * @author W02278
     * @link http://192.168.71.170:83/inland/Domestic/addCfgRemark
     * CreateTime: 2019/4/25 10:07
     */
    public function addCfgRemark()
    {
        if ($this->input->method() == 'post') {
            $api_name = $this->_server_module . strtolower(__FUNCTION__);
            $post = $this->input->post();
            $data = $this->validataRemark($post);
            $result = $this->_curl_request->cloud_post($api_name, $data);
            http_response($this->rsp_package($result));
        }
        $this->data['errorMess'] = 'bad request';
        http_response($this->data);
    }

    /**
     * 备注验证
     * @param $params
     * @return array
     * @author W02278
     * CreateTime: 2019/4/25 10:07
     */
    private function validataRemark($params)
    {
        //验证id
        if (!isset($params['id']) || !$params['id'] || !trim($params['id'])) {
            $this->data['errorMess'] = 'id必须填写';
            http_response($this->data);
        }
        //验证备注
        if (!isset($params['remark']) || !trim($params['remark']) ) {
            $this->data['errorMess'] = '备注必须填写且不能为空';
            http_response($this->data);
        }

        $data = [
            'id' => $params['id'],
            'remark' => $params['remark'],
        ];

        return $data;
    }

    /**
     * 验证配置数据
     * @param $params
     * @return array
     * @author W02278
     * CreateTime: 2019/4/24 18:42
     */
    private function validataCfg($params)
    {
        //验证id
        if (!isset($params['id']) || !$params['id']) {
            $this->data['errorMess'] = '记录主键id必须填写';
            http_response($this->data);
        }

        if (!isset($params['bs']) || !$this->positiveInteger($params['bs'] , 1)) {
            $this->data['errorMess'] = '缓冲库存天数必须填写且不能为空';
            http_response($this->data);
        }
        if (!isset($params['sp']) || !$this->positiveInteger($params['sp'] , 1)) {
            $this->data['errorMess'] = '备货处理周期必须填写且不能为空';
            http_response($this->data);
        }
        if (!isset($params['shipment_time']) || !$this->positiveInteger($params['shipment_time'] , 1)) {
            $this->data['errorMess'] = '发运时效必须填写且不能为空';
            http_response($this->data);
        }
        if (!isset($params['sc']) || !$this->positiveInteger($params['sc'] , 1)) {
            $this->data['errorMess'] = '一次备货天数必须填写且不能为空';
            http_response($this->data);
        }
        if (!isset($params['first_lt']) || !$this->positiveInteger($params['first_lt'] , 3)) {
            $this->data['errorMess'] = '首次供货周期必须填写且不能为空';
            http_response($this->data);
        }
        if (!isset($params['sz']) || !$this->positiveInteger($params['sz'] , 2)) {
            $this->data['errorMess'] = '服务对应"z"值必须填写且不能为空';
            http_response($this->data);
        }

        $data = [
            'id' => $params['id'],
            'bs' => $params['bs'],
            'sp' => $params['sp'],
            'shipment_time' => $params['shipment_time'],
            'sc' => $params['sc'],
            'first_lt' => $params['first_lt'],
            'sz' => $params['sz'],
//            'updated_at' => date('Y-m-d H:i:s'),
//            'updated_zh_name' => $user_name,
//            'updated_uid' => $uid,
        ];
        return $data;
    }

    /**
     * 判断正整数和正数
     * @param $num
     * @param int $case 1: 正整数（包含0）;2:保留两位的正数;3：正整数（不包含0）
     * @return bool
     * @author W02278
     * CreateTime: 2019/4/24 18:10
     */
    private function positiveInteger(& $num, $case = 1){
        $res = false;
        $num = trim($num);
        if((($case == 1) && $num == 0 ) || $num ) {
            if(is_numeric($num)){
                switch ($case) {
                    case 1:
                        if ($num >= 0 && floor($num) == $num) {
                            $res = true;
                        }
                        break;
                    case 2:
                        if ($num > 0 /*&& floor($num) != $num*/) {
                            $num = number_format($num,2, '.' , '');
                            $res = true;
                        }
                        break;
                    case 3:
                        if ($num >= 0 && floor($num) == $num) {
                            $res = true;
                        }
                        break;
                }
                return $res;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    /**
     * 添加备注
     * http://192.168.71.170:83/inland/Domestic/addRemark
     */
    public function addRemark()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['station_code']) || empty($post['remark']) || empty($post['user_id']) || empty($post['user_name'])) {
            $this->response_error('3001');
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 日志列表
     * http://192.168.71.170:83/inland/Domestic/getGlobalLogList
     */
    public function getGlobalLogList()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (empty($get['station_code'])) {
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
/* Location: ./application/modules/fba/controllers/Global_rule_cfg.php */