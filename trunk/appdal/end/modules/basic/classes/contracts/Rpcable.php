<?php 

/**
 * 使用远程java
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-02
 * @link
 * @throw
 */

interface Rpcable
{
    /**
     * 本地表模式
     *
     * @var integer
     */
    const SEARCH_MODE_LOCAL = 1;
    
    /**
     * java模式
     *
     * @var integer
     */
    const SEARCH_MODE_JAVA = 2;
    
    /**
     * 是否是rpc服务
     * @return bool
     */
    public function is_rpc($dir) : bool; 
    
    /**
     * 设置rpc中注册的对应名字
     * @return string
     */
    public function get_my_rpc_name($dir) : string;
    
}