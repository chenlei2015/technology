<?php

/**
 * 泛欧
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-06-01
 * @link
 */
class Fba_pan_eu_model extends MY_Model
{

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_fba_pan_eu';
        $this->tableId = 0;
        parent::__construct();
    }

    public function get_by_tags($tags)
    {
        return group_by($this->_db->from($this->table_name)->where_in('tag', $tags)->get()->result_array(), 'tag');
    }


}