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
class PromotionSkuTemplate extends DefaultTemplate
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
                ),
                'SKU' =>
                array (
                        'col' => 'sku',
                ),
                '创建时间' => [
                        'col' => 'created_at',
                ],
                '状态' => [
                        'col' => 'state',
                ],
                '创建人' =>
                array (
                        'col' => 'created_uid',
                ),
                '备注' =>
                array (
                        'col' => 'remark',
                ),
        );
        
    }
}