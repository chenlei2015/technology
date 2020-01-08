<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 调拨单表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-11
 * @link 
 */
class Allotment_order_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_allotment_order';
        $this->primaryKey = 'gid';
        $this->tableId = 62;
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
    
}