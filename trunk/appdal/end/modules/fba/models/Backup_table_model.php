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
class Backup_table_model extends MY_Model
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
                        'yibai_amazon_listing_alls',
                        'yibai_amazon_sku_map',
                        'yibai_product',
                        'yibai_product_combine'
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



    public function sync()
    {
        foreach ($this->get() as $table)
        {

        }
    }


}