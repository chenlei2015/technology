<?php 

/**
 * 模板接口, 模板决定数据
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw
 */
interface ExportTemplate
{
    /**
     * 接收原始数据
     * 
     * @param unknown $data
     */
    public function accept($data);
    
    /**
     * 制定规则
     */
    public function configiure();
    
    /**
     * 生成数据
     */
    public function make();
    
    /**
     * 获取数据
     */
    public function get();
    
}