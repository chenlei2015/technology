<?php

if (!function_exists('tran_download_list_result')) {
    
    /**
     * 转换list的数据格式
     */
    function tran_download_list_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        
        foreach ($data_list as $key => &$row)
        {
            $row['created_date'] = str_replace('-', '/', $row['created_date']);
            $row['data_type'] = isset(MRP_DOWNLOAD_DATA_TYPE[$row['data_type']]) ? MRP_DOWNLOAD_DATA_TYPE[$row['data_type']]['name'] : '-';
        }
    }
}
