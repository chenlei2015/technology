<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 *
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-09-05
 * @link
 */
class Fba_auto_approve_log_model extends MY_Model
{

    use Table_behavior;

    private $_rpc_module = 'fba';

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_auto_approve_log';
        $this->primaryKey = 'key';
        $this->tableId = 0;
        parent::__construct();
    }

    public function get_today_logs($business_line)
    {
        $start = date('Y-m-d').' 00:00:00';
        $end = date('Y-m-d').' 23:59:59';
        
        $query = $this->_db->from($this->table_name)
        ->where('business_line', $business_line)
        ->where("created_at between '{$start}' and '${end}' ")
        ->get()
        ->result_array();
        
        return $query;
    }
    
    
    


}