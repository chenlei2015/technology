<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 国内 需求跟踪列表服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class PrTrackListService extends AbstractList
{
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
        $this->_ci->load->model('Inland_pr_track_list_model', 'm_inland_pr_track', false, 'inland');
        $this->_ci->load->helper('inland_helper');
        return $this;
    }

    /**
     * 直接从redis里面拿sql获取订单号
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8' )
    {
        $db = $this->_ci->m_inland_pr_track->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_inland_pr_track->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_PR_TRACK_SEARCH_EXPORT)->get();
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

        $this->_ci->load->classes('inland/classes/InlandHugeTrackExport');
        $this->_ci->InlandHugeTrackExport
        ->set_format_type($format_type)
        ->set_data_type($data_type)
        ->set_out_charset($charset)
        ->set_title_map($profile)
        ->set_translator()
        ->set_data_sql($quick_sql)
        ->set_export_nums($total);

        return $this->_ci->InlandHugeTrackExport->run();
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
                'purchase_warehouse'  => [
                        'desc'     => '采购仓库',
                        'dropname' => 'inland_warehouse',
                        'name'     => 'purchase_warehouse_id',
                        'type'     => 'intval',
                        //'callback' => 'is_valid_plan_approval'
                ],
                'pur_state'        => [
                        'desc'     => '备货单状态',
                        'dropname' => 'pur_sn_state',
                        'name'     => 'pur_state',
                        'type'     => 'intval',
                        //'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_pur_sn_state'
                ],
                'sum_sn'        => [
                        'desc'     => '汇总单号,精确，支持多个，","分割',
                        'name'     => 'sum_sn',
                        'type'     => 'strval',
                        //'hook'     => 'tran_sku_sn',
                ],
                'pr_sn'        => [
                        'desc'     => '需求单号,精确，支持多个，","分割',
                        'name'     => 'pr_sn',
                        'type'     => 'strval',
                        'hook'     => 'tran_pr_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                'sku'        => [
                        'desc'     => 'sku,精确，支持多个，","分割',
                        'name'     => 'sku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_skusn'
                ],
                'pur_sn'        => [
                        'desc'     => '备货单号,精确，支持多个，","分割',
                        'name'     => 'pur_sn',
                        'type'     => 'strval',
                        'hook'     => 'tran_pr_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                'exhaust_start'  => [
                        'desc'     => '预计缺货开始时间',
                        'name'     => 'expect_exhaust_date',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'exhaust_end'    => [
                        'desc'     => '预计缺货结束时间',
                        'name'     => 'expect_exhaust_date',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
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
        ];
        $this->_cfg = [
            'title' => [
                    'index',                 'pr_sn',                 'sku',                   'is_refund_tax',         'purchase_warehouse_id',
                    'sku_name',              'require_qty',           'expect_exhaust_date',   'sum_sn',                'pur_sn', 'stocked_qty',
                    'pur_state',             'created_at',            'remark',                'op_name',
            ],
            'select_cols' => [
                    ($this->_ci->m_inland_pr_track->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => ['inland_warehouse', 'pur_sn_state'],
            'user_profile' => 'yibai_inland_pr_track_cfg',
        ];
        return $this->_cfg;
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
        parent::hook_user_format_params_check($defind_valid_key, $format_params);

        if (isset($format_params['expect_exhaust_date']))
        {
            $key = 'expect_exhaust_date';
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
        return true;
    }


    /**
     *
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {
        $db = $this->_ci->m_inland_pr_track->getDatabase();

        $o_t = $this->_ci->m_inland_pr_track->getTable();

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

        if (isset($params['purchase_warehouse_id']))
        {
            $query->where("{$o_t}.purchase_warehouse_id", $params['purchase_warehouse_id']);
        }

        if (isset($params['pur_state']))
        {
            $query->where("{$o_t}.pur_state", $params['pur_state']);
        }

        if (isset($params['sum_sn']))
        {
            if (count($sns = explode(',', $params['sum_sn'])) > 1)
            {
                $query->where_in("{$o_t}.sum_sn", $sns);
            }
            else
            {
                $query->where("{$o_t}.sum_sn", $sns[0]);
            }
        }

        /**
         * pr_sn
         */
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

        if (isset($params['created_at']))
        {
            $query->where("{$o_t}.created_at >", $params['created_at']['start'])
            ->where("{$o_t}.created_at <", $params['created_at']['end']);
        }

        if (isset($params['expect_exhaust_date']))
        {
            $query->where("{$o_t}.expect_exhaust_date >=", $params['expect_exhaust_date']['start'])
            ->where("{$o_t}.expect_exhaust_date <=", $params['expect_exhaust_date']['end']);
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
             $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_PR_TRACK_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
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
