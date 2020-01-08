<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba 账号配置表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link
 */
class Fba_manager_account_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_fba_manager_account';
        $this->primaryKey = 'account_name';
        $this->tableId = 93;
        parent::__construct();
    }

    /**
     *
     * @return unknown
     */
    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }

    /**
     * 获取未配置管理员的数量
     *
     * @return number
     */
    public function get_alone_account_nums()
    {
        $_ci = CI::$APP;
        $_ci->load->model('Fba_amazon_account_model', 'amazon_account', false, 'fba');
        return $_ci->amazon_account->record_count() - $this->record_count();
    }

    /**
     * 根据uid获取账号
     *
     * @param unknown $uid
     * @return array
     */
    public function get_my_accounts($staff_code)
    {
        $result = $this->_db->select('account_name')->from($this->table_name)->where('staff_code', $staff_code)->get()->result_array();
        return array_column($result, 'account_name');
    }

    public function get_my_account_nums($staff_code)
    {
        $result = $this->_db->select('account_num')->from($this->table_name)->where('staff_code', $staff_code)->get()->result_array();
        return array_column($result, 'account_num');
    }

    /**
     * staff_code => $user_zh_name
     */
    public function get_unique_manager()
    {
        $result = $this->_db->select('staff_code, user_zh_name')->from($this->table_name)->group_by('staff_code')->order_by('staff_code', 'asc')->get()->result_array();
        if (empty($result)) return [];
        foreach ($result as $row)
        {
            $options[$row['staff_code']] = $row['staff_code'] . ' '.$row['user_zh_name'];
        }
        return $options;
    }

    public function get_dync_manager_by_name($user_name)
    {
        if (!$user_name || trim($user_name) == '')
        {
            return $this->get_unique_manager();
        }
        $result = $this->_db->select('staff_code, user_zh_name')->from($this->table_name)->like('user_zh_name', $user_name)->group_by('staff_code')->order_by('staff_code', 'asc')->get()->result_array();
        if (empty($result)) return [];
        foreach ($result as $row)
        {
            $options[$row['staff_code']] = $row['staff_code'] . ' '.$row['user_zh_name'];
        }
        return $options;

    }
}