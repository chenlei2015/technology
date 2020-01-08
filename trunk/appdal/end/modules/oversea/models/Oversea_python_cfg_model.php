<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 海外 python配置参数列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-09-05
 * @link
 */
class Oversea_python_cfg_model extends MY_Model
{

    public function __construct()
    {
        $this->primaryKey = 'id';
        $this->tableId = 0;

        $this->database = 'mrp_py';
        $this->table_name = 'unknown_'.date('Ymd');

        parent::__construct();

        //采用自己的备份
        if (!$this->exists_table() || !$this->exists_data()) {
            $this->database = 'stock';
            $this->table_name = 'yibai_oversea_python_cfg';
            parent::__construct();
        }

    }

    public function get_config_by_aggr_ids($aggr_ids)
    {
        $result = $this->_db->from($this->table_name)
//        ->select('aggr_id, sku,is_refund_tax,purchase_warehouse_id,debt_qty,pr_qty,ship_qty,available_qty,weight_sale_pcs,sale_sd_pcs,deliver_sd_day,supply_wa_day,sale_amount_15_days,sale_amount_30_days,exhausted_days')
        ->select('id,aggr_id, sku,site,platform,weight_sale_pcs,sale_sd_pcs,sale_amount_15_days,sale_amount_30_days,available_qty,oversea_ship_qty,exchange_up_qty')
        ->where_in('aggr_id', $aggr_ids)
        ->get()
        ->result_array();

        $result = key_by($result, 'aggr_id');

        return $result;
    }

    public function exists_table()
    {
        return empty($this->_db->query("show tables like '{$this->table_name}'")->result_array()) ? false : true;
    }

    public function exists_data()
    {
        return empty($this->_db->select('aggr_id')->from($this->table_name)->limit(1)->get()->result_array()) ? false : true;
    }

    public function is_exists_python_config()
    {
        return $this->exists_data();
    }

}