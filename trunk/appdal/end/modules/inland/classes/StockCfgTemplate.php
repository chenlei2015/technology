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
class StockCfgTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        $special_field = [
            '修改人' =>
                array (
                    'col' => 'updated_zh_name',
                ),
            '修改时间' =>
                array (
                    'col' => 'updated_at',
                ),
            '审核人' =>
                array (
                    'col' => 'approved_zh_name',
                ),
            '审核时间' =>
                array (
                    'col' => 'approved_at',
                ),
        ];
        $sign_field    = [
            '请勿改动此标记' =>
                [
                    'col' => 'id',
                ]
        ];

        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('inland_stock_cfg_list', [], $special_field, [], $sign_field);

        return $this->_title_map = array (

            '规则' =>
                array (
                    'col' => 'rule_type',
                ),
            '审核状态' =>
                array (
                    'col' => 'state',
                ),
            'SKU' =>
                array (
                    'col' => 'sku',
                ),
            '产品名称' =>
                array (
                    'col' => 'sku_name',
                ),
            '一级产品线' =>
                array (
                    'col' => 'path_name_first',
                ),
            '是否退税' =>
                array (
                    'col' => 'is_refund_tax',
                ),
            '采购仓库' =>
                array (
                    'col' => 'purchase_warehouse_id',
                ),
            '货源状态' =>
                array (
                    'col' => 'provider_status',
                ),
            'SKU状态' =>
                array (
                    'col' => 'sku_state',
                ),
            '是否精品' =>
                array (
                    'col' => 'quality_goods',
                ),
            '备货方式' =>
                array (
                    'col' => 'stock_way',
                ),
            '缓冲库存天数' =>
                array (
                    'col' => 'bs',
                ),
            '备货处理周期' =>
                array (
                    'col' => 'sp',
                ),
            '发运时效' =>
                array (
                    'col' => 'shipment_time',
                ),
            '首次供货周期' =>
                array (
                    'col' => 'first_lt',
                ),
            '一次备货天数' =>
                array (
                    'col' => 'sc',
                ),
            '服务对应"Z"值' =>
                array (
                    'col' => 'sz',
                ),
            '开发完成时间' =>
                array (
                    'col' => 'deved_time',
                ),
            '首次刊登时间' =>
                array (
                    'col' => 'published_time',
                ),
            '创建时间' =>
                array (
                    'col' => 'created_at',
                ),
            '修改人' =>
                array (
                    'col' => 'updated_zh_name',
                ),
            '修改时间' =>
                array (
                    'col' => 'updated_at',
                ),
            '审核人' =>
                array (
                    'col' => 'approved_zh_name',
                ),
            '审核时间' =>
                array (
                    'col' => 'approved_at',
                ),
            '备注' =>
                array (
                    'col' => 'remark',
                ),
            '特殊标记切勿修改' =>
                array (
                    'col' => 'gid',
                ),
        );

    }
}