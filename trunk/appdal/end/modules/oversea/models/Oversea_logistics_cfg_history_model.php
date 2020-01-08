<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 11:31
 */
class Oversea_logistics_cfg_history_model extends MY_Model
{
    public function __construct()
    {
        $this->table_name = 'yibai_oversea_logistics_cfg_history';
        parent::__construct();
    }

    public function insert($info)
    {
        return $this->db->insert($this->table_name,$info);
    }

    public function clean($id)
    {
        return $this->db->delete($this->table_name,['id'=>$id]);
    }

    public function clean_all($ids)
    {
        $this->db->where_in('id',$ids);
        return $this->db->delete($this->table_name);
    }
}
