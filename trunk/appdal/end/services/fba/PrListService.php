<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * FBA 需求列表服务
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
    protected static $_default_sort = 'gid desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_pr_list_model', 'fba_pr_list', false, 'fba');
        $this->_ci->load->helper('fba_helper');
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
        $db = $this->_ci->fba_pr_list->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->fba_pr_list->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_PR_LIST_SEARCH_EXPORT)->get();
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
        $this->_ci->load->classes('fba/classes/FbaHugeExport');
        $this->_ci->FbaHugeExport
            ->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
        return $this->_ci->FbaHugeExport->run();

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
                /**
                 * 销售小组
                 */
                'sale_group'        => [
                        'desc'     => '销售小组',
                        'name'     => 'sale_group',
                        'type'     => 'intval',
                        //'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                /**
                 * 销售人员
                 */
                'salesman'        => [
                        'desc'     => '销售人员',
                        'name'     => 'salesman',
                        'type'     => 'strval',
                        //'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                /**
                 * 销售账号
                 */
                'account_name'        => [
                        'desc'     => '亚马逊账号',
                        'name'     => 'account_name',
                        'type'     => 'strval',
                        //'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                /**
                 * 权限范围销售人员
                 */
                'prev_salesman'        => [
                        'desc'     => '销售人员',
                        'name'     => 'prev_salesman',
                        'type'     => 'strval',
                        //'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                /**
                 * 权限范围销售账号
                 */
                'prev_account_name'        => [
                        'desc'     => '亚马逊账号',
                        'name'     => 'prev_account_name',
                        'type'     => 'strval',
                        //'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                'set_data_scope' => [
                        'desc'     => '设置权限范围',
                        'name'     => 'set_data_scope',
                        'type'     => 'intval',
                        //'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                /**
                 * 站点列表
                 */
                'station_code'        => [
                        'desc'     => '站点列表',
                        'dropname' => 'station_code',
                        'name'     => 'station_code',
                        'type'     => 'strval',
                        //'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_fba_station_code'
                ],
                /**
                 * 审核状态
                 */
                'approve_state'        => [
                        'desc'     => '审核状态',
                        'dropname' => 'fba_approval_state',
                        'name'     => 'approve_state',
                        'type'     => 'intval',
                        'callback' => 'is_valid_approval_state'
                ],
                /**
                 * 是否触发需求
                 */
                'is_trigger_pr'        => [
                        'desc'     => '是否触发需求',
                        'dropname' => 'fba_trigger_pr',
                        'name'     => 'is_trigger_pr',
                        'type'     => 'intval',
                        'callback' => 'is_valid_trigger_pr'
                ],
                'is_first_sale'        => [
                        'desc'     => '是否首发',
                        'dropname' => 'fba_first_sale',
                        'name'     => 'is_first_sale',
                        'type'     => 'intval',
                        'callback' => 'is_valid_first_sale'
                ],
                /**
                 * 是否计划审核
                 */
                'is_plan_approve'  => [
                        'desc'     => '是否计划审核',
                        'dropname' => 'fba_plan_approval',
                        'name'     => 'is_plan_approve',
                        'type'     => 'intval',
                        'callback' => 'is_valid_plan_approval'
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
                'pr_sn'        => [
                        'desc'     => '需求单号,精确，支持多个，","分割',
                        'name'     => 'pr_sn',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
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
                'seller_sku'        => [
                        'desc'     => 'seller_sku,精确，支持多个，","分割',
                        'name'     => 'seller_sku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_skusn'
                ],
                /**
                 *
                 */
                'asin'        => [
                        'desc'     => 'asin,精确，支持多个，","分割',
                        'name'     => 'asin',
                        'type'     => 'strval',
                        'hook'     => 'tran_asin',
                        //'callback' => 'is_valid_skusn'
                ],
                /**
                 * fnsku
                 */
                'fnsku'        => [
                        'desc'     => 'fnsku,精确，支持多个，","分割',
                        'name'     => 'fnsku',
                        'type'     => 'strval',
                        'hook'     => 'tran_fnsku',
                        //'callback' => 'is_valid_skusn'
                ],
                /**
                 * Listing状态
                 */
                'listing_state'        => [
                    'desc'     => 'Listing状态',
                    'dropname' => 'listing_state',
                    'name'     => 'listing_state',
                    'type'     => 'intval',
                        //'hook'     => 'tran_sku_sn',
                    'callback' => 'is_valid_listing_state'
                ],
                /**
                 * Listing状态明细
                 */
                'listing_state_text'        => [
                    'desc'     => 'Listing状态明细',
                    'dropname' => 'listing_state_text',
                    'name'     => 'listing_state_text',
                    'type'     => 'strval',
                ],
                'sku_state'        => [
                        'desc'     => 'sku状态',
                        'dropname' => 'sku_state',
                        'name'     => 'sku_state',
                        'type'     => 'intval',
                        'callback' => 'is_valid_sku_state'
                ],
                /**
                 * 是否过期
                 */
                'expired'        => [
                        'desc'     => '是否过期',
                        'dropname' => 'fba_expired',
                        'name'     => 'expired',
                        'type'     => 'intval',
                        //'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_expired'
                ],
                'required_start'        => [
                        'desc'     => '需求数量起始值',
                        'name'     => 'require_qty',
                        'type'     => 'intval',
                ],
                'required_end'        => [
                        'desc'     => '需求数量结束值',
                        'name'     => 'require_qty',
                        'type'     => 'intval',
                ],
                /**
                 * 生成时间
                 */
                'start_date'  => [
                        'desc'     => '创建开始时间',
                        'name'     => 'created_at',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                /**
                 * 生成时间
                 */
                'end_date'    => [
                        'desc'     => '创建结束时间',
                        'name'     => 'created_at',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'updated_start'  => [
                        'desc'     => '修改开始时间',
                        'name'     => 'updated_at',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'updated_end'    => [
                        'desc'     => '修改结束时间',
                        'name'     => 'updated_at',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'approved_start'  => [
                        'desc'     => '审核开始时间',
                        'name'     => 'approved_at',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'approved_end'    => [
                        'desc'     => '审核结束时间',
                        'name'     => 'approved_at',
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
                'is_boutique'  => [
                        'desc'     => '是否精品',
                        'dropname' => 'boutique_state',
                        'name'     => 'is_boutique',
                        'type'     => 'intval',
                ],
        ];
        $this->_cfg = [
            'title' => [
                    'index',                 'pr_sn',                 'approve_state',         'sku',                   'station_code',
                    'is_refund_tax',         'purchase_warehouse_id', 'fnsku',                 'seller_sku',            'asin',
                    'bd',                    'require_qty',           'stocked_qty',           'is_trigger_pr',         'trigger_mode',
                    'is_plan_approve',       'sku_name',              'sale_group',            'account_name',          'salesman',
                    'listing_state',         'logistics_id',          'weight_sale_pcs',       'sale_sd_pcs',           'avg_deliver_day',
                    'pre_day',               'safe_stock_pcs',        'point_pcs',             'sc_day',                'purchase_qty',
                    'available_qty',         'exchange_up_qty',       'oversea_ship_qty',      'supply_day',            'expect_exhaust_date',
                    'created_at',            'modify_info',           'approve_info',          'remark',                'expired',
                    'op_name',               'listing_state',
            ],
            'select_cols' => [
                    ($this->_ci->fba_pr_list->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                    'fba_sales_group', 'fba_salesman', 'station_code', 'fba_approval_state', 'fba_trigger_pr',
                    'fba_plan_approval', 'listing_state', 'fba_expired', 'refund_tax', 'fba_purchase_warehouse',
                    'boutique_state', 'sku_state', 'fba_first_sale'
            ],
            'user_profile' => 'fba_pr_list'
        ];
        return $this->_cfg;
    }

    protected function params_php_to_es($params)
    {
//        pr($params);exit;
        //从查询参数里面取回page的gid。 md5 => [ 1 => 'sfasdfs', 2 => '2323'];
        $es_query = [
            'query' => [],
            'size'  => $params['per_page'],
            'page'  => $params['page'],
        ];
        if (isset($params['sortId']) && !empty($params['sortId'])) {
            $es_query['sortId'] = $params['sortId'];
        }
        $and_cols      = ['sale_group', 'prev_salesman', 'salesman', 'station_code', 'listing_state', 'sku_state', 'is_trigger_pr', 'is_plan_approve', 'is_refund_tax', 'is_boutique', 'purchase_warehouse_id', 'expired'];
        $where_in_cols = ['gid', 'account_name', 'pr_sn', 'sku', 'seller_sku', 'fnsku', 'asin'];
        $between_cols  = ['created_at', 'updated_at', 'approved_at', 'require_qty'];
        $filter_cols   = ['per_page', 'page', 'sort'];

        foreach ($params as $col => $val) {
            if (in_array($col, $filter_cols)) {
                continue;
            }
            if (in_array($col, $and_cols)) {
                $es_query['query']['and_eq'][$col] = $val;
            } elseif (in_array($col, $where_in_cols)) {
                if (count($sns = explode(',', $params[$col])) > 1) {
                    $es_query['query']['and_in'][$col] = [$sns];
                } else {
                    $es_query['query']['and_eq'][$col] = $sns[0];
                }
            } elseif (in_array($col, $between_cols)) {
                foreach ($val as $inner => $in_val) {
                    if ($inner == 'start') {
                        $es_query['query']['and_gte'][$col] = $in_val;
                    } elseif ($inner == 'end') {
                        $es_query['query']['and_lte'][$col] = $in_val;
                    }
                }
            } elseif ($col == 'set_data_scope') {
                if (isset($params['prev_account_name']) && isset($params['prev_salesman'])) {
                    $es_query['query']['or_bool']['and_eq']['salesman'] = $params['prev_salesman'];
                    //如果选择account_name那么则取交集
                    if (isset($params['account_name'])) {
                        $prev_select    = explode(',', $params['prev_account_name']);
                        $page_select    = explode(',', $params['account_name']);
                        $account_select = array_values(array_intersect($prev_select, $page_select));
                    } else {
                        $account_select = explode(',', $params['prev_account_name']);
                    }
                    if (count($account_select) == 1) {
                        $es_query['query']['or_bool']['and_eq']['account_name'] = $account_select[0];
                    } elseif (count($account_select) > 1) {
                        $es_query['query']['or_bool']['and_eq']['account_name'] = $account_select;
                    }
                }
            } elseif ($col == 'approve_state') {
                if ($params['approve_state'] == APPROVAL_STATE_UNDO) {
                    $es_query['query']['and_bool']['or_in']['sku_state']     = [SKU_STATE_DOWN, SKU_STATE_CLEAN];
                    $es_query['query']['and_bool']['or_eq']['listing_state'] = LISTING_STATE_STOP_OPERATE;
                    $es_query['query']['and_bool']['or_lte']['require_qty']  = 0;
                } else {
                    $es_query['query']['not_in']['sku_state']               = [SKU_STATE_DOWN, SKU_STATE_CLEAN];
                    $es_query['query']['and_eq']['listing_state']           = LISTING_STATE_STOP_OPERATE;
                    $es_query['query']['and_eq']['not_eq']['is_refund_tax'] = REFUND_TAX_UNKNOWN;
                    $es_query['query']['and_gt']['require_qty']             = 0;
                    if (date('N') == 2) {
                        $es_query['query']['and_in']['deny_approve_reason'] = [DENY_APPROVE_NONE, DENY_APPROVE_HALT_SKU];
                    } else {
                        $es_query['query']['and_eq']['deny_approve_reason'] = DENY_APPROVE_NONE;
                    }
                }
            }
        }
//        pr($es_query);exit;
//echo json_encode($es_query);exit;
        return $es_query;
    }
    /**
     * 执行java搜索
     */
    protected function java_search($params)
    {
        //获取sortId
        $this->_ci->load->library('rediss');
        $this->_ci->load->service('basic/SearchExportCacheService');
        $sortId           = $this->_ci->searchexportcacheservice->setESScene($this->_ci->searchexportcacheservice::FBA_PR_LIST_SEARCH_EXPORT)->get();
        $params['sortId'] = $sortId;
        $es_query         = $this->params_php_to_es($params);
        /**
         * 返回回到
         * @var unknown $cb
         */
        $cb = function($result, $map) {
            $my = [];
//            pr($result);exit;
            if (isset($result['data']['content']) && $result['data']['content'])
            {
                $my = $result['data']['content'];
            }

            $my_format = [
                    'page_data' => [
                        'total'  => $result['data']['totalElements'] ?? 0,
                        'offset' => $result['data']['Number'] ?? 1,
                        'limit'  => $result['data']['Size'] ?? 20,
                        'pages'  => $result['data']['totalPages'] ?? 0
                    ],
                    'data_list'  => [
                            'value' => &$my
                    ]
            ];
            $end       = end($my);
            $sortId    = $end['gid'];
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $this->_ci->searchexportcacheservice->setESScene($this->_ci->searchexportcacheservice::FBA_PR_LIST_SEARCH_EXPORT)->set($sortId);


            return $my_format;
        };
//        echo json_encode($es_query);exit;
//        pr($es_query);exit;
        $list = RPC_CALL('YB_ES_FBA_PR', $es_query, $cb, ['debug' => 1]);
//        pr($list);exit;
//        $this->_ci->rpc->debug();exit;
        return $list;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {
        if ($this->is_rpc('fba'))
        {
            return $this->java_search($params);
        }

        $db = $this->_ci->fba_pr_list->getDatabase();

        $o_t = $this->_ci->fba_pr_list->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($o_t);

        if (isset($params['sale_group']))
        {
            $query->where("{$o_t}.sale_group", $params['sale_group']);
        }

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

        //加上权限限制
        if (isset($params['set_data_scope']))
        {
            if (isset($params['prev_account_name']) && isset($params['prev_salesman']))
            {
                //salesman or account_name
                $query->group_start();
                $query->or_where("{$o_t}.salesman", $params['prev_salesman']);
                //如果选择account_name那么则取交集
                if (isset($params['account_name']))
                {
                    $prev_select = explode(',', $params['prev_account_name']);
                    $page_select = explode(',', $params['account_name']);
                    $account_select = array_values(array_intersect($prev_select, $page_select));
                }
                else
                {
                    $account_select = explode(',', $params['prev_account_name']);
                }

                if (count($account_select) == 1 )
                {
                    $query->or_where("{$o_t}.account_name", $account_select[0]);
                }
                elseif(count($account_select) > 1 )
                {
                    $query->or_where_in("{$o_t}.account_name", $account_select);
                }
                //end or group
                $query->group_end();
            }
            elseif (isset($params['prev_salesman']))
            {
                $query->where("{$o_t}.salesman", $params['prev_salesman']);
            }
            elseif (isset($params['prev_account_name']))
            {
                if (count($sns = explode(',', $params['prev_account_name'])) > 1)
                {
                    $query->where_in("{$o_t}.account_name", $sns);
                }
                else
                {
                    $query->where("{$o_t}.account_name", $sns[0]);
                }
            }
        }

        /**
         * 销售人员
         */
        if (isset($params['salesman']))
        {
            $query->where("{$o_t}.salesman", $params['salesman']);
        }

        /**
         * 销售账号
         */
        if (isset($params['account_name']))
        {
            if (count($sns = explode(',', $params['account_name'])) > 1)
            {
                $query->where_in("{$o_t}.account_name", $sns);
            }
            else
            {
                $query->where("{$o_t}.account_name", $sns[0]);
            }
        }

        /**
         * 站点
         */
        if (isset($params['station_code']))
        {
            $query->where("{$o_t}.station_code", $params['station_code']);
        }

        /**
         * 审核状态
         */
        if (isset($params['approve_state']))
        {
            if ($params['approve_state'] == APPROVAL_STATE_UNDO)
            {
                $query->group_start();
                $query->or_where_in("{$o_t}.sku_state", [SKU_STATE_DOWN, SKU_STATE_CLEAN])
                ->or_where("{$o_t}.listing_state", LISTING_STATE_STOP_OPERATE)
                ->or_where("{$o_t}.require_qty <=", 0);
                $query->group_end();
            }
            else
            {
                $query->where("{$o_t}.approve_state", $params['approve_state'])
                ->where_not_in("{$o_t}.sku_state", [SKU_STATE_DOWN, SKU_STATE_CLEAN])
                ->where("{$o_t}.listing_state", LISTING_STATE_OPERATING)
                ->where("{$o_t}.is_refund_tax !=", REFUND_TAX_UNKNOWN)
                ->where("{$o_t}.require_qty >", 0);

                if (date('N') == 2)
                {
                    $query->where_in("{$o_t}.deny_approve_reason", [DENY_APPROVE_NONE, DENY_APPROVE_HALT_SKU]);
                } else {
                    $query->where("{$o_t}.deny_approve_reason", DENY_APPROVE_NONE);
                }
            }
        }

        /**
         * listing状态
         */
        if (isset($params['listing_state']))
        {
            $query->where("{$o_t}.listing_state", $params['listing_state']);
        }

        if (isset($params['sku_state']))
        {
            $query->where("{$o_t}.sku_state", $params['sku_state']);
        }


        /**
         * 是否触发需求
         */
        if (isset($params['is_trigger_pr']))
        {
            $query->where("{$o_t}.is_trigger_pr", $params['is_trigger_pr']);
        }

        if (isset($params['is_first_sale']))
        {
            $query->where("{$o_t}.is_first_sale", $params['is_first_sale']);
        }

        /**
         * 是否计划审核
         */
        if (isset($params['is_plan_approve']))
        {
            $query->where("{$o_t}.is_plan_approve", $params['is_plan_approve']);
        }

        if (isset($params['is_refund_tax']))
        {
            $query->where("{$o_t}.is_refund_tax", $params['is_refund_tax']);
        }

        /**
         * 是否精品
         */
        if (isset($params['is_boutique']))
        {
            $query->where("{$o_t}.is_boutique", $params['is_boutique']);
        }

        if (isset($params['purchase_warehouse_id']))
        {
            $query->where("{$o_t}.purchase_warehouse_id", $params['purchase_warehouse_id']);
        }

        /**
         * 是否过期
         */
        if (isset($params['expired']))
        {
            $query->where("{$o_t}.expired", $params['expired']);
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

        if (isset($params['seller_sku']))
        {
            if (count($sns = explode(',', $params['seller_sku'])) > 1)
            {
                $query->where_in("{$o_t}.seller_sku", $sns);
            }
            else
            {
                $query->where("{$o_t}.seller_sku", $sns[0]);
            }
        }

        /**
         * fnsku
         */
        if (isset($params['fnsku']))
        {
            if (count($sns = explode(',', $params['fnsku'])) > 1)
            {
                $query->where_in("{$o_t}.fnsku", $sns);
            }
            else
            {
                $query->where("{$o_t}.fnsku", $sns[0]);
            }
        }

        /**
         * asin
         */
        if (isset($params['asin']))
        {
            if (count($sns = explode(',', $params['asin'])) > 1)
            {
                $query->where_in("{$o_t}.asin", $sns);
            }
            else
            {
                $query->where("{$o_t}.asin", $sns[0]);
            }
        }

        if (isset($params['require_qty']))
        {
            $query->where("{$o_t}.require_qty >=", $params['require_qty']['start'])
            ->where("{$o_t}.require_qty <=", $params['require_qty']['end']);
        }

        /**
         * 创建时间
         */
        if (isset($params['created_at']))
        {
            $query->where("{$o_t}.created_at >=", $params['created_at']['start'])
            ->where("{$o_t}.created_at <=", $params['created_at']['end']);
        }

        /**
         * 更新时间
         */
        if (isset($params['updated_at']))
        {
            $query->where("{$o_t}.updated_at >=", $params['updated_at']['start'])
            ->where("{$o_t}.updated_at <=", $params['updated_at']['end']);
        }

        /**
         * 审核时间
         */
        if (isset($params['approved_at']))
        {
            $query->where("{$o_t}.approved_at >=", $params['approved_at']['start'])
            ->where("{$o_t}.approved_at <=", $params['approved_at']['end']);
        }

        if (isset($params['require_qty']))
        {
            $query->where("{$o_t}.require_qty >=", $params['require_qty']['start'])
            ->where("{$o_t}.require_qty <=", $params['require_qty']['end']);
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
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_PR_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }

        //执行搜索
        $query->select($select_cols)
              ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
              ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
              /*->get_cache_limit($limit_key, $count, 'gid');*/

//        pr($query->get_compiled_select('',false));exit;
//        pr($query->get());exit;
        $result = $query->get()->result_array();
        $sortId = end($result)['gid']??'';
        if (!empty($sortId)) {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $this->_ci->searchexportcacheservice->setESScene($this->_ci->searchexportcacheservice::FBA_PR_LIST_SEARCH_EXPORT)->set($sortId);
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
//        pr($search_result);exit;
        $sales_group_id = $uids = $saleman_uid = $skus_logistics = $seller_sku_hashs = [];
        foreach ($search_result['data_list']['value'] as $row)
        {
            $sales_group_id[] = $row['sale_group'] ?? 0;
            $uids = array_merge($uids, [$row['salesman'], $row['updated_uid'] ?? '', $row['approved_uid'] ?? '']);
            $seller_sku_hashs[] = md5($row['station_code'].$row['account_name'].$row['asin']);
            $skus_logistics[$row['gid']] = [
                    'logistics_id' => $row['logistics_id'],
                    'hash' => md5($row['sku'].$row['station_code'].$row['asin'].$row['seller_sku']),
                    'highlight' => 'N'
            ];
        }
        $group_map = $uid_map = $sales_map = [];

        //查询组名
        array_unique($sales_group_id);
        $sales_group_id = array_filter($sales_group_id, function($val) {return $val != 0;});
        if (!empty($sales_group_id))
        {
            $this->_ci->load->model('Fba_amazon_group_model', 'm_amazon_group', false, 'fba');
            $group_map = $this->_ci->m_amazon_group->get_group_name($sales_group_id);
        }

        //查询用户名字-员工工号
        $uids = array_unique($uids);
        $uids = array_filter($uids, function($val) {return !(is_numeric($val) && intval($val) == 0 || $val == '' || $val == ' ');});
        if (!empty($uids))
        {
            $list = RPC_CALL('YB_J1_005', array_values($uids));
            //根据uid获取用户姓名
            if ($list)
            {
                $uid_map = array_column($list, 'userName', 'userNumber');
            }
        }

        //查询高亮显示的seller_sku
        $this->_ci->load->model('Fba_diff_seller_sku_model', 'm_diff_seller_sku', false, 'fba');
        $seller_sku_hashs = key_by($this->_ci->m_diff_seller_sku->get_exists_hash($seller_sku_hashs), 'hash');

        //查询有无高亮显示的sku
        $this->get_highlight_sku($skus_logistics);
        $this->_ci->load->service('fba/PrService');

        foreach ($search_result['data_list']['value'] as $row => &$val)
        {
            $val['sale_group']                = $val['sale_group'] == '' ?  '' : $group_map[$val['sale_group']] ?? $val['sale_group'];
            $val['salesman']                  = $val['salesman'] == '' ? '' : $uid_map[$val['salesman']] ?? $val['salesman'];
            $val['updated_uid_cn']            = $val['updated_uid'] == '' ? '' : $uid_map[$val['updated_uid']] ?? $val['updated_uid'];
            $val['approved_uid_cn']           = $val['approved_uid'] == '' ? '' : $uid_map[$val['approved_uid']] ?? $val['approved_uid'];
            $val['bd_highlight']              = $skus_logistics[$val['gid']]['highlight'] ?? 'N';
            $hash = md5($val['station_code'].$val['account_name'].$val['asin']);
            $val['diff_seller_sku_highlight'] = isset($seller_sku_hashs[$hash]) ? 'Y' : 'N';
            //$val['country_name']              = empty($val['country_code']) ? ( FBA_STATION_CODE[strtolower($val['station_code'])]['name'] ?? '' ) : FBA_STATION_CODE[strtolower($val['country_code'])]['name'];
            //15天销量,30天销量
            if (isset($val['ext_trigger_info'])) {
                $sale_info           = $this->_ci->prservice->parse_ext_trigger_info($val['ext_trigger_info']);
                $val['sales_15_day'] = $sale_info['sales_15_day']??'';
                $val['sales_30_day'] = $sale_info['sales_30_day']??'';
            }

        }
        return $search_result;
    }

    /**
     * 获取高亮sku
     *
     * @param unknown $list_rows
     */
    protected function get_highlight_sku(&$list_rows)
    {
        //判断是否处于高亮时间范围
        //listing -> logistist_id -> now_start >= bd_time + 15 + cfg_day * 86400, 不显示
        $this->_ci->load->model('Bd_list_model', 'm_bd_list', false, 'fba');
        $exists_hash = $this->_ci->m_bd_list->get_exists_hash(BUSSINESS_FBA, array_column($list_rows, 'hash'));
        if (empty($exists_hash))
        {
            return;
        }

        $now_start = strtotime(date('Y-m-d'));
        $exists_hash = key_by($exists_hash, 'hash');
        $this->_ci->load->model('Weight_sale_cfg_model', 'm_weight_cfg', false, 'mrp');
        $logistics_cfg = key_by($this->_ci->m_weight_cfg->get_logistics_longest_days(), 'logistics_id');

        foreach ($list_rows as $gid => &$info)
        {
            if (!isset($exists_hash[$info['hash']])) continue;
            $info['highlight'] = $now_start >= $exists_hash[$info['hash']]['bd_time'] + ($logistics_cfg[$info['logistics_id']]['longest_days'] ?? 0 + HIGHLIGHT_VALID_DAYS) * 86400 ? 'N' : 'Y';
        }
        return;
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

        $rewrite_cols = ['updated_start', 'updated_end', 'approved_start', 'approved_end', 'required_start', 'required_end'];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }
        //转换更新时间
        //转换更新时间
        if ($col == 'updated_start')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $unix_time;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的更新开始时间'), 3001);
            }
        }
        if ($col == 'updated_end')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $unix_time;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的更新结束时间'), 3001);
            }
        }

        if ($col == 'approved_start')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $unix_time;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的审核开始时间'), 3001);
            }
        }
        if ($col == 'approved_end')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $unix_time;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的审核结束时间'), 3001);
            }
            $val = strtotime($val);
        }

        if ($col == 'required_start')
        {
            $format_params[$defind_valid_key[$col]['name']]['start'] = intval($val);
        }

        if ($col == 'required_end')
        {
            $format_params[$defind_valid_key[$col]['name']]['end'] = intval($val);
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

        $check_cols = ['updated_at', 'approved_at'];
        foreach ($check_cols as $key)
        {
            if (isset($format_params[$key]))
            {
                if (count($format_params[$key]) == 1) {

                    if (isset($format_params[$key]['start']))
                    {
                        $format_params[$key]['end'] = strtotime(date('Y-m-d'));
                        //mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('y')));
                    }
                    else
                    {
                        //$format_params[$key]['start'] = mktime(23, 59, 59, 1, 1, intval(date('y')));
                        $format_params[$key]['start'] = $format_params[$key]['end'];
                    }
                }
                if ($format_params[$key]['start'] > $format_params[$key]['end'])
                {
                    //交换时间
                    $tmp = $format_params[$key]['start'];
                    $format_params[$key]['start'] =  $format_params[$key]['end'];
                    $format_params[$key]['end'] = $tmp;
                    //throw new \InvalidArgumentException(sprintf('开始时间不能晚于结束时间'), 3001);
                }
                //为开始日期和结束日期添加 00：00：01 和 23:59:59
                $start = $format_params[$key]['start'];
                $end = $format_params[$key]['end'];
                $format_params[$key]['start'] = mktime(0, 0, 1, intval(date('m', $start)), intval(date('d', $start)), intval(date('y', $start)));
                $format_params[$key]['end'] = mktime(23, 59, 59, intval(date('m', $end)), intval(date('d', $end)), intval(date('y', $end)));
            }
        }

        $key = 'require_qty';
        if (isset($format_params[$key]))
        {
            if (count($format_params[$key]) == 1) {

                if (isset($format_params[$key]['start']))
                {
                    $format_params[$key]['end'] = 99999;
                }
                else
                {
                    $format_params[$key]['start'] = 0;
                }
            }
            $start = $format_params[$key]['start'];
            $end = $format_params[$key]['end'];
            $format_params[$key]['start'] = min($start, $end);
            $format_params[$key]['end'] = max($start, $end);
        }

        return true;
    }
}
