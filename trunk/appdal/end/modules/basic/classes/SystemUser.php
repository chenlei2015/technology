<?php 

include_once dirname(__FILE__).'/User.php';

/**
 * 用户的一层封装， 系统用户
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw 
 */
class SystemUser extends User
{
    public function __construct($params  = array())
    {
        $params = empty($params) ? self::default_user_info() : $params;
        parent::__construct($params);
    }
    
    /**
     * 接口
     *
     * @return array
     */
    public function get_user_info() : array
    {
        return $this->get();
    }
    
    /**
     * 系统用户对外展示的名称
     * 
     * @return number[]|string[]
     */
    public static final function default_user_info()
    {
        return [
                'uid' => 0,
                'user_name' => '系统',
                'login_name' => '系统',
        ];
    }
    
    /**
     * 获取计划部的所有成员
     * @todo
     */
    public function get_plan_department_users()
    {
        
    }
    
    /**
     * 获取计划部的主管名单
     * @todo
     */
    public function get_plan_managers()
    {
        
    }
    
    /**
     * 获取计划部主管和所管理的计划部用户列表层级
     * @todo
     */
    public function get_plan_user_trees()
    {
        
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