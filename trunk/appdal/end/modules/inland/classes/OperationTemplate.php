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
class OperationTemplate extends DefaultTemplate
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
            '创建人' =>
                array (
                    'col' => 'created_zh_name',
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
        ];
        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('inland_operation_cfg_list','',$special_field);

        return $this->_title_map = array (
            'gid' =>
                array (
                    'col' => 'gid',
                ),
            '不参与运算开始时间' =>
                array (
                    'col' => 'set_start_date',
                ),
            '不参与运算结束时间' =>
                array (
                    'col' => 'set_end_date',
                ),
            '不参与运算平台' =>
                array (
                    'col' => 'platform_name',
                ),
            '创建人' =>
                array (
                    'col' => 'created_zh_name',
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
            '备注' =>
                array (
                    'col' => 'remark',
                ),
        );

    }
}