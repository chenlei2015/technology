<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 发运计划列表
 *
 * @author Jason 13292
 * @since 2019-07-02=9
 */
class Plan extends MY_Controller {
    
    private $_server_module = 'shipment/Plan/';

    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['shipment_lang']);
        $this->load->helper('shipment_helper');
    }
    
    public function list()
    {
        $get = $this->input->get();
        if($get['business_line'] == 1){
            $api_name = $this->_server_module.'fba_list';
        }elseif ($get['business_line'] == 2){
            $api_name = $this->_server_module.'oversea_list';
        }

        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_shipment_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

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
        }elseif ($line > 30001) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超出3万条';
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

        /**
         * 映射转换
         */
        foreach (FBA_LOGISTICS_ATTR as $key =>$value){
            $logistic_map[$value['name']] = $key;
        }
        foreach (FBA_STATION_CODE as $key =>$value){
            $station_map[$value['name']] = $key;
        }
        foreach (PURCHASE_WAREHOUSE as $key =>$value){
            $warehouse_map[$value['name']] = $key;
        }

        foreach (SHIPMENT_TYPE_LIST as $key =>$value){
            $shipment_type_map[$value['name']] = $key;
        }
        $refund_map = [
            '是'=>REFUND_TAX_YES,
            '否'=>REFUND_TAX_NO,
        ];

        $modify_item = [
            'sku' => 'SKU',
            'fnsku' => 'FNSKU',
            'seller_sku' => 'SELLER_SKU',
            'asin' => 'ASIN',
            'account_name' => 'FBA账号',
            'shipment_type' => '发运类型',
            'logistics_id' => '物流类型',
            'station_code' => '站点',
            'warehouse_id' => '发货仓库',
            'pr_qty' => '需求数量',
            'is_refund_tax' => '是否退税'
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
                    if($key == 'shipment_type'){// TODO 发运类型
                        $row[$key] = $shipment_type_map[$v[$val]]??'';
                    }elseif($key == 'logistics_id'){
                        $row[$key] = $logistic_map[$v[$val]]??'';
                    }elseif($key == 'station_code'){
                        $row[$key] = $station_map[$v[$val]]??'';
                    }elseif($key == 'warehouse_id'){
                        $row[$key] = $warehouse_map[$v[$val]]??'';
                    }elseif($key == 'is_refund_tax'){
                        $row[$key] = $refund_map[$v[$val]]??'';
                    }else{
                        $row[$key] = $v[$val];
                    }
                }
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
            $insertRows = [];
//            $processed += $result['data_list']['processed'];
//            $undisposed += $result['data_list']['undisposed'];

        }
        http_response($this->rsp_package($result));
    }



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
        }elseif ($line > 30001) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超出3万条';
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

        /**
         * 映射转换
         */
        foreach (LOGISTICS_ATTR as $key =>$value){
            $logistic_map[$value['name']] = $key;
        }
        foreach (OVERSEA_STATION_CODE as $key =>$value){
            $station_map[$value['name']] = $key;
        }
        foreach (PURCHASE_WAREHOUSE as $key =>$value){
            $warehouse_map[$value['name']] = $key;
        }
        foreach (SHIPMENT_TYPE_LIST as $key =>$value){
            $shipment_type_map[$value['name']] = $key;
        }
        $refund_map = [
            '是'=>REFUND_TAX_YES,
            '否'=>REFUND_TAX_NO,
        ];

        $modify_item = [
            'sku' => 'SKU',
            'shipment_type' => '发运类型',
            'logistics_id' => '物流类型',
            'station_code' => '站点',
            'warehouse_id' => '发货仓库',
            'pr_qty' => '需求数量',
            'is_refund_tax' => '是否退税'
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
                    if($key == 'shipment_type'){// TODO 发运类型
                        $row[$key] = $shipment_type_map[$v[$val]]??'';
                    }elseif($key == 'logistics_id'){
                        $row[$key] = $logistic_map[$v[$val]]??'';
                    }elseif($key == 'station_code'){
                        $row[$key] = $station_map[$v[$val]]??'';
                    }elseif($key == 'warehouse_id'){
                        $row[$key] = $warehouse_map[$v[$val]]??'';
                    }elseif($key == 'is_refund_tax'){
                        $row[$key] = $refund_map[$v[$val]]??'';
                    }else{
                        $row[$key] = $v[$val];
                    }
                }
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
            $insertRows = [];
//            $processed += $result['data_list']['processed'];
//            $undisposed += $result['data_list']['undisposed'];

        }

        http_response($this->rsp_package($result));
    }



    /**
     * 根据跟踪列表 生成发运计划
     */
    public function planByTracking()
    {
        $post = $this->input->post();
        if($post['business_line'] == BUSINESS_LINE_FBA){
            $api_name = $this->_server_module.'fbaPlanByTracking';
        }elseif ($post['business_line'] == BUSINESS_LINE_OVERSEA){
            $api_name = $this->_server_module.'overseaPlanByTracking';
        }

        $result = $this->_curl_request->cloud_post($api_name, $post);

        http_response($this->rsp_package($result));
    }


    /**
     * 选择日期
     */
    public function getDate(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();

        $result = $this->_curl_request->cloud_get($api_name, $get);

        http_response($this->rsp_package($result));
    }


    /**
     * 下载导入模板  支持下载多条业务线的模板
     */
    public function templateDownload()
    {
        $get = $this->input->get();
        $file_dir = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../')) . "/" . '/end/upload/excel_template/';
        if($get['business_line'] == 1){
            $file_name = "fba_shipment_template.csv";
            $filename = 'FBA发运需求导入模板.csv';
        }elseif ($get['business_line'] == 2){
            $file_name = "oversea_shipment_template.csv";
            $filename = '海外发运需求导入模板.csv';
        }
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


    /**
     * 账号管理列表
     */
    public function oversea_manager_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    
    /**
     * 设置站点管理员
     */
    public function set_station_manager()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        
        if (!isset($post['staff_code']) || !$post['staff_code'])
        {
            http_response(['errorMess' => '请选择要设置的管理员的工号']);
        }
        if (!isset($post['station_code']) || !$post['station_code'])
        {
            http_response(['errorMess' => '请选择要设置的站点编码']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        if ($result['status'] == 1)
        {
            $cache_full_list = 'MANAGER_SHIPMENT_OVERSEA_FULL_LIST';
            $this->load->library('rediss');
            $this->rediss->deleteData($cache_full_list);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 发运计划推送
     */
    public function push(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['shipment_sn']) || !isset($post['business_line'])){
            http_response(['status'=>0,'errorMess' => '参数错误']);
        }
        if($post['business_line'] == 1){
            $api_name = $this->_server_module.'fba_push';
        }elseif ($post['business_line'] == 2){
            $api_name = $this->_server_module.'oversea_push';
        }

        $result = $this->_curl_request->cloud_post($api_name, $post);

        http_response($this->rsp_package($result));
    }

    /**
     * 手动发送至物流系统
     */
    public function send(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['shipment_sn']) || !isset($post['business_line'])){
            http_response(['status'=>0,'errorMess' => '参数错误']);
        }
        if($post['business_line'] == 1){
            $api_name = $this->_server_module.'fba_send';
        }elseif ($post['business_line'] == 2){
            $api_name = $this->_server_module.'oversea_send';
        }

        $result = $this->_curl_request->cloud_post($api_name, $post);

        http_response($this->rsp_package($result));
    }


    
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */