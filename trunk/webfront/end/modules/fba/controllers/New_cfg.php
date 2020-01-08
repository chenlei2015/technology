<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FBA活动配置
 *
 * @author zc
 *
 * @since 2019-10-22
 */
class New_cfg extends MY_Controller{
    private $_server_module = 'fba/New_cfg/';

    public function __construct()
    {
        parent::__construct();
        $this->error_info = $this->config->item('error_code');
        $this->lang->load(['pr_lang']);
        $this->load->helper('fba_helper');
    }

    /**
     * 列表
     */
    public function list()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value'])) {
            tran_new_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 导出
     */
    public function export()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $file_name = 'FBA新品_' . date('Ymd_H_i');
        $options = ['charset' => 'GBK', 'data_type' => VIEW_FILE];
        $this->common_export($api_name, $file_name, $options);
    }

    /**
     * 批量审核
     */
    public function batch_approve()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['id']) || !$post['id']) {
            http_response(['errorMess' => '请选择需要审核的记录']);
        }
        if (!isset($post['result']) || !in_array($post['result'], [NEW_APPROVAL_FAIL, NEW_APPROVAL_SUCCESS])) {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量审核全部未审核
     */
    public function batch_approve_all()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['result']) || !in_array($post['result'], [NEW_APPROVAL_FAIL, NEW_APPROVAL_SUCCESS])) {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 导入新增或更新
     */
    public function import()
    {
        set_time_limit(0);
        ini_set('memory_limit', '2048M');
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        $this->data['status'] = 0;
        if (empty($post['user_id']) || empty($post['user_name'])) {
            $this->data['errorMess'] = 'user id is null';
            http_response($this->data);
        }
        $filename = $_FILES['file']['tmp_name'];
        if (empty ($filename)) {
            $this->data['errorMess'] = '请选择要导入的CSV文件！';
            http_response($this->data);
        }
        fopen($filename, 'r');
        $line = count(file($filename));
        if ($line < 2) {
            $this->data['errorMess'] = '导入的数据为空';
            http_response($this->data);
        }
        if ($line > 10000) {
            $this->data['errorMess'] = '导入的数据不能超出1万条';
            http_response($this->data);
        }
        $content = trim(file_get_contents($filename));
        $excelData = [];
        $excelData = explode("\n", $content);
        $size = 10000;
        $chunkData = array_chunk($excelData, $size); // 将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        $count = count($chunkData);
        $processed = 0;//已处理
        $undisposed = 0;//未处理

        $modify_item = [
            'sale_group' => '销售分组',
            'account_name' => '销售账号',
            'staff_zh_name' => '销售名称',
            'site' => '站点',
            'seller_sku' => 'seller_sku',
            'erpsku' => 'erpsku',
            'fnsku' => 'fnsku',
            'asin' => 'asin',
            'demand_num' => 'quantity',//需求量
        ];

        $this->load->helper('common');
        $result = validate_title($chunkData[0][0], $modify_item);
        unset($chunkData[0][0]);
        if ($result['status'] == 0) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $result['errorMess'];
            http_response($this->data);
        } else {
            $final_list = $result['final_list'];
        }

        $err_info['errorLines'] = [];
        $line_num = 1;
        for ($i = 0; $i < $count; $i++) {
            $insertRows = [];
            foreach ($chunkData[$i] as $k => $value) {
                $line_num++;
                $string = iconv($result['char'], 'utf-8//IGNORE', trim(strip_tags($value)));//转码
                $v = explode(',', trim($string));
                if(count($v) < count($modify_item))
                {
                    array_push($err_info['errorLines'],$line_num);
                    continue;
                }
                $row = [];
                $site_lower = strtolower($v[$final_list['site']]);
                if(empty(FBA_STATION_CODE[$site_lower]))
                {
                    array_push($err_info['errorLines'],$line_num);
                    continue;
                }
                if($v[$final_list['demand_num']] < 1)
                {
                    array_push($err_info['errorLines'],$line_num);
                    continue;
                }
                if(preg_match('/^[+-]?[\d]+([\.][\d]+)?([Ee][+-]?[\d]+)?$/', $v[$final_list['erpsku']]))
                {
                    array_push($err_info['errorLines'],$line_num);
                    continue;
                }
                foreach ($final_list as $key => $val) {
                    $row[$key] = $v[$val];
                }
                $row['line_num'] = $line_num;
                $insertRows[] = $row;
            }

            $post['data_values'] = json_encode($insertRows);
            $post_result = $this->_curl_request->cloud_post($api_name, $post);
            $err_count = count($err_info['errorLines']);
            $processed += $post_result['data_list']['processed']??0;
            $undisposed += $err_count + $post_result['data_list']['undisposed']??0;
            $err_info['errorLines'] = array_merge($err_info['errorLines'],json_decode($post_result['data_list']['line_num']));
        }
        $this->data['status'] = 1;
        $file_name = pathinfo($_FILES["file"]["name"], PATHINFO_FILENAME);
        $errorFiles = import_error_report($err_info,$file_name);
        $this->data['data_list'] = [
            'processed' => $processed,
            'undisposed' => $undisposed,
            'errorFiles' => $errorFiles['error_file'],
            'errorMsg' => $errorFiles['error_msg']
        ];
        http_response($this->rsp_package($this->data));
    }

    public function batch_del()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['id']) || !$post['id']) {
            http_response(['errorMess' => '请选择需要删除的记录']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 添加备注
     * http://192.168.71.170:1083/fba/new_cfg/addRemark
     */
    public function remark_add()
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

    public function info()
    {
        $get = $this->input->get();
        if (empty($get['id'])) {
            $this->response_error('3001');
        }
        $pr_api_name = $this->_server_module . 'base_info';
        $pr = $this->_curl_request->cloud_get($pr_api_name, $get);
        $remark_api_name = $this->_server_module . 'remark';
        $remark = $this->_curl_request->cloud_get($remark_api_name, $get);
        $result['pr'] = $pr['data_list']??[];
        $result['remark'] = $remark['data_list']??[];
        $result['status'] = 1;
        http_response($this->rsp_package($result));
    }

    /**
     * 查看-基本信息
     * http://192.168.71.170:1083/fba/new_cfg/baseinfo
     */
    public function base_info()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 查看-备注
     * http://192.168.71.170:1083/fba/new_cfg/remark
     */
    public function remark()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 查看-日志
     * http://192.168.71.170:1083/fba/new_cfg/log
     */
    public function log()
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
     * 查看-日志
     * http://192.168.71.170:1083/fba/new_cfg/modify
     */
    public function modify()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (empty($post['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 备货明细
     * http://192.168.71.170:1083/fba/new_cfg/stock
     */
    public function stock()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (empty($get['id'])) {
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $get);
        http_response($this->rsp_package($result));
    }

    public function templateDownload()
    {
        $file_dir = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../')) . "/" . '/end/upload/excel_template/';
        $file_name = "fba_new_template.csv";
        $filename = '新品和首发量配置模板.csv';
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