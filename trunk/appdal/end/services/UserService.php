<?php

/**
 * 用户服务， 是对用户的封装，返回当前登录用户的对象
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class UserService
{
    
    private $_ci;
    
    private static $_user_info;
    
    public function __construct()
    {
        $this->_init();
    }

    private function _init() : void
    {
        $this->_ci =& get_instance();
    }
    
    /**
     * 后端进行用户登录操作， 用静态变量代替session存储
     * 计划系统账号操作都是以OA用户操作，存储的uid字段都是用户uid
     */
    public static final function login($redis_user_info = [])
    {
        if (!$redis_user_info)
        {
            throw new \RuntimeException('获取用户认证信息失败，该用户未登陆或者已经失效，请重新登陆');
        }
        $oa_info = RPC_CALL('YB_J1_005', [$redis_user_info['staff_code']])[0] ?? [];
        if (!$oa_info)
        {
            throw new \RuntimeException('找不到该员工信息，此员工可能被禁用');
        }
        if (intval($oa_info['isDel']) !== 0)
        {
            throw new \RuntimeException('该账号已经被删除或禁用');
        }
        $redis_user_info['oa_info'] = $oa_info;
        //转换为oa的用户
        static::$_user_info = $redis_user_info;
    }
    
    /**
     * 获取用户信息
     * @return unknown
     */
    public static final function get_user_info()
    {
        return static::$_user_info;
    }
    
    /**
     * 检测是否登录
     *
     * @return unknown
     */
    public static final function is_login()
    {
        return !empty(static::$_user_info);
    }
    
    /**
     * 获取登录用户
     *
     * @return unknown
     */
    public final function getActiveUser($refresh = false)
    {
        $this->_ci->load->classes('basic/classes/UserFactory');
        return $this->_ci->UserFactory::getInstance($refresh);
    }
    
    /**
     * 同步配置的员工账号状态
     *
     * @return number|string[]
     */
    public final function sync_staff_state()
    {
        $configured_staffs = $disable_staff = $enable_staff = $report = [];
        
        $this->_ci->load->model('Oversea_manager_staff_model', 'm_oversea_staff', false, 'oversea');
        $staff_info = $this->_ci->m_oversea_staff->get_staffs();
        $oversea_staff = array_column($staff_info, 'state', 'staff_code');
        
        $this->_ci->load->model('Shipment_oversea_manager_account_model', 'm_shipment_oversea_account', false, 'shipment');
        $shipment_staff_info = $this->_ci->m_shipment_oversea_account->get_staffs();
        $shipment_oversea_staff = array_column($shipment_staff_info, 'state', 'staff_code');
        
        $configured_staffs = array_merge(array_keys($shipment_oversea_staff), array_keys($oversea_staff));
        
        if (empty($configured_staffs))
        {
            log_message('INFO', '配置账号同步, 还没有任何员工被配置。');
            return 0;
        }
        
        $configured_staffs = array_unique($configured_staffs);
        $oa_staff_rows = RPC_CALL('YB_J1_005', $configured_staffs);
        if (empty($oa_staff_rows))
        {
            log_message('ERROR', '配置账号同步, OA接口返回异常，本次未同步'.json_encode($configured_staffs));
            return 0;
        }
        
        $oa_staff_state = array_column($oa_staff_rows, 'isDel', 'userNumber');
        foreach ($oversea_staff as $staff => $state)
        {
            if ((intval($oa_staff_state[$staff]) ?? 1) == 1)
            {
                if ($state == GLOBAL_YES)
                {
                    $disable_staff[] = $staff;
                }
            }
            else
            {
                if ($state == GLOBAL_NO)
                {
                    $enable_staff[] = $staff;
                }
            }
        }
        
        if (!empty($enable_staff))
        {
            $affected_rows = $this->_ci->m_oversea_staff->enable_staffs($enable_staff);
            $report['oversea_disable_to_enable'] = sprintf('海外管理员状态由禁用变更为启用个数为：%d, 原始状态: %s', $affected_rows, json_encode($oversea_staff));
        }
        
        if (!empty($disable_staff))
        {
            $affected_rows = $this->_ci->m_oversea_staff->disabled_staffs($disable_staff);
            $report['oversea_disable_to_enable'] = sprintf('海外管理员状态由启用变更为禁用个数为：%d, 原始状态: %s', $affected_rows, json_encode($oversea_staff));
        }
        
        
        $oa_staff_state = $disable_staff = $enable_staff = [];
        foreach ($shipment_oversea_staff as $staff => $state)
        {
            if ((intval($oa_staff_state[$staff]) ?? 1) == 1)
            {
                if ($state == GLOBAL_YES)
                {
                    $disable_staff[] = $staff;
                }
            }
            else
            {
                if ($state == GLOBAL_NO)
                {
                    $enable_staff[] = $staff;
                }
            }
        }
        
        if (!empty($enable_staff))
        {
            $affected_rows = $this->_ci->m_shipment_oversea_account->enable_staffs($enable_staff);
            $report['shipment_oversea_disable_to_enable'] = sprintf('发运计划海外管理员状态由禁用变更为启用个数为：%d, 原始状态: %s', $affected_rows, json_encode($oversea_staff));
        }
        
        if (!empty($disable_staff))
        {
            $affected_rows = $this->_ci->m_shipment_oversea_account->disabled_staffs($disable_staff);
            $report['shipment_oversea_disable_to_enable'] = sprintf('发运计划海外管理员状态由启用变更为禁用个数为：%d, 原始状态: %s', $affected_rows, json_encode($oversea_staff));
        }
        
        log_message('INFO', '配置账号同步结果：'.(empty($report) ? '无变化' : implode(';', $report)));
        
        return $report;
        
    }
    
}