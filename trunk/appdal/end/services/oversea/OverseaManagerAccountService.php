<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 海外仓 账号配置服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class OverseaManagerAccountService
{
    public static $s_system_log_name = 'OVERSEA-ACCOUNT';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_manager_account_model', 'm_oversea_manager', false, 'oversea');
        $this->_ci->load->model('User_config_list_model', 'm_user_config_list', false, 'basic');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }
    
    /**
     * 为某个uid设置多个管理的账号
     *
     * @param unknown $params
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return unknown
     */
    public function set_manager($params)
    {
        $params['staff_code'] = is_string($params['staff_code']) ? explode(',', $params['staff_code']) : $params['staff_code'];
        $active_user          = get_active_user();
        $active_user_info     = $active_user->get_user_info();
        $manager_info         = key_by($active_user->get_other_user_info_by_staff_code($params['staff_code']), 'userNumber');
        
        if (false === $manager_info)
        {
            throw new \RuntimeException('您没有权限获取其他账号信息', 500);
        }
        elseif (empty($manager_info))
        {
            throw new \RuntimeException('无法获取管理员信息，请稍后重试或检测该账号是否可用', 500);
        }
        
        if (!is_valid_oversea_station_code($params['station_code']))
        {
            throw new \InvalidArgumentException(sprintf('不合法的站点编码：%s', $params['station_code']), 412);
        }
        if (!is_valid_oversea_platform_code($params['platform_code']))
        {
            throw new \InvalidArgumentException(sprintf('不合法的平台编码：%s', $params['platform_code']), 412);
        }
        
        $this->_ci->load->model('Oversea_manager_staff_model', 'm_manager_staff', false, 'oversea');
        $this->_ci->load->service('basic/SystemLogService');
        
        /**
         * @version 1.2.0 需求未明确的情况下，第一种，页面只传递新的清单列表， 根据新的分离出新增和删除的
         *
         * @var array $exists_rows
         */
        $exists_rows =& $this->_ci->m_oversea_manager->get_station_platform_configs($params['station_code'], $params['platform_code']);
        
        $delete_staff_codes = isset($exists_rows['staff_code']) ? array_diff($exists_rows['staff_code'], $params['staff_code']) : [];
        
        //删除的账号是否只有这一个权限
        $remove_oversea_first_approve_staff = [];
        if (!empty($delete_staff_codes))
        {
            $remove_oversea_first_approve = $this->_ci->m_manager_staff->get_manager_count($delete_staff_codes);
            foreach ($delete_staff_codes as $del_staff)
            {
                if ($remove_oversea_first_approve[$del_staff]['nums'] == 1)
                {
                    //去除权限
                    $remove_oversea_first_approve_staff[] = $del_staff;
                }
            }
        }
        
        $add_staff_codes = isset($exists_rows['staff_code']) ? array_diff($params['staff_code'], $exists_rows['staff_code']) : $params['staff_code'];
        
        /**
         * @version 1.2.0 需求未明确的情况下，第二种，页面传递删除列表和新增列表
         */
        /*
        //分离出删除
        $delete_staff_codes = $params['delete'];
        
        //分离出新增的
        $add_staff_codes = $params['add'];
        */
        $replace_account_row = [
                'gid' => $exists_rows['gid'] ?? $this->_ci->m_oversea_manager->gen_id(),
                'station_code' => $params['station_code'],
                'platform_code' => $params['platform_code'],
                'op_uid' => $active_user_info['oa_info']['userNumber'],
                'op_zh_name' => $active_user_info['oa_info']['userName'],
                'created_at' => $exists_rows['created_at'] ?? date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
        ];
        
        if (!empty($add_staff_codes))
        {
            $gid = $replace_account_row['gid'];
            
            $add_staff_rows = [];
            foreach ($add_staff_codes as $staff_code)
            {
                $add_staff_rows[] = [
                    'gid' => $gid,
                    'staff_code' => $staff_code,
                    'user_zh_name' => $manager_info[$staff_code]['userName'],
                    'state' => $manager_info[$staff_code]['isDel'] == 0 ?  GLOBAL_YES : GLOBAL_NO,
                ];
            }
        }
        
        //新增的manager_uid自动赋予一级审核权限，（ 取消的可能是其他的管理员，不确定是否取消一级权限， 除非验证他是因为这个账号取消的，目前不做处理）
        if (!empty($add_staff_codes) || !empty($remove_oversea_first_approve_staff))
        {
            $form_params = [];
            
            $this->_ci->load->service('basic/UsercfgService');
            
            $staff_privileges = $this->_ci->usercfgservice->get_my_privileges(array_merge($add_staff_codes, $remove_oversea_first_approve_staff));
            
            foreach ($add_staff_codes as $staff_code)
            {
                $manager_privileges = $staff_privileges[$staff_code] ?? [];
                //新增一个
                if (empty($manager_privileges))
                {
                    $form_params[] = [
                            'staff_code' => $staff_code,
                            'bussiness_line' => BUSSINESS_OVERSEA,
                            'data_privilege' => DATA_PRIVILEGE_PRIVATE,
                            'check_privilege' => $this->_ci->usercfgservice->tran_hasx_to_value_str(['has_first' => GLOBAL_YES])
                    ];
                    continue;
                }
                
                //验证海外仓业务线
                if (isset($manager_privileges[BUSSINESS_OVERSEA]))
                {
                    $manager_privileges[BUSSINESS_OVERSEA]['has_first'] = GLOBAL_YES;
                    
                    //更新
                    $form_params[] = [
                            'staff_code' => $staff_code,
                            'bussiness_line' => BUSSINESS_OVERSEA,
                            'data_privilege' => $manager_privileges[BUSSINESS_OVERSEA]['data_privilege'],
                            'check_privilege' => $this->_ci->usercfgservice->tran_hasx_to_value_str($manager_privileges[BUSSINESS_OVERSEA])
                    ];
                }
                else
                {
                    $form_params[] = [
                            'staff_code' => $staff_code,
                            'bussiness_line' => BUSSINESS_OVERSEA,
                            'data_privilege' => DATA_PRIVILEGE_PRIVATE,
                            'check_privilege' => $this->_ci->usercfgservice->tran_hasx_to_value_str(['has_first' => GLOBAL_YES])
                    ];
                }
            }
            
  
            foreach ($remove_oversea_first_approve_staff as $staff_code)
            {
                $manager_privileges = $staff_privileges[$staff_code] ?? [];
                //新增一个
                if (empty($manager_privileges))
                {
                    continue;
                }
                
                //验证海外仓业务线
                if (isset($manager_privileges[BUSSINESS_OVERSEA]))
                {
                    //去掉海外仓一级审核权限
                    $form_params[] = [
                            'staff_code' => $staff_code,
                            'bussiness_line' => BUSSINESS_OVERSEA,
                            'data_privilege' => $manager_privileges[BUSSINESS_OVERSEA]['data_privilege'],
                            'check_privilege' => $this->_ci->usercfgservice->tran_hasx_to_value_str(['has_first' => GLOBAL_NO])
                    ];
                }
            }
        }
        
        $db = $this->_ci->m_oversea_manager->getDatabase();
        
        try
        {
            $db->trans_start();
            
            if (!empty($delete_staff_codes))
            {
                $delete_sql = sprintf(
                    'delete from %s where staff_code in (%s) and gid = (' .
                    'select gid from %s where platform_code = \'%s\' and station_code = \'%s\' limit 1)',
                    $this->_ci->m_manager_staff->getTable(),
                    array_where_in($delete_staff_codes),
                    $this->_ci->m_oversea_manager->getTable(),
                    $params['platform_code'],
                    $params['station_code']
                 );
                if (!$db->query($delete_sql))
                {
                    log_message('ERROR', sprintf('删除用户：%s, 站点：%s 平台：%s 失败， sql=%s', implode(',', $delete_staff_codes), $params['station_code']), $params['platform_code'], $delete_sql);
                }
            }
            
            if (!empty($add_staff_codes))
            {
                $affected_row = $db->insert_batch($this->_ci->m_manager_staff->getTable(), $add_staff_rows);
                if ($affected_row != count($add_staff_codes))
                {
                    throw new \RuntimeException(sprintf('添加管理员失败，预计增加：%d个，实际增加：%d', count($add_staff_codes), $affected_row), 500);
                }
            }
            
            if (!$db->replace($this->_ci->m_oversea_manager->getTable(), $replace_account_row))
            {
                throw new \RuntimeException('新增管理员失败，该站点和平台可能已经被起头用户增加，请确认后重新设置', 500);
            }
          
            //新增的manager_uid自动赋予一级审核权限，（ 取消的可能是其他的管理员，不确定是否取消一级权限， 除非验证他是因为这个账号取消的，目前不做处理）
            if (!empty($form_params))
            {
                $options = [
                        'enable_out_trans' => true,
                ];
                $report = $this->_ci->usercfgservice->assign($form_params, $options);
                
                $report_context = sprintf('给管理员自动开启一级审核权限，返回结果：%s',json_encode($report));
                $this->_ci->systemlogservice->send([], self::$s_system_log_name, $report_context);
            }
            
            $log_context = sprintf('设置海外仓账号管理员：新增：%s 删除：%s 站点：%s, 平台：%s 成功', implode(',', $add_staff_codes), implode(',', $delete_staff_codes), $params['station_code'], $params['platform_code']);
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context);
            
            $db->trans_complete();
            
            if ($db->trans_status() === FALSE)
            {
                throw new \RuntimeException(sprintf('设置海外仓管理员权限事务提交完成，但检测状态为false'), 500);
            }
        }
        catch (\Throwable $e)
        {
            throw new \RuntimeException($e->getMessage(), 500);
        }
        
        return true;
    }

    /**
     * 获取配置
     *
     * @param unknown $staffs
     * @return unknown
     */
    public function get_station_platforms($staffs)
    {
        return $this->_ci->m_oversea_manager->get_station_platforms($staffs);
    }
    
    /**
     * 根据uid获取账号
     *
     * @param unknown $uid 管理员uid
     * @return unknown
     */
    public function get_my_stations($uid)
    {
        $result = $this->get_station_platforms([$uid]);
        if (!empty($result))
        {
            return array_keys($result[$uid]);
        }
    }

    /**
     * 是否有一级审核权限
     *
     * @param unknown $uid
     */
    public function has_first_privileges($staff_code)
    {
       $row = $this->_ci->m_user_config->get($staff_code);
       if (empty($row) || $row[BUSSINESS_OVERSEA]['has_first'] != GLOBAL_YES)
       {
           return false;
       }
       return true;
    }
    
    /**
     * 是否有二级审核权限
     *
     * @param unknown $staff_code
     * @return boolean
     */
    public function has_second_privileges($staff_code)
    {
        $row = $this->_ci->m_user_config->get($staff_code);
        if (empty($row) || $row[BUSSINESS_OVERSEA]['has_second'] != GLOBAL_YES)
        {
            return false;
        }
        return true;
    }
    
 
}
