<?php

/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/3/11
 * Time: 9:32
 */
class Stock_condition extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * FBA库存状况列表
     * http://192.168.71.170:1084/stock/stock_condition/getFbaList
     */
    public function getFbaList()
    {
        $this->load->service('basic/UsercfgProfileService');
        $this->lang->load('common');
        /*
        1、时间维度选择月的时候，日期只显示到年，下面数据根据月份统计
        2、时间维度选择为周的时候，日期显示到月份，下面数据根据周统计，以每月第一天为起始，往后推七天为一周
        3、时间维度选择为日的时候，日期显示到日，下面数据往前倒推统计
        */
        $time_type = $this->input->post_get('time_type');//时间维度
        $time_data = $this->input->post_get('time_data');//根据时间维度选择不同的日期
        $time_start = $this->input->post_get('time_start') ?? '';//上架开始时间
        $time_end = $this->input->post_get('time_end') ?? '';//上架结束时间
        $sku = $this->input->post_get('sku') ?? '';
        $asin = $this->input->post_get('asin') ?? '';
        $fnsku = $this->input->post_get('fnsku') ?? '';
        $station_code = $this->input->post_get('station_code') ?? '';
        $sku_state = $this->input->post_get('sku_state') ?? '';

        $sales_group_id = $this->input->post_get('sales_group_id') ?? '';//销售小组
        $salesman = $this->input->post_get('salesman') ?? '';//销售人员
        $account_id = $this->input->post_get('account_id') ?? '';//销售账号
        $offset = $this->input->post_get('offset');
        $limit = $this->input->post_get('limit');
        $offset = $offset ? $offset : 1;
        $limit = $limit ? $limit : 20;

        $isExcel = $this->input->get_post("isExcel");
        $idsArr = [];
        if ($isExcel == 1){
            $idsArr = params_ids_to_array($this->input->get_post('ids'),false);
            $offset = 1;
            $limit =  MAX_EXCEL_LIMIT;
        }


        $column_keys = array(
            $this->lang->myline('sales_group'),                 //销售小组
            $this->lang->myline('salesman'),                    //销售人员
            $this->lang->myline('account_name'),                //账号名称
            $this->lang->myline('sku'),                         //SKU
            $this->lang->myline('seller_sku'),                  //SellerSKU
            $this->lang->myline('fn_sku'),                      //FNSKU
            $this->lang->myline('asin'),                        //ASIN
            $this->lang->myline('product_name'),                //产品名称
            $this->lang->myline('fba_station'),                 //FBA站点
            $this->lang->myline('shelf_time'),                  //上架时间
            $this->lang->myline('sku_state'),                   //sku状态
        );

        if (empty($time_type) || !in_array($time_type,array(1,2,3))){
            $this->response_error('3001');
        }

       if ($time_type==1){//月
            for ($i = 1;$i<=12;$i++){
                $column_keys[] = $this->lang->myline('month_'.$i);//月份
            }
            $column_keys[] = $this->lang->myline('month_sales');//本月销量
           $result = $this->usercfgprofileservice->get_display_cfg('fba_stock_condition_month');
           $this->data['selected_data_list'] = $result['config'];
           $this->data['profile'] = $result['field'];

        }elseif ($time_type==2){//周
            for ($i = 1;$i<=5;$i++){
                $column_keys[] = $this->lang->myline('week_no_'.$i);//第几周
            }
            $column_keys[] = $this->lang->myline('month_sales');//本月销量

           $result = $this->usercfgprofileservice->get_display_cfg('fba_stock_condition_week');
           $this->data['selected_data_list'] = $result['config'];
           $this->data['profile'] = $result['field'];
        }elseif ($time_type==3){//日
            $column_keys[] = $this->lang->myline('last_days_1');//过去1天
            $column_keys[] = $this->lang->myline('last_days_3');//过去3天
            $column_keys[] = $this->lang->myline('last_days_7');//过去7天
            $column_keys[] = $this->lang->myline('last_days_14');//过去14天
            $column_keys[] = $this->lang->myline('last_days_28');//过去28天
            //$column_keys[] = $this->lang->myline('weighted_sales');//加权销量

           $result = $this->usercfgprofileservice->get_display_cfg('fba_stock_condition_day');
           $this->data['selected_data_list'] = $result['config'];
           $this->data['profile'] = $result['field'];
        }

        $column_keys[] = $this->lang->myline('accumulative_sales');//累计销量
        $column_keys[] = $this->lang->myline('accumulative_return');//累计退货
        $column_keys[] = $this->lang->myline('rma');//RMA
        $column_keys[] = $this->lang->myline('can_sale_stock');//可售库存
        $column_keys[] = $this->lang->myline('cannot_sale_stock');//'不可售库存';
        $column_keys[] = $this->lang->myline('shipping_full_in_transit');//'海运整柜在途';
        $column_keys[] = $this->lang->myline('shipping_bulk_in_transit');//'海运散货在途';
        $column_keys[] = $this->lang->myline('trains_full_in_transit');//'铁运整柜在途';
        $column_keys[] = $this->lang->myline('trains_bulk_in_transit');//'铁运散货在途';
        $column_keys[] = $this->lang->myline('air_in_transit');//'空运在途';
        $column_keys[] = $this->lang->myline('blueorder_in_transit') ;//'蓝单在途';
        $column_keys[] = $this->lang->myline('redorder_in_transit');//'红单在途';

        if ($time_type==1) {//月
            $column_keys[] = $this->lang->myline('fba_onway_data');//'FBA在途数据';

        }

        $params = [
            'time_type' => $time_type,
            'time_data' => $time_data,
            'time_start' => $time_start,
            'time_end' => $time_end,
            'skus' => params_ids_to_array($sku,false),
            'asins' =>  params_ids_to_array($asin,false),
            'fnskus' => params_ids_to_array($fnsku,false),
            'station_code' => $station_code,
            'sku_state' => $sku_state,
            'sales_group_id' => $sales_group_id,
            'salesman' => $salesman,
            'account_id' => $account_id,
            'offset' => $offset,
            'limit' => $limit,
            'idsArr'=>$idsArr
        ];

        $this->load->service('stock/StockConditionService');
        $result = $this->stockconditionservice->getFbaList($params);

        if (isset($result) && count($result['data_list']) > 0) {
            tran_time_result($result['data_list'], ['shelf_time','created_at']);
        }

        //导出
        if (isset($isExcel) && $isExcel == 1) {
            array_splice($column_keys,0,0,$this->lang->myline('item'));
            /*$this->data = [];
            $this->data['status'] = 1;
            $this->data['data_list'] = ['key' => $column_keys, 'value' => $result['data_list']];
            http_response($this->data);*/
        }
        $this->load->service('basic/DropdownService');
        $this->dropdownservice->setDroplist(['fba_salesman']);               //销售人员下拉列表
        $this->dropdownservice->setDroplist(['fba_sales_group']);    //销售小组下拉列表
        $this->dropdownservice->setDroplist(['station_code']);           //站点下拉列表
        $this->dropdownservice->setDroplist(['sku_state']);                 //sku状态下拉列表

        if (isset($result) && count($result['data_list']) > 0) {

            $this->data['status'] = 1;
            $this->data['data_list'] = array(
                'key'           => $column_keys,
                'value'         => $result['data_list'],
                'drop_down_box' => $this->dropdownservice->get()
            );
            $this->data['page_data'] = array(
                'offset' => (int)$result['data_page']['offset'],
                'limit' => (int)$result['data_page']['limit'],
                'total' => $result['data_page']['total'],
            );
        } else {
            $this->data['status'] = 1;
            $this->data['data_list'] = array(
                'key' => $column_keys,
                'value' => array(),
            );
            $this->data['page_data'] = array(
                'offset' => (int)$offset,
                'limit' => (int)$limit,
                'total' => $result['data_page']['total']
            );
        }

        http_response($this->data);
    }

    
    /**
     * 海外仓库存状况列表
     * http://192.168.71.170:1084/stock/stock_condition/getOverseaList
     */
    public function getOverseaList()
    {
        $this->load->service('basic/UsercfgProfileService');
        $this->lang->load('common');
        /*
        1、时间维度选择月的时候，日期只显示到年，下面数据根据月份统计
        2、时间维度选择为周的时候，日期显示到月份，下面数据根据周统计，以每月第一天为起始，往后推七天为一周
        3、时间维度选择为日的时候，日期显示到日，下面数据往前倒推统计
        */
        $time_type = $this->input->post_get('time_type');//时间维度
        $time_data = $this->input->post_get('time_data') ? trim($this->input->post_get('time_data')): '';;//根据时间维度选择不同的日期
        $time_start = $this->input->post_get('time_start') ?? '';//上架开始时间
        $time_end = $this->input->post_get('time_end') ?? '';//上架结束时间
        $sku = $this->input->post_get('sku')?? '';
        $station_code = $this->input->post_get('station_code')?? '';
        $sku_state = $this->input->post_get('sku_state') ?? '';

        $offset = $this->input->post_get('offset');
        $limit = $this->input->post_get('limit');
        $offset = $offset ? $offset : 1;
        $limit = $limit ? $limit : 20;

        if (empty($time_type) || !in_array($time_type,array(1,2,3))){
            $this->response_error('3001');
        }

        $isExcel = $this->input->get_post("isExcel");
        $idsArr = [];
        if ($isExcel == 1){
            $idsArr = params_ids_to_array($this->input->get_post('ids'),false);
            $offset = 1;
            $limit =  MAX_EXCEL_LIMIT;
        }

        $column_keys = array(
            $this->lang->myline('sku'),                         //SKU
            $this->lang->myline('product_name'),                //产品名称
            $this->lang->myline('oversea_station'),                 //FBA站点
            $this->lang->myline('shelf_time'),                  //上架时间
            $this->lang->myline('sku_state'),                   //sku状态
        );

        if ($time_type==1){//月
            for ($i = 1;$i<=12;$i++){
                $column_keys[] = $this->lang->myline('month_'.$i);//月份
            }
            $column_keys[] = $this->lang->myline('month_sales');//本月销量
            $result = $this->usercfgprofileservice->get_display_cfg('oversea_stock_condition_month');

            $this->data['selected_data_list'] = $result['config'];
            $this->data['profile'] = $result['field'];

            empty($time_data) && $time_data = date('Y');
        }elseif ($time_type==2){//周
            for ($i = 1;$i<=5;$i++){
                $column_keys[] = $this->lang->myline('week_no_'.$i);//第几周
            }
            $column_keys[] = $this->lang->myline('month_sales');//本月销量
            $result = $this->usercfgprofileservice->get_display_cfg('oversea_stock_condition_week');
            $this->data['selected_data_list'] = $result['config'];
            $this->data['profile'] = $result['field'];
        }elseif ($time_type==3){//日
            $column_keys[] = $this->lang->myline('last_days_1');//过去1天
            $column_keys[] = $this->lang->myline('last_days_3');//过去3天
            $column_keys[] = $this->lang->myline('last_days_7');//过去7天
            $column_keys[] = $this->lang->myline('last_days_14');//过去14天
            $column_keys[] = $this->lang->myline('last_days_28');//过去28天
            //$column_keys[] = $this->lang->myline('weighted_sales');//加权销量
            $result = $this->usercfgprofileservice->get_display_cfg('oversea_stock_condition_day');
            $this->data['selected_data_list'] = $result['config'];
            $this->data['profile'] = $result['field'];
        }

        $column_keys[] = $this->lang->myline('accumulative_sales');//累计销量
        $column_keys[] = $this->lang->myline('accumulative_return');//累计退货
        $column_keys[] = $this->lang->myline('rma');//RMA
        $column_keys[] = $this->lang->myline('can_sale_stock');//可售库存
        $column_keys[] = $this->lang->myline('cannot_sale_stock');//'不可售库存';

        $column_keys[] = $this->lang->myline('shipping_full_in_transit');//'海运整柜在途';
        $column_keys[] = $this->lang->myline('shipping_bulk_in_transit');//'海运散货在途';
        $column_keys[] = $this->lang->myline('trains_full_in_transit');//'铁运整柜在途';
        $column_keys[] = $this->lang->myline('trains_bulk_in_transit');//'铁运散货在途';
        $column_keys[] = $this->lang->myline('air_in_transit');//'空运在途';
        $column_keys[] = $this->lang->myline('land_in_transit');//'陆运在途';
        $column_keys[] = $this->lang->myline('blueorder_in_transit') ;//'蓝单在途';
        $column_keys[] = $this->lang->myline('redorder_in_transit');//'红单在途';


        $params = [
            'time_type' => $time_type,
            'time_data' => $time_data,
            'time_start' => $time_start,
            'time_end' => $time_end,
            'skus' => params_ids_to_array($sku,false),
            'station_code' => $station_code,
            'sku_state' => $sku_state,
            'offset' => $offset,
            'limit' => $limit,
            'idsArr' => $idsArr
        ];

        $this->load->service('stock/StockConditionService');
        $result = $this->stockconditionservice->getOverseaList($params);

        if (isset($result) && count($result['data_list']) > 0) {
            tran_time_result($result['data_list'], ['shelf_time','created_at']);
        }

        
        //导出
        if ($isExcel == 1) {
            array_splice($column_keys,0,0,$this->lang->myline('item'));
            $this->data = [];
            $this->data['status'] = 1;
            $this->data['data_list'] = ['key' => $column_keys, 'value' => $result['data_list']];
            http_response($this->data);
            return;
        }
        $this->load->service('basic/DropdownService');
        $this->dropdownservice->setDroplist(['os_station_code']);           //站点下拉列表
        $this->dropdownservice->setDroplist(['sku_state']);                 //sku状态下拉列表

        if (isset($result) && count($result['data_list']) > 0) {
            $this->data['status'] = 1;
            $this->data['data_list'] = array(
                'key'           => $column_keys,
                'value'         => $result['data_list'],
                'drop_down_box' => $this->dropdownservice->get()
            );
            $this->data['page_data'] = array(
                'offset' => (int)$result['data_page']['offset'],
                'limit' => (int)$result['data_page']['limit'],
                'total' => $result['data_page']['total'],
            );
        } else {
            $this->data['status'] = 1;
            $this->data['data_list'] = array(
                'key' => $column_keys,
                'value' => array(),
            );
            $this->data['page_data'] = array(
                'offset' => (int)$offset,
                'limit' => (int)$limit,
                'total' => $result['data_page']['total']
            );
        }

        http_response($this->data);
    }

    /**
     * 返回错误响应信息
     * @param $code
     * @param $status
     */
    private function response_error($code = 0, $status = 0)
    {
        $this->data['status'] = $status;
        if ($status == 0) {
            $this->data['errorCode'] = $code;
        }
        $this->data['errorMess'] = $this->error_info[$code];
        http_response($this->data);
    }

    public function fba_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('stock/FbaConditionExportService');
            $this->fbaconditionexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbaconditionexportservice->export('csv',$post['time_type']);
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }

    public function oversea_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('stock/OverseaConditionExportService');
            $this->overseaconditionexportservice->setTemplate($post);
            $this->data['filepath'] = $this->overseaconditionexportservice->export('csv',$post['time_type']);
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }
}