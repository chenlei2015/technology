<?php 
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 10:44
 */
class Default_cfg_log_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_default_cfg_log';
    }

    /**
     * 查询日志表
     */
    public function getLogList($offset,$limit,$default_key=1,$staff_code = ''){
        $offset_c = (($offset > 0 ? $offset : 1) - 1) * $limit;
        $limit_c = $limit;
        $this->db->select('*');
        $this->db->where('default_key',$default_key);
        $this->db->where('op_uid',$staff_code);
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
    public function modifyLog($params){
        $context = '将默认物流属性配置为 ';
        if($params['default_value'] == 1){
            $context .= '海运';
        }elseif ($params['default_value'] == 2){
            $context .= '铁运';
        }elseif ($params['default_value'] == 3){
            $context .= '空运';
        }elseif ($params['default_value'] == 4){
            $context .= '蓝单';
        }elseif ($params['default_value'] == 5){
            $context .= '红单';
        }
        $default_key = $params['default_key'] ?? 1;
        $data = [
            'default_key' => $default_key,
            'op_uid' => $params['op_uid'],
            'op_zh_name' => $params['op_zh_name'],
            'context' => $context,
            'created_at' => date("Y-m-d H:i:s")
        ];

        $this->db->insert($this->table,$data);
    }

}