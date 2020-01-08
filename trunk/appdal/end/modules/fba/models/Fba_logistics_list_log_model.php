<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 10:44
 */
class Fba_logistics_list_log_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_fba_logistics_list_log';
        $this->lang->load('common');
        $this->active_user = get_active_user();
        $this->lang_info   = $this->lang->myline('fba_logistics_list');
        $this->lang_info   = array_column($this->lang_info, 'label', 'field');
    }

    /**
     * 查询日志表
     */
    public function getLogList($log_id='',$offset,$limit){
        $offset_c = (($offset > 0 ? $offset : 1) - 1) * $limit;
        $limit_c = $limit;
        $this->db->select('*');
        $this->db->where('log_id',$log_id);
        $this->db->from($this->table);
        $db = clone $this->db;
        $total = $db->count_all_results();//获取总条数
        unset($db);
        $this->db->limit($limit_c);
        $this->db->offset($offset_c);
        $this->db->order_by('created_at','DESC');
        $data['data_list'] = $this->db->get()->result_array();
        $data['data_page'] = array(
            'limit' => (float)$limit,
            'offset' => (float)$offset,
            'total' => (float)$total
        );
        return $data;
    }

    /**
     * 修改的日志
     */
    public function modifyLog($diff = [])
    {

        $context    = '';
        $other_keys = ['id', 'updated_uid', 'updated_zh_name', 'approve_state'];
        $data       = [];
        if(isset($diff) && !empty(array_filter($diff))){
            foreach ($diff as $key => $item) {
                $context = '修改';
                foreach ($item as $k => $val) {
                    if (in_array($k, $other_keys)) {
                        continue;
                    } elseif ($k == 'logistics_id') {
                        $val     = LOGISTICS_ATTR[$val]['name']??'';
                        $kk      = $this->lang_info[$k]??'';
                        $context .= $kk . ':' . $val . ' ';
                    } elseif ($k == 'listing_state') {
                        $val     = LISTING_STATE[$val]['name']??'';
                        $kk      = $this->lang_info[$k]??'';
                        $context .= $kk . ':' . $val . ' ';
                    } elseif ($k == 'purchase_warehouse_id') {
                        $val     = PURCHASE_WAREHOUSE[$val]['name']??'';
                        $kk      = $this->lang_info[$k]??'';
                        $context .= $kk . ':' . $val . ' ';
                    } elseif ($k == 'rule_type') {
                        $val     = RULE_TYPE[$val]['name']??'';
                        $kk      = $this->lang_info[$k]??'';
                        $context .= $kk . ':' . $val . ' ';
                    } else {
                        $kk      = $this->lang_info[$k]??'';
                        $context .= $kk . ':' . $val . ' ';
                    }

                }
                $data[] = [
                    'log_id'     => $item['id'],
                    'op_uid'     => $this->active_user->staff_code,
                    'op_zh_name' => $this->active_user->user_name,
                    'context'    => $context,
                    'created_at' => date("Y-m-d H:i:s")
                ];
            }
        } else {
            return FALSE;
        }
        if (!empty($data)) {
            $this->db->insert_batch($this->table, $data);
        }
    }

    /**
     * 审核的日志
     */
    public function checkLog($detail, $flag)
    {
        $context = '';
        if($flag == 1){
            $context = '审核通过,';
        }elseif ($flag == 2){
            $context = '审核失败,';
        }
        $id = $detail['id'];
        unset($detail['id']);
        /*
                foreach ($detail as $key => $value){
                    if ($key == 'logistics_id'){
                        $value = LOGISTICS_ATTR[$value]['name']??'';
                    }elseif ($key == 'listing_state'){
                        $value = LISTING_STATE[$value]['name']??'';
                    }elseif ($key == 'purchase_warehouse_id'){
                        $value = PURCHASE_WAREHOUSE[$value]['name']??'';
                    }elseif ($key == 'rule_type'){
                        $value = RULE_TYPE[$value]['name']??'';
                    }
                    $context .= sprintf('%s:%s ',$this->lang_info[$key],$value);
                }*/
        $data    = [
            'log_id'     => $id,
            'context'    => $context,
            'created_at' => date("Y-m-d H:i:s"),
        ];
        $key_map = [
            'userNumber' => 'op_uid',
            'userName'   => 'op_zh_name',
        ];
        append_login_info($data, $key_map);
        $this->db->insert($this->table, $data);
    }

    public function add($params)
    {
        return $this->_db->insert($this->table_name, $params);
    }

    public function madd($params)
    {
        return $this->_db->insert_batch($this->table_name, $params);
    }
}