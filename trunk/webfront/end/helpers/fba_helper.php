<?php

if (!function_exists('tran_list_result')) {

    /**
     * 转换list的数据格式
     */
    function tran_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        $hidden_cols = array_flip([
                'account_id', 'account_num', 'deviate_28_pcs', 'avg_weight_sale_pcs', 'z',
                'approved_uid', 'updated_uid', 'check_attr_state', 'check_attr_uid', 'check_attr_time', 'is_addup',
                'ext_logistics_info', 'ext_trigger_info', 'is_lost_active_trigger', 'country_code',
                'expand_factor', 'product_category_id', 'version', 'max_sp', 'ext_plan_rebuild_vars'
        ]);
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row);
            $row['approve_state_text']   = isset(APPROVAL_STATE[$row['approve_state']]) ? APPROVAL_STATE[$row['approve_state']]['name'] : '-';
            $row['is_trigger_pr_text']   = isset(TRIGGER_PR[$row['is_trigger_pr']]) ? TRIGGER_PR[$row['is_trigger_pr']]['name'] : '-';
            $row['is_plan_approve_text'] = isset(NEED_PLAN_APPROVAL[$row['is_plan_approve']]) ? NEED_PLAN_APPROVAL[$row['is_plan_approve']]['name'] : '-';
            $row['expired_text']         = isset(FBA_PR_EXPIRED[$row['expired']]) ? FBA_PR_EXPIRED[$row['expired']]['name'] : '-';
            $row['station_code_text']    = isset(FBA_STATION_CODE[$row['station_code']]) ? FBA_STATION_CODE[$row['station_code']]['name'] : '-';
            $row['listing_state_text']   = isset(LISTING_STATE[$row['listing_state']]) ? LISTING_STATE[$row['listing_state']]['name'] : '-';
            $row['logistics_id_text']    = isset(LOGISTICS_ATTR[$row['logistics_id']]) ? LOGISTICS_ATTR[$row['logistics_id']]['name'] : '-';
            $row['is_refund_tax_text']   = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['purchase_warehouse_id_text']    = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
            $row['trigger_mode']         = isset(TRIGGER_MODE_STATE[$row['trigger_mode']]) ? TRIGGER_MODE_STATE[$row['trigger_mode']]['name'] : '-';
            //$row['is_boutique_text']     = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
            $row['sku_state_text']       = isset(SKU_STATE[$row['sku_state']]) ? SKU_STATE[$row['sku_state']]['name'] : '-';
            //新增状态
            $row['provider_status_text']     = isset(PROVIDER_STATUS[$row['provider_status']]) ? PROVIDER_STATUS[$row['provider_status']]['name'] : '-';
            $row['is_contraband_text']       = isset(CONTRABAND_STATE[$row['is_contraband']]) ? CONTRABAND_STATE[$row['is_contraband']]['name'] : '-';
            $row['is_first_sale_text']       = isset(FBA_FIRST_SALE_STATE[$row['is_first_sale']]) ? FBA_FIRST_SALE_STATE[$row['is_first_sale']]['name'] : '-';
            $row['is_accelerate_sale_text']  = isset(ACCELERATE_SALE_STATE[$row['is_accelerate_sale']]) ? ACCELERATE_SALE_STATE[$row['is_accelerate_sale']]['name'] : '-';
            $row['inventory_health_text']  = isset(INVENTORY_HEALTH_DESC[$row['inventory_health']]) ? INVENTORY_HEALTH_DESC[$row['inventory_health']]['name'] : '-';
            $row['product_status_text']       = isset(INLAND_SKU_ALL_STATE[$row['product_status']]) ? INLAND_SKU_ALL_STATE[$row['product_status']]['name'] : '-';

            $row['fixed_amount']       = ($row['fixed_amount'] == 0 ? 0 : ($row['fixed_amount'] > 0 ? '+' : '').$row['fixed_amount']);

            $row = array_diff_key($row, $hidden_cols);

        }
    }
}

if (!function_exists('tran_activity_list_result')) {
    function tran_activity_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,['updated_at', 'approved_at']);
            $row['approve_state_text']   = isset(ACTIVITY_APPROVAL_STATE[$row['approve_state']]) ? ACTIVITY_APPROVAL_STATE[$row['approve_state']]['name'] : '-';
//            $row['activity_state_text']   = isset(ACTIVITY_STATE[$row['activity_state']]) ? ACTIVITY_STATE[$row['activity_state']]['name'] : '-';
        }
    }
}

if (!function_exists('tran_manager_list_result')) {
    /**
     * 转换账号管理列表
     *
     * @param unknown $data_list
     * @return array
     */
    function tran_manager_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        //日期格式化
        $data_format_str = 'Y-m-d H:i:s';
        $init_date = '0000-00-00 00:00:00';

        foreach ($data_list as $key => &$row)
        {
            $row['merchant_id'] = substr_replace($row['merchant_id'], '****', 6);
            $row['secret_key'] = substr_replace($row['secret_key'], '********', 6);
            $row['aws_access_key_id'] = substr_replace($row['aws_access_key_id'], '********', 6);
            //$row['updated_at'] = $row['updated_at'] == '0000-00-00 00:00:00' ? '' : $row['updated_at'];
            data_format_filter($row,['updated_at']);
            $row['status_text'] = isset(FBA_ACCOUNT_STATUS[$row['status']])?FBA_ACCOUNT_STATUS[$row['status']]['name']:'-';
        }
    }
}

if (!function_exists('tran_track_list_result')) {

    function tran_track_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }

        //日期格式化
        $date_format = [
                'created_at',
                'updated_at',
        ];
        $data_format_str = 'Y-m-d H:i:s';

        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,['created_at','updated_at','push_time_logistics']);
            isset($row['approve_state']) && $row['approve_state_text']   = isset(APPROVAL_STATE[$row['approve_state']])?APPROVAL_STATE[$row['approve_state']]['name']:'-';
            isset($row['station_code']) && $row['station_code_text']    = isset(FBA_STATION_CODE[$row['station_code']])?FBA_STATION_CODE[$row['station_code']]['name'] : '-';
            if (isset($row['pur_sn']) && $row['pur_sn'] != '' && strlen($row['pur_sn']) > 2)
            {
                $row['pur_state_text'] = isset(PUR_STATE[$row['pur_state']]) ? PUR_STATE[$row['pur_state']]['name'] : '';
            }
            else
            {
                $row['pur_state_text']  = '';
                $row['pur_state'] = '';
            }
            $row['is_refund_tax_text'] = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
            $row['is_boutique_text']   = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
            $row['push_status_logistics_text']   = isset(BOUTIQUE_STATE[$row['push_status_logistics']]) ? BOUTIQUE_STATE[$row['push_status_logistics']]['name'] : '-';
            $row['is_first_sale_text'] = isset(FBA_FIRST_SALE_STATE[$row['is_first_sale']]) ? FBA_FIRST_SALE_STATE[$row['is_first_sale']]['name'] : '-';
        }
    }

}


if (!function_exists('tran_summary_list_result')) {

    function tran_summary_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }

        //日期格式化
        $date_format = [
                'created_at',
        ];
        $data_format_str = 'Y-m-d H:i:s';

        foreach ($data_list as $key => &$row)
        {
            $row['bussiness_line']       = 'FBA';
            $row['created_at']           = date($data_format_str, $row['created_at']);
            data_format_filter($row,['created_at']);
            $row['is_refund_tax_text'] = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
            $row['is_boutique_text']   = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
        }
    }

}

if (!function_exists('tran_promotion_sku_list_result')) {

    function tran_promotion_sku_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            $row['state_text'] = isset(PROMOTION_SKU_STATE[$row['state']]) ? PROMOTION_SKU_STATE[$row['state']]['name'] : '-';
        }
    }

}

if (!function_exists('tran_detail_result')) {
    /**
     * @param unknown $data_list
     * @return array
     */
    function tran_detail_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        $keep_cols = array_flip([
                'pr_sn', 'gid', 'approve_state_text', 'sku', 'expect_exhaust_date',
                'station_code_text', 'logistics_id_text', 'point_pcs', 'purchase_qty', 'fixed_amount',
                'require_qty', 'account_name', 'is_refund_tax_text', 'purchase_warehouse_id_text',
                'sku_name', 'is_boutique_text', 'country_code', 'country_name'
        ]);
        $data_list['approve_state_text']  = isset(APPROVAL_STATE[$data_list['approve_state']])?APPROVAL_STATE[$data_list['approve_state']]['name']:'-';
        $data_list['station_code_text']   = isset(FBA_STATION_CODE[$data_list['station_code']])?FBA_STATION_CODE[$data_list['station_code']]['name'] : $data_list['station_code'];
        $data_list['logistics_id_text']   = isset(LOGISTICS_ATTR[$data_list['logistics_id']])?LOGISTICS_ATTR[$data_list['logistics_id']]['name']:'-';
        $data_list['is_refund_tax_text'] = isset(REFUND_TAX[$data_list['is_refund_tax']]) ? REFUND_TAX[$data_list['is_refund_tax']]['name'] : '-';
        $data_list['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]['name'] : '-';
        $data_list['is_boutique_text']   = isset(BOUTIQUE_STATE[$data_list['is_boutique']]) ? BOUTIQUE_STATE[$data_list['is_boutique']]['name'] : '-';
        //$data_list['country_name'] = $data_list['country_name'] = empty($data_list['country_code']) ? ( FBA_STATION_CODE[strtolower($data_list['station_code'])]['name'] ?? '' ) : FBA_STATION_CODE[strtolower($data_list['country_code'])]['name'];

        $data_list = array_intersect_key($data_list, $keep_cols);
    }
}

if (!function_exists('tran_erp_sku_list_result')) {

    function tran_erp_sku_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            $row['check_state_cn'] = isset(CHECK_STATE[$row['state']]) ? CHECK_STATE[$row['state']]['name'] : '-';
            $row['provider_status_cn'] = isset(PROVIDER_STATUS[$row['provider_status']]) ? PROVIDER_STATUS[$row['provider_status']]['name'] : '-';
            $row['is_refund_tax_cn']   = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['is_boutique_cn']   = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
            $row['sku_state_cn']   = isset(SKU_STATE[$row['sku_state']]) ? SKU_STATE[$row['sku_state']]['name'] : '-';
            $row['is_contraband_cn']   = isset(CONTRABAND_STATE[$row['is_contraband']]) ? CONTRABAND_STATE[$row['is_contraband']]['name'] : '-';
            $row['product_status_text'] = isset(INLAND_SKU_ALL_STATE[$row['product_status']]) ? INLAND_SKU_ALL_STATE[$row['product_status']]['name'] : '-';
        }
    }

}
if (!function_exists('tran_erp_sku_detail_result')) {

    function tran_erp_sku_detail_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }

        //日期格式化
        $date_format = [
            'created_at',
            'updated_at',
        ];
        $data_format_str = 'Y-m-d H:i:s';

        $data_list['check_state_cn'] = isset(CHECK_STATE[$data_list['state']]) ? CHECK_STATE[$data_list['state']]['name'] : '-';
        $data_list['provider_status_cn'] = isset(PROVIDER_STATUS[$data_list['provider_status']]) ? PROVIDER_STATUS[$data_list['provider_status']]['name'] : '-';
        $data_list['is_refund_tax_cn']   = isset(REFUND_TAX[$data_list['is_refund_tax']]) ? REFUND_TAX[$data_list['is_refund_tax']]['name'] : '-';
        $data_list['is_boutique_cn']   = isset(BOUTIQUE_STATE[$data_list['is_boutique']]) ? BOUTIQUE_STATE[$data_list['is_boutique']]['name'] : '-';
        $data_list['sku_state_cn']   = isset(SKU_STATE[$data_list['sku_state']]) ? SKU_STATE[$data_list['sku_state']]['name'] : '-';
        $data_list['is_contraband_cn']   = isset(CONTRABAND_STATE[$data_list['is_contraband']]) ? CONTRABAND_STATE[$data_list['is_contraband']]['name'] : '-';

    }

}

if (!function_exists('tran_new_list_result')) {
    function tran_new_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,['updated_at', 'approved_at']);
            $row['approve_state_text'] = isset(NEW_APPROVAL_STATE[$row['approve_state']]) ? NEW_APPROVAL_STATE[$row['approve_state']]['name'] : '-';
        }
    }
}