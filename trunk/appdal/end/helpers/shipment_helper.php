<?php

if (!function_exists('is_valid_push_state')) {
    /**
     * 是否有效的状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_push_state($state) : bool
    {
        return isset(SHIPMENT_STATE[$state]);
    }
}