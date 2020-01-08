<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/2
 * Time: 16:40
 */

class Processed_date_model extends MY_Model
{
    public function __construct()
    {
        $this->database = 'stock';
        $this->table = 'yibai_processed_date';
        parent::__construct();
    }

    /**
     * 查询已经生成发运计划的日期
     */
    public function fba_shipment_plan()
    {
        return $this->db->select('date')->where('modules',1)->where('business_line',1)->from($this->table)->result_array();
    }

    public function add($params)
    {
        return $this->db->insert($this->table,$params);
    }

}