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
class SpecialListTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('inland_special_list');

        return $this->_title_map = array (
                'gid' =>
                array (
                        'col' => 'gid',
                ),
                '需求单号' =>
                array (
                        'col' => 'pr_sn',
                ),
                '审核状态' =>
                array (
                        'col' => 'approve_state',
                ),
                '申请日期' =>
                array (
                        'col' => 'requisition_date',
                ),
                '申请人' =>
                array (
                        'col' => 'requisition_uid',
                ),
                '申请平台' =>
                array (
                        'col' => 'requisition_platform_code',
                ),
                'SKU' =>
                array (
                        'col' => 'sku',
                ),
                '需求数量' =>
                array (
                        'col' => 'require_qty',
                ),
                '申请原因' =>
                array (
                        'col' => 'requisition_reason',
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
                'SKU是否匹配' =>
                array (
                        'col' => 'is_sku_match',
                ),
                '创建时间' =>
                array (
                        'col' => 'created_at',
                ),
                '修改人' =>
                array (
                        'col' => 'updated_uid',
                ),
                '修改时间' =>
                array (
                        'col' => 'updated_at',
                ),
                '审核人' =>
                array (
                        'col' => 'approved_uid',
                ),
                '审核时间' =>
                array (
                        'col' => 'approved_at',
                ),
                '备注' =>
                array (
                        'col' => 'remark',
                ),
        );

    }
}