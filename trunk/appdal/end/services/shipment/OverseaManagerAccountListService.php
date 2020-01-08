<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 海外仓 站点与平台配置列表
 *
 * @version 1.2.0
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-07-10
 * @link
 */
class OverseaManagerAccountListService extends AbstractList
{

    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'station_code desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Shipment_oversea_manager_account_model', 'm_shipment_oversea_manager', false, 'shipment');
        $this->_ci->load->helper('shipment_helper');
        return $this;
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
                'station_code'        => [
                        'desc'     => '站点账号',
                        'name'     => 'station_code',
                        'type'     => 'strval',
                        //'hook'     => 'tran_split_search',
                ],
                'staff_code'        => [
                        'desc'     => '管理员工号,精确，支持多个，","分割',
                        'name'     => 'staff_code',
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
        ];
        $this->_cfg = [
            'title' => [
                    'no',               'station_name',     'manager_name',
                    'manager_state',    'update_info',      'operation',
            ],
            'select_cols' => [
                    ($this->_ci->m_shipment_oversea_manager->getTable()) => [
                            '*'
                    ],
            ],
            'search_rules' => &$search,
                'droplist' => ['os_station_code', 'oversea_platform_code']
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
        $this->_ci->load->model('Shipment_oversea_manager_staff_model', 'm_shipment_manager_staff', false, 'shipment');
        $db = $this->_ci->m_shipment_oversea_manager->getDatabase();
        $o_t = $this->_ci->m_shipment_oversea_manager->getTable();
        $p_t = $this->_ci->m_shipment_manager_staff->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        //默认页面，非搜索
        if (count($params) == 3)
        {
            return $this->_ci->m_shipment_oversea_manager->get_default_station_platform_list($params['page'], $params['per_page']);
        }



        $query = $db->from($o_t)->join($p_t, "{$p_t}.gid = {$o_t}.gid");

        if (isset($params['station_code']))
        {
            $query->where("{$o_t}.station_code", $params['station_code']);
        }

        if (isset($params['staff_code']))
        {
            if (count($sns = explode(',', $params['staff_code'])) > 1)
            {
                $query->where_in("{$p_t}.staff_code", $sns);
            }
            else
            {
                $query->where("{$p_t}.staff_code", $sns[0]);
            }
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
        $query_counter->select("*");
        $count = $query_counter->count_all_results();
        $page = ceil($count / $params['per_page']);

        /**
         * 导出暂存
         */
        /*if (isset($params['export_save']))
        {
            $query_export = clone $query;
            $query_export->select($select_cols)->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')));
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_PLATFORM_LIST_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }*/

        $staff_info = "GROUP_CONCAT(CONCAT_WS(':', {$p_t}.staff_code, {$p_t}.user_zh_name, {$p_t}.state)) as info";

        //执行搜索
        $query->select($select_cols)
        ->select($staff_info, NULL, true)->group_by("{$o_t}.gid")
        ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
        ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
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

        foreach ($search_result['data_list']['value'] as $row => &$val)
        {
            $group_info = [];

            $val['station_name'] = OVERSEA_STATION_CODE[$val['station_code']]['name'] ?? '-';

            if (isset($val['info']))
            {
                foreach(explode(',', $val['info']) as $tmp_row)
                {
                    $staff_info = explode(':', $tmp_row);
                    $group_info['staff_code'][] = $staff_info[0];
                    $group_info['user_zh_name'][] = $staff_info[1];
                    $group_info['status_text'][] = sprintf('%s:%s', $staff_info[1], FBA_ACCOUNT_STATUS[$staff_info[2]]['name'] ?? '未知');
                }

                $val['staff_code'] = implode(',', $group_info['staff_code']);
                $val['user_zh_name'] = implode(',', $group_info['user_zh_name']);
                $val['status_text'] = implode(',', $group_info['status_text']);

                unset($val['info']);
            }
        }
        return $search_result;
    }
}
