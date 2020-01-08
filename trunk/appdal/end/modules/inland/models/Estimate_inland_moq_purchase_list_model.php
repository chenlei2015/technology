<?php

/**
 * Estimate_inland_moq_purchase_list_model
 *
 */
class Estimate_inland_moq_purchase_list_model extends MY_Model
{
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_estimate_moq_purchase_list';
        $this->primaryKey = 'id';
        parent::__construct();
    }

    /**
     *
     * @param unknown $params
     * @return bool
     */
    public function add($params)
    {
        return $this->_db->insert($this->table_name, $params);
    }

    public function madd($params)
    {
        return $this->_db->insert_batch($this->table_name, $params);
    }

    /**
     * 取表的汇总
     *
     * @param int $version
     * @return string
     */
    public function sum($version)
    {
        $result = $this->_db->from($this->table_name)
        ->select('sum(final_require_money) as final_require_money')
        ->where('version', $version)
        ->get()
        ->result_array();
        if (empty($result)) {
            return '0.00';
        } else {
            return sprintf('%0.2f', $result[0]['final_require_money']/10000);
        }
    }



}