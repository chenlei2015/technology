<?php

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';

/**
 * 默认模板
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw
 * @version 1.0
 */
class ListTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('inland_pr_list');

        return $this->_title_map = array (
            'gid' =>
            array (
                    'col' => 'gid',
            ),
            '需求单号' =>
            array (
                    'col' => 'pr_sn',
            ),
            'SKU' =>
            array (
                    'col' => 'sku',
            ),
            '产品名称' =>
            array (
                    'col' => 'sku_name',
            ),
            '是否退税' =>
            array (
                    'col' => 'is_refund_tax',
            ),
            '采购仓库' =>
            array (
                    'col' => 'purchase_warehouse_id',
            ),
            'SKU状态' =>
            array (
                    'col' => 'sku_state',
            ),
            '备货方式' =>
            array (
                    'col' => 'stock_up_type',
            ),
            '欠货量' =>
            array (
                    'col' => 'debt_qty',
            ),
            'PR数量' =>
            array (
                    'col' => 'pr_qty',
            ),
            '采购在途数量' =>
            array (
                    'col' => 'ship_qty',
            ),
            '可用库存(pcs)' =>
            array (
                    'col' => 'available_qty',
            ),
            '采购单价' =>
            array (
                    'col' => 'purchase_price',
            ),
            '28天内出单天数' =>
            array (
                    'col' => 'accumulated_order_days',
            ),
            '28天总销量' =>
            array (
                    'col' => 'accumulated_sale_qty',
            ),
/*            '排序' =>
            array (
                    'col' => 'sale_qty_order',
            ),*/
            '加权日均销量' =>
            array (
                    'col' => 'weight_sale_pcs',
            ),
            '销量标准偏差' =>
            array (
                    'col' => 'sale_sd_pcs',
            ),
            '交付标准偏差' =>
            array (
                    'col' => 'deliver_sd_day',
            ),
            '权均供货周期' =>
            array (
                    'col' => 'supply_wa_day',
            ),
            '缓冲库存' =>
            array (
                    'col' => 'buffer_pcs',
            ),
            '备货处理周期' =>
            array (
                    'col' => 'purchase_cycle_day',
            ),
            '发运时效' =>
            array (
                    'col' => 'ship_timeliness_day',
            ),
            '备货提前期' =>
            array (
                    'col' => 'pre_day',
            ),
            '一次备货天数SC' =>
            array (
                    'col' => 'sc_day',
            ),
            '服务对应"Z"值' =>
            array (
                    'col' => 'z',
            ),
            '安全库存' =>
            array (
                    'col' => 'safe_stock_pcs',
            ),
            '订购点' =>
            array (
                    'col' => 'point_pcs',
            ),
            '可用库存支撑天数' =>
            array (
                    'col' => 'supply_day',
            ),
            '预计断货时间' =>
            array (
                    'col' => 'expect_exhaust_date',
            ),
            '需求数量' =>
            array (
                    'col' => 'require_qty',
            ),
            '已备货数量' =>
            array (
                    'col' => 'stocked_qty',
            ),
            '触发需求' =>
            array (
                    'col' => 'is_trigger_pr',
            ),
            '创建时间' =>
            array (
                    'col' => 'created_at',
            ),
            '是否过期' =>
            array (
                    'col' => 'expired',
            ),
            '备注' =>
            array (
                    'col' => 'remark',
            ),
        );

    }
}