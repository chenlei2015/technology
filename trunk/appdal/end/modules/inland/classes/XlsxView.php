<?php 

require_once dirname(__FILE__) . '/ExportView.php';

/**
 * xlsx视图
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw
 */
class XlsxView implements ExportView
{
    /**
     * 
     * @var string
     */
    public static $s_content_type = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    
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
     * ExportTemplate
     *
     * @var unknown
     */
    private $_template;
    
    /**
     * PHPExcel
     * 
     * @var unknown
     */
    private $_excel;
    
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
        set_time_limit(-1);
        
        $this->_template = $template;
        
        require_once APPPATH . '/third_party/PHPExcel.php';
         
        $this->_excel = new PHPExcel();
        //excel file global set
        $property = $this->_excel->getProperties();
        $property->setTitle($this->_template->title);
        $property->setCreator($this->_template->creator);
        //$property->setLastModifiedBy("Zeal Li");
        //$property->setSubject("Office XLS Test Document, Demo");
        //$property->setDescription("Test document, generated by PHPExcel.");
        //$property->setKeywords("office excel PHPExcel");
        $property->setCategory("国内");
         
        //sheet property set
        //后续可能导出多张订单要分sheet
        $this->_sheet = $this->_excel->getActiveSheet();
        $this->_sheet->setTitle('Sheet1');
        
        //设置单元格格式
        //$sheet->setCellValueExplicit('A5', '847475847857487584', PHPExcel_Cell_DataType::TYPE_STRING);
        //合并单元格
        //$sheet->mergeCells('B1:C22');
        //分离单元格
        //$sheet->unmergeCells('B1:C22');
        
        //设置单元列外观样式
        //$sheet->getColumnDimension('B')->setAutoSize(true);
        //$sheet->getColumnDimension('A')->setWidth(30);
        $config = $this->_template->get_template_cfg();
        
        $head = $col = '';
        for( $i = ord('A'), $j = ord('A') + $this->_template->max_column, $l = 0; $i <= $j; $i++, $l++)
        {
             $width = current($config)['width'] ?? $this->_template::$s_width;
             if (($y = ($i - 65) % 26) == 0 && $i >= 65 + 26) $head .= 'A';
             $col = $head . chr(65 + $y); 
             $this->_sheet->getColumnDimension($col)->setWidth($width);
             next($config);
        }

        //设置单元格样式
        //$cell_style = $objActSheet->getStyle('A5');
        //$cell_style->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_NUMBER);
        //设置字体
        //$cell_style = $objStyleA5->getFont();
        //$cell_style->setName('Courier New');
        //$cell_style->setSize(10);
        //$cell_style->setBold(true);
        //$cell_style->setUnderline(PHPExcel_Style_Font::UNDERLINE_SINGLE);
        //$cell_style->getColor()->setARGB('FF999999');
        
        //设置对齐方式
        //$cell_style = $objStyleA5->getAlignment();
        //$cell_style->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_RIGHT);
        //$cell_style->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER);
        
        //设置边框
        //$cell_style = $objStyleA5->getBorders();
        //$cell_style->getTop()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        //$cell_style->getTop()->getColor()->setARGB('FFFF0000'); // color
        //$cell_style->getBottom()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        //$cell_style->getLeft()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        //$cell_style->getRight()->setBorderStyle(PHPExcel_Style_Border::BORDER_THIN);
        
        //设置填充颜色
        //$cell_style = $objStyleA5->getFill();
        //$cell_style->setFillType(PHPExcel_Style_Fill::FILL_SOLID);
        //$cell_style->getStartColor()->setARGB('FFEEEEEE');
        
        //从指定的单元格复制样式信息.
        //$cell_style->duplicateStyle($objStyleA5, 'B1:C22');
        
        //添加图片
        //$objDrawing = new PHPExcel_Worksheet_Drawing();
        //$objDrawing->setName('ZealImg');
        //$objDrawing->setDescription('Image inserted by Zeal');
        //$objDrawing->setPath('D:\wamp\www\boss_dpf\crm\statement\13273632001336608000.jpg');
        //$objDrawing->setHeight(36);
        //$objDrawing->setCoordinates('C23');
        //$objDrawing->setOffsetX(10);
        //$objDrawing->setRotation(15);
        //$objDrawing->getShadow()->setVisible(true);
        //$objDrawing->getShadow()->setDirection(36);
        //$objDrawing->setWorksheet($objActSheet);
        
        //添加一个新的worksheet
        //$objExcel->createSheet();
        //$objExcel->getSheet(1)->setTitle('测试2');
        
        //保护单元格
        //$objExcel->getSheet(1)->getProtection()->setSheet(true);
        //$objExcel->getSheet(1)->protectCells('A1:C22', 'PHPExcel');
        
        //设置值
        $this->_sheet->fromArray($this->_template->get());
        
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
                $this->_binary();
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
     * @return string
     */
    protected function _file()
    {
        $file = APPPATH . '/cache/'.date('YmdHis').'_'.substr((string)time(), 5).'.xlsx';
        $writer = PHPExcel_IOFactory::createWriter($this->_excel, self::$view_file_type);
        $writer->save($file);
        return $file;
    }
    
    /**
     * 浏览器下载
     */
    protected function _browser()
    {
        //browse download
         header("Content-Type: application/force-download");
         header("Content-Type: application/octet-stream");
         header("Content-Type: ".self::$s_content_type);
         header("Content-Type: application/download");
         header('Content-Disposition:inline;filename="'.$this->_template->title.'".xlsx');
         header("Content-Transfer-Encoding: binary");
         header("Expires: Mon, 26 Jul 2037 05:00:00 GMT");
         header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
         header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
         header("Pragma: no-cache");
         $writer = PHPExcel_IOFactory::createWriter($this->_excel, self::$view_file_type);
         $writer->save('php://output');
         ob_end_flush();
         exit;
    }
    
    /**
     * 使用二进制数据
     */
    protected function _binary()
    {
        $writer = PHPExcel_IOFactory::createWriter($this->_excel, self::$view_file_type);
        ob_flush();
        $writer->save('php://output');
        $content = ob_get_contents();
        ob_end_flush();
        exit;
        return $content;
    }
    
    /**
     * 
     */
    public function __construct()
    {
        $this->_template = null;
        $this->_excel = null;
        $this->_sheet = null;
    }
}