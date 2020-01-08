<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 新品列表服务
 *
 * @author zc
 * @since 2019-10-22
 * @link
 */
class NewCfgListService extends AbstractList
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
        $this->_ci->load->model('Fba_new_list_model', 'm_fba_new', false, 'fba');
        $this->_ci->load->helper('fba_helper');
        return $this;
    }

    /**
     * 快速导出
     * @param string $gids 页面选择的gids，为空表示从搜索条件获取
     * @param string $profile 用户选择导出的列
     * @param string $format_type 导出csv的格式， 可读还是用于修改的原生字段
     * @throws \RuntimeException
     * @throws \OverflowException
     * @return unknown
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8' )
    {
        $db = $this->_ci->m_fba_new->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_fba_new->getTable())->where_in('id', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            //!!!缓存
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_NEW_EXPORT)->get();
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
        $this->_ci->load->classes('fba/classes/FbaNewHugeExport');
        $this->_ci->FbaNewHugeExport
            ->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
        return $this->_ci->FbaNewHugeExport->run();

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
                'name'     => 'id',
                'type'     => 'intval',
            ],
            'account_name'        => [
                'desc'     => '账号名称',
                'name'     => 'account_name',
                'type'     => 'strval',
            ],
            'salesman'        => [
                'desc'     => '销售账号',
                'name'     => 'salesman',
                'type'     => 'strval',
            ],
            'sale_group'        => [
                'desc'     => '销售小组',
                'name'     => 'sale_group',
                'type'     => 'strval',
            ],
            /**
             * 审核状态
             */
            'approve_state'        => [
                'desc'     => '审核状态',
                'dropname' => 'fba_new_approve_state',
                'name'     => 'approve_state',
                'type'     => 'intval',
                'callback' => 'is_valid_new_approval_state'
            ],
            'seller_sku'        => [
                'desc'     => 'seller_sku,精确，支持多个，","分割',
                'name'     => 'seller_sku',
                'type'     => 'strval',
                'hook'     => 'tran_sku_sn',
                'callback' => 'tran_split_search'
            ],
            'asin'        => [
                'desc'     => 'asin,精确，支持多个，","分割',
                'name'     => 'asin',
                'type'     => 'strval',
                'hook'     => 'tran_asin',
            ],
            /**
             * fnsku
             */
            'fnsku'        => [
                'desc'     => 'fnsku,精确，支持多个，","分割',
                'name'     => 'fnsku',
                'type'     => 'strval',
                'hook'     => 'tran_fnsku',
            ],
            'erpsku'        => [
                'desc'     => 'erpsku,精确，支持多个，","分割',
                'name'     => 'erpsku',
                'type'     => 'strval',
            ],
            'site'        => [
                'desc'     => 'site',
                'name'     => 'site',
                'type'     => 'strval',
            ],
            'demand_num_start'        => [
                'desc'     => '需求量起始值',
                'name'     => 'amount',
                'type'     => 'intval',
            ],
            'demand_num_end'        => [
                'desc'     => '需求量结束值',
                'name'     => 'amount',
                'type'     => 'intval',
            ],
            /**
             * 生成时间
             */
            'created_at_start'  => [
                'desc'     => '创建开始时间',
                'name'     => 'created_at_start',
                'type'     => 'strval',
                'callback' => 'is_valid_date'
            ],
            /**
             * 生成时间
             */
            'created_at_end'    => [
                'desc'     => '创建结束时间',
                'name'     => 'created_at_end',
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
        ];
        $this->_cfg = [
            'title' => [
            ],
            'select_cols' => [
                ($this->_ci->m_fba_new->getTable()) => [
                    '*'
                ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                'new_activity_approve_state','fba_sales_group','fba_salesman','station_code'
            ],
            'user_profile' => 'fba_new'
        ];
        return $this->_cfg;
    }

    /**
     *!!!
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {

        $db = $this->_ci->m_fba_new->getDatabase();

        $o_t = $this->_ci->m_fba_new->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($o_t);
        $query->where("{$o_t}.is_delete", 0);

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
         * account_name  账号名称
         */
        if (isset($params['account_name'])){
            $query->where("{$o_t}.account_name",$params['account_name']);
        }

        /**
         * sales_staff_code  销售账号
         */
        if (isset($params['salesman'])){
            $query->where("{$o_t}.salesman",$params['salesman']);
        }

        /**
         * sale_group 销售小组
         */
        if (isset($params['sale_group'])){
            $query->where("{$o_t}.sale_group",$params['sale_group']);
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

        if (isset($params['erpsku']))
        {
            if (count($sns = explode(',', $params['erpsku'])) > 1)
            {
                $query->where_in("{$o_t}.erp_sku", $sns);
            }
            else
            {
                $query->where("{$o_t}.erp_sku", $sns[0]);
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

        if (isset($params['site']))
        {
           $query->where("{$o_t}.site", strtoupper($params['site']));
        }

        /**
         * 创建时间
         */
        if (isset($params['created_at_start']))
        {
            $created_at_end = $params['created_at_end'] ?? date('Y-m-d H:i:s');
            $query->where("{$o_t}.created_at >=", $params['created_at_start'])
                ->where("{$o_t}.created_at <=", $created_at_end);
        }

        /**
         * 更新时间
         */
        if (isset($params['updated_at']))
        {
            $query->where("{$o_t}.updated_at >=", $params['updated_at']['start'])
                ->where("{$o_t}.updated_at <=", $params['updated_at']['end']);
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

        $query_export = clone $query;

        //执行搜索
        $query->select($select_cols)
            ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
        /*->get_cache_limit($limit_key, $count, 'gid');*/

//        pr($query->get_compiled_select('',false));exit;
        //pr($query->get());exit;
        $result = $query->get()->result_array();


        /**
         * 导出暂存
         */
        if (isset($params['export_save']))
        {
            $query_export->select($select_cols)->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')));
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_NEW_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
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
     * 转换!!!
     *
     * {@inheritDoc}
     * @see AbstractList::translate()
     */
    public function translate($search_result)
    {
        $sales_group_id = $uids = [];
        foreach ($search_result['data_list']['value'] as $item){
            $sales_group_id[] = $item['sale_group']??0;
            $uids = array_merge($uids, [$item['salesman'], $row['updated_uid'] ?? '', $row['approved_uid'] ?? '']);
        }
        $sales_group_id = array_unique($sales_group_id);
        $sales_group_id = array_filter($sales_group_id, function($val) {return $val != 0;});
        $group_map = $uid_map = [];

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
//            var_dump($uid_map);exit;
        }

        if (!empty($sales_group_id))
        {
            $this->_ci->load->model('Fba_amazon_group_model', 'm_amazon_group', false, 'fba');
            $group_map = $this->_ci->m_amazon_group->get_group_name($sales_group_id);
        }
        foreach ($search_result['data_list']['value'] as $k=> &$v){
            $v['sale_group'] = @isset($group_map[$v['sale_group']])?$group_map[$v['sale_group']]:"";
            $v['salesman'] = @isset($uid_map[$v['salesman']])?$uid_map[$v['salesman']]:"";
/*            switch ($v['activity_state']){
                case 1: //未开始
                    if ($v['activity_start_time'] <= date("Y-m-d H:i:s", time()) && $v['activity_end_time'] > date("Y-m-d H:i:s", time())) {//进行中
                        $v['activity_state_text'] = @ACTIVITY_STATE[ACTIVITY_STATE_ING]['name'];
                    } elseif ($v['activity_start_time'] > date("Y-m-d H:i:s", time())) {
                        $v['activity_state_text'] = @ACTIVITY_STATE[ACTIVITY_STATE_NOT_START]['name'];
                    } elseif ($v['activity_end_time'] < date("Y-m-d H:i:s", time())) {//结束
                        $v['activity_state_text'] = @ACTIVITY_STATE[ACTIVITY_STATE_END]['name'];
                    }
                    break;
                case 2://废弃
                    $v['activity_state_text'] = @ACTIVITY_STATE[ACTIVITY_STATE_DISCARD]['name'];
                    break;
            }*/
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
        if ($col == 'activity_end_start')
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

    /**!!!!!
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
