<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * FBA毛需求
 *
 * @author zc
 * @since 2019-10-24
 * @link
 */
class DemandListService extends AbstractList
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
        $this->_ci->load->model('Demand_list_model', 'd_list', false, 'shipment');
        //$this->_ci->load->helper('plan_helper');
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
            'site'    => [
                'desc'     => '发运目的站点',
                'name'     => 'site',
                'type'     => 'strval',
            ],
            'sellersku'    => [
                'desc'     => 'seller_sku',
                'name'     => 'seller_sku',
                'type'     => 'strval',
            ],
            'erpsku'    => [
                'desc'     => 'erp_sku',
                'name'     => 'erp_sku',
                'type'     => 'strval',
            ],
            'asin'    => [
                'desc'     => 'asin',
                'name'     => 'asin',
                'type'     => 'strval',
            ],
            'fnsku'    => [
                'desc'     => 'fnsku',
                'name'     => 'fnsku',
                'type'     => 'strval',
            ],
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
            'export_save'      => [
                'desc'     => '是否保存当前查询条件',
                'name'     => 'export_save',
                'type'     => 'intval',
            ],
        ];
        $this->_cfg = [
            'title' => [
            ],
            'select_cols' => [
                ($this->_ci->d_list->getTable()) => [
                    '*'
                ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                'station_code'
            ],
            'user_profile' => 'python_send_require'
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
        $db = $this->_ci->d_list->getDatabase();
        $db->reset_query();

        $o_t = $this->_ci->d_list->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($o_t);

        //发运目的地站点,python发运表没有该字段!!!
        if (isset($params['site'])){
            $query->where("{$o_t}.site ", $params['site']);
        }

        if (isset($params['seller_sku'])){
            $query->where("{$o_t}.seller_sku ", $params['seller_sku']);
        }

        if (isset($params['erp_sku'])){
            $query->where("{$o_t}.erp_sku ", $params['erp_sku']);
        }

        if (isset($params['asin'])){
            $query->where("{$o_t}.asin ", $params['asin']);
        }

        if (isset($params['fnsku'])){
            $query->where("{$o_t}.fnsku ", $params['fnsku']);
        }
        //die(json_encode($params));
        $query_counter = clone $query;
        $query_counter->select("{$o_t}.index");
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
