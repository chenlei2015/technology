<?php
require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 模拟发运表
 * @author zc
 * @date 2019-10-24
 */
class Demand_list_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'python';
        $this->table_name = 'fayunxuqiu';
        parent::__construct();
    }

    public function test()
    {
        return $this->_db->select('*')->limit(1)->get('fayunxuqiu')->result_array();
    }

}