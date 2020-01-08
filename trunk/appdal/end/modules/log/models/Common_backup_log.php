<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

class Common_backup_log extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database   = 'common';
        $this->table_name = 'yibai_backup_log';
        parent::__construct();
    }

    /**
     * 增加一条日志
     *
     * @param unknown $params
     *
     * @return bool
     */
    public function add($params)
    {
        return $this->_db->insert($this->table_name, $params);
    }

    /**
     *
     */
    public function check_is_exist($condition)
    {
        $result = $this->_db->select('*')->from($this->table_name)
            ->where($condition)
            ->limit(1)->get()->row_array();

        return $result;
    }

}