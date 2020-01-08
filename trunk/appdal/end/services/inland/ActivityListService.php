<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 国内 活动列表服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class ActivityListService extends AbstractList
{
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'id desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_activity_list_model', 'm_inland_activity', false, 'inland');
        $this->_ci->load->helper('inland_helper');
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
        $db = $this->_ci->m_inland_activity->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_inland_activity->getTable())->where_in('id', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_ACTIVITY_SEARCH_EXPORT)->get();
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
        $this->_ci->load->classes('inland/classes/InlandActivityHugeExport');
        $this->_ci->InlandActivityHugeExport
            ->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
        return $this->_ci->InlandActivityHugeExport->run();

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
                'id' => [
                        'desc'     => 'id导出',
                        'name'     => 'gid',
                        'type'     => 'intval',
                ],
                /**
                 * 审核状态
                 */
                'approve_state'        => [
                        'desc'     => '审核状态',
                        'dropname' => 'fba_approval_state',
                        'name'     => 'approve_state',
                        'type'     => 'intval',
                        'callback' => 'is_valid_activity_approval_state'
                ],
                'erpsku'        => [
                        'desc'     => 'seller_sku,精确，支持多个，","分割',
                        'name'     => 'erpsku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'tran_split_search'
                ],
                'platform_code'        => [
                        'desc'     => '平台',
                        'dropname' => 'inland_platform_code',
                        'name'     => 'platform_code',
                        'type'     => 'strval',
                        //'callback' => ''
                ],
                'activity_state'        => [
                    'desc'     => '活动状态',
                    'dropname' => 'fba_activity_state',
                    'name'     => 'activity_state',
                    'type'     => 'intval',
//                    'callback' => 'is_valid_activity_state'
                ],
                'required_start'        => [
                        'desc'     => '活动量起始值',
                        'name'     => 'amount',
                        'type'     => 'intval',
                ],
                'required_end'        => [
                        'desc'     => '活动数量结束值',
                        'name'     => 'amount',
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
                'execute_start'  => [
                        'desc'     => '开始备货开始时间',
                        'name'     => 'execute_purcharse_time',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'execute_end'    => [
                        'desc'     => '开始备货结束时间',
                        'name'     => 'execute_purcharse_time',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'activity_start_start'  => [
                        'desc'     => '活动开始开始时间',
                        'name'     => 'activity_start_time',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'activity_start_end'    => [
                        'desc'     => '活动结束时间',
                        'name'     => 'activity_start_time',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'activity_end_start'  => [
                        'desc'     => '活动结束开始时间',
                        'name'     => 'activity_end_time',
                        'type'     => 'strval',
                        'callback' => 'is_valid_date'
                ],
                'activity_end_end'    => [
                        'desc'     => '活动结束结束时间',
                        'name'     => 'activity_end_time',
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
            ],
            'select_cols' => [
                ($this->_ci->m_inland_activity->getTable()) => [
                    '*'
                ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                    'fba_activity_approve_state', 'fba_activity_state', 'inland_platform_code'
            ],
            'user_profile' => 'inland_activity'
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
        $db = $this->_ci->m_inland_activity->getDatabase();

        $o_t = $this->_ci->m_inland_activity->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($o_t);

        if (isset($params['id']))
        {
            if (count($sns = explode(',', $params['id'])) > 1)
            {
                $query->where_in("{$o_t}.id", $sns);
            }
            else
            {
                $query->where("{$o_t}.id", $sns[0]);
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
         * 活动状态状态
         */
        if (isset($params['activity_state']))
        {
            switch ($params['activity_state']){
                case ACTIVITY_STATE_DISCARD:
                    $query->where("{$o_t}.activity_state", ACTIVITY_STATE_DISCARD);//废弃
                    break;
                case ACTIVITY_STATE_NOT_START:
                    $query->where("{$o_t}.activity_state", ACTIVITY_STATE_NOT_START)->where("{$o_t}.activity_start_time >", date("Y-m-d H:i:s",time()));//未开始
                    break;
                case ACTIVITY_STATE_ING:
                    $query->where("{$o_t}.activity_state", ACTIVITY_STATE_NOT_START)->where("{$o_t}.activity_start_time <=", date("Y-m-d H:i:s", time()))->where("{$o_t}.activity_end_time >", date("Y-m-d H:i:s", time()));//进行中
                    break;
                case ACTIVITY_STATE_END:
                    $query->where("{$o_t}.activity_state", ACTIVITY_STATE_NOT_START)->where("{$o_t}.activity_end_time <", date("Y-m-d H:i:s", time()));//结束
                    break;
            }

        }

        if (isset($params['platform_code']))
        {
            $query->where("{$o_t}.platform_code", $params['platform_code']);
        }

        if (isset($params['erpsku']))
        {
            if (count($sns = explode(',', $params['erpsku'])) > 1)
            {
                $query->where_in("{$o_t}.erpsku", $sns);
            }
            else
            {
                $query->where("{$o_t}.erpsku", $sns[0]);
            }
        }

        if (isset($params['amount']))
        {
            $query->where("{$o_t}.amount >=", $params['amount']['start'])
            ->where("{$o_t}.amount <=", $params['amount']['end']);
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

        if (isset($params['execute_purcharse_time']))
        {
            $query->where("{$o_t}.execute_purcharse_time >=", $params['execute_purcharse_time']['start'])
            ->where("{$o_t}.execute_purcharse_time <=", $params['execute_purcharse_time']['end']);
        }

        if (isset($params['activity_start_time']))
        {
            $query->where("{$o_t}.activity_start_time >=", $params['activity_start_time']['start'])
            ->where("{$o_t}.activity_start_time <=", $params['activity_start_time']['end']);
        }
        if (isset($params['activity_end_time']))
        {
            $query->where("{$o_t}.activity_end_time >=", $params['activity_end_time']['start'])
            ->where("{$o_t}.activity_end_time <=", $params['activity_end_time']['end']);
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
        $query_counter->select("{$o_t}.id");
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
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::INLAND_ACTIVITY_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
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
        foreach ($search_result['data_list']['value'] as $k=> &$v){
            switch ($v['activity_state']){
                case ACTIVITY_STATE_NOT_START: //未开始
                    if ($v['activity_start_time'] <= date("Y-m-d H:i:s", time()) && $v['activity_end_time'] > date("Y-m-d H:i:s", time())) {//进行中
                        $v['activity_state_text'] = ACTIVITY_STATE[ACTIVITY_STATE_ING]['name'];
                    } elseif ($v['activity_start_time'] > date("Y-m-d H:i:s", time())) {
                        $v['activity_state_text'] = ACTIVITY_STATE[ACTIVITY_STATE_NOT_START]['name'];
                    } elseif ($v['activity_end_time'] < date("Y-m-d H:i:s", time())) {//结束
                        $v['activity_state_text'] = ACTIVITY_STATE[ACTIVITY_STATE_END]['name'];
                    }
                    break;
                case ACTIVITY_STATE_DISCARD://废弃
                    $v['activity_state_text'] = ACTIVITY_STATE[ACTIVITY_STATE_DISCARD]['name'];
                    break;
            }
            $v['platform_code_text'] = INLAND_PLATFORM_CODE[strtoupper($v['platform_code'])]['name']?INLAND_PLATFORM_CODE[strtoupper($v['platform_code'])]['name']:"-";
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
        $rewrite_cols = [
                'updated_start', 'updated_end', 'approved_start', 'approved_end', 'required_start', 'required_end',
                'execute_start', 'execute_end', 'activity_start_start', 'activity_start_end', 'start_date', 'end_date',
                'activity_end_start', 'activity_end_end',
        ];
        if (!in_array($col, $rewrite_cols))
        {
            return false;
        }

        if ($col == 'start_date')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的创建开始时间'), 3001);
            }
            return true;
        }
        if ($col == 'end_date')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的创建结束时间'), 3001);
            }
            return true;
        }

        if ($col == 'updated_start')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的更新开始时间'), 3001);
            }
            return true;
        }
        if ($col == 'updated_end')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的更新结束时间'), 3001);
            }
            return true;
        }

        if ($col == 'approved_start')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的审核开始时间'), 3001);
            }
            return true;
        }
        if ($col == 'approved_end')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的审核结束时间'), 3001);
            }
            return true;
        }

        if ($col == 'execute_start')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的审核开始时间'), 3001);
            }
            return true;
        }
        if ($col == 'execute_end')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的审核结束时间'), 3001);
            }
            return true;
        }

        if ($col == 'activity_start_start')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的活动开始开始时间'), 3001);
            }
            return true;
        }
        if ($col == 'activity_start_end')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的活动开始结束时间'), 3001);
            }
            return true;
        }

        if ($col == 'activity_end_start')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['start'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的活动结束开始时间'), 3001);
            }
            return true;
        }
        if ($col == 'activity_end_end')
        {
            if ($unix_time = strtotime($val))
            {
                $format_params[$defind_valid_key[$col]['name']]['end'] = $val;
            }
            else
            {
                throw new \InvalidArgumentException(sprintf('无效的活动结束结束时间'), 3001);
            }
            return true;
        }

        if ($col == 'required_start')
        {
            $format_params[$defind_valid_key[$col]['name']]['start'] = intval($val);
            return true;
        }

        if ($col == 'required_end')
        {
            $format_params[$defind_valid_key[$col]['name']]['end'] = intval($val);
            return true;
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
        $check_cols = ['created_at', 'updated_at', 'approved_at', 'execute_purcharse_time', 'activity_start_time', 'activity_end_time'];
        foreach ($check_cols as $key)
        {
            if (isset($format_params[$key]))
            {
                if (count($format_params[$key]) == 1) {

                    if (isset($format_params[$key]['start']))
                    {
                        $format_params[$key]['end'] = date('Y-m-d');
                    }
                    else
                    {
                        $format_params[$key]['start'] = $format_params[$key]['end'];
                    }
                }
                if ($format_params[$key]['start'] > $format_params[$key]['end'])
                {
                    //交换时间
                    $tmp = $format_params[$key]['start'];
                    $format_params[$key]['start'] =  $format_params[$key]['end'];
                    $format_params[$key]['end'] = $tmp;
                }
                //为开始日期和结束日期添加 00：00：01 和 23:59:59
                $start = $format_params[$key]['start'];
                $end = $format_params[$key]['end'];
                $format_params[$key]['start'] = $start.' 00:00:00';
                $format_params[$key]['end'] = $end.' 23:59:59';
            }
        }

        $key = 'amount';
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
