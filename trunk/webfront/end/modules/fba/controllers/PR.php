<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FBA需求列表
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class PR extends MY_Controller {

    private $_server_module = 'fba/PR/';

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['pr_lang']);
        $this->load->helper('fba_helper');
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
            tran_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 物流扩展信息
     */
    public function extend_logistics_info()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['gid']) || !$get['gid'])
        {
            http_response(['errorMess' => '请选择记录']);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1) {
            $gid = is_array($get['gid']) ? $get['gid'][0] : $get['gid'];
            $result['data'] = $result['data'][$gid];
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 获取列表高亮显示
     */
    public function list_highlight()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '请选择需要高亮的选择记录']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * csv导出
     */
    public function fba_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'FBA需求列表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK', 'data_type' => VIEW_FILE];
        $this->common_export($api_name, $file_name, $options);
    }

    public function fba_track_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'FBA需求跟踪列表_'.date('Ymd_H_i');
        //$options = ['data_type' => VIEW_FILE];
        $this->common_export($api_name, $file_name, $options = []);
    }

    public function fba_summary_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'FBA汇总需求列表_'.date('Ymd_H_i');
       // $options = ['data_type' => VIEW_FILE ];
        $this->common_export($api_name, $file_name, $options = []);
    }

    /**
     * 导出fba需求配置列表
     */
    public function fba_activity_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'FBA活动配置列表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK', 'data_type' => VIEW_FILE];
        $this->common_export($api_name, $file_name, $options);
    }

    /**
     * 导入更新一次修订量
     */
    public function batch_edit_fixed_amount()
    {
        $require_cols = array(
           'pr_sn' => '需求单号',
           'fixed_amount' => '一次修正量',
        );
        $bind_required_cols_callback = [
            'fixed_amount' => function($col, &$line, $actual_col_position) {
                $quantity = $line[$actual_col_position[$col]] ?? 0;
                if (!(empty($quantity) || is_numeric($quantity)))
                {
                    return false;
                }
                return true;
            }
        ];
        $parse_error_tips = [
                'unknown' => '无法识别内容，无法处理',
                'fixed_amount' => '数量填写错误'
        ];
        $api_name = 'batch_edit_fixed_amount';
        $api_name = $this->_server_module.$api_name;
        $curl = $this->_curl_request;

        $this->load->library('CsvReader');

        $this->csvreader->set_rule($require_cols, 'pr_sn')
        ->bind_required_cols_callback($bind_required_cols_callback, null)
        ->bind_parse_error_tips($parse_error_tips)
        ->set_request_handler(
            function($post) use ($api_name, $curl){
                return $curl->cloud_post($api_name, $post);
        });
        $this->csvreader->run();
        http_response(['status' => 1, 'data_list' => $this->csvreader->get_report()]);
    }

    public function rebuild_pr()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

    public function asyc_rebuild_pr()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

    public function asyc_approve()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

	public function approve_process()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

    public function get_fba_total_money()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
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
        //传值
        if (isset($post['country_name']))
        {
            if (empty($post['country_name']))
            {
                unset($post['country_name']);
            }
            else
            {
                $country_code = tran_country_code($post['country_name']);
                if ($country_code == '')
                {
                    $this->data['errorMess'] = '目的国只允许填写： 欧洲，西班牙，意大利，德国，法国，英国其中一个';
                    http_response($this->data);
                }
                else
                {
                    $post['country_code'] = $country_code;
                    unset($post['country_name']);
                }
            }
        }

        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 单个修改一次修订量,也走批量接口
     */
    public function edit_fixed_amount()
    {
        $api_name = $this->_server_module.'batch_edit_fixed_amount';
        $post = $this->input->post();

        if (!isset($post['pr_sn']) || !$post['pr_sn'])
        {
            http_response(['errorMess' => '需求单号不能为空']);
        }
        if (!isset($post['fixed_amount']) || !is_numeric($post['fixed_amount']))
        {
            http_response(['errorMess' => '一次修正量必须填写']);
        }

        //构造参数
        $data = [
            'primary_key' => 'pr_sn',
            'map' => [
                    'pr_sn' => 0,
                    'fixed_amount' => 1
            ],
            'selected' => json_encode([$post['pr_sn'] => [1 => $post['fixed_amount']]]),
            'total' => 1,
            'mode' => 'update'
        ];
        $result = $this->_curl_request->cloud_post($api_name, $data);
        $data = [];
        if ($result['data']['processed'] == 1) {
            $data['status'] = 1;
            $data['data'] = true;
        } else {
            $data['status'] = 0;
            $data['errorMess'] = $result['data']['errorMess'];
        }
        http_response($data);
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
            'require_qty' => '需求数量(pcs)',
            //'country_code' => '目的国',
            //'station_code' => 'FBA站点'
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
            'errorMess' => ''
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
            //无效的目的国家
            /*$station_name = $line[$actual_col_position['station_code']] ?? '';
            $country_name = $line[$actual_col_position['country_code']] ?? '';
            if ($country_name != '' && $station_name == '欧洲')
            {
                $country_code = tran_country_code($country_name);
                if ($country_code == '')
                {
                    $parse_error['invalid_country_name'][] = $total + 2;
                    $report['undisposed'] ++;
                    continue;
                }
                else
                {
                    $line[$actual_col_position['country_code']] = $country_code;
                }
            }*/

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
            $report['errorMess'] .= sprintf(' 第%s行无法识别内容，无法处理', implode(',', $parse_error['unknown']));
        }
        if (isset($parse_error['invalid_bd_quantity']))
        {
            $report['errorMess'] .= sprintf(' 第%s行BD数量填写错误', implode(',', $parse_error['invalid_bd_quantity']));
        }
        /*if (isset($parse_error['invalid_country_name']))
        {
            $report['errorMess'] .= sprintf(' 第%s行目的国填写错误', implode(',', $parse_error['invalid_country_name']));
        }*/
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
     * 批量三级审核
     */
    public function batch_approve_three()
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
            tran_detail_result($result['data']['pr']);
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
        //设置返回格式
        tran_manager_list_result($result['data_list']['value']);
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
        if (!isset($post['account_name']) || !$post['account_name'])
        {
            http_response(['errorMess' => '请选择要设置的账号']);
        }
        if (!is_array($post['account_name']))
        {
            http_response(['errorMess' => '账号格式不正确']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        if ($result['status'] == 1)
        {
            $cache_full_list = 'MANAGER_FULL_LIST';
            $this->load->library('rediss');
            $this->rediss->deleteData($cache_full_list);
        }
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
            tran_track_list_result($result['data_list']['value']);
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
            //tran_detail_result($result['data']['pr']);
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
     * 汇总列表
     */
    public function summary_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_summary_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 促销sku列表
     */
    public function promotion_sku_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_promotion_sku_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 添加一条备注
     */
    public function promotion_remark()
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
     * 促销导出
     */
    public function promotion_sku_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = 'FBA促销SKU列表_'.date('Ymd_H_i');
        $this->common_export($api_name, $file_name, []);
    }

    public function rebuild_cfg()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $config = [
            'allowed_types' => ['text', 'php'],
        ];
        $this->load->library('upload', $config);
        //上传文件不做存储，直接读取数据进行操作，但必须做检测操作
        if (!$this->upload->valid_upload_file($this->upload->get_upload_file('file')))
        {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $this->upload->display_errors();
            return http_response($this->data);
        }
        $cfg_arr['cfg'] = file_get_contents($this->upload->file_temp);
        if (empty($cfg_arr))
        {
            http_response(['status' => 0, 'errorMess' => '配置文件为空']);
        }
        $result = $this->_curl_request->cloud_post($api_name, $cfg_arr);
        http_response($this->rsp_package($result));
    }

    /**
     * 促销导入
     */
    public function promotion_sku_import()
    {
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
                'sku' => 'sku',
                'remark' => '备注',
        ];
        $location_option_col = [

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
        $primary_key       = 'sku';
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
                'undisposed' => 0
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
            $pr_sn = $line[$primary_key_index];
            unset($line[$primary_key_index]);
            $selected[$pr_sn] = $line;

            if (count($selected) % $batch_size == 0)
            {
                $post['selected'] = json_encode($selected);
                $this->_batch_promotion_import($post, $report);
                $selected = [];
            }
        }
        fclose($fhandle);
        @unlink($this->upload->file_temp);

        if (!empty($selected))
        {
            $post['selected'] = json_encode($selected);
            $this->_batch_promotion_import($post, $report);
            $selected = [];
        }

        //给出提示
        $report['total'] = $total;
        if (isset($parse_error['unknown']))
        {
            $report['errorMess'] .= sprintf('第%s行无法识别内容，无法处理', implode(',', $parse_error['unknown']));
        }
        $result = [
                'status' => 1,
                'data_list' => $report
        ];
        http_response($result);
        exit;
    }
    /**
     * 促销删除
     */
    public function promotion_batch_delete()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            http_response(['errorMess' => '请选择数据']);
        }
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
            //tran_detail_result($result['data']['pr']);
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
     * 审核汇总
     */
    public function execute_summary()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, []);
        http_response($this->rsp_package($result));
    }

    /**
     * xls文件上传接口, 先上传visit, 然后通过curl上传到data,
     * 返回hash文件名，请求创建接口时携带hash文件名
     */
    public function csv_edit()
    {
        $config = [
                'upload_path' => APPPATH . '/upload/',
                'allowed_types' => 'csv',
        ];
        $this->load->library('upload', $config);
        $today_dir = get_sgs_upload_path() . date('Ymd') . DIRECTORY_SEPARATOR;
        if (!is_dir($today_dir))
        {
            mkdir($today_dir, 0775, true);
            clearstatcache();
        }

        if (!is_dir($today_dir))
        {
            $this->data['errorMess'] = '创建目录'.$today_dir.'失败，请检查权限';
            http_response($this->data);
            exit;
        }
        $this->upload->set_upload_path($today_dir);

        if (!$this->upload->do_upload('file'))
        {
            $this->data['errorCode'] = 500;
            $this->data['errorDesc'] = $this->upload->display_errors();
            logger('error', 'csv create suggest order error, upload failed', $this->data['errorDesc']);
            http_response($this->data);
        }
        else
        {
            //$full_path = 'E:/www/project_visit/end/upload/fmb_small.xlsx';
            //上传到服务器,返回url地址
            $upload_data = $this->upload->data();

            $full_path = $upload_data['full_path'];

            //改由直接发送数据
            $file = $this->_make_curl_file($full_path);
            $data = [$form_upload_name => $file];
            $this->data = RPC_CALL('YB_J3_FBA_006', $data, null);
            //$api_name = PLAN_FBA_JAVA_API_URL.'/fbaPr/importCsv';
            //$this->data = $this->_curl_upload_file($api_name, $full_path, 'file', ['direct_url' => true]);

            //直接输出文件
            /*$api_name = $this->_server_module.'import_upload_data';
             $post = [
             'file' => $upload_data['file_name'],
             'binary' => file_get_contents($full_path)
             ];
             $this->data = $this->_curl_request->cloud_post($api_name, $post);
             $this->data['file'] = $post['file'];*/

            http_response($this->data);
        }
        exit;
    }

    private function _make_curl_file($file){
        $mime = mime_content_type($file);
        //$finfo = finfo_open(FILEINFO_MIME_TYPE);
        //$mime = strtolower(finfo_file($finfo, $file));
        $info = pathinfo($file);
        $name = $info['basename'];
        $output = new CURLFile($file, $mime, $name);
        return $output;
    }

    private function _curl_upload_file($api_name, $full_path, $form_upload_name, $params = [])
    {
        $url = isset($params['direct_url']) ? $api_name : trim(PLAN_HOST, '/').'/'.$api_name;

        $file = $this->_make_curl_file($full_path);
        $data = [$form_upload_name => $file];
        $param = array_merge($params, $data);

        $headers = [
                'Content-type: application/json;charset=utf-8',
        ];

        $ch = curl_init($url);
        //curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //curl_setopt($ch, CURLOPT_PORT, PLAN_HOST_PORT);
        //curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        //curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        //curl_setopt($ch, CURLOPT_UPLOAD, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        //$reponse = curl_exec($ch);
        curl_setopt($ch, CURLOPT_VERBOSE, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $reponse = curl_exec($ch);


        if (curl_errno($ch) != 0)
        {
            $reponse['errorMess'] = curl_error($ch);
            log_message('ERROR', json_encode($reponse['errorMess']));
            return $reponse;
        }
        curl_close($ch);
        if(!empty($reponse))
        {
            $raw_reponse = $reponse;
            $reponse = json_decode($reponse,true);
            if (json_last_error() != JSON_ERROR_NONE)
            {
                $reponse['errorMess'] = json_last_error_msg();
            }
            $reponse['raw'] = $raw_reponse;
        }
        return $reponse;
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
        log_message('ERROR', json_encode($result, JSON_PRETTY_PRINT));
        if ($result['status'] == 0)
        {
            $report['processed'] += 0;
            $report['undisposed'] += $post['total'];
            $report['errorMess'] .= ($result['errorMess'] ?? '');
            return;
        }
        $report['processed'] += intval($result['data']['processed']);
        $report['undisposed'] += intval($result['data']['undisposed']);
        return;
    }

    /**
     * 促销sku导入
     *
     * @param unknown $post
     * @param unknown $report
     */
    private function _batch_promotion_import($post, &$report)
    {
        $api_name = 'promotion_sku_import';
        $api_name = $this->_server_module.$api_name;
        $result = $this->_curl_request->cloud_post($api_name, $post);
        if ($result['status'] == 0)
        {
            return;
        }
        $report['processed'] += intval($result['data']['processed']);
        $report['undisposed'] += intval($result['data']['undisposed']);
        return;
    }
}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */