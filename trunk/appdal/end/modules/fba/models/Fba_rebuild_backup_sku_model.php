<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba重建需求多版本控制model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-09-05
 * @link
 */
class Fba_rebuild_backup_sku_model extends MY_Model
{

    use Table_behavior;

    private $_rpc_module = 'fba';

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_fba_rebuild_backup_sku_cfg';
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

    public function backup($version)
    {
        //$ci = CI::$APP;
        //$ci->load->model('Fba_logistics_list_model', 'm_logistics', false, 'fba');
        //$ci->load->model('Fba_sku_cfg_model', 'm_sku_cfg', false, 'fba');

        $insert_sql = 'INSERT INTO yibai_fba_rebuild_backup_sku_cfg (version,aggr_id,sku,delivery_cycle,as_up,bs,sc,sz,expand_factor,ls,pt,max_sp,max_lt,max_safe_stock,sp,lt,accelerate_sale_end_time,is_contraband,listing_state,is_warehouse_days_90, is_first_sale, first_require_qty, logistics_id)
        select
          '.$version.',
        	md5(CONCAT_WS("", t_sk.account_id, trim(t_sk.account_num), trim(t_sk.sku), trim(t_sk.seller_sku), trim(t_sk.fnsku), trim(t_sk.asin))) AS aggr_id,
          t_sk.sku,
        	t_sk.delivery_cycle,
        	IF(t_sk.approve_state = 2, IF(t_sk.rule_type = 1, t_sk.as_up, gcfg.as_up), t_sk_his.as_up) as as_up,
        	IF(t_sk.approve_state = 2, IF(t_sk.rule_type = 1, t_sk.bs, gcfg.bs), t_sk_his.bs) as bs,
        	IF(t_sk.approve_state = 2, IF(t_sk.rule_type = 1, t_sk.sc, gcfg.sc), t_sk_his.sc) as sc,
        	IF(t_sk.approve_state = 2, IF(t_sk.rule_type = 1, t_sk.sz, gcfg.sz), t_sk_his.sz) as sz,
        	IF(t_sk.approve_state = 2, t_sk.expand_factor, t_sk_his.expand_factor) as expand_factor,
              IF(t_sk.approve_state = 2, IF(t_sk.rule_type = 1, t_sk.ls,
            		case t_sk.logistics_id
            		when 1 then gcfg.ls_shipping_full
            		when 2 then gcfg.ls_shipping_bulk
            		when 3 then gcfg.ls_trains_full
            		when 4 then gcfg.ls_trains_bulk
            		when 6 then gcfg.ls_air
            		when 7 then gcfg.ls_red
            		when 8 then gcfg.ls_blue
            		end
            ), t_sk_his.ls) as ls,
        	IF(t_sk.approve_state = 2, IF(t_sk.rule_type = 1, t_sk.pt,
        	case t_sk.logistics_id
        		when 1 then gcfg.pt_shipping_full
        		when 2 then gcfg.pt_shipping_bulk
        		when 3 then gcfg.pt_trains_full
        		when 4 then gcfg.pt_trains_bulk
        		when 6 then gcfg.pt_air
        		when 7 then gcfg.pt_red
        		when 8 then gcfg.pt_blue
        		end
        ), t_sk_his.pt) as pt,
        t_erpsku.max_sp,t_erpsku.max_lt,t_erpsku.max_safe_stock,t_erpsku.sp,t_erpsku.lt, t_erpsku.accelerate_sale_end_time,
        t_erpsku.is_contraband,
        IF(t_sk.approve_state = 2, t_sk.listing_state, t_sk_his.listing_state) as listing_state,
        IF(age.inv_age_365_plus_days + age.inv_age_271_to_365_days + age.inv_age_181_to_270_days + age.inv_age_91_to_180_days <= 0, 2 , 1) as is_warehouse_days_90,
        IF(fst.id, 1, 2) as is_first_sale,
        IF(fst.id, fst.demand_num - fst.stock_num, 0) as first_require_qty,
        t_sk.logistics_id
        from yibai_fba_logistics_list t_sk
        left join yibai_fba_logistics_cfg_history t_sk_his on t_sk.id = t_sk_his.id and t_sk.approve_state != 2
        LEFT JOIN yibai_fba_global_rule_cfg gcfg on t_sk.station_code = gcfg.station_code and t_sk.rule_type = 2
        INNER JOIN (
        	select
        		t_es.sku,
        		IF(t_es.state = 2, t_es.max_sp, t_ehis.max_sp) as max_sp,
        		IF(t_es.state = 2, t_es.max_lt, t_ehis.max_lt) as max_lt,
        		IF(t_es.state = 2, t_es.max_safe_stock, t_ehis.max_safe_stock) as max_safe_stock,
        		IF(t_es.state = 2, t_es.sp, t_ehis.sp) as sp,
        		t_es.lt,
        		t_es.accelerate_sale_end_time,
                IF(t_es.state = 2, t_es.is_contraband, t_ehis.is_contraband) as is_contraband
        	from yibai_fba_sku_cfg t_es
        	left join yibai_fba_sku_cfg_history t_ehis on t_es.state != 2 and t_es.id = t_ehis.id
        ) t_erpsku on t_sk.sku = t_erpsku.sku
       left join yibai_amazon_fba_inventory_aged_new age on t_sk.account_id = age.account_id and t_sk.seller_sku = age.sku and t_sk.fnsku = age.fnsku and t_sk.asin = age.asin
       left join yibai_fba_new_cfg fst on fst.id = md5(CONCAT_WS("", trim(t_sk.sku), t_sk.account_id, trim(t_sk.station_code), trim(t_sk.seller_sku), trim(t_sk.fnsku), trim(t_sk.asin))) and fst.is_delete = 0 ';
        //trim(erpsku)、账号、站点、trim(sellersku)、trim(fnsku)、trim(asin)
        //left join yibai_fba_new_cfg first on t_sk.account_id = first.account_id and t_sk.seller_sku = first.sku and t_sk.fnsku = first.fnsku
        //and t_sk.asin = first.asin and t_sk.station_code = first.site and t_sk.sku = first.erp_sku and first.is_delete = 0

        if ($this->_db->query($insert_sql)) {
            return $this->_db->affected_rows();
        }
        return 0;
    }

}