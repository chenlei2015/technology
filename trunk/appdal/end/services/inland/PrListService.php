<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 国内 需求列表服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class PrListService extends AbstractList implements Rpcable
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
        $this->_ci->load->model('Inland_pr_list_model', 'm_inland_pr_list', false, 'inland');
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
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8' )
    {
        $db = $this->_ci->m_inland_pr_list->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_inland_pr_list->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_PR_LIST_SEARCH_EXPORT)->get();
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
        $this->_ci->load->classes('inland/classes/InlandHugeExport');
        $this->_ci->InlandHugeExport
        ->set_format_type($format_type)
        ->set_data_type($data_type)
        ->set_out_charset($charset)
        ->set_title_map($profile)
        ->set_translator()
        ->set_data_sql($quick_sql)
        ->set_export_nums($total);
        return $this->_ci->InlandHugeExport->run();

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
                'is_trigger_pr'        => [
                        'desc'     => '是否触发需求',
                        'dropname' => 'fba_trigger_pr',
                        'name'     => 'is_trigger_pr',
                        'type'     => 'intval',
                        //'callback' => 'is_valid_trigger_pr'
                ],
                'expired'        => [
                        'desc'     => '是否过期',
                        'dropname' => 'fba_expired',
                        'name'     => 'expired',
                        'type'     => 'intval',
                        'callback' => 'is_valid_expired'
                ],
                'purchase_warehouse'  => [
                        'desc'     => '采购仓库',
                        'dropname' => 'inland_warehouse',
                        'name'     => 'purchase_warehouse_id',
                        'type'     => 'intval',
                        //'callback' => 'is_valid_plan_approval'
                ],
                'stock_up_type'        => [
                        'desc'     => '是否过期',
                        'dropname' => 'inland_stock_up',
                        'name'     => 'stock_up_type',
                        'type'     => 'intval',
                        'callback' => 'is_valid_stock_up_type'
                ],
                'pr_sn'        => [
                        'desc'     => '需求单号,精确，支持多个，","分割',
                        'name'     => 'pr_sn',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                'approval_state'        => [
                    'desc'     => '审核状态',
                    'dropname' => 'INLAND_APPROVAL_STATE',
                    'name'     => 'approve_state',
                    'type'     => 'intval',
                    'callback' => 'is_valid_inland_approval_state'
                ],
                /**
                 * sku搜索
                 */
                'sku'        => [
                        'desc'     => 'sku,精确，支持多个，","分割',
                        'name'     => 'sku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_skusn'
                ],
                'sku_state'        => [
                        'desc'     => 'sku状态',
                        'dropname' => 'inland_sku_state',
                        'name'     => 'sku_state',
                        'type'     => 'intval',
                        //'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_listing_state'
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
                    'index',                 'pr_sn',                 'sku',                   'sku_name',              'is_refund_tax',
                    'purchase_warehouse_id', 'sku_state',             'stock_up_type',         'debt_qty',              'pr_qty',
                    'ship_qty',              'available_qty',         'purchase_price',        'accumulated_order_days','accumulated_sale_qty',
                    'sale_qty_order',        'weight_sale_pcs',       'sale_sd_pcs',           'deliver_sd_day',        'supply_wa_day',
                    'buffer_pcs',            'purchase_cycle_day',    'ship_timeliness_day',   'pre_day',               'sc_day',
                    'z',                     'safe_stock_pcs',        'point_pcs',             'supply_day',            'expect_exhaust_date',
                    'require_qty',           'stocked_qty',           'is_trigger_pr',         'created_at',            'expired',
                    'remark',                'op_name',               'approve_state'
            ],
            'select_cols' => [
                    ($this->_ci->m_inland_pr_list->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                    'fba_trigger_pr', 'fba_expired', 'inland_warehouse', 'inland_stock_up_list',
                    'inland_sku_state','inland_approval_state'

            ],
            'user_profile' => 'm_inland_pr_list_cfg'
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

        $db = $this->_ci->m_inland_pr_list->getDatabase();

        $o_t = $this->_ci->m_inland_pr_list->getTable();

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

        if (isset($params['is_trigger_pr']))
        {
            $query->where("{$o_t}.is_trigger_pr", $params['is_trigger_pr']);
        }

        if (isset($params['expired']))
        {
            $query->where("{$o_t}.expired", $params['expired']);
        }

        if (isset($params['purchase_warehouse_id']))
        {
            $query->where("{$o_t}.purchase_warehouse_id", $params['purchase_warehouse_id']);
        }

        if (isset($params['stock_up_type']))
        {
            $query->where("{$o_t}.stock_up_type", $params['stock_up_type']);
        }

        if (isset($params['pr_sn']))
        {
            if (count($sns = explode(',', $params['pr_sn'])) > 1)
            {
                $query->where_in("{$o_t}.pr_sn", $sns);
            }
            else
            {
                $query->where("{$o_t}.pr_sn", $sns[0]);
            }
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

        if (isset($params['sku_state']))
        {
            $query->where("{$o_t}.sku_state", $params['sku_state']);
        }

        if (isset($params['created_at']))
        {
            $query->where("{$o_t}.created_at >=", $params['created_at']['start'])
            ->where("{$o_t}.created_at <=", $params['created_at']['end']);
        }

        if (!empty($params['approve_state']))
        {
            $query->where("{$o_t}.approve_state", $params['approve_state']);
        }
        //从cfg里面获取配置
        $select_cols = '';
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols .= implode(',', $append_table_prefix($cols, $tbl.'.'));
        }

        $query_counter = clone $query;
        $query_counter->select("{$o_t}.gid");
        /*$limit_key = $query_counter->cache_limit([
                'orderBy' => implode(',', $append_table_prefix($params['sort'], $o_t.'.')),
                'primary' => 'gid',
                'type' => 'string'
        ]);*/
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
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_PR_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }

        //执行搜索
        $query->select($select_cols)
              ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
              ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
              /*->get_cache_limit($limit_key, $count, 'gid');*/
        //pr($query->get_compiled_select('',false));exit;
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
        if (parent::hook_user_format_params($defind_valid_key, $col, $val, $format_params))
        {
            return true;
        }

        return false;
    }


    /**
     * 针对hook_user_format_params生成的参数做检测
     * {@inheritDoc}
     * @see AbstractList::hook_user_format_params()
     */
    protected function hook_user_format_params_check($defind_valid_key, &$format_params)
    {
        parent::hook_user_format_params_check($defind_valid_key, $format_params);

    }
}
