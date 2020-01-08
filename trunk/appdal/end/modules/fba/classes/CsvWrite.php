<?php

/**
 * 根据csv进行更新, 格式统一来自CsvReader
 *
 * Array (
        [primary_key] => pr_sn
        [map] => Array
            (
                [pr_sn] => 0 //索引位置
                [fixed_amount] => 10 //索引位置
            )

        [selected] => {"FSS190827AC93421":{"10":"20"},"FSS190805AA12822":{"10":"0"},"FSS190719AE01883":{"10":"0"}}
        [total] => 3
    )

    Array (
    [FSS190827AC93421] => Array
        (
            [10] => 20  //位置 ， value
        )
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw
 */
class CsvWrite
{

    /**
     * @var CI
     */
    private $ci;

    /**
     * 事务由外部控制
     *
     * @var bool
     */
    protected $tran_start_out = false;

    /**
     * 前置、后置 回调
     *
     * @var array
     */
    protected $inspect_callback = ['before' => [], 'after' => []];

    /**
     * 列回调
     *
     * @var array
     */
    protected $column_callback = [];

    /**
     * 关联事务处理
     *
     * @var array
     */
    protected $tran_callback = [];

    /**
     * @var string
     */
    protected $errorMess = '';

    /**
     * @var array
     */
    protected $langs;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var Model
     */
    protected $log_model;

    /**
     * 日志回调
     * @var unknown
     */
    protected $log_func;

    /**
     * 位置与列字段的关联数组， selected存位置和值 （不存字段名）
     *
     * Array
        (
            [0] => pr_sn
            [10] => fixed_amount
        )
     * @var array
     */
    protected $index_to_col = [];

    /**
     * 行号索引
     * @var string
     */
    protected $line_index_name = 'line_index';

    /**
     * @var array
     */
    public $batch_insert = [];



    /**
     * @var array
     */
    public $batch_insert_log = [];


    /**
     * 提交需要变更的数据 （含必更和选更字段），主键作为key
     *
     * Array
     (
         [FSS190827AC93421] => Array
        (
            [10] => 20
        )
     *
     * @var array
     */
    public $selected = [];


    /**
     * 有效记录
     *
     * @var array
     */
    public $valid_records = [];

    /**
     * 业务线
     *
     * @var int
     */
    protected $business_line;

    /**
     * @var User
     */
    protected $active_user;

    /**
     * @var update|insert
     */
    protected $mode = 'update';

    /**
     * 记录能唯一确定的列
     *
     * @var string
     */
    public $primary_key;

    /**
     * 要更新的列
     *
     * @var array
     */
    public $modifiy_cols;

    /**
     * @var integer
     */
    public $batch_size = 500;

    /**
     * 执行报表
     *
     * @var array
     */
    public $report = [
            'total'      => 0,
            'processed'  => 0,
            'undisposed' => 0,
            'errorMess'  => '',
            'succLines' => []
    ];

    public function __construct($business_line)
    {
        $this->business_line = $business_line;
        $this->init_env_resource();
    }

    /**
     * 设置使用资源
     *
     * @return CsvReader
     */
    protected function init_env_resource() : CsvWrite
    {
        set_time_limit(-1);

        setlocale(LC_ALL, 'zh_CN');

        $this->ci = CI::$APP;

        $this->active_user = get_active_user();

        return $this;
    }

    /**
     * 初始化权限
     *
     * @param int $business_line
     */
    protected function init_privileges($business_line)
    {
        switch ($business_line)
        {
            case BUSSINESS_FBA;
                $privilege_scope = $this->active_user->has_all_data_privileges($business_line) ? '*' : $this->active_user->staff_code;
                $manager_accounts = $privilege_scope != '*' ? $this->active_user->get_my_manager_accounts() : [];
            break;
            case BUSSINESS_OVERSEA;
            break;
            case BUSSINESS_IN:
                //国内仓是菜单权限
                $privilege_scope = '*';
                $manager_accounts = [];
            break;
        }

        return [$privilege_scope, $manager_accounts];
    }

    /**
     * 解析csv结构
     *
     * @param unknown $post
     * @throws \InvalidArgumentException
     * @return CsvWrite
     */
    public function decode_csv_reader($post) : CsvWrite
    {
        $requir_cols = array_flip(['primary_key', 'map', 'selected']);
        if (count(array_diff_key($requir_cols, $post)) > 0 )
        {
            $this->errorMess = '无效的参数';
            throw new \InvalidArgumentException('无效的参数', 412);
        }

        $this->index_to_col = array_flip($post['map']);
        $this->selected = json_decode($post['selected'], true);
        $this->mode = $post['mode'] ?? 'update';

        $this->primary_key = $post['primary_key'];
        $this->modifiy_cols = $this->mode == 'insert' ? array_keys($post['map']) : [];
        unset($this->modifiy_cols[$this->primary_key]);

        $this->report['total'] = $post['total'];

        //设置report
        $this->report['undisposed'] = $this->report['total'];

        return $this;
    }

    /**
     * 设置可以修改的字段
     *
     * @param array $cols
     * @return CsvWrite
     */
    public function set_can_edit_cols(array $cols)
    {
        $this->modifiy_cols = $cols;
        return $this;
    }

    /**
     * 解析其他用户定义数据结构
     *
     * @param unknown $post
     * @return CsvWrite
     */
    public function decode_user_define_reader($post) : CsvWrite
    {
        return $this;
    }

    /**
     * 获取模式
     *
     * @return update|insert
     */
    public function get_mode()
    {
        return $this->mode;
    }

    /**
     * @return string
     */
    public function get_line_index_name()
    {
        return $this->line_index_name;
    }


    /**
     * 注册前置、后置回调
     *
     * @param string $inspect_point before|after
     * @param array|callable $cb
     * @return CsvWrite
     */
    public function register_callback($inspect_point, $cb, $params = [])
    {
        $this->inspect_callback[$inspect_point][] = ['cb' => $cb, 'params' => $params];

        return $this;
    }

    /**
     * 注册事务中关联要处理的记录
     *
     * @param string $tran_name
     * @param callable $cb
     * @param array $params
     * @return CsvWrite
     */
    public function set_tran_relation_func(string $tran_name, callable $cb, $params = [])
    {
        $this->tran_callback[$tran_name] = ['cb' => $cb, 'params' => $params];

        return $this;
    }


    /**
     * 列回调
     * @param string $column
     * @param callable $cb
     */
    public function register_columns_recalc_callback($column, $cb, $params = [])
    {
        $this->column_callback[$column] = ['cb' => $cb, 'params' => $params];
        return $this;
    }

    /**
     * 语言索引
     *
     * @param string $lang_index
     */
    public function set_langage($lang_index)
    {
        $this->ci->lang->load('common');
        $all_lines = $this->ci->lang->line($lang_index);

        if (!empty($this->modifiy_cols)) {
            foreach ($all_lines as $line) {
                if (isset($line['field']) && isset($line['label']) && in_array($line['field'], $this->modifiy_cols)) {
                    $this->langs[$line['field']] = $line['label'];
                }
            }
        }

        foreach ($this->modifiy_cols as $col)
        {
            if (!isset($this->langs[$col])) {
                $this->langs[$col] = $col;
            }
        }
        return $this;
    }

    /**
     * @param Model $model
     * @return CsvWrite
     */
    public function set_model($model)
    {
        $this->model = $model;
        return $this;
    }

    /**
     * @param Model $log_model
     * @return CsvWrite
     */
    public function set_log_model($log_model)
    {
        $this->log_model = $log_model;
        return $this;
    }

    public function set_log_func(callable $cb)
    {
        $this->log_func = $cb;
        return $this;
    }

    /**
     * 设置外部控制事务
     */
    public function enable_out_control_trans()
    {
        $this->tran_start_out = true;
    }

    /**
     * 过滤返回可修改记录
     *
     * @param array $modle_filter_method array($object, 'method')...
     * @param mixed $params
     * @return CsvWrite
     */
    public function bind_filter_cvs_rows($modle_filter_method, $parmas = null)
    {
        list($privilege_scope, $accounts) = $this->init_privileges($this->business_line);

        $valid_records = call_user_func_array(
            $modle_filter_method,
            null == $parmas ? array(array_keys($this->selected), $privilege_scope, $accounts) : array(array_keys($this->selected), $privilege_scope, $accounts, $parmas)
        );
        $this->valid_records = $valid_records;
        if (empty($this->valid_records))
        {
            $this->errorMess = '没有有效可操作记录';
        }
        return $this;
    }

    /**
     * 更新框架
     *
     * @return boolean
     */
    protected function update()
    {
        $batch_update_pr = $batch_insert_log = [];

        $can_edit_cols   = $this->modifiy_cols;
        $updated_uid     = $this->active_user->staff_code;
        $updated_name    = $this->active_user->user_name;

        $this->ci->load->classes('basic/classes/Record');

        foreach ($this->valid_records as $val)
        {

            $log_context = [];

            $this->ci->Record->recive($val)->enable_extra_property();

            //需要变更的记录
            $is_error = $is_change = false;

            $selected = $this->selected[$val[$this->primary_key]];

            $new_row = $val;

            $executed_cb = [];

            //上传的值进行处理
            foreach ($selected as $index => $edit_val)
            {
                if (!is_numeric($index) && $index == $this->line_index_name) continue;

                $edit = $this->index_to_col[$index];

                if (!in_array($edit, $can_edit_cols)) {
                    continue;
                }

                $new_row[$edit] = $edit_val;

                $cb = $this->column_callback[$edit] ?? null;
                //回调处理
                if (null !== $cb && is_callable($cb['cb']))
                {
                    try {
                        $executed_cb[$edit] = true;
                        $new_row[$edit] = $edit_val = $cb instanceof Closure ?
                        $cb['cb']($new_row, $val, $cb['params'] ?? []) :
                        call_user_func_array($cb['cb'], array(&$new_row, $val, $cb['params'] ?? []));
                        //log_message('INFO', sprintf('CsvWrite.handler 主键：%s 列：%s 调用返回结果：%s', $val[$this->primary_key], $edit, $new_row[$edit]));
                    } catch (\Exception $e) {
                        log_message('ERROR', sprintf('主键：%s 编辑列:%s检测没有通过，设置的值:%s不符合要求，记录不在进行更新', $val[$this->primary_key], $edit, $new_row[$edit]));
                        $is_error = true;
                        continue;
                    }
                }

                //记录前后变化量
                $old_value = $val[$edit];

                $edit_val = trim($edit_val);
                $this->ci->Record->set($edit, $edit_val);

                if ($edit_val != $old_value) {
                    $is_change = true;
                    $log_context[] = sprintf('将%s由%s调整为 %s', $this->langs[$edit], $old_value, $edit_val);
                }
            }

            //还有其他的回调
            if ($is_change) {
                foreach ($this->column_callback as $edit => $cb) {
                    if (!isset($executed_cb[$edit])) {
                        //执行
                        try {
                            $new_row[$edit] = $edit_val = $cb instanceof Closure ?
                            $cb['cb']($new_row, $val, $cb['params'] ?? []) :
                            call_user_func_array($cb['cb'], array(&$new_row, $val, $cb['params'] ?? []));
                            //log_message('INFO', sprintf('CsvWrite.handler 主键：%s 列：%s 调用返回结果：%s', $val[$this->primary_key], $edit, $new_row[$edit]));
                        } catch (\Exception $e) {
                            log_message('ERROR', sprintf('主键：%s 编辑列:%s检测没有通过，设置的值:%s不符合要求，记录不在进行更新', $val[$this->primary_key], $edit, $new_row[$edit]));
                            $is_error = true;
                            continue;
                        }

                        //记录前后变化量
                        $old_value = $val[$edit];

                        $edit_val = trim($edit_val);
                        $this->ci->Record->set($edit, $edit_val);

                        if ($edit_val != $old_value) {
                            $log_context[] = sprintf('将%s由%s调整为 %s', $this->langs[$edit] ?? $edit, $old_value, $edit_val);
                        }
                    }
                }
            }

            if ($is_error || !$is_change) {
                //log_message('INFO', sprintf('CsvWrite.handler 主键：%s 没有发生变化', $val[$this->primary_key], $edit, $new_row[$edit]));
                continue;
            }

            //记录日志
            if ($this->log_model) {
                $context = mb_substr(implode(',', $log_context), 0, 300);
                if ($this->log_func) {
                    $batch_insert_log[] = ($this->log_func)($val[$this->primary_key], $this->active_user, $context);
                } else {
                    //日志
                    $batch_insert_log[] = [
                        'gid' => $val[$this->primary_key],
                        'uid' => $updated_uid,
                        'user_name' => $updated_name,
                        'context' => $context,
                    ];
                }
            }
            $update_row = $this->ci->Record->report($this->ci->Record::REPORT_FULL_ARR, true, true, true);
            $update_row[$this->primary_key] = $val[$this->primary_key];
            //更新
            $batch_update_pr[] = $update_row;
        }

        //pr($batch_update_pr);
        if (empty($batch_update_pr)) {
            log_message('INFO', sprintf('CsvWrite 没有任何更新的记录'));
            $this->report['errorMess'] = '没有任何更新';
            return false;
        } else {
            //log_message('INFO', sprintf('更新的记录', json_encode($batch_update_pr, JSON_UNESCAPED_UNICODE)));
        }

        $db = $this->model->getDatabase();

        try
        {
            $this->tran_start_out OR $db->trans_start();

            //关联其他操作
            if (!empty($this->tran_callback)) {
                foreach ($this->tran_callback as $trans_name => $cb)
                {
                    if (isset($cb['cb']) && is_callable($cb['cb'])) {
                        try {
                            $result = call_user_func_array($cb['cb'], $cb['params'] ?? []);
                            log_message('INFO', sprintf('CsvWrite事务外部回调:%s返回结果：%s', $trans_name, is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : strval($result)));
                        } catch (\Exception $e) {
                            log_message('ERROR', sprintf('CsvWrite事务外部回调:%s 抛出异常，原因：%s', $trans_name, $e->getMessage()));
                        }
                    }
                }
            }

            //批量更新主记录
            $update_rows = $db->update_batch($this->model->getTable(), $batch_update_pr, $this->primary_key);
            if (!$update_rows)
            {
                $this->report['errorMess'] = 'Csv批量修改更新列表update_batch失败';
                return false;
            }

            //插入日志
            $insert_rows = $this->log_model->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $this->report['errorMess'] = 'Csv批量修改更新列表日志批量插入失败';
                return false;
            }

            $this->tran_start_out OR $db->trans_complete();

            if (!$this->tran_start_out && $db->trans_status() === false)
            {
                $this->report['errorMess'] = 'Csv批量修改更新，事务提交成功，但状态检测为false';
                return false;
            }

            $this->report['processed'] = count($batch_update_pr);;
            $this->report['succLines'] = array_column($this->valid_records, $this->line_index_name);
            $this->report['undisposed'] = $this->report['total'] - $this->report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            $this->selected = $this->valid_records = $this->langs = [];

            return true;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('Csv批量修改更新%s，提交事务出现异常: %s', json_encode($this->selected), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = $records = null;
            unset($batch_update_pr, $batch_insert_log, $records);

            $this->errorMess = 'Csv批量修改更新，抛出异常：'.$e->getMessage();
            return false;
        }
    }


    /**
     * 新增参数准备通常注册到了before hook中
     *
     * @return boolean
     */
    protected function insert()
    {
        if (empty($this->batch_insert))
        {
            $this->errorMess = '没有符合条件的新增记录';
            return false;
        }

        $db = $this->model->getDatabase();

        try
        {
            $this->tran_start_out OR $db->trans_start();

            $insert_rows = $db->insert_batch($this->model->getTable(), $this->batch_insert);
            if (!$insert_rows)
            {
                $this->errorMess = '新增失败，返回行数为0';
                return false;
            }

            //插入日志
            if (!empty($this->batch_insert_log)) {
                $insert_rows = $this->log_model->madd($this->batch_insert_log);
                if (!$insert_rows)
                {
                    $this->errorMess = '日志批量插入失败';
                    return false;
                }
            }

            //关联其他操作
            if (!empty($this->tran_callback)) {
                foreach ($this->tran_callback as $trans_name => $cb)
                {
                    if (isset($cb['cb']) && is_callable($cb['cb'])) {
                        try {
                            $result = call_user_func_array($cb['cb'], $cb['params'] ?? []);
                            log_message('INFO', sprintf('CsvWrite事务外部回调:%s返回结果：%s', $trans_name, is_array($result) ? json_encode($result, JSON_UNESCAPED_UNICODE) : strval($result)));
                        } catch (\Exception $e) {
                            log_message('ERROR', sprintf('CsvWrite事务外部回调:%s 抛出异常，原因：%s', $trans_name, $e->getMessage()));
                        }
                    }
                }
            }

            $this->tran_start_out OR $db->trans_complete();

            if (!$this->tran_start_out && $db->trans_status() === false)
            {
                $this->errorMess = 'Csv批量插入更新，事务提交成功，但状态检测为false';
                return false;
            }

            $this->report['processed'] = $insert_rows;
            $this->report['undisposed'] = $this->report['total'] - $this->report['processed'];

            //释放资源
            $this->batch_insert = $this->batch_insert_log = [];
            $this->selected = $this->valid_records = $this->langs = [];

            return true;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('Csv批量插入%s，提交事务出现异常: %s', json_encode($this->selected), $e->getMessage()));

            $this->batch_insert = $this->batch_insert_log = [];
            $this->selected = $this->valid_records = $this->langs = [];
            $this->report['succLines'] = [];

            $this->errorMess = 'Csv批量插入，抛出异常：'.$e->getMessage();
            return false;
        }
    }

    /**
     * 默认更新fba列表
     *
     * @return boolean
     */
    protected function build_default_fba_list()
    {
        //前置调用
        if (!empty($this->inspect_callback['before'])) {
            foreach ($this->inspect_callback['before'] as $cb)
            {
                if (isset($cb['cb']) && is_callable($cb['cb'])) {
                    call_user_func_array($cb['cb'], $cb['params'] ?? []);
                }
            }
        }

        $batch_update_pr = $batch_insert_log = [];

        $update_time     = time();
        $can_edit_cols   = $this->modifiy_cols;
        $updated_uid     = $this->active_user->staff_code;
        $updated_name    = $this->active_user->user_name;

        $this->ci->load->classes('basic/classes/Record');

        foreach ($this->valid_records as $val)
        {

            $log_context = [];

            $this->ci->Record->recive($val)->enable_extra_property();

            //需要变更的记录
            $is_change = false;

            foreach ($this->selected[$val[$this->primary_key]] as $index => $edit_val)
            {
                $edit = $this->index_to_col[$index];
                if (in_array($edit, $can_edit_cols))
                {
                    //回调处理
                    if (isset($this->column_callback[$edit]['cb']) && is_callable($this->column_callback[$edit]['cb']))
                    {
                        $edit_val = call_user_func_array($this->column_callback[$edit]['cb'], $this->column_callback[$edit]['params']);
                        unset($this->column_callback[$edit]);
                    }
                    $this->ci->Record->set($edit, $edit_val);

                    //记录前后变化量
                    $old_value = $val[$edit];
                    if ($edit_val != $old_value) {
                        $is_change = true;
                        $log_context[] = sprintf('将%s由%s调整为 %s', $this->langs[$edit], $old_value, $edit_val);
                    }
                }
            }

            //关联更新记录
            if (!empty($this->column_callback))
            {
                foreach ($this->column_callback as $edit => $cb) {

                    if (in_array($edit, $can_edit_cols))
                    {
                        //回调处理
                        if (isset($cb['cb']) && is_callable($cb['cb']))
                        {
                            $edit_val = call_user_func_array($cb['cb'], $cb['params']);
                        }
                        $this->ci->Record->set($edit, $edit_val);

                        //记录前后变化量
                        $old_value = $val[$edit];
                        if ($edit_val != $old_value) {
                            $log_context[] = sprintf('将%s由%s调整为 %s', $this->langs[$edit], $old_value, $edit_val);
                        }
                    }
                }
            }

            if (!$is_change) {
                continue;
            }

            isset($val['updated_at']) && $this->ci->Record->set('updated_at', $update_time);
            isset($val['updated_uid']) && $this->ci->Record->set('updated_uid', $updated_uid);

            //日志
            $batch_insert_log[] = [
                'gid' => $val['gid'],
                'uid' => $updated_uid,
                'user_name' => $updated_name,
                'context' => mb_substr(implode(',', $log_context), 0, 300),
            ];

            $update_row = $this->ci->Record->report($this->ci->Record::REPORT_FULL_ARR, true, true, true);
            $update_row[$this->primary_key] = $val[$this->primary_key];

            //更新
            $batch_update_pr[] = $update_row;
        }

        //事务开始
        $this->ci->load->model('Fba_pr_list_log_model', 'fba_pr_list_log', false, 'fba');

        $db = $this->model->getDatabase();

        try
        {
            $this->tran_start_out OR $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->model->getTable(), $batch_update_pr, 'gid');
            if (!$update_rows)
            {
                $this->report['errorMess'] = 'update_batch 更新失败';
                return false;
            }

            //插入日志
            $insert_rows = $this->ci->fba_pr_list_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $this->report['errorMess'] = '日志批量插入失败';
                return false;
            }

            $this->tran_start_out OR $db->trans_complete();

            if (!$this->tran_start_out && $db->trans_status() === false)
            {
                $this->report['errorMess'] = 'Csv批量修改更新，事务提交成功，但状态检测为false';
                return false;
            }

            $this->report['processed'] = count($batch_update_pr);;
            $this->report['undisposed'] = $this->report['total'] - $this->report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            $this->selected = $this->valid_records = $this->langs = [];

            return true;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('Csv批量修改更新%s，提交事务出现异常: %s', json_encode($this->selected), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = $records = null;
            unset($batch_update_pr, $batch_insert_log, $records);

            $this->errorMess = 'Csv批量修改更新，抛出异常：'.$e->getMessage();
            return false;
        }
    }

    protected function hook($pos)
    {
        $result = [];
        //前置调用
        if (!empty($this->inspect_callback[$pos])) {
            foreach ($this->inspect_callback[$pos] as $cb)
            {
                if (isset($cb['cb']) && is_callable($cb['cb'])) {
                    $result[] = $cb['cb'] instanceof Closure ?
                    (isset($cb['params']) ?  $cb['cb']($cb['params']) : $cb['cb']()) :
                    call_user_func_array($cb['cb'], $cb['params'] ?? []);

                    log_message('INFO', sprintf('CsvWrite.handler %s调用返回结果：%s', $pos, json_encode($result)));
                }
            }
        }
        return $result;
    }

    public function run($default_exec_handler = '')
    {
        if (empty($this->valid_records)) {
            $this->report['errorMess'] = $this->errorMess;
            log_message('INFO', 'CsvWrite.run 本次没有有效的记录供处理');
            return true;
        }

        if ($default_exec_handler && method_exists($this, $default_exec_handler)) {
            $this->hook('before');
            if (!($result = $this->$default_exec_handler())) {
                $this->report['errorMess'] = $this->errorMess;
            }
            $this->hook('after');
        } else {
            $this->hook('before');
            if (!($result = $this->mode == 'update' ? $this->update() : $this->insert())) {
                $this->report['errorMess'] = $this->errorMess;
            }
            $this->hook('after');
        }

        return $result;
    }

    public function property($property)
    {
        if (property_exists($this, $property))
        {
            return $this->$property;
        }
        return null;
    }

}