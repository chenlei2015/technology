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
class PlatformListTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {

        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('oversea_platform_list');

        return $this->_title_map = array (
                'gid' =>
                array (
                        'col' => 'gid',
                        'width' => 18,
                ),
                '平台需求单号' =>
                array (
                        'col' => 'pr_sn',
                        'width' => 18,
                ),
                '审核状态' =>
                array (
                        'col' => 'approve_state',
                        'width' => 18,
                ),
                'SKU' =>
                array (
                        'col' => 'sku',
                        'width' => 18,
                ),
                '海外站点' =>
                array (
                        'col' => 'station_code',
                        'width' => 18,
                ),
                '平台' =>
                array (
                        'col' => 'platform_code',
                        'width' => 18,
                ),
                '是否退税' => [
                        'col' => 'is_refund_tax',
                        'width' => 18,
                ],
                '采购仓库' => [
                        'col' => 'purchase_warehouse_id',
                        'width' => 18,
                ],
                'BD(pcs)' =>
                array (
                        'col' => 'bd',
                        'width' => 18,
                ),
                '产品名称' => [
                        'col' => 'sku_name',
                        'width' => 18,
                ],
                'SKU状态' => [
                        'col' => 'sku_state',
                         'width' => 18,
                ],
                '物流属性' =>
                array (
                        'col' => 'logistics_id',
                        'width' => 18,
                ),
                '平台加权日均销量(pcs)' =>
                array (
                        'col' => 'weight_sale_pcs',
                        'width' => 18,
                ),
                '备货提前期(day)' =>
                array (
                        'col' => 'pre_day',
                        'width' => 18,
                ),
                '平台毛需求(pcs)' =>
                array (
                        'col' => 'require_qty',
                        'width' => 18,
                ),
                '一次备货天数SC(day)' =>
                array (
                        'col' => 'sc_day',
                        'width' => 18,
                ),
                '平台订购数量(pcs)' =>
                array (
                        'col' => 'purchase_qty',
                        'width' => 18,
                ),
                '创建时间' =>
                array (
                    'col' => 'created_at',
                    'width' => 18,
                ),
                '修改人' =>
                array (
                    'col' => 'updated_uid',
                    'width' => 18,
                ),
                '修改时间' =>
                array (
                    'col' => 'updated_at',
                    'width' => 18,
                ),
                '审核人' =>
                array (
                    'col' => 'approved_uid',
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
                '是否过期' =>
                array (
                        'col' => 'expired',
                        'width' => 18,
                )

        );
    }
}