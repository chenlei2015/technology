<?php 

if (!function_exists('is_valid_pur_data_state')) {
    /**
     * 是否有效的推送状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_pur_data_state($state) : bool
    {
        return isset(PUR_DATA_STATE[$state]);
    }
}

if (!function_exists('is_valid_virtual_warehouser')) {
    /**
     * 是否有效的虚拟仓
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_virtual_warehouser($state) : bool
    {
        return isset(ALLOT_VM_WAREHOUSE[$state]);
    }
}