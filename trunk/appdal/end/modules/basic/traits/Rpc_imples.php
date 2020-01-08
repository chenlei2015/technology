<?php 

/**
 * rpc行为trait
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-17 09：24
 * @link
 * @throw
 */
trait Rpc_imples
{
    
    /**
     *
     * {@inheritDoc}
     * @see Rpcable::get_my_rpc_name()
     */
    public function get_my_rpc_name($dir) : string
    {
        $url = [$dir, get_called_class()];
        return implode('/', $url);
    }
    
    /**
     * 列表数据是否是rpc服务
     *
     * {@inheritDoc}
     * @see Rpcable::is_rpc()
     */
    public function is_rpc($dir) : bool
    {
        static $rpc_list;
        if (!$rpc_list)
        {
            $ci = property_exists($this, '_ci') ? $this->_ci : CI::$APP;
            $ci->load->config(JAVA_API_CFG_FILE);
            $rpc_list = $ci->config->item('rpc_list');
        }
        return in_array($this->get_my_rpc_name($dir), $rpc_list);
    }
}