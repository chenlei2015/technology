<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**

 */
class Inland_sku_cfg_log_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_sku_cfg_log';
        $this->primaryKey = 'gid';
        $this->tableId = 123;
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

    public function madd($params)
    {
        return $this->_db->insert_batch($this->table_name, $params);
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