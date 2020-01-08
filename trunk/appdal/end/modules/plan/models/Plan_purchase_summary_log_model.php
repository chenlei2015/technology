<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 海外仓汇总log列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @link 
 */
class Plan_purchase_summary_log_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_purchase_summary_log';
        $this->primaryKey = 'gid';
        $this->tableId = 48;
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
    
    public function get($gid, &$total,  $offset = 1, $limit = 20)
    {
        $query = $this->_db->from($this->table_name)->where('gid', $gid);
        $counter_query = clone $query;
        $counter_query->select("gid");
        $total = $counter_query->count_all_results();
        $result = $query->select('user_name,context,created_at')
        ->order_by('created_at', 'desc')
        ->limit($limit, ($offset - 1) * $limit)
        ->get()
        ->result_array();
        return $result;
    }

}