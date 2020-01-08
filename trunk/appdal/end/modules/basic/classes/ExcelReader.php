<?php 

require_once dirname(__FILE__).'/IFileReader.php';
require_once APPPATH . '/third_party/PHPExcel/IOFactory.php';

/**
 * 读取文件接口
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-02
 * @link
 * @throw
 */

class ExcelReader implements IFileReader
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
        /*if (file_exists(APPPATH.'/cache/xls_data'))
        {
            $this->data = require_once(APPPATH.'/cache/xls_data');
            return;
        }*/
        $ci = CI::$APP;
        $id = PHPExcel_IOFactory::identify($this->_target_file);
        $excel_reader = PHPExcel_IOFactory::createReader($id);
        //对excel阅读器进行优化，缩短时间
        $excel_reader->setReadDataOnly(true);
        $excel_reader->setLoadSheetsOnly('Sheet1');
        //$filter = $this->_excel_reader->getReadFilter();
        
        $php_excel = $excel_reader->load($this->_target_file);
        $sheet = $php_excel->getActiveSheet();
        
        $this->total_row = $sheet->getHighestRow();
        $this->max_col = $sheet->getHighestColumn();
        $this->_read($sheet);

        //file_put_contents(APPPATH.'/cache/xls_data', '<?php return '."\n");
        //file_put_contents(APPPATH.'/cache/xls_data', var_export($this->data), FILE_APPEND);
        $sheet = $excel_reader = NULL;
        unset($sheet, $excel_reader);
    }
    
    private function _read($excel_reader)
    {
        for ($j = 1; $j <= $this->total_row; $j ++)
        {
            $tmp = [];
            for ($i = 'A'; $i <= $this->max_col; $i ++)
            {
                $tmp[] = $excel_reader->getCell($i.$j)->getValue();
            }
            $this->data[$j-1] = $tmp;
        }
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