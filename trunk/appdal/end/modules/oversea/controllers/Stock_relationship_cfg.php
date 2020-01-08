<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/2
 * Time: 17:48
 */
class Stock_relationship_cfg extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        get_active_user();
        $this->_manager_cfg = $this->get_owner_station();
    }

    /**
     * 获取一个账号的权限
     *
     * @return array ['au', 'east']
     */
    protected function get_owner_station()
    {
        $active_user = get_active_user();
        if ($active_user->has_all_data_privileges(BUSSINESS_OVERSEA)) {
            return ['*' => '*'];
        }
        //获取
        $account_cfg = $active_user->get_my_stations();

        return $account_cfg;
    }

    /**
     * 备货关系配置表
     * http://192.168.71.170:1084/oversea/stock_relationship_cfg/getStockList
     */
    public function getStockList()
    {
        if (empty($this->_manager_cfg)) {
            $this->data['errorMess'] = '您没有权限';
            return http_response($this->data);
        } elseif (!isset($this->_manager_cfg['*'])) {
            $params['owner_station'] = $this->_manager_cfg;
        }
        $this->load->service('basic/DropdownService');
        $this->lang->load('common');
        $this->load->model('Oversea_sku_cfg_main_model', 'm_main');
        $sku               = $this->input->post_get('sku');
        $station_code      = $this->input->post_get('station_code');
        $state             = $this->input->post_get('state');
        $supplier_code             = $this->input->post_get('supplier_code');
        $rule_type         = $this->input->post_get('rule_type');
        $created_at_start  = $this->input->post_get('created_at_start');
        $created_at_end    = $this->input->post_get('created_at_end');
        $updated_at_start  = $this->input->post_get('updated_at_start');
        $updated_at_end    = $this->input->post_get('updated_at_end');
        $approved_at_start = $this->input->post_get('approved_at_start');
        $approved_at_end   = $this->input->post_get('approved_at_end');
        $is_refund_tax     = $this->input->post_get('is_refund_tax');
        $pur_warehouse_id  = $this->input->post_get('purchase_warehouse');
        $sku_state         = $this->input->post_get('sku_state');
        $product_status         = $this->input->post_get('product_status');
        $offset            = $this->input->post_get('offset');
        $limit             = $this->input->post_get('limit');
        $isExcel           = $this->input->get_post('isExcel')??'';
        $get_count         = $this->input->get_post('get_count')??'';
        $length            = $this->input->get_post('length')??'';
        $start             = $this->input->get_post('start')??'';
        $gid               = [];
        $column_keys       = [
            $this->lang->myline('item'),                        //序号
            $this->lang->myline('rule'),                        //规则
            $this->lang->myline('sku'),                         //sku
            $this->lang->myline('state'),                       //状态
            $this->lang->myline('oversea_station'),             //海外仓站点
            $this->lang->myline('is_refund_tax'),               //是否能退税
            $this->lang->myline('moq_qty'),                     //MOQ数量
            $this->lang->myline('supplier_code'),               //供应商编码
            $this->lang->myline('original_min_start_amount'),   //供应商起订金额1
            $this->lang->myline('min_start_amount'),            //供应商起订金额2
            $this->lang->myline('pur_warehouse'),               //建议采购仓库
            $this->lang->myline('sku_state'),                  //计划系统sku状态
            $this->lang->myline('product_status'),              //erp系统sku状态
            $this->lang->myline('as_up'),                       //上架时效(AS)
            $this->lang->myline('ls_air'),                      //物流时效LS_空运
            $this->lang->myline('ls_shipping_bulk'),            //物流时效LS_海运散货
            $this->lang->myline('ls_shipping_full'),            //物流时效LS_海运整柜
            $this->lang->myline('ls_trains_bulk'),              //物流时效LS_铁运散货
            $this->lang->myline('ls_trains_full'),              //物流时效LS_铁运整柜
            $this->lang->myline('ls_land'),                     //物流时效LS_陆运
            $this->lang->myline('ls_blue'),                     //物流时效LS_蓝单
            $this->lang->myline('ls_red'),                      //物流时效LS_红单
            $this->lang->myline('pt_air'),                      //打包时效PT_空运
            $this->lang->myline('pt_shipping_bulk'),            //打包时效PT_海运散货
            $this->lang->myline('pt_shipping_full'),            //打包时效PT_海运整柜
            $this->lang->myline('pt_trains_bulk'),              //打包时效PT_铁运散货
            $this->lang->myline('pt_trains_full'),              //打包时效PT_铁运整柜
            $this->lang->myline('pt_land'),                     //打包时效PT_陆运
            $this->lang->myline('pt_blue'),                     //打包时效PT_蓝单
            $this->lang->myline('pt_red'),                      //打包时效PT_红单
            $this->lang->myline('bs'),                          //缓冲库存(BS)
            $this->lang->myline('lt'),                          //供货周期(L/T)
            $this->lang->myline('sp'),                          //备货处理周期(SP)
            $this->lang->myline('sc'),                          //备货周期(SC)
            $this->lang->myline('sz'),                          //服务对应"Z"值
            $this->lang->myline('create_at'),                 //创建时间
            $this->lang->myline('update_info'),                      //修改信息
            $this->lang->myline('check_info'),                       //审核信息
            $this->lang->myline('remark'),                      //备注
        ];
        if ($isExcel == 1) {
            if (!empty($this->input->get_post('gid'))) {
                $gid = json_decode($this->input->get_post('gid'), true);           //勾选的id导出
            }
        } else {
            $offset        = $offset ? $offset : 1;
            $limit         = $limit ? $limit : 20;
            $column_keys[] = $this->lang->myline('operation');
        }

        $params = [
            'sku'               => $sku,
            'station_code'      => $station_code,
            'state'             => $state,
            'supplier_code'      => $supplier_code,
            'rule_type'         => $rule_type,
            'created_at_start'  => $created_at_start,
            'created_at_end'    => $created_at_end,
            'updated_at_start'  => $updated_at_start,
            'updated_at_end'    => $updated_at_end,
            'approved_at_start' => $approved_at_start,
            'approved_at_end'   => $approved_at_end,
            'is_refund_tax'     => $is_refund_tax,
            'pur_warehouse_id'  => $pur_warehouse_id,
            'sku_state'         => $sku_state,
            'product_status'         => $product_status,
            'offset'            => $offset,
            'limit'             => $limit,
            'length'            => $length,
            'start'             => $start
        ];
        $result = $this->m_main->getStockList($params, $gid, $get_count);
        //导出前查询数量
        if (isset($result['count'])) {
            $result               = $result['count'];
            $this->data['status'] = 1;
            $this->data['total']  = $result;
            http_response($this->data);

            return;
        }
        //导出
        if (isset($isExcel) && $isExcel == 1) {
            $column_keys[]           = $this->lang->myline('tag');                    //标记gid
            $valueData               = $this->_getView($result['data_list']);
            $this->data              = [];
            $this->data['status']    = 1;
            $this->data['data_list'] = ['key' => $column_keys, 'value' => $valueData];
            http_response($this->data);

            return;
        }

        if (isset($result) && count($result['data_list']) > 0) {
            $valueData               = $this->_getView($result['data_list']);
            $this->data['status']    = 1;
            $this->data['data_list'] = [
                'key'   => $column_keys,
                'value' => $valueData,
            ];
            $this->data['page_data'] = [
                'offset' => (int)$result['data_page']['offset'],
                'limit'  => (int)$result['data_page']['limit'],
                'total'  => $result['data_page']['total'],
            ];
        } else {
            $this->data['status']    = 1;
            $this->data['data_list'] = [
                'key'   => $column_keys,
                'value' => [],
            ];
            $this->data['page_data'] = [
                'offset' => (int)$offset,
                'limit'  => (int)$limit,
                'total'  => 0
            ];
        }
        $this->dropdownservice->setDroplist(['os_station_code']);       //海外仓站点下拉列表
        $this->dropdownservice->setDroplist(['check_state']);           //审核状态下拉列表
        $this->dropdownservice->setDroplist(['rule_type']);             //规则类型下拉列表
        $this->dropdownservice->setDroplist(['refund_tax']);             //是否退税下拉列表
        $this->dropdownservice->setDroplist(['oversea_purchase_warehouse']);     //采购仓库下拉列表
        $this->dropdownservice->setDroplist(['sku_state']);     //SKU状态下拉列表
        $this->dropdownservice->setDroplist(['product_status']);     //erp系统sku下拉
        $this->data['select_list'] = $this->dropdownservice->get();
        $this->load->service('basic/UsercfgProfileService');

        $result                           = $this->usercfgprofileservice->get_display_cfg('oversea_stock_relationship_cfg');
        $this->data['selected_data_list'] = $result['config'];
        $this->data['profile']            = $result['field'];
        http_response($this->data);
    }

    /**
     * 导出头处理
     *
     * @param $filename
     * @param $title
     *
     * @return bool|resource
     */
    public function export_head($filename, $title)
    {
        ob_clean();
        $filename = iconv("UTF-8", "GB2312", $filename);
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $filename . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        $fp = fopen('php://output', 'a');
        if (!empty($title)) {
            foreach ($title as $k => $v) {
                $title[$k] = iconv("UTF-8", "GB2312//IGNORE", $v);
            }
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);

        return $fp;
    }


    /**
     * 导出内容处理
     *
     * @param $fp
     * @param $data
     */

    public function export_content($fp, $data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                fputcsv($fp, $val);
            }
        }
    }

    /**
     * 转中文
     */
    public function _getView($valueData)
    {
        foreach ($valueData as $key => &$value) {
            if (isset($value['station_code'])) {
                $valueData[$key]['station_code_cn'] = $this->syncStation($value['station_code']);
            }
            if (isset($value['rule_type'])) {
                $valueData[$key]['rule_type_cn'] = $this->syncRuleType($value['rule_type']);
            }
            if (isset($value['state'])) {
                $valueData[$key]['check_state_cn'] = $this->syncCheckState($value['state']);
            }
            if (isset($value['is_refund_tax'])) {
                $valueData[$key]['is_refund_tax_cn'] = $this->syncRefund_tax($value['is_refund_tax']);
            }
            if (isset($value['purchase_warehouse_id'])) {
                $valueData[$key]['purchase_warehouse_cn'] = $this->purchase_warehouse($value['purchase_warehouse_id']);
            }

            if (isset($value['sku_state'])) {
                $valueData[$key]['sku_state_text'] = isset(SKU_STATE[$value['sku_state']]) ? SKU_STATE[$value['sku_state']]['name'] : '';
            }

            //erp系统sku状态
            if (isset($value['product_status'])) {
                $valueData[$key]['product_status_text'] = isset(INLAND_SKU_ALL_STATE[$value['product_status']]) ? INLAND_SKU_ALL_STATE[$value['product_status']]['name'] : '';
            }

            if (isset($value['is_import'])) {
                $valueData[$key]['is_import_text'] = isset(IS_IMPORT[$value['is_import']]) ? IS_IMPORT[$value['is_import']]['name'] : '';
            }
//            if(isset($value['sale_state'])) {
//                $valueData[$key]['sale_state_cn'] = $this->syncListingState($value['sale_state']);
//            }
            data_format_filter($value, ['approved_at', 'created_at', 'updated_at']);
        }

        return $valueData;
    }

    /**
     * 转换是否退税
     *
     * @param $value
     *
     * @return mixed
     */
    public function syncRefund_tax($value)
    {
        $data = REFUND_TAX;

        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
    }

    /**
     * 转换采购仓库
     *
     * @param $value
     *
     * @return mixed
     */
    public function purchase_warehouse($value)
    {
        $data = PURCHASE_WAREHOUSE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
    }

    /**
     * 转换站点
     *
     * @param $value
     *
     * @return mixed
     */
    public function syncStation($value)
    {
        $data = OVERSEA_STATION_CODE;//更改

        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
    }

    /**
     * 转换规则类型
     *
     * @param $value
     *
     * @return mixed
     */
    public function syncRuleType($value)
    {
        $data = RULE_TYPE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
    }

    /**
     * 审核状态
     *
     * @param $value
     *
     * @return mixed
     */
    public function syncCheckState($value)
    {
        $data = CHECK_STATE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
    }

    /**
     * 销售状态
     *
     * @param $value
     *
     * @return mixed
     */
//    public function syncListingState($value)
//    {
//        $data = SKU_STATE;
//        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
//    }

    /**
     * 备货关系预览
     * http://192.168.71.170:1084/oversea/stock_relationship_cfg/getStockDetails
     */
    public function getStockDetails()
    {
        $this->load->model('Oversea_sku_cfg_main_model', 'm_sku_main');
        $this->load->model('Oversea_remark_model', 'm_remark');
        $this->load->service('basic/DropdownService');
        $gid                               = $this->input->post_get('gid');
        $result                            = $this->m_sku_main->getStockDetails($gid);
        $remark                            = $this->m_remark->getRemarkList('', $gid);
        $this->data['status']              = 1;
        $this->data['data_list']['value']  = $this->_getView($result);
        $this->data['data_list']['remark'] = $remark;
        $this->dropdownservice->setDroplist(['rule_type']);             //规则类型下拉列表
        $this->data['select_list'] = $this->dropdownservice->get();
        http_response($this->data);
    }

    /**
     * 获取日志列表
     * http://192.168.71.170:1084/oversea/stock_relationship_cfg/getStockLogList
     */
    public function getStockLogList()
    {
        $this->lang->load('common');
        $this->load->model('Oversea_stock_log_model', "m_log");
        $gid         = $this->input->post_get('gid') ?? '';
        $offset      = $this->input->post_get('offset') ?? '';
        $limit       = $this->input->post_get('limit') ?? '';
        $offset      = $offset ? $offset : 1;
        $limit       = $limit ? $limit : 20;
        $column_keys = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $result      = $this->m_log->getStockLogList($gid, $offset, $limit);
        if (isset($result) && count($result['data_list']) > 0) {
            $this->data['status']           = 1;
            $this->data['data_list']['log'] = [
                'key'   => $column_keys,
                'value' => $result['data_list'],
            ];
            $this->data['page_data']['log'] = [
                'offset' => (int)$result['data_page']['offset'],
                'limit'  => (int)$result['data_page']['limit'],
                'total'  => $result['data_page']['total'],
            ];
        } else {
            $this->data['status']           = 1;
            $this->data['data_list']['log'] = [
                'key'   => $column_keys,
                'value' => [],
            ];
            $this->data['page_data']['log'] = [
                'offset' => (int)$offset,
                'limit'  => (int)$limit,
                'total'  => $result['data_page']['total']
            ];
        }

        http_response($this->data);
    }

    /**
     * 备货关系修改
     * http://192.168.71.170:1084/oversea/Global_rule_cfg/modifyStock
     */
    public function modifyStock()
    {

        $this->load->model('Oversea_sku_cfg_part_model', 'm_part');
        $gid              = $this->input->post_get('gid') ?? '';
        $user_id          = $this->input->post_get('user_id') ?? '';
        $user_name        = $this->input->post_get('user_name') ?? '';
        $rule_type        = $this->input->post_get('rule_type') ?? '';     //规则类型
        $as_up            = $this->input->post_get('as_up') ?? '';             //上架时效(AS)
        $ls_air           = $this->input->post_get('ls_air') ?? '';         //物流时效LS_空运
        $ls_shipping_bulk = $this->input->post_get('ls_shipping_bulk') ?? '';           //物流时效LS_海运散货
        $ls_shipping_full = $this->input->post_get('ls_shipping_full') ?? '';       //物流时效LS_海运整柜
        $ls_trains_bulk   = $this->input->post_get('ls_trains_bulk') ?? '';         //物流时效LS_铁运散货
        $ls_trains_full   = $this->input->post_get('ls_trains_full') ?? '';           //物流时效LS_铁运整柜
        $ls_land          = $this->input->post_get('ls_land') ?? '';            //物流时效LS_陆运
        $ls_blue          = $this->input->post_get('ls_blue') ?? '';         //物流时效LS_蓝单
        $ls_red           = $this->input->post_get('ls_red') ?? '';           //物流时效LS_红单
        $pt_air           = $this->input->post_get('pt_air') ?? '';       //打包时效PT_空运
        $pt_shipping_bulk = $this->input->post_get('pt_shipping_bulk') ?? '';         //打包时效PT_海运散货
        $pt_shipping_full = $this->input->post_get('pt_shipping_full') ?? '';           //打包时效PT_海运整柜
        $pt_trains_bulk   = $this->input->post_get('pt_trains_bulk') ?? '';           //打包时效PT_铁运散货
        $pt_trains_full   = $this->input->post_get('pt_trains_full') ?? '';           //打包时效PT_铁运整柜
        $pt_land          = $this->input->post_get('pt_land') ?? '';            //打包时效LS_陆运
        $pt_blue          = $this->input->post_get('pt_blue') ?? '';           //打包时效PT_蓝单
        $pt_red           = $this->input->post_get('pt_red') ?? '';           //打包时效PT_红单
        $bs               = $this->input->post_get('bs') ?? '';       //缓冲库存(BS)
        $sc               = $this->input->post_get('sc') ?? '';       //备货周期(SC)
        $sp               = $this->input->post_get('sp') ?? '';       //备货处理周期(SP)
        $sz               = $this->input->post_get('sz') ?? '';       //服务对应"Z"值
        $original_min_start_amount      = $this->input->post_get('original_min_start_amount') ?? '0';       //供应商最小起订金额1
        $min_start_amount               = $this->input->post_get('min_start_amount') ?? '0';       //供应商最小起订金额2

        $params = [
            'gid'              => $gid,
            'op_uid'           => $user_id,
            'op_zh_name'       => $user_name,
            'as_up'            => $as_up,
            'ls_air'           => $ls_air,
            'ls_shipping_bulk' => $ls_shipping_bulk,
            'ls_shipping_full' => $ls_shipping_full,
            'ls_trains_bulk'   => $ls_trains_bulk,
            'ls_trains_full'   => $ls_trains_full,
            'ls_land'          => $ls_land,
            'ls_blue'          => $ls_blue,
            'ls_red'           => $ls_red,
            'pt_air'           => $pt_air,
            'pt_shipping_bulk' => $pt_shipping_bulk,
            'pt_shipping_full' => $pt_shipping_full,
            'pt_trains_bulk'   => $pt_trains_bulk,
            'pt_trains_full'   => $pt_trains_full,
            'pt_land'          => $pt_land,
            'pt_blue'          => $pt_blue,
            'pt_red'           => $pt_red,
            'bs'               => $bs,
            'sc'               => $sc,
            'sp'               => $sp,
            'sz'               => $sz,
            'original_min_start_amount'      => $original_min_start_amount,
            'min_start_amount'               => $min_start_amount,
            'updated_at'       => date('Y-m-d H:i:s')
        ];
        $result = $this->m_part->modifyStock($params, $rule_type);

        if ($result['code'] == 1) {
            $this->data['status']    = 1;
            $this->data['errorMess'] = '修改成功';
            http_response($this->data);
        } else {
            $this->data['status']    = 0;
            $this->data['errorMess'] = $result['errorMess'];
            http_response($this->data);
        }
    }

    /**
     * 添加备注
     * http://192.168.71.170:1084/oversea/stock_relationship_cfg/addRemark
     */
    public function addRemark()
    {
        $this->load->model('Oversea_remark_model', "m_remark");
        $gid       = $this->input->post_get('gid') ?? '';
        $remark    = $this->input->post_get('remark') ?? '';
        $user_id   = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $params    = [
            'op_uid'     => $user_id,
            'op_zh_name' => $user_name,
            'gid'        => $gid,
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $result    = $this->m_remark->addRemark($params);
        if ($result) {
            $this->data['status']    = 1;
            $this->data['errorMess'] = '备注成功';
            http_response($this->data);
        } else {
            $this->data['status']    = 0;
            $this->data['errorMess'] = '备注失败';
            http_response($this->data);
        }
    }


    /**
     * 批量审核成功
     */
    public function batchCheckSuccess()
    {
        $this->load->model('Oversea_sku_cfg_part_model', 'm_part');
        $gid       = json_decode($this->input->post_get('gid'));
        $uid       = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        if (!is_array($gid)) {
            $this->data['status']    = 0;
            $this->data['errorMess'] = '参数错误';
            http_response($this->data);
        }
        $result = $this->m_part->batchCheckSuccess($gid, $uid, $user_name);
        if ($result) {
            $this->data['status']    = 1;
            $this->data['data_list'] = $result;
            http_response($this->data);
        }
    }

    /**
     * 批量审核失败
     */
    public function batchCheckFail()
    {
        $this->load->model('Oversea_sku_cfg_part_model', 'm_part');
        $gid       = json_decode($this->input->post_get('gid'));
        $uid       = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        if (!is_array($gid)) {
            $this->data['status']    = 0;
            $this->data['errorMess'] = '参数错误';
            http_response($this->data);
        }
        $result = $this->m_part->batchCheckFail($gid, $uid, $user_name);
        if ($result) {
            $this->data['status']    = 1;
            $this->data['data_list'] = $result;
            http_response($this->data);
        }
    }

    /**
     * 获取全量审批过程当中的汇总信息
     * state 1 开始审批 2:全量审批中 3 审批结束 4 数据都已审批完毕
     */
    public function approve_process()
    {
        // state 1 开始审批 2:全量审批中 3 审批结束 4 数据都已审批完毕
        $data = [
            'data' => -1,
            'status' => 0
        ];
        $get = $this->input->get();
        $query = $get['query'] ?? '';
        $result = $get['result'] ?? -1;

        if (strlen($query) != 32) {
            $this->data['errorMess'] = '必须设置查询秘钥';
            return http_response($this->data);
        }
        if (!in_array($get['result'], [2, 3])) {
            $this->data['errorMess'] = '必须设置审核结果';
            return http_response($this->data);
        }

        $this->load->service('oversea/StockRelationshipCfgService');//todo 要改
        $data['data'] = $this->stockrelationshipcfgservice->get_approve_process_summary($result, $query);
        $data['status'] = 1;
        http_response($data);
    }


    /**
     * 异步全量审核入口
     * result =2 审核通过  result= 3 审核失败
     */
    public function asyc_approve(){
        $active_user = get_active_user();
        if (!function_exists('shell_exec')) {
            $this->data['errorMess'] = '请在php.ini或者php-fpm中开启appdal的shell_exec的函数';
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->data['errorMess'] = '该操作不能再Window操作系统下执行';
        }

        if (isset($this->data['errorMess'])) {
            return http_response($this->data);
        }

        $get = $this->input->get();
        if (!isset($get['result']) || !in_array($get['result'], [2, 3])) {
            $this->data['errorMess'] = '必须设置审核结果';
            return http_response($this->data);
        }

        $result = intval($get['result']);
        $salt = mt_rand(10000, 99999);
        $session_uid = $active_user->uid;
        //$user_name = $active_user->user_name;

        //生成一个查询审批进度的key 并保存到redis的hash表logistics_approve_query_pool中
        $this->load->library('Rediss');
        $query_key = $active_user->staff_code.'.'.$result;

        $command = "eval \"redis.call('hdel', 'stock_approve_query_pool', KEYS[1]);return 'SUCC';\" 1 %s";// todo 要改
        $command = sprintf($command,$query_key);
        $result_command = $result && $this->rediss->eval_command($command);

        $query_val = $this->rediss->command(implode(' ', ['hget', 'stock_approve_query_pool', $query_key]));//todo 要改
        log_message('INFO',"query_value : {$query_val}");

        if (!$query_val) {
            $path_entry = FCPATH.'index.php';
            $query_val = md5($session_uid.$salt);
            $cmd = sprintf('/usr/bin/php %s oversea Stock_relationship_cfg batch_approve_all %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $query_val);//todo 要改
            shell_exec($cmd);
            $this->rediss->command(implode(' ', ['hset', 'stock_approve_query_pool', $query_key, $query_val]));//todo 要改
        } else {
            log_message('INFO',"开始执fff行审批");
            //第二次执行
            $path_entry = FCPATH.'index.php';
            $cmd = sprintf('/usr/bin/php %s oversea Stock_relationship_cfg batch_approve_all %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $query_val);//todo 要改
        }

        $this->data = ['status' => 1, 'data' => $query_val, 'cmd' => $cmd];
        http_response($this->data);
    }

    /**
     * 批量审核 命令行 调用
     */
    public function batch_approve_all()
    {
        try
        {
            //审核权限
            if (is_cli() && func_num_args() > 0) {
                list($session_uid, $result, $query_value) = func_get_args();
                log_message('INFO',"session_uid:{$session_uid}, result:{$result}, query_value:{$query_value}");
                if (!$session_uid || !$result) {
                    throw new \InvalidArgumentException('cli请求丢失session_uid,result参数');
                }
                $this->load->library('Rediss');
                $user_data = $this->rediss->getData($session_uid);
                log_message('INFO',"user_data:".json_encode($user_data));
                if (!empty($user_data)) {
                    $this->load->service('UserService');
                    $this->userservice::login($user_data);
                    $active_user = get_active_user(true);
                } else {
                    throw new \InvalidArgumentException('获取用户认证信息失败，该用户未登陆或者已经失效，请重新登陆');
                }
            } else {
                $params = $this->input->get();
                $result = $params['result'];
                $query_value = $params['query'];
            }

            //设置权限
            $this->load->service('oversea/StockRelationshipCfgService');//todo 要改
            $this->data['data'] = $this->stockrelationshipcfgservice->batch_approve_all($result,$query_value);//todo 要改
            $this->data['status'] = 1;
            $code = 200;

        }
        catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 批量修改导入功能
     */
    public function uploadExcel()
    {
        $user_id     = $this->input->post_get('user_id') ?? '';
        $user_name   = $this->input->post_get('user_name') ?? '';
        $data_values = json_decode($this->input->post_get('data_values'), true);
        $this->load->model('Oversea_sku_cfg_part_model', 'm_part');
        $processed  = 0;//已处理
        $undisposed = 0;//未处理
        foreach ($data_values as $key => $value) {
            if (in_array('', $value)) {//如果数组中有空值
                $undisposed++;
                continue;
            }
            $result = $this->m_part->modifyStockByExcel($value, $user_id, $user_name);
            if ($result) {
                $processed++;
            } else {
                $undisposed++;
            }
        }
        $this->data['status']    = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->data);
    }

    /**
     * 导出功能
     */
    public function export()
    {
        try {
            $post = $this->compatible('post');
            $this->load->service('oversea/OverseaStockCfgExportService');
            $this->overseastockcfgexportservice->setTemplate($post);
            $this->data['filepath'] = $this->overseastockcfgexportservice->export('csv');
            $this->data['status']   = 1;
            $code                   = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }
}
