<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/15
 * Time: 15:44
 */

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';

class FbaDayTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('fba_stock_condition_day');

        return $this->_title_map = array (
            '销售小组' =>
                array (
                    'col' => 'sales_group_id',
                    'width' => 18,
                ),
            '销售人员' =>
                array (
                    'col' => 'salesman',
                ),
            '账号名称' =>
                array (
                    'col' => 'account_id',
                ),
            'SKU' =>
                array(
                    'col' => 'sku',
                    'width' => 18,
                ),
            'SELLERSKU' =>
                array(
                    'col' => 'seller_sku',
                    'width' => 18,
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
                ),
            'FBA站点' =>
                array (
                    'col' => 'station_code',
                    'width' => 18,
                ),
            '上架时间' =>
                array (
                    'col' => 'shelf_time',
                ),
            'SKU状态' =>
                array (
                    'col' => 'listing_state',
                ),
            '过去1天' =>
                array (
                    'col' => 'last_days_1',
                ),
            '过去3天' =>
                array (
                    'col' => 'last_days_3',
                ),
            '过去7天' =>
                array (
                    'col' => 'last_days_7',
                ),
            '过去14天' =>
                array (
                    'col' => 'last_days_14',
                ),
            '过去28天' =>
                array (
                    'col' => 'last_days_28',
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