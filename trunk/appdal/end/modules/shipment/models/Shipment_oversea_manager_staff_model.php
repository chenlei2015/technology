<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/11
 * Time: 13:59
 */

class Shipment_oversea_manager_staff_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_shipment_oversea_manager_staff';
        $this->primaryKey = 'gid';
        $this->tableId = 168;
        parent::__construct();
    }


}
