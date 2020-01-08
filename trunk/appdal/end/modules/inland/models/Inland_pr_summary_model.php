<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * Inland 需求统计列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link
 */
class Inland_pr_summary_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_summary';
        $this->primaryKey = 'gid';
        $this->tableId = 42;
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
     *
     * @param unknown $created_date
     * @return unknown
     */
    public function get_sku_summary_rows_by_date($skus, $created_date)
    {
        return $this->_db->from($this->table_name)
        ->where_in('sku', $skus)
        ->where('created_date', $created_date)
        ->get()
        ->result_array();
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
}