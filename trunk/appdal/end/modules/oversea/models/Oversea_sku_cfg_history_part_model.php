<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 11:31
 */
class Oversea_sku_cfg_history_part_model extends MY_Model
{
    public function __construct()
    {
        $this->table_name = 'yibai_oversea_sku_cfg_history_part';
        parent::__construct();
    }

    public function insert($info)
    {
        return $this->db->insert($this->table_name,$info);
    }

    public function clean($gid)
    {
        return $this->db->delete($this->table_name,['gid'=>$gid]);
    }

    public function clean_all($gid)
    {
        $this->db->where_in('gid',$gid);
        return $this->db->delete($this->table_name);
    }


}
