<?php

include_once dirname(__FILE__).'/HugeExportable.php';
/**
 * 大文件导出csv
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw
 */

abstract class AbstractHugeExport implements HugeExportable
{
    /**
     * 列分割符号
     *
     * @var unknown
     */
    public $delim = ',';

    /**
     * 新行分割符号
     * @var unknown
     */
    public $newline = "\n";

    /**
     * 列封闭符号
     * @var string
     */
    public $enclosure = '"';

    /**
     * 数据源是utf-8模式
     *
     * @var string
     */
    private static $s_in_charset = 'UTF-8';

    /**
     * 输出模式
     *
     * @var string
     */
    protected $out_charset = 'UTF-8';

    protected $_ci;

    /**
     * 表头 col => zh_name
     * @var array
     */
    protected $title_map;

    /**
     *
     * @var unknown
     */
    protected $col_map;

    /**
     * 数据
     * @var unknown
     */
    protected $data_sql;

    /**
     * 导出的数据量
     *
     * @var integer
     */
    protected $export_nums = 0;

    /**
     * @var string datetime
     */
    protected $export_time_start;

    /**
     * @var string datetime
     */
    protected $export_time_end;

    /**
     * 选择的列
     * @var unknown
     */
    protected $_cols;

    /**
     * 导出可视化格式
     *
     * @var unknown
     */
    protected $_format_type = EXPORT_VIEW_PRETTY;

    /**
     * 直接输出形式
     * @var unknown
     */
    protected $_data_type = VIEW_BROWSER;


    /**
     * 导出类型,默认为空:正常导出
     * @var string
     */
    protected $_export_type = '';

    /**
     * 设置utf8模式下是否携带bom头，因为有些软件在明确知道bom的情况下，会当做utf8处理
     *
     * @var string
     */
    protected $_output_bom = false;

    /**
     * @param string $export_time_start
     */
    public function setExport_time_start($export_time_start)
    {
        $this->export_time_start = $export_time_start;
    }

    /**
     * @param string $export_time_end
     */
    public function setExport_time_end($export_time_end)
    {
        $this->export_time_end = $export_time_end;
    }

    /**
     * 获取表头, 页面传递选择的列
     */
    public function get_header()
    {
        $out = '';
        foreach ($this->title_map as $col => $name)
        {
            $out .= $this->enclosure.str_replace($this->enclosure, $this->enclosure.$this->enclosure, $name).$this->enclosure.$this->delim;
        }
        $out = substr($out, 0, -strlen($this->delim)).$this->newline;
        return $this->_output_bom ? chr(0xEF).chr(0xBB).chr(0xBF).$out : $out;
    }

    /**
     * 编码输出
     *
     * @param unknown $out
     * @return string
     */
    protected function encode($out)
    {
        if (AbstractHugeExport::$s_in_charset != $this->out_charset)
        {
            $out = mb_convert_encoding($out, $this->out_charset, AbstractHugeExport::$s_in_charset);
            /*
            if (!($out = iconv(AbstractHugeExport::$s_in_charset, $this->out_charset."//IGNORE//TRANSLIT", $out)))
            {
                $out = mb_convert_encoding($out, $this->out_charset);
            }*/
        }
        return $out;
    }

    /**
     * 获取默认template
     */
    abstract protected function get_default_template_cols();

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::set_data_sql()
     */
    public function set_data_sql($sql)
    {
        $this->export_time_start = date('Y-m-d H:i:s');

        $this->data_sql = $sql;
        //选择的id和记录，
        return $this;
    }

    /**
     * 导出
     *
     * @param int $nums
     * @return AbstractHugeExport
     */
    public function set_export_nums($nums)
    {
        $this->export_nums = $nums;
        return $this;
    }

    /**
     * 初始化title
     *
     * @return FbaHugeExport
     */
    public function set_title_map($page_select_cols = '*', $export_gid = 0) : AbstractHugeExport
    {
        $title_map_cfg = $this->get_default_template_cols();

        if ($page_select_cols == '*')
        {
            $this->title_map = $this->_format_type == EXPORT_VIEW_PRETTY ? array_combine(array_column($title_map_cfg, 'col'), array_keys($title_map_cfg)) : array_combine(array_column($title_map_cfg, 'col'), array_column($title_map_cfg, 'col'));
            $this->_cols = array_column($title_map_cfg, 'col');
        }
        else
        {
            $col_to_name = array_combine(array_column($title_map_cfg, 'col'), array_keys($title_map_cfg));
            //根据页面选择获取title
            foreach ($page_select_cols as $col)
            {
                if (isset($col_to_name[$col]))
                {
                    $this->title_map[$col] = $this->_format_type == EXPORT_VIEW_PRETTY ? $col_to_name[$col] : $col;
                }
                else
                {
                    //由多个col组成的的key值
                    foreach ($col_to_name as $cfg_col => $name)
                    {
                        if (($arr = explode(',', $cfg_col)) && in_array($col, $arr))
                        {
                            $this->title_map[$col] = $this->_format_type == EXPORT_VIEW_PRETTY ? $col_to_name[$col] : $col;
                            break;
                        }
                    }
                }
            }
            $this->_cols = array_keys($this->title_map);
        }

        //默认去掉gid  不去掉传$export_gid=1 改为id传2
        /*if ($this->_format_type == EXPORT_VIEW_PRETTY && $export_gid == 0)
        {
            unset($this->title_map['gid']);
            unset($this->_cols['gid']);
        }
        elseif ($this->_format_type == EXPORT_VIEW_PRETTY && $export_gid == 2)//导出id
        {
            unset($this->title_map['gid']);
            unset($this->_cols['gid']);
            if(!isset($this->title_map['id'])){
                $this->title_map['id'] = '请勿改动此标记';
                $this->_cols[] = 'id';
            }
        }*/
        return $this;
    }

    /**
     * 设置数据展现格式
     *
     * @param unknown $format_type
     * @return AbstractHugeExport
     */
    public function set_format_type($format_type)
    {
        $this->_format_type = $format_type;
        return $this;
    }

    /**
     * 设置数据输出格式
     *
     * @param unknown $data_type
     * @return AbstractHugeExport
     */
    public function set_data_type($data_type)
    {
        $this->_data_type = $data_type;
        if ($this->_data_type == VIEW_FILE)
        {
            $this->_mkdir_download();
        }
        return $this;
    }

    public function set_export_type($export_type)
    {
        $this->_export_type = $export_type;
        return $this;
    }

    private function _mkdir_download()
    {
        $dir = date('Ymd');
        $path = get_export_path() . $dir;
        if (!is_dir($path))
        {
            if (!@mkdir($path, 0774, true))
            {
                throw new \RuntimeException('导出csv创建目录失败，请检测权限', 500);
            }
        }
    }

    /**
     * 设置输出bom头
     *
     * @return AbstractHugeExport
     */
    public function set_utf_bom()
    {
        $this->_output_bom = $this->out_charset == 'UTF-8';
        return $this;
    }

    /**
     * 设置字符编码
     * @param unknown $charset
     * @return AbstractHugeExport
     */
    public function set_out_charset($charset)
    {
        $this->out_charset = $charset;
        $this->set_utf_bom();
        return $this;
    }

    /**
     * 设置一个记录转换器
     */
    abstract public function set_translator() : AbstractHugeExport;

    /**
     * 获取所有用户的姓名转换表
     */
    protected function get_all_saleman()
    {
        $this->_ci->load->service('basic/DyncOptionService');
        $salemans =  $this->_ci->dyncoptionservice->get_dync_oa_user('');
        foreach ($salemans as $key => &$val)
        {
            $tmp = explode(' ', $val);
            $val = $tmp[1] ?? $tmp[0];
        }
        return $salemans;
    }

    /**
     * 获取所有分组的转换表
     */
    protected function get_all_fba_group()
    {
        $this->_ci->load->service('basic/DropdownService');
        $this->_ci->dropdownservice->setDroplist(['fba_sales_group']);
        return $this->_ci->dropdownservice->get()['fba_sales_group'];
    }

    /**
     * 文件方式, 当超过100W的时候，拆分两个csv文件，并打包成
     *
     * @return string
     */
    protected function _file($file_name, $genertor, $file_type = 'csv')
    {
        $file_path = $this->get_download_file_path($file_name) . '.' . $file_type;
        $abs_file = get_export_path() . $file_path;
        $fp = fopen($abs_file, 'w+');
        if (!$fp)
        {
            throw new \RuntimeException('导出csv写入文件失败，请检测权限', 500);
        }
        fwrite($fp, $this->encode($this->get_header()));
        $rownums = 1;
        $filenums = 1;
        $files = [$abs_file];

        foreach ($genertor as $rows)
        {
            fwrite($fp, $this->encode($rows));
            $rownums ++;
            if ($rownums % 9999 == 0) {
                fflush($fp);
                fclose($fp);
                $fp = null;

                //开始换文件
                $file_path = $this->get_download_file_path($file_name) . '_part'.$filenums. '.' . $file_type;
                $abs_file = get_export_path() . $file_path;
                $files[] = $abs_file;

                $fp = fopen($abs_file, 'w+');
                fwrite($fp, $this->encode($this->get_header()));
                $filenums ++;
            }
        }
        fflush($fp);
        fclose($fp);
        $genertor = $fp = null;

        if (count($files) > 1) {
            //将文件进行打包
            $file_path = $this->get_download_file_path($file_name) . '.zip';
            $zip_name = get_export_path() . $file_path;
            $zip = new ZipArchive();
            if ($zip->open($zip_name, ZIPARCHIVE::CREATE) !== true) {
                throw new \RuntimeException('生成zip文件失败', 500);
            }
            foreach ($files as $key => $csvfile) {
                $zip->addFile($csvfile, basename($csvfile, '.csv').'_part'.($key+1).'.csv');
            }
            $zip->close();
            return $file_path;
        }

        return $file_path;
    }

    /**
     * 浏览器下载
     */
    protected function _browser($file_name, $genertor)
    {
        /*header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$file_name.'.csv"');
        header('Cache-Control: max-age=0');*/
        echo $this->encode($this->get_header());
        foreach ($genertor as $rows)
        {
            echo $this->encode($rows);
        }
        exit;
    }

    /**
     * 相对下载路径
     *
     * @param unknown $file_name
     * @throws \RuntimeException
     * @return string
     */
    protected function get_download_file_path($file_name)
    {
        $dir = date('Ymd');
        return $dir . DIRECTORY_SEPARATOR . $file_name;
    }

    /**
     * 输出
     *
     * @param string $file_name
     * @param Generator $genertor 生成器
     * @return mixed string | null
     */
    protected function output($file_name, $genertor)
    {
        $this->export_time_end = date('Y-m-d H:i:s');

        switch ($this->_data_type)
        {
            case VIEW_FILE:
                return $this->_file($file_name, $genertor);
                break;
            default:
                $this->_browser($file_name, $genertor);
                break;
        }
    }

    /**
     * 检测文件是否存在,存在即返回路径
     *
     * @param unknown $file_name
     * @param unknown $file_type
     * @return boolean
     */
    protected function check_and_get_downloaded_path($file_name, $file_type)
    {
        $file_path = $this->get_download_file_path($file_name) . '.' . $file_type;
        $abs_file = get_export_path() . $file_path;
        return file_exists($abs_file) ? $file_path : '';
    }

    protected function add_export_log()
    {
        property_exists($this->_ci, 'm_export_log') OR $this->_ci->load->model('Export_log_model', 'm_export_log', false, 'basic');
        $require_params = [
            'amount' => $this->export_nums,
            'created_start' => $this->export_time_start,
            'created_end' => $this->export_time_end,
            'sql' => mb_substr($this->data_sql, 0, 2000)
        ];
        return $this->_ci->m_export_log->add($require_params);
    }


    /**
     * 导出之前的处理
     */
    protected function before()
    {

    }

    /**
     * 导出之后的清理操作
     */
    protected function after()
    {
        $this->add_export_log();
    }

}