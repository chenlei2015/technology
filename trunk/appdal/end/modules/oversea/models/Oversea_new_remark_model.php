<?php

/**
 * User: zc
 * Date: 2019/12/13
 */
class Oversea_new_remark_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_oversea_new_remark';
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
        $this->db->order_by('id', 'DESC');
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
        //新品表备注
        if (!empty($data['new_id'])) {
            $this->load->model('Fba_new_list_model', 'm_new');
            $this->m_new->updateRemark($data);
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

    public function get_remark($id = '',$new_id = '')
    {
        $this->db->select('remark,op_uid,op_zh_name,created_at');
        if (!empty($id)) {
            $this->db->where('id', $id);
        }
        if (!empty($new_id)) {
            $this->db->where('new_id', $new_id);
        }
        $this->db->from($this->table_name);
        $this->db->order_by('id', 'DESC');
        return $this->db->get()->result_array();
    }
}