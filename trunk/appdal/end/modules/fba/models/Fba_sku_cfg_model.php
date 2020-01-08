<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * @version 1.2.2
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @date 2019-09-04
 * @link
 */
class Fba_sku_cfg_model extends MY_Model
{

    use Table_behavior;

    private $_rpc_module = 'fba';

    public function __construct()
    {
        $this->database   = 'stock';
        $this->table_name = 'yibai_fba_sku_cfg';
        $this->table      = 'yibai_fba_sku_cfg';
        $this->primaryKey = 'id';
        $this->tableId    = 152;
        parent::__construct();
    }

    public function tableId()
    {
        return $this->tableId;
    }

    public function pk($id)
    {
        $result = $this->_db->from($this->table)->where('id', $id)->limit(1)->get()->result_array();

        return $result ? $result[0] : [];
    }

    /**
     * 批量审核成功
     *
     * @param array  $id
     * @param string $uid
     *
     * @return mixed
     */
    public function batchCheckSuccess($id = [], $uid = '', $user_name = '')
    {
        try {
            $this->load->model('Fba_sku_cfg_history_model', 'm_cfg_history_model', false, 'fba');
            $this->load->model('Stock_log_model', 'm_log', false, 'fba');
            $processed  = 0;//已处理
            $undisposed = 0;//未处理
            foreach ($id as $key => $value) {
                //查询状态
                $this->db->select('state');
                $this->db->where('id', $value);
                $this->db->from($this->table);
                $state = $this->db->get()->row_array();
                if ($state['state'] == CHECK_STATE_INIT) {
                    $key_map = [
                        'userNumber' => 'approved_uid',
                        'userName'   => 'approved_zh_name',
                    ];
                    $data    = [
                        'state'       => CHECK_STATE_SUCCESS,
                        'approved_at' => date('Y-m-d H:i:s')
                    ];
                    append_login_info($data, $key_map);

                    $this->db->trans_start();
                    $this->db->where('id', $value);
                    $this->db->update($this->table, $data);
                    $detail = $this->getOne($value);
                    //写入日志
                    $this->m_log->checkStockLog($detail, $flag = 1);
                    $this->m_cfg_history_model->check_approve_state_delete($value);
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $undisposed++;
                        log_message('ERROR', "{$value}审核异常");
                    }
                    $processed++;
                } else {
                    $undisposed++;
                }
            }
            $result['processed']  = $processed;
            $result['undisposed'] = $undisposed;

            return $result;
        } catch (\Exception $e) {
            log_message('ERROR', sprintf('批量审核异常', $e->getMessage()));
            throw new \RuntimeException(sprintf('批量审核异常'), 500);
        }
    }

    /**
     * 批量审核失败
     *
     * @param array  $id
     * @param string $uid
     *
     * @return mixed
     */
    public function batchCheckFail($id = [], $uid = '', $user_name = '')
    {
        try {
            $this->load->model('Fba_sku_cfg_history_model', 'm_cfg_history_model', false, 'fba');
            $this->load->model('Stock_log_model', 'm_log', false, 'fba');
            $processed  = 0;//已处理
            $undisposed = 0;//未处理
            foreach ($id as $key => $value) {
                //查询状态
                $this->db->select('state');
                $this->db->where('id', $value);
                $this->db->from($this->table);
                $state = $this->db->get()->row_array();
                if ($state['state'] == CHECK_STATE_INIT) {
                    $key_map = [
                        'userNumber' => 'approved_uid',
                        'userName'   => 'approved_zh_name',
                    ];
                    $data    = [
                        'state'       => CHECK_STATE_FAIL,
                        'approved_at' => date('Y-m-d H:i:s')
                    ];
                    append_login_info($data, $key_map);

                    $this->db->trans_start();
                    $this->db->where('id', $value);
                    $this->db->update($this->table, $data);
                    $detail = $this->getOne($value);
                    //写入日志
                    $this->m_log->checkStockLog($detail, $flag = 2);
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $undisposed++;
                        log_message('ERROR', "{$value}审核异常");
                    }
                    $processed++;
                } else {
                    $undisposed++;
                }
            }
            $result['processed']  = $processed;
            $result['undisposed'] = $undisposed;

            return $result;
        } catch (\Exception $e) {
            log_message('ERROR', sprintf('批量审核异常', $e->getMessage()));
            throw new \RuntimeException(sprintf('批量审核异常'), 500);
        }
    }

    /**
     * 更新备注
     */
    public function updateRemark($data)
    {
        $this->db->where('id', $data['sku_id']);
        $this->db->update($this->table, ['remark' => $data['remark']]);
    }


    /**
     * 查询配置详情
     *
     * @param $station_code
     * @param $sku
     *
     * @return mixed
     */
    public function getDetail($id)
    {
        $this->db->where('id', $id);
        $this->db->from($this->table);
        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sp,sz');

        return $this->db->get()->row_array();
    }


    /**
     * 更新审核人审核时间
     *
     * @param $id
     * @param $uid
     */
    public function check($id, $uid, $user_name)
    {
        $this->db->where('id', $id);
        $this->db->update($this->table, ['approved_at' => date('Y-m-d H:i:s', time()), 'approved_uid' => $uid, 'approved_zh_name' => $user_name]);
    }

    /**
     * 查询单个配置详情
     *
     * @param $id
     */
    public function getOne($id)
    {
        $this->db->where('id', $id);
        $this->db->from($this->table);
        $this->db->select('id,is_contraband,sp,max_sp,max_lt,max_safe_stock');
        $result = $this->db->get()->row_array();

        return $result;
    }

    /**
     * 如果用户没有修改供货周期
     * 取lead_time表的数据
     */
    public function get_lead_time($sku)
    {
        $lt = $this->db->select('arrival_time')->where('sku', $sku)->get('yibai_fba_lead_time')->row_array();
        if (!isset($lt['arrival_time'])) {
            $this->db->select('default_arrival_time')->from('yibai_fba_lead_time')->limit(1);
            $lt = $this->db->get()->row_array();

            return $lt['default_arrival_time'];
        } else {
            return $lt['arrival_time'];
        }
    }


    /**
     * 查询规则类型
     */
    public function getRuleType($id)
    {
        $this->db->where('id', $id);
        $this->db->from('yibai_fba_sku_cfg');
        $this->db->select('rule_type');
        $result = $this->db->get()->row_array();

        return $result['rule_type'];
    }

    /**
     *
     * 在保留历史配置表里新增一条记录
     *
     * @param string $id
     * @param array  $old_global_cfg
     *
     * @return bool
     */
    public function check_approve_state_add($id = '', $old_global_cfg = [], $old_rule_type = '')
    {
        if (empty($id)) {
            return FALSE;
        }
        $info = $this->db->select('*')->from($this->table)->where('id', $id)->get()->row_array();
        if (!empty($info) && $info['state'] == 2 && $old_rule_type == 1) {//审核成功状态保留配置
            return $this->m_cfg_history_model->insert($info);
        }
        if (!empty($info) && $info['state'] == 2 && $old_rule_type == 2) {//保留全局配置
            $old_global_cfg['id']               = $info['id'];
            $old_global_cfg['updated_at']       = $info['updated_at'];
            $old_global_cfg['updated_uid']      = $info['updated_uid'];
            $old_global_cfg['updated_zh_name']  = $info['updated_zh_name'];
            $old_global_cfg['state']            = $info['state'];
            $old_global_cfg['approved_at']      = $info['approved_at'];
            $old_global_cfg['approved_uid']     = $info['approved_uid'];
            $old_global_cfg['approved_zh_name'] = $info['approved_zh_name'];
            $old_global_cfg['remark']           = $info['remark'];

            return $this->m_cfg_history_model->insert($old_global_cfg);
        } else {
            return TRUE;
        }
        exit;
    }

    public function check_approve_state_delete($id = '')
    {
        if (empty($id)) {
            return FALSE;
        }

        return $this->m_cfg_history_model->clean($id);
    }

    public function get_cfg($id)
    {
        if (empty($id)) {
            return [];
        }
        $result = $this->db->select('is_contraband,max_sp,max_lt,max_safe_stock,sp,id')
            ->where_in('id', $id)
            ->get($this->table)
            ->result_array();

        if (!empty($result)) {
            $result = array_column($result, NULL, 'id');
        } else {
            $result = false;
        }

        return $result;

    }

    /**
     * 通过sku匹配供货周期表,未匹配的默认15
     *
     * @param $params
     *
     * @return mixed
     */
    public function join_lead_time($params)
    {
        if (empty($params)) {
            return $params;
        }
        $result = $this->db->select('sku', 'arrival_time')->group_by('sku')->get('yibai_fba_lead_time')->result_array();
        if (!empty($result)) {
            $result = array_column($result, 'arrival_time', 'sku');
        }
        foreach ($params as $key => &$item) {
            $item['lt'] = isset($result[$item['sku']]) ? $result[$item['sku']] : '15';
        }

        return $params;
    }

    public function lead_time_map()
    {
        $result = $this->db->select('sku', 'arrival_time')->group_by('sku')->get('yibai_fba_lead_time')->result_array();
        if (!empty($result)) {
            $result = array_column($result, 'arrival_time', 'sku');
        }

        return $result;
    }

    public function update_column($data)
    {
        $this->_db->update($this->table_name, $data);
        return $this->_db->affected_rows();
    }

    public function get_infos_by_column($data = [])
    {
        $this->db->select("id,{$data['column']}");
        $this->db->from($this->table_name);
        return $this->db->get()->result_array();
    }

    /**
     * @param array $suppliers
     */
    public function get_supplier_moq_info(array $suppliers)
    {
        $query = $this->_db->from($this->table_name)
        ->select('supplier_code,sku,moq_qty,original_min_start_amount as moq_step1,min_start_amount as moq_step2');
        if (count($suppliers) == 1) {
            $query->where('supplier_code', $suppliers[0]);
        } else {
            $query->where_in('supplier_code', $suppliers);
        }

        $result = $query->get()->result_array();

        $format = [];
        foreach ($result as $row) {
            $format[$row['supplier_code']][$row['sku']] = $row;
        }
        unset($result);
        return $format;
    }
}