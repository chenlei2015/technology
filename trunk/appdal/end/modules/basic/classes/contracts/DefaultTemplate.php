<?php 

require_once APPPATH . 'modules/basic/classes/contracts/ExportTemplate.php';
/**
 * 默认模板
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw
 * @version 1.0
 */
abstract class DefaultTemplate implements ExportTemplate
{
    /**
     * 
     * @var string
     */
    const version = '1.0.0';
    
    /**
     * 
     * @var integer
     */
    public static $s_width = 18;
    
    /**
     * title
     * 
     * @var string
     */
    public $title = '导出';
    
    /**
     * 创建者
     * @var string
     */
    public $creator = 'YiBai Ltd';
    
    /**
     * 列数固定
     * 
     * @var integer
     */
    public $max_column = 0;
    
    /**
     * 行数
     * 
     * @var integer
     */
    public $max_row = 0;
    
    private $_data;
    
    /**
     * 
     * @var unknown
     */
    protected $_title_map;
    
    /**
     * phpexcel fromArray
     * @var unknown
     */
    private $_package;
    
    /**
     * 公共字段
     * 
     * @var unknown
     */
    private $_common;
    
    /**
     * 接收原始数据
     * 
     * @param unknown $data
     */
    public function accept($data)
    {
        $this->_data = $data;
        return $this;
    }
    
    /**
     * 制定规则, 可对原模板进行增删改
     * 
     */
    public function configiure($override_rule = [])
    {
        $this->get_default_template_cols();
        if (empty($override_rule)) return $this;
        //@TODO
    }
    
    /**
     * 修改title名称
     * 
     * @param unknown $rename_arr
     */
    private function _rename_title($rename_arr)
    {
       //@TODO  
    }
    
    /**
     * 根据col搜索对应的title
     * 
     * @param unknown $col
     * @return string[]|number[]|string[][]|number[][]|string
     */
    private function _find_title_by_col($col)
    {
        foreach ($this->_title_map as $title => $cfg)
        {
            if (count($cfg['col']) == 1 && $cfg['col'][0] == $col)
            {
                return $title;
            }
        }
        return '';
    }
    
    abstract public function get_default_template_cols();
    
    
    public function split_then_merge_cols()
    {
        
    }
    
    private function general_row($row)
    {
        
    }
    
    /**
     * 解释 $this->_title_map['format']
     * 
     */
    public function replace_format($format, $row)
    {
        foreach ($row as $k => $v)
        {
            $format = str_replace('{#'.$k.'}', $v, $format);
        }
        return $format;
    }
    
    /**
     * 生成数据
     */
    public function make()
    {
        $this->max_column = count($this->_title_map);
        $this->max_row = count($this->_data);
        
        $cols = array_flip(array_column($this->_title_map, 'col'));
        foreach ($this->_data as $k => &$row)
        {
            $row = array_intersect_key($row, $cols);
            $row = $this->_sort($row);
        }
        array_unshift($this->_data, array_keys($this->_title_map));
    }
    
    
    /**
     * 排序
     *
     * @param unknown $sku_row
     * @return string|unknown
     */
    private function _sort($row)
    {
        foreach ($this->_title_map as $title => $cfg)
        {
            $sort[] = $row[$cfg['col']] ?? '字段需校对';
        }
        return $sort;
    }
    
    /**
     * 获取数据
     */
    public function get()
    {
        return $this->_data;
    }
    
    /**
     * 获取模板配置
     * 
     * @return unknown
     */
    public function get_template_cfg()
    {
        return $this->_title_map;
    }
}