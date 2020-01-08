<?php
/**
 * FBA库存状况列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author bigfong
 * @since 2019-3-11
 * @link
 */
class StockConditionService
{
    private $_ci;

    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_at desc, id desc';

    public function __construct()
    {
        $this->_ci =& get_instance();
    }

    /**
     * 直接从redis里面拿sql获取订单号
     */
    public function quick_export_fba($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY,$time_type='1',$data_type = VIEW_BROWSER,$charset='UTF-8')
    {
        $this->_ci->load->model('Stock_fba_condition_model', 'm_stock', false, 'plan');
        $db = $this->_ci->m_stock->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {

            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_stock->getTable().'_'.TIME_TYPE[$time_type])->where_in('id', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_STOCK_CONDITION_EXPORT)->get();
            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);
            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }
            if ($total > MAX_EXCEL_LIMIT)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出30W条以上的数据，请找相关负责人', MAX_EXCEL_LIMIT), 500);
                $quick_sql .= ' limit '.MAX_EXCEL_LIMIT;
            }
        }

        if($time_type == 1){
            $this->_ci->load->classes('stock/classes/FbaMonthHugeExport');
            $this->_ci->FbaMonthHugeExport->set_format_type($format_type)->set_data_type($data_type)->set_out_charset($charset)->set_format_type($format_type)->set_title_map($profile)->set_translator()->set_data_sql($quick_sql)->set_export_nums($total);
            return $this->_ci->FbaMonthHugeExport->run();
        }elseif ($time_type == 2){
            $this->_ci->load->classes('stock/classes/FbaWeekHugeExport');
            $this->_ci->FbaWeekHugeExport->set_format_type($format_type)->set_data_type($data_type)->set_out_charset($charset)->set_format_type($format_type)->set_title_map($profile)->set_translator()->set_data_sql($quick_sql)->set_export_nums($total);
            return $this->_ci->FbaWeekHugeExport->run();
        }elseif ($time_type == 3){
            $this->_ci->load->classes('stock/classes/FbaDayHugeExport');
            $this->_ci->FbaDayHugeExport->set_format_type($format_type)->set_data_type($data_type)->set_out_charset($charset)->set_format_type($format_type)->set_title_map($profile)->set_translator()->set_data_sql($quick_sql)->set_export_nums($total);
            return $this->_ci->FbaDayHugeExport->run();
        }

    }


    /**
     * 直接从redis里面拿sql获取订单号
     */
    public function quick_export_oversea($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY,$time_type='1', $data_type = VIEW_BROWSER, $charset = 'UTF-8')
    {
        $this->_ci->load->model('Stock_oversea_condition_model', 'm_stock', false, 'plan');
        $db = $this->_ci->m_stock->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->m_stock->getTable().'_'.TIME_TYPE[$time_type])->where_in('id', $gids_arr)->order_by(self::$_default_sort)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_STOCK_CONDITION_EXPORT)->get();
            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);
            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }
            if ($total > MAX_EXCEL_LIMIT)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出30W条以上的数据，请找相关负责人', MAX_EXCEL_LIMIT), 500);
                $quick_sql .= ' limit '.MAX_EXCEL_LIMIT;
            }
        }

        if($time_type == 1){
            $this->_ci->load->classes('stock/classes/OverseaMonthHugeExport');
            $this->_ci->OverseaMonthHugeExport->set_format_type($format_type)->set_title_map($profile)->set_translator()->set_data_sql($quick_sql)->set_data_type($data_type)->set_out_charset($charset)->set_export_nums($total);
            return $this->_ci->OverseaMonthHugeExport->run();
        }elseif ($time_type == 2){
            $this->_ci->load->classes('stock/classes/OverseaWeekHugeExport');
            $this->_ci->OverseaWeekHugeExport->set_format_type($format_type)->set_title_map($profile)->set_translator()->set_data_sql($quick_sql)->set_data_type($data_type)->set_out_charset($charset)->set_export_nums($total);
            return $this->_ci->OverseaWeekHugeExport->run();
        }elseif ($time_type == 3){
            $this->_ci->load->classes('stock/classes/OverseaDayHugeExport');
            $this->_ci->OverseaDayHugeExport->set_format_type($format_type)->set_title_map($profile)->set_translator()->set_data_sql($quick_sql)->set_data_type($data_type)->set_out_charset($charset)->set_export_nums($total);
            return $this->_ci->OverseaDayHugeExport->run();
        }

    }

    public function get_cfg() : array
    {
        $this->_cfg = [
            'user_profile' => '',
        ];
        return $this->_cfg;
    }



    /**
     * FBA库存状况列表
     * @param string $params
     * @return array
     */
    public function getFbaList($params=[]){
        if (empty($params)){
            throw new \InvalidArgumentException(sprintf('参数为空'), 412);
        }

        if (empty($params['time_type']) || !in_array($params['time_type'],[1,2,3])){
            throw new \InvalidArgumentException(sprintf('参数错误'), 412);
        }
        $this->_ci->load->model('Fba_amazon_account_model', 'amazon_account', false, 'fba');
        $amazon_account_list = $this->_ci->amazon_account->all();

        //前端传过来的是中文,这里要转为id
        if (!empty($params['account_id']) && !is_numeric($params['account_id'])){
            $amazon_account = array_column($amazon_account_list,'gid','account_name');
            $params['account_id'] = $amazon_account[$params['account_id']]??'-1';
        }

        $this->_ci->load->model('Stock_fba_condition_model','m_stock_fba',false,'stock');
        $result = $this->_ci->m_stock_fba->getList($params);

        $this->_ci->load->service('basic/DropdownService');

        $fba_sales_group = $this->_ci->dropdownservice->dropdown_fba_sales_group();
        $salesmans = $this->_ci->dropdownservice->dropdown_fba_salesman();
        $amazon_account = array_column($amazon_account_list,'account_name','gid');

        $data['data_list'] = $this->_getView($result['data_list'],$fba_sales_group,$amazon_account,$salesmans);
        $data['data_page'] = array(
            'limit' => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total' => (float)$result['data_page']['total']
        );

        return $data;
    }


    /**
     * 转中文
     */
    private function _getView($valueData,$fba_sales_group,$amazon_account,$salesmans)
    {
        foreach ($valueData as $key => $value) {
            $valueData[$key]['station_code_cn'] = $this->syncStation($value['station_code']);
            $valueData[$key]['listing_state_cn'] = $this->syncListingState($value['listing_state']);
            if (!empty($value['sales_group_id'])){
                $valueData[$key]['sales_group_cn'] = $fba_sales_group[$value['sales_group_id']]??'-';
            }else{
                $valueData[$key]['sales_group_cn']='-';
            }
            if (!empty($value['salesman'])){
                $valueData[$key]['salesman'] = $salesmans[$value['salesman']]??$value['salesman'];
            }else{
                $valueData[$key]['salesman']='-';
            }
            if (!empty($value['account_id'])){
                $valueData[$key]['account_name'] = $amazon_account[$value['account_id']]??'-';
            }else{
                $valueData[$key]['account_name']='-';
            }
        }
        return $valueData;
    }

    /**
     * 转换站点
     * @param $value
     * @return mixed
     */
    private function syncStation($value)
    {
        $data = FBA_STATION_CODE;
        return isset($data[$value]) ? $data[$value]['name'] : $value;
    }

    /**
     * listing_state状态
     * @param $value
     * @return mixed
     */
    private function syncListingState($value)
    {
        $data = SKU_STATE;
        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
    }

    /**
     * 海外仓库存状况列表
     * @param string $params
     * @return array
     */
    public function getOverseaList($params=[]){
        if (empty($params)){
            throw new \InvalidArgumentException(sprintf('参数为空'), 412);
        }

        if (empty($params['time_type']) || !in_array($params['time_type'],[1,2,3])){
            throw new \InvalidArgumentException(sprintf('参数错误'), 412);
        }

        $this->_ci->load->model('Stock_oversea_condition_model','m_stock_oversea',false,'stock');
        $result = $this->_ci->m_stock_oversea->getList($params);

        $data['data_list'] = $this->_getOverSeaView($result['list']);
        $data['data_page'] = array(
            'limit' => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total' => (float)$result['total']
        );

        return $data;
    }

    /**
     * 转中文
     */
    private function _getOverSeaView($valueData)
    {
        foreach ($valueData as $key => $value) {
            $valueData[$key]['station_code_cn'] = $this->syncOverSeaStation($value['station_code']);
            $valueData[$key]['sku_state_cn'] = $this->syncOverSeaSkuState($value['listing_state']);
        }
        return $valueData;
    }

    /**
     * 转换站点
     * @param $value
     * @return mixed
     */
    private function syncOverSeaStation($value)
    {
        $data = OVERSEA_STATION_CODE;
        return isset($data[$value]) ? $data[$value]['name'] : $value;
    }

    /**
     * sku_state状态
     * @param $value
     * @return mixed
     */
    private function syncOverSeaSkuState($value)
    {
        return isset(SKU_STATE[$value]['name']) ? SKU_STATE[$value]['name'] : $value;
    }

}