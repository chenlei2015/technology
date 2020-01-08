<?php

/**
 * Export_log_model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @link
 */
class Export_log_model extends MY_Model
{

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_export_log';
        $this->primaryKey = 'id';
        parent::__construct();
    }

    /**
     * 必要字段 amount, created_start, created_end 其他填充
     * @param unknown $params
     * @return bool
     */
    public function add($params)
    {
        $table_cols = $this->getTableFieldsList();
        $require_cols = array_flip(['amount', 'created_start', 'created_end', 'sql']);
        if (count(array_intersect_key($require_cols, $params)) !== count($require_cols)) {
            throw new \RuntimeException('导出日志缺少必要参数');
        }
        if (!isset($params['uid'])) {
            $active_user = get_active_user();
            $params['uid'] = $active_user->staff_code;
            $params['user_name'] = $active_user->user_name;
        }
        if (!isset($params['module'])) {
            list($params['module'], $params['ctrl'], $params['method']) = get_called_module();
        }
        unset($table_cols[$this->primaryKey]);
        $params = array_intersect_key($params, array_flip($table_cols));
        return $this->_db->insert($this->table_name, $params);
    }

    public function madd($params)
    {
        return $this->_db->insert_batch($this->table_name, $params);
    }


}