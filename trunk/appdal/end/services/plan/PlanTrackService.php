<?php 

/**
 * 备货跟踪服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class PlanTrackService
{
    public static $s_system_log_name = 'PLAN-TRACK';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Plan_purchase_track_list_model', 'm_purchase_track', false, 'plan');
        $this->_ci->load->helper('plan_helper');
        return $this;
    }
    
    /**
     * 添加一条备注, 成功为true，否则抛异常
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        
        $record = ($pk_row = $this->_ci->load->m_purchase_track->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->m_purchase_track);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        
        $db = $this->_ci->m_purchase_track->getDatabase();
        
        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('跟踪列表更新备注失败'), 500);
        }
        if (!$this->add_track_remark($params))
        {
            throw new \RuntimeException(sprintf('跟踪列表插入备注失败'), 500);
        }
        $db->trans_complete();
        
        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('跟踪列表添加备注事务提交完成，但检测状态为false'), 500);
        }
        
        return true;
    }
    
    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_track_remark($params)
    {
        $this->_ci->load->model('Plan_purchase_track_remark_model', 'm_purchase_track_remark', false, 'plan');
        append_login_info($params);
        $insert_params = $this->_ci->m_purchase_track_remark->fetch_table_cols($params);
        return $this->_ci->m_purchase_track_remark->add($insert_params);
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->m_purchase_track->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }
    
    public function get_track_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Plan_purchase_track_remark_model', 'purchase_track_remark', false, 'plan');
        return $this->_ci->purchase_track_remark->get($gid, $offset, $limit);
    }

    /**
     * (生成采购单)采购回传计划系统数据
     * @param $data_list
     */
    public function track_check_update($data_list){
        if (empty($data_list)){
            throw new \InvalidArgumentException(sprintf('参数为空'), 301);
        }
        $this->_ci->load->model('Plan_purchase_list_model', 'm_purchase_list', false, 'plan');
        $db = $this->_ci->m_purchase_track->getDatabase();//都是common同一个db
        $db->trans_begin();

        $res = true;
        $msg = "";
        foreach ($data_list as $i=>$item){
            if (!empty($item['expect_arrived_date'])){
                $item['expect_arrived_date'] = date('Y-m-d',strtotime($item['expect_arrived_date']));
            }

            $where = ['pur_sn'=>$item['pur_sn'],'po_sn'=>$item['po_sn'],'po_state'=>$item['po_state'],'po_qty'=>$item['po_qty'],'expect_arrived_date'=>$item['expect_arrived_date']];
            $info = $this->_ci->m_purchase_track->getDetail($where);
            //不存在表示未变更，则修改,为兼容采购系统传过来重复提交的数据
            if (empty($info)){
                $res =  $this->_ci->m_purchase_track->update_item($item);
                if ($res){
                    //不存在表示未变更，则修改
                    $where = ['pur_sn'=>$item['pur_sn'],'state'=>PUR_STATE_FINISHED];
                    $info = $this->_ci->m_purchase_list->getDetail($where);
                    if (empty($info)){
                        //修改备货计划为完结
                        $res =  $this->_ci->m_purchase_list->update_finish($item['pur_sn']);
                        if (empty($res)){
                            $msg = "修改备货计划为完结：失败:".json_encode($item['pur_sn']);
                            break;
                        }
                    }
                }else{
                    $msg = "修改备货跟踪变化：失败:".json_encode($item);
                    break;
                }
            }

        }

        if (!$res || $db->trans_status() === FALSE){
            $db->trans_rollback();
            throw new \RuntimeException(sprintf('更新失败:'.$msg), 500);
        }elseif (!$res || $db->trans_status() === FALSE){
            $db->trans_rollback();
            throw new \RuntimeException(sprintf('更新失败:'.$msg), 500);
        }else{
            $db->trans_commit();
            return true;
        }
    }

    public function track_state_update($data_list){
        if (empty($data_list)){
            throw new \InvalidArgumentException(sprintf('参数为空'), 301);
        }

        $msg =  $this->_ci->m_purchase_track->update_po_state($data_list);
        if (!empty($msg)){
            throw new \RuntimeException($msg, 500);
        }else{
            return true;
        }
    }
}
