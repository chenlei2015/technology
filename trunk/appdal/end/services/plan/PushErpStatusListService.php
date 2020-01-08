<?php 

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 查看推送结果
 * Class PushErpStatusListService
 */
class PushErpStatusListService extends AbstractList
{
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'update_time desc';
    
    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Push_erp_status_model', 'm_push_erp', false, 'plan');
        $this->_ci->load->helper('plan_helper');
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
                'offset'        => [
                        'desc'     => '当前选择页',
                        'name'     => 'page',
                        'type'     => 'intval',
                        'callback' => 'is_valid_page'
                ],
                'limit'        => [
                        'desc'     => '每页显示数量',
                        'name'     => 'per_page',
                        'type'     => 'intval',
                        'callback' => 'is_valid_pagesize'
                ],
        ];
        $this->_cfg = [
            'title' => [
            ],
            'select_cols' => [
                    ($this->_ci->m_push_erp->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [

            ],
            'user_profile' => 'python_push_erp_result'
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
        $db = $this->_ci->m_push_erp->getDatabase();
        $db->reset_query();
        
        $o_t = $this->_ci->m_push_erp->getTable();
        
        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };
        
        $query = $db->from($o_t);
        
        $query_counter = clone $query;
        $query_counter->select("{$o_t}.gid");
        $count = $query_counter->count_all_results();
        $page = ceil($count / $params['per_page']);
        
        //从cfg里面获取配置
        $select_cols = '';
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols .= implode(',', $append_table_prefix($cols, $tbl.'.'));
        }
        //执行搜索
        $query->select($select_cols)
              ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
              ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
        
//        pr($query->get_compiled_select());exit;
        //pr($query->get());exit;
        $result = $query->get()->result_array();
        foreach ($result as $k => $v){
            $result[$k]['success_items'] = $v['total_items'] - $v['fail_items'];
        }

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
     * 转换
     *
     * {@inheritDoc}
     * @see AbstractList::translate()
     */
    public function translate($search_result)
    {
        return $search_result;
    }

}
