<?php 

include_once APPPATH . 'modules/basic/classes/contracts/OrderState.php';

/**
 * inland special 状态机接口, 检测各个状态跳转是否合法并调用回调完成jump动作
 * 
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-21
 * @link
 * @throw 
 */
class PrSpecialState extends OrderState
{
    
    public function __construct($params = array())
    {
        parent::__construct($params);
        $this->_set_state_action();
        $this->_route = $this->_set_default();
        $this->_register_status = SPECIAL_CHECK_STATE;
    }
    
    /**
     * api, 是否可以状态跳转，逻辑关系交给_call保证
     * 
     * @return OrderState
     */
    public function jump()
    {
        if (!in_array($this->_end, $this->_route[$this->_start] ?? [])){
            throw new \InvalidArgumentException(sprintf('状态不能从 %s 变更到  %s ', $this->_register_status[$this->_start]['name'], $this->_register_status[$this->_end]['name']), 3001);
        }
        return $this->_call();
    }
    
    /**
     * api，只验证状态是否可以跳转，不执行动作
     * 
     * {@inheritDoc}
     * @see OrderState::can_jump()
     */
    public function can_jump() : bool
    {
        return in_array($this->_end, $this->_route[$this->_start]);
    }
    
    /**
     * 根据当前状态获取可操作的url
     * 这里实际没有启用
     */
    public function actions($extend_params = []) : array
    {
        $base = [
            'view' => [
                'name' => 'view',
                'url'  => $extend_params['site_url'].'/inland/PR/view/'.$extend_params['gid']                    
            ]
        ];
        
        $edit = [];
        
        if ($this->can_action('edit'))
        {
            $edit = [
                'edit' => [
                    'name' => 'edit',
                    'url'  => $extend_params['site_url'].'/inland/PR/edit/'.$extend_params['gid']
                ]
            ];
        }
        
        $remark = [];
        
        if ($this->can_action('remark'))
        {
            $remark = [
                    'remark' => [
                            'name' => 'remark',
                            'url'  => $extend_params['site_url'].'/inland/PR/remark/'.$extend_params['gid']
                    ]
            ];
        }
        
        return array_merge($base, $edit, $remark);
    }
    
    /**
     * 设置状态跳转所有可走的路径，不同的路径需要触发指定的条件。
     * 
     * @return string[][]
     */
    private final function _set_default()
    {
        return [
                SPECIAL_CHECK_STATE_UNAUDITED => [SPECIAL_CHECK_STATE_INIT],
                SPECIAL_CHECK_STATE_INIT => [SPECIAL_CHECK_STATE_SUCCESS,SPECIAL_CHECK_STATE_FAIL],
                SPECIAL_CHECK_STATE_FAIL => [SPECIAL_CHECK_STATE_INIT]
        ];
    }
    
    /**
     * action注册的动态方法
     * 
     * @return string[]|NULL[]
     */
    private final function _set_state_action()
    {
        return $this->_state_action_map = [
                'view' => '*',
                //待审核、sku必须匹配、需求数量必须大于0可以修改
                'edit_pr' => (
                    function($params) {
                        return in_array($params['approve_state'], [SPECIAL_CHECK_STATE_INIT]) &&
                        $params['require_qty'] > 0 &&
                        $params['is_sku_match'] == SKU_MATCH_STATE_TRUE;
                    }),
        ];
    }
    
    /**
     * 不进行状态跳转，只执行注册的action动作
     */
    public function do_action()
    {
        return $this->_call();
    }
    
    /**
     * 
     * @return mixed|boolean
     */
    protected function _call()
    {
        if (isset($this->_handler[$this->_start][$this->_end]))
        {
            return call_user_func_array($this->_handler[$this->_start][$this->_end][0], array($this->_handler[$this->_start][$this->_end][1] ?? []));
        }
        return true;
    }
    
}