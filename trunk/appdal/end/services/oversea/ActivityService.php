<?php

/**
 * INLAND 活动需求服务
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
    public static $s_system_log_name = 'OVERSEA';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_activity_list_model', 'm_oversea_activity', false, 'inland');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }

    //activity_state
    public function update_remark($post)
    {
        $report = [
            'data' => false,
            'errorMess'  => ''
        ];

        $record = $this->_ci->m_oversea_activity->pk($post['id']);
        if (empty($record))
        {
            $report['errorMess'] = '无效的记录';
            return $report;
        }

        $active_user = get_active_user();

        if ($post['remark'] == $record['remark'])
        {
            $report['errorMess'] = '备注相同，没有任何修改';
            return $report;
        }

        $updated_at =  date('Y-m-d H:i:s');
        $updated_uid = $active_user->staff_code;
        $updated_zh_name  = $active_user->user_name;

        $batch_update_ac = $batch_insert_log = [];

        $batch_update_ac[] = [
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

        $this->_ci->load->model('Oversea_activity_log_model', 'oversea_activity_log', false, 'oversea');

        $db = $this->_ci->m_oversea_activity->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->m_oversea_activity->getTable(), $batch_update_ac, 'id');
            if (!$update_rows)
            {
                $report['errorMess'] = '添加备注失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->oversea_activity_log->madd($batch_insert_log);
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
            unset($batch_update_ac, $batch_insert_log);

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
        $this->_ci->load->model('Inland_activity_remark_model', 'm_inland_activity_remark', false, 'inland');
        append_login_info($params);
        $insert_params = $this->_ci->m_inland_activity_remark->fetch_table_cols($params);
        return $this->_ci->m_inland_activity_remark->add($insert_params);
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
        $valid_ids = $this->_ci->m_oversea_activity->get_can_discard($post['id']);

        if (empty($valid_ids)) {
            $report['errorMess'] = '没有有效的作废记录';
            return $report;
        }

        $updated_at =  date('Y-m-d H:i:s');
        $updated_uid = $active_user->staff_code;
        $updated_zh_name  = $active_user->user_name;

        $batch_update_ac = $batch_insert_log = [];

        foreach ($valid_ids as $id)
        {
            $batch_update_ac[] = [
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

        $this->_ci->load->model('Oversea_activity_log_model', 'oversea_activity_log', false, 'oversea');

        $db = $this->_ci->m_oversea_activity->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->m_oversea_activity->getTable(), $batch_update_ac, 'id');
            if (!$update_rows)
            {
                $report['errorMess'] = '批量作废 更新失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->oversea_activity_log->madd($batch_insert_log);
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
            unset($batch_update_ac, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('批量作废更新%s，提交事务出现异常: %s', json_encode($batch_update_ac), $e->getMessage()));

            unset($batch_update_ac, $batch_insert_log, $records);

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
        $valid_ids = $this->_ci->m_oversea_activity->get_can_approve($post['id']);

        if (empty($valid_ids)) {
            $report['errorMess'] = '没有有效的审核记录';
            return $report;
        }
        $report['total'] = count($post['id']) > count($valid_ids) ? count($post['id']) : count($valid_ids);
        $approve_state = intval($post['result']);
        $updated_at =  date('Y-m-d H:i:s');
        $updated_uid = $active_user->staff_code;
        $updated_zh_name  = $active_user->user_name;

        $batch_update_ac = $batch_insert_log = [];

        foreach ($valid_ids as $id)
        {
            $batch_update_ac[] = [
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

        $this->_ci->load->model('Oversea_activity_log_model', 'oversea_activity_log', false, 'oversea');

        $db = $this->_ci->m_oversea_activity->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->m_oversea_activity->getTable(), $batch_update_ac, 'id');
            if (!$update_rows)
            {
                $report['errorMess'] = '批量审核 更新失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->oversea_activity_log->madd($batch_insert_log);
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
            unset($batch_update_ac, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('批量审核更新%s，提交事务出现异常: %s', json_encode($batch_update_ac), $e->getMessage()));

            unset($batch_update_ac, $batch_insert_log, $records);

            $report['errorMess'] = '批量审核抛出异常：'.$e->getMessage();
            return $report;
        }
    }

    public function import($params)
    {
        $this->_ci->load->classes('fba/classes/CsvWrite', BUSINESS_LINE_OVERSEA);
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->load->model('Oversea_activity_log_model', 'oversea_activity_log', false, 'oversea');

        //解析格式，获得数据
        $this->_ci->CsvWrite->decode_csv_reader($params);
        if ($this->_ci->CsvWrite->get_mode() == 'update')
        {
            $this->_ci->CsvWrite
                ->bind_filter_cvs_rows(array($this->_ci->m_oversea_activity, 'get_can_update'), $this->_ci->CsvWrite)
                ->set_can_edit_cols(['amount', 'activity_name', 'execute_purcharse_time', 'activity_start_time', 'activity_end_time', 'remark']);
        }
        else
        {
            $this->_ci->CsvWrite
                ->bind_filter_cvs_rows(array($this->_ci->m_oversea_activity, 'get_can_insert'), $this->_ci->CsvWrite)
                ->register_callback('before',
                    //构造insert数据
                    function($csvwrite) {

                        $index_to_col = $csvwrite->property('index_to_col');
                        $active_user = $csvwrite->property('active_user');

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
                                $one_row['created_zh_name'] = $active_user->user_name;
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
        $this->_ci->CsvWrite->set_langage('oversea_activity')
            //设置更新表model
            ->set_model($this->_ci->m_oversea_activity)
            //日志model
            ->set_log_model($this->_ci->oversea_activity_log);

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

    /**
     * 检测海外活动配置表创建活动参数并插入
     */
    public function check_param_insert($param = array(),$user_id = '', $user_name = ''){
        //必须检测参数
        $requir_cols = array('station_code','platform_code','skus','activity_title','amount','execute_purcharse_time','activity_start_time','activity_end_time');
        foreach ($requir_cols as $v){
            if (!isset($param[$v]) || $param[$v] == ""){
                throw new \InvalidArgumentException('存在无效的参数', 412);
            }
        }
        if (!is_numeric($param['amount'])){
            throw new \InvalidArgumentException('活动量参数格式错误', 412);
        }
        $execute_purcharse_timestamp = strtotime($param['execute_purcharse_time']);
        $activity_start_timestamp = strtotime($param['activity_start_time']);
        $activity_end_timestamp = strtotime($param['activity_end_time']);
        //开始备货时间， 不能早于明天
        if (($execute_purcharse_timestamp < strtotime(date('Y-m-d')) + 86400) || ($execute_purcharse_timestamp > $activity_start_timestamp)){
            throw new \InvalidArgumentException('备货开始时间格式不对', 412);
        }
        if (($activity_end_timestamp < strtotime(date('Y-m-d')) + 86400) || $activity_end_timestamp < $activity_start_timestamp){
            throw new \InvalidArgumentException('活动结束时间不对', 412);
        }

        //整理数据插入数据
        $data = [];
        $sku_arr = explode(',',$param['skus']);
        foreach ($sku_arr as $v){
            $data[] = array(
                'station_code'                  =>  $param['station_code'],
                'platform_code'                 =>  $param['platform_code'],
                'erpsku'                        =>  $v,
                'activity_title'                =>  $param['activity_title'],
                'amount'                        =>  $param['amount'],
                'execute_purcharse_time'        =>  $param['execute_purcharse_time'],
                'activity_start_time'           =>  $param['activity_start_time'],
                'activity_end_time'             =>  $param['activity_end_time'],
                'created_uid'                   =>  $user_id,
                'created_zh_name'               =>  $user_name,
            );
        }
        //插入数据
        $insert_res = $this->_ci->m_oversea_activity->batchInsert($data);
        if (!$insert_res){
            throw new \InvalidArgumentException('新建活动失败', 412);
        }
    }
}
