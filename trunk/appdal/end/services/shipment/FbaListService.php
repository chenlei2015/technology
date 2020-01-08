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
class FbaListService extends AbstractList implements Rpcable
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
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_list_model', 'm_fba_list', false, 'shipment');
        $this->_ci->lang->load('common');
        $this->_ci->load->helper('shipment_helper');
        return $this;
    }

    /**
     * 直接从redis里面拿sql获取订单号
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8' ,$template='',$filename='')
    {
        $db = $this->_ci->m_fba_list->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->select('a.*,b.logistics_sn,b.shipment_qty,b.logistics_status,b.receipt_qty')
                ->from($this->_ci->m_fba_list->getTable().' a')
                ->join('yibai_shipment_logistics_fba b','a.pr_sn=b.pr_sn','left')
                ->where_in('gid', $gids_arr)
                ->order_by('a.created_at desc, gid desc')
                ->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            if($template == 4){
                //
                $quick_sql = $this->_ci->m_fba_list->get_track_list($filename);

            }else{
                $this->_ci->load->library('rediss');
                $this->_ci->load->service('basic/SearchExportCacheService');
                $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::SHIPMENT_FBA_LIST_SEARCH_EXPORT)->get();
            }
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

        $this->_ci->load->classes('shipment/classes/ShipmentFbaHugeExport');
        if(!empty($template)){
            //设置模板
            $this->_ci->ShipmentFbaHugeExport->set_template($template);
        }
        //设置文件名,文件名发运计划编号
        $this->_ci->ShipmentFbaHugeExport->set_filename($filename);
        $this->_ci->ShipmentFbaHugeExport
        ->set_format_type($format_type)
        ->set_data_type($data_type)
        ->set_out_charset($charset)
        ->set_title_map($profile)
        ->set_translator()
        ->set_data_sql($quick_sql)
        ->set_export_nums($total);

        return $this->_ci->ShipmentFbaHugeExport->run();
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
                'pr_sn'        => [
                        'desc'     => '需求单号,精确，支持多个，","分割',
                        'name'     => 'pr_sn',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                ],
                'sku'        => [
                        'desc'     => 'sku,精确，支持多个，","分割',
                        'name'     => 'sku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_skusn'
                ],
                'seller_sku'  => [
                        'desc'     => 'seller_sku,精确，支持多个，","分割',
                        'name'     => 'seller_sku',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        'callback' => 'is_valid_skusn'
                ],
                'fnsku'  => [
                    'desc'     => 'fnsku,精确，支持多个，","分割',
                    'name'     => 'fnsku',
                    'type'     => 'strval',
                    'hook'     => 'tran_sku_sn',
                    'callback' => 'is_valid_skusn'
                ],

                'account_name' =>[
                        'desc'     => 'account_name,精确，支持多个，","分割',
                        'name'     => 'account_name',
                        'type'     => 'strval',
                ],
                'station_code'        => [
                    'desc'     => '站点列表',
                    'dropname' => 'station_code',
                    'name'     => 'station_code',
                    'type'     => 'strval',
                ],
                'warehouse_id'  => [
                    'desc'     => '发运仓库',
                    'dropname' => 'purchase_warehouse',
                    'name'     => 'warehouse_id',
                    'type'     => 'intval',
                ],
                'shipment_status'        => [
                    'desc'     => '发运状态',
                    //'dropname' => '',
                    'name'     => 'shipment_status',
                    'type'     => 'strval',
                ],
                'shipment_sn'        => [
                    'desc'     => '查看详情页必传,发运单号',
                    'name'     => 'shipment_sn',
                    'type'     => 'strval',

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
                'prev_salesman'        => [
                        'desc'     => '权限设置，账号作为销售人员',
                        'name'     => 'prev_salesman',
                        'type'     => 'strval',
                ],
                'prev_account_name'        => [
                        'desc'     => '账号管理的亚马逊账号',
                        'name'     => 'prev_account_name',
                        'type'     => 'strval',
                ],
                'set_data_scope' => [
                        'desc'     => '设置账号开启权限标记',
                        'name'     => 'set_data_scope',
                        'type'     => 'intval',
                ],
        ];
        $this->_cfg = [
//            'title' => [
//                    'shipment_sn',           'pur_sn',                'pr_date',               'shipment_type',         'business_line',
//                    'logistics_id',          'station_code',          'is_refund_tax',         'warehouse_id',          'country_of_destination',
//                    'pur_cost',              'pr_qty',                'available_inventory',   'shipment_qty',          'warehouse_of_destination',
//                    'logistics_sn',          'shipment_status',
//            ],
            'title_track' => array_column($this->_ci->lang->myline('fba_shipment_track_list'),'label'),
            'title_detail' => array_column($this->_ci->lang->myline('fba_shipment_detail_list'),'label'),
            'select_cols' => [
                    ($this->_ci->m_fba_list->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                'station_code', 'fba_purchase_warehouse', 'shipment_status'
            ],
            'profile_track' => 'fba_shipment_track_list',
            'profile_detail' => 'fba_shipment_detail_list',
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
        if ($this->is_rpc('shipment'))
        {
            return $this->java_search($params);
        }

        $db = $this->_ci->m_fba_list->getDatabase();

        $o_t = $this->_ci->m_fba_list->getTable();

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

        if (isset($params['shipment_type']))
        {
            $query->where("{$o_t}.shipment_type", $params['shipment_type']);
        }

        if (isset($params['warehouse_id']))
        {
            $query->where("{$o_t}.warehouse_id", $params['warehouse_id']);
        }
        if (isset($params['shipment_status']))
        {
            if (count($sns = explode(',', $params['shipment_status'])) > 1)
            {
                $query->where_in("{$o_t}.shipment_status", $sns);
            }
            else
            {
                $query->where("{$o_t}.shipment_status", $sns[0]);
            }
        }

        $query->where("{$o_t}.shipment_sn", $params['shipment_sn']);

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
             $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::SHIPMENT_FBA_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
         }

        //执行搜索
        $query->select($select_cols)
              ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
              ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);

        //pr($query->get_compiled_select());exit;
        //pr($query->get());exit;
        $result = $query->get()->result_array();

        $this->_ci->load->model('Fba_shipment_list_model', 'fba_shipment_list', false, 'inland');
        $sp_info = $this->_ci->fba_shipment_list->sp_info($params['shipment_sn']);
        $sp_info = $this->tran_sp_info($sp_info);

        //设定统一返回格式
        return [
                'page_data' => [
                        'total' => $count,
                        'offset' => $params['page'],
                        'limit' => $params['per_page'],
                        'pages' => $page
                ],
                'data_list'  => [
                        'value' => &$result,
                        'sp_info' => $sp_info??''

                ],
        ];
    }

    public function tran_sp_info($params){

        $list = RPC_CALL('YB_J1_005', [$params['created_uid']]);
        //根据uid获取用户姓名
        if ($list)
        {
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
        $uids = [];
        foreach ($search_result['data_list']['value'] as $row)
        {
            $uids = array_merge($uids, [$row['push_uid'] ?? '', $row['created_uid'] ?? '']);
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
            $val['push_uid_cn'] = $val['push_uid'] == '' ? '' : $uid_map[$val['push_uid']] ?? $val['push_uid'];
            $val['created_uid_cn'] = $val['created_uid'] == '' ? '' : $uid_map[$val['created_uid']] ?? $val['created_uid'];
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

        $rewrite_cols = ['updated_start', 'updated_end'];
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

        return true;
    }

}
