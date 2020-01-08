<?php

/**
 * 响应http请求，返回json数据
 * Params:
 *      $data   Array   返回的数据
 *      $response_code int http请求状态码
 */
if (!function_exists('http_response')) {

    function http_response($data = array(), $response_code = 200)
    {
        if (isset($data['errorCode'])) {
            $ci              = &get_instance();
            $error_code_conf = $ci->config->item('error_code');
            if (!empty($error_code_conf) && isset($error_code_conf[$data['errorCode']])) {
                $data['errorMess'] = $error_code_conf[$data['errorCode']];
            }
        }

        while (ob_get_length() > 0) {
            ob_clean();
        }

        if (extension_loaded('zlib')) {
            if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
                while (ob_get_length() > 0) {
                    ob_clean();
                }
                ob_start('ob_gzhandler');
            }
        }

        if (function_exists('set_status_header')) {
            set_status_header($response_code);
        }

        header('Content-Type: application/json;charset=utf-8');

        $response = json_encode($data);

        header('Content-Length: '.strlen($response));

        echo $response;

        ob_get_length() && ob_end_flush();

        //exit;
    }

}

//调试打印
if (!function_exists('pr')) {
    function pr($arr, $escape_html = true, $bg_color = '#EEEEE0', $txt_color = '#000000')
    {
        echo sprintf('<pre style="background-color: %s; color: %s;">', $bg_color, $txt_color);
        $pr_location = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        echo sprintf('print from %s 第%d行 <br/>', $pr_location['file'], $pr_location['line']);
        if ($arr) {
            if ($escape_html) {
                echo htmlspecialchars(print_r($arr, true));
            } else {
                print_r($arr);
            }

        } else {
            var_dump($arr);
        }
        echo '</pre>';
    }
}

//写入日志
if (!function_exists('logger')) {

    function logger($logLever = 'debug', $logTitle = 'CTRL异常捕获', $msg = '')
    {
        //日志内容不为空
        if (empty($logTitle) && empty($msg)) return;
        if ($msg == '') {
            $logTitle = 'CTRL异常捕获:'.$logTitle;
            $msg = '错误信息：'.json_encode(error_get_last());
        }
        $logMsg = sprintf("#%d %s: %s\r\n", getmypid(), "-----$logTitle-----", $msg);
        log_message($logLever, $logMsg);

    }

}


if (!function_exists('get')) {

    function get($key = '', $urldecode = 0)
    {

        if (!empty($key)) {
            $result = isset($_GET[$key]) ? $_GET[$key] : '';
            $urldecode && $result = urldecode($result);
            return $result;
        } else {
            return $_GET;
        }

    }

}
if (!function_exists('post')) {

    function post($key = '', $urldecode = 0)
    {
//        if(isset($_REQUEST['debug'])) return get($key,$urldecode);
        if (!empty($key)) {
            $result = isset($_POST[$key]) ? $_POST[$key] : '';
            $urldecode && $result = urldecode($result);
            return $result;
        } else {
            return $_POST;
        }

    }

}

if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {

        $ip = false;

        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("^(10|172\.16|192\.168)\.^", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        if (is_cli())
        {
            return '127.0.0.1';
        }

        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);

    }
}

//构造接口请求参数，将数据转为字符串a=1&b=c&d=j
if (!function_exists('createLinkstring')) {

    function createLinkstring($para)
    {

        $arg = "";

        foreach ($para as $key => $val) {
            //if(empty($val)) continue;
            if ($val === '' || $val === null) continue;
            if (is_array($val)) $val = json_encode($val);
            $arg .= $key . "=" . urlencode($val) . "&";
        }

        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

}

if (!function_exists('is_serialized')) {
    function is_serialized($data)
    {
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}

if (!function_exists('getCurlData')) {
    function getCurlData($curl, $Data, $method = 'post', $header = '')
    {
        set_time_limit(3600);
        $ch = curl_init(); //初始化
        curl_setopt($ch, CURLOPT_URL, $curl); //设置访问的URL
        curl_setopt($ch, CURLOPT_HEADER, false); // false 设置不需要头信息 如果 true 连头部信息也输出

        if ($header) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); //只获取页面内容，但不输出
        if ($method == 'post') {
            curl_setopt($ch, CURLOPT_POST, true); //设置请求是POST方式
            curl_setopt($ch, CURLOPT_POSTFIELDS, $Data); //设置POST请求的数据
        }
        $datas = curl_exec($ch); //执行访问，返回结果
        curl_close($ch); //关闭curl，释放资源
        return $datas;
    }
}

if (!function_exists('group_by')) {
    /**
     * 对二维数组制定的列名进行分组
     *
     * @param array $arr
     * @param unknown $column
     * @return array
     */
    function group_by(array $arr, $column) : array
    {
        if (!$arr) return [];
        foreach ($arr as $key => $val)
        {
            $group_arr[$val[$column] ?? '_undefine'][] = $val;
        }
        return $group_arr;
    }
}

if (!function_exists('key_by')) {
    /**
     * 二维数组制定key
     *
     * @param array $arr
     * @param unknown $column array 多个column合并值
     * @return array
     */
    function key_by(array $arr, $column) : array
    {
        if (!$arr) return [];

        $key_arr = [];
        foreach ($arr as $key => $val)
        {
            if (is_array($column)) {
                $flip_column = [];
                foreach ($column as $col) {
                    $flip_column[$col] = $val[$col] ?? '_undefine';
                }
                $key = implode('', $flip_column);
            } else {
                $key = $val[$column] ?? '_undefine';
            }
            $key_arr[$key] = $val;
        }
        return $key_arr;
    }
}

if (!function_exists('array_find_key')) {
    /**
     * 二维数组中寻找第一个符合$column = $val 的key值
     *
     * @param array $arr
     * @param string $column $column == '' 的情况下， 不检测$column, 只检测$val是否存在
     * @param mixed $val
     * @param bool $find_all 是否找出所有
     * @return array
     */
    function array_find_key(array $arr, $column, $val, $find_all = false)
    {
        $key = [];

        foreach ($arr as $k => $row)
        {
            if ($column == '')
            {
                if (!in_array($val, $row)) continue;
                if ($find_all)
                {
                    $key[] = $k;
                }
                else
                {
                    return $k;
                }
            }
            else
            {
                if (!isset($row[$column])) continue;
                if ($row[$column] != $val) continue;
                if ($find_all)
                {
                    $key[] = $k;
                }
                else
                {
                    return $k;
                }
            }
        }
        return $find_all ? $key : '';
    }
}

if (!function_exists('array_delete_col')) {
    /**
     * 删除某个col
     *
     * @param array $arr
     * @param string $column
     */
    function array_delete_col(array &$arr, $column)
    {
        foreach ($arr as $k => &$row)
        {
            unset($row[$column]);
        }
    }
}

if (!function_exists('array_combine_two_col')) {
    /**
     * 二维数组中以$key_col值做key， $val_col为val重新生成一个数组，
     * 如果key值相同，则value转换为数组，否则为原来的值
     *
     * @param array $arr
     * @param string $column
     */
    function array_combine_two_col(array $arr, $key_col, $val_col)
    {
        if (empty($arr)) return [];

        $new_arr = [];
        foreach ($arr as $k => $row)
        {
            if (!isset($row[$key_col])) throw new \RuntimeException(sprintf('array_combine_two_col key %s 不存在', $key_col), 500);
            if (isset($new_arr[$row[$key_col]]))
            {
                if (!is_array($new_arr[$row[$key_col]]))
                {
                    $new_arr[$row[$key_col]][0] = $new_arr[$row[$key_col]];
                }
                else
                {
                    $new_arr[$row[$key_col]][] = $row[$val_col] ?? NULL;
                }
            }
            else
            {
                $new_arr[$row[$key_col]] = $row[$val_col];
            }
        }
        return $new_arr;
    }
}


if (!function_exists('array_collapse')) {
    /**
     * 目标数组有多个元素，每个元素的value值相同，将值合并到一个数组中去重处理
     * [0] => ['a', 'b']
     * [1] => ['c', 'd']
     * ->
     * ['a', 'b', 'c', 'd']
     * @param array $arr
     * @param string $column
     */
    function array_collapse(array $arr)
    {
        if (empty($arr)) return [];
        $collapse = [];
        foreach ($arr as $row)
        {
            $collapse = array_merge($collapse, $row);
        }
        return array_unique($collapse);
    }
}


if (!function_exists('array_where_in')) {
    /**
     * 将数组的val加上''， 以用于where in ()字句
     *
     * @param array $arr
     * @param string $column
     */
    function array_where_in(array $arr, $key_col = '')
    {
        if (empty($arr)) return '';

        if ($key_col == '')
        {
            foreach ($arr as $val)
            {
                $tmp[] = "'".$val."'";
            }
        }
        else
        {
            foreach ($arr as $k => $val)
            {
                if (isset($val[$key_col]))
                {
                    $tmp[] = "'".$val[$key_col]."'";
                }

            }
        }

        return implode(',', $tmp);
    }
}

if (!function_exists('array_same_value')) {
    /**
     * 指定的key是否相同
     *
     * @param array $arr
     * @param string $column
     */
    function array_same_value(array $arr, $key_col = '', $equal_value = null)
    {
        if (empty($arr)) return false;

        $init_val = '';
        $check_arr = $key_col == '' ? $arr : array_column($arr, $key_col);
        foreach ($check_arr as $key => $val)
        {
            if (is_object($val)) {
                $value = spl_object_hash($val);
            } elseif (is_array($val)) {
                $value = md5(json_encode($val));
            }
            if ($key == 0) {
                $init_val = $value;
                if ($equal_value && $init_val != $equal_value) {
                    return false;
                }
            } else {
                if ($init_val != $val) {
                    return false;
                } elseif ($equal_value && $init_val != $equal_value) {
                    return false;
                }
            }
        }
        return true;
    }
}

if (!function_exists('db_float_upgrade')) {
    /**
     * 页面实际浮点数字*1000统一入口, 丢弃超过3位的小数点
     * 数据库最大存储 18446744073709551615
     * 能接受最大原生值 18446744073709551
     *
     * 所有的运算需要基于整数运算，这样不会出现精度丢失。
     *
     * @param array $arr
     * @param unknown $column
     * @return array
     */
    function db_float_upgrade($original_number)
    {
        return explode('.', $original_number * 1000)[0];
    }
}

if (!function_exists('db_float_downgrade')) {
    /**
     * 数据库*1000的数字/1000返回实际浮点数
     *
     * @param array $arr
     * @param unknown $column
     * @return array
     */
    function db_float_downgrade($original_number)
    {
        return $original_number / 1000;
    }
}

if (!function_exists('is_login')) {
    /**
     * 检测用户是否登录， api模拟登录也会检测
     *
     * @param array $arr
     * @param unknown $column
     * @return array
     */
    function is_login() : bool
    {
        $_ci = CI::$APP;
        $_ci->load->service('UserService');

        if ($_ci->userservice::is_login())
        {
            return true;
        }
        else
        {
            /**
             * 本全局变量是检测是否api登录的唯一标记
             * @var bool
             */
            global $g_api_login;

            if ($g_api_login)
            {
                return true;
            }

        }
        return false;
    }
}

if (!function_exists('is_system_call')) {
    /**
     * 检测本次请求是否是系统之间的调用
     *
     * @param array $arr
     * @return array
     */
    function is_system_call() : bool
    {
        global $g_system_login;
        return is_null($g_system_login) ? false : true;
    }
}

if (!function_exists('api_login')) {
    /**
     * 模拟api用户登录
     * api请求指定了用户身份，本次请求的资源必须限制在该用户之下。
     * 这里实施模拟登录，并不会注册session和cookie， 如果注册session因为服务器的session有生命
     * 周期，会导致大量无效的session
     *
     * @param array $arr
     * @return array
     */
    function api_login($user_openid) : bool
    {
        //@todo 是否是系统之间合法的请求
        if (!is_system_call())
        {
            return false;
        }
        //read user data, and set global user info
        global $g_user_info;
        //@todo
        $g_user_info = [];

        //mark api_login if success
        global $g_api_login;
        $g_api_login = true;

        return true;
    }
}

if (!function_exists('is_api_user')) {
    /**
     * api请求指定了用户身份，本次请求的资源必须限制在该用户之下。
     * 这里实施模拟登录，并不会注册session和cookie
     *
     * @param array $arr
     * @return array
     */
    function is_api_user() : bool
    {
        global $g_api_login;
        return is_null($g_api_login) ? false : true;
    }
}

if (!function_exists('get_active_user')) {

    /**
     * 附加登录用户数据的数组， 因为目前登录模块未定，暂不知道key，因多处使用创建者信息，
     * 这里给一个兼容函数统一处理，避免以后代码中大量修改
     *
     * @param unknown $params host params
     * @param unknown $key_map sess_data_key => $new_key
     */
    function get_active_user($refresh = false)
    {
        $_ci = CI::$APP;
        $_ci->load->service('UserService');
        $user = $_ci->userservice->getActiveUser($refresh);
        return $user;
    }
}

if (!function_exists('get_curl')) {

    /**
     * 附加登录用户数据的数组， 因为目前登录模块未定，暂不知道key，因多处使用创建者信息，
     * 这里给一个兼容函数统一处理，避免以后代码中大量修改
     *
     * @param unknown $params host params
     * @param unknown $key_map sess_data_key => $new_key
     */
    function get_curl($params = NULL)
    {
        $curl =& load_instance_class('CurlRequest', 'third_party');
        return $curl;
    }
}

if (!function_exists('get_upload_path')) {

    /**
     * 获取上传统一路径，避免因为upload文件夹修改而修改
     */
    function get_upload_path($file = NULL)
    {
        return $file ? APPPATH . 'upload'.DIRECTORY_SEPARATOR.$file : APPPATH . 'upload'.DIRECTORY_SEPARATOR ;
    }
}

if (!function_exists('get_export_path')) {

    /**
     * 获取上传统一路径，避免因为upload文件夹修改而修改
     */
    function get_export_path()
    {
        return rtrim(EXPORT_FILE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR ;
    }
}

if(!function_exists('get_upload_url'))
{
    /**
     * 统一返回一个upload文件的uri
     *
     * @param unknown $file_path
     * @return string
     */
    function get_upload_url($file_path)
    {
        $ci = CI::$APP;
        $ci->load->helper('url_helper');
        return trim(site_url(), '/').'/'.$file_path;
    }
}

if (!function_exists('db_moneny_format')) {

    /**
     * global money show, db save: actual * 1000
     *
     * @param int $number
     * @return string
     */
    function db_moneny_format($number)
    {
        return number_format($number / 1000, $decimals = 3, $dec_point = '.', $thousands_sep = '');
    }
}

if (!function_exists('db_percent_format')) {

    /**
     * global date show, db save: unixtime
     *
     * @param int $number
     * @return string
     */
    function db_percent_format($number, $char = '%')
    {
        return number_format($number / 1000, $decimals = 2, $dec_point = '.', $thousands_sep = '').$char;
    }
}

if (!function_exists('unix_date_format')) {

    /**
     * global date show, db save: unixtime
     *
     * @param int $number
     * @return string
     */
    function unix_date_format($unix_time)
    {
        return date('Y-m-d H:i:s', intval($unix_time));
    }
}

if (!function_exists('tran_url_decode')) {

    /**
     * global date show, db save: unixtime
     *
     * @param int $number
     * @return string
     */
    function tran_url_decode($str)
    {
        return urldecode($str);
    }
}

if (!function_exists('tran_sku_sn')) {
    /**
     * 是否是可搜索的预测单号. 支持,分割的单号
     *
     * @param unknown $search_sn
     */
    function tran_sku_sn($search_sn)
    {
        $arr = array_filter(explode(',', str_replace(array('%7C', '%20', ',', ' '), ',', $search_sn)));
        return implode(',', $arr);
    }
}

if (!function_exists('tran_split_search')) {
    /**
     * 将不同的分割符号统一替换成,
     *
     * @param unknown $search_sn
     */
    function tran_split_search($search_sn)
    {
        $arr = array_filter(explode(',', str_replace(array('%7C', '%20', ',', ' '), ',', $search_sn)));
        return implode(',', $arr);
    }
}

if (!function_exists('tran_pr_sn')) {
    /**
     * 是否是需求单号. 支持,分割的单号
     *
     * @param unknown $search_sn
     */
    function tran_pr_sn($search_sn)
    {
        $arr = array_filter(explode(',', str_replace(array('%7C', '%20', ',', ' '), ',', $search_sn)));
        return implode(',', $arr);
    }
}

if (!function_exists('is_valid_unsigned_int')) {
    /**
     * 一个有效的数字
     * @param unknown $search_num
     * @return boolean
     */
    function is_valid_unsigned_int($search_num)
    {
        return $search_num > 0 && $search_num < PHP_INT_MAX;
    }
}



if (!function_exists('is_valid_uid')) {
    /**
     *
     */
    function is_valid_uid()
    {
        //@todo
        return true;
    }
}

if (!function_exists('is_valid_user_name')) {
    /**
     *
     */
    function is_valid_user_name()
    {
        //@todo
        return true;
    }
}

if (!function_exists('is_valid_date')) {
    /**
     * valid search date
     *
     * @param unknown $search_date_str
     * @return boolean
     */
    function is_valid_date($search_date_str)
    {
        return (bool)strtotime($search_date_str);
    }
}

if (!function_exists('is_valid_skusn')) {
    /**
     *
     * @param unknown $search_sku_sn
     * @return boolean
     */
    function is_valid_skusn($search_sku_sn)
    {
        //@todo
        return true;
    }
}

if (!function_exists('is_valid_page')) {
    /**
     * valid page
     *
     * @param unknown $search_page
     * @return boolean
     */
    function is_valid_page($search_page)
    {
        return $search_page >= 0 && $search_page < (PHP_INT_MAX / 1000);
    }
}

if (!function_exists('is_valid_pagesize')) {
    /**
     * max page 1000
     *
     * @param unknown $search_page_size
     * @return boolean
     */
    function is_valid_pagesize($search_page_size)
    {
        return $search_page_size >= 0 && $search_page_size <= 2000;
    }
}

if (!function_exists('is_valid_approval_state')) {
    /**
     * 是否有效的fba审核状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_approval_state($state) : bool
    {
        return isset(APPROVAL_STATE[$state]);
    }
}

if (!function_exists('is_valid_pur_sn_state')) {
    /**
     * 是否有效的备货单状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_pur_sn_state($state) : bool
    {
        return isset(PUR_STATE[$state]);
    }
}

if (!function_exists('is_valid_trigger_pr')) {
    /**
     * 是否有效的是否触发pr
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_trigger_pr($state) : bool
    {
        return isset(TRIGGER_PR[$state]);
    }
}

if (!function_exists('is_valid_first_sale')) {
    /**
     * 是否有效的是否触发pr
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_first_sale($state) : bool
    {
        return isset(FBA_FIRST_SALE_STATE[$state]);
    }
}

if (!function_exists('is_valid_account_status')) {
    /**
     * 是否有效的是否触发pr
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_account_status($state) : bool
    {
        return isset(FBA_ACCOUNT_STATUS[$state]);
    }
}

if (!function_exists('is_valid_plan_approval')) {
    /**
     * 是否有效的是否需要计划审核
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_plan_approval($state) : bool
    {
        return isset(NEED_PLAN_APPROVAL[$state]);
    }
}

if (!function_exists('is_valid_expired')) {
    /**
     * 是否有效的是否过期
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_expired($state) : bool
    {
        return isset(FBA_PR_EXPIRED[$state]);
    }
}

if (!function_exists('is_valid_listing_state')) {
    /**
     * 是否有效的listing状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_listing_state($state) : bool
    {
        return isset(LISTING_STATE[$state]);
    }
}

if (!function_exists('is_valid_sku_state')) {
    /**
     * 是否有效的sku状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_sku_state($state) : bool
    {
        return isset(SKU_STATE[$state]);
    }
}


if (!function_exists('is_valid_bussiness_line')) {
    /**
     * 是否有效的业务线
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_bussiness_line($state) : bool
    {
        return isset(BUSSINESS_LINE[$state]);
    }
}

/**
 * post_json数据用于调用java接口
 * @param $url
 * @param $jsonStr
 * @return mixed
 * 2019/1/28
 * @author wangrui
 */
if(!function_exists('http_post_json'))
{
    function http_post_json($url, $jsonStr)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }
}

/**
 * 判断字符串是否是json字符串
 * @param $string
 * @return bool
 * 2019/1/28
 * @author wangrui
 */
if(!function_exists('is_json'))
{
    function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

/**
 * 获取请求头部信息
 */
if (!function_exists('getallheaders'))
{
    function getallheaders()
    {
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $_SERVER;
    }
}

if (!function_exists('append_login_info')) {

    /**
     * 附加登录用户数据的数组， 因为目前登录模块未定，暂不知道key，因多处使用创建者信息，
     * 这里给一个兼容函数统一处理，避免以后代码中大量修改
     * v1.1.0 增加系统自动处理后
     *
     * @param unknown $params host params
     * @param unknown $key_map sess_data_key => $new_key
     */
    function append_login_info(&$params, $key_map = [])
    {
        if (empty($key_map) && !is_system_call())
        {
            $key_map = [
                    //session => db_col
                    'userNumber' => 'uid',
                    'userName' => 'user_name',
            ];
        }
        $_ci = CI::$APP;
        $_ci->load->service('UserService');
        $active_user = $_ci->userservice->getActiveUser();
        $user = is_system_call() ? $active_user->get_user_info() : $active_user->get_user_info()['oa_info'];

        foreach ($key_map as $sess_key => $new_key)
        {
            if (isset($user[$sess_key]))
            {
                $params[$new_key] = $user[$sess_key];
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('append login info, session user data not exists key: %s', $sess_key), 3001);
            }
        }

    }
}

if (!function_exists('generate_global_id')) {
    /**
     * $tableInId 数据表编号
     */
    function gen_id($tableInId){
        usleep(1);
        list($mic, $sec) = explode(" ", microtime());
        $micro = ($sec*1000000+intval(round($mic*1000000)));
        return sprintf("%s%s%s%s", dechex($micro),dechex(getmypid()),rand(100000,999999),$tableInId);
    }
}

if (!function_exists('build_java_api_url')) {
    /**
     * 统一生产java的请求API url, 配置文件 java_api
     *
     * @param unknown $module 模块名
     * @param string $api_code 自定义索引api code
     * @return string
     */
    function build_java_api_url($module, $api_code)
    {
        $ci = CI::$APP;
        $ci->load->config(JAVA_API_CFG_FILE);
        $api = $ci->config->item(strtolower($module))[$api_code] ?? '';
        return JAVA_API_URL.'/'.$api;
    }
}

if (!function_exists('RPC_CALL')) {
    /**
     * java远程调用
     *
     * @param unknown $api_code
     * @return string
     */
    function RPC_CALL($api_code, $input_params = [], callable $after_cb = null,  $ctrl_params = [])
    {
        $ci = CI::$APP;
        $ci->load->service('basic/JavaApiExchangeService', null, 'rpc');
        return $ci->rpc->options($ctrl_params)->set($api_code)->after($after_cb)->run($input_params);
    }
}

if (!function_exists('TMS_RPC_CALL')) {
    /**
     * java远程调用
     *
     * @param unknown $api_code
     * @return string
     */
    function TMS_RPC_CALL($api_code, $input_params = [], callable $after_cb = null,  $ctrl_params = [])
    {
        $ci = CI::$APP;
        $ci->load->service('basic/JavaTMSApiExchangeService', null, 'rpc');
        return $ci->rpc->options($ctrl_params)->set($api_code)->after($after_cb)->run($input_params);
    }
}

if (!function_exists('cloud_service_erp_api_url')) {
    /**
     * 分布式系统新版erp的请求API url
     * @param unknown $api
     * @return string
     */
    function cloud_service_erp_api_url($api)
    {
        return CLOUD_SERVICE_ERP_API_URL.'/'.$api;
    }
}


if (!function_exists('params_ids_to_array')) {
    /**
     * 分
     * @param unknown $api
     * @return string
     */
    function params_ids_to_array($ids='',$isNum=true)
    {
        $idsArr = [];
        if (!empty($ids) && is_string($ids)){
            $ids = explode(',',$ids);
            foreach ($ids as $key=>$value) {
                $value = trim($value);
                if ($isNum){
                    if (preg_match("/^[1-9][0-9]*$/", $value)){
                        $idsArr[] = intval($value);
                    }
                }else{
                    $idsArr[] = $value;
                }

            }
        }
        return $idsArr;
    }
}

if (!function_exists('time_empty')) {
    /**
     * 判断时间是否为空
     *
     * @param string $time
     * @return bool
     */
    function time_empty($time='')
    {
        if (is_numeric($time)){
            $time = intval($time);
        }else{
            $time = str_replace(['-',':',' '],'',$time);
            $time = intval($time);
        }
        return (empty($time) || $time=='0')?true:false;
    }
}

if (!function_exists('data_format_filter')) {
    /**
     * 过滤掉不符合规则的时间，显示空字符串
     *
     * @param array $row
     * @param array $fields
     */
    function data_format_filter(&$row,$fields = ['created_at','updated_at','approved_at','approve_at','check_attr_time'])
    {
        static  $data_format_str = 'Y-m-d H:i:s';
        if (is_array($row)){
            if (!empty($row) && !empty($fields)){
                foreach ($fields as $field){
                    if (isset($row[$field])){
                        if (!time_empty($row[$field])){
                            $row[$field] = is_numeric($row[$field]) ? date($data_format_str, intval($row[$field])) : $row[$field];
                        }else{
                            $row[$field] = '';
                        }
                    }
                }
            }
        }elseif (is_string($row)){
            if (!time_empty($row)){
                $row = is_numeric($row) ? date($data_format_str, intval($row)) : $row;
            }else{
                $row = '';
            }
            return $row;
        }
    }
}

if (!function_exists('tran_time_result')) {

    /**
     * 转换list的数据格式
     */
    function tran_time_result(&$data_list,$fields = ['created_at','updated_at','approved_at','approve_at','check_attr_time'])
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row,$fields);
        }
    }
}

if (!function_exists('is_valid_po_state')) {
    /**
     * 是否有效的备货单状态
     *
     * @param unknown $state
     * @return bool
     */
    function is_valid_po_state($state) : bool
    {
        return isset(PURCHASE_ORDER_STATUS[$state]);
    }
}

if (!function_exists('gen_unique_str')) {
    /**
     * 将数组生成一个特殊的字符串
     *
     * @param unknown $state
     * @return bool
     */
    function gen_unique_str($arr)
    {
        sort($arr);
        return md5(serialize($arr));
    }
}

if (!function_exists('trimArray')) {
    /**
     * 列表页查询参数处理,多个逗号隔开,去除空格
     * @param $str
     *
     * @return array
     */
    function trimArray($str)
    {

        $arr = explode(',', str_replace('，', ',', $str));
        $arr = array_filter($arr);
        foreach ($arr as &$value) {
            $value = trim($value);
        }

        return $arr;
    }
}

if (!function_exists('is_cache_sleep')) {
    /**
     * 缓存组件关闭时间段
     *
     * @return boolean
     */
    function is_cache_sleep()
    {
        if (!defined('DISABLE_CACHE_TIME'))
        {
            return false;
        }
        $items = explode('~', DISABLE_CACHE_TIME);
        $now = date('H:i');
        if ($items[0] > $items[1])
        {
            return $now >= '00:00' && $now <= $items[1] || $now >= $items[0] && $now <= '23:59';
        }
        else
        {
            return $now >= $items[0] && $now <= $items[1];
        }
    }
}


if (!function_exists('get_called_module')) {

    function get_called_module()
    {
        $urls = explode('/', strtolower(get_instance()->uri->uri_string()));
        if (count($urls) != 3)
        {
            $urls = array_pad($urls, 3, 'index');
        }
        [$module, $ctrl, $method] = $urls;
        return [$module, $ctrl, $method];
    }
}

if (!function_exists('mysqli_dynamic_statistics')) {
    /**
     * sql统计信息
     *
     * @param unknown $module
     * @param unknown $sql
     */
    function mysqli_dynamic_statistics($statistics_strategy, $module, $sql, $data, $params, $hit = false)
    {
        if (is_cache_sleep()) return false;

        $ci = CI::$APP;
        !$hit && $ci->load->library('QueryStatistics', null, 'QueryStatistics');

        //'hash', 'sql', 'module', 'method', 'counter'
        $op_code = strtoupper(trim(substr(ltrim($sql), 0, strpos($sql, ' '))));
        if (!$op_code)
        {
            log_message('ERROR', sprintf('mysqli_dynamic_statistics：执行sql: 动作： %s， sql语句：%s, qb_from: %s', $op_code, $sql, implode(',', $params['from'])));
            return true;
        }

        //采用分析sql
        $table = [];
        $offset = 0;
        while ($table_start_pos = strpos($sql, $params['prefix'], $offset))
        {
            $start_flag = substr($sql, $table_start_pos-1, 1);
            $table_end_pos = strpos($sql, $start_flag, $table_start_pos);
            if (false === $table_end_pos) {
                $table_end_pos = strlen($sql);
            }
            $name = substr($sql, $table_start_pos, $table_end_pos - $table_start_pos);
            $table[] = $start_flag != ' ' ? $name : explode('.', $name)[0];
            $offset =  $table_end_pos;
        }
        $params['from'] = array_unique($table);

        //log_message('DEBUG', sprintf('mysqli_dynamic_statistics log: %s', json_encode(func_get_args())));

        switch ($op_code)
        {
            case 'SELECT':
                $one_info = [
                    'hash' => $ci->QueryStatistics->get_sql_md5($sql),
                    'sql' => $sql,
                    'module' => $module[0],
                    'ctrl' => $module[1],
                    'method' => $module[2],
                    'counter' => 1,
                    'updated' => microtime(true),
                    'tables' => implode(',', $params['from']),
                    'db' => $params['db'],
                ];
                $one_info = array_merge($one_info, $params);
                $one_info['tables'] = implode(',', $params['from']);
                unset($one_info['from']);

                if ($hit)
                {
                    $one_info['hit'] = 1;
                }
                if (!$hit)
                {
                    $one_info['data'] = $data;
                }

                $ci->QueryStatistics->set_statistics_strategy($statistics_strategy);
                $ci->QueryStatistics->accept_one_query($one_info);

                //log_message('DEBUG', sprintf('SELECT SQL hash: %s 动作： %s， sql语句：%s, qb_from: %s', $one_info['hash'], $op_code, str_replace(["\r\n", "\n"], ' ', $sql), implode(',', $params['from'])));
                break;
            case 'UPDATE':
            case 'DELETE':
            case 'INSERT':
            case 'ALTER':
            case 'REPLACE':
            case 'TRUNCATE':
                //使得缓存失效, 哪些sql_hash关联table， 使得sql_hash对应的模块失效。
                $result = $ci->QueryStatistics->expired_tables($params['from']);
                log_message('DEBUG', sprintf('DML SQl:%s 设置表缓存失效：%s, 设置结果：%s', $sql, json_encode($params['from']), is_string($result) ? $result : 'false'));
                break;
            default:

                break;
        }

        return true;
    }
}

if (!function_exists('mysqli_statistics_cache'))
{
    /**
     *
     * @param array $statistics_strategy
     * @param array $module
     * @param string $sql
     * @param array $from
     * @return unknown|boolean
     */
    function mysqli_statistics_cache($statistics_strategy, $module, $sql, $params)
    {
        if (is_cache_sleep()) return false;
        if (!isset($statistics_strategy['strategy'])) return false;

        $ci = CI::$APP;
        $ci->load->library('QueryStatistics', null, 'QueryStatistics');

        //查询sql对应的key值是否注册在group组里面，Y继续
        /**
         * @var QueryStatistics QueryStatistics
         */
        $cache_key = $ci->QueryStatistics->get_hash_name(['hash' => $ci->QueryStatistics->get_sql_md5($sql)]);
        $group_key = $ci->QueryStatistics->get_module_zset_name($ci->QueryStatistics->get_prefix(), $module[0]);

        //策略检测
        $from_cache = $ci->QueryStatistics->is_need_cache($statistics_strategy, $cache_key, $group_key);

        //取值
        $from_cache = $from_cache && $ci->QueryStatistics->existed_sql_hash_in_module($cache_key, $group_key);
        $from_cache = $from_cache && (null !== ($data = $ci->QueryStatistics->get_hash_infos($cache_key)));
        $from_cache = $from_cache && isset($data['data']) && !(is_string($data['data'] && $data['data'] == 'NULL'));

        if ($from_cache)
        {
            //对data进行转换处理
            $cache_data = $ci->QueryStatistics->decode($data['data']);
            if (!$cache_data || (is_string($cache_data) && $cache_data == 'NULL'))
            {
                return false;
            }
            //如果是统计查询
            if (isset($params['orderBy']))
            {
                $params['numrows'] = $cache_data;
            }

            //进行hit命中统计
            try
            {
                log_message('INFO', sprintf('--> sql %s 缓存命中。 hash_key: %s, 开始更新统计信息和updated', str_replace(["\r\n", "\n"], " ", $sql), $cache_key));
                mysqli_dynamic_statistics($statistics_strategy, $module, $sql, $data = [], $params, $hit = true);
            }
            catch (\Throwable $e)
            {
                log_message('ERROR', '缓存统计：执行统计更新时跑出异常：%s', $e->getMessage());
            }

            return $cache_data;
        }
        else
        {
            return false;
        }
    }
}

if (!function_exists('mysqli_limit_cache')) {

    /**
     * @param DB_query_build $query
     * @param string $limit_hash
     */
    function mysqli_limit_cache($query, $limit_infos)
    {
        if (is_cache_sleep()) return ;

        $ci = CI::$APP;
        $ci->load->library('rediss');

        $start = LIMIT_SAMPLING_ROW;
        $end = floor($limit_infos['total'] / $start);

        if ($limit_infos['offset'] % $start == 0)
        {
            $start = $limit_infos['offset'];
        } elseif ($limit_infos['offset'] >= $start) {
            $start = min(floor($limit_infos['offset'] / LIMIT_SAMPLING_ROW), $end) * LIMIT_SAMPLING_ROW;
        } else {
            $start = 0;
        }
        //pr($limit_infos);
        $primary_position = $ci->rediss->command('hget '.$limit_infos['limitHash'].' '.$start);
        if (!$primary_position) {
            log_message('INFO', sprintf('limit %s cache lost, info: ', json_encode($limit_infos)));
            return;
        }
        $primary_position = unserialize($primary_position);
        $direction_op = $limit_infos['direction'] == 'desc' ? ' <=' : ' >=';
        $query->where($limit_infos['primary'].$direction_op, $primary_position)->limit($limit_infos['limit'], $limit_infos['offset'] - $start);

        return;
    }
}


if (!function_exists('notify_backserver_event')) {

    /**
     * socket连接
     *
     * @param string $api /login
     * @param array $params
     */
    function notify_backserver_event($api, $params)
    {
        $errno = 0;
        $errstr = '';

        if (is_cache_sleep()) return ;

        if (!defined('ENABLE_BACKSERVER_HOST') || !ENABLE_BACKSERVER_HOST)
        {
            return;
        }

        $fp = fsockopen(BACKSERVER_HOST, BACKSERVER_PORT, $errno, $errstr, $timeout = 3);
        if (!$fp)
        {
            log_message('ERROR', '连接后端服务器失败，失败错误码：' . $errno . '失败原因：' . $errstr);
        }
        else
        {
            //忽略中断退出
            ignore_user_abort(true);
            stream_set_blocking($fp, 0);

            $api_query = $api . '?' . http_build_query($params);

            $out = sprintf("GET %s HTTP/1.1\r\n", $api_query);
            $out .= sprintf("Host: %s:%d\r\n", BACKSERVER_HOST, BACKSERVER_PORT);
            $out .= "Connection: Close\r\n\r\n";

            fwrite($fp, $out);
            # 忽略客户端中断
            //nginx
            //fastcgi_ignore_client_abort on;
            //php
            //ignore_user_abort
            usleep(20000);
            fclose($fp);

            log_message('DEBUG', sprintf('---->发送%s事件完成', $api_query));
        }
    }
}


if (!function_exists('tran_logistics_code')) {
    function tran_logistics_code($logistics_id)
    {
        return LOGISTICS_ATTR[$logistics_id]['code'] ?? '';
    }
}

if (!function_exists('tran_warehouse_code')) {
    function tran_warehouse_code($warehouse_id)
    {
        static $warehouse_map_code;
        if (!$warehouse_map_code)
        {
            $warehouse_map_code = array_flip(WAREHOUSE_CODE);
        }
        return $warehouse_map_code[$warehouse_id] ?? '';
    }
}

if (!function_exists('tran_fba_country_code')) {

    function tran_fba_country_code($country_name)
    {
        static $country_name_map_code;

        if (!$country_name_map_code)
        {
            foreach (FBA_STATION_CODE as $code => $cfg)
            {
                if (isset($cfg['is_eu']) && $cfg['is_eu'])
                {
                    $country_name_map_code[$cfg['name']] = $code;
                }
            }
        }
        return $country_name_map_code[$country_name] ?? '';
    }
}

if (!function_exists('tran_oversea_country_code')) {

    function tran_oversea_country_code($country_name)
    {
        static $country_name_map_code;

        if (!$country_name_map_code)
        {
            foreach (OVERSEA_STATION_CODE as $code => $cfg)
            {
                $country_name_map_code[$cfg['name']] = $code;
            }
        }
        return $country_name_map_code[$country_name] ?? '';
    }
}

