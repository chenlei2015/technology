<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 10:44
 */
class Inland_special_unique_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_inland_special_unique';
    }

    public function batch_select($batch_unique_str,$batch_sku){

        $this->db->select('unique_str');
        $this->db->where_in('sku',$batch_sku);
        $this->db->where_in('unique_str',$batch_unique_str);
        $this->db->from($this->table);
        return $this->db->get()->result_array();
    }

    public function insert_batch($batch_params){
        $this->db->trans_start();
        $this->db->insert_batch($this->table,$batch_params);
        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE)
        {
            throw new \Exception(sprintf('异常数据,唯一索引插入失败'), 500);
        }
    }

    public function delete_batch($unique_arr)
    {
        $this->db->where_in('sku',array_column($unique_arr,'sku'));
        $this->db->where_in('unique_str',array_column($unique_arr,'unique_str'));
        $this->db->delete($this->table);
    }

}