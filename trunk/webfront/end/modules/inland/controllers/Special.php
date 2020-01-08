<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内特殊需求列表
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class Special extends MY_Controller {
    
    private $_server_module = 'inland/Special/';
    
    /**
     * 构造
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['inland_special_lang']);
        $this->load->helper('inland_helper');
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
            tran_inland_special_list_result($result['data_list']['value']);
        }
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
            tran_inland_detail_result($result['data']['pr']);
        }
        http_response($this->rsp_package($result));
    }
    
    /**
     * csv导出
     */
    public function export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内手动需求_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }
    
    /**
     * 审核
     */
    public function approve()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '请选择需要审核的记录']);
        }
        if (!isset($post['result']) || !in_array($post['result'], [APPROVAL_RESULT_PASS, APPROVAL_RESULT_FAILED]))
        {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 编辑需求数量
     */
    public function edit_pr()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '记录主键id必须填写']);
        }
        if (!array_key_exists('require_qty', $post))
        {
            http_response(['errorMess' => '需求数量必须填写']);
        }
        if (intval($post['require_qty']) <= 0)
        {
            http_response(['errorMess' => '需求数量必须大于0']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
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
     * 批量删除
     */
    public function delete()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '请选择需要删除的记录']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    public function uploadExcel()
    {
        require_once APPPATH . "third_party/PHPExcel.php";
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $this->lang->load('common');

        //获取上传文件数据
        $uploadExcel['name'] = $_FILES["file"]["name"];
        $uploadExcel['tmp_name'] = $_FILES["file"]["tmp_name"];

        //判断文件格式
        $path_parts = pathinfo($uploadExcel['name']);
        $extension = strtolower($path_parts['extension']);
        if (!in_array($extension, array('xls', 'xlsx'))) {
            $return_data['status'] = 0;
            $return_data['errorMess'] = $this->lang->myline('upload_file_format_incorrect');
            http_response($return_data);
        }


        $file = $_FILES["file"];

        if (empty($file)) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $this->lang->myline('upload_file_no');
        } else {
            $title = [
                'A'=>'requisition_date',
                'B'=>'requisition_zh_name',
                'C'=>'requisition_platform_name',
                'D'=>'sku',
                'E'=>'require_qty',
                'F'=>'requisition_reason',
                'G'=>'remark'
            ];
            $excelData = getExcelDetail($file, 2, $title,1);
        }


        if(count($excelData) > 50001){
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超过50000';
            http_response($this->data);
        }
        if (!empty($excelData)) {
            $api_name = $this->_server_module . strtolower(__FUNCTION__);
            $post['data'] = json_encode($excelData);//批量将sql插入数据库。
            $post['file_name'] = $file['name']??'';
            $result = $this->_curl_request->cloud_post($api_name, $post);

            http_response($this->rsp_package($result));

        } else {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $this->lang->myline('upload_not_data');
        }


        http_response($this->data);
    }

    public function templateDownload()
    {
        $file_dir = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../')) . "/" . '/end/upload/excel_template/';
        $file_name = "inland_sepcial_template.xlsx";
        $filename = '国内手动需求上传模板.xlsx';
        if (!file_exists($file_dir . $file_name)) {
            echo "文件找不到";
            exit ();
        } else {
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=" . $filename);
            ob_clean();
            flush();
            readfile($file_dir . $file_name);
        }
    }

}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */