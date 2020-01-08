<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * Inland活动列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-09-05
 * @link
 */
class Inland_activity_list_model extends MY_Model
{

    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_activity_cfg';
        $this->primaryKey = 'id';
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
     * 能够作废的
     *
     * @param array $ids
     * @param string $salesman
     * @param array $accounts
     * @return array|array
     */
    public function get_can_discard($ids)
    {
        if (empty($ids)) return [];

        $query = $this->_db->from($this->table_name)
        ->select('id')
        ->where_in('id', $ids)
            ->where('activity_state !=', ACTIVITY_STATE_DISCARD)
        ->limit(count($ids));
        $result = array_column($query->get()->result_array(), 'id');
        return $result;
    }


    /**
     * 能够审核的
     *
     * @param array $ids
     * @param string $salesman
     * @param array $accounts
     * @return array|array
     */
    public function get_can_approve($ids)
    {
        $query = $this->_db->from($this->table_name)
        ->select('id')
        ->where('activity_state !=', ACTIVITY_STATE_DISCARD)
        ->where('approve_state', ACTIVITY_APPROVAL_INIT);
        if(!empty($ids))
        {
            $query->where_in('id', $ids)->limit(count($ids));
        }
        $result = array_column($query->get()->result_array(), 'id');
        return $result;
    }

    /**
     * 刷新csv上传活动中可以出入的记录
     *
     * @param array $aggr_ids
     * @param string $privielges 默认为*
     * @param array $accounts 默认为[]
     * @param Object $csvwrite
     * @return array|NULL[]
     */
    public function get_can_insert($aggr_ids, $privielges, $accounts, $csvwrite)
    {
        if (empty($aggr_ids)) return [];

        $query = $this->_db->from($this->table_name)
        ->select('id,aggr_id,activity_start_time,activity_end_time')
        ->where_in('aggr_id', $aggr_ids)
        ->where('activity_state !=', ACTIVITY_STATE_DISCARD)
        ->where('approve_state !=', ACTIVITY_APPROVAL_FAIL);

        $result = group_by($query->get()->result_array(), 'aggr_id');

        $valide = [];

        $col_to_index = array_flip($csvwrite->property('index_to_col'));

        /**
         * 新插入检测逻辑：
         *
         * 1、检测新增的是否与已经存在的有交叉。
         */
        if (!empty($result)) {

            //上传相同维度
            foreach ($csvwrite->selected as $exists_aggr_id => $csv_rows)
            {
                foreach ($csv_rows as $csv_row)
                {
                    $is_valide = true;

                    $new_activity_start_time = $csv_row[$col_to_index['activity_start_time']];
                    $new_activity_end_time = $csv_row[$col_to_index['activity_end_time']];

                    //每个活动取相同维度里面的检测，是否冲突
                    foreach ($result[$exists_aggr_id] ?? [] as $row)
                    {
                        //max(已经) < min(新) 更靠后时间 || min(已经） > max(新) 更早的时间
                        if (($row['activity_end_time'] < $new_activity_start_time) || ($row['activity_start_time'] > $new_activity_end_time))
                        {
                            continue;
                        }
                        else
                        {
                            $is_valide = false;
                            break;
                        }
                    }
                    //还需要剔除文件内重复的
                    if ($is_valide)
                    {
                        $valide[$exists_aggr_id][] = $csv_row;
                    }
                }
            }

            //剩余的全部可用
            $diff_aggr_ids = array_diff($aggr_ids, array_keys($result));
            foreach ($diff_aggr_ids as $id)
            {
                $valide[$id] = $csvwrite->selected[$id];
            }
        }
        else
        {
            //没有任何交集
            $valide = $csvwrite->selected;
        }

        /**
         * 2、检测新增与新增之间是否存在交叉
         */
        $exists_aggr = [];

        foreach ($valide as $aggr_id => $csv_rows)
        {
            if (count($csv_rows) == 1) continue;

            $is_valide = true;

            foreach ($csv_rows as $k => $csv_row)
            {
                $new_activity_start_time = $csv_row[$col_to_index['activity_start_time']];
                $new_activity_end_time = $csv_row[$col_to_index['activity_end_time']];

                // op[aggr_id] = [ [start, end], [$start, end],];
                if (isset($exists_aggr[$aggr_id])) {
                    foreach ($exists_aggr[$aggr_id] as $time_point)
                    {
                        if (($time_point[1] < $new_activity_start_time) || ($time_point[0] > $new_activity_end_time))
                        {
                            $exists_aggr[$aggr_id][] = [$new_activity_start_time, $new_activity_end_time];
                            continue;
                        }
                        else
                        {
                            $is_valide = false;
                            break;
                        }
                    }
                }
                else
                {
                    $exists_aggr[$aggr_id][] = [$new_activity_start_time, $new_activity_end_time];
                }
            }

            if (!$is_valide)
            {
                unset($valide[$aggr_id][$k]);
            }
        }

        unset($result, $exists_aggr);

        return $valide;
    }

    /**
     * 取相同维度的记录
     *
     * @param unknown $aggr_ids
     * @param array $exclude_ids
     * @return array
     */
    public function get_same_aggr_exclude_ids($aggr_ids, $exclude_ids = [])
    {
        $query = $this->_db->from($this->table_name)
        ->select('id,aggr_id,activity_start_time,activity_end_time')
        ->where_in('aggr_id', $aggr_ids)
        ->where('activity_state !=', ACTIVITY_STATE_DISCARD)
        ->where('approve_state !=', ACTIVITY_APPROVAL_FAIL);

        if (!empty($exclude_ids)) {
            $query->where_not_in('id', $exclude_ids);
        }
        return group_by($query->get()->result_array(), 'aggr_id');
    }

    /**
     * 刷新csv上传活动中可以出入的记录
     *
     * @param array $aggr_ids
     * @param string $salesman *|W01206
     * @param array $accounts manager
     * @param Object $csvwrite
     * @return array|NULL[]
     */
    public function get_can_update($ids, $privielges, $accounts, $csvwrite)
    {
        if (empty($ids)) return [];

        $query = $this->_db->from($this->table_name)
        ->select('*')
        ->where_in('id', $ids)
        ->where('activity_state !=', ACTIVITY_STATE_DISCARD)
        ->where('approve_state !=', ACTIVITY_APPROVAL_FAIL)
        ->limit(count($ids));

        $result = $query->get()->result_array();

        /**
         * 检测逻辑
         * 1. 哪些记录是我所有
         *
         */

        $valide = $exists_aggr = [];

        $col_to_index = array_flip($csvwrite->property('index_to_col'));
        $line_index_name = $csvwrite->get_line_index_name();

        if (!empty($result)) {

            //找出所有同维度的记录
            $aggr_ids = array_column($result, 'aggr_id');

            $running_activity = $this->get_same_aggr_exclude_ids($aggr_ids, $ids);

            //查看是否交叉
            foreach ($result as $row)
            {
                $is_valide = true;

                //新设立的时间
                $new_activity_start_time = $csvwrite->selected[$row['id']][$col_to_index['activity_start_time']];
                $new_activity_end_time = $csvwrite->selected[$row['id']][$col_to_index['activity_end_time']];

                //检测时间是否有冲突。
                // 1. 同先前同维度的是否有冲突。
                // op[aggr_id] = [ [start, end], [$start, end],];
                if (isset($exists_aggr[$row['aggr_id']])) {
                    foreach ($exists_aggr[$row['aggr_id']] as $time_point)
                    {
                        if (($time_point[1] < $new_activity_start_time) || ($time_point[0] > $new_activity_end_time))
                        {
                            continue;
                        }
                        else
                        {
                            //忽略
                            $is_valide = false;
                            break;
                        }
                    }
                }

                if (!$is_valide) {
                    log_message('INFO', sprintf('上传文件记录中已存在相同维度的记录有冲突'));
                    continue;
                }

                // 2. 同数据库存在的是否冲突
                foreach ($running_activity[$row['aggr_id']] ?? [] as $run_row)
                {
                    //max(已经) < min(新) 更靠后时间 || min(已经） > max(新) 更早的时间
                    if (($run_row['activity_end_time'] < $new_activity_start_time) || ($run_row['activity_start_time'] > $new_activity_end_time))
                    {
                        continue;
                    }
                    else
                    {
                        $is_valide = false;
                        break;
                    }
                }

                if (!$is_valide) {
                    log_message('INFO', sprintf('记录id:%d修改后的时间与数据库已存在相同维度的记录有冲突', $row['id']));
                    continue;
                }

                //可以更新的记录，记录行号
                $row[$line_index_name] = $csvwrite->selected[$row['id']][$line_index_name];
                $valide[$row['id']] = $row;

                $exists_aggr[$row['aggr_id']][] = [$new_activity_start_time, $new_activity_end_time];

                continue;

            }
        }

        //只检测时间是否冲突，至于有没有修改值不检测
        unset($result, $running_activity, $exists_aggr);

        //pr($valide);exit;

        return $valide;
    }

    /**
     * 获取此刻正在生效的活动
     *
     * @return array
     */
    public function get_current_valid_activities()
    {
        $now = date('Y-m-d H:i:s');

        $result = $this->_db->from($this->table_name)
        ->select('aggr_id,created_at,activity_start_time,activity_end_time')
        ->where('approve_state', ACTIVITY_APPROVAL_SUCCESS)
        ->where('activity_start_time <', $now)
        ->where('activity_end_time >', $now)
        ->get()
        ->result_array();

        return $result;
    }

    public function get_future_valid_activities()
    {
        $now = date('Y-m-d H:i:s');

        $result = $this->_db->from($this->table_name)
        ->select('erpsku as sku,amount,activity_start_time,activity_end_time, execute_purcharse_time')
        ->where('approve_state', ACTIVITY_APPROVAL_SUCCESS)
        ->where('activity_start_time >', $now)
        ->where('activity_state !=', ACTIVITY_STATE_DISCARD)
        //->where('execute_purcharse_time >', $now)
        ->order_by('execute_purcharse_time asc')
        ->get()
        ->result_array();

        if (!empty($result)) {
            $result = group_by($result, 'sku');
        }
        return $result;
    }
}