<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 发运计划列表model
 *
 * @version 1.2.0
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-07-09
 * @link
 */
class Fba_shipment_list_model extends MY_Model implements Rpcable
{
    
    use Table_behavior, Rpc_imples;
    
    private $_rpc_module = 'shipment';
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_shipment_plan_fba_list';
        $this->primaryKey = 'gid';
        $this->tableId = 151;
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
     * 生成发运计划
     */
    public function addPlan($params)
    {
        return $this->_db->insert($this->table_name,$params);
    }

    public function get_today_used_shipment_sns($sn_prefix, $cut_length)
    {
        $today_start = mktime(0, 0, 0, intval(date('m')), intval(date('d')), intval(date('y')));
        $today_end = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('y')));

        $max_sum_sn = $this->_db->select('shipment_sn')
            ->from($this->table_name)
            ->where('created_at >= ', $today_start)
            ->where('created_at <= ', $today_end)
            ->order_by('shipment_sn', 'desc')
            ->limit(1)
            ->get()
            ->result_array();

        if (empty($max_sum_sn))
        {
            return [];
        }
        $start = strlen($sn_prefix);
        $seq_char = substr($max_sum_sn[0]['shipment_sn'], $start, $cut_length);
        $result = $this->_db->select('shipment_sn')->from($this->table_name)->like('shipment_sn', $sn_prefix.$seq_char, 'after')->get()->result_array();
        if (empty($result)) {
            return [];
        }
        $pr_sns = array_column($result, 'shipment_sn');
        sort($pr_sns);
        return [$seq_char, $pr_sns];
    }

    /**
     * 发运详情页
     */
    public function sp_info($shipment_sn)
    {
        return $this->_db->select('gid,shipment_sn,push_status,created_uid,created_at')
            ->from($this->table_name)
            ->where('shipment_sn',$shipment_sn)
            ->get()->row_array();
    }

    /**
     * 返回推送状态
     */
    public function getStatus($shipment_sn,$status)
    {
        $result = $this->_db->select('shipment_sn')->from($this->table_name)->where_in('shipment_sn',$shipment_sn)->where('push_status',$status)->get()->result_array();
        if(empty($result)){
            return [];
        }
        return array_column($result,'shipment_sn');
    }
}