<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/15
 * Time: 15:44
 */

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';

class TrackTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('track_list');

        return $this->_title_map = array (
            '备货单号' =>
                array (
                    'col' => 'pur_sn',
                    'width' => 18,
                ),
            '需求业务线' =>
                array (
                    'col' => 'bussiness_line',
                ),
            'SKU' =>
                array (
                    'col' => 'sku',
                ),
            '是否退税' =>
                array(
                    'col' => 'is_refund_tax',
                    'width' => 18,
                ),
            '采购仓库' =>
                array(
                    'col' => 'purchase_warehouse_id',
                    'width' => 18,
                ),
            '产品名称' =>
                array (
                    'col' => 'sku_name',
                ),
            '产品状态' =>
                array (
                    'col' => 'product_status',
                ),
            '最早生成时间' =>
                array (
                    'col' => 'earliest_generate_time',
                ),
            '最早缺货时间' =>
                array (
                    'col' => 'earliest_exhaust_date',
                ),
            '需采购数量' =>
                array (
                    'col' => 'actual_purchase_qty',
                ),
            'PO单号' =>
                array (
                    'col' => 'po_sn',
                    'width' => 18,
                ),
            '采购状态' =>
                array (
                    'col' => 'po_state',
                ),
            'PO数量' =>
                array (
                    'col' => 'po_qty',
                ),
            '预计到货时间' =>
                array (
                    'col' => 'expect_arrived_date',
                ),
            '备注' =>
                array (
                    'col' => 'remark',
                ),
        );

    }
}