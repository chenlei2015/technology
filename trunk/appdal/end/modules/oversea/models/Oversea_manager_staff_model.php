<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 海外仓员工表
 *
 * @version 1.2.0
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-07-10
 * @link
 */
class Oversea_manager_staff_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_oversea_manager_staff';
        $this->primaryKey = 'gid';
        $this->tableId = 25;
        parent::__construct();
    }
    
    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }
    
    /**
     * 查询员工管理了多少个有效的平台
     *
     * @param unknown $staff_codes
     * @return array
     */
    public function get_manager_count($staff_codes)
    {
        $query = $this->_db->select('staff_code, count(*) as nums')
        ->from($this->table_name)
        ->where('state', GLOBAL_YES)
        ->where_in('staff_code', $staff_codes)
        ->group_by('staff_code');
        
        return key_by($query->get()->result_array(), 'staff_code');
    }
    
    public function get_staffs($state = 0)
    {
        $query = $this->_db->select('staff_code, state')->from($this->table_name);
        if ($state != 0)
        {
            $query->where('state', $state);
        }
        return $query->get()->result_array();
    }
    
    public function enable_staffs($staff_codes)
    {
        $this->_db->reset_query();
        $this->_db->where_in('staff_code', $staff_codes);
        return $this->_db->update($this->table_name, ['state' => GLOBAL_YES]);
    }
    
    public function disabled_staffs($staff_codes)
    {
        $this->_db->reset_query();
        $this->_db->where_in('staff_code', $staff_codes);
        return $this->_db->update($this->table_name, ['state' => GLOBAL_NO]);
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
    
    /**
     *
     * @param unknown $user_name
     * @return array|string|array|string
     */
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