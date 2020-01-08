<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内销量运算配置表
 */
class Report_inventory extends MY_Controller {

    private $_server_module = 'inland/Report_inventory/';

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
            tran_inventory_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }



    /**
     * csv导出
     */
    public function inventory_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内库存报表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
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