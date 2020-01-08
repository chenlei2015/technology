<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内全局规则配置
 * @author W02278
 * @name  Sales_report Class
 */
class Sales_report extends MY_Controller
{

    private $_server_module = 'inland/Sales_report/';

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 查询国内销量报告列表
     * @author W02278
     * @link http://192.168.71.170:83/inland/Sales_report/getReportList
     * CreateTime: 2019/4/26 10:04
     */
    public function getReportList()
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

    public function export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内销量列表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }
}