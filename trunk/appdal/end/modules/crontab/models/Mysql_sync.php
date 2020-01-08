<?php
/**
 * @author W02278
 * User: Yibai
 * CreateTime: 2019/12/24 10:23
 *
 */

class Mysql_sync extends MY_Model
{

    /**
     * 执行状态记录
     * @var array
     */
    private $stat = array();

    /**
     * 默认值需要加上引号的类型的索引
     * @var array
     */
    private $convert_map = array('varchar', 'char', 'tinytext', 'mediumtext', 'text', 'longtext', 'enum');

    /**
     * 数据库结构同步
     * @param array $selfConf   待同步数据库配置
     * @param array $sourceConf 源数据库配置
     * @return array
     */
    public function sync($selfConf, $sourceConf)
    {
        $self = new \Mysqli($selfConf['host'], $selfConf['user'], $selfConf['psw'], $selfConf['db'], $selfConf['port']);
        $source = new \Mysqli($sourceConf['host'], $sourceConf['user'], $sourceConf['psw'], $sourceConf['db'], $sourceConf['port']);

        $selfData = $this->getStructure($self, $selfConf['db']);     //获取本身，和对比源的结构
        $sourceData = $this->getStructure($source, $sourceConf['db']);
//        var_dump($selfData);
//        var_dump($sourceData);
//        die;

        $selfKeys = array_keys($selfData);      //获取本身，和对比源的表
        $sourceKeys = array_keys($sourceData);

        $removeList = array_diff($selfKeys, $sourceKeys);       //如果自身有，源没有，就删除
        $createList = array_diff($sourceKeys, $selfKeys);       //如果源有，自身没有，就新增

        if (!empty($removeList)) {        //执行删除操作
            $remove_tab = '';
            foreach ($removeList as $val) {
                $remove_tab .= "`{$val}`,";
            }
            $remove_tab = trim($remove_tab, ',');
            $remove_sql = "DROP TABLE {$remove_tab}";
            if ($self->query($remove_sql)) {
                $this->stat['success'][] = $remove_sql;
            } else {
                $this->stat['error'][] = $remove_sql;
            }
        }

        if (!empty($createList)) {        //执行新增操作
            foreach ($createList as $val) {
                $create_arr = array();
                foreach ($sourceData[$val] as $item) {
                    $sql_write = "`{$item['COLUMN_NAME']}` {$item['COLUMN_TYPE']}";
                    if (!empty($item['COLUMN_DEFAULT'])) {
                        if (in_array($item['DATA_TYPE'], $this->convert_map)) {
                            $sql_write .= " DEFAULT '{$item['COLUMN_DEFAULT']}'";
                        } else {
                            $sql_write .= " DEFAULT {$item['COLUMN_DEFAULT']}";
                        }
                    }
                    $create_arr[] = $sql_write;
                }
                $create_sql = "CREATE TABLE IF NOT EXISTS `{$val}` (" . implode(',', $create_arr) . ")";
                if ($self->query($create_sql)) {
                    $this->stat['success'][] = $create_sql;
                } else {
                    $this->stat['error'][] = $create_sql;
                }
            }
        }

        foreach ($sourceData as $pKey => $item) {     //对比表的字段是否相同
            foreach ($selfData as $key => $val) {
                if ($pKey == $key) {     //检测表结构是否相同
                    $removeColumn = array_diff_key($val, $item);
                    $addColumn = array_diff_key($item, $val);
                    if (!empty($removeColumn)) {
                        foreach ($removeColumn as $removeVal) {
                            $removeColumnSql = "ALTER TABLE `{$key}` DROP COLUMN `{$removeVal['COLUMN_NAME']}`";
                            if ($self->query($removeColumnSql)) {
                                $this->stat['success'][] = $removeColumnSql;
                            } else {
                                $this->stat['error'][] = $removeColumnSql;
                            }
                        }
                    }
                    if (!empty($addColumn)) {
                        foreach ($addColumn as $addVal) {
                            $addInfo = "`{$addVal['COLUMN_NAME']}` {$addVal['COLUMN_TYPE']}";
                            if (!empty($addVal['COLUMN_DEFAULT'])) {
                                if (in_array($addVal['DATA_TYPE'], $this->convert_map)) {
                                    $addInfo .= " DEFAULT '{$addVal['COLUMN_DEFAULT']}'";
                                } else {
                                    $addInfo .= " DEFAULT {$addVal['COLUMN_DEFAULT']}";
                                }
                            }
                            $addSql = "ALTER TABLE `{$key}` ADD COLUMN {$addInfo}";
                            if ($self->query($addSql)) {
                                $this->stat['success'][] = $addSql;
                            } else {
                                $this->stat['error'][] = $addSql;
                            }
                        }
                    }
                }
            }
        }

        foreach ($sourceData as $table => $datum) {
            if (isset($selfData[$table])) {
                foreach ($datum as $column => $item) {
                    if (isset($selfData[$table][$column])) {
                        $selfColumn = $selfData[$table][$column];
                        unset($item['TABLE_SCHEMA']);
                        if ($diff = array_diff($item, $selfColumn)) {
                            $is_nullable = ($item['IS_NULLABLE'] == 'YES') ? ' NOT NULL' : '';
                            $column_default = ($item['COLUMN_DEFAULT'] !== null) ? " DEFAULT '{$item['COLUMN_DEFAULT']}'" : '';
                            $column_comment = ($item['COLUMN_COMMENT'] !== null) ? " COMMENT '{$item['COLUMN_COMMENT']}'" : '';
                            $alterColmunSql = "ALTER TABLE `{$table}` MODIFY `{$column}` {$item['COLUMN_TYPE']} {$item['EXTRA']}{$is_nullable}{$column_default}{$column_comment}";

                            if ($self->query($alterColmunSql)) {
                                $this->stat['success'][] = $alterColmunSql;
                            } else {
                                $this->stat['error'][] = $alterColmunSql;
                            }
                        }

                    }

                }
            }
        }
        return $this->stat;
    }

    /**
     * 获取表结构
     * @param \Mysqli $resource
     * @param string $db
     * @return array
     */
    public function getStructure(Mysqli $resource, $db)
    {
        $table_str = '';
        $info = array();
        $sql_table = 'SHOW TABLES';
        $res_table = $resource->query($sql_table);
        while ($row_table = $res_table->fetch_assoc()) {
            $table_str .= "'" . current($row_table) . "',";
        }
        $table_str = trim($table_str, ',');
        $column_sql = "SELECT * FROM INFORMATION_SCHEMA.COLUMNS WHERE table_name IN({$table_str}) AND table_schema = '{$db}'";
        $column_res = $resource->query($column_sql);
        if ($column_res) {
            while ($row_column = $column_res->fetch_assoc()) {
                $info[] = $row_column;
            }
            return $this->gen($info);
        } else {
            return array();
        }
    }

    /**
     * 数据排序处理
     * @param $array
     * @return array
     */
    public function gen($array)
    {
        $data = array();
        foreach ($array as $key => $item) {
            if (!array_key_exists($item['TABLE_NAME'], $data)) {
                foreach ($array as $value) {
                    if ($value['TABLE_NAME'] == $item['TABLE_NAME']) {
                        $data[$item['TABLE_NAME']][$value['COLUMN_NAME']] = $value;
                    }
                }
            }
        }
        return $data;
    }

}
