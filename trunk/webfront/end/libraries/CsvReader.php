<?php

/**
 * 读取上传的csv文件
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-03-10
 * @link
 */
class CsvReader
{

    /**
     * @var string
     */
    protected $form_upload_name;

    /**
     * @var int
     */
    protected $status = 0;

    /**
     * @var string
     */
    protected $errorMess = '';

    /**
     * @var array
     */
    protected $valid_callbacks;

    /**
     * 插入还是更新
     *
     * @var string insert|update
     */
    protected $mode = 'update';

    /**
     * 模式检测, 基于title列来判断
     *
     * @var string
     */
    protected $check_mode_callback;

    /**
     * 模式检测完之后的触发器
     * @var unknown
     */
    protected $check_mode_trigger;

    /**
     * 生成id
     * @var string
     */
    protected $general_id_callback;

    /**
     * 行号索引
     * @var string
     */
    protected $line_index_name = 'line_index';

    /**
     * 记录当前批次的行号范围
     * @var array
     */
    protected $batch_line_index_info = [];

    protected $csv_total_lines = 0;

    /**
     * @var CI
     */
    private $ci;


    /**
     * 记录能唯一确定的列
     *
     * @var string
     */
    public $primary_key;

    /**
     * 记录中必须包含的列
     *
     * @var array
     */
    public $required_cols;

    /**
     * 附带修改的列，有就修改
     *
     * @var array
     */
    public $option_cols;

    /**
     * @var integer
     */
    public $batch_size = 500;

    /**
     * 批处理方法
     *
     * @var unknown
     */
    public $handler;

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
            'errorLines' => [],
            'errorFiles' => '',
    ];

    /**
     * 解析csv的错误提示 col => 错误
     *
     * @var array
     */
    public $col_error_tips = [];

    public function __construct($form_upload_name = 'file')
    {
        $this->init_env_resource();
        $this->form_upload_name = $form_upload_name;
    }

    /**
     * 如何解析
     *
     * @param array $required_cols 数据库列 => csv title名
     *   array(
     *       'pr_sn' => '需求单号',
     *       'bd' => 'BD(pcs)',
     *    )
     * @param string $primary
     * @param array $options_cols
     * @throws \InvalidArgumentException
     */
    public function set_rule(array $required_cols, string $primary = 'id',  $options_cols = [])
    {
        $this->required_cols = $required_cols;
        if (empty($this->required_cols)) {
            $this->errorMess = '请先设置必须要读取的列字段';
            throw new \InvalidArgumentException($this->errorMess, 412);
        }
        $this->primary_key = $primary;
        $this->option_cols = $options_cols;
        return $this;
    }

    /**
     * @param int $size
     */
    public function set_batch_size($size)
    {
        $this->batch_size = intval($size);
        return $this;
    }

    /**
     * @param callable $request_handler
     * @return CsvReader
     */
    public function set_request_handler($request_handler)
    {
        $this->handler = $request_handler;
        return $this;
    }

    /**
     * 执行请求处理
     *
     * @param array ...$post
     */
    protected function exec_handler($post)
    {
        static $num;

        if (!$num) {
            $num = 0;
        }
        $num ++;

        log_message('INFO', sprintf('开始请求后端接口%d次开始，当前内存消耗：%sM', $num, strval(memory_get_usage(true)/1024/1024)));
        $post['mode'] = $this->mode;
        $result = call_user_func_array($this->handler, array($post));

        if (!isset($result['data']) || !array_key_exists('succLines', $result['data']) || empty($result['data']['succLines'])) {
            $this->report['errorLines'] = array_merge($this->report['errorLines'], array_values($this->batch_line_index_info));
        } else {
            $this->report['errorLines'] = array_merge($this->report['errorLines'], array_diff($this->batch_line_index_info, $result['data']['succLines']));
        }

        $this->batch_line_index_info = [];

        if ($result['status'] == 0)
        {
            $this->report['undisposed'] += $post['total'];
            $this->report['errorMess'] .= ($result['errorMess'] ?? '');
            return;
        }

        $this->report['processed'] += intval($result['data']['processed']);
        $this->report['undisposed'] += intval($result['data']['undisposed']);

        return;
    }

    /**
     * entry
     */
    public function run()
    {
        $this->read();
    }

    /**
     * 设置检测回调
     *
     * @param array|string $require_db_cols
     * @param callable $cb null | callable
     * @return CsvReader
     */
    public function bind_required_cols_callback($require_db_cols, ?callable $cb)
    {
        if (is_array($require_db_cols) && is_null($cb)) {
            $this->valid_callbacks = $require_db_cols;
            return $this;
        } elseif (is_string($require_db_cols) && is_callable($cb)) {
            $this->valid_callbacks[$require_db_cols] = $cb;
            return $this;
        }
        return $this;
    }


    /**
     * 扩展设置检测回调
     *
     * @param array|string $require_db_cols
     * @param callable $cb null | callable
     * @return CsvReader
     */
    public function bind_required_cols_extension_callback($require_db_cols, ?callable $cb)
    {
        if (is_array($require_db_cols) && is_null($cb)) {
            $this->valid_callbacks = $require_db_cols;
            return $this;
        } elseif (is_string($require_db_cols) && is_callable($cb)) {
            $this->valid_callbacks[$require_db_cols] = $cb;
            return $this;
        }
        return $this;
    }

    /**
     * 设置解析错误日志
     * @param array $tip_assoc_tips
     * @return CsvReader
     */
    public function bind_parse_error_tips($tip_assoc_tips)
    {
        $this->col_error_tips = $tip_assoc_tips;
        return $this;
    }

    /**
     * 设置使用资源
     *
     * @return CsvReader
     */
    protected function init_env_resource() : CsvReader
    {
        set_time_limit(-1);
        ini_set('memory_limit', '512M');

        setlocale(LC_ALL, 'zh_CN');

        $this->ci = CI::$APP;

        $config = [
            'allowed_types' => 'csv',
        ];

        $this->ci->load->library('upload', $config);

        return $this;
    }

    /**
     * 检测上传文件
     *
     * @return boolean|resource
     */
    protected function valid_upload_check()
    {
        if (!$this->ci->upload->valid_upload_file($this->ci->upload->get_upload_file($this->form_upload_name)))
        {
            $this->errorMess = $this->ci->upload->display_errors();
            return false;
        }
        $fhandle = fopen($this->ci->upload->file_temp, 'r');
        if (!$fhandle)
        {
            $this->errorMess = '上传文件已经被删除';
            return false;
        }
        return $fhandle;
    }

    /**
     * 绑定回调检测模式
     *
     * @param callable $cb
     * @return CsvReader
     */
    public function check_mode(callable $cb, ?callable $trigger)
    {
        if (is_callable($cb)) {
            $this->check_mode_callback = $cb;
        }
        if (null !== $trigger) {
            $this->check_mode_trigger = $trigger;
        }
        return $this;
    }

    public function set_general_insert_id(callable $cb)
    {
        if (is_callable($cb)) {
            $this->general_id_callback = $cb;
        }
        return $this;
    }

    protected function read()
    {
        if (!($fhandle = $this->valid_upload_check()))
        {
            return false;
        }
        $title_line = fgets($fhandle);
        if (empty($title_line))
        {
            $this->errorMess = '无法检测处理上传文件格式编码，请转换为UTF-8编码或者GBK编码后上传';
            return false;
        }

        $is_utf8 = true;
        $file_charset = mb_detect_encoding($title_line, array('GBK', 'GB2312', 'UTF-8'));
        if ($file_charset != 'UTF-8')
        {
            $title_line = iconv($file_charset, "UTF-8", $title_line);
            $is_utf8 = false;
        }
        if (substr($title_line, 0, 3) == chr(0xEF).chr(0xBB).chr(0xBF)) {
            $title_line = substr($title_line,3);
        }
        //$title = str_getcsv($title_line);
        $title = explode(",",$title_line);
        array_walk($title, function(&$val) { $val = trim(trim($val, " \t\n\r\0\x0B\""), " \t\n\r\0\x0B\""); });

        if (empty($title_line))
        {
            $this->errorMess = '无法检测处理上传文件格式编码，请转换为UTF-8编码或者GBK编码后上传';
            return false;
        }

        if ($this->check_mode_callback instanceof Closure)
        {
            $this->mode = ($this->check_mode_callback)($title);
            if ($this->check_mode_trigger instanceof Closure)
            {
                ($this->check_mode_trigger)($this, $this->mode);
            }
        }

        $lost_cols = $actual_col_position = $actual_option_col_position = [];
        foreach ($this->required_cols as $orignal => $zh_ch)
        {
            if (($orignal_index = array_search($orignal, $title)) !== false)
            {
                $actual_col_position[$orignal] = $orignal_index;
            }
            elseif (($pretty_index = array_search($zh_ch, $title)) !== false)
            {
                $actual_col_position[$orignal] = $pretty_index;
            }
            else
            {
                $lost_cols[] = $zh_ch;
                break;
            }
        }
        //没有匹配
        if (!empty($lost_cols))
        {
            $this->report['errorMess'] = sprintf('上传的文件必须包含%s标题列', implode(',', $lost_cols));
            return false;
        }

        //可选更新
        foreach ($this->option_cols as $orignal => $zh_ch)
        {
            if (($orignal_index = array_search($orignal, $title)) !== false)
            {
                $actual_option_col_position[$orignal] = $orignal_index;
            }
            elseif (($pretty_index = array_search($zh_ch, $title)) !== false)
            {
                $actual_option_col_position[$orignal] = $pretty_index;
            }
        }
        //如果没有上传，则不处理
        if (!empty($actual_option_col_position))
        {
            $actual_col_position += $actual_option_col_position;
        }

        //按照索引排序
        $index_map_col     = array_flip($actual_col_position);
        ksort($index_map_col, SORT_NUMERIC);

        $this->primary_key_index = $this->primary_key != '' ? $actual_col_position[$this->primary_key] : -1;
        $this->batch_size        = $this->batch_size;

        $post = [
            'primary_key' => $this->primary_key,
            'map'         => $actual_col_position,
        ];

        $total       = 0;
        $valid       = 0;
        $selected    = [];
        $parse_error = [];
        $this->csv_total_lines = 1;

        while (($line = fgets($fhandle)) !== false)
        {
            $this->csv_total_lines ++;
            $this->batch_line_index_info[] = $this->csv_total_lines;
            //获取指定索引
            //$csv_row = $is_utf8 ? str_getcsv($line) : str_getcsv(iconv($file_charset, "UTF-8//IGNORE", $line)) ;
            $csv_row = $is_utf8 ? explode(",",trim($line)) : explode(",",trim(iconv($file_charset, "UTF-8//IGNORE", $line))) ;
            array_walk($csv_row, function(&$val) { $val = trim(trim($val, " \t\n\r\0\x0B\""), " \t\n\r\0\x0B\""); });

            //过滤掉纯空行
            if (empty(array_filter($csv_row))) {
                $this->report['errorLines'][] = $this->csv_total_lines;
                log_message('INFO', sprintf('读取csv文件第%d行为空行', $this->csv_total_lines));
                continue;
            }

            $total ++;

            if (empty($line))
            {
                $this->report['errorLines'][] = $this->csv_total_lines;
                $parse_error['unknown'][] = $total + 1;
                //纯粹的空行不记录提示
                $this->report['undisposed'] ++;
                log_message('INFO', sprintf('读取csv文件第%d行为空行', $this->csv_total_lines));
                continue;
            }
            //无效行开始
            if ($this->mode == 'update' && (!isset($csv_row[$this->primary_key_index]) || empty($csv_row[$this->primary_key_index])))
            {
                $this->report['errorLines'][] = $this->csv_total_lines;
                $parse_error['unknown'][] = $total + 1;
                $this->report['undisposed'] ++;
                log_message('INFO', sprintf('读取csv文件第%d行为无效行', $this->csv_total_lines));
                continue;
            }
            $line = array_intersect_key($csv_row, $index_map_col);

            $col_valid = true;

            if (!empty($this->valid_callbacks))
            {
                foreach ($this->valid_callbacks as $col => $cb)
                {
                    //$line 引用传值
                    if (!($cb_result = $cb($col, $line, isset($this->required_cols[$col]) ? $actual_col_position : $actual_option_col_position, $this->mode))) {
                        $parse_error[$col][] = $total + 1;
                        $col_valid = false;
                        continue;
                    }
                }
            }
            if (!$col_valid) {
                $this->report['errorLines'][] = $this->csv_total_lines;
                $this->report['undisposed'] ++;
                //log_message('INFO', sprintf('读取csv文件第%d行检测不符合规则，错误原因：%s', $this->csv_total_lines, json_encode($parse_error)));
                continue;
            }

            //记录行号
            $line['line_index'] = $this->csv_total_lines;

            if ($this->mode == 'update')
            {
                $primary_value = $line[$this->primary_key_index];
                unset($line[$this->primary_key_index]);
                $selected[$primary_value] = $line;
            }
            else if($this->mode == 'update_more')//一个id多行记录
            {
                $primary_value = $line[$this->primary_key_index];
                unset($line[$this->primary_key_index]);
                $selected[$primary_value][] = $line;
            }
            else
            {
                if ($this->general_id_callback instanceof Closure)
                {
                    $id = ($this->general_id_callback)($csv_row, $actual_col_position);
                    //一个聚合可能有多个记录
                    $selected[$id][] = $line;
                }
            }

            $valid++;

            if ($valid % $this->batch_size == 0)
            {
                $post['selected'] = json_encode($selected);
                $post['total'] = $valid;
                $this->exec_handler($post);
                $selected = [];
                $valid = 0;
            }
        }
        fclose($fhandle);
        @unlink($this->ci->upload->file_temp);

        if (!empty($selected))
        {
            $post['selected'] = json_encode($selected);
            $post['total'] = $valid;
            $this->exec_handler($post);
            $selected = [];
            $valid = 0;
        }
        //给出提示
        $this->report['total'] = $total;

        if (!empty($parse_error))
        {
            foreach ($parse_error as $error => $lines)
            {
                if (isset($this->col_error_tips[$error])) {
                    $this->report['errorMess'] .= sprintf(' 第%s行%s', implode(',', $lines), $this->col_error_tips[$error]);
                }
            }
        }

        return true;
    }

    public function get_report($get_error_file = false)
    {
        if ($get_error_file) {
            $this->report['errorFiles'] = $this->general_error_csv();
        }else{
            $this->report['errorLines'] = array_values(array_unique($this->report['errorLines']));
        }
        return $this->report;
    }

    protected function general_error_csv()
    {
        if (empty($this->report['errorLines'])) {
            return '';
        }
        $this->report['errorLines'] = array_unique($this->report['errorLines']);
        sort($this->report['errorLines']);
        $reletive_path = date('Ymd') . DIRECTORY_SEPARATOR . pathinfo($this->ci->upload->orig_name, PATHINFO_FILENAME).'_'.date('Hi').'_错误文件.csv';
        $file_path = get_export_path() . $reletive_path;
        $dir_path = get_export_path().date('Ymd') . DIRECTORY_SEPARATOR;
        is_dir($dir_path) or mkdir($dir_path,0700);
        $handle = fopen($file_path, 'w+');
        if (!$handle) {
            $this->report['errorMess'] = '导出目录没有写入权限';
            return '';
        }
        fputcsv($handle, ['错误行数']);

        $current = 2;
        foreach ($this->report['errorLines'] as $line) {
            while ($line > $current) {
                fputcsv($handle, [' ']);
                $current++;
            }
            if ($current == $line) {
                fputcsv($handle, [$line]);
                $current++;
            }
        }
        fclose($handle);
        return EXPORT_DOWNLOAD_URL . '/' . $reletive_path;
    }

}