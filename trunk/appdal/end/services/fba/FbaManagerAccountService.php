<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * FBA 账号配置服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class FbaManagerAccountService
{
    public static $s_system_log_name = 'FBA-ACCOUNT';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_manager_account_model', 'manager_account_model', false, 'fba');
        $this->_ci->load->model('User_config_model', 'm_user_config', false, 'basic');
        $this->_ci->load->helper('fba_helper');
        return $this;
    }

    /**
     * 未配置的账号
     *
     * @return unknown
     */
    public function get_alone_account_nums()
    {
        return $this->_ci->manager_account_model->get_alone_account_nums();
    }

    /**
     * 为某个uid设置多个管理的账号
     *
     * @param unknown $params
     * @throws \RuntimeException
     * @throws \InvalidArgumentException
     * @return unknown
     */
    public function batch_set_manager($params)
    {
        $active_user = get_active_user();
        //必须登录操作，systemuser不行
        if (!is_login())
        {
            throw new \RuntimeException('请先登录然后操作', 500);
        }
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

        //检测账号是否合法
        $this->_ci->load->model('Fba_amazon_account_model', 'amazon_account', false, 'fba');
        $accounts = $this->_ci->amazon_account->get_all_accounts();
        if (count($diff = array_diff($params['account_name'], $accounts)) > 0)
        {
            throw new \InvalidArgumentException(sprintf('存在不合法的账号：%s', implode(',', $diff)), 412);
        }

        //insert or replace
        $batch_replace = [];
        foreach ($params['account_name'] as $account)
        {
            $batch_replace[] = [
                    'account_name' => $account,
                    'account_num' => $accounts[$account] ?? '',
                    'staff_code' => $params['staff_code'],
                    'user_zh_name' => $manager_info[0]['userName'],
                    'op_uid' => $active_user_info['oa_info']['userNumber'],
                    'op_zh_name' => $active_user_info['oa_info']['userName'],
                    'updated_at' => date('Y-m-d H:i:s')
            ];
        }

        $db = $this->_ci->amazon_account->getDatabase();

        try
        {
            $db->trans_start();

            $row = 0;
            foreach ($batch_replace as $rep)
            {
                $re = $db->replace($this->_ci->manager_account_model->getTable(), $rep);
                if ($re)
                {
                    $row += 1;
                }
            }

            //新增的manager_uid自动赋予一级审核权限
            $this->_ci->load->service('basic/UsercfgService');
            $manager_privileges = $this->_ci->usercfgservice->get_my_privileges($params['staff_code']);
            if (isset($manager_privileges[BUSSINESS_FBA]))
            {
                //更新
                $form_params = [
                        0 => [
                                'staff_code' => $params['staff_code'],
                                'bussiness_line' => BUSSINESS_FBA,
                                'data_privilege' => $manager_privileges[BUSSINESS_FBA]['data_privilege'],
                                'check_privilege' => $this->_ci->usercfgservice->tran_hasx_to_value_str($manager_privileges[BUSSINESS_FBA])
                        ],
                ];
            }
            else
            {
                $form_params = [
                        0 => [
                                'staff_code' => $params['staff_code'],
                                'bussiness_line' => BUSSINESS_FBA,
                                'data_privilege' => DATA_PRIVILEGE_PRIVATE,
                                'check_privilege' => $this->_ci->usercfgservice->tran_hasx_to_value_str(['has_first' => GLOBAL_YES])
                        ],
                ];
            }

            $options = [
                    'enable_out_trans' => true,
            ];
            $report = $this->_ci->usercfgservice->assign($form_params, $options);

            $log_context = sprintf('批量设置FBA账号管理员：管理员ID：%s 账号列表：%s, 成功：%d', $params['staff_code'], implode(',', $params['account_name']), $row);
            $report_context = sprintf('给管理员自动开启一级审核权限，返回结果：%s',json_encode($report));
            $this->_ci->load->service('basic/SystemLogService');
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context);
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, $report_context);

            $db->trans_complete();

            if ($db->trans_status() === FALSE)
            {
                throw new \RuntimeException(sprintf('设置FBA管理员权限事务提交完成，但检测状态为false'), 500);
            }

            return $row;
        }
        catch (\Throwable $e)
        {
            throw new \RuntimeException($e->getMessage(), 500);
        }
    }

    /**
     * 根据uid获取账号
     *
     * @param unknown $uid 管理员uid
     * @return unknown
     */
    public function get_my_accounts($uid)
    {
        return $this->_ci->manager_account_model->get_my_accounts($uid);
    }

    public function get_my_account_nums($staff_code)
    {
        return $this->_ci->manager_account_model->get_my_account_nums($staff_code);
    }

    /**
     * 判断这个用户是否是亚马逊账号管理员,
     * 设置过亚马逊的账号
     *
     * @param int $uid
     * @return unknown
     */
    public function is_fba_acccount_manager($staff_code)
    {
        $accounts = $this->_ci->manager_account_model->get_my_accounts($staff_code);
        return !empty($accounts);
    }

    /**
     * 是否有一级审核权限
     *
     * @param unknown $uid
     */
    public function has_first_privileges($staff_code)
    {
       $row = $this->_ci->m_user_config->get($staff_code);
       if (empty($row) || $row[BUSSINESS_FBA]['has_first'] != GLOBAL_YES)
       {
           return false;
       }
       return true;
    }


}
