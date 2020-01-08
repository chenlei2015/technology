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
class OperationListService extends AbstractList implements Rpcable
{
    use Rpc_imples;
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_at desc, updated_at desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_sales_operation_cfg_model', 'm_operation_cfg', false, 'inland');
        $this->_ci->load->model('Inland_sales_operation_cfg_sku_model', 'm_operation_sku', false, 'inland');
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
        $db = $this->_ci->m_operation_cfg->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_operation_cfg->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_OPERATION_CFG_SEARCH_EXPORT)->get();
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
        $this->_ci->load->classes('inland/classes/InlandHugeOperationExport');
        $this->_ci->InlandHugeOperationExport
            ->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
        return $this->_ci->InlandHugeOperationExport->run();

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
                'name'     => 'updated_at',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'updated_end' => [
                'desc'     => '修改结束时间',
                'name'     => 'updated_at',
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
                'index',         'no_operation_date',    'no_operation_platform',  'no_operation_skus',  'created_info',
                'updated_info',  'remark',      'op_name',
            ],
            'select_cols' => [
                ($this->_ci->m_operation_cfg->getTable()) => [
                    '*'
                ],
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
//        print_r($params);exit;
        if ($this->is_rpc('inland'))
        {
            return $this->java_search($params);
        }

        $db = $this->_ci->m_operation_cfg->getDatabase();

        $o_t = $this->_ci->m_operation_cfg->getTable();

        $sku_t = $this->_ci->m_operation_sku->getTable();

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


        if (isset($params['created_at']))
        {
            $query->where("{$o_t}.created_at >=", $params['created_at']['start'])
                ->where("{$o_t}.created_at <=", $params['created_at']['end']);
        }
        if (isset($params['updated_at']))
        {
            $query->where("{$o_t}.updated_at >=", $params['updated_at']['start'])
                ->where("{$o_t}.updated_at <=", $params['updated_at']['end']);
        }
        $query->where("{$o_t}.is_del",0);

        $query->join("{$sku_t}","{$o_t}.gid = {$sku_t}.cfg_gid",'left');

        $query->group_by("{$o_t}.gid");

        //从cfg里面获取配置
        $select_cols = '';
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols .= implode(',', $append_table_prefix($cols, $tbl.'.'));
        }

        $select_cols .= ","."GROUP_CONCAT({$sku_t}.sku) as skus";

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
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_OPERATION_CFG_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }
        //执行搜索
        $query->select($select_cols)
            ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
//        pr($query->get_compiled_select('',false));exit;
//        pr($query->get());exit;
        $result = $query->get()->result_array();
//        echo $query->last_query();exit;

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
        $rewrite_cols = ['updated_start', 'updated_end'];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }
        //转换预期缺货时间
        if ($col == 'updated_start') {
            $format_params[$defind_valid_key[$col]['name']]['start'] = strtotime($val.'00:00:00');
        }
        if ($col == 'updated_end') {
            $format_params[$defind_valid_key[$col]['name']]['end'] = strtotime($val.'23:59:59');
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

        if (isset($format_params['updated_at']))
        {
            $key = 'updated_at';
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
