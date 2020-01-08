<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 11:31
 */
class Fba_logistics_cfg_history_model extends MY_Model
{
    public function __construct()
    {
        $this->table_name = 'yibai_fba_logistics_cfg_history';
        parent::__construct();
    }

    /**
     * 获取字段名
     */
    public function all_column()
    {
        $sql    = "select column_name from information_schema.columns where table_name='{$this->table_name}' and table_schema='yibai_plan_stock'";
        $column = $this->db->query($sql)->result_array();
        if (!empty($column)) {
            $column = array_column($column, 'column_name');
        }

        return $column;
    }

    /**
     *
     * 在保留历史配置表里新增一条记录
     *
     * @param string $id
     * @param array  $old_global_cfg
     *
     * @return bool
     */
    public function check_approve_state_add($id = '')
    {
        if (empty($id)) {
            return FALSE;
        }
        $other  = ['station_code', 'rule_type'];//匹配全局规则使用到的字段
        $column = $this->all_column();
        $column = array_merge($column, $other);
        $column = implode(',', $column);

        $info = $this->db->select($column)
            ->from('yibai_fba_logistics_list a')
            ->where_in('id', $id)->get()->result_array();

        $this->load->model('Fba_logistics_list_model', 'm_logistics', false, 'fba');
        $info = $this->m_logistics->joinGlobal($info);


        if (!empty($info)) {
            foreach ($info as $key => &$item) {
                if ($item['approve_state'] != CHECK_STATE_SUCCESS) {
                    unset($info[$key]);
                }
                foreach ($other as $val) {
                    unset($item[$val]);
                }
            }
        }
        if (!empty($info)) {
            return $this->insert($info);
        }
    }

    /**
     * @param array $ids
     * @return number
     */
    public function modify_save_to_history(array $ids)
    {
        if (empty($ids)) return 0;

        $sql = sprintf('
insert into yibai_fba_logistics_cfg_history(id,logistics_id,purchase_warehouse_id,listing_state,as_up,ls,pt,bs,sc,sz,expand_factor,approve_state,created_at)
select
   t_sk.id,
   t_sk.logistics_id,
   t_sk.purchase_warehouse_id,
   t_sk.listing_state,
   if(t_sk.rule_type = 2, gcfg.as_up, t_sk.as_up),
   IF(t_sk.rule_type = 2,
            case t_sk.logistics_id
            when 1 then gcfg.ls_shipping_full
            when 2 then gcfg.ls_shipping_bulk
            when 3 then gcfg.ls_trains_full
            when 4 then gcfg.ls_trains_bulk
            when 6 then gcfg.ls_air
            when 7 then gcfg.ls_red
            when 8 then gcfg.ls_blue
            end,
            t_sk.ls
    ),
   IF(t_sk.rule_type = 2,
            case t_sk.logistics_id
                when 1 then gcfg.pt_shipping_full
                when 2 then gcfg.pt_shipping_bulk
                when 3 then gcfg.pt_trains_full
                when 4 then gcfg.pt_trains_bulk
                when 6 then gcfg.pt_air
                when 7 then gcfg.pt_red
                when 8 then gcfg.pt_blue
                end,
                t_sk.pt
    ),
   if(t_sk.rule_type = 2, gcfg.bs, t_sk.bs),
   if(t_sk.rule_type = 2, gcfg.sc, t_sk.sc),
   if(t_sk.rule_type = 2, gcfg.sz, t_sk.sz),
   t_sk.expand_factor,
   2,
   CURRENT_TIMESTAMP()
from yibai_fba_logistics_list t_sk
LEFT JOIN yibai_fba_global_rule_cfg gcfg on t_sk.station_code = gcfg.station_code
where t_sk.id in (%d) and t_sk.approve_state = %d',
             implode(',', $ids),
            CHECK_STATE_SUCCESS
            );

        return $this->_db->query($sql) ? $this->_db->affected_rows() : 0;
    }

    public function check_approve_state_delete($id = '')
    {
        if (empty($id)) {
            return FALSE;
        }

        return $this->clean($id);
    }


    public function insert($info)
    {
        if (empty($info)) {
            return FALSE;
        }
        return $this->db->insert_batch($this->table_name,$info);
    }

    public function clean($id)
    {
        $this->db->where_in('id', $id);

        return $this->db->delete($this->table_name);
    }
}