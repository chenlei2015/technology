<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 备货列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-07
 * @link 
 */
class Plan_purchase_summary_list_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_purchase_summary';
        $this->primaryKey = 'gid';
        $this->tableId = 60;
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