<?php 

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
abstract class User
{
    /**
     * 用户的登录信息
     * 
     * @var unknown
     */
    protected $_user_info;
    
    /**
     * 初始信息
     * @var unknown
     */
    protected $_orign_info;
    
    /**
     * 变更的信息
     */
    protected $_modify_info;
    
    /**
     * 登录时间
     * 
     * @var unknown
     */
    protected $_login_time;
    
    /**
     * 
     * @var unknown
     */
    protected $_last_sync_time;
    
    protected $_ci;
    
    public function __construct($params  = array())
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('UserService');
        
        $this->_orign_info = $params;
        $this->_user_info = $params;
    }
    
    abstract public function get_user_info() : array;
    
    /**
     * 获取修改的字段， 不含新增
     */
    protected function _detect_modify_cols()
    {
        $modify_order_cols = [];
        $modify_prd_cols = [];
        
        foreach ($this->_orign_info as $col => $val)
        {
            if ($val != $this->_user_info[$col])
            {
                $this->_modify_info[$col] = ['before' => $val, 'after' => $this->_user_info[$col]];
            }
        }
        return $this->_modify_info;
    }
    
    /**
     * 报告变更情况
     * 
     * @return unknown
     */
    public function report()
    {
        return $this->_detect_modify_cols();
    }
    
    /**
     * 设置属性, 两个参数为订单信息， 三个参数设置sku信息
     * $col, $val =>
     *
     */
    public function set()
    {
        $args_count = func_num_args();
        $args = func_get_args();
        if (count($args) == 2)
        {
            if (isset($this->_user_info[$args[0]]))
            {
                $this->_user_info[$args[0]] = $args[1];
                return $this;
            }
            throw new \RuntimeException(sprintf('设置用户属性错误，无法找到属性名：%s', $args[0]), 500);
        }
        elseif (count($args) == 3)
        {
           //设置数组
        }
        return $this;
    }
    
    /**
     * 获取用户的信息
     */
    public function get()
    {
        $args_count = func_num_args();
        $args = func_get_args();
        if (count($args) == 0)
        {
            return $this->_user_info;
        }
        elseif (count($args) == 1)
        {
            if (is_array($args[0]))
            {
                return array_intersect_key($this->_user_info, array_flip($args[0]));
            }
            else 
            {
                if (isset($this->_user_info[$args[0]]))
                {
                    return $this->_user_info[$args[0]];
                }
            }
            
            throw new \RuntimeException(sprintf('获取用户属性错误，无法找到属性名：%s', $args[0]), 500);
        }
        elseif (count($args) == 2)
        {
            if (isset($this->_user_info[$args[0]][$args[1]]))
            {
                return $this->_user_info[$args[0]][$args[1]];
            }
            throw new \RuntimeException(sprintf('获取用户属性错误，路径错误', implode(',', $args)), 500);
        }
        throw new \RuntimeException(sprintf('获取用户属性错误，参数错误'), 500);
    }
    
    /**
     * 判断是否存在key
     * 
     * @return unknown|boolean
     */
    public function has()
    {
        $args_count = func_num_args();
        $args = func_get_args();
        if (count($args) == 1)
        {
            return isset($this->_user_info[$args[0]]);
        }
        elseif (count($args) == 2)
        {
            return isset($this->_user_info[$args[0]][$args[1]]);
        }
        return false;
    }
    
    /**
     * 静默获取
     * 
     * @return array|unknown|boolean
     */
    public function silent_get($col)
    {
        $args = func_get_args();
        try {
            return $this->get(...$args);
        } 
        catch (\Exception $e)
        {
            return null;    
        }
    }
    
    /**
     * 注销
     */
    public function logout()
    {
        $this->_user_info = [];
        $this->_orign_info = [];
        $this->_modify_info = [];
        global $g_api_login, $g_system_login;
        $g_api_login = null;
        $g_system_login = null;
    }
    
    /**
     * 设置属性获取
     * 
     * @param unknown $col
     * @return NULL|unknown
     */
    public function __get($col)
    {
        return $this->_user_info[$col] ?? null;
    }
    
    /**
     * 设置属性
     * 
     * @param unknown $col
     * @param unknown $val
     * @throws \RuntimeException
     */
    public function __set($col, $val)
    {
        if (isset($this->_user_info[$col]))
        {
            $this->_user_info[$col] = $val;
        } 
        else 
        {
            throw new \RuntimeException(sprintf('不存在的用户属性：%s', $col));
        }
    }
    
    /**
     *
     * @return unknown
     */
    public function __toString()
    {
        return $this->_user_info;
    }

}