<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * Inland summary remark model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @link 
 */
class Inland_special_pr_list_remark_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_special_pr_list_remark';
        $this->primaryKey = 'gid';
        $this->tableId = 36;
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

    public function insert_batch($batch_params)
    {
        $this->_db->trans_start();
        $this->_db->insert_batch($this->table_name, $batch_params);
        $this->_db->trans_complete();

        if ($this->_db->trans_status() === FALSE)
        {
            throw new \Exception(sprintf('手工单备注信息写入数据库失败'), 500);
        }
    }
    
}