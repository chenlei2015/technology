<?php 

/**
 * 视图接口， 用于
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw
 */
interface ExportView
{
    /**
     * 浏览器下载
     * 
     * @var integer
     */
    const VIEW_BROWSER = 1;
    
    /**
     * 文件地址
     * 
     * @var integer
     */
    const VIEW_FILE = 2;
    
    /**
     * 数据流
     * 
     * @var integer
     */
    const VIEW_DATA = 3;
    
    
    /**
     * 用数据装配模板
     */
    public function assemble(ExportTemplate $template);
    
    /**
     * 页面输出
     */
    public function display();
}