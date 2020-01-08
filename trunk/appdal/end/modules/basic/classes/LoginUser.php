<?php

include_once dirname(__FILE__).'/User.php';

/**
 * 用户的一层封装, api模拟登录用户
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw
 */
class LoginUser extends User
{

    /**
     * 权限
     *
     * @var unknown
     */
    private $_privileges;

    public function __construct($params  = array())
    {
        parent::__construct($params);
        $this->get_user_privileges();
    }

    /**
     * 接口
     *
     * @return array
     */
    public function get_user_info() : array
    {
        return $this->get();
    }

    /**
     * 获取用户权限
     */
    public function get_user_privileges()
    {
        if (!$this->_privileges)
        {
            $this->_ci->load->service('basic/UsercfgService');
            $this->_privileges = $this->_ci->usercfgservice->get_my_privileges($this->staff_code);
        }
        return $this->_privileges;
    }

    /**
     * 将变更的用户信息推送到redis
     */
    public function push()
    {

    }

    /**
     * 获取用户的部门信息
     * @todo
     */
    public function get_user_department() : array
    {
        return [];
    }


    /**
     * 判断我是否是销售人员
     */
    public function isSalesman()
    {
        $ci = CI::$APP;
        $ci->load->service('basic/DropdownService');
        $ci->dropdownservice->setDroplist(['fba_salesman']);
        $salesmans = $ci->dropdownservice->get()['fba_salesman'];
        return array_key_exists($this->staff_code, $salesmans);
    }

    /**
     * 判断我是否是账号管理员
     */
    public function isAccountManager()
    {
        $ci = CI::$APP;
        $ci->load->service('fba/FbaManagerAccountService');
        return $ci->fbamanageraccountservice->is_fba_acccount_manager($this->staff_code);
    }

    /**
     * 判断我是否有一级审核权限
     *
     * @param unknown $bussiness_type
     * @return boolean
     */
    public function is_first_approver($bussiness_type)
    {
        $cfg_value = intval($this->_privileges[$bussiness_type]['has_first'] ?? -1);
        return $cfg_value == GLOBAL_YES ? true : false;
    }

    /**
     * 判断我是否是二级审核权限,
     *
     */
    public function is_second_approver($bussiness_type)
    {
        $cfg_value = intval($this->_privileges[$bussiness_type]['has_second'] ?? -1);
        return GLOBAL_YES == $cfg_value ? true : false;
    }

    /**
     * 判断我是否是三级审核权限,
     *
     * @param unknown $bussiness_type
     * @return boolean
     */
    public function is_three_approver($bussiness_type)
    {
        $cfg_value = intval($this->_privileges[$bussiness_type]['has_three'] ?? -1);
        return GLOBAL_YES == $cfg_value ? true : false;
    }

    /**
     * 拥有全部数据权限
     *
     * @param unknown $bussiness_type
     * @return boolean
     */
    public function has_all_data_privileges($bussiness_type)
    {
        $cfg_value = intval($this->_privileges[$bussiness_type]['data_privilege'] ?? -1);
        return DATA_PRIVILEGE_ALL == $cfg_value ? true : false;
    }

    /**
     * 我有哪几条业务线的全部权限, 用,分割
     */
    public function get_all_data_privilege_buss_lines()
    {
        $lines = [];
        foreach ($this->_privileges as $buss => $row)
        {
            if ($row['data_privilege'] == DATA_PRIVILEGE_ALL)
            {
                $lines[] = $buss;
            }
        }
        sort($lines);
        return $lines;
    }

    /**
     * 只有私人数据权限
     *
     * @param unknown $bussiness_type
     * @return boolean
     */
    public function has_private_data_privileges($bussiness_type)
    {
        $cfg_value = intval($this->_privileges[$bussiness_type]['data_privilege'] ?? -1);
        return DATA_PRIVILEGE_PRIVATE == $cfg_value ? true : false;
    }

    /**
     * 非业务员账号， 无任何权限
     *
     * @param unknown $bussiness_type
     */
    public function has_none_data_privileges($bussiness_type)
    {
       return !$this->has_all_data_privileges($bussiness_type) && !$this->has_private_data_privileges($bussiness_type);
    }

    /**
     * 获取我管理的FBA账号
     *
     * @return array
     */
    public function get_my_manager_accounts()
    {
        $ci = CI::$APP;
        $ci->load->service('fba/FbaManagerAccountService');
        return $ci->fbamanageraccountservice->get_my_accounts($this->staff_code);
    }

    public function get_my_manager_account_nums()
    {
        $ci = CI::$APP;
        $ci->load->service('fba/FbaManagerAccountService');
        return $ci->fbamanageraccountservice->get_my_account_nums($this->staff_code);
    }

    /**
     * 当前账号全部是否是我管辖的FBA账号
     */
    public function is_my_manager_accounts($account_name)
    {
        $account_name = (array)$account_name;
        $child_accounts = $this->get_my_manager_accounts();
        return count(array_diff($account_name, $child_accounts)) == 0;
    }

    /**
     * 获取海外仓的管理的站点和平台列表
     *
     * @return unknown
     */
    public function get_my_station_platforms()
    {
        static $account_config;
        if (null === $account_config)
        {
            $ci = CI::$APP;
            $ci->load->service('oversea/OverseaManagerAccountService');
            $account_config =  $ci->overseamanageraccountservice->get_station_platforms([$this->staff_code])[$this->staff_code] ?? [];
        }
        return $account_config;
    }

    /**
     * 获取海外仓站点权限， 目前与发运的站点配置相同
     *
     * @todo 待确认
     */
    public function get_my_stations()
    {
        static $station_config;
        if (null === $station_config)
        {
            $ci = CI::$APP;
            $ci->load->service('shipment/OverseaManagerAccountService');
            $station_config =  $ci->overseamanageraccountservice->get_my_stations($this->staff_code) ?? [];
        }
        return $station_config;

    }

    /**
     * 获取发运计划海外仓站点
     *
     * @return array
     */
    public function get_shipment_oversea_stations()
    {
        static $shipment_account_config;
        if (null === $shipment_account_config)
        {
            $ci = CI::$APP;
            $ci->load->service('shipment/OverseaManagerAccountService');
            $shipment_account_config =  $ci->overseamanageraccountservice->get_my_stations($this->staff_code) ?? [];
        }
        return $shipment_account_config;
    }

    /**
     * 获取指定uid的信息
     */
    public function get_others_user_info($uid)
    {
        return RPC_CALL('YB_J1_001', $uid);
    }

    /**
     * 获取多个员工工号信息
     *
     * @param unknown $staff_codes
     * @return string
     */
    public function get_other_user_info_by_staff_code($staff_codes)
    {
        return RPC_CALL('YB_J1_005', $staff_codes);
    }


    /**
     * 获取授权的平台的列表，这个配置在计划系统实现
     *
     * @todo 这里默认返回所有平台
     */
    public function get_authed_platform()
    {
        $this->_ci->load->library('basic/dropdownservice');
        return $this->_ci->dropdownservice->dropdown_platform();
    }
}