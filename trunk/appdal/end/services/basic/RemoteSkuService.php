<?php 

/**
 * 该文件用于获取远程sku信息
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-01-03
 * @link
 */
class RemoteSkuService
{
    private $_curl;
    
    public static $s_remote_api = '';
    
    public function __construct()
    {
       
    }
    
    public function _init()
    {
        $this->_curl = get_curl();    
    }
    
    /**
     * 该方法用户获取远程sku信息，并需要围绕此做尽可能的优化。
     * 并对结果进行关联更新。
     * 
     * @param unknown $sku_sns
     * @return array 返回数据需要制定sku_sn作为key值。
     */
    public function get_skus($sku_sns)
    {
        //build query
        //request
        //response
        //return
        return include_once(APPPATH.'/cache/sku_api.php');
    }
    
    public function get_sku($sku)
    {
        return array (
                'TMP_0001' =>
                array (
                        'price' => 34.321,
                        'name' => 'xls模拟sku名字_0001',
                        'status' => 1,
                        'sku_sn' => 'TMP_0001'
                )
        );
    }

}