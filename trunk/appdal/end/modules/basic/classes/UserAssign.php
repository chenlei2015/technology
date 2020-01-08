<?php

include_once dirname(__FILE__).'/User.php';

/**
 * 权限分配
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw
 */
class UserAssign
{
    /**
     * 类格式化后参数
     * [uid]
     *   ->[bussiness_line]
     *     ->[]
     *
     * @var array
     */
    private $_params = [];
    
    /**
     * 新增的， uid 为 key
     * @var array
     */
    private $_batch_config_insert = [];
    
    /**
     * 列表新增  uid 为 key
     *
     * @var array
     */
    private $_batch_list_insert = [];
    
    /**
     * 更新  uid 为 key
     * @var array
     */
    private $_batch_config_update = [];
    
    /**
     * 插入新的日志
     * @var array
     */
    private $_batch_log_insert = [];
    
    /**
     * 执行报告
     * @var unknown
     */
    private $_report = [];
    
    /**
     * 需要重置的用户id
     *
     * @var array
     */
    private $_reset_staff_code = [];
    
    /**
     * 是否参数准备就绪，可以执行插入
     *
     * @var string
     */
    private $_is_separated = false;
    
    /**
     * 当前操作用户信息
     *
     * @var unknown
     */
    private $_op_user_info;

    /**
     * 事务由内部还是外部设置
     *
     * @var string
     */
    private $_out_trans = false;
    
    /**
     * 默认来源是FBA
     * @var string
     */
    private $_set_from = BUSSINESS_FBA;
    
    private $_ci;
    
    
    public function __construct()
    {
        $this->_init();
    }
    
    /**
     * 初始化
     */
    private function _init()
    {
        $this->_ci = &get_instance();
        $this->_ci->load->model('User_config_model', 'm_user_config', false, 'basic');
        $this->_ci->load->model('User_config_list_model', 'm_user_config_list', false, 'basic');
        $this->_ci->load->model('User_config_log_model', 'm_user_config_log', false, 'basic');
        //获取登录用户信息
        $this->_op_user_info = get_active_user()->get_user_info();
    }
    
    /**
     * 检测两个数组是否一致
     *
     * @param unknown $arr1
     * @param unknown $arr2
     * @return boolean
     */
    private function _is_change($arr1, $arr2)
    {
        ksort($arr1);
        ksort($arr2);
        return md5(json_encode($arr1)) == md5(json_encode($arr2));
    }
    
    /**
     * 生成全新插入的log日志
     *
     * @param unknown $items
     * @return string
     */
    private function _general_new_context($items)
    {
        $log_message = '新增用户配置：';
        $buss_msg = '业务线：%s, 数据权限：%s, 审核权限：%s, ';
        foreach ($items as $it)
        {
            $level = [];
            $buss_name = BUSSINESS_LINE[$it['bussiness_line']]['name'];
            $data_name = DATA_PRIVILEGE[$it['data_privilege']]['name'];
            $it['has_first'] == GLOBAL_YES && $level[] =  '一级审核';
            $it['has_second'] == GLOBAL_YES && $level[] =  '二级审核';
            $it['has_three'] == GLOBAL_YES && $level[] =  '三级审核';
            sort($level);
            $log_message .= sprintf($buss_msg, $buss_name, $data_name, implode(',', $level));
        }
        return $log_message;
    }
    
    /**
     * 生成更新日志
     *
     * @param unknown $items
     * @return string
     */
    private function _general_update_context($buss_line, $items)
    {
        $log_message = sprintf('修改%s业务线：', BUSSINESS_LINE[$buss_line]['name']);
        if (isset($items['data_privilege']))
        {
            $log_message .= sprintf('数据权限：%s->%s；', DATA_PRIVILEGE[$items['data_privilege']['before']]['name'], DATA_PRIVILEGE[$items['data_privilege']['after']]['name']);
        }
        unset($items['data_privilege']);
        if ($items)
        {
            //数据权限
            $log_message .= '数据权限：';
            $col_name_map = [
                    'has_first' => '一级审核',
                    'has_second' => '二级审核',
                    'has_three' => '三级审核',
            ];
            foreach ($items as $col => $it)
            {
                $before = $it['before'] == GLOBAL_YES ? '开启' : '取消';
                $after = $it['after'] == GLOBAL_YES ? '开启' : '取消';
                $log_message .= sprintf('%s:%s->%s ', $col_name_map[$col], $before, $after);
            }
        }
        return $log_message;
    }
    
    /**
     * 接收表单参数做格式化
     */
    public function recive($form_params)
    {
        //根据用户uid和bussiness_type分组
        foreach ($form_params as $par)
        {
            if (!is_array($par))
            {
                continue;
            }
            $priv_items = $prv_value = [];
            if (array_key_exists('check_privilege', $par))
            {
                $prv_value = explode(',', $par['check_privilege']);
                unset($par['check_privilege']);
            }
            
            foreach ($this->_ci->m_user_config::$s_map_value as $col => $val)
            {
                $priv_items[$col] = in_array($val['val'], $prv_value) ? GLOBAL_YES : GLOBAL_NO;
            }
            $params[$par['staff_code']][$par['bussiness_line']] = array_merge($par, $priv_items);
        }
        
        $this->_params = $params;
        //检测用户
        $unfound_reset_privilege = true;
        $staff_codes = array_keys($this->_params);
        if ($diff = $this->_check_staff_exists($staff_codes))
        {
            if ($unfound_reset_privilege)
            {
                $this->_reset_staff_code = $diff;
            }
            throw new \InvalidArgumentException(sprintf('获取不到用户ID：%s的信息，该用户可能不存在或者禁用', implode(',', $diff)));
        }
        
        return $this;
    }
    
    /**
     * 检测uid，返回不存在的用户uid
     *
     * @param unknown $uids
     * @return unknown|array
     */
    private function _check_staff_exists($staff_codes)
    {
        $list = RPC_CALL('YB_J1_005', $staff_codes);
        if (!$list)
        {
            log_message('ERROR', sprintf('获取用户工号:%s 信息接口返回无效数据', implode(',', $staff_codes)));
            return $uids;
        }
        $exists = array_filter(array_column($list, 'isDel', 'userNumber'), function($val) { return $val == 0;});
        if (empty($exists))
        {
            return $staff_codes;
        }
        return array_diff($staff_codes, array_keys($exists));
    }
    
    /**
     * 由外部控制事务
     *
     * @return UserAssign
     */
    public function set_out_trans()
    {
        $this->_out_trans = true;
        return $this;
    }
    
    public function set_from($business_line)
    {
        $this->_set_from = $business_line;
        return $this;
    }
    
    /**
     * 将待操作的数据分离到各自的数组中
     *
     */
    public function separate()
    {
        if (empty($this->_params))
        {
            throw new \RuntimeException('请先设置参数', 500);
        }
        $staff_codes = array_keys($this->_params);
        $exists_config = [];
        $exists_list = key_by($this->_ci->m_user_config_list->get($staff_codes), 'staff_code');
        foreach ($this->_ci->m_user_config->get_list_by_staff_codes($staff_codes) as $key => $row)
        {
            $exists_config[$row['staff_code']][$row['bussiness_line']] = $row;
        }
        foreach ($this->_params as $staff => $buss_lines)
        {
            //全新新增
            $uid_record = $exists_list[$staff] ?? [];
            if (empty($uid_record))
            {
                $this->_separate_new_insert($staff, $buss_lines);
                continue;
            }
            //检测是否新增了业务线或者变化的部分
            $this->_separate_new_lines_or_update($staff, $buss_lines, $exists_config[$staff], $exists_list[$staff]);
        }
        $this->_is_separated = true;
        return $this;
    }
    
    /**
     * 执行更新
     */
    public function update()
    {
        if (!$this->_is_separated)
        {
            throw new \RuntimeException('请先执行separate方法', 500);
        }
        
        $db = $this->_ci->m_user_config->getDatabase();
        try
        {
            $nothing_execute = true;
            
            !$this->_out_trans && $db->trans_start();
            
            if (!empty($this->_batch_list_insert))
            {
                $nothing_execute = false;
                $succ_rows = $db->insert_batch($this->_ci->m_user_config_list->getTable(), $this->_batch_list_insert);
                if ($succ_rows == 0)
                {
                    $message = '批量插入用户流水列表失败，返回影响行数0';
                    log_message('ERROR', sprintf('批量插入用户流水列表失败，返回影响行数0, 数据：%s', json_encode($this->_batch_list_insert)));
                    throw new \RuntimeException($message, 500);
                }
            }
            
            if (!empty($this->_batch_config_insert))
            {
                $nothing_execute = false;
                $succ_rows = $db->insert_batch($this->_ci->m_user_config->getTable(), $this->_merge_into_batch($this->_batch_config_insert));
                if ($succ_rows == 0)
                {
                    $message = '批量插入用户配置表失败，返回影响行数0';
                    log_message('ERROR', sprintf('批量插入用户配置表失败，返回影响行数0, 数据：%s', json_encode($this->_batch_config_insert)));
                    throw new \RuntimeException($message, 500);
                }
                //插入记录
                foreach ($this->_batch_config_insert as $staff => $row)
                {
                    $this->_report[$staff]['insert'] = count($row);
                }
            }
            
            if (!empty($this->_batch_config_update))
            {
                $nothing_execute = false;
                foreach ($this->_batch_config_update as $staff => $infos)
                {
                    foreach ($infos as $info)
                    {
                        $affect_rows = $db->update($this->_ci->m_user_config->getTable(), $info['update'], $info['where']);
                    }
                }
                //更新列表记录
                $update_config_list = [
                        'updated_at' => date('Y-m-d H:i:s'),
                        'op_uid' => $this->_op_user_info['oa_info']['userNumber'],
                        'op_zh_name' => $this->_op_user_info['oa_info']['userName']
                ];
                $db->reset_query();
                $db->where_in('staff_code', array_keys($this->_batch_config_update));
                $db->update($this->_ci->m_user_config_list->getTable(), $update_config_list);
                
                //插入记录
                foreach ($this->_batch_config_update as $staff => $row)
                {
                    $this->_report[$staff]['update'] = count($row);
                }
            }
            
            if (!empty($this->_batch_log_insert))
            {
                $nothing_execute = false;
                $succ_rows = $db->insert_batch($this->_ci->m_user_config_log->getTable(), $this->_merge_into_batch($this->_batch_log_insert));
                if ($succ_rows == 0)
                {
                    $message = '批量插入用户日志表失败，返回影响行数0';
                    log_message('ERROR', sprintf('批量插入用户日志表失败，返回影响行数0, 数据：%s', json_encode($this->_batch_config_insert)));
                    throw new \RuntimeException($message, 500);
                }
            }
            
            !$this->_out_trans && $db->trans_complete();
            
            if (!$nothing_execute && !$this->_out_trans && $db->trans_status() === FALSE)
            {
                throw new \RuntimeException(sprintf('设置用户权限事务提交完成，但检测状态为false'), 500);
            }
            
            return true;
        }
        catch (\Exception $e)
        {
            log_message('ERROR', sprintf('设置用户权限事务抛出异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException('设置用户权限失败，请重试', 500);
        }
        catch(\Throwable $e)
        {
            log_message('ERROR', sprintf('设置用户权限事务抛出异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException('设置用户权限失败，请重试', 500);
        }
    }
    
    /**
     * 报告
     *
     * @param string $staff
     */
    public function report($staff = '')
    {
        return $this->_report;
    }
    
    public function __destruct()
    {
        $this->_params = [];
        $this->_batch_config_insert = [];
        $this->_batch_list_insert = [];
        $this->_batch_config_update = [];
        $this->_batch_log_insert = [];
    }
    
    
    /**
     * 多个记录合并为一个insert
     *
     * @param unknown $arr
     * @return unknown[]
     */
    private function _merge_into_batch($arr)
    {
        $merge_arr = [];
        foreach($arr as $staff => $rows)
        {
            foreach ($rows as $row)
            {
                $merge_arr[] = $row;
            }
        }
        return $merge_arr;
    }
    
    private function _separate_new_lines_or_update($staff, $buss_lines, $exists_buss_lines, $list_row)
    {
        $date = date('Y-m-d H:i:s');
        
        foreach ($buss_lines as $buss_type => $row)
        {
            //已经存在
            if (isset($exists_buss_lines[$buss_type]))
            {
                if ($this->_is_change($row, $exists_buss_lines[$buss_type]))
                {
                    //记录日志
                    continue;
                }
                else
                {
                    //检测是否更新
                    $where = ['staff_code' => $staff, 'bussiness_line' => $buss_type];
                    $updat_cols = array_diff_assoc($row, $exists_buss_lines[$buss_type]);
                    if (empty($updat_cols))
                    {
                        //没有更新
                        continue;
                    }
                    $this->_batch_config_update[$staff][] = ['where' => $where, 'update' => $updat_cols];
                    foreach ($updat_cols as $col => $val)
                    {
                        $modify_uid_row[$col] = ['after' => $val, 'before' => $exists_buss_lines[$buss_type][$col]];
                    }
                    //插入一条日志
                    $log_params = [
                            'gid' => $list_row['gid'],
                            'uid' => $this->_op_user_info['oa_info']['userNumber'],
                            'user_name' => $this->_op_user_info['oa_info']['userName'],
                            'context' => $this->_general_update_context($buss_type, $modify_uid_row),
                            'created_at' => $date
                    ];
                    $this->_batch_log_insert[$staff][] = $log_params;
                }
            }
            else
            {
                $this->_batch_config_insert[$staff][$buss_type] = $row;
                //插入一条日志
                $log_params = [
                        'gid' => $list_row['gid'],
                        'uid' => $this->_op_user_info['oa_info']['userNumber'],
                        'user_name' => $this->_op_user_info['oa_info']['userName'],
                        'context' => $this->_general_new_context([$row]),
                        'created_at' => $date
                ];
                $this->_batch_log_insert[$staff][] = $log_params;
            }
        }
    }
    
    private function _separate_new_insert($staff_code, $buss_lines)
    {
        $date = date('Y-m-d H:i:s');
        //获取登录用户信息
        $this->_op_user_info = get_active_user()->get_user_info();
        //是否全新新增, 批量插入
        //$rows = $db->insert_batch($this->_ci->m_user_config->getTable(), $buss_lines);
        $this->_batch_config_insert[$staff_code] = $buss_lines;
        //插入一条配置记录
        $list_params = [
                'gid' => $this->_ci->m_user_cfg_list->gen_id(),
                'staff_code' => $staff_code,
                'remark' => '',
                'created_at' => $date,
                'updated_at' => $date,
                'op_uid' => $this->_op_user_info['oa_info']['userNumber'],
                'op_zh_name' => $this->_op_user_info['oa_info']['userName'],
        ];
        //插入一条日志
        $log_params = [
                'gid' => $list_params['gid'],
                'uid' => $this->_op_user_info['oa_info']['userNumber'],
                'user_name' => $this->_op_user_info['oa_info']['userName'],
                'context' => $this->_general_new_context($buss_lines),
                'created_at' => $date
        ];
        $this->_batch_list_insert[$staff_code] = $list_params;
        $this->_batch_log_insert[$staff_code][] = $log_params;
    }
    
    
    
}