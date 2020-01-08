<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 11:48
 */

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';


class OverseaStockListTemplate extends DefaultTemplate
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
                    'width' => 18,
                ),
            '修改时间' =>
                array (
                    'col' => 'updated_at',
                    'width' => 18,
                ),
            '审核人' =>
                array (
                    'col' => 'approved_zh_name',
                    'width' => 18,
                ),
            '审核时间' =>
                array (
                    'col' => 'approved_at',
                    'width' => 18,
                ),
        ];
        $sign_field    = [
            '请勿改动此标记' =>
                [
                    'col' => 'gid',
                ]
        ];

        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('oversea_stock_relationship_cfg', [], $special_field, [], $sign_field);

        return $this->_title_map = array (
            '规则' =>
                array (
                    'col' => 'rule_type',
                    'width' => 18,
                ),
            'SKU' =>
                array(
                    'col' => 'sku',
                    'width' => 18,
                ),
            '审核状态' =>
                array (
                    'col' => 'state',
                    'width' => 18,
                ),
            '海外站点' =>
                array(
                    'col' => 'station_code',
                    'width' => 18,
                ),
            '是否退税' =>
                array (
                    'col' => 'is_refund_tax',
                    'width' => 18,
                ),
            '采购仓库' =>
                array (
                    'col' => 'purchase_warehouse_id',
                    'width' => 18,
                ),
            '供应商最小起订金额1' =>
                array (
                    'col' => 'original_min_start_amount',
                    'width' => 18,
                ),
            '供应商最小起订金额2' =>
                array (
                    'col' => 'min_start_amount',
                    'width' => 18,
                ),
            '上架时效(AS)' =>
                array (
                    'col' => 'as_up',
                    'width' => 18,
                ),
            '物流时效LS_空运' =>
                array (
                    'col' => 'ls_air',
                    'width' => 18,
                ),
            '物流时效LS_海运散货' =>
                array (
                    'col' => 'ls_shipping_bulk',
                    'width' => 18,
                ),
            '物流时效LS_海运整柜' =>
                array (
                    'col' => 'ls_shipping_full',
                    'width' => 18,
                ),
            '物流时效LS_铁运散货' =>
                array (
                    'col' => 'ls_trains_bulk',
                    'width' => 18,
                ),
            '物流时效LS_铁运整柜' =>
                array (
                    'col' => 'ls_trains_full',
                    'width' => 18,
                ),
            '物流时效LS_陆运' =>
                array (
                    'col' => 'ls_land',
                    'width' => 18,
                ),
            '物流时效LS_蓝单' =>
                array (
                    'col' => 'ls_blue',
                    'width' => 18,
                ),
            '物流时效LS_红单' =>
                array (
                    'col' => 'ls_red',
                    'width' => 18,
                ),
            '打包时效PT_空运' =>
                array (
                    'col' => 'pt_air',
                    'width' => 18,
                ),
            '打包时效PT_海运散货' =>
                array (
                    'col' => 'pt_shipping_bulk',
                    'width' => 18,
                ),
            '打包时效PT_海运整柜' =>
                array (
                    'col' => 'pt_shipping_full',
                    'width' => 18,
                ),
            '打包时效PT_铁运散货' =>
                array (
                    'col' => 'pt_trains_bulk',
                    'width' => 18,
                ),
            '打包时效PT_铁运整柜' =>
                array (
                    'col' => 'pt_trains_full',
                    'width' => 18,
                ),
            '打包时效PT_陆运' =>
                array (
                    'col' => 'pt_land',
                    'width' => 18,
                ),
            '打包时效PT_蓝单' =>
                array (
                    'col' => 'pt_blue',
                    'width' => 18,
                ),
            '打包时效PT_红单' =>
                array (
                    'col' => 'pt_red',
                    'width' => 18,
                ),
            '缓冲库存(BS)' =>
                array (
                    'col' => 'bs',
                    'width' => 18,
                ),
            '供货周期(L/T)' =>
                array (
                    'col' => 'lt',
                    'width' => 18,
                ),
            '备货处理周期(SP)' =>
                array (
                    'col' => 'sp',
                    'width' => 18,
                ),
            '备货周期(SC)' =>
                array (
                    'col' => 'sc',
                    'width' => 18,
                ),
            '服务对应"Z"值' =>
                array (
                    'col' => 'sz',
                    'width' => 18,
                ),
            '创建时间' =>
                array (
                    'col' => 'created_at',
                    'width' => 18,
                ),
            '修改人' =>
                array (
                    'col' => 'updated_zh_name',
                    'width' => 18,
                ),
            '修改时间' =>
                array (
                    'col' => 'updated_at',
                    'width' => 18,
                ),
            '审核人' =>
                array (
                    'col' => 'approved_zh_name',
                    'width' => 18,
                ),
            '审核时间' =>
                array (
                    'col' => 'approved_at',
                    'width' => 18,
                ),
            '备注' =>
                array (
                    'col' => 'remark',
                    'width' => 18,
                ),
            '请勿改动此标记' =>
                array (
                    'col' => 'gid',
                    'width' => 18,
                ),
        );

    }
}
