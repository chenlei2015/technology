<?php 

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 11:31
 */
class Fba_remark_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_fba_remark';

    }

    /**
     * 获取备注列表
     * @param string $station_code
     * @param string $sku
     * @return mixed
     */
    public function getRemarkList($station_code = '', $gid = '', $log_id = '')
    {
        $this->db->select('remark,op_uid,op_zh_name,created_at');
        if (!empty($gid)) {
            $this->db->where('sku_id', $gid);
        }
        if (!empty($station_code)) {
            $this->db->where('station_code', $station_code);
        }
        if (!empty($log_id)) {
            $this->db->where('log_id', $log_id);
        }
        $this->db->from($this->table);
        $this->db->order_by('created_at', 'DESC');
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
        $this->db->trans_start();
        //ERPSKU属性配置表备注
        if (!empty($data['sku_id'])) {
            $this->load->model('Fba_sku_cfg_model', 'm_sku_cfg');
            $this->m_sku_cfg->updateRemark($data);
        }
        //全局规则配置表备注
        if (!empty($data['station_code'])) {
            $this->load->model('Global_rule_cfg_model', 'm_global');
            $this->m_global->updateRemark($data);
        }
        //物流属表备注
        if (!empty($data['log_id'])) {
            $this->load->model('Fba_logistics_list_model', 'm_logistics');
            $this->m_logistics->updateRemark($data);
        }
        //写入备注表
        $this->db->insert($this->table, $data);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    }
}