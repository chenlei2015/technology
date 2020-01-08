<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/9
 * Time: 14:21
 */
class Oversea_logistics_list_log_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_oversea_logistics_list_log';
    }

    private function getLogisticsAttrName($logistics_id){
        $logistics_attr = LOGISTICS_ATTR;
        return (isset($logistics_attr[$logistics_id]))?$logistics_attr[$logistics_id]['name']:'未知标识';
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
    public function modifyLog($diff,$params){
        $context = '';
        if(isset($diff) && !empty(array_filter($diff))){
            foreach ($diff as $key => $value){
                if($key == 'logistics_id'){
                    $context .= '将物流属性修改为: '.LOGISTICS_ATTR[$value]['name']??'属性异常'.' ';
                }elseif ($key == 'sku_state'){
                    $context .= '将SKU状态修改为: ' . SKU_STATE[$value]['name']??'状态异常' . ' ';
                }
            }
        }else{
            return FALSE;
        }

        $data = [
            'log_id' => $params['id'],
            'op_uid' => $params['updated_uid'],
            'op_zh_name' => $params['updated_zh_name'],
            'context' => $context,
            'created_at' => $params['updated_at']
        ];

        $this->db->insert($this->table,$data);
    }

    /**
     * 审核的日志
     */
    public function checkLog($logistics_id='',$id='',$uid='',$user_name='',$flag=''){
        if($flag == 1){
            $context = '审核通过,物流属性:';
        }elseif ($flag == 2){
            $context = '审核失败,物流属性:';
        }
        $context .= $this->getLogisticsAttrName($logistics_id);
        $data = [
            'log_id' => $id,
            'op_uid' => $uid,
            'op_zh_name' => $user_name,
            'context' => $context,
            'created_at' => date("Y-m-d H:i:s"),
        ];
        $this->db->insert($this->table,$data);
    }


    /**
     * 批量插入审核日志
     */
    public function batchInsertLogData($approve_data,$uid='',$user_name='',$approve_result){
        if($approve_result == 2){
            $context = '审核通过,物流属性:';
        }elseif ($approve_result == 3){
            $context = '审核失败,物流属性:';
        }
        $data = [];
        foreach ($approve_data as $key => $detail) {
            $context .= $this->getLogisticsAttrName($detail['logistics_id']);
            $data[$key] = [
                'log_id' => $detail['id'],
                'op_uid' => $uid,
                'op_zh_name' => $user_name,
                'context' => $context,
                'created_at' => date("Y-m-d H:i:s"),
            ];
        }
        //写入日志
        $this->db->insert_batch($this->table,$data);
    }

}
