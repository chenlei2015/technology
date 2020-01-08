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
class Shipment_oversea_manager_account_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_shipment_oversea_manager_account';
        $this->primaryKey = '';
        $this->tableId = 26;
        parent::__construct();
    }

    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }

    protected function get_foreign_table_model()
    {
        $ci = CI::$APP;
        $ci->load->model('Shipment_oversea_manager_staff_model', 'm_shipment_manager_staff', false, 'shipment');
        return $ci->m_shipment_manager_staff;
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
        $map = $existed_rows = [];

        //查询所有记录， 全部记录也不多， 如果一个平台可以配多个管理员则要构造查询条件，因为记录数量已经不可预计
         foreach ($this->get_staffs_by_stations() as $row)
         {
             $existed_rows[$row['station_code']] = $row;
         }

        //构造一个数组
        foreach (OVERSEA_STATION_CODE as $state => $cfg)
        {

            $map[] = [
                    'station_code' => $state,
                    'station_name' => $cfg['name'],
                    'staff_code' => $existed_rows[$state]['staff_code'] ?? '',
                    'user_zh_name' => $existed_rows[$state]['user_zh_name'] ?? '-',
                    'status_text' => $existed_rows[$state]['status_text'] ?? '-',
                    'op_zh_name' => $existed_rows[$state]['op_zh_name'] ?? '-',
                    'updated_at' => $existed_rows[$state]['updated_at'] ?? '-',
            ];
        }

        return [
                'page_data' => [
                        'total' => count($map),
                        'offset' => $page,
                        'limit' => $page_size,
                        'pages' => floor(count($map) / $page_size)
                ],
                'data_list'  => [
                        'value' => array_slice($map, ($page - 1) * $page_size, $page_size)
                ]
        ];
    }


    /**
     *
     * @param unknown $stations
     */
    public function get_staffs_by_stations()
    {
        $t_staff = $this->get_foreign_table_model()->getTable();

        $query = $this->_db->select('a.station_code,a.op_zh_name,a.updated_at,b.*')
            ->from($this->table_name . ' as a')
            ->join($t_staff . ' as b', 'a.gid = b.gid')
            ->group_by('a.gid');

        $staff_info = "GROUP_CONCAT(CONCAT_WS(':', b.staff_code, b.user_zh_name, b.state)) as info";
        $query->select($staff_info, NULL, true);
        $result = $query->get()->result_array();
        foreach ($result as $row => &$val)
        {
            $group_info = [];
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
        return $result;
    }


    /**
     * staff_code => $user_zh_name
     */
    public function get_unique_manager()
    {
        $result = $this->_db->select('staff_code, user_zh_name')->from($this->get_foreign_table_model()->getTable())->group_by('staff_code')->order_by('staff_code', 'asc')->get()->result_array();
        if (empty($result)) return [];
        foreach ($result as $row)
        {
            $options[$row['staff_code']] = $row['staff_code'] . ' '.$row['user_zh_name'];
        }
        return $options;
    }

    public function get_dync_manager_by_name($user_name)
    {
        if (!$user_name || trim($user_name) == '')
        {
            return $this->get_unique_manager();
        }
        $result = $this->_db->select('staff_code, user_zh_name')->from($this->get_foreign_table_model()->getTable())->like('user_zh_name', $user_name)->group_by('staff_code')->order_by('staff_code', 'asc')->get()->result_array();
        if (empty($result)) return [];
        foreach ($result as $row)
        {
            $options[$row['staff_code']] = $row['staff_code'] . ' '.$row['user_zh_name'];
        }
        return $options;
    }

    /**
     * 获取平台和站点的记录
     *
     * @param unknown $station_code
     * @param unknown $platform_code
     * @param string $staff_code
     * @return unknown
     */
    public function get_station_row($station_code, $staff_code = '')
    {
        $query = $this->_db->select('*')
            ->from($this->table_name)
            ->where('station_code', $station_code);
        if ($staff_code != '')
        {
            $query->where('staff_code', $staff_code);
        }
        return $query->get()->result_array();
    }


    /**
     * 获取站点管理员信息
     * @param $station_code
     * @param array $staff_code
     * @return mixed
     */
    public function get_station_staff($station_code, $staff_code = [])
    {
        $o_t = $this->table_name;
        $p_t = $this->get_foreign_table_model()->getTable();
        $query = $this->_db->from($o_t.' as o')->join($p_t.' as p','p.gid =o.gid')->where('o.station_code',$station_code)->group_by('o.gid');

        if(!empty($staff_code)){
            count($staff_code) == 1 ? $query->where('p.staff_code', $staff_code[0]) : $query->where_in('p.staff_code', $staff_code);
        }
        $query->select('o.*')->select("GROUP_CONCAT(CONCAT_WS(':', p.staff_code, p.user_zh_name, p.state)) as info", NULL, true);
        $result = $query->get()->result_array();
        if (empty($result)) return $result;
        foreach ($result as $key=> &$val){
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
        return $result;
    }

    /**
     * @param int $state
     * @return mixed
     */

    public function get_staffs($state = 0)
    {
        $query = $this->_db->select('staff_code, state')->from($this->table_name);
        if ($state != 0)
        {
            $query->where('state', $state);
        }
        return $query->get()->result_array();
    }

    public function enable_staffs($staff_codes)
    {
        $this->_db->reset_query();
        $this->_db->where_in('staff_code', $staff_codes);
        return $this->_db->update($this->table_name, ['state' => GLOBAL_YES]);
    }

    public function disabled_staffs($staff_codes)
    {
        $this->_db->reset_query();
        $this->_db->where_in('staff_code', $staff_codes);
        return $this->_db->update($this->table_name, ['state' => GLOBAL_NO]);
    }

    public function get_my_stations($staff_code, $state = GLOBAL_YES)
    {
        $query = $this->_db->select('station_code')->from($this->table_name)->where('staff_code', $staff_code);
        if ($state !== 0)
        {
            $query->where('state', $state);
        }
        return array_column($query->get()->result_array(), 'station_code');
    }

}
