<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * seller_sku 高亮显示
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-06-01
 * @link
 */
class Fba_diff_seller_sku_model extends MY_Model implements Rpcable
{
    
    use Table_behavior, Rpc_imples;
    
    private $_rpc_module = 'fba';
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_fba_diff_seller_sku';
        $this->primaryKey = 'hash';
        $this->tableId = 33;
        parent::__construct();
    }
    
    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }
    
    public function get_exists_hash($hashs)
    {
        if (empty($hashs))
        {
            return [];
        }
        $this->_db->from($this->table_name);
        $this->_db->group_start();
        $hashs = array_chunk($hashs,500);
        foreach($hashs as $hash_part)
        {
            $this->_db->or_where_in('hash', $hash_part);
        }
        $this->_db->group_end();
        return $this->_db->get()->result_array();

//        return $this->_db->from($this->table_name)->where_in('hash', $hashs)->get()->result_array();
    }
}