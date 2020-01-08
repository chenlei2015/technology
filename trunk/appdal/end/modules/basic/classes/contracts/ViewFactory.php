<?php 

/**
 * 视图工厂，
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw 
 */
class ViewFactory
{
    private static $s_bind_view;
    
    protected $_ci;
    
    public function __construct($params  = array())
    {
        $this->_ci =& get_instance();
    }
    
    public static final function build($file_type)
    {
        if (!ViewFactory::$s_bind_view[$file_type] instanceof ViewFactory)
        {
            (new self())->setTemplate($file_type);
        }
        return ViewFactory::$s_bind_view[$file_type];
    }
    
    
    public function setTemplate($file_type, $appoint_handler = '')
    {
        $class = $appoint_handler ? : '';
        
        if ($class == '')
        {
            switch ($file_type)
            {
                case 'xls':
                case 'xlsx':
                case 'xlxs':
                    $class = 'fba/classes/XlsxView';
                    break;
                case 'csv':
                    $class = 'fba/classes/CsvView';
                    break;
                case 'pdf':
                    $class = 'fba/classes/pdfView';
                    break;
                default:
                    $class = 'fba/classes/CsvView';
                    break;
            }
        }
        
        if ($class == '')
        {
            throw new \RuntimeException(sprintf('该文件类型：%s暂无法解析', $file_type), 3001);
        }
        
        $class_instance = pathinfo($class)['filename'];
        $this->_ci->load->classes($class);
        ViewFactory::$s_bind_view[$file_type] = $this->_ci->{$class_instance};
        return ViewFactory::$s_bind_view[$file_type];
    }
    
}