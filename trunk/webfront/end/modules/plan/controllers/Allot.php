<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 调拨单
 *
 * @author Jason 13292
 * @since 2019-03-10
 */
class Allot extends MY_Controller {
    
    private $_server_module = 'plan/Allot/';
    
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
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_plan_allot_list_result($result['data_list']['value']);
        }
        $res = $this->rsp_package($result);
        if (!empty($get['isExcel'])) {
            if (empty($res['data_list']['value'])) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '数据为空,导出失败';
                http_response($this->data);
            }
            if (count($res['data_list']['value']) > MAX_EXCEL_LIMIT) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '导出数据必须小于' . MAX_EXCEL_LIMIT;
                http_response($this->data);
            }

            $date = date('YmdHis');
            $titleArr = $res['data_list']['key'];
            unset($titleArr[count($titleArr)-1]);
            $fp = export_head('备货计划-调拨列表导出-' . $date,$titleArr );
            $item = 1;
            $data = [];
            foreach ($result['data_list']['value'] as $key => $value) {
                $data[$key][] = $item;
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['allot_sn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pur_sn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['earliest_exhaust_date']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['purchase_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['in_warehouse_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['in_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['out_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['out_warehouse_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['actual_purchase_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['created_at']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pur_sn_state_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['remark']);

                $item++;
            }
            export_content($fp, $data);
            exit();
        }
        http_response($res);
    }
    
    /**
     * 添加一条备注
     */
    public function remark()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            $this->data['errorMess'] = '记录主键id必须填写';
            http_response($this->data);
        }
        if (!isset($post['remark']) || !$post['remark'] || trim($post['remark']) == '')
        {
            $this->data['errorMess'] = '备注必须填写且不能为空';
            http_response($this->data);
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }
    
    /**
     * 详情
     */
    public function detail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['gid']) || !$get['gid'])
        {
            http_response(['errorMess' => '请选择数据']);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if (isset($result['data']['pr']) && !empty($result['data']['pr']))
        {
            tran_oversea_detail_result($result['data']['pr']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 调拨列表
     */
    public function allot_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_plan_allot_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */