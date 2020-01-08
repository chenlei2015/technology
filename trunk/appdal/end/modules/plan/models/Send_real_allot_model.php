<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 实际发运表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author lewei
 * @date 2019-10-24
 * @link 
 */
class Send_real_allot_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'python';
        $this->table_name = 'send_real_allot_show';
        parent::__construct();
    }


    public function test()
    {
        return $this->_db->select('*')->limit(10)->get('send_real_allot_show')->result_array();
    }


}