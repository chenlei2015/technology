<?php

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';
/**
 * 默认模板
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2019-01-03 17:05
 * @link
 * @throw
 * @version 1.0
 */
class InventoryTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        return $this->_title_map = array (
            'gid' =>
                array (
                    'col' => 'gid',
                ),
            '创建时间' =>
                array (
                    'col' => 'created_at',
                ),
            'SKU' =>
                array (
                    'col' => 'sku',
                ),
            '产品名称' =>
                array (
                    'col' => 'sku_name',
                ),
            'SKU状态' =>
                array (
                    'col' => 'sku_state',
                ),
            '28天内出单天数' =>
                array (
                    'col' => 'out_order_day',
                ),
            '欠货量' =>
                array (
                    'col' => 'owe_qty',
                ),
            'PR数量' =>
                array (
                    'col' => 'pr_qty',
                ),
            '采购在途数量' =>
                array (
                    'col' => 'purchase_way_qty',
                ),
            '可用库存' =>
                array (
                    'col' => 'available_stock',
                ),
            '采购单价' =>
                array (
                    'col' => 'purchase_price',
                ),
            '安全库存' =>
                array (
                    'col' => 'safe_stock_pcs',
                ),
            '订购点' =>
                array (
                    'col' => 'order_point',
                ),
        );

    }
}