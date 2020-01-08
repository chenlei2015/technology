<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * bd 修改列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-06-01
 * @link
 */
class Bd_list_model extends MY_Model
{
    
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_bd_list';
        $this->primaryKey = 'hash';
        $this->tableId = 32;
        parent::__construct();
    }
    
    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }
    
    public function get_exists_hash($bussiness_line, $hashs)
    {
        if (empty($hashs)) return [];
        
        $query = $this->_db->from($this->table_name)->where('bussiness_line', $bussiness_line);
        if (!empty($hashs))
        {
            $query->where_in('hash', $hashs);
        }
        //新bd必须后面再跑的时候才显示，而不是当天显示
        $query->where('created_at <', date('Y-m-d H:i:s', strtotime(date('Y-m-d'))));
        return $query->get()->result_array();
    }
}