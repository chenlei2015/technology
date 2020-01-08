<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 海外仓需求列表
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class PR extends MY_Controller {

    private $_server_module = 'oversea/PR/';

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['oversea_lang']);
        $this->load->helper('oversea_helper');
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
            tran_oversea_list_result($result['data_list']['value']);
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
     * 编辑BD
     */
    public function edit_pr_listing()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '记录主键id必须填写']);
        }
        if (!array_key_exists('bd', $post))
        {
            http_response(['errorMess' => 'bd必须填写']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量编辑BD
     */
    public function batch_edit_pr()
    {
        set_time_limit(-1);
        $config = [
        'allowed_types' => 'csv',
        ];
        $this->load->library('upload', $config);

        //上传文件不做存储，直接读取数据进行操作，但必须做检测操作
        if (!$this->upload->valid_upload_file($this->upload->get_upload_file('file')))
        {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $this->upload->display_errors();
            http_response($this->data);
        }

        setlocale(LC_ALL, 'zh_CN');
        $fhandle = fopen($this->upload->file_temp, 'r');
        if (!$fhandle)
        {
            http_response(['status' => 0, 'errorMess' => '上传文件已经被删除']);
        }
        $title_line = fgets($fhandle);
        if (empty($title_line))
        {
            http_response(['status' => 0, 'errorMess' => '无法检测处理上传文件格式编码，请转换为UTF-8编码或者GBK编码后上传']);
        }
        $is_utf8 = true;
        $file_charset = mb_detect_encoding($title_line, array('GBK', 'GB2312', 'UTF-8'));
        //pr($file_charset);exit;
        if ($file_charset != 'UTF-8')
        {
            $title_line = iconv($file_charset, "UTF-8", $title_line);
            $is_utf8 = false;
        }
        $title_line = str_replace(chr(0xEF).chr(0xBB).chr(0xBF), '', $title_line, $counts);
        $title = str_getcsv($title_line);
        if (empty($title_line))
        {
            http_response(['status' => 0, 'errorMess' => '无法检测处理上传文件格式编码，请转换为UTF-8编码或者GBK编码后上传']);
        }
        $location_csv_col_position = [
            'pr_sn' => '需求单号',
            'bd' => 'BD(pcs)',
        ];
        $location_option_col = [
            'remark' => '备注',
            'require_qty' => '需求数量(pcs)'
        ];
        //必要字段
        $lost_cols = $actual_col_position = $actual_option_col_position = [];
        foreach ($location_csv_col_position as $orignal => $zh_ch)
        {
            if (($orignal_index = array_search($orignal, $title)) !== false)
            {
                $actual_col_position[$orignal] = $orignal_index;
            }
            elseif (($pretty_index = array_search($zh_ch, $title)) !== false)
            {
                $actual_col_position[$orignal] = $pretty_index;
            }
            else
            {
                $lost_cols[] = $zh_ch;
                break;
            }
        }
        //没有匹配
        if (!empty($lost_cols))
        {
            http_response(['status' => 0, 'errorMess' => sprintf('上传的文件必须包含%s标题列', implode(',', $lost_cols))]);
        }

        //可选更新
        foreach ($location_option_col as $orignal => $zh_ch)
        {
            if (($orignal_index = array_search($orignal, $title)) !== false)
            {
                $actual_option_col_position[$orignal] = $orignal_index;
            }
            elseif (($pretty_index = array_search($zh_ch, $title)) !== false)
            {
                $actual_option_col_position[$orignal] = $pretty_index;
            }
        }
        //如果没有上传，则不处理
        if (!empty($actual_option_col_position))
        {
            $actual_col_position += $actual_option_col_position;
        }

        //按照索引排序
        $primary_key       = 'pr_sn';
        $parse_error       = [];
        $index_map_col     = array_flip($actual_col_position);
        ksort($index_map_col);
        $primary_key_index = $actual_col_position[$primary_key];
        $batch_size        = 500;
        $post = [
            'primary_key' => $primary_key,
            'map'         => $actual_col_position,
        ];
        $report = [
            'total' => 0,
            'processed' => 0,
            'undisposed' => 0,
            'errorMess' => '',
        ];

        $total = 0;
        $selected = [];
        while (($line = fgets($fhandle)) !== false)
        {
            $total ++;
            //获取指定索引
            $csv_row = $is_utf8 ? str_getcsv($line) : str_getcsv(iconv($file_charset, "UTF-8//IGNORE", $line)) ;
            if (empty($line))
            {
                $parse_error['unknown'][] = $total + 2;
                $report['undisposed'] ++;
                continue;
            }
            //无效行开始
            if (!isset($csv_row[$primary_key_index]) || empty($csv_row[$primary_key_index]))
            {
                $parse_error['unknown'][] = $total + 2;
                $report['undisposed'] ++;
                continue;
            }
            $line = array_intersect_key($csv_row, $index_map_col);
            //数量运行数字和空
            $bd_quantity = $line[$actual_col_position['bd']] ?? 0;
            if (!(empty($bd_quantity) || is_numeric($bd_quantity)))
            {
                $parse_error['invalid_bd_quantity'][] = $total + 2;
                $report['undisposed'] ++;
                continue;
            }
            if (empty($bd_quantity))
            {
                $bd_quantity = 0;
            }
            $line[$actual_col_position['bd']] = $bd_quantity;
            $pr_sn = $line[$primary_key_index];
            unset($line[$primary_key_index]);
            $selected[$pr_sn] = $line;

            if (count($selected) % $batch_size == 0)
            {
                $post['selected'] = json_encode($selected);
                $post['total'] = count($selected);
                $this->_batch_edit_bd_quantity($post, $report);
                $selected = [];
            }
        }
        fclose($fhandle);
        @unlink($this->upload->file_temp);

        if (!empty($selected))
        {
            $post['selected'] = json_encode($selected);
            $post['total'] = count($selected);
            $this->_batch_edit_bd_quantity($post, $report);
            $selected = [];
        }

        //给出提示
        $report['total'] = $total;
        if (isset($parse_error['unknown']))
        {
            $report['errorMess'] .= sprintf('第%s行无法识别内容，无法处理', implode(',', $parse_error['unknown']));
        }
        if (isset($parse_error['invalid_bd_quantity']))
        {
            $report['errorMess'] .= sprintf('第%s行BD数量填写错误', implode(',', $parse_error['invalid_bd_quantity']));
        }
        $result = [
            'status' => 1,
            'data_list' => $report
        ];
        http_response($result);
        exit;
    }

    /**
     * 批量一级审核
     */
    public function batch_approve_first()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '请选择需要审核的记录']);
        }
        if (!isset($post['result']) || !in_array($post['result'], [APPROVAL_RESULT_PASS, APPROVAL_RESULT_FAILED]))
        {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 批量二级审核
     */
    public function batch_approve_second()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '请选择需要审核的记录']);
        }
        if (!isset($post['result']) || !in_array($post['result'], [APPROVAL_RESULT_PASS, APPROVAL_RESULT_FAILED]))
        {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 汇总 审核
     */
    public function summary_approve()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '请选择需要审核的记录']);
        }
        if (!isset($post['result']) || !in_array($post['result'], [APPROVAL_RESULT_PASS, APPROVAL_RESULT_FAILED]))
        {
            http_response(['errorMess' => '请选择需要审核结果']);
        }
        if (is_string($post['gid']))
        {
            $post['gid'] = explode(',', $post['gid']);
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
     * 详情
     */
    public function detail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['gid']) || !$get['gid'])
        {
            http_response(['errorMess' => '请选择数据']);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if (isset($result['data']['pr']) && !empty($result['data']['pr']))
        {
            tran_oversea_detail_result($result['data']['pr']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 账号管理列表
     */
    public function manager_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 获取未配置管理员的账号数量
     */
    public function get_alone_account_nums()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }


    /**
     * 跟踪列表
     */
    public function track_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_oversea_track_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }


    /**
     * 详情
     */
    public function track_detail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['gid']) || !$get['gid'])
        {
            http_response(['errorMess' => '请选择数据']);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if (isset($result['data']['pr']) && !empty($result['data']['pr']))
        {
            //tran_oversea_detail_result($result['data']['pr']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 添加一条备注
     */
    public function track_remark()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '主键gid必须填写']);
        }
        if (!isset($post['remark']) || !$post['remark'] || trim($post['remark']) == '')
        {
            http_response(['errorMess' => '备注必须填写且不能为空']);
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 跟踪列表
     */
    public function summary_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_oversea_summary_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 添加一条备注
     */
    public function summary_remark()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '主键gid必须填写']);
        }
        if (!isset($post['remark']) || !$post['remark'] || trim($post['remark']) == '')
        {
            http_response(['errorMess' => '备注必须填写且不能为空']);
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 详情
     */
    public function summary_detail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['gid']) || !$get['gid'])
        {
            http_response(['errorMess' => '请选择数据']);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if (isset($result['data']['pr']) && !empty($result['data']['pr']))
        {
            //tran_oversea_detail_result($result['data']['pr']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 审核汇总
     */
    public function execute_summary()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, []);
        http_response($this->rsp_package($result));
    }


    /**
     * csv导出
     */
    public function oversea_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '海外需求导出列表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK', 'data_type' => VIEW_FILE];
        $this->common_export($api_name, $file_name, $options);
    }

    public function oversea_track_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '海外跟踪需求列表_'.date('Ymd_H_i');
        $this->common_export($api_name, $file_name);
    }

    public function oversea_summary_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '海外汇总需求列表_'.date('Ymd_H_i');
        $this->common_export($api_name, $file_name);
    }

    /**
     * 推送修改
     *
     * @param unknown $post
     * @param unknown $report
     */
    private function _batch_edit_bd_quantity($post, &$report)
    {
        $api_name = 'batch_edit_bd';
        $api_name = $this->_server_module.$api_name;
        $result = $this->_curl_request->cloud_post($api_name, $post);
        if ($result['status'] == 0)
        {
            $report['undisposed'] += $post['total'];
            $report['errorMess'] .= ($result['errorMess'] ?? '');
            return;
        }
        $report['processed'] += intval($result['data']['processed']);
        $report['undisposed'] += intval($result['data']['undisposed']);
        return;
    }

    /**
     * 设置账号管理员
     */
    public function set_account_manager()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();

        if (!isset($post['staff_code']) || !$post['staff_code'])
        {
            http_response(['errorMess' => '请选择要设置的管理员的工号']);
        }
        if (!isset($post['platform_code']) || !$post['platform_code'])
        {
            http_response(['errorMess' => '请选择要设置的平台编码']);
        }
        if (!isset($post['station_code']) || !$post['station_code'])
        {
            http_response(['errorMess' => '请选择要设置的站点编码']);
        }
        //转array
        if (is_string($post['staff_code']))
        {
            $post['staff_code'] = explode(',', $post['staff_code']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        if ($result['status'] == 1)
        {
            $cache_full_list = 'MANAGER_OVERSEA_FULL_LIST';
            $this->load->library('rediss');
            $this->rediss->deleteData($cache_full_list);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 重新生成需求列表
     * @author W02278
     * CreateTime: 2019/12/12 17:20
     */
    public function asyc_rebuild_pr()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */
