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
class Fba_amazon_group_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_amazon_group';
        $this->tableId = 98;
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
     * 获取分组列表
     * 
     * @return array
     */
    public function get_group_list()
    {
        $result = $this->_db->select('group_id, group_name')->from($this->table_name)->get()->result_array();
        return array_column($result, 'group_name', 'group_id');
    }
    
    public function get_group_name($group_ids)
    {
        $result = $this->_db->select('group_id, group_name')->from($this->table_name)->where_in('group_id', $group_ids)->get()->result_array();
        return array_column($result, 'group_name', 'group_id');
    }

    public function get_group_id($group_name)
    {
        $result = $this->_db->select('group_id')->from($this->table_name)
                            ->where('group_name', $group_name)
                            ->limit(1)->get()->row_array();
        return $result['group_id'] ?? 0;
    }
}