<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 备份表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-06-01
 * @link
 */
class Backup_remote_table_model extends MY_Model
{

    use Table_behavior;

    public function __construct()
    {
        $this->database = 'yibai_order';
        $this->table_name = 'yibai_backup_log';
        $this->primaryKey = 'id';
        $this->tableId = 0;
        parent::__construct();
    }

    public function get_backup_names()
    {
        return [
                'yibai_order' => [
                        'yibai_clear_warehouse_sku',
                        'yibai_order_amazon',
                        'yibai_order_amazon_detail',
                        'yibai_order_stop_clearwarehouse_sku',
                        'yibai_sku_outofstock_statisitics'
                ],
                'yibai_product' => [
                        'yibai_amazon_fba_inventory_month_end',
                        'yibai_amazon_fba_daily_inventory_mrp',
                        'yibai_amazon_listing_alls',
                        'yibai_amazon_sku_map',
                        'yibai_product',
                        'yibai_product_combine',
                        'fba_inventory_status',
                        'mrp_log_status'
                ],
                'yibai_system' => [
                        'yibai_amazon_account',
                        'yibai_amazon_fba_replenishment_control',
                        'yibai_amazon_group'
                ]
        ];
    }

    public function get($date = '')
    {
        $date = $date == '' ? date('Y-m-d') : $date;
        return $this->_db->from($this->table_name)->where('date', $date)->get()->result_array();
    }

    //同步新增的表
    public function sync()
    {
        $cache_key = 'backup_table_flag';
        $no_backups = [];

        $ci = CI::$APP;
        $ci->load->library('Rediss');

        if ($ci->rediss->getData($cache_key)) {
            return true;
        }

        //取备份表
        $ci->load->model('Backup_local_table_model', 'm_backup_local', false, 'basic');
        $local = key_by($ci->m_backup_local->get(), ['database', 'table', 'date']);

        $backup_table_cfg = $this->get_backup_names();
        foreach ($backup_table_cfg as $database => $tables) {
            foreach ($tables as $table) {
                $key = $database.$table.date('Y-m-d');
                if (!isset($local[$key])) {
                    //未备份完
                    $remote_key = $database.$table.('_'.date('d')).date('Y-m-d');
                    $no_backups[$remote_key] = $table;
                }
            }
        }

        if (empty($no_backups)) {
            //备份完成
            $ci->rediss->setData($cache_key, 3600);
            return true;
        }

        $is_exception = false;

        $batch_insert = [];

        $remote_inventory_info = $remote = [];

        try {
            $remote = key_by($this->get(), ['database', 'table', 'date']);
        } catch (\Throwable $e) {
            log_message('ERROR', '获取运维备份表连接超时下次重试');
            $is_exception = true;
        }

        try {
            $ci->load->model('Backup_inventory_table_model', 'm_inventory', false, 'basic');
            $remote_inventory_info = $ci->m_inventory->get();
        } catch (\Throwable $e) {
            log_message('ERROR', '获取yibai_system.yibai_ebay_cache_config连接超时下次重试');
            $is_exception = true;
        }

        foreach ($no_backups as $key => $table)
        {
            $table_row = $remote[$key] ?? [];
            if (empty($table_row) && isset($remote_inventory_info[$table])) {
                $table_row = [
                    'database' => 'yibai_product',
                    'table' => $table,
                    'date' => date('Y-m-d'),
                    'created' => date('Y-m-d H:i:s'),
                ];
            }

            if (!empty($table_row)) {
                //插入
                $batch_insert[] = [
                    'database' => $table_row['database'],
                    'table' => $table,
                    'date' => $table_row['date'],
                    'created' => $table_row['created'],
                ];
                unset($no_backups[$key]);
            }
        }

        if (empty($batch_insert))
        {
            $backup_table_cfg = $remote = null;
            unset($backup_table_cfg, $remote);
            return 0;
        }

        if (empty($no_backups) && !$is_exception)
        {
            $ci->rediss->setData($cache_key, 3600);
        }

        $db = $ci->m_backup_local->getDatabase();
        return $db->insert_batch($ci->m_backup_local->getTable(), $batch_insert);

    }


}