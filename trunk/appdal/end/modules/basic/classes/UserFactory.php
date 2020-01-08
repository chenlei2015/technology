<?php 

include_once dirname(__FILE__).'/User.php';

/**
 * 获取当前登录的用户对象， 简单工厂
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw 
 */
final class UserFactory
{
    /**
     * 
     * @var unknown
     */
    private static $_user;
    
    public static function getInstance($refresh = false)
    {
        if (!$refresh  && self::$_user instanceof User)
        {
            return self::$_user;
        }
        
         $_ci =& get_instance();
        
        if (is_login())
        {
            $_ci->load->service('UserService');
            $user_info = $_ci->userservice->get_user_info();
            if (is_api_user())
            {
                $_ci->load->classes('basic/classes/ApiUser', $user_info);
                self::$_user = $_ci->ApiUser;
            }
            else
            {
                $_ci->load->classes('basic/classes/LoginUser', $user_info);
                self::$_user = $_ci->LoginUser;
            }
            return self::$_user;
            
        }
        elseif (is_system_call())
        {
            $_ci->load->classes('basic/classes/SystemUser');
            self::$_user = $_ci->SystemUser;
            return self::$_user;
        }
        else
        {
            $_ci->load->classes('basic/classes/GuestUser');
            self::$_user = $_ci->GuestUser;
            return self::$_user;
        }
        
    }
    
    public function get_login_info()
    {
        return [
            'openid' => 100,
            'username' => '登录用户'
        ];
    }

}