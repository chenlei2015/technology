<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 海外仓物流属性配置表
 *
 * @author bigfong
 * @since 2019-03-09
 */
class Logistics_attr_cfg extends MY_Controller {

    private $_server_module = 'oversea/Logistics_attr_cfg/';

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 列表页
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/getLogisticsList
     */
    public function getLogisticsList()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();

        if (!empty($get['isExcel'])) {
            $file_path = './end/upload/Oversea_logistics_attr_temp.csv';
            if (file_exists($file_path)) {
                unlink($file_path);
            }
            //1.先查询出要查询的数据的数据量
            $get['get_count'] = 1;
            $count = $this->_curl_request->cloud_get($api_name, $get);
            $get['get_count'] = NULL;
            $count = $count['total'];
            if($count > 10000){
                $this->data['status'] = 0;
                $this->data['errorMess'] = '导出的数据不能超过10000条,请筛选后导出';
                http_response($this->data);
            }
            $length = 1000;
            $start = 0;
            while ($start < 10000) {
                $get['length'] = $length;
                $get['start'] = $start;
                $result = $this->_curl_request->cloud_get($api_name, $get);
                if (count(array_filter($result)) > 0) {
                    if ($start == 0) {//首次要写入标题
                        if (empty($result['data_list']['value'])) {
                            $this->data['status'] = 0;
                            $this->data['errorMess'] = '数据为空,导出失败';
                            http_response($this->data);
                        }
                        $fp = fopen($file_path, 'a');
                        //写标题
                        $title = $result['data_list']['key'];
                        if (!empty($title)) {
                            foreach ($title as $k => $v) {
                                $title[$k] = iconv("UTF-8", "GB2312//IGNORE", $v);
                            }
                        }
                        fputcsv($fp, $title);
                        //转码
                        $data = $this->character($result, $start);
                        unset($result);
                        //写入内容
                        export_content($fp, $data);
                        fclose($fp);
                        if(!empty($get['id']) && $get['id']!='[]'){//勾选最多支持2000
                            export_csv($file_path, '海外仓物流属性配置导出');
                        }
                    } else {
                        $fp = fopen($file_path, 'a');
                        //转码
                        $data = $this->character($result, $start);
                        unset($result);
                        //写入
                        export_content($fp, $data);
                        fclose($fp);
                    }
                    $start += 1000;

                } else {//查不出结果了 导出
                    export_csv($file_path, '海外仓物流属性配置导出');
                }
            }
            //循环结束导出文件
            export_csv($file_path, '海外仓物流属性配置导出');

        }else{
            //正常访问列表
            $result = $this->_curl_request->cloud_get($api_name,$get);
            http_response($this->rsp_package($result));
        }
    }

    /**
     * 导出转码处理
     * @param $result
     * @param int $item
     * @return array
     */
    public function character($result, $item = 1)
    {
        $data = [];
        if (is_array($result['data_list']['value'])) {
            foreach ($result['data_list']['value'] as $key => $value) {
                foreach ($value as $k => $v){
                    $value[$k] = str_replace(",","，",$v);//将英文逗号转成中文逗号
                }
                $data[$key][] = $item+1;
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['station_code']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_state_zh']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['logistics_id_zh']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['approve_state_zh']);
                $value['created_at'] = $value['created_at'] . "\t";  //解决时间显示#####
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['created_at']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['updated_zh_name'].' '.$value['updated_at']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['approve_zh_name'].' '.$value['approve_at']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['remark']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['id']);

                $item++;
            }
        }
        return $data;
    }

    /**
     * 预览详情页
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/getLogisticsDetails
     */
    public function getLogisticsDetails()
    {
        $get = $this->input->get();
        if (empty($get['id'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($this->_server_module.strtolower(__FUNCTION__),$get);
        http_response($this->rsp_package($result));
    }

    /**
     * 修改
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/modify
     */
    public function modify(){
        $post = $this->input->post();
        if(empty($post['id']) || empty($post['user_id'])  || empty($post['user_name']) || empty($post['refund_rate'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($this->_server_module.strtolower(__FUNCTION__), $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 添加备注
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/addRemark
     */
    public function addRemark(){
        $post = $this->input->post();
        if(empty($post['id']) || empty($post['remark']) || empty($post['user_id']) || empty($post['user_name'])){
            $this->response_error('3001');
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($this->_server_module.strtolower(__FUNCTION__), $post);
        http_response($this->rsp_package($result));
    }


    /**
     * 批量审核成功
     * http://192.168.71.170:1084/oversea/stock_relationship_cfg/batchCheckSuccess
     */
    public function batchCheckSuccess()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(empty($post['id']) || empty($post['user_id']) || empty($post['user_name'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量审核失败
     * http://192.168.71.170:1083/oversea/Stock_relationship_cfg/batchCheckFail
     */
    public function batchCheckFail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(empty($post['id']) || empty($post['user_id']) || empty($post['user_name'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }


    /**
     * 异步全量审核入口
     */
    public function asyc_approve(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

    /**
     * 获取异步全量统计信息
     */
    public function approve_process()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

    /**
     * 日志列表
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/getLogList
     */
    public function getLogList(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if(empty($get['id'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $get);
        http_response($this->rsp_package($result));
    }


    /**
     * 默认物流配置页面
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/defaultCfDetail
     */
    public function defaultCfDetail(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_post($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 默认物流配置修改
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/defaultCfModify
     */
    public function defaultCfModify(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(empty($post['user_id']) || empty($post['user_name']) || empty($post['logistics_id'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 默认物流配置日志列表
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/getDefaultCfLogList
     */
    public function getDefaultCfLogList(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_post($api_name, $get);
        http_response($this->rsp_package($result));
    }

    function detect_encoding($file) {
        $list = array('GBK', 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1');
        $str = file_get_contents($file);
        foreach ($list as $item) {
            $tmp = mb_convert_encoding($str, $item, $item);
            if (md5($tmp) == md5($str)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * 批量修改导入
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/uploadExcel
     */
/*    public function uploadExcel()
    {

        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['user_id']) || empty($post['user_name'])) {
            $this->response_error('3001');
        }
        $filename = $_FILES['file']['tmp_name'];
        if (empty ($filename)) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '请选择要导入的CSV文件！';
            http_response($this->data);
        }

        $char = $this->detect_encoding($filename);
        $coding = ['GBK','GB2312'];
        if($char == 'UTF-8'){
            $this->data['status'] = 0;
            $this->data['errorMess'] = '无法导入未修改文件！';
            http_response($this->data);
        }elseif (!in_array($char,$coding)){
            $this->data['status'] = 0;
            $this->data['errorMess'] = '文件编码异常！';
            http_response($this->data);
        }

        fopen($filename, 'r');
        $line = count(file($filename));
        if ($line < 2) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据为空';
            http_response($this->data);
        } elseif ($line > 10001) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超出1万条';
            http_response($this->data);
        }
        $content = trim(file_get_contents($filename));
        $excelData = [];
        $excelData = explode("\n", $content);
        $chunkData = array_chunk($excelData, 2000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        //listing状态映射
        foreach (SKU_STATE as $key =>$value){
            $sku_state[$value['name']] = $key;
        }
        //物流属性状态映射
        foreach (LOGISTICS_ATTR as $key =>$value){
            $logistic_list[$value['name']] = $key;
        }

        for ($i = 0; $i < $count; $i++) {
            $insertRows = array();
            foreach ($chunkData[$i] as $k => $value) {
                $string = iconv($char, 'utf-8//IGNORE', trim(strip_tags($value)));//转码
                $n = substr_count($string,',');
                if($n != 12){
                    continue;
                }
                $v = explode(',', trim($string));
                $row = array();
                $row['sku_state'] = $sku_state[$v[3]]??'';
                $row['logistics_id'] = $logistic_list[$v[4]]??'';
                $row['id'] = is_numeric($v[12]) ? $v[12] : '';
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
            $processed += $result['data_list']['processed'];
            $undisposed += $result['data_list']['undisposed'];
        }
        $undisposed -= 1;  //减去第一行未处理
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));
    }*/

    /**
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/export
     * 新导出功能
     */
    public function export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '海外_物流属性配置表'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }

    /**
     * 消除所有特殊情况
     */
//    public function eliminate($str){
//        $str = str_replace(array("\r\n", "\r", "\n"), "", $str);
//        return $str;
//
//    }

    public function uploadExcel()
    {

        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['user_id']) || empty($post['user_name'])) {
            $this->response_error('3001');
        }
        $filename = $_FILES['file']['tmp_name'];
//        pr($filename);exit;
        if (empty ($filename)) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '请选择要导入的CSV文件！';
            http_response($this->data);
        }

        fopen($filename, 'r');
        $line = count(file($filename));
        if ($line < 2) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据为空';
            http_response($this->data);
        } elseif ($line > 10001) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超出1万条';
            http_response($this->data);
        }
        $content = trim(file_get_contents($filename));
        $excelData = [];
        $excelData = explode("\n", $content);
        $chunkData = array_chunk($excelData, 2000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        //listing状态映射
        foreach (SKU_STATE as $key =>$value){
            $sku_state[$value['name']] = $key;
        }
        //物流属性状态映射
        foreach (LOGISTICS_ATTR as $key =>$value){
            $logistic_list[$value['name']] = $key;
        }
        foreach (PRODUCT_STATUS_ALL as $key => $value){
            $product_status[$value['name']] = $key;
        }
        //允许空海混发映射
        foreach (MIX_HAIR_STATE as $key => $value){
            $mix_hair_state[$value['name']] = $key;
        }
        //侵权属性映射
        foreach (INFRINGEMENT_STATE as $key => $value){
            $infringement_state[$value['name']] = $key;
        }
        //违禁属性映射
        foreach (CONTRABAND_STATE as $key => $value){
            $contraband_state[$value['name']] = $key;
        }
//        //运营状态映射
//        foreach (LISTING_STATE as $key => $value){
//            $listing_state[$value['name']] = $key;
//        }
        $modify_item = [
            'sku_state' => '计划系统sku状态',
            'product_status' => 'erp系统sku状态',
            'logistics_id' => '物流属性',
            'mix_hair' => '是否允许空海混发',
            'infringement_state' => '是否侵权',
            'contraband_state' => '是否违禁',
//            'listing_state' => '运营状态',
            'id' => '请勿改动此标记'
        ];

        $this->load->helper('common');
        $result = validate_title($chunkData[0][0],$modify_item);
        unset($chunkData[0][0]);
        if ($result['status']==0){
            $this->data['status'] = 0;
            $this->data['errorMess'] = $result['errorMess'];
            http_response($this->data);
        }else{
            $final_list = $result['final_list'];
        }

        for ($i = 0; $i < $count; $i++) {
            $insertRows = [];
            foreach ($chunkData[$i] as $k => $value) {
                $string = iconv($result['char'], 'utf-8//IGNORE', trim(strip_tags($value)));//转码
//                $n = substr_count($string,',');
//                if($n > 12){
//                    continue;
//                }
                $v = explode(',', trim($string));
                $row = [];
                foreach ($final_list as $key => $val) {
                    if($key == 'sku_state'){
                        $row[$key] = $sku_state[$v[$val]]??'';
                    }elseif ($key == 'logistics_id'){
                        $row[$key] = $logistic_list[$v[$val]]??'';
                    }elseif($key == 'id'){
                        $row[$key] = is_numeric($v[$val]) ? $v[$val] : '';
                    }elseif ($key == 'infringement_state'){
                        $row[$key] = $infringement_state[$v[$val]]??'';
                    }elseif ($key == 'mix_hair'){
                        $row[$key] = $mix_hair_state[$v[$val]]??'';
                    }elseif ($key == 'contraband_state'){
                        $row[$key] = $contraband_state[$v[$val]]??'';
                    }elseif ($key == 'product_status'){
                        $row[$key] = $product_status[$v[$val]]??'';
                    }
                }
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
            $processed += $result['data_list']['processed'];
            $undisposed += $result['data_list']['undisposed'];
        }
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));
    }


    /*
     * 批量修改运营状态
     */
    public function edit_listing_state(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['ids']) || empty($get['ids'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $get);
        http_response($this->rsp_package($result));
    }


    /**
     * 批量导入修改
     * http://192.168.71.170:1083/oversea/Logistics_attr_cfg/upload_add
     */
    public function upload_add()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();

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
        }elseif ($line > 10001) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超出1万条';
            http_response($this->data);
        }
        $content = trim(file_get_contents($filename));
        $excelData = [];
        $excelData = explode("\n", $content);
//        $chunkData = array_chunk($excelData, 2000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
//        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        $error_data = [];
        $errorMess = '';

        //站点中文转code
        $station_code = array_keys(OVERSEA_STATION_CODE);
        $station_name = array_column(OVERSEA_STATION_CODE,'name');
        $station_map = array_combine($station_name,$station_code);
        //物流属性中文转code
        $logistics_key = array_keys(LOGISTICS_ATTR);
        $logistics_name = array_column(LOGISTICS_ATTR,'name');
        $logistics_map = array_combine($logistics_name,$logistics_key);

        $modify_item = [
            'sku' => 'SKU',
            'station_code' => '站点',
            'logistics_id' => '物流属性',
            'refund_rate' => '退款率',
        ];

        $this->load->helper('common');
        $result = validate_title($excelData[0],$modify_item);
        unset($excelData[0]);//删除标题
        if ($result['status']==0){
            $this->data['status'] = 0;
            $this->data['errorMess'] = $result['errorMess'];
            http_response($this->data);
        }else{
            $final_list = $result['final_list'];
        }
        $lie_count = count($final_list);
        $insertRows = [];
        foreach ($excelData as &$value) {
            $string = iconv($result['char'], 'utf-8//IGNORE', trim(strip_tags($value)));//转码
            $data = explode(',', trim($string));

            $row = [];
            foreach ($final_list as $key => $val) {
                if($key == 'station_code') {
                    $row[$key] = $station_map[$data[$val]]??'';
                    if (empty($row[$key])){
                        //站点异常
//                        $errorMess .= sprintf('站点异常:%s;',$data[$val]);
                        unset($row[$key]);
                    }
                }elseif ($key == 'logistics_id'){
                    $row[$key] = $logistics_map[$data[$val]]??0;
                }elseif($key == 'sku'){
                    $row[$key] = $data[$val];
                    if (empty($data[$val])){
                        unset($row[$key]);
                    }
                }
                if ($key == 'refund_rate') {
                    $row[$key] = number_format(floatval($data[$val]), 2);
                }
            }
            if (count($row) != $lie_count){//每一列都是必填,不能为空
                //将必填项为空的和填写的数据不存在的记录到redis
                $error_data[] = $data;
            }else{
                $insertRows[] = $row;
            }
        }

/*        $error_data = json_encode($error_data);
        $active_login_info = get_active_user()->get_user_info();
        $staff_code = $active_login_info['oa_info']['staff_code'];
        $this->load->library('rediss');
        $this->rediss->setData(sprintf('FBA_LOGISTIC_UPLOAD_ADD_%s',$staff_code),$error_data);*/



//        if (count($excelData) != count(array_filter($insertRows)) || !empty($errorMess)){
//            http_response(['status'=>0,'errorMess'=>$errorMess]);
//        }
        if (!empty($insertRows)){
            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
        }
        if (!empty($error_data)){
            $post['error_data'] = json_encode($error_data);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        if($result['status'] == 0){
            http_response($result);
        }elseif ($result['status'] == 1){
            $processed += $result['data_list']['processed'];
            $all_count = $line - 1;//导入文件的行数减去标题
            $undisposed = $all_count - $processed;
        }
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));

    }

    /**
     * 下载导入模板
     */
    public function templateDownload()
    {
        $file_dir = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../')) . "/" . '/end/upload/excel_template/';

        $file_name = "oversea_add_template.csv";
        $filename = '海外物流属性配置表导入SKU模板.csv';

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
     * 下载导入失败的数据
     */
    public function undisposed_data_download()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        $result = $this->_curl_request->cloud_post($api_name, $post);
        $head_list = [
            'sku' => 'SKU',
            'station_code'=>'站点',
            'logistics_id'=>'物流属性',
        ];
        $filename = '操作失败数据'.date('YmdHis');
        if ($result['status'] == 1 && isset($result['data'])){
            csv_export($result['data'],$head_list,$filename);
        }else{
            http_response(['status'=>0]);
        }
    }

    /**
     * 返回错误响应信息
     * @param $code
     * @param $status
     */
    private function response_error($code = 0, $status = 0)
    {
        $this->data['status'] = $status;
        if ($status == 0) {
            $this->data['errorCode'] = $code;
        }
        $this->data['errorMess'] = $this->error_info[$code];
        http_response($this->data);
    }
}
