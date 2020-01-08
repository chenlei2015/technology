<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 发运计划 海外仓 账号配置服务
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
    public static $s_system_log_name = 'SHIPMENT-OVERSEA-ACCOUNT';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Shipment_oversea_manager_account_model', 'm_shipment_oversea_manager', false, 'shipment');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }

    /**
     * 为某个uid设置多个管理的账号
     * todo : 上线前删除 记住
     * @param unknown $params
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return unknown
     */
    public function set_managery($params)
    {
        $active_user = get_active_user();
        $active_user_info = $active_user->get_user_info();
        $manager_info = $active_user->get_other_user_info_by_staff_code([$params['staff_code']]);
        if (false === $manager_info)
        {
            //无权限
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

        $exists_row = $this->_ci->m_shipment_oversea_manager->get_station_row($params['station_code']);
        if (!empty($exists_row) && $exists_row[0]['staff_code'] == $params['staff_code'])
        {
            throw new \InvalidArgumentException(sprintf('管理员相同，请重新选择管理员'), 412);
        }
        //config配置
        //$staff_config_rows = $this->_ci->m_user_config_list->get([$exists_row[0]['staff_code'], $params['staff_code']]);

        $db = $this->_ci->m_shipment_oversea_manager->getDatabase();

        try
        {
            $db->trans_start();

            if (!empty($exists_row))
            {
                $db->where('staff_code', $exists_row[0]['staff_code'])
                ->where('station_code', $exists_row[0]['station_code']);
                $update = [
                    'staff_code' => $params['staff_code'],
                    'user_zh_name' => $manager_info[0]['userName'],
                    'op_uid' => $active_user_info['oa_info']['userNumber'],
                    'op_zh_name' => $active_user_info['oa_info']['userName'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'state' => FBA_ACCOUNT_MGR_ENABLE_YES
                ];
                $affected_row = $db->update($this->_ci->m_shipment_oversea_manager->getTable(), $update);
                if ($affected_row == 0)
                {
                    throw new \RuntimeException('设置管理员失败，该管理员可能已经被其他用户修改，请确认后重新设置', 500);
                }
            }
            else
            {
                $insert_manager = [
                    'station_code' => $params['station_code'],
                    'staff_code' => $params['staff_code'],
                    'user_zh_name' => $manager_info[0]['userName'],
                    'op_uid' => $active_user_info['oa_info']['userNumber'],
                    'op_zh_name' => $active_user_info['oa_info']['userName'],
                    'updated_at' => date('Y-m-d H:i:s'),
                    'state' => FBA_ACCOUNT_MGR_ENABLE_YES
                ];
                if (!$db->insert($this->_ci->m_shipment_oversea_manager->getTable(), $insert_manager))
                {
                    throw new \RuntimeException('新增管理员失败，该站点可能已经被起头用户增加，请确认后重新设置', 500);
                }
            }

            $db->trans_complete();

            if ($db->trans_status() === FALSE)
            {
                throw new \RuntimeException(sprintf('设置发运计划海外仓管理员权限事务提交完成，但检测状态为false'), 500);
            }
        }
        catch (\Throwable $e)
        {
            throw new \RuntimeException($e->getMessage(), 500);
        }

        return true;
    }


    public function set_manager($params)
    {
        $active_user = get_active_user();
        $active_user_info = $active_user->get_user_info();
        $staff_codes = explode(',',$params['staff_code']);
        $manager_info         = key_by($active_user->get_other_user_info_by_staff_code($staff_codes), 'userNumber');

        if (false === $manager_info)
        {
            //无权限
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

        $this->_ci->load->model('Shipment_oversea_manager_staff_model', 'm_shipment_manager_staff', false, 'shipment');
        $exists_rows = $this->_ci->m_shipment_oversea_manager->get_station_staff($params['station_code']);

        //站点表更新数据
        $replace_account_row = [
            'gid' => $exists_rows['gid'] ?? $this->_ci->m_shipment_oversea_manager->gen_id(),
            'station_code' => $params['station_code'],
            'op_uid' => $active_user_info['oa_info']['userNumber'],
            'op_zh_name' => $active_user_info['oa_info']['userName'],
            'created_at' => $exists_rows['created_at'] ?? date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];


        //需要删除的站点管理员
        $delete_staff_codes = isset($exists_rows['staff_code']) ? array_diff($exists_rows['staff_code'], $staff_codes) : [];

        //需要添加的站点管理员
        $add_staff_codes = isset($exists_rows['staff_code']) ? array_diff($staff_codes, $exists_rows['staff_code']) : $staff_codes;
        if (!empty($add_staff_codes))
        {
            $gid = $replace_account_row['gid'];
            $add_staff_rows = [];
            foreach ($add_staff_codes as $staff_code)
            {
                if(isset($manager_info[$staff_code])){
                    $add_staff_rows[] = [
                        'gid' => $gid,
                        'staff_code' => $staff_code,
                        'user_zh_name' => $manager_info[$staff_code]['userName'],
                        'state' => $manager_info[$staff_code]['isDel'] == 0 ?  GLOBAL_YES : GLOBAL_NO,
                    ];
                }
            }
        }

        $db = $this->_ci->m_shipment_oversea_manager->getDatabase();
        try{
            $db->trans_start();
            //更新站点主表信息
            if(!$db->replace($this->_ci->m_shipment_oversea_manager->getTable(), $replace_account_row)){
                throw new \RuntimeException('新增管理员失败,请确认后重新设置', 500);
            }
            //删除站点管理员
            if(!empty($delete_staff_codes)){
                $db->where('gid',$replace_account_row['gid']);
                $db->where_in('staff_code', $delete_staff_codes);
                if(!$db->delete($this->_ci->m_shipment_manager_staff->getTable())){
                    throw new \RuntimeException('删除管理员失败,请确认后重新设置', 500);
                }
            }

            //新增站点管理员
            if (!empty($add_staff_codes))
            {
                $affected_row = $db->insert_batch($this->_ci->m_shipment_manager_staff->getTable(), $add_staff_rows);
                if ($affected_row != count($add_staff_codes))
                {
                    throw new \RuntimeException(sprintf('添加管理员失败，预计增加：%d个，实际增加：%d', count($add_staff_codes), $affected_row), 500);
                }
            }
            $db->trans_complete();
            if ($db->trans_status() === FALSE) throw new \RuntimeException(sprintf('设置海外仓站点管理员提交完成，但检测状态为false'), 500);

        }catch (Throwable $e){
            throw new \RuntimeException($e->getMessage(), 500);
        }

        return true;
    }

    /**
     * 根据uid获取账号
     *
     * @param unknown $uid 管理员uid
     * @return unknown
     */
    public function get_my_stations($uid)
    {
        return $this->_ci->m_shipment_oversea_manager->get_my_stations($uid);
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


}
