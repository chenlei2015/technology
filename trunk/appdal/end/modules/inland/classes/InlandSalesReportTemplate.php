<?php 

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';

/**
 * 国内销量导出模板
 * @author W02278
 * @name InlandSalesReportTemplate Class
 */
class InlandSalesReportTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        return $this->_title_map = array (
                '序号' =>
                array (
                        'col' => 'i',
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
                '28天总销售' =>
                array (
                        'col' => 'accumulated_sale_qty',
                ),
                '排序' =>
                array (
                        'col' => 'sort',
                ),
                '加权日均销量' =>
                array (
                        'col' => 'weight_sale_pcs',
                ),
                '支付标准偏差' =>
                array (
                        'col' => 'deliver_sd_day',
                ),
                '权均供货周期' =>
                array (
                        'col' => 'supply_wa_day',
                ),
        );
        
    }
}