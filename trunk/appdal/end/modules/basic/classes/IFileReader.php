<?php 

/**
 * 读取文件接口
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-02
 * @link
 * @throw
 */

interface IFileReader extends Iterator
{
    
    public function get_title();
    
    public function get_count();
    
    public function get_data();
    
    /**
     * 读取一行
     */
    public function get_row();
    
    /**
     * 读取一行，并按照$explode_char拆分
     * @param string $explod_char
     * @return array
     */
    public function get_explode_row($explode_char = ',') : array;
    
    public function current ();
    
    public function next ();
    
    public function key ();
    
    public function valid ();
    
    public function rewind ();
}