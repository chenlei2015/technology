<?php 

require_once APPPATH . 'modules/basic/classes/contracts/ExportView.php';

/**
 * csv视图
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw
 */
class CsvView implements ExportView
{
    /**
     * 
     * @var string
     */
    public static $s_content_type = 'application/vnd.ms-excel';
    
    /**
     *
     * @var string
     */
    public static $view_file_type = 'Excel2007';
    
    /**
     * 
     * @var string
     */
    public $display_type = ExportView::VIEW_FILE;
    
    /**
     * extension
     * 
     * @var string
     */
    public static $s_file_type = 'csv';
    
    /**
     * ExportTemplate
     *
     * @var unknown
     */
    private $_template;
    
    /**
     * 
     * @var unknown
     */
    private $_sheet;
    
    /**
     * 用数据装配模板
     */
    public function assemble(ExportTemplate $template)
    {
        $this->_template = $template;
        
        return true;
    }
    
    /**
     * 输出已经生成的文件， header浏览器， socket传送
     * 
     */
    public function display($view_type = -1)
    {
        if ($view_type != -1)
        {
            $this->set_display_type($view_type);
        }
        switch ($this->display_type)
        {
            case ExportView::VIEW_DATA:
                return $this->_binary();
                break;
            case ExportView::VIEW_BROWSER:
                $this->_browser();
                break;
            case ExportView::VIEW_FILE:
            default:
                return $this->_file();
                break;
        }
    }
    
    /**
     * 设置输出方式
     * 
     * @param unknown $type
     */
    public function set_display_type($type)
    {
        $this->display_type = $type;
    }
    
    /**
     * 文件方式
     * 
     * @return string
     */
    protected function _file()
    {
        $file = APPPATH . '/cache/'.date('YmdHis').'_'.substr((string)time(), 5).'.'.self::$s_file_type;
        $fp = fopen($file, 'a');
        $data = $this->_template->get();
        foreach ($data as $key => $rows) {
            foreach ($rows as $ik => &$val)
            {
                $val = iconv('utf-8', 'gbk', $val);
            }
            fputcsv($fp, $rows);
        }
        fclose($fp);
        $data = null;
        unset($data);
        return $file;
    }
    
    /**
     * 浏览器下载
     */
    protected function _browser()
    {
        $file_name = 'Inland_Export_'.date('Ymd_H_i').'.csv';
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$file_name.'.csv"');
        header('Cache-Control: max-age=0');
        
        $fp = fopen('php://output', 'a');
        
        $data = $this->_template->get();
        $title = array_shift($data);
        foreach ($title as $key => $value) {
            $title[$key] = iconv('utf-8', 'gbk', $value);
        }
        fputcsv($fp, $title);
        
        $num = 0;
        $limit = 100000;
        
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {
            $num++;
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }
            
            $row = $data[$i];
            foreach ($row as $key => $value) {
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            fputcsv($fp, $row);
        }
        ob_end_flush();
        fclose($fp);
        
        $data = null;
        unset($data);
        exit;
    }
    
    /**
     * 使用二进制数据
     */
    protected function _binary()
    {
        $data =  $this->_template->get();
        $title = array_shift($data);
        return ['data' => $data, 'title' => $title];
    }
    
    /**
     * 
     */
    public function __construct()
    {
        $this->_template = null;
    }
}