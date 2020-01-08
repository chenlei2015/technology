<?php 

include_once dirname(__FILE__).'/LoginUser.php';

/**
 * 登录用户的一层封装
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw 
 */
class ApiUser extends LoginUser
{
    
    public function __construct($params  = array())
    {
        parent::__construct($params);
    }

}