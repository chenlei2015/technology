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
class Fba_amazon_account_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_amazon_account';
        $this->tableId = 99;
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

    public function get_account_id_by_name($account_name)
    {
        $result = $this->_db->select('id')->from($this->table_name)->where('account_name',$account_name)->limit(1)->get()->row_array();
        $account_id = $result['id'] ?? 0;
        return $account_id;
    }

    public function get_account_id_map()
    {
        return key_by($this->_db->select('id, account_name, account_num, site')->from($this->table_name)->get()->result_array(), 'id');
    }

    public function get_account_name_and_nums()
    {
        return array_column($this->_db->select('account_name, account_num')->from($this->table_name)->get()->result_array(), 'account_num', 'account_name');
    }

    public function get_all_accounts()
    {
        return array_keys($this->get_account_name_and_nums());
    }

    public function get_my_account_nums()
    {
        return array_filter(array_values($this->get_account_name_and_nums()));
    }

    /**
     * 根据分组获取账号列表
     *
     * @param unknown $group_id
     */
    public function get_accounts_by_group($group_id)
    {
        $result = $this->_db->select("account_name,CONCAT(account_num,'欧洲') as account_num")->from($this->table_name)->where('group_id', $group_id)->get()->result_array();
        $pan_eu = array_unique(array_column($result, 'account_num', 'account_num'));
        $result = array_merge(array_column($result, 'account_name', 'account_name'), $pan_eu);//下拉返回,account_num+欧洲
        natsort($result);// 自然排序法 排序 保持键名不变
        return $result;
    }
}