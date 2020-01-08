<?php

if (!function_exists('rebuild_cfg_list_result')) {
    /**
     * 转换list的数据格式
     */
    function rebuild_cfg_list_result(&$data_list)
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
                $data_list['sale_category_cfg'][$key]['subscription_day'] = $item[5]??'';       //起订阈值天数
            }
        }
    }
}
