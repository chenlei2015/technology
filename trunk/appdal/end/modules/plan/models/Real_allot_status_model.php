<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 查看调拨结果列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author lewei
 * @date 2019-10-24
 * @link 
 */
class Real_allot_status_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'python';
        $this->table_name = 'real_allot_status';
        parent::__construct();
    }


    public function test()
    {
        return $this->_db->select('*')->limit(10)->get('real_allot_status')->result_array();
    }


}