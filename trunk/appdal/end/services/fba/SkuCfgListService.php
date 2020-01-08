<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 列表服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since
 * @link
 */
class SkuCfgListService extends AbstractList implements Rpcable
{
    use Rpc_imples;

    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_at desc, id desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_sku_cfg_model', 'm_sku_cfg', false, 'fba');
        $this->_ci->lang->load('common');

        return $this;
    }

    /**
     *
     * @param string $ids 页面选择的ids，为空表示从搜索条件获取
     * @param string $profile 用户选择导出的列
     * @param string $format_type 导出csv的格式， 可读还是用于修改的原生字段
     *
     * @throws \RuntimeException
     * @throws \OverflowException
     * @return unknown
     */
    public function quick_export($ids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8')
    {
        $db = $this->_ci->m_sku_cfg->getDatabase();
        $this->_ci->load->dbutil();

        if ($ids != '') {
            $ids_arr   = explode(',', $ids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_sku_cfg->getTable())->where_in('id', $ids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        } else {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT)->get();
            $total     = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);

            if (!$quick_sql) {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }

            if ($total > MAX_EXCEL_LIMIT) {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出更大数量的数据，请找相关负责人', MAX_EXCEL_LIMIT), 500);
                $quick_sql .= ' limit ' . MAX_EXCEL_LIMIT;
            }

            if ($total > MAX_BROWSE_LIMIT) {
                //强制转文件模式
                $data_type = VIEW_FILE;
            } else {
                if ($data_type == VIEW_AUTO) {
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
    public function get_cfg(): array
    {
        $search     = [
            'id'            => [
                'desc' => 'id',
                'name' => 'id',
                'type' => 'strval',
            ],
            'sku'           => [
                'desc'     => 'sku,精确，支持多个，","分割',
                'name'     => 'sku',
                'type'     => 'strval',
                'hook'     => 'tran_sku_sn',
                'callback' => 'is_valid_skusn'
            ],

            'is_refund_tax' => [
                'desc'     => '是否退税',
                'dropname' => 'refund_tax',
                'name'     => 'is_refund_tax',
                'type'     => 'intval',
                //'callback' => 'is_valid_plan_approval'
            ],
            'state'         => [
                'desc' => '审核状态',
                'name' => 'state',
                'type' => 'intval',
            ],

            'created_at_start'  => [
                'desc'     => '创建开始时间',
                'name'     => 'created_at_start',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'created_at_end'    => [
                'desc'     => '创建结束时间',
                'name'     => 'created_at_end',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'updated_at_start'  => [
                'desc'     => '修改开始时间',
                'name'     => 'updated_at_start',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'updated_at_end'    => [
                'desc'     => '修改结束时间',
                'name'     => 'updated_at_end',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'approved_at_start' => [
                'desc'     => '审核开始时间',
                'name'     => 'approved_at_start',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'approved_at_end'   => [
                'desc'     => '审核结束时间',
                'name'     => 'approved_at_end',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            'offset'            => [
                'name'     => 'page',
                'type'     => 'intval',
                'callback' => 'is_valid_page'
            ],
            'limit'             => [
                'name'     => 'per_page',
                'type'     => 'intval',
                'callback' => 'is_valid_pagesize'
            ],
            'export_save'       => [
                'name' => 'export_save',
                'type' => 'intval',
            ],
            'prev_salesman'     => [
                'desc' => '权限设置，账号作为销售人员',
                'name' => 'prev_salesman',
                'type' => 'strval',
            ],
            'prev_account_name' => [
                'desc' => '账号管理的亚马逊账号',
                'name' => 'prev_account_name',
                'type' => 'strval',
            ],
            'set_data_scope'    => [
                'desc' => '设置账号开启权限标记',
                'name' => 'set_data_scope',
                'type' => 'intval',
            ],
        ];
        $this->_cfg = [
            'title' => array_column($this->_ci->lang->myline('fba_stock_relationship_cfg'), 'label'),

            'select_cols'  => [
                ($this->_ci->m_sku_cfg->getTable()) => [
                    '*'
                ],
            ],
            'search_rules' => &$search,
            'droplist'     => [
                'station_code', 'refund_tax', 'check_state'
            ],
            'profile'      => 'fba_stock_relationship_cfg',
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
        $cb = function ($result, $map) {
            $my = [];
            if (isset($result['data']['items']) && $result['data']['items']) {
                foreach ($result['data']['items'] as $res) {
                    $tmp = [];
                    foreach ($res as $col => $val) {
                        $tmp[$map[$col] ?? $col] = $val;
                    }
                    $my[] = $tmp;
                }
            }

            $my_format = [
                'page_data' => [
                    'total'  => $result['data']['totalCount'],
                    'offset' => $result['data']['pageNumber'],
                    'limit'  => $result['data']['pageSize'],
                    'pages'  => $result['data']['totalPage']
                ],
                'data_list' => [
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
        if ($this->is_rpc('fba')) {
            return $this->java_search($params);
        }

        $db = $this->_ci->m_sku_cfg->getDatabase();


        $o_t = $this->_ci->m_sku_cfg->getTable();

        $append_table_prefix = function ($arr, $tbl) {
            array_walk($arr, function (&$val) use ($tbl) { $val = $tbl . $val; });

            return $arr;
        };

        $query = $db->from($o_t);

        if (isset($params['id'])) {
            if (count($sns = explode(',', $params['id'])) > 1) {
                $query->where_in("{$o_t}.id", $sns);
            } else {
                $query->where("{$o_t}.id", $sns[0]);
            }
        }
        if (isset($params['sku'])) {
            if (count($sns = explode(',', $params['sku'])) > 1) {
                $query->where_in("{$o_t}.sku", $sns);
            } else {
                $query->where("{$o_t}.sku", $sns[0]);
            }
        }
        if (isset($params['is_refund_tax'])) {
            $query->where("{$o_t}.is_refund_tax", $params['is_refund_tax']);
        }
        if (isset($params['state'])) {
            $query->where("{$o_t}.state", $params['state']);
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

        //从cfg里面获取配置
        $select_cols = '';
        foreach ($this->_cfg['select_cols'] as $tbl => $cols) {
            $select_cols .= implode(',', $append_table_prefix($cols, $tbl . '.'));
        }

        $query_counter = clone $query;
        $query_counter->select("{$o_t}.id");
        $count = $query_counter->count_all_results();
        $page  = ceil($count / $params['per_page']);

        /**
         * 导出暂存
         */
        if (isset($params['export_save'])) {
            $query_export = clone $query;
            $query_export->select($select_cols)->order_by(implode(',', $append_table_prefix($params['sort'], $o_t . '.')));
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT)->set($total . ($query_export->get_compiled_select('', false)));
        }

        //执行搜索
        $query->select($select_cols)
            ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t . '.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);

//        pr($query->get_compiled_select());
//        exit;
//        pr($query->get());
//        exit;
        $result = $query->get()->result_array();
        //供货周期
//        $result = $this->_ci->m_sku_cfg->join_lead_time($result);

        //设定统一返回格式
        return [
            'page_data' => [
                'total'  => $count,
                'offset' => $params['page'],
                'limit'  => $params['per_page'],
                'pages'  => $page
            ],
            'data_list' => [
                'value' => &$result,
            ],
        ];
    }


    public function tran_sp_info($params)
    {

        $list = RPC_CALL('YB_J1_005', [$params['created_uid']]);
        //根据uid获取用户姓名
        if ($list) {
            $uid_map = array_column($list, 'userName', 'userNumber');
        }

        $params['created_uid_cn'] = $params['created_uid'] == '' ? '' : $uid_map[$params['created_uid']] ?? $params['created_uid'];

        return $params;
    }

    /**
     * 转换
     *
     * {@inheritDoc}
     * @see AbstractList::translate()
     */
    public function translate($search_result)
    {
//        $uids = [];
//        foreach ($search_result['data_list']['value'] as $row)
//        {
//            $uids = array_merge($uids, [$row['push_uid'] ?? '', $row['created_uid'] ?? '']);
//        }
//
//        //查询用户名字
//        $uids = array_unique($uids);
//        $uids = array_filter($uids, function($val) {return !(is_numeric($val) && intval($val) == 0 || $val == '');});
//        if (!empty($uids))
//        {
//            $list = RPC_CALL('YB_J1_005', array_values($uids));
//            //根据uid获取用户姓名
//            if ($list)
//            {
//                $uid_map = array_column($list, 'userName', 'userNumber');
//            }
//        }

//        foreach ($search_result['data_list']['value'] as $row => &$val)
//        {
//            $val['push_uid_cn'] = $val['push_uid'] == '' ? '' : $uid_map[$val['push_uid']] ?? $val['push_uid'];
//            $val['created_uid_cn'] = $val['created_uid'] == '' ? '' : $uid_map[$val['created_uid']] ?? $val['created_uid'];
//        }
        return $search_result;
    }

    /**
     * 用户自定义处理参数的模板方法，由各自实例化类实现。
     *
     * @param unknown $defind_valid_key
     * @param unknown $col
     * @param unknown $val
     * @param unknown $format_params
     *
     * @return boolean
     */
    protected function hook_user_format_params($defind_valid_key, $col, $val, &$format_params)
    {
//        if (parent::hook_user_format_params($defind_valid_key, $col, $val, $format_params)) {
//            return true;
//        }
        /*
                $rewrite_cols = ['created_at_start', 'created_at_end', 'updated_at_start', 'updated_at_end', 'approved_at_start', 'approved_at_end'];
                if (!in_array($col, $rewrite_cols)) {
                    return false;
                }
                if (in_array($col,$rewrite_cols)){
                    $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
                }*/

        //转换时间
//        if ($col == 'created_at_start') {
//            if ($unix_time = strtotime($val)) {
//                $format_params[$defind_valid_key[$col]['name']]['start'] = $unix_time;
//            } else {
//                throw new \InvalidArgumentException(sprintf('无效的开始时间'), 3001);
//            }
//        }
//        if ($col == 'created_at_end') {
//            if ($unix_time = strtotime($val)) {
//                $format_params[$defind_valid_key[$col]['name']]['end'] = $unix_time;
//            } else {
//                throw new \InvalidArgumentException(sprintf('无效的结束时间'), 3001);
//            }
//        }
//
//        if ($col == 'updated_at_start') {
//            if ($unix_time = strtotime($val)) {
//                $format_params[$defind_valid_key[$col]['name']]['start'] = $unix_time;
//            } else {
//                throw new \InvalidArgumentException(sprintf('无效的更新开始时间'), 3001);
//            }
//        }
//        if ($col == 'updated_at_end') {
//            if ($unix_time = strtotime($val)) {
//                $format_params[$defind_valid_key[$col]['name']]['end'] = $unix_time;
//            } else {
//                throw new \InvalidArgumentException(sprintf('无效的更新结束时间'), 3001);
//            }
//        }
//
//        if ($col == 'approved_at_start') {
//            if ($unix_time = strtotime($val)) {
//                $format_params[$defind_valid_key[$col]['name']]['start'] = $unix_time;
//            } else {
//                throw new \InvalidArgumentException(sprintf('无效的更新开始时间'), 3001);
//            }
//        }
//        if ($col == 'approved_at_end') {
//            if ($unix_time = strtotime($val)) {
//                $format_params[$defind_valid_key[$col]['name']]['end'] = $unix_time;
//            } else {
//                throw new \InvalidArgumentException(sprintf('无效的更新结束时间'), 3001);
//            }
//        }

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
        /*
                $check_cols = ['updated_at', 'created_at', 'approved_at'];
                foreach ($check_cols as $key) {
                    if (isset($format_params[$key])) {
                        if (count($format_params[$key]) == 1) {

                            if (isset($format_params[$key]['start'])) {
                                $format_params[$key]['end'] = strtotime(date('Y-m-d'));
                                //mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('y')));
                            } else {
                                //$format_params[$key]['start'] = mktime(23, 59, 59, 1, 1, intval(date('y')));
                                $format_params[$key]['start'] = $format_params[$key]['end'];
                            }
                        }
                        if ($format_params[$key]['start'] > $format_params[$key]['end']) {
                            //交换时间
                            $tmp                          = $format_params[$key]['start'];
                            $format_params[$key]['start'] = $format_params[$key]['end'];
                            $format_params[$key]['end']   = $tmp;
                            //throw new \InvalidArgumentException(sprintf('开始时间不能晚于结束时间'), 3001);
                        }
                        //为开始日期和结束日期添加 00：00：01 和 23:59:59
                        $start                        = $format_params[$key]['start'];
                        $end                          = $format_params[$key]['end'];
                        $format_params[$key]['start'] = mktime(0, 0, 1, intval(date('m', $start)), intval(date('d', $start)), intval(date('y', $start)));
                        $format_params[$key]['end']   = mktime(23, 59, 59, intval(date('m', $end)), intval(date('d', $end)), intval(date('y', $end)));
                    }
                }*/

//        return true;
    }
}
