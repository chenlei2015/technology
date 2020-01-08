<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 海外仓需求列表
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class PR extends MY_Controller {
    
    private $_server_module = 'inland/PR/';
    
    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['inland_lang']);
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
            tran_inland_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }
    
    /**
     * csv导出
     */
    public function inland_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内需求列表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }
    
    public function inland_track_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内跟踪列表_'.date('Ymd_H_i');
        $this->common_export($api_name, $file_name);
    }
    
    public function inland_summary_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内汇总需求列表_'.date('Ymd_H_i');
        $this->common_export($api_name, $file_name);
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
     * 批量一级审核
     */
    public function batch_approve_first()
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
     *审核
     */
    public function asyc_approve()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
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
     * 跟踪列表
     */
    public function track_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_inland_track_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }
    
    /**
     * 详情
     */
    public function track_detail()
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
            //tran_inland_detail_result($result['data']['pr']);
        }
        http_response($this->rsp_package($result));
    }
    
    /**
     * 添加一条备注
     */
    public function track_remark()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '主键gid必须填写']);
        }
        if (!isset($post['remark']) || !$post['remark'] || trim($post['remark']) == '')
        {
            http_response(['errorMess' => '备注必须填写且不能为空']);
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }
    
    /**
     * 跟踪列表
     */
    public function summary_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_inland_summary_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }
    
    /**
     * 详情
     */
    public function summary_detail()
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
            //tran_detail_result($result['data']['pr']);
        }
        http_response($this->rsp_package($result));
    }
    
    /**
     * 添加一条备注
     */
    public function summary_remark()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '主键gid必须填写']);
        }
        if (!isset($post['remark']) || !$post['remark'] || trim($post['remark']) == '')
        {
            http_response(['errorMess' => '备注必须填写且不能为空']);
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 审核汇总
     */
    public function execute_summary()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, []);
        http_response($this->rsp_package($result));
    }

    /**
     * xls文件上传接口, 先上传visit, 然后通过curl上传到data,
     * 返回hash文件名，请求创建接口时携带hash文件名
     */
    public function csv_edit()
    {
        $config = [
                'upload_path' => APPPATH . '/upload/',
                'allowed_types' => 'csv',
        ];
        $this->load->library('upload', $config);
        $today_dir = get_sgs_upload_path() . date('Ymd') . DIRECTORY_SEPARATOR;
        if (!is_dir($today_dir))
        {
            mkdir($today_dir, 0775, true);
            clearstatcache();
        }
        
        if (!is_dir($today_dir))
        {
            $this->data['errorMess'] = '创建目录'.$today_dir.'失败，请检查权限';
            http_response($this->data);
            exit;
        }
        $this->upload->set_upload_path($today_dir);
        
        if (!$this->upload->do_upload('file'))
        {
            $this->data['errorCode'] = 500;
            $this->data['errorDesc'] = $this->upload->display_errors();
            logger('error', 'csv create suggest order error, upload failed', $this->data['errorDesc']);
            http_response($this->data);
        }
        else
        {
            //$full_path = 'E:/www/project_visit/end/upload/fmb_small.xlsx';
            //上传到服务器,返回url地址
            $upload_data = $this->upload->data();
            
            $full_path = $upload_data['full_path'];
            
            //改由直接发送数据
            $file = $this->_make_curl_file($full_path);
            $data = [$form_upload_name => $file];
            $this->data = RPC_CALL('YB_J3_FBA_006', $data, null);
            //$api_name = PLAN_FBA_JAVA_API_URL.'/fbaPr/importCsv';
            //$this->data = $this->_curl_upload_file($api_name, $full_path, 'file', ['direct_url' => true]);
            
            //直接输出文件
            /*$api_name = $this->_server_module.'import_upload_data';
             $post = [
             'file' => $upload_data['file_name'],
             'binary' => file_get_contents($full_path)
             ];
             $this->data = $this->_curl_request->cloud_post($api_name, $post);
             $this->data['file'] = $post['file'];*/
            
            http_response($this->data);
        }
        exit;
    }

    /**
     * 修改
     * http://192.168.71.170:1083/inland/pr/modify
     */
    public function modify()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['gid'])) {
            $this->data['errorMess'] = 'id为空';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 导入新增或更新
     */
    public function import()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        $this->data['status'] = 0;
        $filename = $_FILES['file']['tmp_name'];
        if (empty ($filename)) {
            $this->data['errorMess'] = '请选择要导入的CSV文件！';
            http_response($this->data);
        }
        fopen($filename, 'r');
        $line = count(file($filename));
        if ($line < 2) {
            $this->data['errorMess'] = '导入的数据为空';
            http_response($this->data);
        }
        if ($line > 10001) {
            $this->data['errorMess'] = '导入的数据不能超出1万条';
            http_response($this->data);
        }
        $content = trim(file_get_contents($filename));
        $excelData = explode("\n", $content);
        $chunkData = array_chunk($excelData, 2000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理

        $modify_item = [
            'pr_sn' => '需求单号',
            'fixed_amount' => '一次修正量'//一次修订量
        ];

        $this->load->helper('common');
        $result = validate_title($chunkData[0][0], $modify_item);
        unset($chunkData[0][0]);
        if ($result['status'] == 0) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $result['errorMess'];
            http_response($this->data);
        } else {
            $final_list = $result['final_list'];
        }

        $err_info['errorLines'] = [];
        $line_num = 1;
        for ($i = 0; $i < $count; $i++) {
            $insertRows = [];
            foreach ($chunkData[$i] as $k => $value) {
                $line_num++;
                $string = iconv($result['char'], 'utf-8//IGNORE', trim(strip_tags($value)));//转码
                $v = explode(',', trim($string));
                $row = [];
                foreach ($final_list as $key => $val) {
                    $row[$key] = $v[$val];
                }
                $row['line_num'] = $line_num;
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库
            $post_result = $this->_curl_request->cloud_post($api_name, $post);
            $processed += $post_result['data_list']['processed'];
            $undisposed += $post_result['data_list']['undisposed'];
            $err_info['errorLines'] = array_merge($err_info['errorLines'],json_decode($post_result['data_list']['line_num']));
        }
        $this->data['status'] = 1;
        $file_name = pathinfo($_FILES["file"]["name"], PATHINFO_FILENAME);
        $errorFiles = import_error_report($err_info,$file_name);
        $this->data['data_list'] = [
            'processed' => $processed,
            'undisposed' => $undisposed,
            'errorFiles' => $errorFiles['error_file'],
            'errorMsg' => $errorFiles['error_msg']
        ];
        http_response($this->rsp_package($this->data));
    }

    /**
     * 批量审核
     */
    public function batch_approve()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid']) {
            http_response(['errorMess' => '请选择需要审核的记录']);
        }
        if (!isset($post['result']) || !in_array($post['result'], [INLAND_APPROVAL_STATE_FAIL, INLAND_APPROVAL_STATE_SUCCESS])) {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量审核全部未审核
     */
    public function batch_approve_all()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['result']) || !in_array($post['result'], [INLAND_APPROVAL_STATE_FAIL, INLAND_APPROVAL_STATE_SUCCESS])) {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }
    
    private function _make_curl_file($file){
        $mime = mime_content_type($file);
        //$finfo = finfo_open(FILEINFO_MIME_TYPE);
        //$mime = strtolower(finfo_file($finfo, $file));
        $info = pathinfo($file);
        $name = $info['basename'];
        $output = new CURLFile($file, $mime, $name);
        return $output;
    }
    
    private function _curl_upload_file($api_name, $full_path, $form_upload_name, $params = [])
    {
        $url = isset($params['direct_url']) ? $api_name : trim(PLAN_HOST, '/').'/'.$api_name;
        
        $file = $this->_make_curl_file($full_path);
        $data = [$form_upload_name => $file];
        $param = array_merge($params, $data);
        
        $headers = [
                'Content-type: application/json;charset=utf-8',
        ];
        
        $ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //curl_setopt($ch, CURLOPT_PORT, PLAN_HOST_PORT);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($ch, CURLOPT_UPLOAD, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //$reponse = curl_exec($ch);
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        
        $reponse = curl_exec($ch);

        
        if (curl_errno($ch) != 0)
        {
            $reponse['errorMess'] = curl_error($ch);
            log_message('ERROR', json_encode($reponse['errorMess']));
            return $reponse;
        }
        curl_close($ch);
        if(!empty($reponse))
        {
            $raw_reponse = $reponse;
            $reponse = json_decode($reponse,true);
            if (json_last_error() != JSON_ERROR_NONE)
            {
                $reponse['errorMess'] = json_last_error_msg();
            }
            $reponse['raw'] = $raw_reponse;
        }
        return $reponse;
    }
    
	public function rebuild_pr()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

    public function asyc_rebuild_pr()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }
    
    public function get_inland_total_money()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */