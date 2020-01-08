<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 发运计划详情列表
 *
 * @author Jason 13292
 * @since 2019-07-09
 */
class Detail extends MY_Controller {
    
    private $_server_module = 'shipment/Detail/';

    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['shipment_lang']);
        $this->load->helper('shipment_helper');
    }
    
    public function fba()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if(!isset($get['shipment_sn']) || empty($get['shipment_sn'])){
            $this->data['status'] = 0;
            $this->data['errorMess'] = '未找到对应的发运详情';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_shipment_fba_list_result($result['data_list']['value']);
        }
        if (!empty($result['data_list']['sp_info'])){
            tran_sp_info_result($result['data_list']['sp_info']);
        }
        http_response($this->rsp_package($result));
    }
    
    public function oversea()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if(!isset($get['shipment_sn'])|| empty($get['shipment_sn'])){
            $this->data['status'] = 0;
            $this->data['errorMess'] = '未找到对应的发运详情';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);

        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_shipment_oversea_list_result($result['data_list']['value']);
        }
        if (!empty($result['data_list']['sp_info'])){
            tran_sp_info_result($result['data_list']['sp_info']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 修改发运数量(未推送状态)
     */
    public function fba_modify()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        $result = $this->_curl_request->cloud_post($api_name, $post);

        http_response($this->rsp_package($result));
    }

    /**
     * 修改发运数量(未推送状态)
     */
    public function oversea_modify()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        $result = $this->_curl_request->cloud_post($api_name, $post);

        http_response($this->rsp_package($result));
    }

    /**
     * 导入批量修改发运数量
     */
    public function fba_upload()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $this->lang->load('common');

        //获取上传文件数据
        $filename = $_FILES['file']['tmp_name'];
        if (empty ($filename)) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '请选择要导入的CSV文件！';
            http_response($this->data);
        }

        //将文件一次性全部读出来
        fopen($filename, 'r');
        $line = count(file($filename));
        if ($line < 2) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据为空';
            http_response($this->data);
        }elseif ($line > 50001) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超出5万条';
            http_response($this->data);
        }

        $content = trim(file_get_contents($filename));
        if(($char = detect_encoding($content)) == null){
            $this->data['status'] = 0;
            $this->data['errorMess'] = '文件编码异常!';
            http_response($this->data);
        };

        $excelData = [];
        $excelData = explode("\n", $content);
        $chunkData = array_chunk($excelData, 5000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        $modify_item = [
            'shipment_qty' => '发运数量',
            'gid'=>'请勿改动此标记'
        ];
        $this->load->helper('common');
        $result = validate_title($char,$chunkData[0][0],$modify_item);
        unset($chunkData[0][0]);
        if ($result['status']==0){
            $this->data['status'] = 0;
            $this->data['errorMess'] = $result['errorMess'];
            http_response($this->data);
        }else{
            $final_list = $result['final_list'];
        }

        for ($i = 0; $i < $count; $i++) {

            foreach ($chunkData[$i] as $value) {
                $string = iconv($char, 'utf-8//IGNORE', trim(strip_tags($value)));//转码
                $v = explode(',', trim($string));
                $row = [];
                foreach ($final_list as $key => $val){
                    $row[$key] = $v[$val];
                }
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
            $insertRows = [];
            $processed += $result['data_list']['processed'];
            $undisposed += $result['data_list']['undisposed'];

        }

        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));
    }

    /**
     * 导入批量修改发运数量
     */
    public function oversea_upload()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $this->lang->load('common');

        //获取上传文件数据
        $filename = $_FILES['file']['tmp_name'];
        if (empty ($filename)) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '请选择要导入的CSV文件！';
            http_response($this->data);
        }

        //将文件一次性全部读出来
        fopen($filename, 'r');
        $line = count(file($filename));
        if ($line < 2) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据为空';
            http_response($this->data);
        }elseif ($line > 50001) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超出5万条';
            http_response($this->data);
        }

        $content = trim(file_get_contents($filename));
        if(($char = detect_encoding($content)) == null){
            $this->data['status'] = 0;
            $this->data['errorMess'] = '文件编码异常!';
            http_response($this->data);
        };

        $excelData = [];
        $excelData = explode("\n", $content);
        $chunkData = array_chunk($excelData, 5000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        $modify_item = [
            'shipment_qty' => '发运数量',
            'gid'=>'请勿改动此标记'
        ];
        $this->load->helper('common');
        $result = validate_title($char,$chunkData[0][0],$modify_item);
        unset($chunkData[0][0]);
        if ($result['status']==0){
            $this->data['status'] = 0;
            $this->data['errorMess'] = $result['errorMess'];
            http_response($this->data);
        }else{
            $final_list = $result['final_list'];
        }

        for ($i = 0; $i < $count; $i++) {

            foreach ($chunkData[$i] as $value) {
                $string = iconv($char, 'utf-8//IGNORE', trim(strip_tags($value)));//转码
                $v = explode(',', trim($string));
                $row = [];
                foreach ($final_list as $key => $val){
                    $row[$key] = $v[$val];
                }
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
            $insertRows = [];
            $processed += $result['data_list']['processed'];
            $undisposed += $result['data_list']['undisposed'];

        }

        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));
    }

    /**
     * 导出
     */
    public function fba_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'FBA_发运计划详情'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }


    /**
     * 导出
     */
    public function oversea_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '海外_发运计划详情'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }

    /**
     * 发运详情
     */
    public function fba_shipment_detail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data']))
        {
            tran_fba_tracking_detail_result($result['data']);
        }

        http_response($this->rsp_package($result));
    }

    /**
     * 发运详情
     */
    public function oversea_shipment_detail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data']))
        {
            tran_oversea_tracking_detail_result($result['data']);
        }
        http_response($this->rsp_package($result));
    }
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */