<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/5/20
 * Time: 16:27
 */
class Weight_sale_cfg_model Extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_weight_sale_cfg';
    }

    public function getCfgInfo($logistics_id)
    {
        return $this->_db
            ->select('*')
            ->from($this->table)
            ->where('logistics_id',$logistics_id)
            ->get()
            ->result_array();
    }

    public function add_cfg($params)
    {
        return $this->_db->insert_batch($this->table,$params);
    }

    public function delete_cfg($logistics_id)
    {
        return $this->_db
            ->where('logistics_id',$logistics_id)
            ->delete($this->table);
    }
    
    /**
     * 获取物流配置中最长时间
     *
     * @return unknown
     */
    public function get_logistics_longest_days()
    {
        return $this->_db->from($this->table_name)
        ->select('logistics_id, max(number_of_days) as longest_days')
        ->group_by('logistics_id')
        ->get()
        ->result_array();
    }
}