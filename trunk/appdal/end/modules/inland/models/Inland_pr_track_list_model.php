<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * Inland 需求跟踪列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link 
 */
class Inland_pr_track_list_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_pr_track';
        $this->primaryKey = 'gid';
        $this->tableId = 40;
        parent::__construct();
    }
    
    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }
    
    public function get_pursn_by_prsn($pr_sns)
    {
        $result = $this->_db->select('pr_sn, pur_sn')
        ->from($this->table_name)
        ->where('pur_sn !=', '')
        ->where_in('pr_sn', $pr_sns)
        ->get()
        ->result_array();
        return key_by($result, 'pur_sn');
    }
}