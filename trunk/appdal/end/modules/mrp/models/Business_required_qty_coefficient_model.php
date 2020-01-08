<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/5/20
 * Time: 16:27
 */
class Business_required_qty_coefficient_model Extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_business_required_qty_coefficient';//业务线逻辑配置需求数量系数
    }

    public function getCfgInfo($business_line)
    {
        return $this->_db
            ->select('*')
            ->from($this->table)
            ->where('business_line',$business_line)
            ->get()
            ->result_array();
    }

    public function add_cfg($params)
    {
        return $this->_db->insert_batch($this->table,$params);
    }

    public function delete_cfg($business_line)
    {
        return $this->_db
            ->where('business_line',$business_line)
            ->delete($this->table);
    }
}