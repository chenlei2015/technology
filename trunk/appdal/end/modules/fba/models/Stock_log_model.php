<?php 
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 10:44
 */
class Stock_log_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_fba_stock_log';
    }

    /**
     * 查询日志
     */
    public function getStockLogList($id = '', $offset, $limit)
    {
        $offset_c = (($offset > 0 ? $offset : 1) - 1) * $limit;
        $limit_c = $limit;
        $this->db->select('*');
        $this->db->where('sku_id', $id);
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
     * 修改日志
     */
    public function modifyStockLog($diff = [])
    {
        $this->lang->load('common');

        $active_user = get_active_user();
        $lang_info   = $this->lang->myline('fba_stock_relationship_cfg');
        $lang_info   = array_column($lang_info, 'label', 'field');
        $other_keys  = ['id', 'updated_uid', 'updated_zh_name', 'state'];
        if(isset($diff) && !empty(array_filter($diff))){
            foreach ($diff as $key => $item) {
                $context = '修改';
                foreach ($item as $k => $val) {
                    if (in_array($k, $other_keys)) {
                        continue;
                    } elseif ($k == 'is_contraband') {
                        $val     = CONTRABAND_STATE[$val]['name']??'';
                        $kk      = $lang_info[$k]??'';
                        $context .= $kk . ':' . $val . ' ';
                    } else {
                        $kk      = $lang_info[$k]??'';
                        $context .= $kk . ':' . $val . ' ';
                    }

                }
                $data[] = [
                    'sku_id'     => $item['id'],
                    'op_uid'     => $active_user->staff_code,
                    'op_zh_name' => $active_user->user_name,
                    'context'    => $context,
                    'created_at' => date("Y-m-d H:i:s")
                ];
            }
        }
        if (!empty($data)) {
            $this->db->insert_batch($this->table, $data);
        }
//        pr($data);exit;
//        if($rule_type == 1){
//            $context .= '规则:自定义';
//        }elseif ($rule_type == 2){
//            $context .= '规则:全局';
//        }
//        $data = [
//            'id' => $params['id'],
//            'op_uid' => $params['op_uid'],
//            'op_zh_name' => $params['op_zh_name'],
//            'context' => $context,
//            'created_at' => date("Y-m-d H:i:s")
//        ];

    }

    /**
     * 审核日志
     */
    public function checkStockLog($detail, $flag)
    {
        $this->lang->load('common');
        $lang_info = $this->lang->myline('fba_stock_relationship_cfg');
        $lang_info = array_column($lang_info, 'label', 'field');
        if($flag == 1){
            $context = '审核通过,';
        }elseif ($flag == 2){
            $context = '审核失败,';
        }
        $id = $detail['id'];
        unset($detail['id']);

        foreach ($detail as $key => $value){
            if ($key == 'is_contraband') {
                $value = CONTRABAND_STATE[$value]['name']??'';
            }
            $context .= sprintf('%s:%s ', $lang_info[$key], $value);
        }
        $data    = [
            'sku_id'     => $id,
            'context'    => $context,
            'created_at' => date("Y-m-d H:i:s"),
        ];
        $key_map = [
            'userNumber' => 'op_uid',
            'userName'   => 'op_zh_name',
        ];
        append_login_info($data, $key_map);
        $this->db->insert($this->table,$data);
    }

    public function madd($params)
    {
        return $this->db->insert_batch($this->table, $params);
    }

}