<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 备货列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-10
 * @link
 */
class PlanListService extends AbstractList
{
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_at desc,gid desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Plan_purchase_list_model', 'purchase_pr_list', false, 'plan');
        $this->_ci->load->helper('plan_helper');
        return $this;
    }
    /**
     * 直接从redis里面拿sql获取订单号
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8' )
    {
        $db = $this->_ci->purchase_pr_list->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->purchase_pr_list->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PLAN_PUR_LIST_SEARCH_EXPORT)->get();
            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);
            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }
            if ($total > MAX_EXCEL_LIMIT)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出更大数量的数据，请找相关负责人', MAX_EXCEL_LIMIT), 500);
                $quick_sql .= ' limit '.MAX_EXCEL_LIMIT;
            }

            if ($total > MAX_BROWSE_LIMIT)
            {
                //强制转文件模式
                $data_type = VIEW_FILE;
            }
            else
            {
                if ($data_type == VIEW_AUTO)
                {
                    $data_type = VIEW_BROWSER;
                }
            }
        }

        $this->_ci->load->classes('plan/classes/HugeStockExport');
        $this->_ci->HugeStockExport
        ->set_format_type($format_type)
        ->set_data_type($data_type)
        ->set_out_charset($charset)
        ->set_title_map($profile)
        ->set_translator()
        ->set_data_sql($quick_sql)
        ->set_export_nums($total);

        return $this->_ci->HugeStockExport->run();
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
                'bussiness_line'        => [
                        'desc'     => '需求业务线',
                        'dropname' => 'buss_line',
                        'name'     => 'bussiness_line',
                        'type'     => 'strval',
                        //'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_bussiness_line'
                ],
                'state'        => [
                        'desc'     => '备货状态',
                        'dropname' => 'pur_sn_state',
                        'name'     => 'state',
                        'type'     => 'intval',
                        'callback' => 'is_valid_pur_sn_state'
                ],
                'push_state'        => [
                        'desc'     => '推送状态',
                        'dropname' => 'pur_data_state',
                        'name'     => 'is_pushed',
                        'type'     => 'intval',
                        'callback' => 'is_valid_pur_data_state'
                ],
                'sku'        => [
                        'desc'     => 'sku,精确，支持多个，","分割',
                        'name'     => 'sku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_skusn'
                ],
                'sku_state'        => [
                    'desc'     => '计划系统sku状态',
                    'dropname' => 'sku_state',
                    'name'     => 'sku_state',
                    'type'     => 'intval',
                    'callback' => 'is_valid_sku_state'
                ],
                'product_status'        => [
                    'desc'     => 'erp系统sku状态',
                    'dropname' => 'product_status',
                    'name'     => 'product_status',
                    'type'     => 'intval',
//                    'callback' => 'is_valid_sku_state'
                ],
                'pur_sn'        => [
                        'desc'     => 'pur_sn,精确，支持多个，","分割',
                        'name'     => 'pur_sn',
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
                ],
                'is_boutique'  => [
                        'desc'     => '是否精品',
                        'dropname' => 'boutique_state',
                        'name'     => 'is_boutique',
                        'type'     => 'intval',
                ],
//                'select_push' => [
//                    'desc'     => '勾选是否可以推送的记录',
//                    'name'     => 'select_push',
//                    'type'     => 'intval',
//                ]
        ];
        $this->_cfg = [
            'title' => [
                'no','pur_sn','sum_sn','bussiness_line','sku_state','product_status','sku','is_refund_tax','purchase_warehouse_id','sku_name','earliest_exhaust_date',
                'total_required_qty','pr_quantity','on_way_qty','avail_qty','surplus_inventory','actual_purchase_qty','created_at','state',
                'is_pushed','remark','op_name'
            ],
            'select_cols' => [
                    ($this->_ci->purchase_pr_list->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                    'buss_line', 'pur_sn_state', 'pur_data_state','refund_tax', 'purchase_warehouse','product_status','sku_state',
                    'boutique_state'
//                'purchase_can_push'
            ],
            'user_profile' => 'stock_list',
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
        $db = $this->_ci->purchase_pr_list->getDatabase();

        $o_t = $this->_ci->purchase_pr_list->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($o_t);

        /**
         * 业务线
         */
        if (isset($params['bussiness_line']))
        {
            if (count($sns = explode(',', $params['bussiness_line'])) > 1)
            {
                $query->where_in("{$o_t}.bussiness_line", $sns);
            }
            else
            {
                $query->where("{$o_t}.bussiness_line", $sns[0]);
            }
        }

        if (isset($params['state']))
        {
            $query->where("{$o_t}.state", $params['state']);
        }

        if (isset($params['is_pushed']))
        {
            $query->where("{$o_t}.is_pushed", $params['is_pushed']);
        }

        if (isset($params['product_status']))
        {
            $query->where("{$o_t}.product_status", $params['product_status']);
        }

        if (isset($params['sku_state']))
        {
            $query->where("{$o_t}.sku_state", $params['sku_state']);
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
         * fnsku
         */
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

        if (isset($params['is_boutique']))
        {
            $query->where("{$o_t}.is_boutique", $params['is_boutique']);
        }

        /**
         * 采购仓库
         */
        if (isset($params['purchase_warehouse_id']))
        {
            $query->where("{$o_t}.purchase_warehouse_id", $params['purchase_warehouse_id']);
        }

        /**
         * 勾选了筛选的记录
         * v1.1.1 废弃 推送采购备货数量大于0的筛选查询
         */
//        if (isset($params['select_push']) && $params['select_push'] == PURCHASE_CAN_PUSH_YES)
//        {
//            $today_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
//            $today_end = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
//
//            $query->where("{$o_t}.created_at >", $today_start)
//            ->where("{$o_t}.created_at <", $today_end)
//            ->where("{$o_t}.is_pushed", PUR_DATA_UNPUSH)
//            ->where("{$o_t}.state", PUR_STATE_ING)
//            ->where("{$o_t}.push_stock_quantity > ", 0);
//        }

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

        /**
         * 导出暂存
         */
        if (isset($params['export_save']))
        {
            $query_export = clone $query;
            $query_export->select($select_cols)->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')));;
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PLAN_PUR_LIST_SEARCH_EXPORT)->set($total.$query_export->get_compiled_select('', false));
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
