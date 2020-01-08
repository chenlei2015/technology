<?php

/**
 * 转换list的数据格式
 */
function tran_inland_list_result(&$data_list)
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
        data_format_filter($row);
        $row['is_refund_tax_text']    = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
        $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
        $row['sku_state_text']       = isset(SKU_STATE[$row['sku_state']]) ? SKU_STATE[$row['sku_state']]['name'] : '-';
        $row['stock_up_type_text']   = isset(STOCK_UP_TYPE[$row['stock_up_type']]) ? STOCK_UP_TYPE[$row['stock_up_type']]['name'] : '-';
        $row['is_trigger_pr_text']   = TRIGGER_PR[$row['is_trigger_pr']]['name'];
        $row['expired_text']         = FBA_PR_EXPIRED[$row['expired']]['name'];
        $row['approve_state_text']       = isset(INLAND_APPROVAL_STATE[$row['approve_state']]) ? INLAND_APPROVAL_STATE[$row['approve_state']]['name'] : '-';
    }
}

function tran_inland_track_list_result(&$data_list)
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
        data_format_filter($row,['created_at','updated_at']);
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
    }
}

function tran_inland_summary_list_result(&$data_list)
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
        $row['bussiness_line']       = '国内';
        data_format_filter($row,['created_at']);
        $row['is_refund_tax_text']    = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
        $row['purchase_warehouse_id_text']    = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
    }
}

if (!function_exists('tran_inland_detail_result')) {
    function tran_inland_detail_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        $keep_cols = array_flip([
            'pr_sn', 'gid', 'sku', 'sku_name', 'approve_state_text','fixed_amount',
            'require_qty', 'calc_start_date', 'calc_end_date', 'requisition_zh_name'
        ]);

        $data_list['is_refund_tax_text']    = isset(REFUND_TAX[$data_list['is_refund_tax']]) ? REFUND_TAX[$data_list['is_refund_tax']]['name'] : '-';
        $data_list['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]['name'] : '-';
        $data_list['approve_state_text'] = isset(SPECIAL_CHECK_STATE[$data_list['approve_state']])? SPECIAL_CHECK_STATE[$data_list['approve_state']]['name']:'-';

        $data_list = array_intersect_key($data_list, $keep_cols);
    }
}

/**
 * 转换list的数据格式
 */
function tran_inland_special_list_result(&$data_list)
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
        data_format_filter($row);
        $row['is_refund_tax_text']    = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
        $row['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
        $row['approve_state_text'] = isset(SPECIAL_CHECK_STATE[$row['approve_state']]) ? SPECIAL_CHECK_STATE[$row['approve_state']]['name'] : '-';
        $row['is_sku_match_text'] = isset(SKU_MATCH_STATE[$row['is_sku_match']]) ? SKU_MATCH_STATE[$row['is_sku_match']]['name'] : '-';
    }
}


if (!function_exists('tran_operation_cfg_result')) {
    /**
     * @param unknown $data_list
     * @return array
     */
    function tran_operation_cfg_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row) {
            data_format_filter($row, ['created_at', 'updated_at']);
        }
//        $keep_cols = array_flip([
//            'gid', 'sku', 'sku_name', 'expect_exhaust_date','point_pcs', 'purchase_qty',
//            'require_qty', 'pr_sn', 'is_refund_tax_text', 'purchase_warehouse_id_text',
//            'sku_state_text', 'stock_up_type_text', 'is_trigger_pr_text', 'expired_text'
//        ]);
//
//        $data_list['is_refund_tax_text']    = isset(REFUND_TAX[$data_list['is_refund_tax']]) ? REFUND_TAX[$data_list['is_refund_tax']]['name'] : '-';
//        $data_list['purchase_warehouse_id_text'] = isset(PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]['name'] : '-';
//        $data_list['sku_state_text']       = isset(INLAND_SKU_STATE[$data_list['sku_state']]) ? INLAND_SKU_STATE[$data_list['sku_state']]['name'] : '-';
//        $data_list['stock_up_type_text']   = isset(STOCK_UP_TYPE[$data_list['stock_up_type']]) ? STOCK_UP_TYPE[$data_list['stock_up_type']]['name'] : '-';
//        $data_list['is_trigger_pr_text']   = TRIGGER_PR[$data_list['is_trigger_pr']]['name'];
//        $data_list['expired_text']         = FBA_PR_EXPIRED[$data_list['expired']]['name'];
//
//        $data_list = array_intersect_key($data_list, $keep_cols);
    }
}

if (!function_exists('tran_inventory_list_result')) {
    /**
     * @param unknown $data_list
     * @return array
     */
    function tran_inventory_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row);
            $row['sku_state_cn']    = isset(INLAND_SKU_ALL_STATE[$row['sku_state']]) ? INLAND_SKU_ALL_STATE[$row['sku_state']]['name'] : '-';
        }
    }
}

if (!function_exists('tran_stock_cfg_list_result')) {
    /**
     * @param unknown $data_list
     * @return array
     */
    function tran_stock_cfg_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {

            data_format_filter($row,['created_at', 'updated_at','approved_at','deved_time','published_time']);
            $row['rule_type_cn']    = isset(RULE_TYPE[$row['rule_type']]) ? RULE_TYPE[$row['rule_type']]['name'] : '-';
            $row['state_cn']    = isset(CHECK_STATE[$row['state']]) ? CHECK_STATE[$row['state']]['name'] : '-';
            $row['is_refund_tax_cn']    = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['purchase_warehouse_id_cn']    = isset(PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['purchase_warehouse_id']]['name'] : '-';
            $row['provider_status_cn']    = isset(PROVIDER_STATUS[$row['provider_status']]) ? PROVIDER_STATUS[$row['provider_status']]['name'] : '-';
            $row['sku_state_cn']    = isset(SKU_STATE[$row['sku_state']]) ? SKU_STATE[$row['sku_state']]['name'] : '-';
            $row['stock_way_cn']    = isset(STOCK_UP_TYPE[$row['stock_way']]) ? STOCK_UP_TYPE[$row['stock_way']]['name'] : '-';
            $row['quality_goods_cn']    = isset(INLAND_QUALITY_GOODS[$row['quality_goods']]) ? INLAND_QUALITY_GOODS[$row['quality_goods']]['name'] : '-';
            $row['product_status_cn']    = isset(PRODUCT_STATUS_ALL[$row['product_status']]) ? PRODUCT_STATUS_ALL[$row['product_status']]['name'] : '-';
        }
    }
}


if (!function_exists('tran_stock_cfg_detail_result')) {
    /**
     * @param unknown $data_list
     * @return array
     */
    function tran_stock_cfg_detail_result(&$data_list)
    {

        if (empty($data_list))
        {
            return [];
        }
//        $keep_cols = array_flip([
//            'gid','rule_type','state','sku','sku_name','path_name_first','is_refund_tax','purchase_warehouse_id',
//            'provider_status','sku_state','quality_goods','stock_way','bs','sp','shipment_time','first_lt',
//            'sc','sz','deved_time','published_time','created_at','approved_at','updated_at','updated_uid','updated_zh_name'
//
//        ]);
        data_format_filter($row,['created_at', 'updated_at']);
        $data_list['rule_type_cn']    = isset(RULE_TYPE[$data_list['rule_type']]) ? RULE_TYPE[$data_list['rule_type']]['name'] : '-';
        $data_list['state_cn']    = isset(CHECK_STATE[$data_list['state']]) ? CHECK_STATE[$data_list['state']]['name'] : '-';
        $data_list['is_refund_tax_cn']    = isset(REFUND_TAX[$data_list['is_refund_tax']]) ? REFUND_TAX[$data_list['is_refund_tax']]['name'] : '-';
        $data_list['purchase_warehouse_id_cn']    = isset(PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]) ? PURCHASE_WAREHOUSE[$data_list['purchase_warehouse_id']]['name'] : '-';
        $data_list['provider_status_cn']    = isset(PROVIDER_STATUS[$data_list['provider_status']]) ? PROVIDER_STATUS[$data_list['provider_status']]['name'] : '-';
        $data_list['sku_state_cn']    = isset(SKU_STATE[$data_list['sku_state']]) ? SKU_STATE[$data_list['sku_state']]['name'] : '-';
        $data_list['stock_way_cn']    = isset(STOCK_UP_TYPE[$data_list['stock_way']]) ? STOCK_UP_TYPE[$data_list['stock_way']]['name'] : '-';

//        $data_list = array_intersect_key($data_list, $keep_cols);

    }
}



if (!function_exists('inland_cfg_list_result')) {
    /**
     * 转换list的数据格式
     */
    function inland_cfg_list_result(&$data_list)
    {
        if (empty($data_list)) {
            return [];
        }

        //在库+在途预计可售卖天数
        if (isset($data_list['supply_day_cfg'])) {
            foreach ($data_list['supply_day_cfg'] as $key => $item) {
                if ($item[1] == 'max') {
                    $data_list['supply_day_cfg'][$key]['cfg_range'] = sprintf('在库+在途预计可售卖天数>=%s', $item[0]);
                } else {
                    $data_list['supply_day_cfg'][$key]['cfg_range'] = sprintf('%s<=在库+在途预计可售卖天数<=%s', $item[0], strval($item[1]+1));
                }

                $data_list['supply_day_cfg'][$key]['factor'] = strval($item[2]??'');//可售卖天数系数
            }
        }

        //断货天数
        if (isset($data_list['exhaust_cfg'])) {
            foreach ($data_list['exhaust_cfg'] as $key => $item) {
                $data_list['exhaust_cfg'][$key]['cfg_range'] = sprintf('%s--%s', $item[0]??'', $item[1]??'');
                $data_list['exhaust_cfg'][$key]['factor'] = strval($item[2]??'');        //断货系数
            }
        }

        //销量
        if (isset($data_list['sales_amount_cfg'])) {
            foreach ($data_list['sales_amount_cfg'] as $key => $item) {
                $day = array_keys($item['cfg']??'');
                sort($day);
                $data_list['sales_amount_cfg'][$key]['cfg_range'] = sprintf('%s天%s%s;%s天%s%s', $day[0]??'', $item['cfg'][$day[0]][0]??'',$item['cfg'][$day[0]][1]??'',$day[1]??'',$item['cfg'][$day[1]][0]??'',$item['cfg'][$day[1]][1]??'');
                $data_list['sales_amount_cfg'][$key]['factor'] = strval($item['factor']??'');        //订单系数
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
                $data_list['in_warehouse_age_cfg'][$key]['factor'] = strval($item[1]??'');        //超90天库龄系数
            }
        }

        //销量分类
        if (isset($data_list['sale_category_cfg'])) {
            foreach ($data_list['sale_category_cfg'] as $key => $item) {
                $s1 = '<=';
                $s2 = '<';
                $type = '';
                if (bccomp($item[0], 0, 6) === 0){
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
                if (bccomp($item[0], 0, 6) === 0){
                    $data_list['sale_category_cfg'][$key]['cfg_range'] = '日均销量=0';
                }
                if ($item[0] >= 20){
                    $data_list['sale_category_cfg'][$key]['cfg_range'] = sprintf('爆款(日均销量>=%s)',$item[0]);
                }
                $item[0] = strval($item[0]);
                $item[1] = strval($item[1]);
                $data_list['sale_category_cfg'][$key]['sz'] = strval($item[2]??'');       //Z值
                $data_list['sale_category_cfg'][$key]['sc'] = strval($item[3]??'');       //一次发运天数
                $data_list['sale_category_cfg'][$key]['expand_factor'] = strval($item[4]??'');       //扩销系数
                $data_list['sale_category_cfg'][$key]['day_sale_factor'] = strval($item[5]??'');       //日均销量扩销系数
                $data_list['sale_category_cfg'][$key]['max_sp'] = strval($item[6]??'');       //最大额外备货天数
                $data_list['sale_category_cfg'][$key]['subscription_day'] = $item[7]??'';       //起订阈值天数
            }
        }

        if (isset($data_list['global_cfg'])) {
            $cols = ['ship_min_weight', 'ship_min_amount', 'ship_max_extend_amount'];
            $tmp = [];
            foreach ($cols as $key => $col) {
                $tmp[$col] = $data_list['global_cfg']['config_1'][$key];
            }
            unset($data_list['global_cfg']['config_1']);
            $data_list['global_cfg'] = [ '0' => $tmp];

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
}