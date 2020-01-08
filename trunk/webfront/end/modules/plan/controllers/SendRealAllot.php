<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 实际发运表
 *
 * @author lewei
 * @since 2019-10-24
 */
class SendRealAllot extends MY_Controller {
    
    private $_server_module = 'plan/SendRealAllot/';
    private $_export_url = '/sendfba/download/toerp';
    private $_server_name = 'FBA实际发运单';
    
    /**
     * 
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['plan_lang']);
        $this->load->helper('plan_helper');
    }
    
    /**
     * 列表
     */
    public function list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        $res = $this->rsp_package($result);
        http_response($res);
    }


    /**
     * 推送erp
     */
    public function push_erp(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        $res = $this->rsp_package($result);
        http_response($res);
    }

    /**
     *Notes:查看推送结果列表
     *User: lewei
     *Date: 2019/11/4
     *Time: 19:34
     */
    public function push_erp_result_list(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_push_erp_result_list_result($result['data_list']['value']);
        }
        $res = $this->rsp_package($result);
        http_response($res);
    }

    public function export()
    {
        $source_file = PYTHON_MRP_API.$this->_export_url;
        $filename = $this->_server_name.'_'.date('YmdHi').'.zip';
        header("Content-Type: application/force-download");
        header("Content-Disposition: attachment; filename=" . $filename);
        ob_clean();
        flush();
        readfile($source_file);
    }

}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */