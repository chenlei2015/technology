<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 海外仓备货关系配置表
 *
 * @author Manson
 * @since 2019-03-05
 */
class Stock_relationship_cfg extends MY_Controller
{

    private $_server_module = 'oversea/Stock_relationship_cfg/';

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 列表页
     * http://192.168.71.170:1083/oversea/Stock_relationship_cfg/getStockList
     */
    public function getStockList()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!empty($get['isExcel'])) {
            $file_path = './end/upload/Oversea_stock_relationship_temp.csv';
            if(file_exists($file_path)){
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
//            if (empty($get['limit'])) {
                //limit为空就是要导出全部
                $length = 500;
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
//                            print_r($result);exit;
                            fputcsv($fp, $title);
                            //转码
                            $data = $this->character($result, $start);
                            unset($result);
                            //写入内容
                            export_content($fp, $data);
                            fclose($fp);
                            if(!empty($get['gid'])){//勾选最多支持2000
                                export_csv($file_path, '海外仓备货关系配置导出');
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
                        $start += 500;

                    } else {//查不出结果了 导出
                        export_csv($file_path, '海外仓备货关系配置导出');
                    }
                }
                //循环结束导出文件
                export_csv($file_path, '海外仓备货关系配置导出');

//            }
//            else {
//                //有传limit导出
//                $result = $this->_curl_request->cloud_get($api_name, $get);
//                $date = date('YmdHis');
//
//                $fp = export_head('海外仓备货关系配置导出-' . $date, $result['data_list']['key']);
//                $data = $this->character($result);
//
//                export_content($fp, $data);
//                exit();
//            }

        } else {
            //正常访问列表
            $result = $this->_curl_request->cloud_get($api_name, $get);
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
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['rule_type_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['check_state_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['station_code_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['is_refund_tax_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['purchase_warehouse_cn']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_state_text']);
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
                $value['gid'] = $value['gid'] . "\t";  //解决excel打开文件显示科学计数法
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['gid']);
                $item++;
            }
        }
        return $data;
    }

    /**
     * 预览详情页
     * http://192.168.71.170:1083/oversea/Stock_relationship_cfg/getStockDetails
     */
    public function getStockDetails()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (empty($get['gid'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 日志列表
     * http://192.168.71.170:1083/oversea/Stock_relationship_cfg/getStockLogList
     */
    public function getStockLogList()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (empty($get['gid'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 修改
     * http://192.168.71.170:1083/oversea/Stock_relationship_cfg/modifyStock
     */
    public function modifyStock()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['gid']) || empty($post['user_id']) || empty($post['rule_type'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 添加备注
     * http://192.168.71.170:1083/oversea/Stock_relationship_cfg/addRemark
     */
    public function addRemark()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['gid']) || empty($post['remark']) || empty($post['user_id']) || empty($post['user_name'])) {
            $this->response_error('3001');
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
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
     * 批量审核成功
     * http://192.168.71.170:1084/oversea/stock_relationship_cfg/batchCheckSuccess
     */
    public function batchCheckSuccess()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['gid']) || empty($post['user_id'])) {
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
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['gid']) || empty($post['user_id'])) {
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
     * 批量导入修改
     * http://192.168.71.170:1083/oversea/Stock_relationship_cfg/uploadExcel
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
                if($n != 35){//控制最大列
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
                $row['ls_land'] = is_numeric($v[13]) ? $v[13] : '';
                $row['ls_blue'] = is_numeric($v[14]) ? $v[14] : '';
                $row['ls_red'] = is_numeric($v[15]) ? $v[15] : '';
                $row['pt_air'] = is_numeric($v[16]) ? $v[16] : '';
                $row['pt_shipping_bulk'] = is_numeric($v[17]) ? $v[17] : '';
                $row['pt_shipping_full'] = is_numeric($v[18]) ? $v[18] : '';
                $row['pt_trains_bulk'] = is_numeric($v[19]) ? $v[19] : '';
                $row['pt_trains_full'] = is_numeric($v[20]) ? $v[20] : '';
                $row['pt_land'] = is_numeric($v[21]) ? $v[21] : '';
                $row['pt_blue'] = is_numeric($v[22]) ? $v[22] : '';
                $row['pt_red'] = is_numeric($v[23]) ? $v[23] : '';
                $row['bs'] = is_numeric($v[24]) ? $v[24] : '';
                $row['sp'] = is_numeric($v[26]) ? $v[26] : '';
                $row['sc'] = is_numeric($v[27]) ? $v[27] : '';
                $row['sz'] = is_numeric($v[28]) ? $v[28] : '';
                $row['gid'] = trim(str_replace('"', '', $v[35]))??'';
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

//        $handle = fopen($filename, 'r');
//        $result = input_csv($handle); //解析csv
////        print_r($result);exit;
//        $len_result = count($result);
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
//            'pt_ship' => 12, 'pt_train' => 13, 'pt_air' => 14, 'pt_blue' => 15, 'pt_red' => 16, 'bs' => 17, 'sp' => 19, 'sc' => 20, 'sz' => 21, 'gid' => 26];
//
////        print_r($len_result);exit;
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
//                }elseif ($key == 'gid') {
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

    public function export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '海外_备货关系配置表'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }
    /**
     * 批量导入修改
     * http://192.168.71.170:1083/oversea/Stock_relationship_cfg/uploadExcel
     */
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
            'rule_type' => '规则',
            'original_min_start_amount' => '供应商最小起订金额1',
            'min_start_amount' => '供应商最小起订金额2',
            'as_up' => '上架时效(AS)',
            'ls_air' => '物流时效LS_空运',
            'ls_shipping_bulk' => '物流时效LS_海运散货',
            'ls_shipping_full' => '物流时效LS_海运整柜',
            'ls_trains_bulk' => '物流时效LS_铁运散货',
            'ls_trains_full' => '物流时效LS_铁运整柜',
            'ls_land' => '物流时效LS_陆运',
            'ls_blue' => '物流时效LS_蓝单',
            'ls_red' => '物流时效LS_红单',
            'pt_air' => '打包时效PT_空运',
            'pt_shipping_bulk' => '打包时效PT_海运散货',
            'pt_shipping_full' => '打包时效PT_海运整柜',
            'pt_trains_bulk' => '打包时效PT_铁运散货',
            'pt_trains_full' => '打包时效PT_铁运整柜',
            'pt_land' => '打包时效PT_陆运',
            'pt_blue' => '打包时效PT_蓝单',
            'pt_red' => '打包时效PT_红单',
            'bs' => '缓冲库存(BS)',
            'sp' => '备货处理周期(SP)',
            'sc' => '一次备货天数(SC)',
            'sz' => '服务对应"Z"值',
            'gid' => '请勿改动此标记'
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
            foreach ($chunkData[$i] as $value) {
                $string = iconv($result['char'], 'utf-8//IGNORE', trim(strip_tags($value)));//转码
//                $n = substr_count($string,',');
//                if($n > 35){//控制最大列
//                    continue;
//                }
                $v = explode(',', trim($string));
                $row = [];
                foreach ($final_list as $key => $val){
                    if($key == 'rule_type'){
                        if ($v[$val] == '自定义') {
                            $row[$key] = 1;
                        } elseif ($v[$val] == '全局') {
                            $row[$key] = 2;
                        } else {
                            $row[$key] = '';
                        }
                    }elseif($key == 'gid'){
                        $row[$key] = trim(str_replace('"', '', $v[$val]))??'';
                    }else{
                        $row[$key] = is_numeric($v[$val]) ? $v[$val] : '';
                    }
                }
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
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
/* Location: ./application/modules/oversea/controllers/Stock_relationship_cfg.php */
