<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba异常列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-09-05
 * @link
 */
class Inland_exception_list_model extends MY_Model
{

    use Table_behavior;

    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_inland_exception_list';
        $this->primaryKey = 'gid';
        $this->tableId = 0;
        parent::__construct();
    }

    public function save_exception_list(array $gid_arr)
    {
        if (empty($gid_arr)) {
            return 0;
        }
        $sql = sprintf('replace into %s
select gid, pr_sn, sku, is_refund_tax, purchase_warehouse_id, sku_state, product_status, stock_up_type
from yibai_inland_pr_list
where gid in (%s)',
            $this->table_name,
            array_where_in($gid_arr)
            );
        if ($this->_db->query($sql)) {
            return $this->_db->affected_rows();
        } else {
            return 0;
        }
    }


}