<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内销量运算配置表
 */
class Operation_cfg extends MY_Controller {

    private $_server_module = 'inland/Operation_cfg/';

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
            tran_operation_cfg_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     *Notes: 国内销量运算配置模版下载
     *User: lewei
     *Date: 2019/11/21
     *Time: 11:16
     */
    public function templateDownload(){
        $file_dir = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../')) . "/" . '/end/upload/excel_template/';
        $file_name = "inland_operation_cfg_template.csv";
        $filename = '国内销量运算配置模板.csv';
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


    public function get_platform_info()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_post($api_name);
        http_response($this->rsp_package($result));
    }

    /**
     * 新增
     */
    public function add()
    {
        $post = $this->input->post();
        if(isset($post['is_get_base']) && $post['is_get_base'] == 1){//新增列表页
            $api_name = $this->_server_module.'get_platform_info';
            $result = $this->_curl_request->cloud_post($api_name);
            http_response($this->rsp_package($result));
        }
        $api_name = $this->_server_module.strtolower(__FUNCTION__);

        if(!isset($post['set_start_date']) || !isset($post['set_end_date'])){
            $this->data['errorMess'] = '参数不允许为空';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量删除
     */
    public function batch_delete()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(!isset($post['gid']) || empty($post['gid'])){
            $this->data['errorMess'] = '请勾选后再批量删除';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 修改
     */
    public function update()
    {
        $post = $this->input->post();
        $api_name = $this->_server_module.strtolower(__FUNCTION__);

        if(!isset($post['gid']) || !isset($post['set_start_date']) || !isset($post['set_end_date'])){
            $this->data['errorMess'] = '参数不允许为空';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 详情
     */
    public function detail()
    {
        $get = $this->input->get();
        if(!isset($get['gid']) || empty($get['gid'])){
            $this->data['errorMess'] = '参数不允许为空';
            http_response($this->data);
        }
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_operation_cfg_detail_result($result['data_list']['value']);
        }
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
     * csv导出
     */
    public function operation_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内运算配置表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }


    /**
     *Notes: 上传国内销量运算配置
     *User: lewei
     *Date: 2019/11/7
     *Time: 11:38
     */
    public function import(){
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $this->data['status'] = 0;
        $filename = $_FILES['file']['tmp_name'];
        if (empty ($filename)) {
            $this->data['errorMess'] = '请选择要导入的CSV文件！';
            http_response($this->data);
        }
        //导入数据的行数
        $line = count(file($filename));
        if ($line < 2) {
            $this->data['errorMess'] = '导入的数据为空';
            http_response($this->data);
        }

        $handle = fopen($filename, 'r');
        if (!$handle) {
            exit('读取文件失败');
        }
        $excelData = [];
        while (($data = fgetcsv($handle)) !== false) {
            $excelData[] = $data;
        }
        fclose($handle);
        //检测标题栏
        $this->load->helper('common');
        $modify_item = [
            'sku' => 'sku',
            'platform_code' => '平台',
            'set_start_date' => '开始时间',
            'set_end_date' => '结束时间',
        ];
        $check_title = validate_title(implode(',',$excelData[0]), $modify_item);
        if ($check_title['status'] == 0) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $check_title['errorMess'];
            http_response($this->data);
        }
        //删除标题栏目
        unset($excelData[0]);
        $processed = 0;//已处理
        $undisposed = 0;//未处理

        $insert_data_arr = [];
        foreach ($excelData as $k => $v){
            $insert_data_arr[] = array(
                'sku'   =>  $v[$check_title['final_list']['sku']],
                'platform_code'   =>  $v[$check_title['final_list']['platform_code']],
                'set_start_date'   =>  $v[$check_title['final_list']['set_start_date']],
                'set_end_date'   =>  $v[$check_title['final_list']['set_end_date']],
            );
        }
//        var_dump($insert_data_arr);exit;
        //导入到数据中
        $result = $this->_curl_request->cloud_post($api_name, $insert_data_arr);
        $processed = $result['data_list']['processed'];
        $undisposed = $result['data_list']['undisposed'];
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));
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

}