<?php


if (!function_exists('get_fba_upload_path')) {

    /**
     * 获取sgs的上传路径
     */
    function get_sgs_upload_path()
    {
        return get_upload_path() . '_fba'.DIRECTORY_SEPARATOR;
    }
}

if (!function_exists('tran_fnsku')) {
    /**
     * 是否是可搜索的预测单号. 支持,分割的单号
     *
     * @param unknown $search_sn
     */
    function tran_fnsku($search_sn)
    {
        $arr = array_filter(explode(',', str_replace(array('%7C', '%20', ',', ' '), ',', $search_sn)));
        return implode(',', $arr);
    }
}

if (!function_exists('tran_asin')) {
    /**
     * 是否是可搜索的预测单号. 支持,分割的单号
     *
     * @param unknown $search_sn
     */
    function tran_asin($search_sn)
    {
        $arr = array_filter(explode(',', str_replace(array('%7C', '%20', ',', ' '), ',', $search_sn)));
        return implode(',', $arr);
    }
}

if (!function_exists('is_valid_fba_station_code')) {
    /**
     * 是否是可搜索的预测单号. 支持,分割的单号
     *
     * @param unknown $search_sn
     */
    function is_valid_fba_station_code($station_code)
    {
        $name = 'station_code';
        $ci = CI::$APP;
        $ci->load->service('basic/DropdownService');
        $ci->dropdownservice->setDroplist([$name]);
        return isset($ci->dropdownservice->get()[$name][$station_code]);
    }
}

if (!function_exists('is_valid_promotion_sku_state')) {
    /**
     * 是否是有效的促销sku状态
     *
     * @param unknown $search_sn
     */
    function is_valid_promotion_sku_state($sku_state)
    {
        return isset(PROMOTION_SKU_STATE[intval($sku_state)]);
    }
}

if (!function_exists('is_valid_activity_approval_state')) {
    /**
     * 是否有效的fba审核状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_activity_approval_state($state) : bool
    {
        return isset(ACTIVITY_APPROVAL_STATE[$state]);
    }
}

if (!function_exists('is_valid_activity_state')) {
    /**
     * 是否有效的fba活动状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_activity_state($state) : bool
    {
        return isset(ACTIVITY_STATE[$state]);
    }
}

if (!function_exists('is_valid_new_approval_state')) {
    /**
     * 是否有效的fba新品审核状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_new_approval_state($state) : bool
    {
        return isset(NEW_APPROVAL_STATE[$state]);
    }
}