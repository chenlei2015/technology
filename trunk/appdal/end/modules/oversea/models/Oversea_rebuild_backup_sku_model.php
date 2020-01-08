<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 国内重建需求多版本控制model
 *
 * @package -
 * @subpackage -
 * @category -
 * @link
 */
class Oversea_rebuild_backup_sku_model extends MY_Model
{

    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_oversea_rebuild_backup_sku_cfg';
        $this->primaryKey = 'version';
        $this->tableId = 0;
        parent::__construct();
    }

    public function get_config_by_aggr_ids($version, $aggr_ids)
    {
        $result = $this->_db->from($this->table_name)
        ->where_in('aggr_id', $aggr_ids)
        ->where('version', $version)
        ->get()
        ->result_array();

        $result = key_by($result, 'aggr_id');
        return $result;
    }

    //todo:确认字段
    public function backup($version)
    {
        //from yibai_inland_sku_cfg t_sk
        $insert_sql = 'INSERT INTO yibai_oversea_rebuild_backup_sku_cfg (`version`,aggr_id,sku,max_safe_stock_day,reduce_factor,bs,sp,shipment_time,sc,first_lt,sz,stock_way)
        select '.$version.',
        md5(CONCAT_WS("", t_sk.sku, t_sk.is_refund_tax, t_sk.purchase_warehouse_id)),
    	t_sk.sku,
    	t_sk.max_safe_stock_day ,
        t_sk.reduce_factor,
    	IF(t_sk.state = 2, IF(t_sk.rule_type = 1, t_sk.bs, @bs), t_sk_his.bs) as bs,
    	IF(t_sk.state = 2, IF(t_sk.rule_type = 1, t_sk.sp, @sp), t_sk_his.sp) as sp,
    	IF(t_sk.state = 2, IF(t_sk.rule_type = 1, t_sk.shipment_time, @shipment_time), t_sk_his.shipment_time) as shipment_time,
    	IF(t_sk.state = 2, IF(t_sk.rule_type = 1, t_sk.sc, @sc), t_sk_his.sc) as sc,
    	IF(t_sk.state = 2, IF(t_sk.rule_type = 1, t_sk.first_lt, @first_lt), t_sk_his.first_lt) as first_lt,
    	IF(t_sk.state = 2, IF(t_sk.rule_type = 1, t_sk.sz, @sz), t_sk_his.sz) as sz,
        IF(t_sk.state = 2, t_sk.stock_way, t_sk_his.stock_way) as stock_way
        from 
        (SELECT p.*,m.sku,m.is_refund_tax,m.purchase_warehouse_id,m.rule_type FROM `yibai_oversea_sku_cfg_main` m LEFT JOIN `yibai_oversea_sku_cfg_part` p ON m.gid = p.gid) as t_sk
        left join yibai_oversea_sku_cfg_history_part t_sk_his on t_sk.gid = t_sk_his.gid and t_sk.state != 2,
        (select @bs:=bs, @sp:=sp, @shipment_time:=shipment_time, @sc:=sc, @first_lt:=first_lt, @sz:=sz from yibai_oversea_global_rule_cfg limit 1) as  gcfg
        ';
        if ($this->_db->query($insert_sql)) {
            return $this->_db->affected_rows();
        }
        return 0;
    }

}