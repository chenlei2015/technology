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
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('inland_pr_track_list');

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
                '是否退税' =>
                array (
                        'col' => 'is_refund_tax',
                ),
                '采购仓库' =>
                array (
                        'col' => 'purchase_warehouse_id',
                ),
                '产品名称' =>
                array (
                        'col' => 'sku_name',
                ),
                '需求数量' =>
                array (
                        'col' => 'require_qty',
                ),
                '预计断货时间' =>
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
                '已备货数量' =>
                array (
                        'col' => 'stocked_qty',
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