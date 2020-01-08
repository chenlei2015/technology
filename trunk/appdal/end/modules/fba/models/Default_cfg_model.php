<?php 

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 11:31
 */
class Default_cfg_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_default_cfg';
    }

    /**
     * 查询fba的物流配置
     */
    public function getFbaDetail($staff_code){
        $this->db->select('*');
        $this->db->where('default_key',1);
        $this->db->where('op_uid',$staff_code);
        $this->db->from($this->table);
        return $this->db->get()->row_array();
    }


    /**
     * 修改
     * @param string $logistics_id
     * @param string $op_uid
     * @param string $op_zh_name
     */
    public function modify($params){
        $result = $this->getFbaDetail($params['op_uid']);
        if(empty($result)){
            $params['default_key'] = 1;
            $this->db->insert($this->table,$params);
        }else{
            $this->db->where('default_key',1);
            $this->db->update($this->table,$params);
        }
    }

    /**
     * 查询海外仓的物流配置
     */
    public function getOverseaDetail($staff_code){
        $this->db->select('*');
        $this->db->where('default_key',2);
        $this->db->where('op_uid',$staff_code);
        $this->db->from($this->table);
        return $this->db->get()->row_array();
    }

    /**
     * 修改
     * @param string $logistics_id
     * @param string $op_uid
     * @param string $op_zh_name
     */
    public function overseaModify($params){
        $result = $this->getOverseaDetail($params['op_uid']);
        if(empty($result)){
            $params['default_key'] = 2;
            $this->db->insert($this->table,$params);
        }else{
            $this->db->where('default_key',2);
            $this->db->update($this->table,$params);
        }
    }
}