<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 发运计划 - Oversea列表
 *
 * @version 1.2.0
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-07-09
 * @link
 */
class Oversea_list_model extends MY_Model implements Rpcable
{
    
    use Table_behavior, Rpc_imples;
    
    private $_rpc_module = 'shipment';
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_shipment_oversea_list';
        $this->primaryKey = 'gid';
        $this->tableId = 154;
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
    /**
     * 检查是否为待推送
     */
//    public function check_gid($gid)
//    {
//        $sql = "SELECT detail.*,plan.push_status FROM yibai_shipment_oversea_list detail LEFT JOIN yibai_shipment_plan_oversea_list plan
//ON detail.shipment_sn = plan.shipment_sn WHERE detail.gid = '{$gid}'";
//        $result = $this->_db->query($sql)->row_array();
//        return $result;
//    }


    public function check_gid($gid)
    {
        $sql = "SELECT detail.*,plan.push_status FROM yibai_shipment_oversea_list detail LEFT JOIN yibai_shipment_plan_oversea_list plan
ON detail.shipment_sn = plan.shipment_sn WHERE detail.gid IN ({$gid})";
        $result = $this->_db->query($sql)->result_array();
        return $result;
    }

    public function get_shipment_qty($params)
    {
        $result = $this->_db->select('gid,shipment_qty')
            ->from($this->table_name)
            ->where('sku',$params['sku'])
            ->where('shipment_sn',$params['shipment_sn'])
            ->get()
            ->result_array();

        return array_column($result,'shipment_qty','gid');
    }

    //获取sku对应可用库存
    public function get_available_inventory($params)
    {
        $result = $this->_db->select('sku,available_inventory')
            ->from($this->table_name)
            ->where_in('sku',$params['all_sku'])
            ->where('shipment_sn',$params['shipment_sn'])
            ->get()
            ->result_array();

        return array_column($result,'available_inventory','sku');
    }


    //导出状态为已推送的跟踪列表
    public function get_track_list($shipment_sn)
    {
        $this->_db->select('a.*,b.logistics_sn,b.shipment_qty,b.logistics_status,b.receipt_qty')
            ->from($this->table_name.' a')
            ->join('yibai_shipment_logistics_oversea b','a.pr_sn=b.pr_sn','left')
            ->where('shipment_sn',$shipment_sn)
            ->order_by('a.created_at desc, a.gid desc');
        $query_counter = clone $this->_db;
        $count = $query_counter->count_all_results();
        $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);

        $sql = $this->_db->get_compiled_select();

        return $total.$sql;
    }
}