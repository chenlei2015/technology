<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/15
 * Time: 15:44
 */

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';

class SummaryTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        return $this->_title_map = array (
            '需求业务线' =>
                array (
                    'col' => 'bussiness_line',
                    'width' => 18,
                ),
            'SKU' =>
                array (
                    'col' => 'sku',
                ),
            '产品名称' =>
                array (
                    'col' => 'sku_name',
                ),
            '总需求数量' =>
                array(
                    'col' => 'total_required_qty',
                    'width' => 18,
                ),
            '最早缺货时间' =>
                array(
                    'col' => 'earliest_exhaust_date',
                    'width' => 18,
                ),
            '实际备货数量' =>
                array (
                    'col' => 'actual_purchase_qty',
                ),
            '采购在途数量' =>
                array (
                    'col' => 'on_way_qty',
                ),
            '可用库存数量' =>
                array (
                    'col' => 'avail_qty',
                ),
            '采购备货数量' =>
                array (
                    'col' => 'purchase_order_qty',
                    'width' => 18,
                ),
            '创建时间' =>
                array (
                    'col' => 'created_at',
                ),
            '备注' =>
                array (
                    'col' => 'remark',
                ),
        );

    }
}