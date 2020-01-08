<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 备货列表
 *
 * @author Jason 13292
 * @since 2019-03-10
 */
class Purchase extends MY_Controller {

    private $_server_module = 'plan/Purchase/';

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['plan_lang']);
        $this->load->helper('plan_helper');
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
            tran_plan_list_result($result['data_list']['value']);
        }
        $res = $this->rsp_package($result);

        if (!empty($get['isExcel'])) {
            if (empty($res['data_list']['value'])) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '数据为空,导出失败';
                http_response($this->data);
            }
            if (count($res['data_list']['value']) > MAX_EXCEL_LIMIT) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '导出数据必须小于' . MAX_EXCEL_LIMIT;
                http_response($this->data);
            }
            $date = date('YmdHis');
            $titleArr = $res['data_list']['key'];
            unset($titleArr[count($titleArr)-1]);
            $fp = export_head('备货计划-备货列表导出-' . $date,$titleArr );
            $item = 1;
            $data = [];

            foreach ($result['data_list']['value'] as $key => $value) {
                $data[$key][] = $item;
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pur_sn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['bussiness_line_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['is_refund_tax_text']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['purchase_warehouse_id_text']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['earliest_exhaust_date']."\t");
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['total_required_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['delay_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['on_way_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['avail_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['purchase_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['actual_purchase_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['created_at']."\t");
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pur_sn_state_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['is_pushed_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['remark']);

                $item++;
            }
            export_content($fp, $data);
            exit();
        }

        http_response($res);
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
     * 调拨列表
     */
    public function allot_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_plan_allot_list_result($result['data_list']['value']);
        }
        http_response($this->rsp_package($result));
    }

    /**
     * 汇总列表
     */
    public function summary_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_plan_summary_list_result($result['data_list']['value']);
        }

        $res = $this->rsp_package($result);

        if (!empty($get['isExcel'])) {
            if (empty($res['data_list']['value'])) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '数据为空,导出失败';
                http_response($this->data);
            }
            if (count($res['data_list']['value']) > MAX_EXCEL_LIMIT) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '导出数据必须小于' . MAX_EXCEL_LIMIT;
                http_response($this->data);
            }
            $date = date('YmdHis');
            $titleArr = $res['data_list']['key'];
            unset($titleArr[count($titleArr)-1]);
            $fp = export_head('备货计划-备货汇总列表导出-' . $date,$titleArr );
            $item = 1;
            $data = [];

            foreach ($result['data_list']['value'] as $key => $value) {
                $data[$key][] = $item;
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['bussiness_line_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['total_required_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['earliest_exhaust_date']."\t");
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['actual_purchase_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['on_way_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['avail_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['purchase_order_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['created_at']."\t");
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['remark']);
                $item++;
            }

            export_content($fp, $data);
            exit();
        }

        http_response($res);
    }

    /**
     * 汇总列表查看
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
     * 添加一条汇总备注
     */
    public function summary_remark()
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
     * 跟踪列表
     */
    public function track_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            tran_plan_track_list_result($result['data_list']['value']);
        }

        $res = $this->rsp_package($result);

        if (!empty($get['isExcel'])) {
            if (empty($res['data_list']['value'])) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '数据为空,导出失败';
                http_response($this->data);
            }
            if (count($res['data_list']['value']) > MAX_EXCEL_LIMIT) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '导出数据必须小于' . MAX_EXCEL_LIMIT;
                http_response($this->data);
            }
            $date = date('YmdHis');
            $titleArr = $res['data_list']['key'];
            unset($titleArr[count($titleArr)-1]);
            $fp = export_head('备货计划-备货跟踪列表导出-' . $date,$titleArr );
            $item = 1;
            $data = [];

            foreach ($result['data_list']['value'] as $key => $value) {
                $data[$key][] = $item;
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pur_sn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['bussiness_line_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['is_refund_tax_text']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['purchase_warehouse_id_text']??'');
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['earliest_exhaust_date']."\t");
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['actual_purchase_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['po_sn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['po_state_text']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['po_qty']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['expect_arrived_date']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['remark']);

                $item++;
            }
            export_content($fp, $data);
            exit();
        }

        http_response($res);
    }

    /**
     * 添加一条汇总备注
     */
    public function track_remark()
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
     * 汇总列表查看
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

    public function list_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '备货列表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }

    public function track_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '备货跟踪列表_'.date('Ymd_H_i');
        $this->common_export($api_name, $file_name);
    }

    public function summary_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name = '备货汇总列表_'.date('Ymd_H_i');
        $this->common_export($api_name, $file_name);
    }

    public function edit_push_stock_quantity()
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
        $allow_charset = ['UTF8', 'UTF-8', 'GBK'];
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
        $file_charset = mb_detect_encoding($title_line);
        if ($file_charset != 'UTF-8')
        {
            $title_line = iconv("GBK", "UTF-8", $title_line);
            $is_utf8 = false;
        }
        $title_line = str_replace(chr(0xEF).chr(0xBB).chr(0xBF), '', $title_line);
        $title = str_getcsv($title_line);
        if (empty($title_line))
        {
            http_response(['status' => 0, 'errorMess' => '无法检测处理上传文件格式编码，请转换为UTF-8编码或者GBK编码后上传']);
        }
        $location_csv_col_position = [
            'pur_sn' => '备货单号',
            'push_stock_quantity' => '推送采购备货数量',
        ];
        $location_option_col = [
            'remark' => '备注',
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
        $primary_key       = 'pur_sn';
        $parse_error       = [];
        $index_map_col     = array_flip($actual_col_position);
        ksort($index_map_col);
        $primary_key_index = $actual_col_position[$primary_key];
        $batch_size        = 500;
        $post = [
                'primary_key' => $primary_key,
                'map'         => $index_map_col,
        ];
        $report = [
                'total' => 0,
                'processed' => 0,
                'undisposed' => 0
        ];

        $total = 0;
        while (($line = fgets($fhandle)) !== false)
        {
            $total ++;
            //获取指定索引
            $csv_row = $is_utf8 ? str_getcsv($line) : str_getcsv(iconv("GBK", "UTF-8//IGNORE", $line)) ;
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
            $push_stock_quantity = $line[$actual_col_position['push_stock_quantity']] ?? 0;
            if (!(empty($push_stock_quantity) || is_numeric($push_stock_quantity)))
            {
                $parse_error['invalid_quantity'][] = $total + 2;
                 $report['undisposed'] ++;
                 continue;
            }
            if (empty($push_stock_quantity))
            {
                $push_stock_quantity = 0;
            }
            $line[$actual_col_position['push_stock_quantity']] = $push_stock_quantity;
            $selected[] = $line;

            if (count($selected) % $batch_size == 0)
            {
                $post['selected'] = json_encode($selected);
                $this->_batch_push_stock_quantity($post, $report);
                $selected = [];
            }
        }
        fclose($fhandle);
        @unlink($this->upload->file_temp);

        if (!empty($selected))
        {
            $post['selected'] = json_encode($selected);
            $this->_batch_push_stock_quantity($post, $report);
            $selected = [];
        }

        //给出提示
        $report['total'] = $total;
        if (isset($parse_error['unknown']))
        {
            $report['errorMess'] .= sprintf('第%s行无法识别内容，无法处理', implode(',', $parse_error['unknown']));
        }
        if (isset($parse_error['invalid_quantity']))
        {
            $report['errorMess'] .= sprintf('第%s行推送采购系统数量填写错误', implode(',', $parse_error['invalid_quantity']));
        }
        $result = [
            'status' => 1,
            'data_list' => $report
        ];
        http_response($result);
        exit;
    }

    /**
     * 推送修改
     *
     * @param unknown $post
     * @param unknown $report
     */
    private function _batch_push_stock_quantity($post, &$report)
    {
        $api_name = 'edit_push_stock_quantity';
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


    /**
     * 三条业务线的汇总列表手动调用推送的备货列表
     */
    public function push_summary()
    {
        $get = $this->input->get();
        if (!isset($get['business_line']))
        {
            http_response(['errorMess' => '请选择推送的业务线']);
        }
        if (!isset(BUSSINESS_LINE[$get['business_line']]))
        {
            http_response(['errorMess' => '请选择正确的业务线']);
        }
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 三条业务线的汇总列表手动调用推送的备货列表
     */
    public function push_purchase()
    {
        $get = $this->input->get();
        if (!isset($get['business_line']))
        {
            return http_response(['errorMess' => '请选择推送的业务线']);
        }
        $lines = explode(',', $get['business_line']);
        $invalid_lines = array_diff($lines, array_keys(BUSSINESS_LINE));
        if (!empty($invalid_lines)) {
            return http_response(['errorMess' => '无效的业务线'.implode(',', $invalid_lines)]);
        }
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 手动推送
     */
    public function manual_push()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['pur_sn']) || !$post['pur_sn'])
        {
            if (!(isset($post['select_push']) && $post['select_push'] == PURCHASE_CAN_PUSH_YES))
            {
                $this->data['errorMess'] = '请选择需要推送的记录';
                http_response($this->data);
            }
        }
        else
        {
            unset($post['select_push']);
        }
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    public function warehouse_transfer_list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['transfer_apply_sn']) && !$post['transfer_apply_sn']){
            http_response($this->rsp_package(['status'=>0,'errorMess'=>'参数错误']));
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }


}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */