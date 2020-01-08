<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba 需求跟踪列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link
 */
class Fba_pr_track_list_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_fba_pr_track';
        $this->primaryKey = 'gid';
        $this->tableId = 90;
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
     * 回写审核了但未创建汇总的跟踪单
     *
     * @param string $date
     * @return unknown|boolean
     */
    public function rewrite_unassign_sumsn($date)
    {
        return false;
        
        $date_start = strtotime($date);
        $data_end = $date_start + 86400;
        
        $ci = CI::$APP;
        $ci->load->model('Fba_pr_summary_model', 'm_fba_summary', false, 'fba');
        $update_sql = sprintf(
            'UPDATE %s track, %s summary SET track.sum_sn = summary.sum_sn WHERE track.sku = summary.sku '.
            ' AND track.is_refund_tax = summary.is_refund_tax AND track.purchase_warehouse_id = summary.purchase_warehouse_id '.
            ' AND summary.created_date = "%s"  AND track.sum_sn = "" AND track.created_at > %d AND track.created_at < %d',
            $this->table_name,
            $ci->m_fba_summary->getTable(),
            $date,
            $date_start,
            $data_end
            );
        if ($this->_db->query($update_sql))
        {
            return $this->_db->affected_rows();
        }
        return false;
    }

    /**
     * 查询跟踪列表的创建时间
     */
    public function tracking_date()
    {
        return $this->_db->select('created_at date,is_shipment_plan status')->from($this->table_name)->group_by('created_at')->order_by('created_at desc')->get()->result_array();
    }

    public function dataCount($date)
    {
        $count = $this->_db->select('*')->from($this->table_name)
            ->where('created_at >=',$date['start'])
            ->where('created_at <=',$date['end'])
            ->where('is_shipment_plan',2)
            ->count_all_results();
        return $count;
    }

    public function getData($date)
    {
        $result = $this->_db->select('*')->from($this->table_name)
            ->where('created_at >=',$date['start'])
            ->where('created_at <=',$date['end'])
            ->where('is_shipment_plan', 2)//未生成发运计划
            ->limit($date['limit'])
            ->get()->result_array();
    return $result;
//        return $this->_db->select('*')->from($this->table_name)
//            ->where('created_at >=',$date['start'])
//            ->where('created_at <=',$date['end'])
//            ->get()->result_array();

//        echo $this->_db->last_query();exit;
    }



    public function update_shipment_status($gids)
    {
        $this->_db->where_in('gid',$gids);
        $this->_db->set('is_shipment_plan',1);
        $this->_db->update($this->table_name);
    }

}