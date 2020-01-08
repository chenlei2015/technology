<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba 需求统计列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link 
 */
class Mrp_source_from_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_mrp_source_from';
        $this->primaryKey = '';
        $this->tableId = 47;
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