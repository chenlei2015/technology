<?php 

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 库存状态下载
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-04-10
 * @link
 */
class MrpDownloadListService extends AbstractList
{
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_date desc';
    
    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Mrp_source_from_model', 'm_mrp_source_from', false, 'stock');
        return $this;
    }
    
    /**
     * 列表都需要按照key值配置服务， 这里可以放到配置文件
     * 
     * {@inheritDoc}
     * @see Listable::get_cfg()
     */
    public function get_cfg() : array
    {
        $search = [
                'start_date'  => [
                        'desc'     => '创建开始时间',
                        'name'     => 'created_date',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'end_date'    => [
                        'desc'     => '创建结束时间',
                        'name'     => 'created_date',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'line'    => [
                        'desc'     => '业务类型',
                        'name'     => 'bussiness_line',
                        'type'     => 'intval',
                ],
                'state'    => [
                        'desc'     => '状态',
                        'name'     => 'state',
                        'type'     => 'intval',
                ],
                'data_type'    => [
                        'desc'     => '数据类型',
                        'name'     => 'data_type',
                        'type'     => 'intval',
                ],
                'offset'        => [
                        'name'     => 'page',
                        'type'     => 'intval',
                        'callback' => 'is_valid_page'
                ],
                'limit'        => [
                        'name'     => 'per_page',
                        'type'     => 'intval',
                        'callback' => 'is_valid_pagesize'
                ],
        ];
        $this->_cfg = [
            'title' => [
                    'index', 'created_date', 'data_type', 'op_name'
            ],
            'select_cols' => [
                    ($this->_ci->m_mrp_source_from->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => ['mrp_source'],
        ];
        return $this->_cfg;
    }
    
    /**
     * 
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {
        //pr($params);exit;
        $db = $this->_ci->m_mrp_source_from->getDatabase();
        
        $o_t = $this->_ci->m_mrp_source_from->getTable();
        
        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };
        
        $query = $db->from($o_t);
        
        /**
         * 创建日期
         */
        if (isset($params['created_date']))
        {
            $query->where("{$o_t}.created_date >=", $params['created_date']['start'])
            ->where("{$o_t}.created_date <=", $params['created_date']['end']);
        }
        
        if (isset($params['data_type']))
        {
            $query->where("{$o_t}.data_type", $params['data_type']);
        }
        
        if (isset($params['bussiness_line']))
        {
            $query->where("{$o_t}.bussiness_line", $params['bussiness_line']);
        }
        
        //从cfg里面获取配置
        $select_cols = '';
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols .= implode(',', $append_table_prefix($cols, $tbl.'.'));
        }
        
        $query_counter = clone $query;
        $query_counter->select("{$o_t}.created_date");
        $count = $query_counter->count_all_results();
        $page = ceil($count / $params['per_page']);
        
        //执行搜索
        $query->select($select_cols)
              ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
              ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
        
        //pr($query->get_compiled_select());exit;
        //pr($query->get());exit;
        $result = $query->get()->result_array();
        
        //设定统一返回格式
        return [
                'page_data' => [
                        'total' => $count,
                        'offset' => $params['page'],
                        'limit' => $params['per_page'],
                        'pages' => $page
                ],
                'data_list'  => [
                        'value' => &$result
                ]
        ];
    }

    /**
     * 用户自定义处理参数的模板方法，由各自实例化类实现。
     *
     * @param unknown $defind_valid_key
     * @param unknown $col
     * @param unknown $val
     * @param unknown $format_params
     * @return boolean
     */
    protected function hook_user_format_params($defind_valid_key, $col, $val, &$format_params)
    {
        $rewrite_cols = ['start_date', 'end_date'];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }
        //转换预期缺货时间
        if ($col == 'start_date') {
            $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
        }
        if ($col == 'end_date') {
            $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
        }
        return true;
    }
    
    /**
     * 针对hook_user_format_params生成的参数做检测
     * {@inheritDoc}
     * @see AbstractList::hook_user_format_params()
     */
    protected function hook_user_format_params_check($defind_valid_key, &$format_params)
    {
        if (isset($format_params['created_date']))
        {
            $key = 'created_date';
            if (count($format_params[$key]) == 1) {
                if (isset($format_params[$key]['start']))
                {
                    $format_params[$key]['end'] = date('Y-m-d');
                }
                else
                {
                    //$format_params[$key]['start'] = sprintf('%s-%s-%s', date('Y'), '01', '01');
                    $format_params[$key]['start'] = $format_params[$key]['end'];
                }
            }
            if ($format_params[$key]['start'] > $format_params[$key]['end'])
            {
                $tmp = $format_params[$key]['start'];
                $format_params[$key]['start'] =  $format_params[$key]['end'];
                $format_params[$key]['end'] = $tmp;
                //throw new \InvalidArgumentException(sprintf('开始时间不能晚于结束时间'), 3001);
            }
        }
    }
    /**
     * 转换, 增加url
     * 
     * {@inheritDoc}
     * @see AbstractList::translate()
     */
    public function translate($search_result)
    {
        foreach ($search_result['data_list']['value'] as $row => &$val)
        {
            $val['download_url'] = MRP_SOURCE_DOWNLOAD_URL . '/' . ltrim($val['download_url'], '/');
        }
        return $search_result;
    }
}
