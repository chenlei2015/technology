<?php

/**
 * 转换list的数据格式
 */
function tran_oversea_list_result(&$data_list)
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
        data_format_filter($row);
        //$row['created_at']           = is_numeric($row['created_at']) ? date($data_format_str, $row['created_at']) : $row['created_at'];
        //$row['updated_at']           = intval($row['updated_at']) > 0 ? date($data_format_str, $row['updated_at']) : '';
        $row['approve_state_text']   = isset(APPROVAL_STATE[$row['approve_state']]) ? APPROVAL_STATE[$row['approve_state']]['name'] : '-';
        $row['is_trigger_pr_text']   = TRIGGER_PR[$row['is_trigger_pr']]['name'];
        $row['is_plan_approve_text'] = NEED_PLAN_APPROVAL[$row['is_plan_approve']]['name'];
        $row['sku_state_text']       = isset(SKU_STATE[$row['sku_state']]) ? SKU_STATE[$row['sku_state']]['name'] : '-';
        $row['product_status_text']       = isset(PRODUCT_STATUS_ALL[$row['product_status']]) ? PRODUCT_STATUS_ALL[$row['product_status']]['name'] : '-';
        $row['expired_text']         = FBA_PR_EXPIRED[$row['expired']]['name'];
        $row['station_code']         = isset(OVERSEA_STATION_CODE[$row['station_code']]) ? OVERSEA_STATION_CODE[$row['station_code']]['name'] : '-';
        $row['logistics_id_text']    = isset(LOGISTICS_ATTR[$row['logistics_id']]) ? LOGISTICS_ATTR[$row['logistics_id']]['name'] : '-';
        $row['is_refund_tax_text']    = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
        $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
        $row['is_boutique_text']   = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
    }
}

/**
 * 转换list的数据格式
 */
function tran_oversea_platform_list_result(&$data_list)
{
    if (empty($data_list))
    {
        return [];
    }
    
    foreach ($data_list as $key => &$row)
    {
        data_format_filter($row);
        $row['approve_state_text']   = isset(OVERSEA_PLATFROM_APPROVAL_STATE[$row['approve_state']]) ? OVERSEA_PLATFROM_APPROVAL_STATE[$row['approve_state']]['name'] : '-';
        $row['sku_state_text']       = isset(SKU_STATE[$row['sku_state']]) ? SKU_STATE[$row['sku_state']]['name'] : '-';
        $row['product_status_text']       = isset(PRODUCT_STATUS_ALL[$row['product_status']]) ? PRODUCT_STATUS_ALL[$row['product_status']]['name'] : '-';
        $row['expired_text']         = FBA_PR_EXPIRED[$row['expired']]['name'];
        $row['station_code']         = isset(OVERSEA_STATION_CODE[$row['station_code']]) ? OVERSEA_STATION_CODE[$row['station_code']]['name'] : '-';
        $row['platform_code']        = strtoupper($row['platform_code'] ?? '');
        $row['platform_code']         = isset(INLAND_PLATFORM_CODE[$row['platform_code']]) ? INLAND_PLATFORM_CODE[$row['platform_code']]['name'] : '-';
        $row['logistics_id_text']    = isset(LOGISTICS_ATTR[$row['logistics_id']]) ? LOGISTICS_ATTR[$row['logistics_id']]['name'] : '-';
        $row['is_refund_tax_text']    = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
        $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
        $row['is_boutique_text']   = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
    }
}


function tran_oversea_track_list_result(&$data_list)
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
        $row['station_code']         = isset(OVERSEA_STATION_CODE[$row['station_code']]) ? OVERSEA_STATION_CODE[$row['station_code']]['name'] : '-';
        if (isset($row['pur_sn']) && $row['pur_sn'] != '' && strlen($row['pur_sn']) > 2)
        {
            $row['pur_state_text'] = isset(PUR_STATE[$row['pur_state']]) ? PUR_STATE[$row['pur_state']]['name'] : '';
        }
        else
        {
            $row['pur_state_text']  = '';
            $row['pur_state'] = '';
        }
        $row['is_refund_tax_text']    = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
        $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
        $row['is_boutique_text']   = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
        $row['push_status_logistics_text']   = isset(BOUTIQUE_STATE[$row['push_status_logistics']]) ? BOUTIQUE_STATE[$row['push_status_logistics']]['name'] : '-';
    }
}

function tran_oversea_summary_list_result(&$data_list)
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
        $row['bussiness_line']       = '海外仓';
        data_format_filter($row,['created_at']);
        $row['is_refund_tax_text']    = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
        $row['purchase_warehouse_id_text']    = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
        $row['approve_state_text']   = isset(OVERSEA_SUMMARY_APPROVAL_STATE[$row['approve_state']]) ? OVERSEA_SUMMARY_APPROVAL_STATE[$row['approve_state']]['name'] : '-';
        $row['is_boutique_text']   = isset(BOUTIQUE_STATE[$row['is_boutique']]) ? BOUTIQUE_STATE[$row['is_boutique']]['name'] : '-';
    }
}

if (!function_exists('tran_oversea_detail_result')) {
    /**
     * @param unknown $data_list
     * @return array
     */
    function tran_oversea_detail_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        $keep_cols = array_flip([
                'gid', 'approve_state_text', 'sku', 'expect_exhaust_date',
                'station_code_text', 'logistics_id_text', 'point_pcs', 'purchase_qty', 'bd',
                'require_qty', 'pr_sn', 'is_refund_tax_text', 'purchase_warehouse_id_text',
                'sku_name', 'is_boutique_text'
        ]);
        $data_list['approve_state_text']   = APPROVAL_STATE[$data_list['approve_state']]['name'];
        $data_list['station_code_text']   = OVERSEA_STATION_CODE[$data_list['station_code']]['name'] ?? $data_list['station_code'];
        $data_list['logistics_id_text']   = isset(LOGISTICS_ATTR[$data_list['logistics_id']])?LOGISTICS_ATTR[$data_list['logistics_id']]['name']:'-';
        $data_list['is_refund_tax_text'] = isset(REFUND_TAX[$data_list['is_refund_tax']]) ? REFUND_TAX[$data_list['is_refund_tax']]['name'] : '-';
        $data_list['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]['name'] : '-';
        $data_list['is_boutique_text']   = isset(BOUTIQUE_STATE[$data_list['is_boutique']]) ? BOUTIQUE_STATE[$data_list['is_boutique']]['name'] : '-';
        
        $data_list = array_intersect_key($data_list, $keep_cols);
    }
}


if (!function_exists('oversea_cfg_list_result')) {
    /**
     * 转换list的数据格式
     */
    function oversea_cfg_list_result(&$data_list)
    {
        if (empty($data_list)) {
            return [];
        }


        //在库+在途预计可售卖天数
        if (isset($data_list['supply_day_cfg'])) {
            foreach ($data_list['supply_day_cfg'] as $key => $item) {
                $data_list['supply_day_cfg'][$key]['cfg_range'] = sprintf('%s<=在库+在途预计可售卖天数<%s', $item[0]??'', $item[1]??'');
                $data_list['supply_day_cfg'][$key]['factor'] = $item[2]??'';//可售卖天数系数
            }
        }

        //断货天数
        if (isset($data_list['exhaust_cfg'])) {
            foreach ($data_list['exhaust_cfg'] as $key => $item) {
                $data_list['exhaust_cfg'][$key]['cfg_range'] = sprintf('%s--%s', $item[0]??'', $item[1]??'');
                $data_list['exhaust_cfg'][$key]['factor'] = $item[2]??'';        //断货系数
            }
        }

        //销量
        if (isset($data_list['sales_amount_cfg'])) {
            foreach ($data_list['sales_amount_cfg'] as $key => $item) {
                $day = array_keys($item['cfg']??'');
                sort($day);
                $data_list['sales_amount_cfg'][$key]['cfg_range'] = sprintf('%s天%s%s;%s天%s%s', $day[0]??'', $item['cfg'][$day[0]][0]??'',$item['cfg'][$day[0]][1]??'',$day[1]??'',$item['cfg'][$day[1]][0]??'',$item['cfg'][$day[1]][1]??'');
                $data_list['sales_amount_cfg'][$key]['factor'] = $item['factor']??'';        //订单系数
            }
        }

        //是否有超90天库龄
        if (isset($data_list['in_warehouse_age_cfg'])) {
            foreach ($data_list['in_warehouse_age_cfg'] as $key => $item) {
                if ($item[0]==1){
                    $data_list['in_warehouse_age_cfg'][$key]['cfg_range'] = sprintf('%s', '是');
                }elseif ($item[0]==2){
                    $data_list['in_warehouse_age_cfg'][$key]['cfg_range'] = sprintf('%s', '否');
                }
                $data_list['in_warehouse_age_cfg'][$key]['factor'] = $item[1]??'';        //超90天库龄系数
            }
        }

        //销量分类
        if (isset($data_list['sale_category_cfg'])) {
            foreach ($data_list['sale_category_cfg'] as $key => $item) {
                $s1 = '<=';
                $s2 = '<';
                $type = '';
                if ($item[0] === '1.0E-7'){
                    $s1 = '<';
                    $item[0] = 0;
                }
                if ($item[0]>=0.1 && $item[1]<=0.3){
                    $type = '弱销';
                }
                if ($item[0]>=0.3 && $item[1]<=0.6){
                    $type = '低销';
                }
                if ($item[0]>=0.6 && $item[1]<=1){
                    $type = '平销';
                }
                if ($item[0]>=1 && $item[1]<=3){
                    $type = '中销';
                }
                if ($item[0]>=3 && $item[1]<=5){
                    $type = '高销';
                }
                if ($item[0]>=5 && $item[1]<=10){
                    $type = '畅销';
                }
                if ($item[0]>=10 && $item[1]<=20){
                    $type = '热销';
                }
                if ($item[0]>=20){
                    $type = '爆款';
                }
                $data_list['sale_category_cfg'][$key]['cfg_range'] = sprintf('%s(%s%s日均销量%s%s)',$type,$item[0],$s1,$s2,$item[1]);
                if ($item[1] === '1.0E-7'){
                    $data_list['sale_category_cfg'][$key]['cfg_range'] = '日均销量=0';
                }
                if ($item[0] >= 20){
                    $data_list['sale_category_cfg'][$key]['cfg_range'] = sprintf('爆款(日均销量>=%s)',$item[0]);
                }
                $data_list['sale_category_cfg'][$key]['sz'] = $item[2]??'';       //Z值
                $data_list['sale_category_cfg'][$key]['sc'] = $item[3]??'';         //一次备货天数
                $data_list['sale_category_cfg'][$key]['expand_factor'] = $item[4]??'';       //扩销系数
                $data_list['sale_category_cfg'][$key]['day_sale_factor'] = $item[5]??'';       //日均销量扩销系数
                $data_list['sale_category_cfg'][$key]['bs'] = $item[6]??'';       //缓冲天数
                $data_list['sale_category_cfg'][$key]['safe_day'] = $item[7]??'';       //安全天数
                $data_list['sale_category_cfg'][$key]['subscription_day'] = $item[8]??'';       //起订阈值天数
            }
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
            $row['station_code_text']   = isset(OVERSEA_STATION_CODE[$row['station_code']]) ? OVERSEA_STATION_CODE[$row['station_code']]['name'] : '-'; //站点
//            $row['activity_state_text']   = isset(ACTIVITY_STATE[$row['activity_state']]) ? ACTIVITY_STATE[$row['activity_state']]['name'] : '-';
        }
    }
}