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
class SummaryTemplate extends DefaultTemplate
{
    /**
     * 默认模板
     * @return string[][]|number[][]|string[][][]|number[][][]
     */
    public final function get_default_template_cols()
    {
        return $this->_title_map = array (
                'gid' =>
                array (
                        'col' => 'gid',
                        'width' => 18,
                ),
                '汇总单号' =>
                array (
                        'col' => 'sum_sn',
                ),
                'SKU' =>
                array (
                        'col' => 'sku',
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
                '总需求数量' =>
                array (
                        'col' => 'total_required_qty',
                ),
                '最早缺货时间' =>
                array (
                        'col' => 'earliest_exhaust_date',
                ),
                '创建时间' =>
                array (
                    'col' => 'created_at',
                    'width' => 18,
                ),
                '备注' =>
                array (
                        'col' => 'remark',
                ),
        );
        
    }
}