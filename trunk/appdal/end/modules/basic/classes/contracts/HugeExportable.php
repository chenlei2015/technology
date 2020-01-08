<?php 

/**
 * 大文件导出csv
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw 
 */

interface HugeExportable
{
    /**
     * 转换友好可读
     * 
     * @var integer
     */
    const VIEW_PRETTY = 1;
    
    /**
     * 原生
     * 
     * @var integer
     */
    const VIEW_ORIGINAL = 2;
    
    /**
     * 获取表头
     */
    public function get_header();
    
    /**
     * 入口
     */
    public function run();
    
}