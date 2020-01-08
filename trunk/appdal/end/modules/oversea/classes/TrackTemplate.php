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
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('oversea_track_list');

        return $this->_title_map = array (
                'gid' =>
                array (
                        'col' => 'gid',
                        'width' => 18,
                ),
                '需求单号' =>
                array (
                        'col' => 'pr_sn',
                ),
                'SKU' =>
                array (
                        'col' => 'sku',
                ),
                '海外站点' =>
                array (
                        'col' => 'station_code',
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
                'BD(pcs)' =>
                array (
                        'col' => 'bd',
                ),
                '需求数量(pcs)' =>
                array (
                        'col' => 'require_qty',
                ),
                '已备货数量' =>
                array (
                        'col' => 'stocked_qty',
                ),
                '预计缺货时间' =>
                array (
                        'col' => 'expect_exhaust_date',
                ),
                '汇总单号' =>
                array (
                        'col' => 'sum_sn',
                ),
                '备货单号' =>
                array (
                        'col' => 'pur_sn',
                ),
                '备货状态' =>
                array (
                        'col' => 'pur_state',
                ),
                '创建时间' =>
                array (
                        'col' => 'created_at',
                ),
                '备注' =>
                array (
                        'col' => 'remark',
                ),
        );
                
        
    }
}