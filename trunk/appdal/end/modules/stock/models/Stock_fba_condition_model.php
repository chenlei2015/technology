<?php 

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/9
 * Time: 11:01
 */
class Stock_fba_condition_model extends MY_Model
{
    private $_ci;
    private $tables = [
        1=>'yibai_stock_fba_condition_month',
        2=>'yibai_stock_fba_condition_week',
        3=>'yibai_stock_fba_condition_day',
    ];

    public function __construct()
    {
        $this->database = 'common';
        $this->_ci =& get_instance();
        //$this->table = 'yibai_stock_fba_condition';
        parent::__construct();
    }

    /**
     * FBA库存状况列表
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
        if (!empty($params['asins'])) {
            $this->_db->where_in('asin', $params['asins']);
        }
        if (!empty($params['fnskus'])) {
            $this->_db->where_in('fn_sku', $params['fnskus']);
        }
        if (!empty($params['sales_group_id'])) {
            $this->_db->where('sales_group_id', $params['sales_group_id']);
        }
        if (!empty($params['account_id'])) {
            $this->_db->where('account_id', $params['account_id']);
        }

        if (!empty($params['salesman'])) {
            $this->_db->where('salesman', $params['salesman']);
        }
        if (!empty($params['idsArr'])) {
            $this->_db->where_in('id', $params['idsArr']);
        }

        $this->_db->from($this->tables[$params['time_type']]);
        $db = clone$this->_db;
        $total = $db->count_all_results();//获取总条数
        unset($db);

        //暂存
        $query_export = clone $this->_db;
        $this->_ci->load->library('rediss');
        $this->_ci->load->service('basic/SearchExportCacheService');
        $total_l = str_pad((string)$total, 10, '0', STR_PAD_LEFT);
        $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_STOCK_CONDITION_EXPORT)->set($total_l.$query_export->get_compiled_select('',false));

        $this->_db->limit($limit);
        $this->_db->offset($offset);//表示第几页
        $this->_db->order_by('created_at', 'DESC');
        $list = $this->_db->get()->result_array();

        $data['data_list'] = $list;
        $data['data_page'] = array(
            'limit' => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total' => (float)$total
        );
        return $data;
    }


}