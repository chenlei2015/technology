<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba 需求统计列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link
 */
class Oversea_pr_summary_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_oversea_summary';
        $this->primaryKey = 'gid';
        $this->tableId = 66;
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
     * 获取指定sku的汇总记录， 创建日期已经汇总的sku
     *
     * @version 1.2.0 增加审核状态
     *
     * @param string $created_date
     * @return unknown
     */
    public function get_sku_summary_rows_by_date($skus, $created_date, $approve_state = -1)
    {
        $query = $this->_db->from($this->table_name)->where_in('sku', $skus)->where('created_date', $created_date);
        if ($approve_state != -1)
        {
            $query->where('approve_state', $approve_state);
        }
        return $query->get()->result_array();
    }

    /**
     * 获取汇总的sku，按照状态
     *
     * @param string $created_date
     * @param int $approve_state
     * @return array
     */
    public function get_skus_by_date($created_date, $op = '=', $approve_state = -1)
    {
        $query = $this->_db->from($this->table_name)->select('sku')->where('created_date', $created_date);
        if ($approve_state != -1)
        {
            switch ($op)
            {
                case '=':
                    is_array($approve_state) ? $query->where_in('approve_state', $approve_state) : $query->where('approve_state', $approve_state);
                    break;
                    //取反操作
                case '!':
                    is_array($approve_state) ? $query->where_not_in('approve_state', $approve_state) : $query->where('approve_state !=', $approve_state);
                    break;
            }
        }

        return array_unique(array_column($query->get()->result_array(), 'sku'));
    }

    /**
     * 获取今天的序列和汇总单
     *
     * @return array|array
     */
    public function get_today_used_sum_sns($sn_prefix, $cut_length)
    {
        $max_sum_sn = $this->_db->select('sum_sn')
        ->from($this->table_name)
        ->where('created_date', date('Y-m-d'))
        ->order_by('sum_sn', 'desc')
        ->limit(1)
        ->get()
        ->result_array();

        if (empty($max_sum_sn))
        {
            return [];
        }
        $start = strlen($sn_prefix);
        $seq_char = substr($max_sum_sn[0]['sum_sn'], $start, $cut_length);

        $result = $this->_db->select('sum_sn')->from($this->table_name)->like('sum_sn', $sn_prefix.$seq_char, 'after')->get()->result_array();
        if (empty($result)) {
            return [];
        }
        $pr_sns = array_column($result, 'sum_sn');
        sort($pr_sns);
        return [$seq_char, $pr_sns];
    }

    /**
     * 删除没有跟踪单的汇总单号
     *
     * @param array $track_sns
     * @return boolean
     */
    public function delete_alone_summary($track_sns = [])
    {
        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        CI::$APP->load->model('Oversea_pr_track_list_model', 'oversea_track_list_model', false, 'oversea');
        $table_track = CI::$APP->oversea_track_list_model->getTable();

        $where = !empty($track_sns) ? ' and track.pr_sn in ('.array_where_in($track_sns) . ')' : '';

        $sql = sprintf('select sum.sum_sn
            	from %s sum
            	left join  %s track on  sum.sum_sn = track.sum_sn
            	where sum.created_at > %d and sum.created_at < %d %s
                and track.created_at > %d and track.created_at < %d
            	group by sum.sum_sn
            	having count(track.sum_sn) = 0 ',
                $this->table_name,
                $table_track,
                $today_start,
                $today_end,
                $where,
                $today_start,
                $today_end
            );

        $result = $this->_db->query($sql)->result_array();
        if (empty($result)) return true;

        $this->_db->reset_query();
        $this->_db->where_in('sum_sn', $sums = array_column($result, 'sum_sn'));
        $this->_db->delete($this->table_name);
        log_message('INFO', '删除没有跟踪单的汇总单：%s, 删除数量：%d', implode(',', $sums), $this->_db->affected_rows());
        return true;
    }

    /**
     * 汇总列表审核。
     */
    public function get_can_approve($gids)
    {
        if (empty($gids)) return [];

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $query = $this->_db->from($this->table_name)
        ->select('gid, sum_sn, approve_state')
        ->where_in('gid', $gids)
        ->where('approve_state', APPROVAL_STATE_THREE)
        ->where('created_at > ', $today_start)
        ->where('created_at < ', $today_end)
        ->limit(count($gids));

        $result = $query->get()->result_array();

        return $result;
    }

    /**
     * 获取可以审批的数据的ids
     */
    public function get_can_approve_data($limit=300){
        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;
        $approve_data = $this->db->select('gid, sum_sn, approve_state')
            ->from($this->table_name)->where('approve_state',APPROVAL_STATE_THREE)
            ->limit($limit)
            ->where('created_at > ', $today_start)
            ->where('created_at < ', $today_end)
            ->get()->result_array();
        return empty($approve_data)?[]:array_column($approve_data,'gid');
    }
}
