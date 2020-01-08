<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FBA备货关系配置表
 *
 * @author Manson
 * @since 2019-03-05
 */
class Stock_relationship_cfg extends MY_Controller
{

    private $_server_module = 'fba/Stock_relationship_cfg/';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('fba_helper');
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 列表页
     * http://192.168.71.170:1083/fba/Stock_relationship_cfg/getStockList
     */
    public function getStockList()
    {
        //$api_name = 'fba/Sku_cfg/List';        //v1.2.2版本改动
        $api_name = $this->_server_module . strtolower(__FUNCTION__);

        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_erp_sku_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
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
                $data[$key][] = $item + 1;
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['rule_type_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['check_state_cn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['station_code_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['is_refund_tax_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['purchase_warehouse_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sale_state']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['as_up']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_ship']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_train']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_air']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_blue']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_red']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_ship']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_train']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_air']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_blue']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_red']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['bs']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['lt']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sp']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sc']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sz']);
                $value['created_at'] = $value['created_at'] . "\t";  //解决时间显示#####
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['created_at']);  //创建信息
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['updated_zh_name']) . ' ' . iconv("UTF-8", "GB2312//IGNORE", $value['updated_at']); //修改信息
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['approved_zh_name']) . ' ' . iconv("UTF-8", "GB2312//IGNORE", $value['approved_at']);      //审核信息
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['remark']);
                $value['id'] = $value['id'] . "\t";  //解决excel打开文件显示科学计数法
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['id']);
                $item++;
            }
        }

        return $data;
    }


    /**
     * 预览详情页
     * http://192.168.71.170:1083/fba/Stock_relationship_cfg/getStockDetails
     */
    public function getStockDetails()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (empty($get['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_erp_sku_detail_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 日志列表
     * http://192.168.71.170:1083/fba/Stock_relationship_cfg/getStockLogList
     */
    public function getStockLogList()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (empty($get['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 修改
     * http://192.168.71.170:1083/fba/Stock_relationship_cfg/modifyStock
     */
    public function modifyStock()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post     = $this->input->post();
        $post = $this->validationData($post);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 添加备注
     * http://192.168.71.170:1083/fba/Stock_relationship_cfg/addRemark
     */
    public function addRemark()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['id']) || empty($post['remark']) || empty($post['user_id']) || empty($post['user_name'])) {
            $this->response_error('3001');
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量审核成功
     * http://192.168 .71.170:1083/fba/stock_relationship_cfg/batchCheckSuccess
     */
    public function batchCheckSuccess()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['id']) || empty($post['user_id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量审核失败
     * http://192.168.71.170:1083/fba/Stock_relationship_cfg/batchCheckFail
     */
    public function batchCheckFail()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['id']) || empty($post['user_id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
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
     * http://192.168.71.170:1083/fba/Stock_relationship_cfg/uploadExcel
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
            $chunkData = array_chunk($excelData, 2000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
            $count = count($chunkData);
            $processed = 0;//已处理
            $undisposed = 0;//未处理
            for ($i = 0; $i < $count; $i++) {
                $insertRows = array();
                foreach ($chunkData[$i] as $value) {
                    $string = iconv($char, 'utf-8//IGNORE', trim(strip_tags($value)));//转码
                    $n = substr_count($string,',');
                    if($n != 33){//控制最大列
                        continue;
                    }
                    $v = explode(',', trim($string));
                    $row = array();
                    if ($v[0] == '自定义') {
                        $v[0] = 1;
                    } elseif ($v[0] == '全局') {
                        $v[0] = 2;
                    } else {
                        $v[0] = '';
                    }
                    $row['rule_type'] = $v[0];
                    $row['as_up'] = is_numeric($v[7]) ? $v[7] : '';
                    $row['ls_air'] = is_numeric($v[8]) ? $v[8] : '';
                    $row['ls_shipping_bulk'] = is_numeric($v[9]) ? $v[9] : '';
                    $row['ls_shipping_full'] = is_numeric($v[10]) ? $v[10] : '';
                    $row['ls_trains_bulk'] = is_numeric($v[11]) ? $v[11] : '';
                    $row['ls_trains_full'] = is_numeric($v[12]) ? $v[12] : '';
                    $row['ls_blue'] = is_numeric($v[13]) ? $v[13] : '';
                    $row['ls_red'] = is_numeric($v[14]) ? $v[14] : '';
                    $row['pt_air'] = is_numeric($v[15]) ? $v[15] : '';
                    $row['pt_shipping_bulk'] = is_numeric($v[16]) ? $v[16] : '';
                    $row['pt_shipping_full'] = is_numeric($v[17]) ? $v[17] : '';
                    $row['pt_trains_bulk'] = is_numeric($v[18]) ? $v[18] : '';
                    $row['pt_trains_full'] = is_numeric($v[19]) ? $v[19] : '';
                    $row['pt_blue'] = is_numeric($v[20]) ? $v[20] : '';
                    $row['pt_red'] = is_numeric($v[21]) ? $v[21] : '';
                    $row['bs'] = is_numeric($v[22]) ? $v[22] : '';
                    $row['sp'] = is_numeric($v[24]) ? $v[24] : '';
                    $row['sc'] = is_numeric($v[25]) ? $v[25] : '';
                    $row['sz'] = is_numeric($v[26]) ? $v[26] : '';
                    $row['id'] = trim(str_replace('"', '', $v[33]))??'';

                    $insertRows[] = $row;
                }
    //            print_r($insertRows);
    //            exit;
                $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
                $result = $this->_curl_request->cloud_post($api_name, $post);
    //            print_r($result);exit;
                if($result['status'] == 0){
                    http_response($result);
                }elseif ($result['status'] == 1){
                    $processed += $result['data_list']['processed'];
                    $undisposed += $result['data_list']['undisposed'];
                }
            }
            $undisposed -= 1;  //减去第一行未处理
            $this->data['status'] = 1;
            $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
            http_response($this->rsp_package($this->data));

    //        $result = input_csv($handle); //解析csv
    //        $len_result = count(array_filter($result));
    //        if ($len_result < 2) {
    //            $this->data['status'] = 0;
    //            $this->data['errorMess'] = '导入的数据为空';
    //            http_response($this->data);
    //        } elseif ($len_result > 10001) {
    //            $this->data['status'] = 0;
    //            $this->data['errorMess'] = '导入的数据不能超出1万条';
    //            http_response($this->data);
    //        }
    //        $data_values = [];
    //        $number = ['rule_type_cn' => 1, 'as_up' => 6, 'ls_ship' => 7, 'ls_train' => 8, 'ls_air' => 9, 'ls_blue' => 10, 'ls_red' => 11,
    //            'pt_ship' => 12, 'pt_train' => 13, 'pt_air' => 14, 'pt_blue' => 15, 'pt_red' => 16, 'bs' => 17, 'sp' => 19, 'sc' => 20, 'sz' => 21, 'id' => 26];

    //        for ($i = 1; $i < $len_result; $i++) //循环获取各字段值
    //        {
    //            foreach ($number as $key => $value) {
    //                if ($key == 'rule_type_cn') {
    //                    $g = iconv("GB2312", "utf-8//IGNORE", $result[$i][$value]);
    //                    if ($g == '自定义') {
    //                        $data_values[$i]['rule_type'] = 1;
    //                    } elseif ($g == '全局') {
    //                        $data_values[$i]['rule_type'] = 2;
    //                    }
    //                } elseif ($key == 'id') {
    //                    $g = iconv("GB2312", "utf-8//IGNORE", $result[$i][$value]);
    //                    $data_values[$i][$key] = rtrim($g);//去除右空格
    //                } else {
    //                    $g = iconv("GB2312", "utf-8//IGNORE", $result[$i][$value]);
    //                    $data_values[$i][$key] = $g;
    //                }
    //            }
    //        }
    //        fclose($handle); //关闭指针
    //        try {
    //            $post['data_values'] = json_encode($data_values);
    //            $result = $this->_curl_request->cloud_post($api_name, $post);
    //            http_response($this->rsp_package($result));
    //        } catch (Exception $e) {
    //            echo $e;
    //        }
        }*/

    /**
     * 新导出功能
     * http://192.168.71.170:1083/fba/Stock_relationship_cfg/export
     */
//    public function export()
//    {
//        set_time_limit(-1);
//        ini_set('memory_limit', '-1');
//        ini_set('default_socket_timeout', 120);
//
//        //默认直接下载
//        $export_type = $get['file_type'] ?? 'csv';
//        $post = $this->input->post();
//        $api_name = $this->_server_module.strtolower(__FUNCTION__);
//        $result = $this->_curl_request->cloud_post($api_name, $post, $params = null, $is_josn = 0);
//        $file_name = 'FBA_备货关系配置表'.date('Ymd_H_i');
//        if (in_array($export_type, ['xlsx', 'xls']))
//        {
//            $this->_export_xls($result);
//        }
//        else
//        {
//            $this->_quick_export_csv($file_name, $result);
//        }
//    }

    public function export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'ERPSKU属性配置表'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }

    public function uploadExcel()
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
        $chunkData = array_chunk($excelData, 2000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理

        $modify_item = [
            'is_contraband' => '是否违禁',
            'max_sp' => '最大额外备货天数',
            'max_lt' => '最大供货周期',
            'max_safe_stock' => '最大安全库存天数',
            'sp' => '备货处理周期(SP)',
            'id' => '请勿改动此标记'
        ];

        //是否违禁
        foreach (CONTRABAND_STATE as $key =>$value){
            $contraband_map[$value['name']] = $key;
        }

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
            foreach ($chunkData[$i] as $value) {
                $string = iconv($result['char'], 'utf-8//IGNORE', trim(strip_tags($value)));//转码
//                $n = substr_count($string,',');
//                if($n > 33){//控制最大列
//                    continue;
//                }
                $v = explode(',', trim($string));
                $row = [];

                foreach ($final_list as $key => $val){
                    if($key == 'is_contraband'){
                        $row[$key] = $contraband_map[$v[$val]]??'';
                    }elseif($key == 'id'){
                        $row[$key] = trim(str_replace('"', '', $v[$val]))??'';
                    }else{
                        $row[$key] = is_numeric(trim($v[$val])) ? $v[$val] : '';
                    }
                }
                $insertRows[] = $row;
            }
            foreach ($insertRows as $key => $item){
                if (in_array('',$item)){
                    unset($insertRows[$key]);
                }
            }
            $insertRows = array_column($insertRows,NULL,'id');

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
//            print_r($result);exit;
            if($result['status'] == 0){
                http_response($result);
            }elseif ($result['status'] == 1){
                $processed += $result['data_list']['processed'];
                $undisposed += $result['data_list']['undisposed'];
            }
        }
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));
    }


    /**
     *
     * @param $post
     */
    private function validationData($post)
    {
        $this->lang->load('common');

        if (empty($post)){
            http_response(['status' => 0, 'errorMess' => $this->lang->myline('empty')]);
        }

        $modify_field = ['id','is_contraband','sp','max_sp','max_lt','max_safe_stock'];
        foreach ($post as $key => $value){
            if(!in_array($key,$modify_field)){
                unset($post[$key]);
            }
        }



        if (!positiveInteger($post['max_sp'],2)){
            http_response(['status' => 0, 'errorMess' => '最大备货天数只允许填正数']);
        }
        if (!positiveInteger($post['max_lt'],2)){
            http_response(['status' => 0, 'errorMess' => '最大供货周期只允许填正数']);
        }
        if (!positiveInteger($post['max_safe_stock'],2)){
            http_response(['status' => 0, 'errorMess' => '最大安全库存天数只允许填正数']);
        }
        if (!positiveInteger($post['sp'],2)){
            http_response(['status' => 0, 'errorMess' => '备货处理周期只允许填正数']);
        }

        return $post;
    }

    /**
     * 相关列修改
     * /fba/Stock_relationship_cfg/update_column
     */
    public function update_column()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['column']) || empty($post['column_value'])) {
            $this->response_error('3001');
        }
        $post['column'] = trim($post['column']);
        if(empty(ERP_SKU_MODIFY_COLUMN[$post['column']]))
        {
            http_response(['status' => 0, 'errorMess' => '不支持修改列']);
        }
        if(!preg_match("/^[1-9][0-9]*$/" ,$post['column_value'])) {
            http_response(['status' => 0, 'errorMess' => '请输入正整数']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    public function get_update_column()
    {
        $this->data['status'] = 1;
        $updat_column = [];
        foreach (ERP_SKU_MODIFY_COLUMN as $key =>$value){
            $updat_column[$key] = $value['name'];
        }
        $this->data['data']['update_column'] = $updat_column;
        http_response($this->data);
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
/* End of file Stock_relationship_cfg.php */
/* Location: ./application/modules/fba/controllers/Stock_relationship_cfg.php */