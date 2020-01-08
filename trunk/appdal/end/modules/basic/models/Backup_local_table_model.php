<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 备份本地表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-06-01
 * @link
 */
class Backup_local_table_model extends MY_Model
{

    use Table_behavior;

    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_backup_log';
        $this->primaryKey = 'id';
        $this->tableId = 0;
        parent::__construct();
    }

    public function get($date = '')
    {
        $date = $date == '' ? date('Y-m-d') : $date;
        return $this->_db->from($this->table_name)->where('date', $date)->get()->result_array();
    }

    //同步
    public function insert($params)
    {
        $date = $params['date'];
        $step = [
            1 => ['yibai_product', 'yibai_amazon_fba_inventory_month_end'],
            2 => ['yibai_product', 'yibai_amazon_fba_daily_inventory_mrp'],
        ];
        $replace = [
            'database' => $step[$params['step']][0],
            'table' => $step[$params['step']][1],
            'date' => $date,
        ];
        $result = $this->_db->replace($this->getTable(), $replace);
        return $result;
    }

    public function get_inventory_backup_info()
    {
        //SELECT * FROM yibai_system.`yibai_ebay_cache_config` where `key` = 'fba_inventory_status';
        //SELECT * FROM yibai_system.`yibai_ebay_cache_config` where `key` = 'mrp_log_status';

        $result = $this->_db->from($this->table_name)
        ->where('database', 'yibai_product')
        ->where('date', date('Y-m-d'))
        ->where_in('table', ['fba_inventory_status', 'mrp_log_status'])
        ->get()
        ->result_array();

        $re = [];
        foreach (array_column($result, 'table') as $table) {
            $re[$table] = 1;
        }
        return $re;
    }
}