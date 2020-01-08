<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 备货跟踪列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-07
 * @link 
 */
class Plan_purchase_track_list_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_purchase_track';
        $this->primaryKey = 'gid';
        $this->tableId = 58;
        parent::__construct();
    }
    
    /**
     * 
     * @return unknown
     */
    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }

    /**
     * 根据条件查详情
     */
    public function getDetail($condition=[]){
        $this->_db->select('*');
        $this->_db->where($condition);
        $this->_db->from($this->table_name);
        return $this->_db->get()->row_array();
    }

    /**
     * (生成采购单)采购回传计划系统数据,备货跟踪变化
     * @param $data_list
     * @return bool
     */
    public function update_item($item){
        if (empty($item['pur_sn'])){
            return false;
        }
        if (!empty($item['expect_arrived_date'])){
            $item['expect_arrived_date'] = date('Y-m-d',strtotime($item['expect_arrived_date']));
        }

        $where = ['pur_sn'=>$item['pur_sn']];
        $update = ['po_sn'=>$item['po_sn'],'po_state'=>$item['po_state'],'po_qty'=>$item['po_qty'],'expect_arrived_date'=>$item['expect_arrived_date']];
        $this->_db->where($where)->update($this->table_name,$update);
        $res = $this->_db->affected_rows();

        return !empty($res) && $res>=0 ? true:false;
    }

    /**
     * 采购回传计划系统数据:（采购系统）需求单采购单状态变更=》（计划系统）备货跟踪状态的备货状态同步变更
     * @param $data_list
     * @return bool
     */
    public function update_po_state_old($data_list){
        if (empty($data_list)){
            return false;
        }

        $this->_db->trans_begin();
        $db = clone $this->_db;

        $res = true;
        $updated_at = time();
        $msg = "";
        foreach ($data_list as $i=>$item) {
            $where = ['pur_sn' => $item['pur_sn']];

            if (!empty($item['expect_arrived_date'])){
                $item['expect_arrived_date'] = date('Y-m-d',strtotime($item['expect_arrived_date']));
            }

            $update = ['po_state' => $item['po_state'],'expect_arrived_date'=> $item['expect_arrived_date'],'po_qty'=> $item['po_qty']];//po_qty实际为采购系统单个需求中的数量
            //查找就否与原来一样，不一样才更新
            $where2 = array_merge($where,$update);
            $db->select("gid")->from($this->table_name)->where($where2);
            $total = $db->count_all_results();

            if (empty($total)){
                $update['updated_at'] = $updated_at;
                $this->_db->where($where)->update($this->table_name, $update);
                $res = $this->_db->affected_rows();
                if (empty($res)){
                    $res = false;
                    break;
                }
                if ($update['po_state']==7){
                    $this->_db->where($where)->update("yibai_pr_number", ['state'=>2,'updated_at'=>$updated_at]);
                }
            }else{

            }

        }

        if (!$res || $this->_db->trans_status() === FALSE){
            $this->_db->trans_rollback();
            return false;
        }else{
            $this->_db->trans_commit();
            return true;
        }
    }

    /**
     * 采购回传计划系统数据:（采购系统）需求单采购单状态变更=》（计划系统）备货跟踪状态的备货状态同步变更
     * @param $data_list
     * @return bool
     */
    public function update_po_state($data_list){
        if (empty($data_list)){
            return false;
        }

        $this->_db->trans_begin();
        $db = clone $this->_db;

        $res = true;
        $updated_at = time();
        $msg = "";
        foreach ($data_list as $i=>$item) {
            $where = ['pur_sn' => $item['pur_sn']];

            if (!empty($item['expect_arrived_date'])){
                $item['expect_arrived_date'] = date('Y-m-d',strtotime($item['expect_arrived_date']));
            }

            $update = ['po_state' => $item['po_state'],'expect_arrived_date'=> $item['expect_arrived_date'],'po_qty'=> $item['po_qty']];//po_qty实际为采购系统单个需求中的数量

            $update['updated_at'] = $updated_at;
            $res = $this->_db->where($where)->update($this->table_name, $update);
            if (!$res){
                $msg = '修改备货计划：失败，update：'.json_encode($update).',where:'.json_encode($where);
                break;
            }
            if ($update['po_state']==7){
                $res = $this->_db->where($where)->update("yibai_pr_number", ['state'=>2,'updated_at'=>$updated_at]);
                if (!$res){
                    $msg = '修改表pr_number：失败，update：'.json_encode(['state'=>2,'updated_at'=>$updated_at]).',where:'.json_encode($where);
                    break;
                }
            }

        }

        if (!$res){
            $this->_db->trans_rollback();
        }elseif ($res && $this->_db->trans_status() === FALSE){
            $this->_db->trans_rollback();
            $msg = '提交事务执行失败';
        }else{
            $this->_db->trans_commit();
        }

        return $msg;
    }

    /**
     * 一个po_sn可能对应多个pur_sn
     * 
     * @param unknown $pur_sns
     */
    public function get_pursn_map_posn($pur_sns)
    {
        $result = $this->_db->select('pur_sn, po_sn')
        ->from($this->table_name)
        ->where_in('pur_sn', $pur_sns)
        ->where('po_sn != ', '')
        ->get()
        ->result_array();
        return array_column($result, 'po_sn', 'pur_sn');
    }

    /**
     * 从备货跟踪表里获取 采购单号,发运类型
     * 采购单号：在需求跟踪列表中根据需求单对应找到需求单的备货单号，
     * 在备货跟踪单根据备货单找到对应的po单。
     *
     */
    public function getPurSn($pur_sn_arr)
    {
        $result = $this->_db->select('pur_sn, po_sn, shipment_type')
            ->from($this->table_name)
            ->where_in('pur_sn', $pur_sn_arr)
            ->get()
            ->result_array();
        return array_column($result, NULL, 'pur_sn');
    }

    public function getPurTrackInfo($pur_sn_arr)
    {
        $result = $this->_db->select('pur_sn, po_sn, earliest_exhaust_date')
            ->from($this->table_name)
            ->where_in('pur_sn', $pur_sn_arr)
            ->get()
            ->result_array();
        return array_column($result, NULL, 'pur_sn');
    }



}