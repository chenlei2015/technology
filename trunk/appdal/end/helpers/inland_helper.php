<?php 

if (!function_exists('is_valid_stock_up_type')) {
    /**
     * 是否是可用的备货方式
     *
     * @param unknown $search_sn
     */
    function is_valid_stock_up_type($search)
    {
        $name = 'inland_stock_up';
        $ci = CI::$APP;
        $ci->load->service('basic/DropdownService');
        $ci->dropdownservice->setDroplist([$name]);
        return isset($ci->dropdownservice->get()[$name][$search]);
    }
}

if (!function_exists('is_valid_inland_approval_state')) {
    /**
     * 是否有效的fba审核状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_inland_approval_state($state) : bool
    {
        return isset(SPECIAL_CHECK_STATE[$state]);
    }
}