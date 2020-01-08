<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 11:48
 */

require_once APPPATH . 'modules/basic/classes/contracts/DefaultTemplate.php';


class FbaLogisticsTemplate extends DefaultTemplate
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
                    'col' => 'approve_zh_name',
                    'width' => 18,
                ),
            '审核时间' =>
                array (
                    'col' => 'approve_at',
                    'width' => 18,
                ),
        ];
        $sign_field    = [
            '请勿改动此标记' =>
                [
                    'col' => 'id',
                ]
        ];

        return $this->_title_map = $this->_ci->usercfgprofileservice->export_temple('fba_logistics_list', [], $special_field, [], $sign_field);

    }
}