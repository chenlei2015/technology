<?php 

require_once dirname(__FILE__).'/IFileReader.php';

/**
 * Csv, Text 阅读器
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-02
 * @link
 * @throw
 */

class TextReader implements IFileReader
{
    private $_target_file;
    
    public $total_row = 0;
    
    public $max_col;
    
    public $data;
    
    public $title;
    
    public $_line = 1;
    
    public function __construct($file)
    {
        $this->_target_file = $file;
        $this->_init();
    }
    
    private function _init()
    {
        $this->_read($this->_target_file);
    }
    
    /**
     * 读取文件
     * @param unknown $excel_reader
     */
    private function _read($target_file)
    {
        $fhandle = fopen($target_file, 'r');
        if (!$fhandle)
        {
            throw new \RuntimeException(sprintf('CVS文件当前不可读'), 500);
        }
        
        $title = fgets($fhandle);
        $encoding = mb_detect_encoding($title);
        if (!$title || !$encoding || !in_array(strtoupper($encoding), ['UTF-8', 'UTF8']))
        {
            throw new \RuntimeException(sprintf('请先将CVS文件转换为UTF8编码'), 500);
        }
        $this->data[0] = str_getcsv($title);
        $this->max_col = count($this->data[0]);
        
        $line = 1;
        while (($data = fgetcsv($fhandle, 800, ',')) !== false)
        {
            $this->data[] = $data;
            $line++;
        }
        fclose($fhandle);
        
        $this->total_row = $line;
    }
       
    public function get_title()
    {
        return $this->data[0];
    }
    
    public function get_count()
    {
        return $this->total_row;
    }
    
    /**
     * 读取一行
     */
    public function get_row()
    {
        return $this->current();
    }
    
    public function get_data()
    {
        return $this->data;
    }
    
    /**
     * 读取一行，并按照$explode_char拆分
     * @param string $explod_char
     * @return array
     */
    public function get_explode_row($explode_char = ',') : array
    {
        return $this->get_row();
    }
    
    public function current () 
    {
        return $this->data[$this->_line];
    }
    
    public function next () 
    {
        $this->_line ++;
        
    }
    
    public function key () 
    {
        return $this->_line;
    }
    
    public function valid () 
    {
        return $this->_line <= $this->total_row;
    }
    
    public function rewind () 
    {
        $this->_line = 1;
    }
    
}