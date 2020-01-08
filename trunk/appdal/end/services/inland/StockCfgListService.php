<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 国内 销量配置列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2018-12-20
 * @link
 */
class StockCfgListService extends AbstractList implements Rpcable
{
    use Rpc_imples;
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_at desc, gid desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_sku_cfg_model', 'm_sku_cfg', false, 'inland');
        $this->_ci->load->helper('inland_helper');
        return $this;
    }

    /**
     *
     * @param string $gids 页面选择的gids，为空表示从搜索条件获取
     * @param string $profile 用户选择导出的列
     * @param string $format_type 导出csv的格式， 可读还是用于修改的原生字段
     * @throws \RuntimeException
     * @throws \OverflowException
     * @return unknown
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8')
    {
        $db = $this->_ci->m_sku_cfg->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_sku_cfg->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT)->get();
            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);

            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }

            if ($total > MAX_EXCEL_LIMIT)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出30W条以上的数据，请找相关负责人', MAX_EXCEL_LIMIT), 500);
                $quick_sql .= ' limit '.MAX_EXCEL_LIMIT;
            }
        }
        $this->_ci->load->classes('inland/classes/InlandHugeStockCfgExport');
        $this->_ci->InlandHugeStockCfgExport
            ->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
        return $this->_ci->InlandHugeStockCfgExport->run();

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
            'gid' => [
                'desc'     => 'gid导出',
                'name'     => 'gid',
                'type'     => 'strval',
            ],
            'updated_start' => [
                'desc'     => '修改开始时间',
                'name'     => 'updated_at_start',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'updated_end' => [
                'desc'     => '修改结束时间',
                'name'     => 'updated_at_end',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'start_date'  => [
                'desc'     => '创建开始时间',
                'name'     => 'created_at_start',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'end_date'    => [
                'desc'     => '创建结束时间',
                'name'     => 'created_at_end',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'approved_start' => [
                'desc'     => '修改开始时间',
                'name'     => 'approved_at_start',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'approved_end' => [
                'desc'     => '修改结束时间',
                'name'     => 'approved_at_end',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'state' => [
                'desc'     => '审核状态',
                'name'     => 'state',
                'type'     => 'intval',
            ],
            'rule_type' => [
                'desc'     => '当前规则类型',
                'name'     => 'rule_type',
                'type'     => 'intval',
            ],

            'sku' => [
                'desc'     => 'SKU,多个逗号隔开',
                'name'     => 'sku',
                'type'     => 'strval',
                'hook'     => 'tran_sku_sn'
            ],
            'provider_status' => [
                'desc'     => '货源状态',
                'name'     => 'provider_status',
                'type'     => 'intval',
            ],
            'sku_state' => [
                'desc'     => 'SKU状态',
                'name'     => 'sku_state',
                'type'     => 'intval',
            ],
            'product_status' => [
                'desc'     => 'erp系统sku状态',
                'name'     => 'product_status',
                'type'     => 'intval',
            ],
            'purchase_warehouse_id' => [
                'desc'     => '采购仓库',
                'name'     => 'purchase_warehouse_id',
                'type'     => 'intval',
            ],
            'stock_way' => [
                'desc'     => '备货方式',
                'name'     => 'stock_way',
                'type'     => 'intval',
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
                'index','rule_type','state','sku','sku_name','path_name_first','is_refund_tax','purchase_warehouse_id',
                'provider_status','sku_state','product_status_cn','quality_goods','stock_way','bs','max_safe_stock_day','sp','shipment_time','first_lt','pd','reduce_factor','sz','deved_time',
                'published_time','created_at','updated_info','approved_info','remark','op_name'
            ],
            'select_cols' => [
                ($this->_ci->m_sku_cfg->getTable()) => [
                    '*'
                ],
            ],
            'droplist' => [
                'check_state', 'rule_type', 'provider_status', 'listing_state', 'inland_warehouse','inland_stock_up','inland_sku_all_state','sku_state',
//                'product_status'

            ],
            'search_rules' => &$search,
//            'user_profile' => 'm_inland_pr_list_cfg'
        ];

        return $this->_cfg;
    }

    /**
     * 执行java搜索
     */
    protected function java_search($params)
    {
        /**
         * 返回回到
         * @var unknown $cb
         */
        $cb = function($result, $map) {
            $my = [];
            if (isset($result['data']) && $result['data'])
            {
                foreach ($result['data'] as $res)
                {
                    $tmp = [];
                    foreach ($res as $col => $val)
                    {
                        $tmp[$map[$col] ?? $col] = $val;
                    }
                    $my[] = $tmp;
                }
            }

            $my_format = [
                'page_data' => [
                    'total' => $result['data']['totalCount'] ?? 0,
                    'offset'=> $result['data']['pageNumber'] ?? 1,
                    'limit' => $result['data']['pageSize'] ?? 20,
                    'pages' => $result['data']['totalPage'] ?? 0
                ],
                'data_list'  => [
                    'value' => &$my
                ]
            ];
            return $my_format;
        };

        $list = RPC_CALL('YB_J3_INLAND_001', $params, $cb, ['debug' => 1]);
        return $list;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {

        if ($this->is_rpc('inland'))
        {
            return $this->java_search($params);
        }

        $db = $this->_ci->m_sku_cfg->getDatabase();

        $o_t = $this->_ci->m_sku_cfg->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($o_t);

        if (isset($params['gid']))
        {
            if (count($sns = explode(',', $params['gid'])) > 1)
            {
                $query->where_in("{$o_t}.gid", $sns);
            }
            else
            {
                $query->where("{$o_t}.gid", $sns[0]);
            }
        }
        if (!empty($params['created_at_start']) && !empty($params['created_at_end'])) {
            $query->where('created_at >=', $params['created_at_start'] . ' 00:00:00');
            $query->where('created_at <=', $params['created_at_end'] . ' 23:59:59');
        }
        if (!empty($params['approved_at_start']) && !empty($params['approved_at_end'])) {
            $query->where('approved_at >=', $params['approved_at_start'] . ' 00:00:00');
            $query->where('approved_at <=', $params['approved_at_end'] . ' 23:59:59');
        }
        if (!empty($params['updated_at_start']) && !empty($params['updated_at_end'])) {
            $query->where('updated_at >=', $params['updated_at_start'] . ' 00:00:00');
            $query->where('updated_at <=', $params['updated_at_end'] . ' 23:59:59');
        }


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
        if (isset($params['state']))
        {
            $query->where("{$o_t}.state", $params['state']);
        }
        if (isset($params['rule_type']))
        {
            $query->where("{$o_t}.rule_type", $params['rule_type']);
        }

        if (isset($params['provider_status']))
        {
            $query->where("{$o_t}.provider_status", $params['provider_status']);
        }
        if (isset($params['sku_state']))
        {
            $query->where("{$o_t}.sku_state", $params['sku_state']);
        }
        if (isset($params['purchase_warehouse_id']))
        {
            $query->where("{$o_t}.purchase_warehouse_id", $params['purchase_warehouse_id']);
        }
        if (isset($params['stock_way']))
        {
            $query->where("{$o_t}.stock_way", $params['stock_way']);
        }
        if (isset($params['product_status'])){
            $query->where("{$o_t}.product_status", $params['product_status']);
        }





        //从cfg里面获取配置
        $select_cols = '';
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols .= implode(',', $append_table_prefix($cols, $tbl.'.'));
        }

        $query_counter = clone $query;
        $query_counter->select("{$o_t}.gid");
        $count = $query_counter->count_all_results();
        $page = ceil($count / $params['per_page']);


        /**
         * 导出暂存
         */
        if (isset($params['export_save']))
        {
            $query_export = clone $query;
            $query_export->select($select_cols)->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')));
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }
        //执行搜索
        $query->select($select_cols)
            ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
//        pr($query->get_compiled_select('',false));exit;
//        pr($query->get());exit;
        $result = $query->get()->result_array();


        //全局的属性从全局配置表获取,自定义在part表获取
        $result = $this->joinGlobal($result);

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
     * 全局的属性从全局配置表获取,自定义在part表获取
     * @param $result
     * @return mixed
     */
    public function joinGlobal($result = [])
    {
        $this->_ci->load->model('Inland_global_rule_cfg_model', 'cfgModel', false, 'inland');
        //将全局表所有信息查出来
        $g_result = $this->_ci->cfgModel->getOneGlobal();

        //更新规则类型为全局的数据
        foreach ($result as $key => $value) {
            if ($value['rule_type'] == 2) {
                $value['bs'] = $g_result['bs'];
                $value['sp'] = $g_result['sp'];
                $value['shipment_time'] = $g_result['shipment_time'];
                $value['sc'] = $g_result['sc'];
                $value['first_lt'] = $g_result['first_lt'];
                $value['sz'] = $g_result['sz'];
                $result[$key] = $value;
            } else {
                continue;
            }
        }
        return $result;

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
//        if (parent::hook_user_format_params($defind_valid_key, $col, $val, $format_params))
//        {
//            return true;
//        }
//        $rewrite_cols = ['updated_start', 'updated_end'];
//        if (!in_array($col, $rewrite_cols))
//        {
//            return false;
//        }
//        //转换预期缺货时间
//        if ($col == 'updated_start') {
//            $format_params[$defind_valid_key[$col]['name']]['start'] = strtotime($val);
//        }
//        if ($col == 'updated_end') {
//            $format_params[$defind_valid_key[$col]['name']]['end'] = strtotime($val);
//        }
//
//        return true;
    }


    /**
     * 针对hook_user_format_params生成的参数做检测
     * {@inheritDoc}
     * @see AbstractList::hook_user_format_params()
     */
    protected function hook_user_format_params_check($defind_valid_key, &$format_params)
    {
//        parent::hook_user_format_params_check($defind_valid_key, $format_params);
//
//        if (isset($format_params['updated_at']))
//        {
//            $key = 'updated_at';
//            if (count($format_params[$key]) == 1) {
//                if (isset($format_params[$key]['start']))
//                {
//                    $format_params[$key]['end'] = date('Y-m-d');
//                }
//                else
//                {
//                    //$format_params[$key]['start'] = sprintf('%s-%s-%s', date('Y'), '01', '01');
//                    $format_params[$key]['start'] = $format_params[$key]['end'];
//                }
//            }
//            if ($format_params[$key]['start'] > $format_params[$key]['end'])
//            {
//                $tmp = $format_params[$key]['start'];
//                $format_params[$key]['start'] =  $format_params[$key]['end'];
//                $format_params[$key]['end'] = $tmp;
//                //throw new \InvalidArgumentException(sprintf('开始时间不能晚于结束时间'), 3001);
//            }
//        }
    }

}
