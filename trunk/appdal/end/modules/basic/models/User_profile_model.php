<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/2
 * Time: 16:40
 */

class User_profile_model extends MY_Model
{
    public function __construct()
    {
        $this->database = 'stock';
        $this->table = 'yibai_user_profile';
        parent::__construct();
    }

    public function all()
    {
        $query = $this->_db->get($this->table);
        $result = $query->result_array();
        return $result;
    }

    /**
     * 根据staff_code获取
     * @param $staff_code
     * @return mixed
     */
    public function get($params)
    {
        return $this->_db->from($this->table)
            ->where('staff_code', $params['staff_code'])->where('collection',$params['collection'])
            ->get()->row_array();
    }

    /**
     * 存在更新,不存在新增
     * @param $params
     */
    public function modify($params)
    {
        $info = $this->get($params);
        if(!empty($info)){
            $this->_db->where('staff_code',$params['staff_code'])
                ->where('collection',$params['collection'])
                ->update($this->table,$params);

        }else{
            $params['created_at'] = $params['updated_at'];
            $this->_db->insert($this->table,$params);
        }
    }

    /**
     * 获取配置内容
     * @param $params
     * @return mixed
     */
    public function getConfig($params)
    {
         return $this->_db->from($this->table)->select('config,field')
            ->where('staff_code', $params['staff_code'])->where('collection',$params['collection'])
            ->get()->row_array();
    }
}