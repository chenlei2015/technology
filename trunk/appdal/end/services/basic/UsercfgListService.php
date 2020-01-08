<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * 用户配置列表服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class UsercfgListService extends AbstractList
{
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_at desc';
    
    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('User_config_list_model', 'm_user_config_list', false, 'basic');
        //$this->_ci->load->helper('fba_helper');
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
                'userName'        => [
                        'desc'     => '用户姓名',
                        'name'     => 'userName',
                        'type'     => 'strval',
                ],
                'userNumber'        => [
                        'desc'     => '员工工号,精确，支持多个，","分割',
                        'name'     => 'userNumber',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                ],
                'departId'        => [
                        'desc'     => '直属部门id',
                        'name'     => 'departId',
                        'type'     => 'intval',
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
                    'no',               'userName',         'userNumber',       'userEnglishName',  'jobName',
                    'departName',       'modify_info',      'remark',
                    'op_name',
            ],
            'select_cols' => [
            ],
            'search_rules' => &$search,
            'droplist' => [
                    'oa_depart_list'
            ]
        ];
        return $this->_cfg;
    }
    
    /**
     * 是否有本地搜索
     *
     * @param array $params
     * @return boolean
     */
    private function has_local_search($params)
    {
        $market_search_params = array_flip(['station_code', 'platform_code']);
        return count(array_intersect_key($market_search_params, $params)) > 0;
    }
    
    /**
     * 是否有api搜索
     *
     * @param array $params
     * @return boolean
     */
    private function has_remote_search($params)
    {
        $market_search_params = array_flip(['userName', 'userNumber', 'departId']);
        return count(array_intersect_key($market_search_params, $params)) > 0;
    }
    
    /**
     * 转发请求
     *
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {
        $return = [
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
        $params['pageSize'] = $params['per_page'];
        $params['pageNumber'] = $params['page'];
        
        //搜索本地符合条件的数据
        /*if ($this->has_local_search($params))
        {
            $db = $this->_ci->m_user_config_list->getDatabase();
            $o_t = $this->_ci->m_user_config_list->getTable();
            
            $append_table_prefix = function($arr, $tbl) {
                array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
                return $arr;
            };
            
            $query = $db->from($o_t);
            
            if (isset($params['station_code']))
            {
                $query->where("{$o_t}.station_code", $params['station_code']);
            }
            
            if (isset($params['platform_code']))
            {
                $query->where("{$o_t}.platform_code", $params['platform_code']);
            }
            
            $query->select('*')
            ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);
            $result = $query->get()->result_array();
            
            if (empty($result))
            {
                return $return;
            }
            
            //获取符合条件的工号
            $modify_info = key_by($result, 'staff_code');
            $staff_codes = array_keys($modify_info);
            $result = NULL;
            unset($result);
            
            if (isset($params['userNumber']))
            {
                $intersect_staff_codes = array_intersect($staff_codes, explode(',', $params['userNumber']));
                if (empty($intersect_staff_codes))
                {
                    return $return;
                }
                $params['userNumber'] = implode(',', $intersect_staff_codes);
            }
            else
            {
                $params['userNumber'] = implode(',', $staff_codes);
            }
        }
        */
        unset($params['sort'], $params['per_page'], $params['page']);
        
        //根据uid获取用户姓名
        $list = RPC_CALL('YB_J1_004', $params);
        if (!$list || empty($list['data']['records']))
        {
            log_message('ERROR', sprintf('获取管理员信息接口返回无效数据, 请求参数：%s', json_encode($params)));
            return $return;
        }
        
        if (!$this->has_local_search($params))
        {
            $uids = array_column($list['data']['records'], 'userNumber');
            $modify_info = key_by($this->_ci->m_user_config_list->get($uids), 'staff_code');
        }
        
        $empty_fill_row = [
                'gid' => '',
                'remark' => '',
                'op_zh_name' => '',
                'updated_at' => '',
                'unassign' => true,
        ];
        
        foreach ($list['data']['records'] as $key => &$row)
        {
            if (isset($modify_info[$row['userNumber']]))
            {
                $row = array_merge($row, $modify_info[$row['userNumber']]);
            }
            else
            {
                $row = array_merge($row, $empty_fill_row);
            }
        }
        //设定统一返回格式
        return [
                'page_data' => [
                        'total' => $list['data']['total'],
                        'offset' => $params['pageNumber'],
                        'limit' => $params['pageSize'],
                        'pages' => $list['data']['pages'],
                ],
                'data_list'  => [
                        'value' => $list['data']['records']
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
