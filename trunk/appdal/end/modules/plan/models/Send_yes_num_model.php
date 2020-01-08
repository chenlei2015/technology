<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 模拟发运表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author lewei
 * @date 2019-10-24
 * @link 
 */
class Send_yes_num_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'python';
        $this->table_name = 'send_yes_num_show';
        parent::__construct();
    }


    public function test()
    {
        return $this->_db->select('*')->limit(10)->get('send_yes_num_show')->result_array();
    }


}