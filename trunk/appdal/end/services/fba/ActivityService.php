<?php

/**
 * FBA 活动需求服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class ActivityService
{
    public static $s_system_log_name = 'FBA';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_activity_list_model', 'm_fba_activity', false, 'fba');
        $this->_ci->load->helper('fba_helper');
        return $this;
    }

    //activity_state
    public function update_remark($post)
    {
        $report = [
                'data' => false,
                'errorMess'  => ''
        ];

        $record = $this->_ci->m_fba_activity->pk($post['id']);
        if (empty($record))
        {
            $report['errorMess'] = '无效的记录';
            return $report;
        }

        $active_user = get_active_user();
        $salesman = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? '*' : $active_user->staff_code;
        //$manager_accounts = $active_user->get_my_manager_accounts();
        $account_nums = $active_user->get_my_manager_account_nums();

        if (!($salesman == '*' || $record['salesman'] == $salesman || in_array($record['account_num'], $account_nums)))
        {
            $report['errorMess'] = '没有权限，需要销售本人或者账号管理员';
            return $report;
        }

        if ($post['remark'] == $record['remark'])
        {
            $report['errorMess'] = '备注相同，没有任何修改';
            return $report;
        }

        $updated_at =  date('Y-m-d H:i:s');
        $updated_uid = $active_user->staff_code;
        $updated_zh_name  = $active_user->user_name;

        $batch_update_pr = $batch_insert_log = [];

        $batch_update_pr[] = [
            'id' => $record['id'],
            'remark' => $post['remark'],
            'updated_uid' => $active_user->staff_code,
            'updated_at' => $updated_at,
            'updated_zh_name' => $updated_zh_name
        ];
        $batch_insert_log[] = [
            'gid' => $record['id'],
            'uid' => $updated_uid,
            'user_name' => $updated_zh_name,
            'context' => '添加备注：'.$post['remark'],
        ];

        $this->_ci->load->model('Fba_activity_log_model', 'fba_activity_log', false, 'fba');

        $db = $this->_ci->m_fba_activity->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->m_fba_activity->getTable(), $batch_update_pr, 'id');
            if (!$update_rows)
            {
                $report['errorMess'] = '添加备注失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->fba_activity_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $report['errorMess'] = '添加备注插入失败';
                throw new \RuntimeException($report['errorMess']);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '添加备注，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['data'] = true;
            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('添加备注更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = $records = null;
            unset($batch_update_pr, $batch_insert_log, $records);

            $report['errorMess'] = '添加备注抛出异常：'.$e->getMessage();
            return $report;
        }
    }

    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_list_remark($params)
    {
        $this->_ci->load->model('Fba_pr_list_remark_model', 'fba_pr_list_remark', false, 'fba');
        append_login_info($params);
        $insert_params = $this->_ci->m_fba_activity_remark->fetch_table_cols($params);
        return $this->_ci->m_fba_activity_remark->add($insert_params);
    }

    //activity_state
    public function batch_discard($post)
    {
        $report = [
            'total'      => count($post['id']),
            'processed'  => 0,
            'undisposed' => count($post['id']),
            'errorMess'  => ''
        ];
        $active_user = get_active_user();
        $salesman = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? '*' : $active_user->staff_code;
        //$manager_accounts = $active_user->get_my_manager_accounts();
        $account_nums = $active_user->get_my_manager_account_nums();
        $valid_ids = $this->_ci->m_fba_activity->get_can_discard($post['id'], $salesman, $account_nums);

        if (empty($valid_ids)) {
            $report['errorMess'] = '没有有效的作废记录';
            return $report;
        }

        $updated_at =  date('Y-m-d H:i:s');
        $updated_uid = $active_user->staff_code;
        $updated_zh_name  = $active_user->user_name;

        $batch_update_pr = $batch_insert_log = [];

        foreach ($valid_ids as $id)
        {
            $batch_update_pr[] = [
                'id' => $id,
                'activity_state' => ACTIVITY_STATE_DISCARD,
                'updated_uid' => $active_user->staff_code,
                'updated_at' => $updated_at,
                'updated_zh_name' => $updated_zh_name
            ];
            $batch_insert_log[] = [
                    'gid' => $id,
                    'uid' => $updated_uid,
                    'user_name' => $updated_zh_name,
                    'context' => '活动操作作废',
            ];
        }

        $this->_ci->load->model('Fba_activity_log_model', 'fba_activity_log', false, 'fba');

        $db = $this->_ci->m_fba_activity->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->m_fba_activity->getTable(), $batch_update_pr, 'id');
            if (!$update_rows)
            {
                $report['errorMess'] = '批量作废 更新失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->fba_activity_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $report['errorMess'] = '日志批量插入失败';
                throw new \RuntimeException($report['errorMess']);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '批量作废，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $update_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('批量作废更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = $records = null;
            unset($batch_update_pr, $batch_insert_log, $records);

            $report['errorMess'] = '批量作废抛出异常：'.$e->getMessage();
            return $report;
        }
    }

    public function batch_approve($post)
    {
        $report = [
                'total'      => count($post['id']),
                'processed'  => 0,
                'undisposed' => count($post['id']),
                'errorMess'  => ''
        ];
        $active_user = get_active_user();
        $salesman = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? '*' : $active_user->staff_code;
        //$manager_accounts = $active_user->get_my_manager_accounts();
        $account_nums = $active_user->get_my_manager_account_nums();
        $valid_ids = $this->_ci->m_fba_activity->get_can_approve($post['id'], $salesman, $account_nums);

        if (empty($valid_ids)) {
            $report['errorMess'] = '没有有效的审核记录';
            return $report;
        }

        $approve_state = intval($post['result']);
        $updated_at =  date('Y-m-d H:i:s');
        $updated_uid = $active_user->staff_code;
        $updated_zh_name  = $active_user->user_name;

        $batch_update_pr = $batch_insert_log = [];

        foreach ($valid_ids as $id)
        {
            $batch_update_pr[] = [
                    'id' => $id,
                    'approve_state' => $approve_state,
                    'updated_uid' => $active_user->staff_code,
                    'updated_at' => $updated_at,
                    'updated_zh_name' => $updated_zh_name,
                    'approved_uid' => $active_user->staff_code,
                    'approved_at' => $updated_at,
                    'approved_zh_name' => $updated_zh_name,
            ];
            $batch_insert_log[] = [
                    'gid' => $id,
                    'uid' => $updated_uid,
                    'user_name' => $updated_zh_name,
                    'context' => '活动审核'.($approve_state == ACTIVITY_APPROVAL_FAIL ? '失败' : '成功'),
            ];
        }

        $this->_ci->load->model('Fba_activity_log_model', 'fba_activity_log', false, 'fba');

        $db = $this->_ci->m_fba_activity->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->m_fba_activity->getTable(), $batch_update_pr, 'id');
            if (!$update_rows)
            {
                $report['errorMess'] = '批量审核 更新失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->fba_activity_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $report['errorMess'] = '批量审核日志插入失败';
                throw new \RuntimeException($report['errorMess']);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '批量审核，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $update_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('批量审核更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = $records = null;
            unset($batch_update_pr, $batch_insert_log, $records);

            $report['errorMess'] = '批量审核抛出异常：'.$e->getMessage();
            return $report;
        }
    }

    public function import($params)
    {
        $this->_ci->load->classes('fba/classes/CsvWrite', BUSINESS_LINE_FBA);
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->load->model('Fba_activity_log_model', 'fba_activity_log', false, 'fba');

        //解析格式，获得数据
        $this->_ci->CsvWrite->decode_csv_reader($params);
        if ($this->_ci->CsvWrite->get_mode() == 'update')
        {
            $this->_ci->CsvWrite
            ->bind_filter_cvs_rows(array($this->_ci->m_fba_activity, 'get_can_update'), $this->_ci->CsvWrite)
            ->set_can_edit_cols(['amount', 'activity_name', 'execute_purcharse_time', 'activity_start_time', 'activity_end_time', 'remark'])
            ->register_columns_recalc_callback('updated_at', function($new_row, $old_row){
                return date('Y-m-d H:i:s');
            })
            ->register_columns_recalc_callback('updated_uid', function($new_row, $old_row){
                return get_active_user()->staff_code;
            })
            ->register_columns_recalc_callback('updated_zh_name', function($new_row, $old_row){
                return get_active_user()->user_name;
            })
            ;
        }
        else
        {
            $this->_ci->CsvWrite
            ->bind_filter_cvs_rows(array($this->_ci->m_fba_activity, 'get_can_insert'), $this->_ci->CsvWrite)
            ->register_callback('before',
                //构造insert数据
                function($csvwrite) {

                    $index_to_col = $csvwrite->property('index_to_col');
                    $active_user = $csvwrite->property('active_user');

                    $now_timestamp = date('Y-m-d H:i:s');
                    $this->_ci->load->model('Fba_amazon_group_model', 'm_amazon_group', false, 'fba');
                    $group_map = $this->_ci->m_amazon_group->get_group_list();
                    $group_map = array_flip($group_map);
                    $this->_ci->load->service('basic/DropdownService');
                    $fba_salesman = $this->_ci->dropdownservice->dropdown_fba_salesman();              //销售人员
                    $fba_salesman = array_flip($fba_salesman);

                    $line_index_name = $csvwrite->get_line_index_name();

                    foreach ($csvwrite->valid_records as $aggr_id => $csv_rows)
                    {
                        foreach ($csv_rows as $csv_row)
                        {
                            $csvwrite->report['succLines'][] = $csv_row[$csvwrite->get_line_index_name()];

                            $one_row = [];
                            foreach ($csv_row as $index => $val)
                            {
                                if (is_numeric($index) && isset($index_to_col[$index])) {
                                    $one_row[$index_to_col[$index]] = $val;
                                }
                            }

                            $one_row['aggr_id'] = $aggr_id;
                            $one_row['salesman'] = $fba_salesman[$one_row['salesman']] ?? '';
                            $one_row['staff_zh_name'] = $active_user->user_name;
                            $one_row['created_at'] = $now_timestamp;
                            $one_row['sale_group'] = $group_map[$one_row['sale_group']] ?? 0;  //销售小组
                            $one_row['eu_tag'] = isset($one_row['is_pan_eu']) && $one_row['is_pan_eu'] == 'Y' ? $one_row['eu_tag'] : '';
                            $csvwrite->batch_insert[] = $one_row;
                        }
                    }

                    return count($csvwrite->batch_insert);
                },
                $this->_ci->CsvWrite
            )
            ;
        }

        //前置回调
        //->register_columns_recalc_callback('fixed_amount', function(){})
        //设置语言文件
        $this->_ci->CsvWrite->set_langage('fba_activity')
        //设置更新表model
        ->set_model($this->_ci->m_fba_activity)
        //日志model
        ->set_log_model($this->_ci->fba_activity_log);

        unset($params);

        $this->_ci->CsvWrite->run();

        return $this->_ci->CsvWrite->report;
    }

    /**
     * 判断操作的截止时间
     *
     * @return true
     * @throws \OutOfRangeException
     */
    public function check_enable_time($operate = '')
    {
        $register_operate = [
            'batch_approve',
            'batch_discard',
        ];
        if ($operate == '' || !in_array(strtolower($operate), $register_operate))
        {
            return true;
        }
        list($start, $end) = explode('~', PLAN_EDIT_PUSH_CLOSE_TIME);
        $now = date('H:i');
        $disabled = $now >= $start && $now <= $end;
        if ($disabled)
        {
            throw new \OutOfRangeException('备货列表生成中，请0点后再审核', 500);
        }
        return true;
    }
}
