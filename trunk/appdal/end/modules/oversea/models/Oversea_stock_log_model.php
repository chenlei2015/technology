<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 10:44
 */
class Oversea_stock_log_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_oversea_stock_log';
    }

    /**
     * 查询日志
     */
    public function getStockLogList($gid='',$offset,$limit){
        $offset_c = (($offset > 0 ? $offset : 1) - 1) * $limit;
        $limit_c = $limit;
        $this->db->select('*');
        $this->db->where('gid',$gid);
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
    public function modifyStockLog($diff = [],$params = [],$rule_type = ''){
        $this->lang->load('common');
        $context = '修改';
        if(isset($diff) && !empty(array_filter($diff))){
            foreach ($diff as $key => $value){
                $key = $this->lang->myline($key);
                $context .= $key.':'.$value.' ';
            }
        }
        if($rule_type == 1){
            $context .= '规则:自定义';
        }elseif ($rule_type == 2){
            $context .= '规则:全局';
        }
        $data = [
            'gid' => $params['gid'],
            'op_uid' => $params['op_uid'],
            'op_zh_name' => $params['op_zh_name'],
            'context' => $context,
            'created_at' => date("Y-m-d H:i:s")
        ];

        $this->db->insert($this->table,$data);
    }

    /**
     * 审核日志
     */
    public function checkStockLog($detail,$rule_type,$gid,$uid,$user_name,$flag){
        $this->lang->load('common');
        if($flag == 1){
            $context = '审核通过';
        }elseif ($flag == 2){
            $context = '审核失败';
        }
        foreach ($detail as $key => $value){
            $key = $this->lang->myline($key);
            $context .= $key.':'.$value.' ';
        }
        if($rule_type == 1){
            $context .= '规则:自定义';
        }elseif ($rule_type == 2){
            $context .= '规则:全局';
        }
        $data = [
            'gid' => $gid,
            'op_uid' => $uid,
            'op_zh_name' => $user_name,
            'context' => $context,
            'created_at' => date("Y-m-d H:i:s"),
        ];
        $this->db->insert($this->table,$data);
    }

    public function getStockLogData($detail,$rule_type,$gid,$uid,$user_name,$flag){
        $this->lang->load('common');
        if($flag == 2){
            $context = '审核通过';
        }elseif ($flag == 3){
            $context = '审核失败';
        }
        foreach ($detail as $key => $value){
            $key_str = 'as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_land,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_land,pt_air,pt_red,pt_blue,bs,sc,sp,sz';
            $keys = explode(',',$key_str);
            if(!in_array($key,$keys)) continue;
            $key = $this->lang->myline($key);
            $context .= $key.':'.$value.' ';
        }
        if($rule_type == 1){
            $context .= '规则:自定义';
        }elseif ($rule_type == 2){
            $context .= '规则:全局';
        }
        $data = [
            'gid' => $gid,
            'op_uid' => $uid,
            'op_zh_name' => $user_name,
            'context' => $context,
            'created_at' => date("Y-m-d H:i:s"),
        ];
        return $data;
    }

    public function batchInsertLogData($logData){
         $this->db->insert_batch($this->table,$logData);
    }

}
