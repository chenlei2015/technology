<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内活动配置
 *
 * @author Jason 13292
 *
 * @since 2019-09-06
 */
class Activity extends MY_Controller {

    private $_server_module = 'inland/Activity/';

    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['inland_lang']);
        $this->load->helper('inland_helper');
    }

    public function list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_activity_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    public function export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '国内仓活动列表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK', 'data_type' => VIEW_FILE];
        $this->common_export($api_name, $file_name, $options);
    }

    /**
     * 添加一条备注
     */
    public function remark()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['id']) || !$post['id'])
        {
            $this->data['errorMess'] = '记录主键id必须填写';
            http_response($this->data);
        }
        if (!isset($post['remark']) || !$post['remark'] || trim($post['remark']) == '')
        {
            $this->data['errorMess'] = '备注必须填写且不能为空';
            http_response($this->data);
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 300);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     *Notes: 国内活动量配置表模版下载
     *User: lewei
     *Date: 2019/11/27
     *Time: 10:20
     */
    public function templateDownload(){
        $file_dir = str_replace('\\', '/', realpath(dirname(__FILE__) . '/../../../../')) . "/" . '/end/upload/excel_template/';
        $file_name = "inland_activity_template.csv";
        $filename = '国内活动量配置模板.csv';
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

    public function batch_approve()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['id']))
        {
            http_response(['errorMess' => '请选择需要审核的记录']);
        }
        if (is_string($post['id'])) {
            $post['id'] = empty(trim($post['id'])) ? '' : explode(',', $post['id']);
        }
        if (!isset($post['result']) || !in_array($post['result'], [ACTIVITY_APPROVAL_FAIL, ACTIVITY_APPROVAL_SUCCESS]))
        {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    public function batch_discard()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['id']) || !$post['id'])
        {
            http_response(['errorMess' => '请选择需要废弃的记录']);
        }
        if (is_string($post['id'])) {
            $post['id'] = explode(',', $post['id']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 导入新增或更新
     */
    public function import()
    {
        $insert_require_cols = array(
                'erpsku'        => 'erpsku',
                'platform_code' => '平台',
                'amount'      => '活动量',
                //'activity_name' => '活动名称',
                'execute_purcharse_time' => '开始备货时间',
                'activity_start_time' => '活动开始时间',
                'activity_end_time'   => '活动结束时间',
        );
        $update_require_cols = [
                'id'  => '活动ID',
                'amount'      => '活动量',
                'platform_code' => '平台',
                //'activity_name' => '活动名称',
                'execute_purcharse_time' => '开始备货时间',
                'activity_start_time' => '活动开始时间',
                'activity_end_time'   => '活动结束时间',
        ];

        $bind_required_cols_callback = [

            //活动量检查, 有效正整数
            'amount' => function($col, &$line, $actual_col_position) {
                $quantity = $line[$actual_col_position[$col]] ?? 0;

                if (!(empty($quantity) || is_numeric($quantity)) || $quantity == 0)
                {
                    return false;
                }
                return true;
            },
            //平台
            'platform_code' => function($col, &$line, $actual_col_position) {
                $platform_code = strtoupper($line[$actual_col_position[$col]] ?? '');
                if (!isset(INLAND_PLATFORM_CODE[$platform_code]))
                {
                    return false;
                }
                return true;
                },
            //开始备货时间， 不能早于明天
            'execute_purcharse_time' => function($col, &$line, $actual_col_position, $mode) {
                $execute_purcharse_time = $line[$actual_col_position[$col]] ?? '';
                $activity_start_time = $line[$actual_col_position['activity_start_time']] ?? '';
                $execute_purcharse_timestamp = strtotime($execute_purcharse_time);
                $activity_start_timestamp = strtotime($activity_start_time);
                if (false === $execute_purcharse_timestamp) return false;
                if (false === $activity_start_timestamp) return false;
                if ($execute_purcharse_timestamp > $activity_start_timestamp){
                    return false;
                }
                if ($mode == 'insert' && $execute_purcharse_timestamp < strtotime(date('Y-m-d')) + 86400) {
                    return false;
                }
                $line[$actual_col_position[$col]] = date('Y-m-d H:i:s', $execute_purcharse_timestamp);
                return true;
            },
            //活动开始时间
            'activity_start_time' => function($col, &$line, $actual_col_position, $mode) {
                $value = $line[$actual_col_position[$col]] ?? '';
                $timestamp = strtotime($value);
                if (false === $timestamp) return false;
                $line[$actual_col_position[$col]] = date('Y-m-d H:i:s', $timestamp);
                return true;
            },
            //活动结束时间， 结束时间必须还余1天时间。
            'activity_end_time' => function($col, &$line, $actual_col_position, $mode) {
                $activity_start_time = $line[$actual_col_position['activity_start_time']];
                $value = $line[$actual_col_position[$col]] ?? '';
                $timestamp = strtotime($value);
                if (false === $timestamp) return false;
                //结束时间早于开始时间
                if ($timestamp < strtotime($activity_start_time) )return false;

                if ($mode == 'insert' && $timestamp < strtotime(date('Y-m-d')) + 86400) {
                    return false;
                }
                //y-m-d - y-m-d+1
                $line[$actual_col_position[$col]] = false === strpos($value, ':') ? date('Y-m-d', $timestamp).' 23:59:59' : date('Y-m-d H:i:s', $timestamp);
                return true;
            },
        ];

        $parse_error_tips = [
                'unknown'                => '无法识别内容，无法处理',
                'repeat'                 => '该记录与前面记录发生重复，被忽略。',
                'amount'                 => '数量填写错误',
                'platform_code'          => '无效的国内仓平台code',
                'execute_purcharse_time' => '执行时间格式错误或必须保证执行时间从明天开始 Y-m-d H:i:s或者不能大于活动开始时间',
                'activity_start_time'    => '活动开始时间设置错误 Y-m-d H:i:s',
                'activity_end_time'      => '活动结束时间设置错误或早于开始时间或必须保证至少1天有效时间'
        ];
        $api_name = 'import';
        $api_name = $this->_server_module.$api_name;
        $curl = $this->_curl_request;

        $this->load->library('CsvReader');

        $this->csvreader
        ->bind_required_cols_callback($bind_required_cols_callback, null)
        ->bind_parse_error_tips($parse_error_tips)
        ->check_mode(
            function($title) {
                return in_array('活动ID', $title) ? 'update' : 'insert';
            },
            function($csvReader, $mode) use ($insert_require_cols, $update_require_cols) {
                if ($mode == 'insert') {
                    $csvReader->set_rule($insert_require_cols, '', ['remark' => '备注'])
                    ->set_general_insert_id(
                        function($row, $actual_col_position) {
                            return md5(
                                trim($row[$actual_col_position['erpsku']]).
                                trim($row[$actual_col_position['platform_code']])
                            );
                    });
                } else {
                    $csvReader->set_rule($update_require_cols, 'id', ['remark' => '备注']);
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
/* End of file Activity.php */
/* Location: ./application/modules/fba/controllers/Activity.php */