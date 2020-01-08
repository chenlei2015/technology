<?php

/**
 * 国内销量导出
 * @author W02278
 * @name InlandSalesReportExportService Class
 */
class InlandSalesReportExportService
{
    /**
     * client
     * 
     * @var array
     */
    public static $s_encode = ['UTF-8'] ;
    
    private $_entity;
    
    private $_ci;
    
    /**
     * 模板
     * @var string
     */
    private $_template;
    
    /**
     * 文件类型
     * @var string
     */
    private $_file_type;
    
    /**
     * 前端展示类型
     * 
     * @var integer
     */
    private $_data_type;
    
    /**
     * 指定的文件类型
     *
     * @var array
     */
    private $_allow_file_types = ['csv', 'xls', 'xlsx', 'pdf'];
    
    private $_gids;
    
    private $_data;

    /**
     * InlandSalesReportExportService constructor.
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
    }

    public function setTemplate($post)
    {
        $this->_template = $post['template'] ?? __CLASS__;
        $this->_data_type = $post['data_type'] ?? VIEW_BROWSER;
        $this->_charset   = $post['charset'] ?? 'UTF-8';
        if (!isset($post['gid']))
        {
            //默认选择当前筛选
            $this->_gids = '';
        }
        else {
            $this->_gids = $post['gid'];
        }
        $this->_profile = $post['profile'] ?? '';
        if ($this->_profile == '')
        {
            //取默认
            $this->_profile = '*';
        }
        else
        {
            $this->_profile = is_array($this->_profile) ? $this->_profile : explode(',', $this->_profile);
        }
        $this->_format_type = $post['format_type'] ?? EXPORT_VIEW_PRETTY;
    }

    /**
     * 导出
     *
     * @param unknown $post
     */
    public function export($file_type = 'csv' , $gid)
    {
        $this->_file_type = strtolower($file_type);
        if (!in_array($this->_file_type, $this->_allow_file_types))
        {
            throw new \InvalidArgumentException(sprintf('目前暂不支持这种格式'), 3001);
        }

        $this->_ci->load->service('inland/InlandSalesReportListService');
        return $this->_ci->inlandsalesreportlistservice->quick_export($this->_gids, $this->_profile, $this->_format_type,$gid, $this->_data_type, $this->_charset);
    }

    public function __desctruct()
    {
        $this->_data = NULL;
        unset($this->_data);
    }
}