<?php 
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/9
 * Time: 14:27
 */
class Oversea_logistics_list_remark_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_oversea_logistics_list_remark';

    }

    /**
     * 获取备注列表
     * @param string $station_code
     * @param string $sku
     * @return mixed
     */
    public function getRemarkList($station_code='',$gid='',$log_id =''){
        $this->db->select('remark,op_uid,op_zh_name,created_at');
        if(!empty($gid)){
            $this->db->where('gid',$gid);
        }
        if(!empty($station_code)){
            $this->db->where('station_code',$station_code);
        }
        if(!empty($log_id)){
            $this->db->where('log_id',$log_id);
        }
        $this->db->from($this->table);
        $this->db->order_by('created_at','DESC');
        return $this->db->get()->result_array();
    }

    /**
     * 按属性id分组获取最新一条的备注列表
     * @param string $station_code
     * @param string $sku
     * @return mixed
     */
    public function getNewByGroupList($log_ids =[],$station_code=''){
        $where = "where 1=1 ";
        if(!empty($station_code)){
            $where.=" and station_code='{$station_code}'";
        }
        if(!empty($log_ids)){
            $where.=" and log_id in (".implode(',',$log_ids).")";
        }
        $sql  = 'select log_id,remark,op_uid,op_zh_name,created_at from `'.$this->table.'` where EXISTS (
                    select `id` from (
                        SELECT max(`id`) as id FROM `'.$this->table.'` '.$where.' group by `log_id`) t 
                    where t.`id`=`'.$this->table.'`.`id`
                )';
        
        return $this->_db->query($sql)->result_array();
    }

    /**
     * 新增备注
     * @param $data
     * @return bool
     */
    public function addRemark($data){
        //写入备注表
        $this->db->insert($this->table,$data);
        $rows = $this->db->affected_rows();
        if($rows > 0){
            return TRUE;
        }else{
            return FALSE;
        }

    }
}