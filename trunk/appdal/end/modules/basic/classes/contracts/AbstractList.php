<?php 

require_once APPPATH . 'modules/basic/classes/contracts/Listable.php';

/**
 * 列表父类
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-02
 * @link
 * @throw
 */

abstract class AbstractList implements Listable
{
    /**
     *
     * @var string
     */
    public static $sn_separate = ',';
    
    /**
     *
     * @var string
     */
    public static $sn_separate_encode = array('%7C', '%20', ',', ' ');
    
    /**
     * 所有的配置
     * @var unknown
     */
    protected $_cfg;
    
    /**
     *
     * @var unknown
     */
    protected $_ci;
    
    /**
     * pagesize
     *
     * @var integer
     */
    protected static $_default_persize = 20;
    
    /**
     * 默认排序
     * 
     * @var string
     */
    protected static $_default_sort = 'id desc';
    
    /**
     * hook 构件
     * 
     * @var unknown
     */
    protected $_hooks;
    
    /**
     * 搜索参数
     * @var array
     */
    public $search_params;
    
    public function __construct()
    {
        $this->importDependent();
        $this->get_cfg();
    }
    
    /**
     * setSearchParams
     *
     * {@inheritDoc}
     * @see AbstractList::setSearchParams()
     */
    public function setSearchParams($params)
    {
        //默认开启
        $params['export_save'] = 1;
        $this->search_params = $params;
    }
    
    /**
     * 全局覆盖
     * 
     * {@inheritDoc}
     * @see Listable::setTableHeader()
     */
    public function setTableHeader(array $header_cols)
    {
        $this->_cfg['title'] = $header_cols;
        return $this;
    }
    
    /**
     * 全局覆盖
     * 
     * {@inheritDoc}
     * @see Listable::setSelect()
     */
    public function setSelect(array $cols)
    {
        $this->_cfg['select_cols'] = $cols;
        return $this;
    }
    
    /**
     * 更新， 根据key值更新
     * 
     * {@inheritDoc}
     * @see Listable::setSearchRule()
     */
    public function setSearchRule(array $rules)
    {
        if (!isset($this->_cfg['search_rule']))
        {
            $this->_cfg['search_rule'] = $rules;
        }
        else
        {
            $this->_cfg['search_rule'] = array_merge($this->_cfg['search_rule'], $rules);
        }
        return $this;
        
    }
    
    /**
     * 注册搜索前置处理， 支持多个hook
     * 
     * {@inheritDoc}
     * @see Listable::setPreSearchHook()
     */
    public function setPreSearchHook($hook, $params = [])
    {
       $this->_hooks['pre'][] = [$hook, $params]; 
    }
    
    /**
     * 这是一个模板方法, 返回的数据由各list的转换服务等处理
     * 
     * {@inheritDoc}
     * @see Listable::execSearch()
     */
    public function execSearch()
    {
       if (empty($this->_cfg))
       {
           $error_msg = sprintf('列表类：%s 必须初始化配置, 请检查_cfg参数', get_called_class());
           throw new \InvalidArgumentException($error_msg, 412);
       }
       
       if (isset($this->_hooks['pre']))
       {
           foreach ($this->_hooks['pre'] as $key => $more_hook)
           {
               //处理hook调用
               $input = isset($more_hook[1]['input']) ? [$more_hook[1]['input']] : [];
               $pre_hook_return = call_user_func_array($more_hook[0], $input);
               
               //返回值如何处理
               if (isset($more_hook[1]['update']))
               {
                   $how_update = $more_hook[1]['update'];
                   if ($how_update != 'none')
                   {
                       if (property_exists($this, $how_update))
                       {
                           $this->$how_update = $pre_hook_return;
                       }
                       else
                       {
                           throw new \RuntimeException(sprintf('搜索Hook：%s 无法更新指定属性:%s', $more_hook[0], $how_update));
                       }
                   }
               }
             
           }
       }
       //pr($this->search_params);exit;
       $search_result = $this->search($this->search_params);
       
       if (isset($this->_hooks['after']))
       {
           foreach ($this->_hooks['after'] as $key => $more_hook)
           {
               //处理hook调用, 通常是返回的list列表
               $input = isset($more_hook[1]['input']) ? $more_hook[1]['input'] : [];
               if (is_string($input) && $input == 'return')
               {
                   $input = $search_result;
               }
               $search_result = call_user_func_array($more_hook[0], [$input]);
               //返回值如何处理
               if (isset($more_hook[1]['update']))
               {
                   $how_update = $more_hook[1]['update'];
                   if ($how_update != 'none')
                   {
                       if (property_exists($this, $how_update))
                       {
                           $this->{$how_update} = $after_hook_return;
                       }
                       else
                       {
                           throw new \RuntimeException(sprintf('搜索Hook：%s 无法更新指定属性:%s', $more_hook[0], $how_update));
                       }
                   }
               }
           }
       }
       return $search_result;
    }
    
    /**
     * 具体搜索，由子类各自实现
     * 
     */
    abstract protected function search($search_params);
    
    /**
     * 转换
     * @param unknown $search_result
     */
    abstract public function translate($search_result);
    
    /**
     * 获取搜索参数
     * 
     * {@inheritDoc}
     * @see Listable::get_search_params()
     */
    public function get_search_params() : array
    {
        return $this->search_params;
    }
    
    /**
     * 设置搜索后后置hook，支持多个
     * 
     * {@inheritDoc}
     * @see Listable::setAfterSearchHook()
     */
    public function setAfterSearchHook($hook, $params = [])
    {
        $this->_hooks['after'][] = [$hook, $params];   
        
    }
    
    public function registerService(\Serviceable $service)
    {
        
    }
    
    /**
     * preSearchHook 过滤空参数
     *
     * @param unknown $params
     * @return array
     */
    protected function hook_filter_params($params)
    {
        return array_filter($params, function($val) { return !(is_null($val) ||  is_string($val) && trim($val) == '');});
    }
    
    /**
     * preSearchHook 转换参数
     *
     * @param unknown $params
     */
    protected function hook_translate_params($params)
    {
        return $params;
    }
    
    /**
     * 格式化为执行最终参数
     *
     * @param unknown $params
     */
    protected function hook_format_params($params)
    {
        //规则定义
        $defind_valid_key = $this->_cfg['search_rules'];
        
        //保留控制字段
        $ctrl_params = [];
        
        /**
         * 定义的字段全部过滤, 保留sort
         */
        foreach ($params as $col => $val)
        {
            //搜索key
            if (isset($defind_valid_key[$col])) {
                continue;
            }
            
            //转换sort_xxxx
            if (substr($col, 0, 5) === 'sort_' && in_array(strtolower($val), ['desc', 'asc']))
            {
                $sort_col = substr($col, 5);
                if (isset($defind_valid_key[$sort_col]))
                {
                    $params['sort'][] = $defind_valid_key[$sort_col]['name'].','.$val;
                    unset($params[$col]);
                    continue;
                }
            }
            
            //控制key
            if (!in_array($col, $ctrl_params))
            {
                unset($params[$col]);
            }
        }
        
        $params = html_escape($params);
        
        foreach ($params as $col => $val)
        {
            if ($col == 'sort') continue;
            
            //转换类型
            if (isset($defind_valid_key[$col]['type']))
            {
                $val = ($defind_valid_key[$col]['type'])($val);
            }
            
            //hook处理字段
            if (isset($defind_valid_key[$col]['hook']))
            {
                $val = call_user_func_array($defind_valid_key[$col]['hook'], [$val]);
            }
            
            //检测取值
            if (isset($defind_valid_key[$col]['callback']))
            {
                if (!call_user_func_array($defind_valid_key[$col]['callback'], [$val]))
                {
                    throw new \InvalidArgumentException(sprintf('搜索列  "%s" 无效的值: %s', $col, $val), 3002);
                }
            }
            
            //如果有列表自定义字段，通过设置自定义字段增加
            if ($this->hook_user_format_params($defind_valid_key, $col, $val, $format_params))
            {
                continue;
            }
            
            $format_params[$defind_valid_key[$col]['name']] = $val;
        }
        
        //针对hook_user_format_params生成的参数做检测
        $this->hook_user_format_params_check($defind_valid_key, $format_params);
        
        //page control
        $format_params['per_page'] = $format_params['per_page'] ?? self::$_default_persize;
        $format_params['page'] = $format_params['page'] ?? 1;
        
        //默认sort
        $format_params['sort'] = $format_params['sort'] ?? explode(',', static::$_default_sort);
        
        return $format_params;
    }
    
    /**
     * 用户自定义处理参数的模板方法，由各自实例化类实现。
     * 
     * @param unknown $defind_valid_key
     * @param unknown $col
     * @param unknown $val
     * @param unknown $format_params
     * @return boolean
     */
    protected function hook_user_format_params($defind_valid_key, $col, $val, &$format_params)
    {
        $rewrite_cols = ['start_date', 'end_date'];
        
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }
        
        //转换日期
        if ($col == 'start_date') {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $unix_time;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的开始时期'), 3001);
            }
        }
        if ($col == 'end_date') {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $unix_time;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的结束日期'), 3001);
            }
        }
        
        return true;
    }
    
    /**
     * 对转换后的参数进行验证, 如果有自定义检测，子类应该覆盖此方法。
     * 
     * @param unknown $defind_valid_key
     * @param unknown $col
     * @param unknown $val
     * @param unknown $format_params
     * @throws \InvalidArgumentException
     */
    protected function hook_user_format_params_check($defind_valid_key, &$format_params)
    {
        $check_cols = ['created_at'];
        foreach ($check_cols as $key)
        {
            if (isset($format_params[$key]))
            {
                if (count($format_params[$key]) == 1) {
                    
                    if (isset($format_params[$key]['start']))
                    {
                        $format_params[$key]['end'] = strtotime(date('Y-m-d'));
                        //mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('y')));
                    }
                    else
                    {
                        //$format_params[$key]['start'] = mktime(23, 59, 59, 1, 1, intval(date('y')));
                        $format_params[$key]['start'] = $format_params[$key]['end'];
                    }
                }
                if ($format_params[$key]['start'] > $format_params[$key]['end'])
                {
                    //交换时间
                    $tmp = $format_params[$key]['start'];
                    $format_params[$key]['start'] =  $format_params[$key]['end'];
                    $format_params[$key]['end'] = $tmp;
                    //throw new \InvalidArgumentException(sprintf('开始时间不能晚于结束时间'), 3001);
                }
                //为开始日期和结束日期添加 00：00：01 和 23:59:59
                $start = $format_params[$key]['start'];
                $end = $format_params[$key]['end'];
                $format_params[$key]['start'] = mktime(0, 0, 1, intval(date('m', $start)), intval(date('d', $start)), intval(date('y', $start)));
                $format_params[$key]['end'] = mktime(23, 59, 59, intval(date('m', $end)), intval(date('d', $end)), intval(date('y', $end)));
            }
        }
        
        return true;
    }
    
    /**
     * 访问无效的方法
     * 
     * @param unknown $method
     */
    public function __call($method, $params)
    {
        return false;
    }
}