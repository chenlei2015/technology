<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * Oversea_new_log_model
 *
 * @author zc
 */
class Oversea_new_log_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_oversea_new_log';
        $this->primaryKey = 'id';
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
        $query = $this->_db->from($this->table_name)->where('id', $gid);
        $counter_query = clone $query;
        $counter_query->select("id");
        $total = $counter_query->count_all_results();
        $result = $query->select('user_name,context,created_at')
            ->order_by('created_at', 'desc')
            ->limit($limit, ($offset - 1) * $limit)
            ->get()
            ->result_array();
        return $result;
    }

    /**
     * 查询日志
     */
    public function getStockLogList($id = '', $offset, $limit)
    {
        $offset_c = (($offset > 0 ? $offset : 1) - 1) * $limit;
        $limit_c = $limit;
        $this->db->select('id,user_name,context,created_at');
        $this->db->where('new_id', $id);
        $this->db->from($this->table_name);
        $db = clone $this->db;
        $total = $db->count_all_results();//获取总条数
        unset($db);
        $this->db->limit($limit_c);
        $this->db->offset($offset_c);
        $this->db->order_by('id','DESC');
        $data['data_list'] = $this->db->get()->result_array();
        $data['page_data'] = array(
            'limit' => (int)$limit,
            'offset' => (int)$offset,
            'total' => (int)$total
        );
        return $data;
    }
}