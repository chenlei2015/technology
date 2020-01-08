<?php 

/**
 * 表行为trait, 必须在model中使用
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-17 09：24
 * @link
 * @throw
 */
trait Table_behavior
{
    /**
     * 快照记录
     * 
     * @var unknown
     */
    private $_snapshot_records;
    
    /**
     * 记录是否设置了快照标志
     * 
     * @var unknown
     */
    private $_has_snapshot;
    
    /**
     * 快照恢复时设定的主键
     * 
     * @var unknown
     */
    private $_snapshot_primary_key;
    
    /**
     * 检测params是否有非表字段的column
     * 
     * @param unknown $params
     * @return array
     */
    public function has_foreign_key($params)
    {
        return array_diff_key($params, array_flip($this->columns()));
    }
    
    /**
     * 从params里面获取与数据表列同col的值
     * 
     * @param unknown $params
     */
    public function fetch_table_cols($params)
    {
        return array_intersect_key($params, array_flip($this->get_table_cols()));
    }
    
    /**
     * 根据外部设置的 参数key -> 表子段key 获取符合条件的字段
     */
    public function fetch_table_cols_by_trans($params, $tran_map)
    {
        if (!empty($tran_map) && ($foreign = $this->has_foreign_key(array_flip(array_values($tran_map)))))
        {
            throw new \InvalidArgumentException(sprintf('指定的表字段转换中出现了无效的字段名:%s', implode(',', $foreign)), 3001);
        }
        foreach ($params as $old_key => $val)
        {
            $new_params[$tran_map[$old_key] ?? $old_key] = $val;
        }
        return $this->fetch_table_cols($new_params);
    }
    
    /**
     * 获取表字段
     *
     * @return unknown
     */
    public function get_table_cols()
    {
        static $table_cols;
        if ($table_cols) return $table_cols;
        $table_cols = $this->_db->list_fields($this->table_name);
        return $table_cols;
    }
    
    /**
     * 继承自MY_Model的primaryKey属性，默认是id
     * 
     * @return unknown
     */
    public function get_primary_key()
    {
        return $this->primaryKey;
    }
    
    /**
     * 去除
     */
    public function columns($kick_cols = ['id'])
    {
        return array_diff($this->get_table_cols(), $kick_cols);
    }

    /**
     * 对符合where条件的记录进行快照，以方便后面恢复, 注意select必须选择主键,
     * 对快照数据追加lock in share mode 防止事务期间其他数据的写
     * 
     * @param unknown $where
     * @param unknown $selects
     */
    public function snapshot($selects, $primary_key, callable $where_callback)
    {
        $select_arr = explode(',', $selects);
        if (!in_array($primary_key, $select_arr))
        {
            $select_arr[] = $primary_key;
        }
        $selects = implode(',', $select_arr);
        
        $query = $this->_db->from($this->table_name);
        $query = $where_callback($query);
        $query->select($selects);
        
        $sql = $query->get_compiled_select($this->table_name, false);
        $sql .= ' LOCK IN SHARE MODE';
        $this->_snapshot_records = $this->_db->query($sql)->result_array();
        $this->_has_snapshot = true;
        $this->_snapshot_primary_key = $primary_key;
        
        return true;
    }
    
    /**
     * 回放快照
     */
    public function recovery_snapshot()
    {
        if (!$this->_has_snapshot) return false;
        if (empty($this->_snapshot_records)) return true;
        if ($this->_db->update_batch($this->_snapshot_records, $this->table_name, $this->_snapshot_primary_key))
        {
            $this->_has_snapshot = false;
            return true;
        }
        return false;
    }
    
    public function record_count()
    {
        $result = $this->_db->query(sprintf('SELECT COUNT(*) AS num FROM %s', $this->table_name))->result_array();
        return $result[0]['num'];
    }
    
    /**
     * 设置了TableId的情况下生成一个全局id
     * @return string
     */
    public function gen_id()
    {
        if (!$this->tableId)
        {
            throw new \InvalidArgumentException('请先设置表tableId属性', 412);
        }
        usleep(1);
        list($mic, $sec) = explode(" ", microtime());
        $micro = ($sec*1000000+intval(round($mic*1000000)));
        return sprintf("%s%s%s%s", dechex($micro),dechex(getmypid()),rand(100000,999999), $this->tableId);
    }

    /**
     * 将多个二位元素数组组成一条多insert into () values (), ()语句
     */
    public function batch_insert_string($batch_insert)
    {
        if (empty($batch_insert)) return '';
        
        $key_hash = '';
        $fields = $values = array();
        $table = $this->_db->protect_identifiers($this->table_name, TRUE, NULL, FALSE);
        
        foreach ($batch_insert as $key => $data)
        {
            $keys = array_keys($data);
            if (empty($fields)) 
            {
                $key_hash = md5(implode(',', $keys));
                foreach ($keys as $col)
                {
                    $fields[] = $this->_db->escape_identifiers($col);
                }
            }
            if ($key_hash != md5(implode(',', $keys)))
            {
                throw new \InvalidArgumentException('生成批量INSERT语句失败，元素中key值不一样');
            }
            
            $one_value = [];
            foreach ($data as $col => $val)
            {
                $one_value[] = $this->_db->escape($val);
            }
            $values[] = '('.implode(', ', $one_value).')';
        }
        return 'INSERT INTO '. $table .' ('.implode(', ', $keys).') VALUES ('.implode(', ', $values).')';
    }
}