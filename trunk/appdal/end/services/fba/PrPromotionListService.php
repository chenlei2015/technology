<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * FBA 促销sku列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-06-01
 * @link
 */
class PrPromotionListService extends AbstractList
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
        $this->_ci->load->model('Fba_promotion_sku_model', 'fba_promotion_list', false, 'fba');
        $this->_ci->load->helper('fba_helper');
        return $this;
    }

    /**
     * 直接从redis里面拿sql获取订单号
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8' )
    {
        $db = $this->_ci->fba_promotion_list->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->fba_promotion_list->getTable())->where_in('gid', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_PROMOTION_SKU_EXPORT)->get();
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

        $this->_ci->load->classes('fba/classes/FbaHugePromotionExport');
        $this->_ci->FbaHugePromotionExport->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
            return $this->_ci->FbaHugePromotionExport->run();
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
                'state'        => [
                        'desc'     => '促销sku状态',
                        'name'     => 'state',
                        'type'     => 'intval',
                        'callback' => 'is_valid_promotion_sku_state'
                ],
                'sku'        => [
                        'desc'     => 'sku,精确，支持多个，","分割',
                        'name'     => 'sku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_skusn'
                ],
                'created_date'  => [
                        'desc'     => '创建当天0点时间戳',
                        'name'     => 'created_time',
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
                    'index',                 'sku',                   'created_at',            'promotion_state',       'created_uid',
                    'remark',                'op_name',
            ],
            'select_cols' => [
                    ($this->_ci->fba_promotion_list->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                    'promotion_sku_state',
            ],
            'user_profile' => 'yibai_fba_promotion_sku',

        ];
        return $this->_cfg;
    }

    /**
     * 对入参的自定义处理
     *
     * @param unknown $defind_valid_key
     * @param unknown $col
     * @param unknown $val
     * @param unknown $format_params
     * @return boolean
     */
    protected function hook_user_format_params($defind_valid_key, $col, $val, &$format_params)
    {
        $rewrite_cols = ['created_date'];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }
        if ($col == 'created_date')
        {
            //日期转当天0点的时间戳
            $date_start = strtotime($val);
            $format_params[$defind_valid_key[$col]['name']] = $date_start;
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
        return true;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {
        $db = $this->_ci->fba_promotion_list->getDatabase();

        $o_t = $this->_ci->fba_promotion_list->getTable();

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

        //加上权限限制
        /*if (isset($params['set_data_scope']))
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
        }*/

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
         * 创建时间
         */
        if (isset($params['created_time']))
        {
            $query->where("{$o_t}.created_time", $params['created_time']);
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

        if (isset($params['state']))
        {
            $query->where("{$o_t}.state", $params['state']);
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
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_PROMOTION_SKU_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
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

        $sales_group_id = $uids = $skus = [];
        foreach ($search_result['data_list']['value'] as $row)
        {
            $sales_group_id[] = $row['sale_group'] ?? 0;
            $uids = array_merge($uids, [$row['salesman']]);
        }

        $group_map = $uid_map = [];

        //查询组名
        array_unique($sales_group_id);
        $sales_group_id = array_filter($sales_group_id, function($val) {return $val != 0;});
        if (!empty($sales_group_id))
        {
            $this->_ci->load->model('Fba_amazon_group_model', 'm_amazon_group', false, 'fba');
            $group_map = $this->_ci->m_amazon_group->get_group_name($sales_group_id);
        }
        //查询用户名字
        $uids = array_unique($uids);
        $uids = array_filter($uids, function($val) {return !(is_numeric($val) && intval($val) == 0);});
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
            $val['sale_group'] = $group_map[$val['sale_group']] ?? $val['sale_group'];
            $val['salesman'] = $uid_map[$val['salesman']] ?? $val['salesman'];
        }
        return $search_result;
    }
}
