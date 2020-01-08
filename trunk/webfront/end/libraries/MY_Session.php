<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
require BASEPATH.'/libraries/Session/Session.php';

/**
 * Class MY_Session
 * @desc 此类提供三个方法操作session
 *       setData:保存session
 *       getData:获取session
 *       deleteData:删除session
 * @authoer liht
 * @since 2018-06-07
 */
class MY_Session extends CI_Session
{
    //前缀
    private $_sessionPrefix = 'PLAN_SESSION_';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 将数据保存到session
     * @param string $key
     * @param string|array $value
     * @return bool|void
     */
    public function setData($key='',$value='') {

        if(empty($key) || empty($value)) return false;

        /**
         * 转换数组为序列化字符串存储
         */
        if(is_array($value)) $value = serialize($value);
        return $this->set_userdata($this->_sessionPrefix . $key,$value);

    }

    /**
     * 获取session
     * @param string $key
     * @return bool|mixed
     */
    public function getData($key='') {

        if(empty($key)) return false;

        $data = $this->userdata($this->_sessionPrefix . $key);
        if(is_serialized($data)) return unserialize($data);
        return $data;

    }

    /**
     * 删除session
     * @param string $key
     * @return bool|void
     */
    public function deleteData($key='') {
        if(empty($key)) return false;
        return $this->unset_userdata($this->_sessionPrefix .$key);
    }

}
// END MY_Session Class

/* End of file MY_Session.php */
/* Location: ./libraries/MY_Session.php */