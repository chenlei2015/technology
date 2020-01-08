<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/2
 * Time: 17:48
 */
class Logistics_attr_cfg extends MY_Controller
{
    private $_ci;
    public function __construct()
    {
        $this->_ci =& get_instance();
        parent::__construct();
        get_active_user();
    }

    /**
     * 物流属性配置列表
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/getLogisticsList
     */
    public function getLogisticsList()
    {
//        $login_user = get_active_user();
//        $login_user->user_name;  //获取用户名
        $this->load->service('basic/DropdownService');
        $this->lang->load('common');
        $this->load->model('Fba_logistics_list_model', 'm_logistics');
        $sales_group           = $this->input->post_get('sales_group');
        $salesman              = $this->input->post_get('salesman');
        $account_name          = $this->input->post_get('account_name');
        $station_code          = $this->input->post_get('station_code');
        $created_at_start      = $this->input->post_get('created_at_start');
        $created_at_end        = $this->input->post_get('created_at_end');
        $approved_at_start     = $this->input->post_get('approved_at_start');
        $approved_at_end       = $this->input->post_get('approved_at_end');
        $logistics_id          = $this->input->post_get('logistics_id');
        $is_first_sale         = $this->input->post_get('is_first_sale');
        $listing_state         = $this->input->post_get('listing_state');
        $sku_state             = $this->input->post_get('sku_state');
        $sku                   = $this->input->post_get('sku');
        $asin                  = $this->input->post_get('asin');
        $fn_sku                = $this->input->post_get('fnsku');
        $seller_sku            = $this->input->post_get('seller_sku');
        $purchase_warehouse_id = $this->input->post_get('purchase_warehouse_id');
        $rule_type             = $this->input->post_get('rule_type');
        $updated_at_start      = $this->input->post_get('updated_at_start');
        $updated_at_end        = $this->input->post_get('updated_at_end');
        $approved_state        = $this->input->post_get('approved_state');
        $offset                = $this->input->post_get('offset');
        $limit                 = $this->input->post_get('limit');
        $isExcel               = $this->input->get_post('isExcel')??'';
        $get_count             = $this->input->get_post('get_count')??'';
        $length                = $this->input->get_post('length')??'';
        $start                 = $this->input->get_post('start')??'';
        $id                    = [];
        $column_keys = array(
            $this->lang->myline('item'),                        //序号
            $this->lang->myline('sales_group'),                 //销售小组
            $this->lang->myline('salesman'),                    //销售人员
            $this->lang->myline('account_name'),                //账号名称
            $this->lang->myline('sku'),                         //SKU
            $this->lang->myline('state'),                       //审核状态
            $this->lang->myline('fn_sku'),                      //FNSKU
            $this->lang->myline('asin'),                        //ASIN
            $this->lang->myline('seller'),                      //SellerSKU
            $this->lang->myline('fba_station'),                 //FBA站点
            $this->lang->myline('product_name'),                //产品名称
            $this->lang->myline('listing_state'),               //listing状态
            $this->lang->myline('listing_state_text'),          //listing状态明细
            $this->lang->myline('logistics_attr'),              //物流属性
            $this->lang->myline('delivery_cycle'),              //发货周期
            $this->lang->myline('refund_rate'),                 //退款率
            $this->lang->myline('created_at'),                  //创建时间
            $this->lang->myline('update_info'),                 //修改信息
            $this->lang->myline('check_info'),                  //审核信息
            $this->lang->myline('remark'),                      //备注
        );
        if ($isExcel == 1) {
            if (!empty($this->input->get_post('id'))) {
                $id = json_decode($this->input->get_post('id'), true);           //勾选的id导出
            }
        } else {
            $offset = $offset ? $offset : 1;
            $limit = $limit ? $limit : 20;
            $column_keys[] = $this->lang->myline('operation');
        }


        $params = [
            'sale_group_id'     => $sales_group,
            'salesman_id'       => $salesman,
            'account_name'          => $account_name,
            'station_code'          => $station_code,
            'approved_at_start'     => $approved_at_start,
            'approved_at_end'       => $approved_at_end,
            'listing_state'         => $listing_state,
            'sku_state'             => $sku_state,
            'logistics_id'          => $logistics_id,
            'is_first_sale'         => $is_first_sale,
            'sku'                   => $sku,
            'asin'                  => $asin,
            'fnsku'                 => $fn_sku,
            'seller_sku'            => $seller_sku,
            'purchase_warehouse_id' => $purchase_warehouse_id,
            'rule_type'             => $rule_type,
            'created_at_start'      => $created_at_start,
            'created_at_end'        => $created_at_end,
            'approve_state'         => $approved_state,
            'updated_at_start'      => $updated_at_start,
            'updated_at_end'        => $updated_at_end,
            'offset'                => $offset,
            'limit'                 => $limit,
            'length'                => $length,
            'start'                 => $start
        ];


        $active_user = get_active_user();

        if(!$active_user->has_all_data_privileges(BUSSINESS_FBA)){//如果没有所有权限
            //判断是否为销售人员
            if($active_user->isSalesman()){
                //加上自己作为销售员的条件
                $params['set_data_scope'] = 1;
                //这个账号是否是子账号的管理员
                $account_name = $active_user->get_my_manager_accounts();
                if (!empty($account_name))
                {
                    $params['prev_account_name'] = implode(',', $account_name);
                    $params['prev_salesman'] = $active_user->staff_code;
                }
                else
                {
                    $params['prev_salesman'] = $active_user->staff_code;
                }
            }else{
                $this->data['status'] = 0;
                $this->data['errorMess'] = '您不是销售人员,无法查看';
                http_response($this->data);
                return;
            }
        }
//        else{//如果有所有权限跳过判断是否为销售人员
//            //这个账号是否是子账号的管理员
//            $account_name = $active_user->get_my_manager_accounts();
//            if (!empty($account_name))
//            {
//                $params['account_name'] = implode(',', $account_name);
//                //加上自己作为销售员的条件
//                $params['account_or_salesman'] = 1;
//                $params['salesman'] = $active_user->staff_code;
//            }
//            else
//            {
//                $params['salesman'] = $active_user->staff_code;
//            }
//        }


        $result = $this->m_logistics->getLogisticsList($params, $id,$get_count);
        //导出前查询数量
        if (isset($result['count'])) {
            $result = $result['count'];
            $this->data['status'] = 1;
            $this->data['total'] = $result;
            http_response($this->data);
            return;
        }

        //导出
        if (isset($isExcel) && $isExcel == 1) {
            if (isset($result) && count($result['data_list']) > 0) {
                $valueData = $this->_getView($result['data_list']);
                $column_keys[] = $this->lang->myline('tag');                    //标记gid
                $this->data = [];
                $this->data['status'] = 1;
                $this->data['data_list'] = ['key' => $column_keys, 'value' => $valueData];
                http_response($this->data);
                return;
            } else {
                $column_keys[] = $this->lang->myline('tag');                    //标记gid
                $this->data = [];
                $this->data['status'] = 1;
                $this->data['data_list'] = ['key' => $column_keys, 'value' => ''];
                http_response($this->data);
                return;
            }
        }

        //列表显示
        if (isset($result) && count($result['data_list']) > 0) {
            $valueData = $this->_getView($result['data_list']);
            $this->data['status'] = 1;
            $this->data['data_list'] = array(
                'key' => $column_keys,
                'value' => $valueData,
            );
            $this->data['page_data'] = array(
                'offset' => (int)$result['data_page']['offset'],
                'limit' => (int)$result['data_page']['limit'],
                'total' => $result['data_page']['total'],
            );
        } else {
            $this->data['status'] = 1;
            $this->data['data_list'] = array(
                'key' => $column_keys,
                'value' => array(),
            );
            $this->data['page_data'] = array(
                'offset' => (int)$offset,
                'limit' => (int)$limit,
                'total' => $result['data_page']['total']
            );
        }
        $this->dropdownservice->setDroplist(['station_code']);              //站点
        $this->dropdownservice->setDroplist(['listing_state']);   //listing状态
        $this->dropdownservice->setDroplist(['check_state']);               //审核状态
        $this->dropdownservice->setDroplist(['fba_sales_group']);           //销售小组
        $this->dropdownservice->setDroplist(['fba_salesman']);              //销售人员
        $this->dropdownservice->setDroplist(['fba_logistics_attr']); //物流属性(含待设置)
        $this->dropdownservice->setDroplist(['rule_type']); //当前规则类型
        $this->dropdownservice->setDroplist(['fba_purchase_warehouse']); //采购仓库
        $this->dropdownservice->setDroplist(['fba_purchase_warehouse']); //采购仓库

        $dropdown = ['station_code', 'listing_state', 'check_state', 'fba_sales_group', 'fba_salesman', 'fba_logistics_attr', 'rule_type', 'fba_purchase_warehouse', 'fba_first_sale'];
        $this->dropdownservice->setDroplist($dropdown);
        $this->data['select_list'] = $this->dropdownservice->get();

        $this->load->service('basic/UsercfgProfileService');

        $result = $this->usercfgprofileservice->get_display_cfg('fba_logistics_list');
        $this->data['selected_data_list'] = $result['config'];
        $this->data['profile'] = $result['field'];
        http_response($this->data);
    }

    /**
     * 导出头处理
     * @param $filename
     * @param $title
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
            if (isset($value['approve_state'])) {
                $valueData[$key]['approve_state_cn'] = $this->syncCheckState($value['approve_state']);
            }
            if (isset($value['sku_state'])) {
                $valueData[$key]['sku_state_cn'] = $this->syncSkuState($value['sku_state']);
            }
            if (isset($value['product_status'])) {
                $valueData[$key]['product_status_cn'] = INLAND_SKU_ALL_STATE[$value['product_status']]['name'] ?? '';
            }
            if (isset($value['listing_state'])) {
                $valueData[$key]['listing_state_cn'] = $this->syncListingState($value['listing_state']);
            }
            if (isset($value['logistics_id'])) {
                $valueData[$key]['logistics_id_cn'] = $this->syncLogisticsAttr($value['logistics_id']);
            }

            if (isset($value['rule_type'])) {
                $valueData[$key]['rule_type_cn'] = $this->syncRuleType($value['rule_type']);
            }
            if (isset($value['purchase_warehouse_id'])) {
                $valueData[$key]['purchase_warehouse_cn'] = $this->syncWarehouse($value['purchase_warehouse_id']);
            }
            if (isset($value['is_first_sale'])) {
                $valueData[$key]['is_first_sale_cn'] = FBA_FIRST_SALE_STATE[$value['is_first_sale']]['name'] ?? '未知';
            }
            if (isset($value['pan_eu'])) {
                $valueData[$key]['pan_eu'] = IS_PAN_EU[$value['pan_eu']]['name'] ?? '未知';
            }
            if (isset($value['refund_rate'])) {
                $valueData[$key]['refund_rate'] .= '%';
            }
            data_format_filter($value,['approve_at','updated_at','created_at']);
        }
        return $valueData;
    }

    /**
     * 转换站点
     * @param $value
     * @return mixed
     */
    public function syncStation($value)
    {
        $data = FBA_STATION_CODE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : '';
    }

    /**
     * 审核状态
     * @param $value
     * @return mixed
     */
    public function syncCheckState($value)
    {
        $data = CHECK_STATE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : '';
    }

    /**
     * sku状态
     *
     * @param $value
     *
     * @return mixed
     */
    public function syncSkuState($value)
    {
        $data = SKU_STATE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : '';
    }

    /**
     * listing状态
     *
     * @param $value
     *
     * @return mixed
     */
    public function syncListingState($value)
    {
        $data = LISTING_STATE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : '';
    }

    /**
     * 物流属性
     * @param $value
     * @return mixed
     */
    public function syncLogisticsAttr($value)
    {
        $data = LOGISTICS_ATTR;
        return isset($data[$value]['name']) ? $data[$value]['name'] : '未知';
    }

    public function syncRuleType($value)
    {
        $data = RULE_TYPE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : '未知';
    }

    public function syncWarehouse($value)
    {
        $data = PURCHASE_WAREHOUSE;

        return isset($data[$value]['name']) ? $data[$value]['name'] : '未知';
    }
    /**
     * 物流属性详情预览
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/getLogisticsDetails
     */
    public function getLogisticsDetails()
    {
        $this->load->service('basic/DropdownService');
        $this->load->model('Fba_logistics_list_model', 'm_logistics');
        $this->load->model('Fba_remark_model', 'm_remark');
        $id = $this->input->post_get('id') ?? '';
        $result = $this->m_logistics->getLogisticsDetails($id);
        $active_user = get_active_user();
        if (!$active_user->has_all_data_privileges(BUSSINESS_FBA) && $result['data_detail']['salesman_id'] != $active_user->staff_code)
        {
            //这条记录的账号是否是我管辖的
            $account_name = $active_user->get_my_manager_accounts();
            if (empty($account_name) || !in_array($result['data_detail']['salesman_id'], $account_name))
            {
                http_response(['status'=>0,'errorMess' => '没有权限']);
            }
        }

        $remark = $this->m_remark->getRemarkList('', '', $id);
        $this->data['status'] = 1;
        $this->data['data_list']['value'] = $this->_getView($result);
        $this->data['data_list']['remark'] = $remark;
        $this->dropdownservice->setDroplist(['fba_logistics_attr']);           //物流属性下拉
        $this->dropdownservice->setDroplist(['listing_state']);             //listing状态下拉
        $this->dropdownservice->setDroplist(['fba_purchase_warehouse']);     //采购仓库下拉
        $this->data['select_list'] = $this->dropdownservice->get();
        http_response($this->data);
    }

    /**
     * 添加备注
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/addRemark
     */
    public function addRemark()
    {
        $active_user = get_active_user();
        $this->load->model('Fba_remark_model', "m_remark");
        $id = $this->input->post_get('id') ?? '';
        $remark = $this->input->post_get('remark') ?? '';
        $user_id = $active_user->staff_code ?? '';
        $user_name = $active_user->user_name ?? '';
        $params = [
            'log_id' => $id,
            'op_uid' => $user_id,
            'op_zh_name' => $user_name,
            'remark' => $remark,
            'created_at' => date('Y-m-d H:i:s', time())
        ];
        $result = $this->m_remark->addRemark($params);

        if ($result) {
            $this->data['status'] = 1;
            $this->data['errorMess'] = '备注成功';
            http_response($this->data);
        } else {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '备注失败';
            http_response($this->data);
        }
    }

    /**
     * 批量审核成功
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/batchCheckSuccess
     */
    public function batchCheckSuccess()
    {
        $active_user = get_active_user();
        $this->load->model('Fba_logistics_list_model', 'm_logistics');
        $id = json_decode($this->input->post_get('id')) ?? [];
        $uid = $active_user->staff_code ?? '';
        $user_name = $active_user->user_name ?? '';
        $result = $this->m_logistics->batchCheckSuccess($id, $uid, $user_name);
        if ($result) {
            $this->data['status'] = 1;
            $this->data['data_list'] = $result;
            http_response($this->data);
        }
    }

    /**
     * 批量审核失败
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/batchCheckFail
     */
    public function batchCheckFail()
    {
        $active_user = get_active_user();
        $this->load->model('Fba_logistics_list_model', 'm_logistics');
        $id = json_decode($this->input->post_get('id')) ?? [];
        $uid = $active_user->staff_code ?? '';
        $user_name = $active_user->user_name ?? '';
        $result = $this->m_logistics->batchCheckFail($id, $uid, $user_name);
        if ($result) {
            $this->data['status'] = 1;
            $this->data['data_list'] = $result;
            http_response($this->data);
        }
    }

    /**
     * 获取日志列表
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/getLogList
     */
    public function getLogList()
    {
        $this->lang->load('common');
        $this->load->model('Fba_logistics_list_log_model', "m_log");
        $id = $this->input->post_get('id') ?? '';
        $offset = $this->input->post_get('offset') ?? '';
        $limit = $this->input->post_get('limit') ?? '';
        $offset = $offset ? $offset : 1;
        $limit = $limit ? $limit : 20;
        $column_keys = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $result = $this->m_log->getLogList($id, $offset, $limit);
        if (isset($result) && count($result['data_list']) > 0) {
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key' => $column_keys,
                'value' => $result['data_list'],
            );
            $this->data['page_data']['log'] = array(
                'offset' => (int)$result['data_page']['offset'],
                'limit' => (int)$result['data_page']['limit'],
                'total' => $result['data_page']['total'],
            );
        } else {
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key' => $column_keys,
                'value' => array(),
            );
            $this->data['page_data']['log'] = array(
                'offset' => (int)$offset,
                'limit' => (int)$limit,
                'total' => $result['data_page']['total']
            );
        }
        http_response($this->data);
    }

    /**
     * 默认物流配置页面
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/defaultDetail
     */
    public function defaultDetail()
    {
        $this->load->service('basic/DropdownService');
        $this->load->model('Default_cfg_model', 'm_default');
        //销售人员各自设置的默认物流属性
        $active_user = get_active_user();
        $result = $this->m_default->getFbaDetail($active_user->staff_code);//自己只能查看自己的配置信息
        if(empty($result)){
            $this->data['status'] = 1;
            $this->data['data_list']['value'] = NULL;
            http_response($this->data);
            return;
        }
        $result['default_value_cn'] = $this->syncLogisticsAttr($result['default_value']);
        $this->data['status'] = 1;
        $this->data['data_list']['value'] = $result;
        $this->dropdownservice->setDroplist(['logistics_attr']);               //物流属性下拉
        $this->data['select_list'] = $this->dropdownservice->get();
        http_response($this->data);
    }

    /**
     * 默认物流配置修改
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/defaultModify
     */
    public function defaultModify()
    {
        $active_user = get_active_user();
        $logistics_id = $this->input->post_get('logistics_id');
//        $op_uid = $this->input->post_get('user_id');
//        $op_zh_name = $this->input->post_get('user_name');
        $this->load->model('Default_cfg_model', 'm_default');
        $params = [
            'default_value' => $logistics_id,
            'op_uid' => $active_user->staff_code,
            'op_zh_name' => $active_user->user_name
        ];

        $this->m_default->modify($params);

        //写入日志
        $this->load->model('Default_cfg_log_model', 'm_log');
        $this->m_log->modifyLog($params);
        $this->data['status'] = 1;
        $this->data['errorMess'] = '修改成功';
        http_response($this->data);
    }

    /**
     * 默认物流配置日志列表
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/getDefaultLogList
     */
    public function getDefaultLogList()
    {
        $this->lang->load('common');
        $offset = $this->input->post_get('offset') ?? '';
        $limit = $this->input->post_get('limit') ?? '';
        $offset = $offset ? $offset : 1;
        $limit = $limit ? $limit : 20;
        $column_keys = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $this->load->model('Default_cfg_log_model', 'm_log');
        $active_user = get_active_user();
        $result = $this->m_log->getLogList($offset, $limit,1,$active_user->staff_code);
        if (isset($result) && count($result['data_list']) > 0) {
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key' => $column_keys,
                'value' => $result['data_list'],
            );
            $this->data['page_data']['log'] = array(
                'offset' => (int)$result['data_page']['offset'],
                'limit' => (int)$result['data_page']['limit'],
                'total' => $result['data_page']['total'],
            );
        } else {
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key' => $column_keys,
                'value' => array(),
            );
            $this->data['page_data']['log'] = array(
                'offset' => (int)$offset,
                'limit' => (int)$limit,
                'total' => $result['data_page']['total']
            );
        }
        http_response($this->data);
    }

    /**
     * 修改配置
     */
    public function modify()
    {
        try {
            $params = $this->compatible('post');
            $this->load->service('fba/FbaLogisticsService');
            $result = $this->fbalogisticsservice->modifyOne($params);
            if (!$result) {
                throw new \RuntimeException(sprintf('修改失败'), 500);
            }
            $this->data['status'] = 1;
            $code                 = 200;
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 批量修改导入功能
     */
    public function uploadExcel()
    {
        try {
            $data_values = json_decode($this->input->post_get('data_values'), true);
            $all         = count($data_values);
            $this->data  = [];
            $processed   = 0;//已处理
            $undisposed  = 0;//未处理
            $this->load->service('fba/FbaLogisticsService');
            $processed               = $this->fbalogisticsservice->modifyByExcel($data_values);
            $undisposed              = $all - $processed;
            $this->data['status']    = 1;
            $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
            $code                    = 200;
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 导入修改或插入
     */
    public function import()
    {
        try
        {
            $params = $this->compatible('post');
            $requir_cols = array_flip(['primary_key', 'map', 'selected']);
            if (count(array_diff_key($requir_cols, $params)) > 0 )
            {
                throw new \InvalidArgumentException('无效的参数', 412);
            }
            $this->load->service('fba/FbaLogisticsService');
            $this->data['data'] = $this->fbalogisticsservice->import($params);
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
     * 导出
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/export
     */
    public function export(){
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaLogisticsExportService');
            $this->fbalogisticsexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbalogisticsexportservice->export('csv');
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
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    /**
     * 导出
     * http://192.168.71.170:1084/fba/logistics_attr_cfg/export_listing_state
     */
    public function export_listing_state(){
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaLogisticsExportService');
            $this->fbalogisticsexportservice->setListingStateTemplate($post);
            $this->data['filepath'] = $this->fbalogisticsexportservice->export_listing_state('csv');
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
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }


    /**
     * 导入
     */
    public function update_logistics()
    {
        try {
            $info       = [];
            $data       = json_decode($this->input->post('data'), true);
            $count      = count($data)??'';
            $update_sql = '';
            $this->load->model('Fba_logistics_list_model', 'm_logistics');
            foreach ($data as $key => $item) {
                $sql        = $this->m_logistics->update_logistics($item);
                $update_sql .= sprintf("%s;%s", $sql, PHP_EOL);
            }
            file_put_contents('./update_sql.sql', $update_sql);
            exit;


            $info['total']      = $count;
            $info['processed']  = $result['success']??'';//已处理
            $info['undisposed'] = $result['fail']??'';  //未处理


            $this->data['status']    = 1;
            $this->data['data_list'] = $info;
            $code                    = 200;
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    /**
     * 导出erp上listing_alls在yibai_amazon_sku_map表中未匹配到的sellersku
     */
    public function unmatch_seller_sku_export()
    {
        try {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaUnMatchSellerSkuExportService');
            $this->fbaunmatchsellerskuexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbaunmatchsellerskuexportservice->export('csv');
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     *
     * @author zc
     * @date 2019-10-22
     * @desc 相关列修改
     * @link
     */
    public function update_column()
    {
        try
        {
            $params = $this->input->post();
            $column = $this->input->post_get('column') ?? '';
            $column_value = $this->input->post_get('column_value') ?? '';
            $active_user = get_active_user();
            $user_id = $active_user->staff_code;
            $user_name = $active_user->user_name;
            $post_params = [
                'column' => $column,
                'column_value' => $column_value,
            ];
            $this->load->service('fba/FbaLogisticsService');
            $this->data['data'] = $this->fbalogisticsservice->update_column($post_params,$user_id,$user_name);
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
    public function import_listing_state()
    {
        try
        {
            $params = $this->compatible('post');
            $requir_cols = array_flip(['primary_key', 'map', 'selected']);
            if (count(array_diff_key($requir_cols, $params)) > 0 )
            {
                throw new \InvalidArgumentException('无效的参数', 412);
            }
            $this->load->service('fba/FbaLogisticsService');
            $active_user = get_active_user();
            $user_id = $active_user->staff_code;
            $user_name = $active_user->user_name;
            $this->data['data'] = $this->fbalogisticsservice->import_listing_state($params,$user_id,$user_name);
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
}