<?php

/**
 * 处理导出， 目前支持xls格式
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 16：21
 * @link
 */
class OverseaDetailExportService
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
     * @var unknown
     */
    private $_template;

    /**
     * 文件类型
     * @var unknown
     */
    private $_file_type;

    /**
     * 前端展示类型
     *
     * @var unknown
     */
    private $_data_type;

    /**
     * 编码
     * @var unknown
     */
    private $_charset;

    /**
     * 指定的文件类型
     *
     * @var array
     */
    private $_allow_file_types = ['csv', 'xls', 'xlsx', 'pdf'];

    private $_gids;

    private $_data;

    /**
     * 导出选择的字段
     * @var unknown
     */
    private $_profile;

    /**
     * 导出格式  1 原生  2 可视化
     * @var unknown
     */
    private $_format_type;

    /**
     *
     * @param unknown $template
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
    }

    public function setTemplate($post)
    {
        $this->_template  = $post['template'] ?? __CLASS__;
        $this->_data_type = $post['data_type'] ?? VIEW_BROWSER;
        $this->_charset   = $post['charset'] ?? 'UTF-8';
        $this->_shipment_sn = $post['shipment_sn']??'';

        if (!isset($post['gid']))
        {
            //默认选择当前筛选
            $this->_gids = '';
        }
        else {
            $this->_gids = $post['gid'];
            $this->_ci->load->helper('fba_helper');
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
    public function export($file_type = 'csv')
    {
        $this->_file_type = strtolower($file_type);
        if (!in_array($this->_file_type, $this->_allow_file_types))
        {
            throw new \InvalidArgumentException(sprintf('目前暂不支持这种格式'), 3001);
        }
        $this->_ci->load->service('shipment/OverseaListService');
        return $this->_ci->oversealistservice->quick_export($this->_gids, $this->_profile, $this->_format_type, $this->_data_type, $this->_charset,$this->_template,$this->_shipment_sn);
    }

    public function __desctruct()
    {
        $this->_data = NULL;
        unset($this->_data);
    }
}