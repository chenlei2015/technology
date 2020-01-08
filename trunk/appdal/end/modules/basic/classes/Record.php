<?php

/**
 * 对table表的一条记录进行分装，以达到快速的针对record的增删改
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-03-01
 * @link
 * @throw
 */
class Record
{
    /**
     * 返回变更的数量
     *
     * @var integer
     */
    const REPORT_ONLY_NUMS = 1;

    /**
     * 返回变更的字段name
     * @var integer
     */
    const REPORT_ONLY_COLS = 2;

    /**
     * 返回变更的key=>value
     * @var integer
     */
    const REPORT_FULL_ARR = 3;

    /**
     * 原始条件
     *
     * @var array
     */
    protected $_origin;

    /**
     * 当前值
     *
     * @var array
     */
    protected $_current;

    /**
     * 该记录的主键
     *
     * @var mixed id
     */
    protected $_primary_key = 'id';

    /**
     * 记录对应的表model
     *
     * @var unknown
     */
    protected $_model;

    /**
     * 开启接收额外的属性
     *
     * @var unknown
     */
    protected $_enable_extra_property = false;

    /**
     * 被修改的字段
     * @var unknown
     */
    protected $_modify_property;

    /**
     * 新增的字段
     *
     * @var unknown
     */
    protected $_add_property;

    /**
     * 更新api接口
     *
     * @var unknown
     */
    protected $_update_api;

    /**
     *
     * @param array $params 一维数组
     */
    public function __construct($params  = array())
    {
        if (!empty($params)) {
            $this->recive($params);
        }
    }

    /**
     * 接收一个一维数组， 注意model未重置
     *
     * @param array $params
     * @return Record
     */
    public function recive(array $params) : Record
    {
        $this->_modify_property = [];
        $this->_add_property = [];
        $this->_update_api = '';
        $this->_enable_extra_property = false;

        $this->_origin = $params;
        $this->_current = $this->_origin;
        return $this;
    }

    /**
     * 返回原始数据
     */
    public function origin()
    {
        return $this->_origin;
    }

    public function current()
    {
        return $this->_current;
    }

    /**
     * 设置多个属性
     *
     * @param unknown $arr
     * @throws \RuntimeException
     */
    public function mset($arr)
    {
        foreach ($arr as $key => $val)
        {
            if (!isset($this->_current[$key]) && $this->_enable_extra_property)
            {
                throw new \RuntimeException(sprintf('设置属性错误，无法找到属性名：%s', $key), 500);
            }
            $this->_current[$key] = $val;
        }
    }

    /**
     * 开启接收外部新字段
     */
    public function enable_extra_property()
    {
        $this->_enable_extra_property = true;
    }

    /**
     * 设置属性, 三个参数设置val为数组的值，目前只支持到2层
     *
     * @throws \RuntimeException
     * @return Record
     */
    public function set() : Record
    {
        $args_count = func_num_args();
        $args = func_get_args();
        if (count($args) == 2)
        {
            if (array_key_exists($args[0], $this->_current) || $this->_enable_extra_property)
            {
                $this->_current[$args[0]] = $args[1];
                return $this;
            }
            else
            {
                throw new \RuntimeException(sprintf('设置属性错误，无法找到属性名：%s', $args[0]), 500);
            }
        }
        elseif (count($args) == 3)
        {
            //设置数组
            if ((array_key_exists($args[0], $this->_current) && array_key_exists($args[1], $this->_current[$args[0]])) || $this->_enable_extra_property)
            {
                $this->_current[$args[0]][$args[1]] = $args[2];
                return $this;
            }
            else
            {
                throw new \RuntimeException(sprintf('设置数组属性错误，无法找到属性名：%s', $args[0]), 500);
            }

        }
        return $this;
    }


    /**
     * 获取record信息
     *
     * @throws \RuntimeException
     * @return unknown|array
     */
    public function get()
    {
        $args_count = func_num_args();
        $args = func_get_args();
        if (count($args) == 0)
        {
            return $this->_current;
        }
        elseif (count($args) == 1)
        {
            if (is_array($args[0]))
            {
                return array_intersect_key($this->_current, array_flip($args[0]));
            }
            else
            {
                if (isset($this->_current[$args[0]]))
                {
                    return $this->_current[$args[0]];
                }
            }
            throw new \RuntimeException(sprintf('获取属性错误，无法找到属性名：%s', $args[0]), 500);
        }
        elseif (count($args) == 2)
        {
            if (isset($this->_current[$args[0]][$args[1]]))
            {
                return $this->_current[$args[0]][$args[1]];
            }
            throw new \RuntimeException(sprintf('获取数组属性错误，路径错误', implode(',', $args)), 500);
        }
        throw new \RuntimeException(sprintf('获取属性错误，不支持的参数个数'), 500);
    }

    /**
     * 判断是否存在key
     *
     * @return unknown|boolean
     */
    public function has() : bool
    {
        $args_count = func_num_args();
        $args = func_get_args();
        if (count($args) == 1)
        {
            return isset($this->_current[$args[0]]);
        }
        elseif (count($args) == 2)
        {
            return isset($this->_current[$args[0]][$args[1]]);
        }
        return false;
    }

    /**
     * 静默获取， 非法字段的时候返回null值
     *
     * @return array|unknown|boolean
     */
    public function silent_get()
    {
        $args = func_get_args();
        try {
            return $this->get(...$args);
        }
        catch (\Exception $e)
        {
            return null;
        }
    }

    /**
     * 获取修改的字段， 不含新增
     */
    protected function _scan_change() : bool
    {
        $this->_add_property = [];
        $this->_modify_property = [];

        foreach ($this->_current as $key => $val)
        {
            if (!array_key_exists($key, $this->_origin))
            {
                $this->_add_property[$key] = $val;
                continue;
            }

            if ($this->_origin[$key] != $val)
            {
                $this->_modify_property[$key] = ['before' => $this->_origin[$key], 'after' => $val];
            }
        }

        return true;
    }

    /**
     * 汇报变更情况
     *
     * @param int $type 返回类型
     * @param bool $only_after true: 只返回变更after字段
     * @param string $report_add false 只返回变更的字段, true 包含新增
     * @param bool $is_merge modify和add是否合并
     * @return unknown[]|unknown
     */
    public function report(int $type = Record::REPORT_ONLY_NUMS, $only_after = true, $report_add = false, $is_merge = false, $append_cols = [])
    {
        $this->_scan_change();

        if ($report_add)
        {
            $changes = $is_merge ? array_merge($this->_modify_property) : ['modify' => $this->_modify_property, 'add' => $this->_add_property];
        }
        else
        {
            $changes = $this->_modify_property;
        }

        if ($type == Record::REPORT_ONLY_NUMS)
        {
            return count(array_keys($changes));
        }
        elseif ($type == Record::REPORT_ONLY_COLS)
        {
            return array_keys($changes);
        }
        else
        {
            $afters = [];
            if ($only_after)
            {
                if ($is_merge)
                {
                    foreach ($changes as $key => $val)
                    {
                        $afters[$key] = $val['after'];
                    }
                }
                else
                {
                    //多part
                    if ($report_add)
                    {
                        foreach ($changes as $part => $items)
                        {
                            foreach ($items as $key => $val)
                            {
                                $afters[$part][$key] = $val['after'];
                            }
                        }
                    }
                    else
                    {
                        foreach ($changes as $key => $val)
                        {
                            $afters[$key] = $val['after'];
                        }
                    }

                }
            }
            foreach ($append_cols as $col)
            {
                if (isset($this->_current[$col]))
                {
                    $afters[$col] = $this->_current[$col];
                }
            }
            return $afters;
        }
    }

    /**
     * 字段是否有任何变更， 排除掉指定字段，比如设置的更新的时间戳
     */
    public function has_change($ignore_cols = []) : bool
    {
        return $this->report(Record::REPORT_ONLY_NUMS, false, true, true) != 0;
    }


    /**
     * 生成修改日志描述信息
     *
     * @param callable $cb
     * @param unknown $cb_params
     * @return unknown
     */
    public function general_modify_desc(callable $cb, $cb_params) : string
    {
        return $cb($this, $cb_params);
    }


    /**
     * 设置更新依赖的model，并可选指定更新的方法
     *
     * @param unknown $model
     * @param string $use_model_api model的更新接口，接受一个数组传参
     */
    public function setModel($model, $use_model_api = '')
    {
        $this->_model = $model;
        if ($use_model_api != '')
        {
            $this->_update_api = $use_model_api;
        }
    }

    /**
     * 修改一条记录
     *
     * @param string $verify_col_mode strict 有表未定义字段即抛出异常， loose 宽松， 只取表出现的字段
     * @param string $update_add 是否更新新增的字段
     * @param array $ignore_cols 忽略检测
     * @throws \RuntimeException
     * @return number|unknown
     */
    public function update($verify_col_mode = 'strict', $update_add = false, $ignore_cols = [])
    {
        //主键检测
        $p_keys = explode(',', $this->_model->get_primary_key());
        $primary_assoc = array_intersect_key($this->_current, array_flip($p_keys));
        if (count($primary_assoc) != count($p_keys))
        {
            throw new \RuntimeException('Record 无法进行更新处理，字段必须涵盖定义的主键字段', 500);
        }

        //修改检测
        $report_changes = $this->report(Record::REPORT_FULL_ARR, $only_after = true, $update_add);
        $all_change_columns = $update_add ? array_merge($report_changes['modify'], $report_changes['add']) : $report_changes;

        //去除忽略字段
        if (!empty($ignore_cols))
        {
            $all_change_columns = array_diff_key($all_change_columns, array_flip($ignore_cols));
        }
        $format_params = $this->_model->fetch_table_cols($all_change_columns);
        if (count($intersect = array_intersect_key($primary_assoc, $format_params)) > 0)
        {
            throw new \RuntimeException(sprintf('Record 无法进行更新处理，无法更新有修改的主键字典', implode(',', array_keys($intersect))), 500);
        }

        //没有修改
        if (empty($format_params))
        {
            return -1;
        }

        //必须约定use_model_api的统一格式，否则不可用
        if ($this->_update_api != '' && property_exists($this->_model, $this->_update_api))
        {
            return $this->_model->$use_model_api($this->_current);
        }

        //Record更新
        if (!$this->_model)
        {
            throw new \RuntimeException('Record 无法进行更新处理，没有有效的资源', 500);
        }
        if (!property_exists($this->_model, 'primaryKey') || !method_exists($this->_model, 'get_primary_key'))
        {
            throw new \RuntimeException('Record 无法进行更新处理，指定model无法获取primaryKey属性', 500);
        }

        $db = $this->_model->getDatabase();
        $table = $this->_model->getTable();

        //如果有未定义字段，则直接报错
        if ($verify_col_mode == 'strict')
        {
            if ($diff_assoc = $this->_model->has_foreign_key($format_params))
            {
                throw new \RuntimeException(sprintf('Record 无法进行更新处理，记录中出现未定义的字段', implode(',', array_keys($diff_assoc))), 500);
            }
        }
        try {
            $db->reset_query();
            foreach ($primary_assoc as $pri => $val)
            {
                $db->where($pri, $val);
            }
            $db->update($table, $format_params);
            if (($count = $db->affected_rows()) == 0)
            {
                logger('ERROR', 'Record更新错误', sprintf('Model: %s， 更新api: %s, 参数信息：%s', get_class($this->_model), $this->_update_api, json_encode(['where' => $primary_assoc, 'params' => $format_params])));
                throw new \RuntimeException(sprintf('Record 没有更新记录，实际应该更新一条记录', $count), 500);
            }
            return $count;
        }
        catch (Exception $e)
        {
            logger('ERROR', 'Record更新错误', sprintf('Model: %s， 更新api: %s, 异常信息：%s', get_class($this->_model), $this->_update_api, $e->getMessage()));
            throw new \RuntimeException(sprintf('Record DB 出现异常'), 500);
        }

    }

    /**
     * 回退到初始数据
     */
    public function reback()
    {
        $origin = $this->_origin;
        $current = $this->_current;

        //exchange
        $this->_origin = $current;
        $this->_current = $origin;

        $result = $this->update();

        //reset
        $this->_origin = $origin;
        $this->_current = $current;

        return $result;

    }

    /**
     * 设置属性获取
     *
     * @param unknown $col
     * @return NULL|unknown
     */
    public function __get($col)
    {
        return $this->_current[$col] ?? null;
    }

    /**
     * 设置属性
     *
     * @param unknown $col
     * @param unknown $val
     * @throws \RuntimeException
     */
    public function __set($col, $val)
    {
        $args = func_get_args();
        return $this->set(...$args);
    }

    /**
     *
     * @return unknown
     */
    public function __toString()
    {
        return $this->_current;
    }

}