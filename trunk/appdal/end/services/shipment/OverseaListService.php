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
class OverseaListService extends AbstractList implements Rpcable
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
        $this->_ci->load->model('Oversea_list_model', 'm_oversea_list', false, 'shipment');
        $this->_ci->lang->load('common');
        $this->_ci->load->helper('shipment_helper');
        return $this;
    }

    /**
     * 直接从redis里面拿sql获取订单号
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8',$template='',$filename='')
    {
        $db = $this->_ci->m_oversea_list->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->select('a.*,b.logistics_sn,b.shipment_qty,b.logistics_status,b.receipt_qty')
                ->from($this->_ci->m_oversea_list->getTable().' a')
                ->join('yibai_shipment_logistics_oversea b','a.pr_sn=b.pr_sn','left')
                ->where_in('gid', $gids_arr)
                ->order_by('a.created_at desc, gid desc')
                ->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            if ($template == 4){
                $quick_sql = $this->_ci->m_oversea_list->get_track_list($filename);
            }else{
                $this->_ci->load->library('rediss');
                $this->_ci->load->service('basic/SearchExportCacheService');
                $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::SHIPMENT_OVERSEA_LIST_SEARCH_EXPORT)->get();
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
//        echo $quick_sql;exit;

        $this->_ci->load->classes('shipment/classes/ShipmentOverseaHugeExport');
        if(!empty($template)){
            //设置模板
            $this->_ci->ShipmentOverseaHugeExport->set_template($template);
        }
        //设置文件名,文件名发运计划编号
        $this->_ci->ShipmentOverseaHugeExport->set_filename($filename);
        $this->_ci->ShipmentOverseaHugeExport
        ->set_format_type($format_type)
        ->set_data_type($data_type)
        ->set_out_charset($charset)
        ->set_title_map($profile)
        ->set_translator()
        ->set_data_sql($quick_sql)
        ->set_export_nums($total);

        return $this->_ci->ShipmentOverseaHugeExport->run();
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
            'gid'      => [
                'desc'     => 'gid',
                'name'     => 'gid',
                'type'     => 'strval',
            ],
            'stock_sn' => [
                'desc' => '备货单号,精确，支持多个，","分割',
                'name' => 'stock_sn',
                'type' => 'strval',
                'hook' => 'tran_sku_sn',
            ],
            'pr_sn'    => [
                'desc'     => '需求单号,精确，支持多个，","分割',
                'name'     => 'pr_sn',
                'type'     => 'strval',
                'hook'     => 'tran_sku_sn',
            ],
            'sku'      => [
                'desc'     => 'sku,精确，支持多个，","分割',
                'name'     => 'sku',
                'type'     => 'strval',
                'hook'     => 'tran_sku_sn',
                'callback' => 'is_valid_skusn'
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
                    'hook'     => 'tran_sku_sn',
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
            'owner_station'   => [
                    'desc' => '数据权限设置',
                    'name' => 'owner_station',
            ]
        ];
        $this->_cfg = [
            'title_track' => array_column($this->_ci->lang->myline('oversea_shipment_track_list'),'label'),
            'title_detail' => array_column($this->_ci->lang->myline('oversea_shipment_detail_list'),'label'),
            'select_cols' => [
                    ($this->_ci->m_oversea_list->getTable()) => [
                        '*'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => [
                'os_station_code', 'shipment_status', 'oversea_purchase_warehouse'
            ],
            'profile_track' => 'oversea_shipment_track_list',
            'profile_detail' => 'oversea_shipment_detail_list',
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

        $db = $this->_ci->m_oversea_list->getDatabase();

        $o_t = $this->_ci->m_oversea_list->getTable();

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

        if (isset($params['stock_sn']))
        {
            if (count($sns = explode(',', $params['stock_sn'])) > 1)
            {
                $query->where_in("{$o_t}.stock_sn", $sns);
            }
            else
            {
                $query->where("{$o_t}.stock_sn", $sns[0]);
            }
        }

        if (isset($params['pr_sn'])) {
            if (count($sns = explode(',', $params['pr_sn'])) > 1) {
                $query->where_in("{$o_t}.pr_sn", $sns);
            } else {
                $query->where("{$o_t}.pr_sn", $sns[0]);
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

        if (isset($params['shipment_type']))
        {
            $query->where("{$o_t}.shipment_type", $params['shipment_type']);
        }



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
             $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::SHIPMENT_OVERSEA_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
         }

        //执行搜索
        $query->select($select_cols)
              ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
              ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);

        //pr($query->get_compiled_select());exit;
        //pr($query->get());exit;
        $result = $query->get()->result_array();
        $this->_ci->load->model('Oversea_shipment_list_model', 'oversea_shipment_list', false, 'shipment');
        $sp_info = $this->_ci->oversea_shipment_list->sp_info($params['shipment_sn']);
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
