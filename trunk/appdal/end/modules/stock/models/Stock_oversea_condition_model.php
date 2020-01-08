<?php 

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/9
 * Time: 11:01
 */
class Stock_oversea_condition_model extends MY_Model
{
    private $tables = [
        1=>'yibai_stock_oversea_condition_month',
        2=>'yibai_stock_oversea_condition_week',
        3=>'yibai_stock_oversea_condition_day',
    ];
    private $_ci;

    public function __construct()
    {
        $this->database = 'common';
        //$this->table = 'yibai_stock_oversea_condition';
        $this->_ci =& get_instance();
        parent::__construct();
    }

    /**
     * 获取物流属性配置列表
     */
    public function getList($params = [])
    {
        if (!is_array($params)) return FALSE;

        $this->load->service('basic/DropdownService');

        $offset = (($params['offset'] > 0 ? $params['offset'] : 1) - 1) * $params['limit'];
        $limit = $params['limit'];
        $this->_db->select('*');

        if (!empty($params['time_data'])) {
            $this->_db->where('time_data', $params['time_data']);
        }

        if (!empty($params['station_code'])) {
            $this->_db->where('station_code', $params['station_code']);
        }
        if (!empty($params['time_start']) && !empty($params['time_end'])) {
            $this->_db->where('shelf_time >=', $params['time_start'] . ' 00:00:00');
            $this->_db->where('shelf_time <=', $params['time_end'] . ' 23:59:59');
        }

        if (!empty($params['sku_state'])) {
            $this->_db->where('listing_state', $params['sku_state']);
        }
        if (!empty($params['skus'])) {
            $this->_db->where_in('sku', $params['skus']);
        }

        if (!empty($params['idsArr'])) {
            $this->_db->where_in('id', $params['idsArr']);
        }

        $this->_db->from($this->tables[$params['time_type']]);
        $db = clone $this->_db;
        $total = $db->count_all_results();//获取总条数
        unset($db);

        //暂存
        $query_export = clone $this->_db;
        $this->_ci->load->library('rediss');
        $this->_ci->load->service('basic/SearchExportCacheService');
        $total_l = str_pad((string)$total, 10, '0', STR_PAD_LEFT);
        $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_STOCK_CONDITION_EXPORT)->set($total_l.$query_export->get_compiled_select('',false));

        $this->_db->limit($limit);
        $this->_db->offset($offset);
        $this->_db->order_by('created_at', 'DESC');
        $list = $this->_db->get()->result_array();

        $data['data_list'] = $list;
        $data['data_page'] = array(
            'limit' => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total' => (float)$total
        );
        return ['list' => $list, 'total' => $total];
    }


}