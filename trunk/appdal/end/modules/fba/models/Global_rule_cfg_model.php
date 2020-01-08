<?php 

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/1
 * Time: 17:18
 */
class Global_rule_cfg_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_fba_global_rule_cfg';

    }

    /**
     * 全局规则配置列表
     */
    public function globalRuleList($params = [])
    {
        if (!is_array($params)) return FALSE;

        $offset = (($params['offset'] > 0 ? $params['offset'] : 1) - 1) * $params['limit'];
        $limit = $params['limit'];
        $this->db->select('*');
        if (!empty($params['station_code'])) {
            $this->db->where('station_code', $params['station_code']);
        }
        $this->db->from($this->table);
        $db = clone $this->db;
        $total = $db->count_all_results();//获取总条数
        unset($db);
        $this->db->limit($limit);
        $this->db->offset($offset);
        $this->db->order_by('created_at', 'DESC');
        $data['data_list'] = $this->db->get()->result_array();

        $data['data_page'] = array(
            'limit' => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total' => (float)$total
        );
        return $data;
    }

    /**
     * 获取详情
     * @param string $station_code
     * @return mixed
     */
    public function getRuleDetails($station_code = '')
    {
        $this->load->model('Global_cfg_log_model', "m_log");
        $this->db->select('*');
        $this->db->where('station_code', $station_code);
        $this->db->from($this->table);
        $data['data_detail'] = $this->db->get()->row_array();
        return $data;
    }


    /**
     * 修改规则
     * @param $params
     * @return bool
     */
    public function modifyRule($params)
    {
        $this->db->trans_start();
        $result = [];
        if (!is_array($params)) {

            $result['code'] == 0;
            $result['errorMess'] = '数据异常,修改失败';
            return $result;
        }
        $this->load->model('Global_cfg_log_model', "m_log");
        $temp = [
            'as_up' => $params['as_up'],
            'ls_shipping_full' => $params['ls_shipping_full'],
            'ls_shipping_bulk' => $params['ls_shipping_bulk'],
            'ls_trains_full' => $params['ls_trains_full'],
            'ls_trains_bulk' => $params['ls_trains_bulk'],
            'ls_air' => $params['ls_air'],
            'ls_red' => $params['ls_red'],
            'ls_blue' => $params['ls_blue'],
            'pt_shipping_full' => $params['pt_shipping_full'],
            'pt_shipping_bulk' => $params['pt_shipping_bulk'],
            'pt_trains_full' => $params['pt_trains_full'],
            'pt_trains_bulk' => $params['pt_trains_bulk'],
            'pt_air' => $params['pt_air'],
            'pt_red' => $params['pt_red'],
            'pt_blue' => $params['pt_blue'],
            'bs' => $params['bs'],
            'sc' => $params['sc'],
//            'sp' => $params['sp'],
            'sz' => $params['sz'],
        ];

        $result = $this->getOneRule($params['station_code']);
        if (empty($result)) {
            $result['code'] = 0;
            $result['errorMess'] = '未找到此条记录的信息,修改失败';
            return $result;
        }

        $diff = [];
        $count = 0;
        foreach ($result as $key =>$value){
            if($result[$key] != $temp[$key]){
                $diff[$key] =  $temp[$key];
                $count++;
            }
        }

        if($count == 0){
            $result['code'] = 0;
            $result['errorMess'] = '您未作任何修改，无法保存！';
            return $result;
        }
        //更新全局配置表
        $this->db->where('station_code', $params['station_code']);
        $this->db->update($this->table, $params);

        //新增日志
        $this->m_log->addGlobalRuleLog($diff, $params);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            $result['code'] = 0;
            $result['errorMess'] = '修改失败';
        } else {
            $result['code'] = 1;
        }
        return $result;
    }

    /**
     * 查询单条规则
     * @param string $station_code
     * @return mixed
     */
    public function getOneRule($station_code = '')
    {
        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sz');
        $this->db->where('station_code', $station_code);
        $this->db->from($this->table);
        return $this->db->get()->row_array();
    }

    /**
     * 更新备注
     */
    public function updateRemark($data)
    {
        $this->db->where('station_code', $data['station_code']);
        $this->db->update($this->table, ['remark' => $data['remark']]);
    }

    public function global_cfg()
    {
        //将全局表所有信息查出来
        $this->db->select('*');
        $this->db->from('yibai_fba_global_rule_cfg');
        $g_result = $this->db->get()->result_array();
        //键名为站点名
        if (!empty($g_result)) {
            foreach ($g_result as $k => $val) {
                $all_station[$val['station_code']] = $val;
            }
        }

        return $all_station;
    }
}