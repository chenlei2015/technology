<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 发运毛需求
 *
 * @author zc
 * @since 2019-10-24
 */
class Demand extends MY_Controller {
    private $_server_module = 'shipment/Demand/';
    private $_export_url = '/sendfba/download/require';
    private $_server_name = 'FBA发运毛需求';

    public function __construct()
    {
        parent::__construct();
        //$this->lang->load(['plan_lang']);
        //$this->load->helper('plan_helper');
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