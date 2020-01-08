<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 用户列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-07
 * @link 
 */
class User_config_list_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_user_config_list';
        $this->primaryKey = 'gid';
        $this->tableId = 54;
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
    
    public function get($staff_codes)
    {
        return $this->_db->from($this->table_name)->where_in('staff_code', $staff_codes)->get()->result_array();
    }

    /**
     * 是否存在
     *
     * @param int $uid
     * @param int $role
     * @param int $bussiness_type
     * @return number
     */
    public function exists($staff_code)
    {
        return count($this->_db
            ->select('staff_code')
            ->from($this->table_name)
            ->where('staff_code', $staff_code)
            ->get()
            ->result_array()) > 0;
            
    }

}