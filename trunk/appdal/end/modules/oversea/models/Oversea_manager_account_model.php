<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * 海外仓 账号配置表
 *
 * @version 1.2.0
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-07-10
 * @link
 */
class Oversea_manager_account_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_oversea_manager_account';
        $this->primaryKey = 'gid';
        $this->tableId = 27;
        parent::__construct();
    }
    
    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }
    
    /**
     * 返回一个默认的站点和平台对应关系表
     *
     * @param number $page
     * @param number $page_size
     * @return number[]|NULL[]
     */
    public function get_default_station_platform_list($page = 1, $page_size = 20, &$total = 0)
    {
        //海外仓平台列表
        $platform_code = $map = $all = $existed_account_rows =  $serach_station = [];
        
        foreach (INLAND_PLATFORM_CODE as $state => $cfg) {
            if ($cfg['oversea'] ?? false) {
                $platform_code[$state] = $cfg['name'];
            }
        }
        
        $all = $this->all();
        
        foreach ($all as $row)
        {
            $existed_account_rows[$row['station_code'] . '_' . $row['platform_code']] = $row;
        }
        
        $start_index = ($page - 1) * $page_size;
        $end_index = $start_index + 20;
        
        //构造一个数组
        $row_index = 0;
        
        foreach (OVERSEA_STATION_CODE as $state => $cfg)
        {
            foreach ($platform_code as $p_state => $p_name)
            {
                if ($row_index >= $start_index  && $row_index <= $end_index)
                {
                    $key = $state . '_' . $p_state;
                    $map[] = [
                            'gid'           => $existed_account_rows[$key]['gid'] ?? '',
                            'station_code'  => $state,
                            'station_name'  => $cfg['name'],
                            'platform_code' => $p_state,
                            'platform_name' => $p_name,
                            'staff_code'    => '',
                            'user_zh_name'  => '-',
                            'status_text'   => '-',
                            'op_zh_name'    => $existed_account_rows[$key]['op_zh_name'] ?? '-',
                            'updated_at'    => $existed_account_rows[$key]['updated_at'] ?? '-',
                    ];
                    if (isset($existed_account_rows[$key]))
                    {
                        $serach_station[$state] = 1;
                    }
                }
                $row_index ++;
            }
        }
        
        if (!empty($serach_station))
        {
            $gid_map_staff = [];
            $station_all_staff = $this->get_staffs_by_stations(array_keys($serach_station));
            foreach ($station_all_staff as $row)
            {
                $gid_map_staff[$row['gid']]['user_zh_name'][] = $row['user_zh_name'];
                $gid_map_staff[$row['gid']]['manager_uid'][] = $row['staff_code'];
                $gid_map_staff[$row['gid']]['status_text'][] = sprintf('%s:%s', $row['user_zh_name'], FBA_ACCOUNT_STATUS[$row['state']]['name'] ?? '未知');
            }
        }
        
        foreach ($map as $key => &$val)
        {
            if ($val['gid'] != '' &&  isset($gid_map_staff[$val['gid']]))
            {
                $val['staff_code'] = implode(',', $gid_map_staff[$val['gid']]['manager_uid']) ;
                $val['user_zh_name'] = implode(',', $gid_map_staff[$val['gid']]['user_zh_name']);
                $val['status_text'] = implode(',', $gid_map_staff[$val['gid']]['status_text']);
            }
        }
        
        $platform_code = $all = $existed_account_rows =  $serach_station = [];
        
        return [
                'page_data' => [
                        'total' => count($map),
                        'offset' => $page,
                        'limit' => $page_size,
                        'pages' => floor(count($map) / $page_size)
                ],
                'data_list'  => [
                        'value' => $map
                ]
        ];
    }
    
    protected function get_foreign_table_model()
    {
        $ci = CI::$APP;
        $ci->load->model('Oversea_manager_staff_model', 'm_manager_staff', false, 'oversea');
        return $ci->m_manager_staff;
    }
    
    /**
     * 获取平台和站点的记录
     *
     * @param unknown $station_code
     * @param unknown $platform_code
     * @param string $staff_code
     * @return unknown
     */
    public function &get_station_platform_configs($station_code, $platform_code, $staff_code = [])
    {
        $o_t = $this->table_name;
        $p_t = $this->get_foreign_table_model()->getTable();
        
        $this->_db->reset_query();
        
        $query = $this->_db->from($o_t . ' as a')
            ->join($p_t.' as b', "a.gid = b.gid")
            ->where('a.station_code', $station_code)
            ->where('a.platform_code', $platform_code)
            ->group_by('a.gid');
        
        if (!empty($staff_code))
        {
            count($staff_code) == 1 ? $query->where('b.staff_code', $staff_code[0]) : $query->where_in('b.staff_code', $staff_code);
        }
        $query->select('a.*')->select("GROUP_CONCAT(CONCAT_WS(':', b.staff_code, b.user_zh_name, b.state)) as info", NULL, true);
        
        $result = $query->get()->result_array();
        if (empty($result)) return $result;
        foreach ($result as $key => &$val)
        {
            $group_info = [];
            
            foreach(explode(',', $val['info']) as $tmp_row)
            {
                $staff_info                   = explode(':', $tmp_row);
                $group_info['staff_code'][]   = $staff_info[0];
                $group_info['user_zh_name'][] = $staff_info[1];
                $group_info['status_text'][]  = sprintf('%s:%s', $staff_info[1], FBA_ACCOUNT_STATUS[$staff_info[2]]['name'] ?? '未知');
            }
            
            $val['staff_code']   = $group_info['staff_code'];
            $val['user_zh_name'] = $group_info['user_zh_name'];
            $val['status_text']  = $group_info['status_text'];
            
            unset($val['info']);
        }
        
        return $result[0];
    }
    
    /**
     *
     * @param unknown $stations
     */
    public function get_staffs_by_stations($stations, $state = -1)
    {
        $t_staff = $this->get_foreign_table_model()->getTable();
        
        $query = $this->_db->select('b.*')
        ->from($this->table_name . ' as a')
        ->join($t_staff . ' as b', 'a.gid = b.gid');
        
        if ($state != -1)
        {
            $query->where('b.state', $state);
        }
        
        count($stations) == 1 ?
        $query->where('a.station_code', $stations[0]) :
        $query->where_in('a.station_code', $stations);
        
        return $query->get()->result_array();
    }
    
    public function get_staffs($station_map_platform)
    {
        
        $where_station_platform = [];
        
        //构造条件
        $stations = array_keys($station_map_platform);
        
        $where_station_platform[] = count($stations) == 1 ?
        sprintf('%s.station_code = "%s"', $o_t, $stations[0]) :
        sprintf('%s.station_code in (%s)', $o_t, array_where_in($stations));
        
        $where_station_platform[] = ' and (case ';
        
        foreach ($station_map_platform as $c_station => $info)
        {
            $where_station_platform[] = count($info) == 1 ?
            sprintf('when %s.station_code = "%s" then %s.platform_code = "%s"', $o_t, $c_station, $o_t, array_keys($info)[0]) :
            sprintf('when %s.station_code = "%s" then %s.platform_code in (%s)', $o_t, $c_station, $o_t, array_where_in(array_keys($info)));
        }
        $where_station_platform[] = ' end)';
        
        $query->where(implode('', $where_station_platform), NULL, false);
    }
    
    
    
    /**
     * 获取对应的站点、平台信息
     *
     * @param unknown $staff_code_arrays
     * @return array|array|number
     */
    public function get_station_platforms($staff_code_arrays)
    {
        if (empty($staff_code_arrays)) return [];
        
        $t_staff = $this->get_foreign_table_model()->getTable();
        
        $query = $this->_db->select('b.staff_code, a.station_code, a.platform_code')
        ->from($this->table_name . ' as a')
        ->join($t_staff . ' as b', 'a.gid = b.gid')
        ->where('state', GLOBAL_YES);
        
        count($staff_code_arrays) == 1 ?
            $query->where('b.staff_code', $staff_code_arrays[0]) :
            $query->where_in('b.staff_code', $staff_code_arrays);
        
        $result = $query->get()->result_array();
        
        if (empty($result)) return [];
        
        $staff_station_platform = [];
        foreach ($result as $row)
        {
            $staff_station_platform[$row['staff_code']][$row['station_code']][$row['platform_code']] = 1;
        }
        $result = NULL;
        unset($result);
        
        return $staff_station_platform;
    }
    
    

}