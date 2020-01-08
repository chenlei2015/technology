<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * Suggest_log_model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @link 
 */
class System_log_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'log';
        $this->table_name = 'yibai_system_log';
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
    
    public function get($module)
    {
        $result = $this->_db->select('user_name,context,created')
                         ->from($this->table_name)
                         ->where('module', $module)
                         ->get()
                         ->result_array();
        if (empty($result)) return [];
        return $result;
        
    }
    
}