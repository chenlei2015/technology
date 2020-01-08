<?php

if (!function_exists('tran_detail_result')) {
    /**
     * 转换list的数据格式
     */
    function tran_detail_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        $data_format_str = 'Y-m-d H:i:s';

        foreach ($data_list as $key => &$row)
        {
            $row['bussiness_line_text'] = BUSSINESS_LINE[$row['bussiness_line']]['name'];
            $row['data_privilege_text']   = DATA_PRIVILEGE[$row['data_privilege']]['name'];
            $row['has_first_text'] = GLOBAL_YES == $row['has_first'] ? '一级权限' : '';
            $row['has_second_text'] = GLOBAL_YES == $row['has_second'] ? '二级权限' : '';
            $row['has_three_text'] = GLOBAL_YES == $row['has_three'] ? '三级权限' : '';
        }
    }
}
