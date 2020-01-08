<?php 

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 模拟发运表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author lewei 13292
 * @since 2019-10-24
 * @link
 */
class SendYesNumListService extends AbstractList
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
        $this->_ci->load->model('Send_yes_num_model', 'm_send_sys', false, 'plan');
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
                'update_start_date'    => [
                    'desc'     => '更新开始时间',
                    'name'     => 'update_start_date',
                    'type'     => 'strval',
                ],
                'update_end_date'    => [
                    'desc'     => '更新结束时间',
                    'name'     => 'update_end_date',
                    'type'     => 'strval',
                ],
                'account_name'    => [
                    'desc'     => '账户站点',
                    'name'     => 'account_name',
                    'type'     => 'strval',
                ],
                'batch_num'    => [
                    'desc'     => '批次号',
                    'name'     => 'batch_num',
                    'type'     => 'strval',
                ],
                'seller_sku'    => [
                    'desc'     => 'seller_sku',
                    'name'     => 'seller_sku',
                    'type'     => 'strval',
                ],
                'erp_sku'    => [
                    'desc'     => 'erp_sku',
                    'name'     => 'erp_sku',
                    'type'     => 'strval',
                ],
                'account_id'    => [
                    'desc'     => 'account_id',
                    'name'     => 'account_id',
                    'type'     => 'strval',
                ],
                'site'    => [
                    'desc'     => '站点',
                    'name'     => 'site',
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
                'account_name','batch_num','need_batch_weight','order_batch_weight','if_product_attr','only_flag',
                'site','account_id','sellersku','erpsku','sale_30','quantity','per_weight','total_quantity',
                'fba_available_inv','country_inv','country_per_sale','country_can_allot','sz_hm_can_allot','need_allot',
                'real_allot','sz_real_allot','hm_real_allot','real_send','transferApplyNum','by_need','by_order',
                'product_cost','update_time'
            ],
            'select_cols' => [
                    ($this->_ci->m_send_sys->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                'station_code'
            ],
            'user_profile' => 'python_send_yes_num'
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
        $db = $this->_ci->m_send_sys->getDatabase();
        $db->reset_query();
        
        $o_t = $this->_ci->m_send_sys->getTable();
        
        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };
        
        $query = $db->from($o_t);

        /**
         *  更新时间
         */
        if (isset($params['update_start_date']))
        {
            $query->where("{$o_t}.update_time >", $params['update_start_date']);
        }
        if (isset($params['update_end_date'])){
            $query->where("{$o_t}.update_time <", $params['update_end_date']);
        }

        //账号站点搜索
        if (isset($params['account_name'])){
            $query->where("{$o_t}.account_name ", $params['account_name']);
        }

        //批次号搜索
        if (isset($params['batch_num'])){
            if (count($sns = explode(',', $params['batch_num'])) > 1)
            {
                $query->where_in("{$o_t}.batch_num", $sns);
            }
            else
            {
                $query->where("{$o_t}.batch_num", $sns[0]);
            }
        }

        //sellersku
        if (isset($params['seller_sku'])){
            if (count($sns = explode(',', $params['seller_sku'])) > 1)
            {
                $query->where_in("{$o_t}.seller_sku", $sns);
            }
            else
            {
                $query->where("{$o_t}.seller_sku", $sns[0]);
            }
        }

        //erpsku
        if (isset($params['erp_sku'])){
            if (count($sns = explode(',', $params['erp_sku'])) > 1)
            {
                $query->where_in("{$o_t}.erp_sku", $sns);
            }
            else
            {
                $query->where("{$o_t}.erp_sku", $sns[0]);
            }
        }

        //account_id
        if (isset($params['account_id'])){
            if (count($sns = explode(',', $params['account_id'])) > 1)
            {
                $query->where_in("{$o_t}.account_id", $sns);
            }
            else
            {
                $query->where("{$o_t}.account_id", $sns[0]);
            }
        }

        //站点
        if (isset($params['site'])){
            $query->where("{$o_t}.site ", $params['site']);
        }
        
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
