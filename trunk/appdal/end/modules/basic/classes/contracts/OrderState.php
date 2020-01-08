<?php 

/**
 * 检测各个状态跳转是否合法并调用回调完成jump动作
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-05
 * @link
 * @throw 
 */
abstract class OrderState
{
    protected $_ci;
    
    /**
     * 合法注册状态
     * 
     * @var unknown
     */
    protected $_register_status;
    
    /**
     * 开始
     * @var unknown
     */
    protected $_start;
    
    /**
     * 结束
     * 
     * @var unknown
     */
    protected $_end;
    
    /**
     * 注册handler
     * @var unknown
     */
    protected $_handler;
    
    /**
     * 跳转规则
     * @var unknown
     */
    protected $_route;
    
    /**
     * 状态可以操作的回调检测表
     * 
     * @var unknown
     */
    protected $_state_action_map;
    
    
    public function __construct($params  = array())
    {
        $this->_ci =& get_instance();
    }
    
    /**
     * 初始状态
     * 
     * @param unknown $state
     * @throws \InvalidArgumentException
     * @return OrderState
     */
    public function from($state) : OrderState
    {
        if (!isset($this->_register_status[$state]))
        {
            throw new \InvalidArgumentException(sprintf('状态  %d 没有定义', $state));
        }
        $this->_start = intval($state);
        return $this;
    }
    
    /**
     * 目的状态
     * 
     * @param unknown $state
     * @throws \InvalidArgumentException
     * @return OrderState
     */
    public function go($state) : OrderState
    {
        if (!isset($this->_register_status[$state]))
        {
            throw new \InvalidArgumentException(sprintf('状态  %d 没有定义', $state));
        }
        $this->_end = intval($state);
        return $this;
    }
    
    /**
     * 状态跳转动作api
     * 
     * @return OrderState
     */
    abstract function jump();
    
    /**
     * 状态是否调整检测api
     */
    abstract function can_jump() : bool;
    
    /**
     * 当前状态是否能够进行的操作，一个指定的状态只能操作指定的操作，
     * 是否可以操作，是由_state_action_map配置的回调来实现的
     */
    public function can_action($action, $parmas = []) : bool
    {
        $def = $this->_state_action_map[$action] ?? [];
        if (empty($def)) return false;
        if ($def === '*') return true;
        if ($def instanceof Closure)
        {
            return $def($parmas);
        }
        elseif (is_callable($def))
        {
            if (method_exists($this, $def))
            {
                return call_user_func_array([$this, $def], [$parmas]);
            }
            else
            {
                return call_user_func_array($def, $parmas);
            }
        }
        return in_array($this->_start, $def);
    }
    
    /**
     * 设置跳转路由
     */
    public function setRoute($route)
    {
        $this->_route = array_merge($this->_route, $route);
        return $this->_route;
    }
    
    /**
     * 注册跳转的动作hander
     * 
     * @param unknown $handler
     * @param unknown $start give override
     * @param unknown $end   give override
     */
    function register_handler($handler, $params = [], $start = '', $end = '')
    {
        if (!is_callable($handler))
        {
            throw new \RuntimeException(sprintf('状态变更注册回调无法调用'), 500);
        }
        $start = $start ? : $this->_start;
        $end = $end ? : $this->_end;
        $this->_handler[$start][$end] = array($handler, $params);
        return $this;
    }
}