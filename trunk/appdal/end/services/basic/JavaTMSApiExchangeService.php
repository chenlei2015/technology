<?php

require_once APPPATH . 'modules/basic/classes/contracts/ApiAdapterable.php';

/**
 * 参数转
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-03-10
 * @link
 */
class JavaTMSApiExchangeService implements ApiAdapterable
{
    /**
     * 控制参数
     * @var unknown
     */
    public $ctrl_code;
    
    /**
     * 请求code
     * @var unknown
     */
    private $_api_code;
    
    /**
     * api配置文件
     *
     * @var unknown
     */
    private $_api_cfg;
    
    /**
     * 表映射文件
     *
     * @var unknown
     */
    private $_tbl_map;
    
    /**
     * 当前api配置文件
     *
     * @var unknown
     */
    private $_current_api_cfg;
    
    /**
     * 处理结果回调
     *
     * @var unknown
     */
    private $_after_cb;
    
    /**
     *
     * @var unknown
     */
    private $_pre_cb;
    
    /**
     * 调试模式
     *
     * @var string
     */
    private $_debug = false;
    
    /**
     * 调试信息
     * @var array
     */
    private $_debug_info = [];
    
    private $_ci;
    
    /**
     *
     * @var string
     */
    private $_oauth_token_key = 'JAVA_TMS_OAUTH_TOKEN';
    
    private $_token;
    
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->library('rediss');
    }
    
    /**
     * 设置请求的接口
     *
     * @param unknown $module
     * @param unknown $api_code
     */
    public function set($api_code)
    {
       $this->_api_code = $api_code;
       $this->_get_api_config($this->_api_code);
       return $this;
    }
    
    /**
     * 设置外部控制参数
     *
     * show_api_url => 显示请求api,
     * show_api_params => '显示api请求参数',
     * show_map_input => '显示入参转换表'
     * show_map_output => '显示出参转换表'
     * show_api_response => '显示接口原生数据'
     *
     * @param unknown $params
     */
    public function options($params)
    {
        $this->ctrl_code = $params;
        if (isset($params['debug']))
        {
            $this->_debug = true;
        }
        return $this;
    }
    
    /**
     * 取api配置
     *
     * @param unknown $api_code
     * @return string
     */
    private function _get_api_config($api_code)
    {
        if (!$this->_api_cfg)
        {
            $this->_ci->load->config(JAVA_API_CFG_FILE);
            $this->_api_cfg = $this->_ci->config->item('api');
            $this->_tbl_map = $this->_ci->config->item('tbl');
        }
        $this->_current_api_cfg =  $this->_api_cfg[$api_code] ?? '';
        if (!$this->_current_api_cfg)
        {
            throw new \InvalidArgumentException(sprintf('RPC调用%s不存在', $this->_api_code), 412);
        }
        $this->_debug && ($this->_debug_info['show_map_input'] = $this->_current_api_cfg['map']['input'] ?? []);
        $this->_debug && ($this->_debug_info['show_map_output'] = $this->_current_api_cfg['map']['output'] ?? []);
        return $this->_current_api_cfg;
    }

    /**
     *
     * @param callable $cb
     */
    public function after(?callable $cb)
    {
        $this->_after_cb = $cb;
        return $this;
    }
    
    public function pre(callable $cb)
    {
        $this->_pre_cb = $cb;
        return $this;
    }
    
    /**
     * 执行查询
     */
    public function run($input_params = [], $header = [])
    {
        $other_params = $this->transalte_input($input_params, $header);
        return $this->transalte_output($this->request($other_params, $header));
        
    }
    
    /**
     * 获取token
     * http://192.168.71.156/web/#/73?page_id=3162
     *
     * @throws \RuntimeException
     */
    private function _get_access_token()
    {
        static $repeat_counter;
        //获取token
        $curl = get_curl();
        $auth_opts = [
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => sprintf('%s:%s', JAVA_SECRET_USER, JAVA_SECRET_PASS)
        ];
        
        repeat_request:
        if (is_numeric($repeat_counter) && $repeat_counter >= 3)
        {
            throw new \BadMethodCallException(sprintf('授权认证token接口已重试3次失败，服务可能重启中，请稍后重试或联系管理员处理'), 500);
        }
        try {
            $tokens = $curl->http_post(JAVA_TMS_TOKEN_PATH, [], [], $auth_opts);
            $token_info = json_decode($tokens, true);
            if (!$token_info)
            {
                log_message('ERROR', sprintf('OAUTH JAVA SECRET TOKEN FAIL, 返回消息无法解析', $tokens));
                throw new \RuntimeException('接口授权认证失败', 500);
            }
            $this->_ci->rediss->setData($this->_oauth_token_key, $tokens, 3600);
            $this->_token = $token_info['data'];
            return $this->_token;
        }
        catch(\Exception $e)
        {
            $message = json_decode($e->getMessage(), true);
            if ($message && isset($message['error']) && $message['error'] == 'unauthorized')
            {
                log_message('ERROR', sprintf('OAUTH JAVA SECRET TOKEN FAIL， curl error %s', $e->getMessage()));
                throw new \BadMethodCallException('接口授权认证失败，用户名或密码错误', 500);
            }
            else
            {
                log_message('ERROR', sprintf('OAUTH JAVA SECRET TOKEN FAIL, curl error %s', $e->getMessage()));
                //重试
                if (!$repeat_counter)
                {
                    $repeat_counter = 1;
                }
                else
                {
                    $repeat_counter += 1;
                }
                goto repeat_request;
            }
        }
    }
    
    /**
     * 获取token
     *
     * @return mixed|unknown
     */
    protected function get_token()
    {
        //return $this->_get_access_token();
        
        $token = $this->_ci->rediss->getData($this->_oauth_token_key);

        if (!$token)
        {
            return $this->_get_access_token();
        }
        if ($this->_ci->rediss->getTTL($this->_oauth_token_key) < 30)
        {
            return $this->_get_access_token();
        }
        $token_arr = json_decode($token, true);
        $this->_token = $token_arr['data'];
        return $this->_token;
    }
    
    /**
     *
     * @param unknown $url
     * @param unknown $method
     * @param unknown $params
     * @param unknown $header
     * @return array
     */
    protected function request($params, $header)
    {
        static $repeat_counter;
        
        $api_code_cfg = $this->_current_api_cfg;
        $method = $api_code_cfg['method'];
        $curl = get_curl();
        
        repeat_request:
        if (is_numeric($repeat_counter) && $repeat_counter >= 3)
        {
            throw new \RuntimeException(sprintf('接口:%s已重试3次失败。接口已不可用，请稍后重试或联系管理员处理', $api_code_cfg['api']), 500);
        }
        
        try {
            $url = $this->build_api($api_code_cfg);
//            echo $url;exit;
            if (strtolower($method) == 'post')
            {
                $rsp = $curl->http_post($url, $params, $header);
                $list = json_decode($rsp, true);
                if (!$list)
                {
                    log_message('ERROR', sprintf('RPC请求远程接口：%s, 方法：%s, 参数： %s, 返回无效数据：%s', $url, $method, json_encode($params), $rsp));
                    return [];
                }
            }
            else
            {
                $rsp = $curl->http_get($url, $params, $header);
                $list = json_decode($rsp, true);
                if (!$list)
                {
                    log_message('ERROR', sprintf('RPC请求远程接口：%s, 方法：%s, 参数： %s, 返回无效数据：%s', $url, $method, json_encode($params), $rsp));
                    return [];
                }
            }
            //log_message('ERROR', sprintf('RPC-DEBUG: RPC请求远程接口：%s, 方法：%s, 参数： %s, 返回数据：%s', $url, $method, json_encode($params), $rsp));
            $this->_debug and ($this->_debug_info['show_api_response'] = $rsp);
            return $list;
        }
        catch (\BadMethodCallException $e)
        {
            //token服务不可用
            throw new \RuntimeException($e->getMessage(), 500);
        }
        catch (\Exception $e)
        {
            //接口不可用，重试
            if (!$repeat_counter)
            {
                $repeat_counter = 1;
            }
            else
            {
                $repeat_counter += 1;
            }
            
            //token过期
            $message = json_decode($e->getMessage(), true);
            if ($message && isset($message['error']) && $message['error'] == 'invalid_token')
            {
                $this->_get_access_token();
                log_message('ERROR', sprintf('OAUTH JAVA SECRET TOKEN FAIL, 重新请求了token， curl error %s', $e->getMessage()));
                goto repeat_request;
            }
            else
            {
                log_message('ERROR', sprintf('接口: %s请求失败, curl error %s', $api_code_cfg['api'], $e->getMessage()));
                sleep(1);
                goto repeat_request;
                //throw new \RuntimeException(sprintf('接口: %s请求失败, 错误提示： %s', $api_code_cfg['api'], $e->getMessage()), 500);
            }
        }
    }
    
    /**
     * 转换参数
     *
     * @param unknown $input_params
     * @param unknown $api_code_cfg
     * @return array|unknown
     */
    protected function transalte_input($input_params, &$header)
    {
        if (empty($input_params))
        {
            return [];
        }
        //附加登录参数
        if (isset($this->_current_api_cfg['send_login_uid']))
        {
            $active_login_info = get_active_user()->get_user_info();
            $input_params['loginUid'] = $active_login_info['oa_info']['staff_code'];
        }
        
        $api_code_cfg = $this->_current_api_cfg;
        $map = $api_code_cfg['map']['input'] ?? [];
        if (!empty($map))
        {
            //只能处理一维数组
            if (is_array($map))
            {
                //参数不匹配，也传送
                foreach ($input_params as $col => $val)
                {
                    $other_col = $map[$col] ?? $col;
                    if (is_array($other_col))
                    {
                        //数组对应多个参数
                        $other_params = array_merge($other_params, array_combine($other_col, array_values($val)));
                    }
                    else
                    {
                        $other_params[$other_col] = $val;
                    }
                    if ($this->_debug)
                    {
                        $debug_key = sprintf('%s<--->%s', $col, $other_col);
                        $this->_debug_info['show_match_params'][$debug_key] = $val;
                    }
                }
            }
            elseif (is_string($map))
            {
                //转换表转换
                $tbl_map = $this->_tbl_map[$map] ?? [];
                if (empty($tbl_map))
                {
                    $other_params = $input_params;
                }
                else
                {
                    $tbl_map = array_flip($tbl_map);
                    
                    //参数不匹配，也传送
                    foreach ($input_params as $col => $val)
                    {
                        $other_col = $tbl_map[$col] ?? $col;
                        $other_params[$other_col] = $val;
                        if ($this->_debug)
                        {
                            $debug_key = sprintf('%s<--->%s', $col, $other_col);
                            $this->_debug_info['show_match_params'][$debug_key] = $val;
                        }
                    }
                }
            }
        }
        else
        {
            $other_params = $input_params;
        }
        if (isset($api_code_cfg['type']))
        {
            switch ($api_code_cfg['type'])
            {
                case 'json':
                    $other_params = json_encode($other_params);
                    $header[] = 'Content-type:application/json;charset=UTF-8';
                    break;
                default:
                    break;
            }
        }
        $this->_debug && ($this->_debug_info['show_request_params'] = $other_params);
        return $other_params;
    }
    
    private function _check_input_params_dimension($arr)
    {
        $keys = array_keys($arr);
        $non_number_keys = array_filter($keys, function($val) { return !is_numeric($val);});
        if (count($non_number_keys) > 0)
        {
            //关联数组
            return 1;
        }
        //数字， 是否是有序数字
        $copy_keys = $keys;
        sort($copy_keys);
        if ($keys == $copy_keys)
        {
            //维度
            foreach ($arr as $key => $val)
            {
                if (is_array($val))
                {
                    //数字key的2位以上数组
                    return 2;
                }
            }
        }
        
        return ;
    }
    
    /**
     * 转换
     *
     * @param unknown $api_return
     * @param unknown $api_code_cfg
     * @return array|unknown
     */
    protected function transalte_output($api_return)
    {
        /*if (empty($api_return))
        {
            return [];
        }*/
        $api_code_cfg = $this->_current_api_cfg;
        if (!is_callable($this->_after_cb))
        {
            return $api_return;
        }
        $map_index = $api_code_cfg['map']['output'] ?? '';
        $map = $this->_tbl_map[$map_index] ?? [];
        return ($this->_after_cb)($api_return, $map);
    }
    
    /**
     * 生成请求api
     *
     * @param unknown $api_code_cfg
     * @return string
     */
    protected function build_api($api_code_cfg)
    {
        $url = trim($api_code_cfg['server'], '/') . '/' .$api_code_cfg['api'];
        //附加token
        if (ENABLE_JAVA_API_TOKEN)
        {
            strpos($url, '?') ? $url .= '&access_token='.$this->get_token() : $url .= '?access_token='.$this->get_token();
        }
        $this->_debug and ($this->_debug_info['show_api_url'] = $url);
        return $url;
    }
   
    /**
     * show_api_url => 显示请求api,
     * show_api_params => '显示api请求参数',
     * show_map_input => '显示入参转换表'
     * show_map_output => '显示出参转换表'
     * show_api_response => '显示接口原生数据'
     */
    public function debug($item = '')
    {
        if (!$this->_debug)
        {
            pr([
                    'USAGE' => '----',
                    'show_api_url'      => '显示请求api',
                    'show_api_params'   => '显示api请求参数',
                    'show_map_input'   => '显示入参转换表',
                    'show_map_output'   => '显示出参转换表',
                    'show_api_response' => '显示接口原生数据',
                    
            ]);
            exit;
        }
        if (is_string($item) && $item != '')
        {
            pr($this->_debug_info[$item] ?? '');
        }
        elseif (is_array($item))
        {
            pr(array_intersect_key($this->_debug_info, array_flip($item)));
        }
        else
        {
            pr($this->_debug_info);
        }
    }
    
    /**
     *
     */
    public function __destruct()
    {
        $this->_api_cfg = null;
        $this->_debug_info = null;
    }
    
}