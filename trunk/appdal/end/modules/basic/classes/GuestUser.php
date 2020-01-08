<?php 

include_once dirname(__FILE__).'/User.php';

/**
 * 用户的一层封装
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw 
 */
class GuestUser extends User
{
    
    public function __construct($params  = array())
    {
        parent::__construct($params);
    }
    
    /**
     * 接口
     * 
     * @return array
     */
    public function get_user_info() : array
    {
        return [
                'uid' => 0,
                'user_name' => '游客',
                'login_name' => '游客',
        ];
    }

    /**
     * 转发调用的方法返回false
     *
     * @param unknown $method
     * @param unknown $params
     */
    public function __call($method, $params)
    {
        return NULL;
    }
}