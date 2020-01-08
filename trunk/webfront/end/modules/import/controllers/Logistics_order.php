<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/10
 * Time: 13:41
 */

class Logistics_order extends MY_Controller
{

    private $_server_module = 'import/Logistics_order/';

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
    }

    public function list()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name);
        http_response($this->rsp_package($result));
    }

    public function uploadExcel()
    {
        require_once APPPATH . "third_party/PHPExcel.php";
        set_time_limit(3600);
        ini_set('memory_limit', '1024M');
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
            $excelData = getExcelDetail($file, 1, ['A']);
        }

        if(count($excelData) > 50000){
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
        $file_name = "logistics_template.xlsx";
        $filename = '物流单号上传模板.xlsx';
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