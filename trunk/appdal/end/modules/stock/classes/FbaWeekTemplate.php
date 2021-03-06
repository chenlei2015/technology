<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/15
 * Time: 15:44
 */

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';

class FbaWeekTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('fba_stock_condition_week');

        return $this->_title_map = array (
            '销售小组' =>
                array (
                    'col' => 'sales_group_id',
                ),
            '销售人员' =>
                array (
                    'col' => 'salesman',
                ),
            '账号名称' =>
                array(
                    'col' => 'account_id',
                    'width' => 18,
                ),
            'SKU' =>
                array(
                    'col' => 'sku',
                    'width' => 18,
                ),
            'SELLERSKU' =>
                array (
                    'col' => 'seller_sku',
                ),
            'FNSKU' =>
                array (
                    'col' => 'fn_sku',
                ),
            'ASIN' =>
                array (
                    'col' => 'asin',
                ),
            '产品名称' =>
                array (
                    'col' => 'product_name',
                    'width' => 18,
                ),
            'FBA站点' =>
                array (
                    'col' => 'station_code',
                ),
            '上架时间' =>
                array (
                    'col' => 'shelf_time',
                ),
            'SKU状态' =>
                array (
                    'col' => 'listing_state',
                ),
            '第1周' =>
                array (
                    'col' => 'week_no_1',
                ),
            '第2周' =>
                array (
                    'col' => 'week_no_2',
                ),
            '第3周' =>
                array (
                    'col' => 'week_no_3',
                ),
            '第4周' =>
                array (
                    'col' => 'week_no_4',
                ),
            '第5周' =>
                array (
                    'col' => 'week_no_5',
                ),
            '本月销量' =>
                array (
                    'col' => 'month_sales',
                ),
            '累计销量' =>
                array (
                    'col' => 'accumulative_sales',
                ),
            '累计退货' =>
                array (
                    'col' => 'accumulative_return',
                ),
            'RMA' =>
                array (
                    'col' => 'rma',
                ),
            '可售库存' =>
                array (
                    'col' => 'can_sale_stock',
                ),
            '不可售库存' =>
                array (
                    'col' => 'cannot_sale_stock',
                ),
            '海运在途' =>
                array (
                    'col' => 'shipping_in_transit',
                ),
            '铁运在途' =>
                array (
                    'col' => 'iron_in_transit',
                ),
            '空运在途' =>
                array (
                    'col' => 'air_in_transit',
                ),
            '蓝单在途' =>
                array (
                    'col' => 'blueorder_in_transit',
                ),
            '红单在途' =>
                array (
                    'col' => 'redorder_in_transit',
                ),
        );

    }
}