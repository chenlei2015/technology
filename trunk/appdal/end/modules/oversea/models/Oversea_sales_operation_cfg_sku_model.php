<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * Inland 销量运算配置列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @link
 */
class Oversea_sales_operation_cfg_sku_model extends MY_Model implements Rpcable
{

    use Table_behavior, Rpc_imples;

    private $_rpc_module = 'oversea';

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_oversea_sales_operation_cfg_sku';
        $this->primaryKey = 'id';
        $this->tableId = 163;
        parent::__construct();
    }

    /**
     *Notes: 根据gid获取对应的不参与运算的sku数据
     * @param $gid
     * @return mixed
     */
    public function info($gid){
        $this->_db->from($this->table_name);
        $this->_db->where('cfg_gid',$gid);
        return $this->_db->select('sku')->get()->result_array();
    }
}