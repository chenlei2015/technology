<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba python老品表
 *
 * @author zc
 * @date 2019-11-11
 */
class Fba_old_model extends MY_Model
{
    private $_rpc_module = 'fba';

    public function __construct()
    {
        $this->primaryKey = 'id';
        $this->tableId = 0;

        $this->database = 'mrp_py';
        $this->table_name = 'old_goods';

        parent::__construct();

    }

    public function get_id($data)
    {
        //md5(account_name,seller_sku,fnsku,asin)
        $id = md5(trim($data['account_name']).trim($data['seller_sku']).trim($data['fnsku']).trim($data['asin']));
        return $id;
    }

    public function get_config_by_aggr_ids($aggr_ids)
    {
        $result = $this->_db->from($this->table_name)
            ->select('id,account_id,seller_sku,fnsku,asin')
            ->where_in('id', $aggr_ids)
            ->get()
            ->result_array();

        $result = key_by($result, 'id');
        return $result;
    }

    public function exists_table()
    {
        return empty($this->_db->query("show tables like '{$this->table_name}'")->result_array()) ? false : true;
    }

    public function exists_data()
    {
        return empty($this->_db->select('id')->from($this->table_name)->limit(1)->get()->result_array()) ? false : true;
    }


    public function exists_id($id = 0)
    {
        return empty($this->_db->select('id')->from($this->table_name)->where('id',$id)->limit(1)->get()->row_array()) ? false : true;
    }

    public function exists_ids($ids = [])
    {
        if(empty($ids)) return [];
        return $this->_db->select('id,seller_sku')->from($this->table_name)->where_in('id',$ids)->limit(count($ids))->get()->result_array();
    }




    public function is_exists_python_config()
    {
        return $this->exists_data();
    }
}
