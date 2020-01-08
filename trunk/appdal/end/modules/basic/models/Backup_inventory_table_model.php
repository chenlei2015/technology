<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 查询备份情况
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-06-01
 * @link
 */
class Backup_inventory_table_model extends MY_Model
{

    use Table_behavior;

    public function __construct()
    {
        $this->database = 'yibai_system';
        $this->table_name = 'yibai_ebay_cache_config';
        $this->primaryKey = 'id';
        $this->tableId = 0;
        parent::__construct();
    }

    //数据预计00:30拉完，从这个时间点开始查询，4：00结束
    public function get()
    {
        $start_time = strtotime(date('Y-m-d').' 02:10:00');
        $end_time = strtotime(date('Y-m-d').' 04:30:00');
        $now = time();

        if ($now < $start_time || $now > $end_time) {
            return -1;
        }

        $info = [
            'fba_inventory_status' => false,
            'mrp_log_status' => false,
        ];

        $result = $this->_db->from($this->table_name)->where_in('key', ['fba_inventory_status', 'mrp_log_status'])->get()->result_array();
        if (empty($result)) {
            //未完成
            return -1;
        } else {
            //是否已经同步
            foreach ($result as $row) {
                 $arr = json_decode($row['type'], true);
                 if (isset($arr['value']) && strtolower($arr['value']) == 'yes') {
                     $info[$row['key']] = 1;
                 } else {
                     $info[$row['key']] = 1;
                 }
            }
            return $info;
        }
    }
}