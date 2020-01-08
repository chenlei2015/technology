<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内销量运算配置表
 */
class Stock_relationship_cfg extends MY_Controller {

    private $_server_module = 'inland/Stock_relationship_cfg/';

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['stock_lang']);
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
            tran_stock_cfg_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
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

        if(!isset($post['set_start_date']) || !isset($post['set_end_date']) || !isset($post['platform_code'])){
            $this->data['errorMess'] = '参数不允许为空';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量
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
     * 批量审核成功
     */
    public function batchCheckSuccess()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(!isset($post['gid']) || empty($post['gid'])){
            $this->data['errorMess'] = '请勾选后再批量审核';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量审核失败
     */
    public function batchCheckFail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if(!isset($post['gid']) || empty($post['gid'])){
            $this->data['errorMess'] = '请勾选后再批量审核';
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

        if(!isset($post['gid']) || !isset($post['rule_type']) || !isset($post['stock_way']) || !isset($post['bs']) ||
            !isset($post['sp']) || !isset($post['shipment_time']) || !isset($post['first_lt']) || !isset($post['sc']) ||
            !isset($post['sz']) || !isset($post['reduce_factor']) || !isset($post['max_safe_stock_day']) || !isset($post['refund_rate'])
        ){
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

        if ($result['status'] == 1 && !empty($result['data']['cfg']))
        {
            tran_stock_cfg_detail_result($result['data']['cfg']);
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
    public function stock_cfg_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内备货关系配置表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
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

    public function uploadExcel()
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
        }elseif ($line > 50001) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '导入的数据不能超出5万条';
            http_response($this->data);
        }
        $content = trim(file_get_contents($filename));
        $excelData = [];
        $excelData = explode("\n", $content);
        $chunkData = array_chunk($excelData, 5000); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        $insertRows = array();

        for ($i = 0; $i < $count; $i++) {

            foreach ($chunkData[$i] as $value) {
                    $string = iconv($char, 'utf-8//IGNORE', trim(strip_tags($value)));//转码

                    $n = substr_count($string,',');
                    if($n != 28){//控制最大列
                        $undisposed++;
                        continue;
                    }
                    $v = explode(',', trim($string));
//                    print_r($v);exit;
                    $row = array();
                    if ($v[0] == '自定义') {
                        $v[0] = 1;
                    } elseif ($v[0] == '全局') {
                        $v[0] = 2;
                    } else {
                        $v[0] = '';
                    }

                    if ($v[10] == '正常备货') {
                        $v[10] = 1;
                    } elseif ($v[10] == '出单补货') {
                        $v[10] = 2;
                    } elseif ($v[10] == '不备货') {
                        $v[10] = 3;
                    } else {
                        $v[10] = '';
                    }
                    $row['rule_type'] = $v[0];
                    $row['stock_way'] = is_numeric($v[10]) ? $v[10] : '';
                    $row['bs'] = is_numeric($v[11]) ? $v[11] : '';
                    $row['max_safe_stock_day'] = is_numeric($v[12]) ? $v[12] : '';
                    $row['sp'] = is_numeric($v[13]) ? $v[13] : '';
                    $row['shipment_time'] = is_numeric($v[14]) ? $v[14] : '';
                    $row['first_lt'] = is_numeric($v[15]) ? $v[15] : '';
                    $row['sc'] = is_numeric($v[16]) ? $v[16] : '';
                    $row['reduce_factor'] = is_numeric($v[17]) ? $v[17] : '';
                    $row['sz'] = is_numeric($v[18]) ? $v[18] : '';
                    $row['gid'] = trim(str_replace('"', '', $v[27]))??'';
                    $row['refund_rate'] = number_format(floatval($v[28]), 2);

                    $insertRows[] = $row;

                }
                    $post['data_values'] = json_encode($insertRows);//批量将sql插入数据库。
                    $result = $this->_curl_request->cloud_post($api_name, $post);
                    $insertRows = [];
                    $processed += $result['data_list']['processed'];
                    $undisposed += $result['data_list']['undisposed'];

            }

        $undisposed -= 1;  //减去第一行未处理
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->rsp_package($this->data));
    }

}