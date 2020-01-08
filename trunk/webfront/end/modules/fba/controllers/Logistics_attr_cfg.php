<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FBA备货关系配置表
 *
 * @author Manson
 * @since 2019-03-05
 */
class Logistics_attr_cfg extends MY_Controller
{

    private $_server_module = 'fba/Logistics_attr_cfg/';

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 列表页
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/getLogisticsList
     */
    public function getLogisticsList()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!empty($get['isExcel'])) {
            $file_path = './end/upload/FBA_logistics_attr_temp.csv';
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
//            if (empty($get['limit'])) {
                //limit为空就是要导出全部
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
                            if(!empty($get['id'])){//勾选最多支持2000
                                export_csv($file_path, 'FBA物流属性配置导出');
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
                        export_csv($file_path, 'FBA物流属性配置导出');
                    }
                }
                //循环结束导出文件
                export_csv($file_path, 'FBA物流属性配置导出');

            }
//            else {
//                //有传limit导出
//                $result = $this->_curl_request->cloud_get($api_name, $get);
//                $date = date('YmdHis');
//
//                $fp = export_head('FBA物流属性配置导出-' . $date, $result['data_list']['key']);
//                $data = $this->character($result);
//
//                export_content($fp, $data);
//                exit();
//            }

         else {
            //正常访问列表
            $result = $this->_curl_request->cloud_get($api_name, $get);
//            pr($this->rsp_package($result));exit;
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

                $data[$key][] = $item + 1;
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sale_group_zh_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['salesman_zh_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['account_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['approve_state_cn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['fnsku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['asin']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['seller_sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['station_code_cn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['listing_state_cn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['logistics_id_cn']);
                $value['created_at'] = $value['created_at'] . "\t";  //解决时间显示#####
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['created_at']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['updated_zh_name']) . ' ' . iconv("UTF-8", "GB2312//IGNORE", $value['updated_at']); //修改信息
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['approve_zh_name']) . ' ' . iconv("UTF-8", "GB2312//IGNORE", $value['approve_at']);      //审核信息
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['remark']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['id']);
                $item++;
            }
        }
        return $data;
    }

    /**
     * 预览详情页
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/getLogisticsDetails
     */
    public function getLogisticsDetails()
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
     * 日志列表
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/getLogList
     */
    public function getLogList()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (empty($get['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 修改
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/modify
     */
    public function modify()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post     = $this->input->post();
        $post = $this->validationData($post);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
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
        $modify_field = ['id','logistics_id','purchase_warehouse_id','listing_state','rule_type','expand_factor','as_up','ls','pt','bs','sc','sz'];
        foreach ($post as $key => $value){
            if(!in_array($key,$modify_field)){
                unset($post[$key]);
            }
        }

        if (!positiveInteger($post['expand_factor'],2)){
            http_response(['status' => 0, 'errorMess' => '扩销系数只允许填正数']);
        }
        if (!positiveInteger($post['as_up'],2)){
            http_response(['status' => 0, 'errorMess' => '上架时效只允许填正数']);
        }
        if (!positiveInteger($post['ls'],2)){
            http_response(['status' => 0, 'errorMess' => '物流时效只允许填正数']);
        }
        if (!positiveInteger($post['pt'],2)){
            http_response(['status' => 0, 'errorMess' => '打包时效(PT)']);
        }
        if (!positiveInteger($post['bs'],2)){
            http_response(['status' => 0, 'errorMess' => '缓冲库存(BS)']);
        }
        if (!positiveInteger($post['sc'],2)){
            http_response(['status' => 0, 'errorMess' => '一次备货天数(SC)']);
        }
        if (!positiveInteger($post['sz'],2)){
            http_response(['status' => 0, 'errorMess' => '服务对应"Z"值填正数']);
        }


        return $post;
    }

    /**
     * 备注
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/addRemark
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
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/batchCheckSuccess
     */
    public function batchCheckSuccess()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['id']) || empty($post['user_id']) || empty($post['user_name'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量审核失败
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/batchCheckFail
     */
    public function batchCheckFail()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['id']) || empty($post['user_id']) || empty($post['user_name'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 默认物流配置页面
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/defaultDetail
     */
    public function defaultDetail()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_post($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 默认物流配置修改
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/defaultModify
     */
    public function defaultModify()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['user_id']) || empty($post['user_name']) || empty($post['logistics_id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 默认物流配置日志列表
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/getDefaultLogList
     */
    public function getDefaultLogList()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
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
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/uploadExcel
     */
    public function uploadExcel1()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
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
        foreach (LISTING_STATE as $key =>$value){
            $listing_state[$value['name']] = $key;
        }
        //物流属性状态映射
        foreach (FBA_LOGISTICS_ATTR as $key =>$value){
            $logistic_list[$value['name']] = $key;
        }
        //采购仓库状态映射
        foreach (PURCHASE_WAREHOUSE as $key =>$value){
            $purchase_warehouse_map[$value['name']] = $key;
        }
        //规则映射
        foreach (RULE_TYPE as $key =>$value){
            $rule_type_map[$value['name']] = $key;
        }

        $modify_item = [
            'listing_state' => 'Listing状态',
            'logistics_id' => '物流属性',
            'purchase_warehouse_id' => '采购仓库',
            'rule_type' => '规则',
            'expand_factor' => '扩销系数',
            'as_up' => '上架时效(AS)',
            'ls' => '物流时效(LS)',
            'pt' => '打包时效(PT)',
            'bs' => '缓冲库存(BS)',
            'sc' => '一次备货天数(SC)',
            'sz' => '服务对应"Z"值',
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
//                if($n > 19){
//                    continue;
//                }
                $v = explode(',', trim($string));
                $row = [];

                foreach ($final_list as $key => $val) {
                    if($key == 'listing_state'){
                        $row[$key] = $listing_state[$v[$val]]??'';
                    }elseif ($key == 'logistics_id'){
                        $row[$key] = $logistic_list[$v[$val]]??'';
                    }elseif ($key == 'purchase_warehouse_id'){
                        $row[$key] = $purchase_warehouse_map[$v[$val]]??'';
                    }elseif ($key == 'rule_type'){
                        $row[$key] = $rule_type_map[$v[$val]]??'';
                    }elseif($key == 'id'){
                        $row[$key] = is_numeric($v[$val]) ? $v[$val] : '';
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
//pr($insertRows);exit;
            $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);

            if (!isset($result['status']) || $result['status']==0) {
                $result['status']=0;
                http_response($result);
            } elseif (isset($result['status']) && $result['status'] == 1) {
                $processed += $result['data_list']['processed'];
                $undisposed += $result['data_list']['undisposed'];
            }
        }
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));

    }

    /**
     *Notes:批量修改导入
     *User: lewei
     *Date: 2019/11/28
     *Time: 20:16
     */
    public function uploadExcelxxxx()
    {
        set_time_limit(0);
        ini_set('memory_limit', '512M');
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

        //listing状态映射
        foreach (LISTING_STATE as $key =>$value){
            $listing_state[$value['name']] = $key;
        }
        //物流属性状态映射
        foreach (FBA_LOGISTICS_ATTR as $key =>$value){
            $logistic_list[$value['name']] = $key;
        }
        //采购仓库状态映射
        foreach (PURCHASE_WAREHOUSE as $key =>$value){
            $purchase_warehouse_map[$value['name']] = $key;
        }
        //规则映射
        foreach (RULE_TYPE as $key =>$value){
            $rule_type_map[$value['name']] = $key;
        }

        $modify_item = [
//            'listing_state' => 'Listing状态',
            'logistics_id' => '物流属性',
            'purchase_warehouse_id' => '采购仓库',
            'rule_type' => '规则',
            'expand_factor' => '扩销系数',
            'as_up' => '上架时效(AS)',
            'ls' => '物流时效(LS)',
            'pt' => '打包时效(PT)',
            'bs' => '缓冲库存(BS)',
            'sc' => '一次备货天数(SC)',
            'sz' => '服务对应"Z"值',
            'id' => '请勿改动此标记'
        ];
        $this->load->helper('common');
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        $total = 0;
        $data = [];
        $title = [];
        $final_list = [];
        foreach(yieldBigFile($filename) as $line) {
            $total ++;
            if ($total == 1){
                unset($data[0]);
                $title = validate_title($line,$modify_item);
                if ($title['status']==0){
                    $this->data['status'] = 0;
                    $this->data['errorMess'] = $title['errorMess'];
                    http_response($this->data);
                }else{
                    $final_list = $title['final_list'];
                }
            }else{
                //读取文件内容为可上传数据
                $string = iconv($title['char'], 'utf-8//IGNORE', trim(strip_tags($line)));//转码
                $v = explode(',', trim($string));
                $row = [];
                foreach ($final_list as $key => $val) {
                    if($key == 'listing_state'){
                        $row[$key] = $listing_state[$v[$val]]??'';
                    }elseif ($key == 'logistics_id'){
                        $row[$key] = $logistic_list[$v[$val]]??'';
                    }elseif ($key == 'purchase_warehouse_id'){
                        $row[$key] = $purchase_warehouse_map[$v[$val]]??'';
                    }elseif ($key == 'rule_type'){
                        $row[$key] = $rule_type_map[$v[$val]]??'';
                    }elseif($key == 'id'){
                        $row[$key] = is_numeric($v[$val]) ? $v[$val] : '';
                    }
                }
                $data[$row['id']] = $row;
            }

            //每500组提交一次
            if ($total > 0 && count($data) % 500 == 0 && !empty($data)){
                $post['data_values'] = json_encode($data);//批量将sql插入数据库。
                $result = $this->_curl_request->cloud_post($api_name, $post);
                if (!isset($result['status']) || $result['status']==0) {
                    $result['status']=0;
                    http_response($result);
                } elseif (isset($result['status']) && $result['status'] == 1) {
                    $processed += $result['data_list']['processed'];
                    $undisposed += $result['data_list']['undisposed'];
                }
                //释放内存
                $data = [];
                unset($post);
            }
        }
        //还有剩余数据需要提交
        if (!empty($data)){
            //post 请求
            $post['data_values'] = json_encode($data);//批量将sql插入数据库。
            $result = $this->_curl_request->cloud_post($api_name, $post);
            if (!isset($result['status']) || $result['status']==0) {
                $result['status']=0;
                http_response($result);
            } elseif (isset($result['status']) && $result['status'] == 1) {
                $processed += $result['data_list']['processed'];
                $undisposed += $result['data_list']['undisposed'];
            }
        }
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));

    }

    /**
     * 导入新增或更新
     */
    public function uploadExcel()
    {
        $update_require_cols = [
            'logistics_id'          => '物流属性',
            'purchase_warehouse_id' => '采购仓库',
            'rule_type'             => '规则',
            'expand_factor'         => '扩销系数',
            'as_up'                 => '上架时效(AS)',
            'ls'                    => '物流时效(LS)',
            'pt'                    => '打包时效(PT)',
            'bs'                    => '缓冲库存(BS)',
            'sc'                    => '一次备货天数(SC)',
            'sz'                    => '服务对应"Z"值',
            'id'                    => '请勿改动此标记'
        ];

        //中文转id
        $listing_state = $logistic_list = $purchase_warehouse_map = $rule_type_map = [];

        //listing状态映射
        foreach (LISTING_STATE as $key =>$value){
            $listing_state[$value['name']] = $key;
        }
        //物流属性状态映射
        foreach (FBA_LOGISTICS_ATTR as $key =>$value){
            $logistic_list[$value['name']] = $key;
        }
        //采购仓库状态映射
        foreach (PURCHASE_WAREHOUSE as $key =>$value){
            $purchase_warehouse_map[$value['name']] = $key;
        }
        //规则映射
        foreach (RULE_TYPE as $key =>$value){
            $rule_type_map[$value['name']] = $key;
        }

        $bind_required_cols_callback = [
            //物流属性
            'logistics_id' => function($col, &$line, $actual_col_position) use ($logistic_list) {
                $logistics_name = $line[$actual_col_position[$col]] ?? '';
                if (empty($logistics_name) || !isset($logistic_list[$logistics_name]))
                {
                    return false;
                }
                $line[$actual_col_position[$col]] = $logistic_list[$logistics_name];
                return true;
            },
            //listing状态
            'listing_state' => function($col, &$line, $actual_col_position) use ($listing_state) {
                $listing_name = $line[$actual_col_position[$col]] ?? '';
                if (empty($listing_name) || !isset($listing_state[$listing_name]))
                {
                    return false;
                }
                $line[$actual_col_position[$col]] = $listing_state[$listing_name];
                return true;
             },
             //采购仓库
             'purchase_warehouse_id' => function($col, &$line, $actual_col_position) use ($purchase_warehouse_map) {
                 $warehouse_name = $line[$actual_col_position[$col]] ?? '';
                 if (empty($warehouse_name) || !isset($purchase_warehouse_map[$warehouse_name]))
                 {
                     return false;
                 }
                 $line[$actual_col_position[$col]] = $purchase_warehouse_map[$warehouse_name];
                 return true;
             },
             //规则
             'rule_type' => function($col, &$line, $actual_col_position) use ($rule_type_map) {
                 $rule_name = $line[$actual_col_position[$col]] ?? '';
                 if (empty($rule_name) || !isset($rule_type_map[$rule_name]))
                 {
                     return false;
                 }
                 $line[$actual_col_position[$col]] = $rule_type_map[$rule_name];
                 return true;
             },

            ];
        $parse_error_tips = [
                'unknown'               => '无法识别内容，无法处理',
                'repeat'                => '该记录与前面记录发生重复，被忽略。',
                'logistics_id'          => '物流属性填写错误',
                'listing_state'         => 'listing状态填写错误',
                'purchase_warehouse_id' => '采购仓库填写错误',
                'rule_type'             => '规则填写错误'
        ];

        $api_name = 'import';
        $api_name = $this->_server_module.$api_name;
        $curl = $this->_curl_request;

        //改功能暂无新增功能
        $insert_require_cols = [];

        $this->load->library('CsvReader');

        $this->csvreader
        ->bind_required_cols_callback($bind_required_cols_callback, null)
        ->bind_parse_error_tips($parse_error_tips)
        ->check_mode(
            function($title) {
                return in_array('请勿改动此标记', $title) ? 'update' : 'insert';
            },
            //没有导入
            function($csvReader, $mode) use ($insert_require_cols, $update_require_cols) {
                if ($mode == 'insert') {
                    log_message('ERROR', 'SellerSKU属性批量修改暂无新增功能');
                    return false;
                } else {
                    //如果有Listing状态则加载
                    $csvReader->set_rule($update_require_cols, 'id', []);
                }
                return true;
            }
            )
            ->set_request_handler(
                function($post) use ($api_name, $curl){
                    return $curl->cloud_post($api_name, $post);
                });
            $this->csvreader->run();

            http_response(['status' => 1, 'data_list' => $this->csvreader->get_report(true)]);
    }


    /**
     * 新导出功能
     */
    public function export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'SELLERSKU属性配置表'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
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


    /**
     * 批量修改导入
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/uploadExcel
     */
    public function update_logistics()
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
                'A'=>'sale_group_zh_name',
                'B'=>'account_name',
                'C'=>'seller_sku',
                'D'=>'original_sku',
            ];
            $excelData = getExcelDetail($file, 2, $title,1);
        }
        foreach ($excelData as $key => $item){
            $tag = sprintf('%s_%s_%s_%s',$item['sale_group_zh_name'],$item['account_name'],$item['seller_sku'],$item['original_sku']);
            $arr[$tag] = 1;
        }
//file_put_contents('./海运散货的数据.txt',json_encode($arr));exit;
//pr($excelData);exit;

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

    public function unmatch_seller_sku_export(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '未匹配到的seller_sku'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }

    /**
     * 相关列修改
     * http://192.168.71.170:1083/fba/Logistics_attr_cfg/update_column
     */
    public function update_column()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['column']) || empty($post['column_value'])) {
            $this->response_error('3001');
        }
        if(empty(SELLER_SKU_MODIFY_COLUMN[$post['column']]))
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
        foreach (SELLER_SKU_MODIFY_COLUMN as $key =>$value){
            $updat_column[$key] = $value['name'];
        }
        $this->data['data']['update_column'] = $updat_column;
        http_response($this->rsp_package($this->data));
    }

    public function export_listing_state()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'sellsersku属性配置表_设置listing状态'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }

    /**
     * 导入新增或更新
     */
    public function import_listing_state()
    {
        //中文转id
        $listing_state = $site_map = $pan_eu= [];

        //站点映射
        foreach (FBA_STATION_CODE as $key =>$value){
            $site_map[$value['name']] = $key;
        }
        //listing状态映射
        foreach (LISTING_STATE as $key =>$value){
            $listing_state[$value['name']] = $key;
        }
        //是否范欧
        foreach (IS_PAN_EU as $key =>$value){
            $pan_eu[$value['name']] = $key;
        }

        $bind_required_cols_callback = [
            //站点映射
            'site' => function($col, &$line, $actual_col_position) use ($site_map) {
                $site_name = $line[$actual_col_position[$col]] ?? '';
                if (empty($site_map) || !isset($site_map[$site_name]))
                {
                    return false;
                }
                $line[$actual_col_position[$col]] = $site_map[$site_name];
                return true;
            },
            //listing状态
            'listing_state' => function($col, &$line, $actual_col_position) use ($listing_state) {
                $listing_name = $line[$actual_col_position[$col]] ?? '';
                if (empty($listing_name) || !isset($listing_state[$listing_name]))
                {
                    return false;
                }
                $line[$actual_col_position[$col]] = $listing_state[$listing_name];
                return true;
            },
            //是否泛欧
            'pan_eu' => function($col, &$line, $actual_col_position) use ($pan_eu) {
                $pan_eu_name = $line[$actual_col_position[$col]] ?? '';
                if (empty($pan_eu_name) || !isset($pan_eu[$pan_eu_name]))
                {
                    return false;
                }
                $line[$actual_col_position[$col]] = $pan_eu[$pan_eu_name];
                return true;
            },

        ];
        $parse_error_tips = [
            'unknown'               => '无法识别内容，无法处理',
            'repeat'                => '该记录与前面记录发生重复，被忽略。',
            'site_map'          => '站点填写错误',
            'listing_state'         => 'listing状态填写错误',
        ];

        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $curl = $this->_curl_request;

        //改功能暂无新增功能
        $insert_require_cols = [
        ];

        $update_require_cols = [
            'pan_eu' => '是否泛欧',
            'site' => '站点',
            'listing_state' => 'listing状态明细',
            'id' => '请勿改动此标记'
        ];
        $this->load->library('CsvReader');
        $this->csvreader
            ->bind_required_cols_callback($bind_required_cols_callback, null)
            ->bind_parse_error_tips($parse_error_tips)
            ->check_mode(
                function($title) {
                    return in_array('请勿改动此标记', $title) ? 'update_more' : 'insert';
                },
                //没有导入
                function($csvReader, $mode) use ($insert_require_cols, $update_require_cols) {
                    if ($mode == 'insert') {
                        log_message('ERROR', '暂无新增功能');
                        return false;
                    } else {
                        //如果有Listing状态则加载
                        $csvReader->set_rule($update_require_cols, 'id', ['listing_state' => 'Listing状态']);
                    }
                    return true;
                }
            )
            ->set_request_handler(
                function($post) use ($api_name, $curl){
                    return $curl->cloud_post($api_name, $post);
                });
        $this->csvreader->run();

        http_response(['status' => 1, 'data_list' => $this->csvreader->get_report(true)]);
    }
}
/* End of file Logistics_attr_cfg.php */
/* Location: ./application/modules/fba/controllers/Logistics_attr_cfg.php */