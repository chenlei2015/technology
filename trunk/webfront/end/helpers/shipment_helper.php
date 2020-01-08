<?php

if (!function_exists('tran_shipment_list_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_shipment_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }

        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,['created_at','updated_at','push_time']);
            $row['push_status_text']    = isset(SHIPMENT_STATE[$row['push_status']]) ? SHIPMENT_STATE[$row['push_status']]['name'] : '-';
        }
    }
}

if (!function_exists('tran_shipment_fba_list_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_shipment_fba_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,['created_at','updated_at']);
            $row['business_type_text'] = isset(BUSINESS_TYPE[$row['business_type']]) ? BUSINESS_TYPE[$row['business_type']]['name'] : '-';
            $row['station_code_text']    = isset(FBA_STATION_CODE[$row['station_code']]) ? FBA_STATION_CODE[$row['station_code']]['name'] : '-';
            $row['logistics_id_text']    = isset(FBA_LOGISTICS_ATTR[$row['logistics_id']]) ? FBA_LOGISTICS_ATTR[$row['logistics_id']]['name'] : '-';
            $row['is_refund_tax_text']   = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['warehouse_id_text']    = isset(PURCHASE_WAREHOUSE[$row['warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['warehouse_id']]['name'] : '-';
            $row['shipment_status_text']    = isset(SHIPMENT_STATUS[$row['shipment_status']]) ? SHIPMENT_STATUS[$row['shipment_status']]['name'] : '-';
            $row['shipment_type_text']    = isset(SHIPMENT_TYPE_LIST[$row['shipment_type']]) ? SHIPMENT_TYPE_LIST[$row['shipment_type']]['name'] : '-';
            $row['is_inspection_text']    = isset(INSPECTION_STATE[$row['is_inspection']]) ? INSPECTION_STATE[$row['is_inspection']]['name'] : '-';
            $row['is_fumigation_text']    = isset(FUMIGATION_STATE[$row['is_fumigation']]) ? FUMIGATION_STATE[$row['is_fumigation']]['name'] : '-';
        }
    }
}

if (!function_exists('tran_shipment_oversea_list_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_shipment_oversea_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,['created_at','updated_at']);
            $row['business_type_text']   = isset(BUSINESS_TYPE[$row['business_type']]) ? BUSINESS_TYPE[$row['business_type']]['name'] : '-';
            $row['station_code_text']    = isset(OVERSEA_STATION_CODE[$row['station_code']]) ? OVERSEA_STATION_CODE[$row['station_code']]['name'] : '-';
            $row['logistics_id_text']    = isset(LOGISTICS_ATTR[$row['logistics_id']]) ? LOGISTICS_ATTR[$row['logistics_id']]['name'] : '-';
            $row['is_refund_tax_text']   = isset(REFUND_TAX[$row['is_refund_tax']]) ? REFUND_TAX[$row['is_refund_tax']]['name'] : '-';
            $row['warehouse_id_text']    = isset(PURCHASE_WAREHOUSE[$row['warehouse_id']]) ? PURCHASE_WAREHOUSE[$row['warehouse_id']]['name'] : '-';
            $row['shipment_status_text']    = isset(SHIPMENT_STATUS[$row['shipment_status']]) ? SHIPMENT_STATUS[$row['shipment_status']]['name'] : '-';
            $row['shipment_type_text']    = isset(SHIPMENT_TYPE_LIST[$row['shipment_type']]) ? SHIPMENT_TYPE_LIST[$row['shipment_type']]['name'] : '-';
            $row['is_inspection_text']    = isset(INSPECTION_STATE[$row['is_inspection']]) ? INSPECTION_STATE[$row['is_inspection']]['name'] : '-';
            $row['is_fumigation_text']    = isset(FUMIGATION_STATE[$row['is_fumigation']]) ? FUMIGATION_STATE[$row['is_fumigation']]['name'] : '-';
        }
    }
}

if (!function_exists('tran_sp_info_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_sp_info_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        data_format_filter($data_list['created_at'],['created_at']);
        $data_list['push_status_text'] = SHIPMENT_STATE[$data_list['push_status']]['name']??'-';
    }
}
if (!function_exists('tran_oversea_tracking_detail_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_oversea_tracking_detail_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row) {
            $row['logistics_status_text']    = isset(LOGISTICS_STATUS[$row['logistics_status']]) ? LOGISTICS_STATUS[$row['logistics_status']]['name'] : '-';
        }
    }
}

if (!function_exists('tran_fba_tracking_detail_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_fba_tracking_detail_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row) {
            $row['logistics_status_text']    = isset(LOGISTICS_STATUS[$row['logistics_status']]) ? LOGISTICS_STATUS[$row['logistics_status']]['name'] : '-';
        }
    }
}


if (!function_exists('shipment_cfg_list_result')) {
    /**
     * 转换list的数据格式
     */
    function shipment_cfg_list_result(&$data_list)
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
            }
        }

        if (isset($data_list['global_cfg'])) {
            $cols = ['ship_min_weight', 'ship_min_amount', 'ship_max_extend_amount'];
            $tmp1 = [];
            foreach ($cols as $key => $col) {
                $tmp1[$col] = $data_list['global_cfg']['config_1'][$key];
            }

            //最大安全天数和缓冲库存
            $safe_buffer_config = $data_list['global_cfg']['config_2'];
            unset($data_list['global_cfg']);
            $data_list['global_cfg'] = [
                    '0' => $tmp1
            ];
            $data_list['bs_cfg'] = ['0' => ['bs' => $safe_buffer_config[0]]];
            $data_list['max_safe_stock_day_cfg'] = [ '0' => ['max_safe_stock_day' => $safe_buffer_config[1]]];
        }
    }
}


