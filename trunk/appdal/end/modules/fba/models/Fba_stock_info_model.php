<?php
require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

class Fba_stock_info_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_fba_stock_info';
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

    public function get($id)
    {
        $this->_db->select('id,stock_no,stock_num');
        $this->_db->where('new_id', $id);
        $this->_db->from($this->table_name);
        $this->_db->order_by('id','DESC');
        $result = $this->_db->get()->result_array();
        return $result;
    }
}