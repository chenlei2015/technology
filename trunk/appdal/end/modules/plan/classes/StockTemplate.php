<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/15
 * Time: 15:44
 */

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';

class StockTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('stock_list');

        return $this->_title_map = array (
            '备货单号' =>
                array (
                    'col' => 'pur_sn',
                    'width' => 18,
                ),
            '需求汇总单号' =>
                array (
                    'col' => 'sum_sn',
                    'width' => 18,
                ),
            '需求业务线' =>
                array (
                    'col' => 'bussiness_line',
                ),
            '产品状态' =>
                array (
                    'col' => 'product_status',
                ),
            'SKU' =>
                array (
                    'col' => 'sku',
                ),
            '是否退税' => [
                'col' => 'is_refund_tax',
                'width' => 18,
            ],
            '采购仓库' => [
                'col' => 'purchase_warehouse_id',
                'width' => 18,
            ],
            '产品名称' =>
                array (
                    'col' => 'sku_name',
                ),
            '最早缺货时间' =>
                array (
                    'col' => 'earliest_exhaust_date',
                ),
            '总需求数量' =>
                array (
                    'col' => 'total_required_qty',
                ),
            'PR数量' =>
                array (
                    'col' => 'pr_quantity',
                    'width' => 18,
                ),
            '采购在途' =>
                array (
                    'col' => 'on_way_qty',
                ),
            '可用库存' =>
                array (
                    'col' => 'avail_qty',
                ),
            '富余库存' =>
                array (
                    'col' => 'surplus_inventory',
                ),
            '需采购数量' =>
                array (
                    'col' => 'actual_purchase_qty',
                ),
            '创建时间' =>
                array (
                    'col' => 'created_at',
                ),
            '备货状态' =>
                array (
                    'col' => 'state',
                ),
            '推送状态' =>
                array (
                    'col' => 'is_pushed',
                ),
            '备注' =>
                array (
                    'col' => 'remark',
                ),
        );

    }
}