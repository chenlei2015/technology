<?php

/**
 * Inland 备货关系配置表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @date 2019-03-04
 * @link
 */
class Inland_sku_cfg_history_model extends MY_Model
{

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_sku_cfg_history';
        $this->primaryKey = 'gid';
        parent::__construct();
    }

    public function insert($info)
    {
        $data = [
            'gid' => $info['gid'],
            'state' => $info['state'],
            'stock_way' => $info['stock_way'],
            'bs' => $info['bs'],
            'sp' => $info['sp'],
            'shipment_time' => $info['shipment_time'],
            'first_lt' => $info['first_lt'],
            'sc' => $info['sc'],
            'sz' => $info['sz'],
            'approved_at' => $info['approved_at'],
            'approved_uid' => $info['approved_uid'],
            'approved_zh_name' => $info['approved_zh_name'],
            'updated_at' => $info['updated_at'],
            'updated_uid' => $info['updated_uid'],
            'updated_zh_name' => $info['updated_zh_name'],
            'remark' => $info['remark'],
        ];

        return $this->db->insert($this->table_name,$data);
    }

    public function clean($gid)
    {
        return $this->db->delete($this->table_name,['gid'=>$gid]);
    }


}