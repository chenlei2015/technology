<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 海外仓需求列表服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-08
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
        $this->_ci->load->model('Oversea_pr_list_model', 'oversea_pr_list', false, 'oversea');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }

    /**
     * 直接从redis里面拿sql获取订单号
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8' )
    {
        $db = $this->_ci->oversea_pr_list->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->oversea_pr_list->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_PR_LIST_SEARCH_EXPORT)->get();
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

        $this->_ci->load->classes('oversea/classes/OverseaHugeExport');
        $this->_ci->OverseaHugeExport
        ->set_format_type($format_type)
        ->set_data_type($data_type)
        ->set_out_charset($charset)
        ->set_title_map($profile)
        ->set_translator()
        ->set_data_sql($quick_sql)
        ->set_export_nums($total);

        return $this->_ci->OverseaHugeExport->run();
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
                        'desc'     => 'gid',
                        'name'     => 'gid',
                        'type'     => 'strval',
                ],
                /**
                 * 海外仓站点列表
                 */
                'station_code'        => [
                        'desc'     => '海外仓站点列表',
                        'dropname' => 'os_station_code',
                        'name'     => 'station_code',
                        'type'     => 'strval',
                        //'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_oversea_station_code'
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
                'sku_state'  => [
                        'desc'     => '计划系统Sku状态',
                        'dropname' => 'sku_state',
                        'name'     => 'sku_state',
                        'type'     => 'intval',
                        'callback' => 'is_valid_sku_state'
                ],
                'product_status'  => [
                    'desc'     => 'erp系统Sku状态',
                    'dropname' => 'product_status',
                    'name'     => 'product_status',
                    'type'     => 'intval',
//                    'callback' => 'is_valid_sku_state'
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
                        'name'     => 'page',
                        'type'     => 'intval',
                        'callback' => 'is_valid_page'
                ],
                'limit'        => [
                        'name'     => 'per_page',
                        'type'     => 'intval',
                        'callback' => 'is_valid_pagesize'
                ],
                'export_save'      => [
                        'name'     => 'export_save',
                        'type'     => 'intval',
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
                'owner_station'   => [
                        'desc' => '数据权限设置',
                        'name' => 'owner_station',
                ],
                'is_boutique'  => [
                        'desc'     => '是否精品',
                        'dropname' => 'boutique_state',
                        'name'     => 'is_boutique',
                        'type'     => 'intval',
                ],
        ];
        $this->_cfg = [
            /*'title' => [
                    'index',               'pr_sn',            'approve_state',    'sku',              'station_code',
                    'is_refund_tax',    'purchase_warehouse_id', 'sku_name',
                    'sku_state',        'logistics_id',     'available_qty',    'oversea_up_qty',
                    'oversea_ship_qty', 'sc_day',           'deviate_28_pcs',   'avg_weight_sale_pcs','avg_deliver_day',
                    'z',                'safe_stock_pcs',   'weight_sale_pcs',  'pre_day',          'point_pcs',
                    'purchase_qty',     'supply_day',       'expect_exhaust_date','bd',               'require_qty',
                    'stocked_qty',      'is_trigger_pr',    'is_approve',       'created_at',       'updated_info',
                    'approve_info',     'remark',           'expired',          'op_name',
            ],*/
            'title' => [
                    'index',                 'pr_sn',                 'sku',                   'station_code',          'is_refund_tax',
                    'purchase_warehouse_id', 'require_qty_sum',       'require_qty',           'stocked_qty',           'is_trigger_pr',
                    'sku_name',
                    'sku_state',             'product_status',           'logistics_id',          'weight_sale_pcs',       'sale_sd_pcs',           'pre_day',
                    'z',                     'safe_stock_pcs',        'point_pcs',             'purchase_qty',          'available_qty',
                    'oversea_up_qty',        'oversea_ship_qty',      'supply_day',            'expect_exhaust_date',   'created_at',
                    'updated_info',          /*'approve_info',*/      'remark',               /*'expired',*/               'op_name',
            ],
            'select_cols' => [
                    ($this->_ci->oversea_pr_list->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                    'os_station_code',  'oversea_approval_state', 'fba_expired', 'fba_trigger_pr', 'fba_plan_approval',
                    'sku_state', 'refund_tax_unknown', 'oversea_purchase_warehouse', 'boutique_state','product_status'
            ],
            'user_profile' => 'oversea_pr_list',

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
            if (isset($result['data']['items']) && $result['data']['items'])
            {
                foreach ($result['data']['items'] as $res)
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
                            'total' => $result['data']['totalCount'],
                            'offset'=> $result['data']['pageNumber'],
                            'limit' => $result['data']['pageSize'],
                            'pages' => $result['data']['totalPage']
                    ],
                    'data_list'  => [
                            'value' => &$my
                    ]
            ];
            return $my_format;
        };

        $list = RPC_CALL('YB_J2_OVERSEA_001', $params, $cb, ['debug' => 1]);
        //$this->_ci->rpc->debug();exit;
        return $list;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {
        if ($this->is_rpc('oversea'))
        {
            return $this->java_search($params);
        }

        $empty_return = [
                'page_data' => [
                        'total' => 0,
                        'offset' => $params['page'],
                        'limit' => $params['per_page'],
                        'pages' => 0
                ],
                'data_list'  => [
                        'value' => []
                ]
        ];

        if (isset($params['owner_station']))
        {
            $my_stations = $params['owner_station'];
            if (isset($params['station_code']))
            {
                if (!in_array($params['station_code'], $my_stations))
                {
                    //选择的站点不在自己的权限范围内
                    return $empty_return;
                }
            }
            else
            {
                $params['station_code'] = $params['owner_station'];
            }
            unset($params['owner_station']);
        }

        $db = $this->_ci->oversea_pr_list->getDatabase();

        $o_t = $this->_ci->oversea_pr_list->getTable();

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

        /**
         * 站点
         */
        if (isset($params['station_code']))
        {
            if (is_array($params['station_code']) && count($params['station_code']) > 1)
            {
                $query->where_in("{$o_t}.station_code", $params['station_code']);
            }
            else
            {
                $query->where("{$o_t}.station_code", is_array($params['station_code']) ? $params['station_code'][0] : $params['station_code']);
            }
        }

        /**
         * 审核状态
         */
        if (isset($params['approve_state']))
        {
            $query->where("{$o_t}.approve_state", $params['approve_state']);
        }

        /**
         * 是否触发需求
         */
        if (isset($params['is_trigger_pr']))
        {
            $query->where("{$o_t}.is_trigger_pr", $params['is_trigger_pr']);
        }

        /**
         * 是否计划审核
         */
        if (isset($params['is_plan_approve']))
        {
            $query->where("{$o_t}.is_plan_approve", $params['is_plan_approve']);
        }

        /**
         * 是否精品
         */
        if (isset($params['is_boutique']))
        {
            $query->where("{$o_t}.is_boutique", $params['is_boutique']);
        }

        /**
         * 是否过期
         */
        if (isset($params['expired']))
        {
            $query->where("{$o_t}.expired", $params['expired']);
        }

        /**
         * 计划系统sku状态
         */
        if (isset($params['sku_state']))
        {
            $query->where("{$o_t}.sku_state", $params['sku_state']);
        }

        /**
         * erp系统sku状态
         */
        if (isset($params['product_status']))
        {
            $query->where("{$o_t}.product_status", $params['product_status']);
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

        if (isset($params['is_refund_tax']))
        {
            $query->where("{$o_t}.is_refund_tax", $params['is_refund_tax']);
        }

        if (isset($params['purchase_warehouse_id']))
        {
            $query->where("{$o_t}.purchase_warehouse_id", $params['purchase_warehouse_id']);
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

        /**
         * 记录是否显示
         */
        $query->where("{$o_t}.display", OVERSEA_STATION_DISPLAY_YES);

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
             $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_PR_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
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

        $uids = [];
        foreach ($search_result['data_list']['value'] as $row)
        {
            $uids = array_merge($uids, [$row['updated_uid'] ?? '', $row['approved_uid'] ?? '']);
        }

        //查询用户名字
        $uids = array_unique($uids);
        $uids = array_filter($uids, function($val) {return !(is_numeric($val) && intval($val) == 0 || $val == '');});
        if (!empty($uids))
        {
            $list = RPC_CALL('YB_J1_005', array_values($uids));
            //根据uid获取用户姓名
            if ($list)
            {
                $uid_map = array_column($list, 'userName', 'userNumber');
            }
        }
        foreach ($search_result['data_list']['value'] as $row => &$val)
        {
            $val['updated_uid_cn'] = $val['updated_uid'] == '' ? '' : $uid_map[$val['updated_uid']] ?? $val['updated_uid'];
            $val['approved_uid_cn'] = $val['approved_uid'] == '' ? '' : $uid_map[$val['approved_uid']] ?? $val['approved_uid'];
        }
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

        $rewrite_cols = ['updated_start', 'updated_end', 'approved_start', 'approved_end', 'required_start', 'required_end'];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }

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
