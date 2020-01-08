<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 备货列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-07
 * @link 
 */
class Plan_purchase_list_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_purchase_list';
        $this->primaryKey = 'gid';
        $this->tableId = 64;
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
     * 获取详情
     * @param string $station_code
     * @return mixed
     */
    public function getDetail($where=[])
    {
        if (empty($where)){
            return [];
        }
        $this->_db->select('*');
        $this->_db->where($where);
        $this->_db->from($this->table_name);
        return $this->_db->get()->row_array();
    }

    /**
     * (生成采购单)采购回传计划系统数据,修改备货计划为完结
     * @param $data_list
     * @return bool
     */
    public function update_finish($pur_sn){
        if (empty($pur_sn)){
            return false;
        }
        $where = ['pur_sn'=>$pur_sn];
        $update = ['state'=>PUR_STATE_FINISHED];

        $this->_db->where($where)->update($this->table_name,$update);
        $res = $this->_db->affected_rows();
        return !empty($res) && $res>=0 ?true:false;
    }

    /**
     * 旧
     * 采购回传计划系统数据:（采购系统）需求单过期=》计划系统备货列表过期
     * @param $data_list
     * @return bool
     */
    public function batch_update_state_old($data_list){
        if (empty($data_list)){
            return false;
        }

        $this->_db->trans_begin();
        $db = clone $this->_db;

        $res = true;
        foreach ($data_list as $i=>$item) {
            $where = ['pur_sn' => $item['pur_sn']];
            $update = ['state' => $item['state']];

            //查找就否与原来一样，不一样才更新
            $where2 = array_merge($where,$update);
            $db->select("gid")->from($this->table_name)->where($where2);
            $total = $db->count_all_results();

            if (empty($total)) {
                $this->_db->where($where)->update($this->table_name, $update);
                $res = $this->_db->affected_rows();
                if (empty($res)) {
                    $res = false;
                    break;
                }
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
     * 采购回传计划系统数据:（采购系统）需求单过期=》计划系统备货列表过期
     * @param $data_list
     * @return bool
     */
    public function batch_update_state($data_list){
        if (empty($data_list)){
            return false;
        }

        $this->_db->trans_start();
        $collspac_batch_params = [];
        foreach($data_list as $item)
        {
            $collspac_batch_params[] = ['pur_sn'=>$item['pur_sn'],'state'=>$item['state']];
        }

        $this->_db->update_batch($this->table_name, $collspac_batch_params, 'pur_sn');
        $this->_db->trans_complete();
        if ($this->_db->trans_status() === FALSE){
            return false;
        }else{
            return true;
        }
    }

    /**
     * 获取需要修改的手动上传文件中的pur—sn
     * 
     * @param unknown $pr_sns
     * @return unknown
     */
    public function get_manual_rows($pr_sns)
    {
        $today_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $today_end = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        $result = $this->_db->select('gid, pur_sn, push_stock_quantity')
        ->from($this->table_name)
        ->where('is_pushed', PUR_DATA_UNPUSH)
        ->where('created_at >= ', $today_start)
        ->where('created_at <= ', $today_end)
        ->where('state', PUR_STATE_ING)
        ->where_in('pur_sn', $pr_sns)
        ->get()
        ->result_array();
        return $result;
    }

    /**
     * 获取可以push推送的条码, 因为要判断是否有已推送的，所以取全量然后做判断
     * 
     * @param unknown $pur_sns
     * @return unknown
     */
    public function get_push_rows($pur_sns)
    {
        $today_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $today_end = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        
        $result = $this->_db->select('pur_sn')
        ->from($this->table_name)
        ->where('is_pushed', PUR_DATA_UNPUSH)
        ->where('created_at >= ', $today_start)
        ->where('created_at <= ', $today_end)
        ->where('push_stock_quantity >', 0)
        ->where_in('pur_sn', $pr_sns)
        ->get()
        ->result_array();
        
        return $result;
    }
    
    /**
     * 简单根据单号查询
     * 
     * @param unknown $pur_sns
     * @param string $select
     * @return unknown
     */
    public function get_by_pur_sns($pur_sns, $select = '*')
    {
        $result = $this->_db->select($select)
        ->from($this->table_name)
        ->where_in('pur_sn', $pur_sns)
        ->get()
        ->result_array();
        return $result;
    }

    public function get_today_used_pur_sns($sn_prefix, $cut_length)
    {
        $today_start = mktime(0, 0, 0, intval(date('m')), intval(date('d')), intval(date('y')));
        $today_end   = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('y')));

        $max_sum_sn = $this->_db->select('pur_sn')
            ->from($this->table_name)
            ->where('created_at >= ', $today_start)
            ->where('created_at <= ', $today_end)
            ->order_by('pur_sn', 'desc')
            ->limit(1)
            ->get()
            ->result_array();

        if (empty($max_sum_sn)) {
            return [];
        }
        $start    = strlen($sn_prefix);
        $seq_char = substr($max_sum_sn[0]['pur_sn'], $start, $cut_length);
        $result   = $this->_db->select('pur_sn')->from($this->table_name)->like('pur_sn', $sn_prefix . $seq_char . '%')->get()->result_array();
        if (empty($result)) {
            return [];
        }
        $pur_sns = array_column($result, 'pur_sn');
        sort($pur_sns);

        return [$seq_char, $pur_sns];
    }
}