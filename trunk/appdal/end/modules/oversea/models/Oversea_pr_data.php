<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * python离线版数据库
 * @author W02278
 * @name Oversea_activity_list_model
 */
class Oversea_pr_data extends MY_Model
{

    use Table_behavior;

    public function __construct()
    {
        $this->database = 'oversea_mrp';
        $this->table_name = 'yibai_oversea_pr_data';
        $this->primaryKey = 'gid';
        parent::__construct();
    }

    public function pk($gid)
    {
        $result = $this->_db->from($this->table_name)->where('id', $gid)->limit(1)->get()->result_array();
        if ($result)
        {
            return $result[0];
        }
        else
        {
            return [];
        }
    }

    /**
     * @param $skus
     * @return array
     * @author W02278
     * CreateTime: 2019/12/16 17:15
     */
    public function get_skus_qty_list($skus)
    {
        if (!$skus) {
            return [];
        }
        $select = 'sku,station_code,available_qty,oversea_up_qty,oversea_ship_qty,approve_state';
        $sql = sprintf(
            'SELECT %s FROM %s where sku in (%s) group by sku,station_code',
            $select,
            $this->table_name,
            array_where_in($skus)
        );
        return $this->_db->query($sql)->result_array();
    }



}