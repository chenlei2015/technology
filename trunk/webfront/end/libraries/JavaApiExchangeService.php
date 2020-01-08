<?php 

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
class JavaApiExchangeService
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
    private $_oauth_token_key = 'JAVA_OAUTH_TOKEN';
    
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
     * {
     *   "access_token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJyZWFkIl0sImV4cCI6MTU1MzI1NTY2NiwiYXV0aG9yaXRpZXMiOlsiMTAiXSwianRpIjoiNTcxNjk5Y2QtYTZkNi00Njg1LTk2MTQtMWFlY2VkMzNlZGRiIiwiY2xpZW50X2lkIjoic2VydmljZSJ9.2FMoQEZaU3BA0Sp7OXjeTIG0QwD7VcU1JWZqMSBWa8o",
     *   "token_type": "bearer",
     *   "expires_in": 899,
     *   "scope": "read",
     *   "jti": "571699cd-a6d6-4685-9614-1aeced33eddb"
     *  }
     * 
     * @throws \RuntimeException
     */
    private function _get_access_token()
    {
        //获取token
        $auth_opts = [
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => sprintf('%s:%s', JAVA_SECRET_USER, JAVA_SECRET_PASS)
        ];
        
        try {
            $tokens = $this->http_post(JAVA_SECRET_PATH, [], [], $auth_opts);
            $token_info = json_decode($tokens, true);
            if (!$token_info)
            {
                log_message('ERROR', sprintf('OAUTH JAVA SECRET TOKEN FAIL, 返回消息无法解析', $tokens));
                throw new \RuntimeException('接口授权认证失败', 500);
            }
            $this->_ci->rediss->setData($this->_oauth_token_key, $token_info, $token_info['expires_in'] - 45);
            $this->_token = $token_info['access_token'];
            return $this->_token;
        }
        catch(\Exception $e)
        {
            log_message('ERROR', sprintf('OAUTH JAVA SECRET TOKEN FAIL, curl error %s', $e->getMessage()));
            throw new \RuntimeException('接口授权认证失败', 500);
        }
    }
    
    /**
     * 获取token
     * 
     * @return mixed|unknown
     */
    protected function get_token()
    {
        $token = $this->_ci->rediss->getData($this->_oauth_token_key);
        if (!$token) 
        {
            return $this->_get_access_token();
        }
        if ($this->_ci->rediss->getTTL($this->_oauth_token_key) < 30)
        {
            return $this->_get_access_token();
        }
        $this->_token = $token['access_token'];
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
        $api_code_cfg = $this->_current_api_cfg;
        $url = $this->build_api($api_code_cfg); 
        $method = $api_code_cfg['method'];
        if (strtolower($method) == 'post')
        {
            $rsp = $this->http_post($url, $params, $header);
            $list = json_decode($rsp, true);
            if (!$list)
            {
                log_message('ERROR', sprintf('RPC请求远程接口：%s, 方法：%s, 参数： %s, 返回无效数据：%s', $url, $method, json_encode($params), $rsp));
                return [];
            }
        }
        else
        {
            $rsp = $this->http_get($url, $params, $header);
            $list = json_decode($rsp, true);
            if (!$list)
            {
                log_message('ERROR', sprintf('RPC请求远程接口：%s, 方法：%s, 参数： %s, 返回无效数据：%s', $url, $method, json_encode($params), $rsp));
                return [];
            }
        }
        log_message('ERROR', sprintf('RPC-DEBUG: RPC请求远程接口：%s, 方法：%s, 参数： %s, 返回数据：%s', $url, $method, json_encode($params), $rsp));
        $this->_debug and ($this->_debug_info['show_api_response'] = $rsp);
        return $list;
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
        if (ENABLE_JAVA_API_TOKEN)
        {
            //附加token
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
    
    private function http_post($url, $data=null, $headers = array(), $curl_opts = []){
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($curl_opts))
        {
            curl_setopt_array($ch, $curl_opts);
        }
        if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        
        if(strlen($url) > 5 && strtolower(substr($url,0,5)) == "https" ) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        
        $reponse = curl_exec($ch);
        if (curl_errno($ch))
            throw new Exception(curl_error($ch),0);
            else{
                $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                
                if (200 !== $httpStatusCode)
                    throw new Exception($reponse,$httpStatusCode);
            }
            
            curl_close($ch);
            
            return $reponse;
    }
    
    private function http_get($url, $data=null, $headers = array(), $curl_opts = [])
    {
        if (! empty($data))
        {
            $url = trim($url, '/') . '?' . http_build_query($data);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (!empty($curl_opts))
        {
            curl_setopt_array($ch, $curl_opts);
        }
        if (! empty($headers))
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            
            if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https")
            {
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            }
            curl_setopt($ch, CURLOPT_POST, false);
            
            $reponse = curl_exec($ch);
            if (curl_errno($ch))
            {
                throw new Exception(curl_error($ch), 0);
            }
            else
            {
                $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if (200 !== $httpStatusCode)
                {
                    throw new Exception($reponse, $httpStatusCode);
                }
            }
            
            curl_close($ch);
            
            return $reponse;
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