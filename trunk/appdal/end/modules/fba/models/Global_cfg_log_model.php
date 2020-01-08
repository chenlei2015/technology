<?php 
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/2
 * Time: 11:53
 */
class Global_cfg_log_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_fba_global_cfg_log';
    }

    /**
     * 查询全局规则日志
     */
    public function getGlobalLogList($station_code='',$offset,$limit){
        $offset_c = (($offset > 0 ? $offset : 1) - 1) * $limit;
        $limit_c = $limit;
        $this->db->select('*');
        $this->db->where('station_code',$station_code);
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
     * 新增全局规则日志
     */
    public function addGlobalRuleLog($diff = [],$params = []){
        $this->lang->load('common');
        $context = '修改';
        foreach ($diff as $key => $value){
            $key = $this->lang->myline($key);
            $context .= $key.':'.$value.' ';
        }
        $context .= '规则:全局';
        $data = [
            'station_code' => $params['station_code'],
            'op_uid' => $params['op_uid'],
            'op_zh_name' => $params['op_zh_name'],
            'context' => $context,
            'created_at' => date("Y-m-d H:i:s")
        ];

        $this->db->insert($this->table,$data);
    }
}