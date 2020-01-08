<?php


if (!function_exists('get_oversea_upload_path')) {

    /**
     * 获取sgs的上传路径
     */
    function get_oversea_upload_path()
    {
        return get_upload_path() . '_oversea'.DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('is_valid_oversea_station_code')) {
    /**
     * 是否是可搜索的预测单号. 支持,分割的单号
     *
     * @param unknown $search_sn
     */
    function is_valid_oversea_station_code($station_code)
    {
        return isset(OVERSEA_STATION_CODE[$station_code]);
    }
}

if (!function_exists('is_valid_oversea_platform_code')) {
    /**
     * 是否是可搜索的预测单号. 支持,分割的单号
     *
     * @param unknown $search_sn
     */
    function is_valid_oversea_platform_code($platform_code)
    {
        return INLAND_PLATFORM_CODE[$platform_code]['oversea'] ?? false;
    }
}

if (!function_exists('is_valid_oversea_platform_state')) {
    /**
     * 是否是可搜索的预测单号. 支持,分割的单号
     *
     * @param unknown $search_sn
     */
    function is_valid_oversea_platform_state($station_code)
    {
        return isset(OVERSEA_PLATFROM_APPROVAL_STATE[intval($station_code)]) || intval($station_code) == APPROVAL_STATE_UNDO;
    }
}