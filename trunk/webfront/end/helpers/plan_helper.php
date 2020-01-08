<?php

if (!function_exists('tran_list_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_plan_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,['created_at','updated_at']);
            $row['bussiness_line_text']        = isset(BUSSINESS_LINE[$row['bussiness_line']])?BUSSINESS_LINE[$row['bussiness_line']]['name']:'-';
            $row['pur_sn_state_text']          = isset(PUR_STATE[$row['state']])?PUR_STATE[$row['state']]['name']:'-';
            $row['is_pushed_text']             = isset(PUR_DATA_STATE[$row['is_pushed']])?PUR_DATA_STATE[$row['is_pushed']]['name']:'-';
            $row['is_refund_tax_text']         = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
            $row['sku_state_text']             = isset(SKU_STATE[$row['sku_state']]) ? SKU_STATE[$row['sku_state']]['name'] : '-';//产品状态
            $row['product_status_text']        = isset(PRODUCT_STATUS_ALL[$row['product_status']]) ? PRODUCT_STATUS_ALL[$row['product_status']]['name'] : '-';//产品状态
            $row['is_boutique_text']           = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
        }
    }
}

if (!function_exists('tran_plan_allot_list_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_plan_allot_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,['created_at','updated_at']);
            $row['pur_sn_state_text']          = isset(PUR_STATE[$row['state']])?PUR_STATE[$row['state']]['name']:'-';
            $row['in_warehouse_text']          = isset(ALLOT_VM_WAREHOUSE[$row['in_warehouse']])?ALLOT_VM_WAREHOUSE[$row['in_warehouse']]['name']:'-';
            $row['out_warehouse_text']         = isset(ALLOT_VM_WAREHOUSE[$row['out_warehouse']])?ALLOT_VM_WAREHOUSE[$row['out_warehouse']]['name']:'-';
            $row['is_refund_tax_text']         = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
        }
    }
}

if (!function_exists('tran_plan_summary_list_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_plan_summary_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        $data_format_str = 'Y-m-d H:i:s';
        
        foreach ($data_list as $key => &$row)
        {
            $row['bussiness_line_text'] = isset(BUSSINESS_LINE[$row['bussiness_line']])?BUSSINESS_LINE[$row['bussiness_line']]['name']:'-';
            //$row['created_at']          = date($data_format_str, $row['created_at']);
            //$row['updated_at']          = date($data_format_str, $row['updated_at']);
            data_format_filter($row,['created_at','updated_at']);
        }
    }
}

if (!function_exists('tran_plan_track_list_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_plan_track_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }

        foreach ($data_list as $key => &$row)
        {
            $row['bussiness_line_text']        = isset(BUSSINESS_LINE[$row['bussiness_line']])?BUSSINESS_LINE[$row['bussiness_line']]['name']:'-';
            $row['po_state_text']              = isset(PURCHASE_ORDER_STATUS[$row['po_state']])?PURCHASE_ORDER_STATUS[$row['po_state']]['name']:'-';
            $row['is_refund_tax_text']         = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
            $row['product_status_text']        = isset(PLAN_PRODUCT_STATUS[$row['product_status']]) ? PLAN_PRODUCT_STATUS[$row['product_status']]['name'] : '-';//产品状态
            $row['shipment_type_text']         = isset(SHIPMENT_TYPE_LIST[$row['shipment_type']]) ? SHIPMENT_TYPE_LIST[$row['shipment_type']]['name'] : '-';
            $row['is_boutique_text']           = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
            data_format_filter($row,['created_at','updated_at','earliest_exhaust_date','expect_arrived_date','earliest_generate_time']);

            if (empty($row['po_state']) && (empty($row['po_qty']) || $row['po_qty']=='0')){
                $row['po_qty'] = '';
            }
        }
    }
}

/**
 * 转换list的数据格式
 */
if (!function_exists('tran_push_erp_result_list_result'))
{
    function tran_push_erp_result_list_result(&$data_list){
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row){
            $row['push_status_cn'] = isset(PUSH_STATUS[$row['push_status']])?PUSH_STATUS[$row['push_status']]['name']:"-";
        }
    }
}

/**
 * 转换list的数据格式
 */
if (!function_exists('tran_real_allot_result_list'))
{
    function tran_real_allot_result_list(&$data_list){
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row){
            $row['allot_status_cn'] = isset(ALLOT_STATUS[$row['allot_status']])?ALLOT_STATUS[$row['allot_status']]['name']:"-";
        }
    }
}
