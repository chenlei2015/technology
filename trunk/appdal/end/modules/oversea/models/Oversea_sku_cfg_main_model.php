<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/2
 * Time: 17:48
 */
class Oversea_sku_cfg_main_model extends MY_Model
{
    private $_ci;

    public function __construct()
    {
        $this->_ci   =& get_instance();
        $this->table = 'yibai_oversea_sku_cfg_main';
        parent::__construct();
    }

    /**
     * 查询备货关系列表
     * v1.1.1 供货周期从common库yibai_oversea_lead_time表取
     *
     * @param array $params
     *
     * @return bool
     */
    public function getStockList($params = [], $gid = [], $get_count = '')
    {
        if (!is_array($params)) return FALSE;
        //权限控制
        if(isset($params['owner_station'])){
            $my_station = $params['owner_station'];
            if(isset($params['station_code'])){//查询
                if (!in_array($params['station_code'],$my_station)){//查询的站点 没有权限
                    return false;
                }
            }else{//不查询
                $params['station_code'] = $params['owner_station'];
            }
        }
        $offset = (($params['offset'] > 0 ? $params['offset'] : 1) - 1) * $params['limit'];
        $limit  = $params['limit'];
        $this->db->select('a.*,b.as_up,b.ls_shipping_full,b.ls_shipping_bulk,b.ls_trains_full,b.ls_trains_bulk,b.ls_land,b.ls_air,b.ls_red,b.ls_blue,
                b.pt_shipping_full,b.pt_shipping_bulk,b.pt_trains_full,b.pt_trains_bulk,b.pt_land,b.pt_air,b.pt_red,b.pt_blue,        
                b.bs,b.sc,b.sp,b.sz,lt.lead_time as lt,b.updated_at,b.updated_uid,b.updated_zh_name,b.state,b.approved_at,b.approved_uid,b.approved_zh_name,b.remark');
        if (!empty($gid)) {
            $this->db->where_in('a.gid', $gid);
            $this->db->from($this->table . ' a');
            $this->db->join('yibai_oversea_sku_cfg_part b', 'a.gid=b.gid', 'LEFT');
            $this->db->join('yibai_oversea_lead_time lt', 'a.sku=lt.sku', 'LEFT');
            $this->db->limit($params['length']);
            $this->db->offset($offset);
            $this->db->order_by('a.created_at', 'DESC');
            $this->db->order_by('a.gid', 'DESC');
            $result            = $this->db->get()->result_array();
            $result            = $this->joinGlobal($result);
            $data['data_list'] = $result;

            return $data;
        }
        $this->db->where('a.rule_type >',0);//优化sql,走索引
        if (!empty($params['sku'])) {
            $this->db->where_in('a.sku', trimArray($params['sku']));
        }
        if (!empty($params['station_code'])) {
            $this->db->where('a.station_code', $params['station_code']);
        }
        if (!empty($params['sku_state'])) {
            $this->db->where('a.sku_state', $params['sku_state']);
        }
        if (!empty($params['product_status'])) {
            $this->db->where('a.product_status', $params['product_status']);
        }
        if (!empty($params['sale_state'])) {
            $this->db->where('sale_state', $params['sale_state']);
        }
        if (!empty($params['state'])) {
            $this->db->where('state', $params['state']);
        }
        if (!empty($params['supplier_code'])) {
            $this->db->where('supplier_code', $params['supplier_code']);
        }
        if (!empty($params['rule_type'])) {
            $this->db->where('rule_type', $params['rule_type']);
        }
        if (!empty($params['created_at_start']) && !empty($params['created_at_end'])) {
            $this->db->where('created_at >=', $params['created_at_start'] . ' 00:00:00');
            $this->db->where('created_at <=', $params['created_at_end'] . ' 23:59:59');
        }
        if (!empty($params['updated_at_start']) && !empty($params['updated_at_end'])) {
            $this->db->where('updated_at >=', $params['updated_at_start'] . ' 00:00:00');
            $this->db->where('updated_at <=', $params['updated_at_end'] . ' 23:59:59');
        }
        if (!empty($params['approved_at_start']) && !empty($params['approved_at_end'])) {
            $this->db->where('approved_at >=', $params['approved_at_start'] . ' 00:00:00');
            $this->db->where('approved_at <=', $params['approved_at_end'] . ' 23:59:59');
        }
        if (!empty($params['is_refund_tax'])) {
            $this->db->where('is_refund_tax', $params['is_refund_tax']);
        }
        if (!empty($params['pur_warehouse_id'])) {
            $this->db->where('purchase_warehouse_id', $params['pur_warehouse_id']);
        }
        $this->db->from('yibai_oversea_sku_cfg_part b');
        $this->db->join($this->table . ' a', 'a.gid=b.gid', 'left');
        $this->db->join('yibai_oversea_lead_time lt', 'a.sku=lt.sku', 'LEFT');

        $db    = clone $this->db;
        $total = $db->count_all_results();//获取总条数

        //暂存
        $query_export = clone $this->db;
        $this->_ci->load->library('rediss');
        $this->_ci->load->service('basic/SearchExportCacheService');
        $total_l = str_pad((string)$total, 10, '0', STR_PAD_LEFT);
        $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT)->set($total_l . $query_export->get_compiled_select('', false));

        if (!empty($get_count)) {
            $data['count'] = $total;

            return $data;
        }
        unset($db);
        if (!empty($params['length']) && isset($params['start'])) {
            $this->db->limit($params['length'], $params['start']);
        } else {
            $this->db->limit($limit);
        }
        $this->db->offset($offset);
        $this->db->order_by('created_at', 'DESC');
        $this->db->order_by('a.gid', 'DESC');
        $result = $this->db->get()->result_array();

        //全局的属性从全局配置表获取,自定义在part表获取
        $result = $this->joinGlobal($result);

        //拼上供货周期
        $result = $this->joinLeadTime($result);

        $data['data_list'] = $result;
        $data['data_page'] = [
            'limit'  => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total'  => (float)$total
        ];

        return $data;

    }

    /**
     * 海外供货周期为空的默认为42
     * @param array $result
     */
    public function joinLeadTime($result = [])
    {
        foreach ($result as $key => &$item){
            if (empty($item['lt'])){
                $item['lt'] = 42;
            }
        }
        return $result;
    }


    /**
     * 全局的属性从全局配置表获取,自定义在part表获取
     *
     * @param $result
     *
     * @return mixed
     */
    public function joinGlobal($result = [])
    {
        //将全局表所有信息查出来
        $this->db->select('*');
        $this->db->from('yibai_oversea_global_rule_cfg');
        $g_result = $this->db->get()->result_array();
        //键名为站点名
        foreach ($g_result as $k => $val) {
            $all_station[$val['station_code']] = $val;
        }

        //更新规则类型为全局的数据
        foreach ($result as $key => $value) {
            $station_code = $value['station_code'];

            if ($value['rule_type'] == 2 && isset($all_station[$station_code])) {
                $value['as_up']            = $all_station[$station_code]['as_up'];
                $value['ls_shipping_full'] = $all_station[$station_code]['ls_shipping_full'];
                $value['ls_shipping_bulk'] = $all_station[$station_code]['ls_shipping_bulk'];
                $value['ls_trains_full']   = $all_station[$station_code]['ls_trains_full'];
                $value['ls_trains_bulk']   = $all_station[$station_code]['ls_trains_bulk'];
                $value['ls_land']          = $all_station[$station_code]['ls_land'];
                $value['ls_air']           = $all_station[$station_code]['ls_air'];
                $value['ls_red']           = $all_station[$station_code]['ls_red'];
                $value['ls_blue']          = $all_station[$station_code]['ls_blue'];
                $value['pt_shipping_full'] = $all_station[$station_code]['pt_shipping_full'];
                $value['pt_shipping_bulk'] = $all_station[$station_code]['pt_shipping_bulk'];
                $value['pt_trains_full']   = $all_station[$station_code]['pt_trains_full'];
                $value['pt_trains_bulk']   = $all_station[$station_code]['pt_trains_bulk'];
                $value['pt_land']          = $all_station[$station_code]['pt_land'];
                $value['pt_air']           = $all_station[$station_code]['pt_air'];
                $value['pt_red']           = $all_station[$station_code]['pt_red'];
                $value['pt_blue']          = $all_station[$station_code]['pt_blue'];
                $value['bs']               = $all_station[$station_code]['bs'];
                $value['sc']               = $all_station[$station_code]['sc'];
                $value['sp']               = $all_station[$station_code]['sp'];
                $value['sz']               = $all_station[$station_code]['sz'];
                $result[$key]              = $value;
            } else {
                continue;
            }
        }

        return $result;
    }

    public function get_all_station()
    {
        //将全局表所有信息查出来
        $this->db->select('*');
        $this->db->from('yibai_oversea_global_rule_cfg');
        $g_result = $this->db->get()->result_array();
        //键名为站点名
        foreach ($g_result as $k => $val) {
            $all_station[$val['station_code']] = $val;
        }

        return $all_station??[];
    }

    /**
     * 获取详情
     *
     * @param string $station_code
     *
     * @return mixed
     */
    public function getStockDetails($gid)
    {
        $this->db->select('a.*,b.as_up,b.ls_shipping_full,b.ls_shipping_bulk,b.ls_trains_full,b.ls_trains_bulk,b.ls_land,b.ls_air,b.ls_red,b.ls_blue,        
        b.pt_shipping_full,b.pt_shipping_bulk,b.pt_trains_full,b.pt_trains_bulk,b.pt_land,b.pt_air,b.pt_red,b.pt_blue,        
        b.bs,b.sc,b.sp,b.sz,lt.lead_time as lt,b.updated_at,b.updated_uid,b.updated_zh_name,b.state,b.approved_at,b.approved_uid,b.approved_zh_name,b.remark');
        $this->db->where('a.gid', $gid);
        $this->db->from('yibai_oversea_sku_cfg_part b');
        $this->db->join($this->table . ' a', 'a.gid=b.gid', 'left');
        $this->db->join('yibai_oversea_lead_time lt', 'a.sku=lt.sku', 'left');

        $result = $this->db->get()->row_array();
        //全局规则的拼全局配置表
        if ($result['rule_type'] == 2) {
            $this->db->select('*');
            $this->db->from('yibai_oversea_global_rule_cfg');
            $this->db->where('station_code', $result['station_code']);
            $g_result = $this->db->get()->row_array();
            if (empty($g_result)) {
                return NULL;
            }
            $result['as_up']            = $g_result['as_up'];
            $result['ls_shipping_full'] = $g_result['ls_shipping_full'];
            $result['ls_shipping_bulk'] = $g_result['ls_shipping_bulk'];
            $result['ls_trains_full']   = $g_result['ls_trains_full'];
            $result['ls_trains_bulk']   = $g_result['ls_trains_bulk'];
            $result['ls_land']          = $g_result['ls_land'];
            $result['ls_air']           = $g_result['ls_air'];
            $result['ls_red']           = $g_result['ls_red'];
            $result['ls_blue']          = $g_result['ls_blue'];
            $result['pt_shipping_full'] = $g_result['pt_shipping_full'];
            $result['pt_shipping_bulk'] = $g_result['pt_shipping_bulk'];
            $result['pt_trains_full']   = $g_result['pt_trains_full'];
            $result['pt_trains_bulk']   = $g_result['pt_trains_bulk'];
            $result['pt_land']          = $g_result['pt_land'];
            $result['pt_air']           = $g_result['pt_air'];
            $result['pt_red']           = $g_result['pt_red'];
            $result['pt_blue']          = $g_result['pt_blue'];
            $result['bs']               = $g_result['bs'];
            $result['sc']               = $g_result['sc'];
            $result['sp']               = $g_result['sp'];
            $result['sz']               = $g_result['sz'];
        }

        $data['data_detail'] = $result;

        return $data;
    }


    /**
     * 根据station_code查询gid
     */
    public function getGidBystation($station_code)
    {
        $this->db->select('gid');
        $this->db->from($this->table);
        $this->db->where(['rule_type' => 2, 'station_code' => $station_code]);
        return $this->db->get()->result_array();
    }

    /**
     * @param array $suppliers
     */
    //todo:对字段
    public function get_supplier_moq_info(array $suppliers)
    {
        $query = $this->_db->from($this->table_name)
            ->select('supplier_code,sku,moq_qty,original_min_start_amount as moq_step1,min_start_amount as moq_step2');
        if (count($suppliers) == 1) {
            $query->where('supplier_code', $suppliers[0]);
        } else {
            $query->where_in('supplier_code', $suppliers);
        }

        $result = $query->get()->result_array();

        $format = [];
        foreach ($result as $row) {
            $format[$row['supplier_code']][$row['sku']] = $row;
        }
        unset($result);
        return $format;
    }


}