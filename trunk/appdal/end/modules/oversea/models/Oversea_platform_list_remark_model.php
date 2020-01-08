<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 海外仓remark model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @link
 */
class Oversea_platform_list_remark_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_oversea_platform_list_remark';
        $this->primaryKey = 'gid';
        $this->tableId = 29;
        parent::__construct();
    }
    
    /**
     *
     * @param unknown $params
     * @return bool
     */
    public function add($params)
    {
        return $this->_db->insert($this->table_name, $params);
    }
    
    public function get($gid, $offset = 1, $limit = 20)
    {
        $result = $this->_db->select('user_name,remark,created_at')
                         ->from($this->table_name)
                         ->where('gid', $gid)
                         ->order_by('created_at', 'desc')
                         ->limit($limit, ($offset - 1) * $limit)
                         ->get()
                         ->result_array();
        return $result;
        
    }
    
}