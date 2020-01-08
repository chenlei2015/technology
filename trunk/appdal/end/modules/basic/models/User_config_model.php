<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 用户数据权限配置表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-07
 * @link
 */
class User_config_model extends MY_Model
{
    use Table_behavior;
    
    public static $s_map_value = [
            'has_first'  => ['name' => '一级审核', 'val' => '1'],
            'has_second' => ['name' => '二级审核', 'val' => '2'],
            'has_three'  => ['name' => '三级审核', 'val' => '3'],
    ];
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_user_config';
        $this->primaryKey = '';
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
     * 添加一条记录
     *
     * @param unknown $params
     * @return unknown
     */
    public function add($params)
    {
        return $this->_db->insert($this->table_name, $params);
    }
    
    /**
     * 更新
     *
     * {@inheritDoc}
     * @see MY_Model::update()
     */
    public function update_cfg($where, $params)
    {
        $this->_db->reset_query();
        if ($this->_db->update($this->table_name, $params, $where))
        {
            return $this->_db->affected_rows();
        }
        return 0;
    }
    
    /**
     * 根据列表获取uid
     *
     * @param unknown $role
     * @return array
     */
    public function get_list_by_staff_codes($staff_codes)
    {
        $query = $this->_db->from($this->table_name)->where_in('staff_code', $staff_codes);
        return $query->get()->result_array();
    }
    
    /**
     * 获取单个用户的权限设置
     *
     * @param unknown $uid
     */
    public function get($staff_code)
    {
        $rows = $this->_db->from($this->table_name)->where('staff_code', $staff_code)->get()->result_array();
        if (empty($rows))
        {
            return [];
        }
        return key_by($rows, 'bussiness_line');
    }
    
    
    public function mget($staff_code)
    {
        $rows = $this->_db->from($this->table_name)->where_in('staff_code', $staff_code)->get()->result_array();
        if (empty($rows))
        {
            return [];
        }
        $cfg = [];
        foreach ($rows as $row)
        {
            $cfg[$row['staff_code']][$row['bussiness_line']] = $row;
        }
        $rows = NULL;
        unset($rows);
        
        return $cfg;
    }
    
    /**
     * 是否存在
     *
     * @param int $uid
     * @param int $role
     * @param int $bussiness_type
     * @return number
     */
    public function exists($staff_code, $bussiness_type)
    {
        return count($this->_db
        ->select('staff_code')
        ->from($this->table_name)
        ->where('staff_code', $staff_code)
        ->where('bussiness_type', $bussiness_type)
        ->get()
        ->result_array()) > 0;
    }
    
    /**
     * 获取指定uid中指定业务线的权限
     *
     * @param unknown $uids
     * @param unknown $bussiness_lines
     * @return array|unknown
     */
    public function get_existed_staff($staff_codes, $bussiness_lines)
    {
        $rows = $this->_db->from($this->table_name)->where_in('staff_code', $staff_codes)->where('bussiness_line', $bussiness_lines)->get()->result_array();
        if (empty($rows))
        {
            return [];
        }
        foreach ($rows as $row)
        {
            $format[$row['staff_code']] = $row;
        }
        $rows = NULL;
        unset($rows);
        return $format;
    }
    
    /**
     * 一级审核uid
     *
     * @param unknown $bussiness_line
     * @return array
     */
    public function get_first_privilege_staff_codes($bussiness_line)
    {
        $rows = $this->_db->select('staff_code')->from($this->table_name)->where('bussiness_line', $bussiness_line)->where('has_first', GLOBAL_YES)->get()->result_array();
        return array_column($rows, 'staff_code');
    }
    
    /**
     * 二级审核uid,
     *
     * @param unknown $bussiness_line
     * @return array
     */
    public function get_second_privilege_staff_codes($bussiness_line = -1)
    {
        $query = $this->_db->select('staff_code')->from($this->table_name)->where('has_second', GLOBAL_YES);
        if ($bussiness_line != -1)
        {
            $query->where('bussiness_line', $bussiness_line);
        }
        $rows = $query->get()->result_array();
        return array_column($rows, 'staff_code');
    }
    
    /**
     * 三级审核uid
     *
     * @param unknown $bussiness_line
     * @return array
     */
    public function get_three_privilege_staff_codes($bussiness_line = -1)
    {
        $query = $this->_db->select('staff_code')->from($this->table_name)->where('has_three', GLOBAL_YES);
        if ($bussiness_line != -1)
        {
            $query->where('bussiness_line', $bussiness_line);
        }
        $rows = $query->get()->result_array();
        return array_column($rows, 'staff_code');
    }
    
}