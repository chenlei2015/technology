<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Class Cookie
 * @desc 此类提供三个方法操作cookie
 *       setData:保存cookie
 *       getData:获取cookie
 *       deleteData:删除cookie
 * @authoer liht
 * @since 2018-06-07
 *
 */
class Cookie
{
    //前缀
    private $_cookiePrefix = 'PLAN_COOKIE_WEBFRONT_';

    public $data='';
    public $_expire = 31536000;

    public function __construct()
    {

    }

    /**
     * 将数据保存到cookie
     * @param string $key
     * @param string|array $value
     * @return bool|void
     */
    public function setData($key='',$value='') {

        if(empty($key)) return false;

        /**
         * 转换数组为序列化字符串存储
         */
        if(is_array($value)) $value = serialize($value);
        return setcookie($this->_cookiePrefix . $key,$value,time() + $this->_expire,'/');

    }

    /**
     * 获取cookie
     * @param string $key
     * @return bool|mixed
     */
    public function getData($key='') {

        if(empty($key)) return false;

        $data = isset($_COOKIE[$this->_cookiePrefix . $key]) ? $_COOKIE[$this->_cookiePrefix . $key] : '';

        if (get_magic_quotes_gpc()) {
            $data = stripslashes($data);
        }

        if(is_serialized($data)) {
            $data = unserialize($data);
        }

        return $data;

    }

    /**
     * 删除cookie
     * @param string $key
     * @return bool|void
     */
    public function deleteData($key='') {
        if(empty($key)) return false;
        return setcookie($this->_cookiePrefix . $key,'',time() - 1,'/');
    }

}
// END Cookie Class

/* End of file Cookie.php */
/* Location: ./libraries/Cookie.php */