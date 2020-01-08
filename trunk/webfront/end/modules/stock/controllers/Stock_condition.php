<?php 

/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/3/11
 * Time: 9:32
 */
class Stock_condition extends MY_Controller
{
    private $_server_module = 'stock/stock_condition/';
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * FBA库存状况列表
     * http://192.168.71.170:1083/stock/stock_condition/getFbaList
     */
    public function getFbaList()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if(empty($get['time_type']) || empty($get['time_data'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $get);

        if (!empty($get['isExcel'])) {
            if (empty($result['data_list']['value'])) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '数据为空,导出失败';
                http_response($this->data);
            }
            if (count($result['data_list']['value']) > MAX_EXCEL_LIMIT) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '导出数据必须小于' . MAX_EXCEL_LIMIT;
                http_response($this->data);
            }
            $date = date('YmdHis');
            $fp = export_head('FBA库存状况列表导出-' . $date, $result['data_list']['key']);
            $item = 1;
            $data = [];



            foreach ($result['data_list']['value'] as $key => $value) {
                $data[$key][] = $item;

                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sales_group_cn']);                //销售组
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['salesman']);                   //销售人员
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['account_name']);               //账号名称
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);                        //SKU
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['seller_sku']);                 //SellerSKU
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['fn_sku']);                     //FNSKU
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['asin']);                       //ASIN
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['product_name']);               //产品名称
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['station_code_cn']);                //FBA站点
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['shelf_time']);                 //上架时间
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['station_code_cn']);                  //sku状态

                if ($get['time_type']==1){//月
                    for ($i = 1;$i<=12;$i++){
                        $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['month_'.$i]);
                    }
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['month_sales']);//本月销量
                }elseif ($get['time_type']==2){//周
                    for ($i = 1;$i<=5;$i++){
                        $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['week_no_'.$i]);//第几周
                    }
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['month_sales']);//本月销量
                }elseif ($get['time_type']==3){//日
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_1']);//过去1天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_3']);//过去3天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_7']);//过去7天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_14']);//过去14天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_28']);//过去28天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['weighted_sales']);//加权销量
                }

                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['accumulative_sales']);//累计销量
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['accumulative_return']);//累计退货
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['rma']);//RMA
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['can_sale_stock']);//可售库存
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['cannot_sale_stock']);//'不可售库存';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['shipping_in_transit']);//'海运在途';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['iron_in_transit']);// '铁运在途';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['air_in_transit']);//'空运在途';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['blueorder_in_transit']);//'蓝单在途';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['redorder_in_transit']);//'红单在途';
                if ($get['time_type']==1) {
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['fba_onway_data']);//FBA在途数据
                }
                $item++;
            }
            export_content($fp, $data);
            exit();
        }


        http_response($this->rsp_package($result));
    }



    /**
     * 海外仓库存状况列表
     * http://192.168.71.170:1083/stock/stock_condition/getOverseaList
     */
    public function getOverseaList()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if(empty($get['time_type']) || empty($get['time_data'])){
            $this->response_error('3001');
        }
        $result = $this->_curl_request->cloud_post($api_name, $get);
        if (!empty($get['isExcel'])) {
            if (empty($result['data_list']['value'])) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '数据为空,导出失败';
                http_response($this->data);
            }
            if (count($result['data_list']['value']) > MAX_EXCEL_LIMIT) {
                $this->data['status'] = 0;
                $this->data['errorMess'] = '导出数据必须小于' . MAX_EXCEL_LIMIT;
                http_response($this->data);
            }
            $date = date('YmdHis');
            $fp = export_head('海外仓库存状况列表导出-' . $date, $result['data_list']['key']);
            $item = 1;
            $data = [];

            foreach ($result['data_list']['value'] as $key => $value) {
                $data[$key][] = $item;
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['product_name']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['station_code_cn']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['shelf_time']);
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku_state_cn']);

                if ($get['time_type']==1){//月
                    for ($i = 1;$i<=12;$i++){
                        $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['month_'.$i]);
                    }
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['month_sales']);//本月销量
                }elseif ($get['time_type']==2){//周
                    for ($i = 1;$i<=5;$i++){
                        $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['week_no_'.$i]);//第几周
                    }
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['month_sales']);//本月销量
                }elseif ($get['time_type']==3){//日
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_1']);//过去1天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_3']);//过去3天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_7']);//过去7天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_14']);//过去14天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['last_days_28']);//过去28天
                    $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['weighted_sales']);//加权销量
                }

                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['accumulative_sales']);//累计销量
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['accumulative_return']);//累计退货
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['rma']);//RMA
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['can_sale_stock']);//可售库存
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['cannot_sale_stock']);//'不可售库存';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['shipping_in_transit']);//'海运在途';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['iron_in_transit']);// '铁运在途';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['air_in_transit']);//'空运在途';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['blueorder_in_transit']);//'蓝单在途';
                $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['redorder_in_transit']);//'红单在途';

                $item++;
            }
            export_content($fp, $data);
            exit();
        }

        http_response($this->rsp_package($result));
    }

    public function fba_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name =  'FBA库存状况表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
    }

    public function oversea_export()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $file_name =  '海外库存状况表_'.date('Ymd_H_i');
        $options = ['charset' => 'GBK'];
        $this->common_export($api_name, $file_name, $options);
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

}