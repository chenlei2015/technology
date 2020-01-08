<?php 

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 调拨单列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-10
 * @link
 */
class AllotmentListService extends AbstractList
{
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_at desc';
    
    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Allotment_order_model', 'allotment_order_list', false, 'plan');
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
                'state'        => [
                        'desc'     => '备货状态',
                        'dropname' => 'pur_sn_state',
                        'name'     => 'state',
                        'type'     => 'intval',
                        'callback' => 'is_valid_pur_sn_state'
                ],
                'in_warehourse'        => [
                        'desc'     => '调入仓库',
                        'dropname' => 'virtual_warehouse',
                        'name'     => 'in_warehouse',
                        'type'     => 'strval',
                        'callback' => 'is_valid_virtual_warehouser'
                ],
                'out_warehourse'        => [
                        'desc'     => '调出仓库',
                        'dropname' => 'virtual_warehouse',
                        'name'     => 'out_warehouse',
                        'type'     => 'strval',
                        'callback' => 'is_valid_virtual_warehouser'
                ],
                'sku'        => [
                        'desc'     => 'sku,精确，支持多个，","分割',
                        'name'     => 'sku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_skusn'
                ],
                'pur_sn'        => [
                        'desc'     => 'pur_sn,精确，支持多个，","分割',
                        'name'     => 'pur_sn',
                        'type'     => 'strval',
                        'hook'     => 'tran_pr_sn',
                ],
                'allot_sn'        => [
                        'desc'     => '调拨单,精确，支持多个，","分割',
                        'name'     => 'allot_sn',
                        'type'     => 'strval',
                        'hook'     => 'tran_pr_sn',
                ],
                'start_date'  => [
                        'desc'     => '创建开始时间',
                        'name'     => 'created_at',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'end_date'    => [
                        'desc'     => '创建结束时间',
                        'name'     => 'created_at',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'exhaust_start'  => [
                        'desc'     => '最早缺货开始时间',
                        'name'     => 'earliest_exhaust_date',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'exhaust_end'    => [
                        'desc'     => '最早缺货结束时间',
                        'name'     => 'earliest_exhaust_date',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
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
                'isExcel'        => [
                    'desc'     => '是否是导出',
                    'name'     => 'isExcel',
                    'type'     => 'intval',
                ],
                'gidsArr'        => [
                    'desc'     => '指定导出gid',
                    'name'     => 'gidsArr',
                ],
                'is_refund_tax'  => [
                    'desc'     => '是否退税',
                    'dropname' => 'refund_tax',
                    'name'     => 'is_refund_tax',
                    'type'     => 'intval',
                    //'callback' => 'is_valid_plan_approval'
                ],
                'purchase_warehouse'  => [
                    'desc'     => '采购仓库',
                    'dropname' => 'purchase_warehouse',
                    'name'     => 'purchase_warehouse_id',
                    'type'     => 'intval',
                    //'callback' => 'is_valid_plan_approval'
                ]
        ];
        $this->_cfg = [
            'title' => [
                    'no',               'allot_sn',         'pur_sn',           'sku',
                    'is_refund_tax'     ,'purchase_warehouse_id', 'sku_name',
                    'earliest_exhaust_date','purchase_qty',     'in_warehouse',     'in_qty',
                    'out_warehouse',    'actual_purchase_qty','created_at',       'state',            'remark',
                    'op_name', 
            ],
            'select_cols' => [
                    ($this->_ci->allotment_order_list->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                    'virtual_warehouse', 'pur_sn_state','refund_tax', 'purchase_warehouse'
            ]
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
        $db = $this->_ci->allotment_order_list->getDatabase();
        $db->reset_query();
        
        $o_t = $this->_ci->allotment_order_list->getTable();
        
        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };
        
        $query = $db->from($o_t);
        
        /**
         *  备货状态
         */
        if (isset($params['state']))
        {
            $query->where("{$o_t}.state", $params['state']);
        }

        //数据库中存的是中文
        if (!empty($params['in_warehouse']) && is_numeric($params['in_warehouse'])){
            $params['in_warehouse'] = ALLOT_VM_WAREHOUSE[$params['in_warehouse']]?ALLOT_VM_WAREHOUSE[$params['in_warehouse']]['name']:$params['in_warehouse'];
        }
        if (!empty($params['out_warehouse']) && is_numeric($params['out_warehouse'])){
            $params['out_warehouse'] = ALLOT_VM_WAREHOUSE[$params['out_warehouse']]?ALLOT_VM_WAREHOUSE[$params['out_warehouse']]['name']:$params['out_warehouse'];
        }

        if (isset($params['in_warehouse']))
        {
            $query->where("{$o_t}.in_warehouse", $params['in_warehouse']);
        }
        
        if (isset($params['out_warehouse']))
        {
            $query->where("{$o_t}.out_warehouse", $params['out_warehouse']);
        }
        
        if (isset($params['allot_sn']))
        {
            if (count($sns = explode(',', $params['allot_sn'])) > 1)
            {
                $query->where_in("{$o_t}.allot_sn", $sns);
            }
            else
            {
                $query->where("{$o_t}.allot_sn", $sns[0]);
            }
        }
        
        if (isset($params['pur_sn']))
        {
            if (count($sns = explode(',', $params['pur_sn'])) > 1)
            {
                $query->where_in("{$o_t}.pur_sn", $sns);
            }
            else
            {
                $query->where("{$o_t}.pur_sn", $sns[0]);
            }
        }

        if (isset($params['gidsArr']))
        {
            $query->where_in("{$o_t}.gid", $params['gidsArr']);
        }

        if (isset($params['isExcel']))
        {
            $params['per_page'] = MAX_EXCEL_LIMIT;
        }
        
        /**
         * sku
         */
        if (isset($params['sku']))
        {
            if (count($sns = explode(',', $params['sku'])) > 1)
            {
                $query->where_in("{$o_t}.sku", $sns);
            }
            else
            {
                $query->where("{$o_t}.sku", $sns[0]);
            }
        }
       
        /**
         * 创建时间
         */
        if (isset($params['created_at']))
        {
            $query->where("{$o_t}.created_at >", $params['created_at']['start'])
            ->where("{$o_t}.created_at <", $params['created_at']['end']);
        }
        
        /**
         * 最早缺货时间
         */
        if (isset($params['earliest_exhaust_date']))
        {
            $query->where("{$o_t}.earliest_exhaust_date >=", $params['earliest_exhaust_date']['start'])
            ->where("{$o_t}.earliest_exhaust_date <", $params['earliest_exhaust_date']['end']);
        }

        /**
         * 退税
         */
        if (isset($params['is_refund_tax']))
        {
            $query->where("{$o_t}.is_refund_tax", $params['is_refund_tax']);
        }

        /**
         * 采购仓库
         */
        if (isset($params['purchase_warehouse_id']))
        {
            $query->where("{$o_t}.purchase_warehouse_id", $params['purchase_warehouse_id']);
        }


        /**
          * 导出暂存
          */
         if (isset($params['export_save']))
         {
             $query_export = clone $query;
             $query_export->select("{$o_t}.pr_sn");
             $this->_ci->load->library('rediss');
             $this->_ci->load->service('basic/SearchExportCacheService');
             $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PLAN_ALLOT_SN_SEARCH_EXPORT)->set($query_export->get_compiled_select('', false));
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
    
    /* 用户自定义处理参数的模板方法，由各自实例化类实现。
     *
     * @param unknown $defind_valid_key
     * @param unknown $col
     * @param unknown $val
     * @param unknown $format_params
     * @return boolean
     */
    protected function hook_user_format_params($defind_valid_key, $col, $val, &$format_params)
    {
        if (parent::hook_user_format_params($defind_valid_key, $col, $val, $format_params))
        {
            return true;
        }
        
        $rewrite_cols = ['exhaust_start', 'exhaust_end'];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }
        //转换预期缺货时间
        if ($col == 'exhaust_start') {
            $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
        }

        if ($col == 'exhaust_end') {
            $format_params[$defind_valid_key[$col]['name']]['end'] = $val.' 23:59:59';
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
        parent::hook_user_format_params_check($defind_valid_key, $format_params);
        
        if (isset($format_params['earliest_exhaust_date']))
        {
            $key = 'earliest_exhaust_date';
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
}
