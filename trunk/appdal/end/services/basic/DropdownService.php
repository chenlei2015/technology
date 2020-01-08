<?php

/**
 * 该文件用于获取页面公共下拉列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-01-03
 * @link
 */
class DropdownService
{
    private $_ci;

    /**
     * map
     *
     * @var array
     */
    private $_dropdown_list_name = [
        'fba_approval_state'         => 'dropdown_fba_approval_state',
        'oversea_approval_state'     => 'dropdown_oversea_approval_state',
        'fba_trigger_pr'             => 'dropdown_fba_trigger_pr',
        'fba_plan_approval'          => 'dropdown_fba_plan_approval',
        'fba_expired'                => 'dropdown_fba_is_expired',
        'manager_list'               => 'dropdown_manager_list',
        'second_list'                => 'dropdown_second_level_list',
        'three_list'                 => 'dropdown_three_level_list',
        'buss_line'                  => 'dropdown_bussiness_line_list',
        'station_code'               => 'dropdown_fba_station_code',
        'os_station_code'            => 'dropdown_oversea_station_code',
        'check_state'                => 'dropdown_check_state',
        'listing_state'              => 'dropdown_listing_state',
        'mix_hair_state'             => 'dropdown_mix_hair_state',
        'sku_state'                  => 'dropdown_sku_state',
        'fba_sales_group'            => 'dropdown_fba_sales_group',
        'fba_salesman'               => 'dropdown_fba_salesman',
        'logistics_attr'             => 'dropdown_logistics_attr',
        'logistics_attr_unknown'     => 'dropdown_logistics_attr_unknown',
        'first_way_transportation'   => 'dropdown_details_of_first_way_transportation',
        'pur_sn_state'               => 'dropdown_pur_sn_state',
        'pur_data_state'             => 'dropdown_pur_data_push_state',
        'rule_type'                  => 'dropdown_rule_type',
        'virtual_warehouse'          => 'dropdown_virtual_warehouse',
        'oa_depart_list'             => 'dropdown_oa_department_list',
        'purchase_order_status'      => 'dropdown_purchase_order_status',
        'refund_tax'                 => 'dropdown_refund_tax',
        'refund_tax_unknown'         => 'dropdown_refund_tax_unknown',
        'purchase_warehouse'         => 'dropdown_purchase_warehouse',
        'mrp_source'                 => 'dropdown_mrp_source_from',
        'provider_status'            => 'dropdown_provider_status',
        'inland_sku_state'           => 'dropdown_inland_sku_state',
        'special_check_state'        => 'dropdown_special_check_state',
        'sku_match_state'            => 'dropdown_sku_match_state',
        'inland_stock_up'            => 'dropdown_inland_stock_up_type',
        'inland_stock_up_list'       => 'dropdown_inland_stock_up_type_list',
        'inland_warehouse'           => 'dropdown_inland_warehouse',
        'inland_platform_code'       => 'dropdown_inland_platform_code',
        'inland_sku_all_state'       => 'dropdown_inland_sku_all_state',
        'inland_approval_state'      => 'dropdown_inland_approval_state',
        'purchase_can_push'          => 'dropdown_purchase_can_push',
        'promotion_sku_state'        => 'dropdown_promotion_sku_state',
        'oversea_platfrom_state'     => 'dropdown_oversea_platfrom_state',
        'mrp_business_line'          => 'dropdown_mrp_business_line',
        'oversea_platform_code'      => 'dropdown_oversea_platform_code',
        'fba_logistics_attr'         => 'dropdown_fba_logistics_attr',
        'fba_purchase_warehouse'     => 'dropdown_fba_purchase_warehouse',
        'oversea_purchase_warehouse' => 'dropdown_oversea_purchase_warehouse',
        'inland_purchase_warehouse'  => 'dropdown_inland_purchase_warehouse',
        'shipment_push_state'        => 'dropdown_shipment_push_state',
        'oversea_summary_approval'   => 'dropdown_oversea_summary_approval_state',
        'shipment_status'            => 'dropdown_shipment_status',
        'boutique_state'             => 'dropdown_boutique_state',
        'push_status_logistics'      => 'dropdown_push_status_logistics',
        'eu_country'                 => 'dropdown_eu_country',
        'oversea_undo_state'         => 'dropdown_oversea_undo_state',
        'fba_activity_approve_state' => 'dropdown_fba_activity_approve_state',
        'oversea_activity_approve_state' => 'dropdown_oversea_activity_approve_state',
        'fba_activity_state'         => 'dropdown_fba_activity_state',
        'oversea_activity_state'     => 'dropdown_oversea_activity_state',
        'is_accelerate_sale'         => 'dropdown_is_accelerate_sale',
        'is_contraband'              => 'dropdown_is_contraband',
        'is_infringement'            => 'dropdown_is_infringement',
        'new_activity_approve_state' => 'dropdown_fba_new_approve_state',
        'fba_first_sale'             => 'dropdown_fba_first_sale',
        'product_status'             => 'dropdown_product_status',
        'account_status'             => 'dropdown_platform_account_status',
    ];

    private $_runtime_droplist_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
    }

    public function get_names()
    {
        return array_keys($this->_dropdown_list_name);
    }

    public function get()
    {
        $request_hash = md5(json_encode($this->_runtime_droplist_cb));

        static $last_hash;
        static $options;

        if ($last_hash == $request_hash) {
            return $options;
        }
        foreach ($this->_runtime_droplist_cb as $name => $callback) {
            if (isset($options[$name])) {
                continue;
            }
            if (is_string($callback)) {
                if (method_exists($this, $callback)) {
                    $options[$name] = $this->$callback();
                } else {
                    $options[$name] = $callback();
                }
            } else {
                $options[$name] = call_user_func_array($callback, []);
            }
        }
        $last_hash = $request_hash;

        return array_intersect_key($options, $this->_runtime_droplist_cb);
    }

    /**
     * 设置要获取的下拉列表
     */
    public function setDroplist($callbacks = [], $is_override = false)
    {
        if ($is_override) {
            $this->_runtime_droplist_cb = [];
        }

        if (empty($callbacks)) {
            return;
        }

        foreach ($callbacks as $name => $cb) {
            //传递多个name
            if (is_numeric($name)) {
                $callback = $this->_dropdown_list_name[$cb] ?? '';
                $name     = $cb;
            } else {
                $callback = $cb;
            }

            if (is_string($callback)) {
                //$this->method
                if (!(method_exists($this, $callback) || function_exists($callback))) {
                    throw new \BadMethodCallException(sprintf('获取下拉列表%s方法无法调用', $name), 500);
                }
            } else {
                if (!is_callable($callback)) {
                    throw new \BadMethodCallException(sprintf('获取下拉列表%s方法无法调用', $name), 500);
                }
            }

            $this->_runtime_droplist_cb[$name] = $callback;
        }

        return;
    }

    /**
     * delete
     *
     * @param unknown $del_names
     *
     * @return array
     */
    public function delDropList($del_names)
    {
        foreach ($this->_runtime_droplist_cb as $name => $cb) {
            if (in_array($name, $del_names, true)) {
                unset($this->_runtime_droplist_cb[$name]);
            }
        }

        return $this->_runtime_droplist_cb;
    }






    /**
     * pr 需求单 - 审核状态 常量定量
     *
     * name = fba_approval_state
     * @return array|array[]
     */
    public function dropdown_fba_approval_state()
    {
        $options = [];
        foreach (APPROVAL_STATE as $state => $cfg)
        {
            $options[$state] = $cfg['name'];
        }
        unset($options[APPROVAL_STATE_NONE]);
        return $options;
    }

    /**
     * 海外仓审核状态
     *
     * name = fba_approval_state
     * @return array|array[]
     */
    public function dropdown_oversea_approval_state()
    {
        $options = [];
        foreach (APPROVAL_STATE as $state => $cfg) {
            if (in_array($state, [APPROVAL_STATE_THREE, APPROVAL_STATE_UNDO])) {
                continue;
            }
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 是否触发pr
     * name = fba_trigger_pr
     *
     * @return unknown[]
     */
    public function dropdown_fba_trigger_pr()
    {
        $options = [];
        foreach (TRIGGER_PR as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 是否需要计划审核
     * name = fba_plan_approval
     *
     * @return unknown[]
     */
    public function dropdown_fba_plan_approval()
    {
        $options = [];
        foreach (NEED_PLAN_APPROVAL as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 是否需要计划审核
     * name = fba_plan_approval
     *
     * @return unknown[]
     */
    public function dropdown_fba_is_expired()
    {
        $options = [];
        foreach (FBA_PR_EXPIRED as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    //fba站点
    public function dropdown_fba_station_code()
    {

        $options = [];
        foreach (FBA_STATION_CODE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;

    }

    //审核状态
    public function dropdown_check_state()
    {
        $options = [];
        foreach (CHECK_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    //规则类型
    public function dropdown_rule_type()
    {
        $options = [];
        foreach (RULE_TYPE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    //listing状态
    public function dropdown_listing_state()
    {
        $options = [];
        foreach (LISTING_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    //允许空海混发状态
    public function dropdown_mix_hair_state(){
        $options = [];
        foreach (MIX_HAIR_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 物流属性
     * @return array
     */
    public function dropdown_logistics_attr()
    {
        $options = [];
        foreach (LOGISTICS_ATTR as $state => $cfg) {
            if (!isset($cfg['has_unknown'])) {
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }

    /**
     * 物流属性
     * @return array
     */
    public function dropdown_logistics_attr_unknown()
    {
        $options = [];
        foreach (LOGISTICS_ATTR as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 头程运输方式大类
     * @return array
     */
    public function dropdown_details_of_first_way_transportation()
    {
        $options = [];
        foreach (DETAILS_OF_FIRST_WAY_TRANSPORTATION as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * fba的物流属性配置(只有海运散货和空运)
     */
    public function dropdown_fba_logistics_attr()
    {
        $options = [];
        foreach (LOGISTICS_ATTR as $state => $cfg) {
            if (isset($cfg['business_line'])) {
                $options[$state] = $cfg['name'];
            }
        }
        return $options;
    }

    /**
     * 海外仓站点
     * name = oversea_station
     *
     * @return unknown[]
     */
    public function dropdown_oversea_station_code()
    {
        $options = [];
        foreach (OVERSEA_STATION_CODE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * sku状态
     * name = sku_state
     * @return unknown
     */
    public function dropdown_sku_state()
    {
        $options = [];
        foreach (SKU_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name=manager_list
     * 获取配置了1级审核权限的用户并且是FBA的用户
     * 如果本地配置的id拉取不到信息则不显示
     * @return array
     */
    public function dropdown_manager_list()
    {
        $this->_ci->load->model('User_config_model', 'm_user_config', false, 'basic');
        $staff_codes = $this->_ci->m_user_config->get_first_privilege_staff_codes(BUSSINESS_FBA);

        $list = RPC_CALL('YB_J1_005', $staff_codes);
        //根据uid获取用户姓名
        if (!$list) {
            return [];
        }

        return array_column($list, 'userName', 'userNumber');
    }

    /**
     * 二级审核, 不区分业务线
     *
     * @return array|array
     */
    public function dropdown_second_level_list()
    {
        $this->_ci->load->model('User_config_model', 'm_user_config', false, 'basic');
        $uids = $this->_ci->m_user_config->get_second_privilege_staff_codes();
        $list = RPC_CALL('YB_J1_005', $staff_codes);
        //根据uid获取用户姓名
        if (!$list) {
            return [];
        }

        return array_column($list, 'userName', 'userNumber');
    }

    /**
     * 三级审核 , 不区分业务线
     *
     * @return array|array
     */
    public function dropdown_three_level_list()
    {
        //获取列表
        $this->_ci->load->model('User_config_model', 'm_user_config', false, 'basic');
        $uids = $this->_ci->m_user_config->get_three_privilege_staff_codes();
        $list = RPC_CALL('YB_J1_005', $staff_codes);
        //根据uid获取用户姓名
        if (!$list) {
            return [];
        }

        return array_column($list, 'userName', 'userNumber');
    }

    /**
     * 获取业务类型列表
     *
     * @return unknown[]
     */
    public function dropdown_bussiness_line_list()
    {
        $options = [];
        foreach (BUSSINESS_LINE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 备货单状态
     * name = pur_sn_state
     */
    public function dropdown_pur_sn_state()
    {
        $options = [];
        foreach (PUR_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 虚拟仓库
     * name = virtual_warehouse
     */
    public function dropdown_virtual_warehouse()
    {
        $options = [];
        foreach (ALLOT_VM_WAREHOUSE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 备货单推送状态
     *
     * @return unknown[]
     */
    public function dropdown_pur_data_push_state()
    {
        $options = [];
        foreach (PUR_DATA_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * fba 亚马逊账号的销售小组
     */
    public function dropdown_fba_sales_group()
    {
        //获取列表
        $this->_ci->load->model('Fba_amazon_group_model', 'amazon_group', false, 'fba');

        return $this->_ci->amazon_group->get_group_list();
    }

    /**
     * 获取fba下拉销售人员列表， 这里需要做缓存处理
     *
     * @return array|array
     */
    public function dropdown_fba_salesman()
    {
        //根据uid获取用户姓名
        $list = RPC_CALL('YB_J1_002', ['id' => FBA_SALEMAN_DEP_ID]);
        if (!$list || !json_last_error_msg()) {
            log_message('ERROR', sprintf('获取管理员信息接口返回无效数据：%s', $rsp));

            return [];
        }

        return array_column($list['data'] ?? [], 'userName', 'userNumber');
    }

    /**
     * 获取部门列表
     *
     */
    public function dropdown_oa_department_list()
    {
        //根据uid获取用户姓名
        $list = RPC_CALL('YB_J1_003');
        if (!$list || !json_last_error_msg()) {
            log_message('ERROR', sprintf('获取用户直属部门接口返回无效数据：%s', $rsp));

            return [];
        }

        return array_column($list['data'] ?? [], 'name', 'id');
    }

    /**
     * 采购状态
     * name = purchase_order_status
     */
    public function dropdown_purchase_order_status()
    {
        $options = [];
        foreach (PURCHASE_ORDER_STATUS as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = refund_tax
     */
    public function dropdown_refund_tax()
    {
        $options = [];
        foreach (REFUND_TAX as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = refund_tax_unknown
     */
    public function dropdown_refund_tax_unknown()
    {
        $options = [];
        foreach (REFUND_TAX as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }
        return $options;
    }

    /**
     * name = purchase_warehouse
     */
    public function dropdown_purchase_warehouse()
    {
        $options = [];
        foreach (PURCHASE_WAREHOUSE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = mrp_from
     */
    public function dropdown_mrp_source_from()
    {
        $options = [];
        foreach (MRP_DOWNLOAD_DATA_TYPE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }


    /**
     * name = provider_status
     */
    public function dropdown_provider_status()
    {
        $options = [];
        foreach (PROVIDER_STATUS as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = inland_sku_state
     */
    public function dropdown_inland_sku_state()
    {
        $options = [];
        foreach (SKU_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }


    /**
     * name = special_check_state
     */
    public function dropdown_special_check_state()
    {
        $options = [];
        foreach (SPECIAL_CHECK_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }


    /**
     * name = sku_match_state
     */
    public function dropdown_sku_match_state()
    {
        $options = [];
        foreach (SKU_MATCH_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = stock_up_type
     */
    public function dropdown_inland_stock_up_type()
    {
        $options = [];
        foreach (STOCK_UP_TYPE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = inland_stock_up_list
     * @return unknown[]
     */
    public function dropdown_inland_stock_up_type_list()
    {
        $options                  = [];
        $options[STOCK_UP_NORMAL] = STOCK_UP_TYPE[STOCK_UP_NORMAL]['name'];
        $options[STOCK_UP_APPEND] = STOCK_UP_TYPE[STOCK_UP_APPEND]['name'];

        return $options;
    }


    /**
     * name = inland_warehouse
     */
    public function dropdown_inland_warehouse()
    {
        $options = [];
        foreach (PURCHASE_WAREHOUSE as $id => $cfg)
        {
            if (isset($cfg['business_line']) && $cfg['business_line'] == BUSSINESS_IN)
            {
                $options[$id] = $cfg['name'];
            }
        }

        return $options;
    }


    /**
     * name = inland_warehouse
     */
    public function dropdown_inland_platform_code()
    {
        $options = [];
        foreach (INLAND_PLATFORM_CODE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = inland_warehouse
     */
    public function dropdown_inland_sku_all_state()
    {
        $options = [];
        foreach (INLAND_SKU_ALL_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = 'purchase_can_push'
     */
    public function dropdown_purchase_can_push()
    {
        $options = [];
        foreach (PURCHASE_CAN_PUSH as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = 'mrp_business_line'
     */
    public function dropdown_mrp_business_line()
    {
        $options = [];
        foreach (BUSINESS_LINE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }


    /**
     * name = 'promotion_sku_state'
     */
    public function dropdown_promotion_sku_state()
    {
        $options = [];
        foreach (PROMOTION_SKU_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = 'oversea_undo_state'
     */
    public function dropdown_oversea_undo_state()
    {
        $options = [];
        foreach (OVERSEA_PLATFROM_APPROVAL_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }
        $options[APPROVAL_STATE_UNDO] = APPROVAL_STATE[APPROVAL_STATE_UNDO]['name'];
        return $options;
    }

    /**
     * name = 'oversea_platfrom_state'
     */
    public function dropdown_oversea_platfrom_state()
    {
        $options = [];
        foreach (OVERSEA_PLATFROM_APPROVAL_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = 'oversea_platfrom_state'
     */
    public function dropdown_oversea_platform_code()
    {
        $options = [];
        foreach (INLAND_PLATFORM_CODE as $state => $cfg) {
            if ($cfg['oversea'] ?? false) {
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }

    /**
     * name = 'fba_purchase_warehouse'
     */
    public function dropdown_fba_purchase_warehouse()
    {
        $options = [];

        foreach (PURCHASE_WAREHOUSE as $state => $cfg) {
            if (isset($cfg['business_line']) && $cfg['business_line']==BUSINESS_LINE_FBA){
                $options[$state] = $cfg['name'];
            }
        }
        return $options;
    }

    /**
     * name = 'oversea_purchase_warehouse'
     */
    public function dropdown_oversea_purchase_warehouse()
    {
        $options = [];
        foreach (PURCHASE_WAREHOUSE as $state => $cfg) {
            if (isset($cfg['business_line']) && $cfg['business_line']==BUSINESS_LINE_OVERSEA){
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }


    /**
     * name = 'inland_purchase_warehouse'
     */
    public function dropdown_inland_purchase_warehouse()
    {
        $options = [];
        foreach (PURCHASE_WAREHOUSE as $state => $cfg) {
            if (isset($cfg['business_line']) && $cfg['business_line']==BUSINESS_LINE_INLAND){
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }

    /**
     * name = 'shipment_push_state'
     */
    public function dropdown_shipment_push_state()
    {
        $options = [];
        foreach (SHIPMENT_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = 'oversea_summary_approval'
     */
    public function dropdown_oversea_summary_approval_state()
    {
        $options = [];
        foreach (OVERSEA_SUMMARY_APPROVAL_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    public function dropdown_shipment_status()
    {
        $options = [];
        foreach (SHIPMENT_STATUS as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * name = 'boutique_state'
     */
    public function dropdown_boutique_state()
    {
        $options = [];
        foreach (BOUTIQUE_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    public function dropdown_push_status_logistics()
    {
        $options = [];
        foreach (PUSH_STATUS_LOGISTICS as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    public function dropdown_eu_country()
    {
        $options = [];
        foreach (FBA_STATION_CODE as $state => $cfg) {
            if (isset($cfg['is_eu']) && $cfg['is_eu'])
            {
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }


    public function dropdown_fba_activity_approve_state()
    {
        $options = [];
        foreach (ACTIVITY_APPROVAL_STATE as $state => $cfg) {
            if (isset($cfg['is_fba']) && $cfg['is_fba'])
            {
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }

    public function dropdown_oversea_activity_approve_state()
    {
        $options = [];
        foreach (ACTIVITY_APPROVAL_STATE as $state => $cfg) {
            if (isset($cfg['is_fba']) && $cfg['is_fba'])
            {
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }

    public function dropdown_fba_activity_state()
    {
        $options = [];
        foreach (ACTIVITY_STATE as $state => $cfg) {
            if (isset($cfg['is_fba']) && $cfg['is_fba'])
            {
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }

    public function dropdown_oversea_activity_state()
    {
        $options = [];
        foreach (ACTIVITY_STATE as $state => $cfg) {
            if (isset($cfg['is_fba']) && $cfg['is_fba'])
            {
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }

    /**
     * 是否加快动销
     * @return array
     */
    public function dropdown_is_accelerate_sale()
    {
        $options = [];
        foreach (ACCELERATE_SALE_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 是否违禁
     * @return array
     */
    public function dropdown_is_contraband()
    {
        $options = [];
        foreach (CONTRABAND_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

    /**
     * 平台账号状态
     * name = account_name
     * @return array
     */
    public function dropdown_platform_account_status(){
        $options = [];
        foreach (FBA_ACCOUNT_STATUS as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }
        return $options;
    }

    /**
     * 是否侵权
     * @return array
     */
    public function dropdown_is_infringement()
    {
        $options = [];
        foreach (INFRINGEMENT_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }


    /**
     * 是否首发
     * @return arrat
     */
    public function dropdown_fba_first_sale()
    {
        $options = [];
        foreach (FBA_FIRST_SALE_STATE as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }
        //隐藏首发未知状态
        unset($options[FBA_FIRST_SALE_UNKNOWN]);

        return $options;
    }

    public function dropdown_fba_new_approve_state()
    {
        $options = [];
        foreach (NEW_APPROVAL_STATE as $state => $cfg) {
            if (isset($cfg['is_fba']) && $cfg['is_fba'])
            {
                $options[$state] = $cfg['name'];
            }
        }

        return $options;
    }

    /**
     *Notes: sku产品状态
     *User: lewei
     *Date: 2019/11/5
     *Time: 14:18
     */
    public function dropdown_product_status (){
        $options = [];
        foreach (PRODUCT_STATUS_ALL as $state => $cfg) {
            $options[$state] = $cfg['name'];
        }

        return $options;
    }

/**
     * 国内审核状态
     *
     * name = inland_approval_state
     * @return array
     */
    public function dropdown_inland_approval_state()
    {
        $options = [];
        foreach (INLAND_APPROVAL_STATE as $state => $cfg)
        {
            $options[$state] = $cfg['name'];
        }
//        unset($options[INLAND_APPROVAL_STATE]);
        return $options;
    }
}
