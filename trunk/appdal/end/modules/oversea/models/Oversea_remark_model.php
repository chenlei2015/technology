<?php 
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 11:31
 */
class Oversea_remark_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_oversea_remark';

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
     * 新增备注
     * @param $data
     * @return bool
     */
    public function addRemark($data)
    {
        if (!is_array($data)) {
            return FALSE;
        }
        //更新附表备注
        if (!empty($data['gid'])) {
            $this->load->model('Oversea_sku_cfg_part_model', 'm_part');
            $this->m_part->updateRemark($data);
        }
        //全局规则配置表备注
        if (!empty($data['station_code'])) {
            $this->load->model('Oversea_global_rule_cfg_model', 'm_global');
            $this->m_global->updateRemark($data);
        }
        //写入备注表
        $this->db->insert($this->table, $data);
        $rows = $this->db->affected_rows();
        if ($rows > 0) {
            return TRUE;
        } else {
            return FALSE;
        }
    }
}