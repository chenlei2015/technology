<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 备货跟踪列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson 13459
 * @date 2019-03-07
 * @link
 */
class Plan_pr_number_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_pr_number';
        $this->primaryKey = 'gid';
        $this->tableId = 141;
        parent::__construct();
    }

    /**
     * 采购回传计划系统数据:（采购系统）需求单采购单状态变更=》（计划系统）备货跟踪状态的备货状态同步变更
     * @param $data_list
     * @return bool
     */
    public function update_state($data_list){
        if (empty($data_list)){
            return false;
        }
        $res = true;
        $this->_db->trans_start();
        $updated_at = time();
        foreach ($data_list as $i=>$item) {
            if (!empty($item['demand_number']) && !empty($item['audit_status']) && $item['audit_status'] == 2) {//审核不通过时,修改状态为失效
                $update['updated_at'] = $updated_at;
                $update['state'] = 2;
                $this->_db->where('pur_sn',$item['demand_number'])->update($this->table_name, $update);
                $res = $this->_db->affected_rows();
                if (empty($res)){
                    $res = false;
                    break;
                }
            }elseif (!empty($item['demand_number'])&&!empty($item['audit_status'])&&$item['audit_status']==1){//审核通过不做处理
                continue;
            }else{
                $res = false;
                break;
            }
        }
        $this->_db->trans_complete();
        if (!$res || $this->_db->trans_status() === FALSE){
            return false;
        }else{
            return true;
        }
    }

}