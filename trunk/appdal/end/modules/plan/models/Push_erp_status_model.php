<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 查看推送结果
 *
 * @package -
 * @subpackage -
 * @category -
 * @author lewei
 * @date 2019-10-24
 * @link 
 */
class Push_erp_status_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'python';
        $this->table_name = 'push_erp_status';
        parent::__construct();
    }


    public function test()
    {
        return $this->_db->select('*')->limit(10)->get('push_erp_status')->result_array();
    }


}