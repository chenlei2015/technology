<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 发运计划 - Fba列表
 *
 * @version 1.2.0
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-07-09
 * @link
 */
class Oversea_shipment_logistics_model extends MY_Model implements Rpcable
{

    use Table_behavior, Rpc_imples;

    private $_rpc_module = 'shipment';

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_shipment_logistics_oversea';
        $this->primaryKey = 'gid';
        $this->tableId = 155;
        parent::__construct();
    }

    public function tableId()
    {
        return $this->tableId;
    }

    public function pk($gid)
    {
        $result = $this->_db->from($this->table_name)->where('gid', $gid)->limit(1)->get()->result_array();
        return $result ? $result[0] : [];
    }

    /**
     * 不带兼容方式的批量更新
     *
     * @param unknown $batch_params
     * @return unknown
     */
    public function batch_update($batch_params)
    {
        return $this->_db->update_batch($this->table_name, $batch_params, 'gid');
    }

    /**
     * 批量插入
     */
    public function batch_insert($batch_arr)
    {
        return $this->_db->insert_batch($this->table_name,$batch_arr);
    }

    public function get_shipment_detail($pr_sn)
    {
        return $this->_db->select('pr_sn,logistics_sn,logistics_status,shipment_qty,receipt_qty')
            ->from($this->table_name)
            ->where('pr_sn',$pr_sn)
            ->get()
            ->result_array();
    }


}