<?php

/**
 * 发运计划
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2018-12-20
 * @link
 */
class FbaPlanService
{
    public static $s_system_log_name = 'SHIPMENT';

    /**
     * __construct
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        return $this;
    }

    /**
     * 获取需求跟踪列表的日期
     */
    public function get_tracking_date()
    {
        $this->_ci->load->model('Fba_pr_track_list_model', 'm_pr_track', false, 'fba');
        $result        = $this->_ci->m_pr_track->tracking_date();//创建时间(时间戳)

        if (!empty($result)) {
            foreach ($result as $key => &$item) {
                $item['label'] = date('Y年m月d日', $item['date']);
                $arr = date_parse_from_format('Y年m月d日', $item['label']);
                $item['start'] = mktime(0, 0, 0, $arr['month'], $arr['day'], $arr['year']);
                $item['end'] = mktime(23, 59, 59, $arr['month'], $arr['day'], $arr['year']);
//                $item['end'] = mktime(23, 59, 59, date('m',$item['date']), date('d',$item['date']),date('Y',$item['date']));
//                $item['end'] =  strtotime(date('Y-m-d H:i:s',$item['date']));
            }
        }
        $result = array_column($result,NULL,'label');
         rsort($result);
        return $result;
    }


    public function planByTracking($params)
    {
        $date = $params['date']??'';

        if (empty($date)) {
            $this->data['status']    = 0;
            $this->data['errorMess'] = '请选择日期';
            http_response($this->data);
        }
        $this->_ci->load->model('Fba_pr_track_list_model', 'm_pr_track', false, 'fba');
        $this->_ci->load->model('Fba_pr_list_model', 'm_pr', false, 'fba');
        $this->_ci->load->model('Plan_purchase_track_list_model', 'm_purchase_track', false, 'plan');



        $arr           = date_parse_from_format('Y年m月d日', $date);

        $track_date = sprintf('%s-%s-%s',  $arr['year'],$arr['month'], $arr['day']);

        $db = $this->_ci->m_pr_track->getDatabase();
        $final_data = $db->select('*')->from('yibai_shipment_detail_fba_temp')->where('track_time',$track_date)->get()->result_array();

        //TODO
        if(empty($final_data)){//临时表无数据时尝试采用直接获取  出现该情况几率比较小 发生的原因提前保留数据失败  1.需求跟踪列表
            return $this->once_add_shipment($date);
        }

        $shipment_sn = $final_data[0]['shipment_sn'];

        $skus = array_column($final_data,'sku');

        //调获取可用库存接口
        $avail_qty = $this->get_available_inventory($skus);

        //查询最早缺货时间
        $pur_sns = array_column($final_data,'pur_sn');


        foreach ($final_data as $key => &$item)
        {
            //可调拨库存
            if($item['is_refund_tax'] == 1){//退税
                $item['available_inventory'] = $avail_qty[$item['sku']]['taxAvailQty']??0;
            }elseif ($item['is_refund_tax'] == 2){//不退税
                $item['available_inventory'] = $avail_qty[$item['sku']]['noTaxAvailQty']??0;
            }

            //默认发运数量


//            $item['pur_cost'];  //采购成本 调java接口
//            $item['available_inventory'] = ;      //可调拨库存  调java接口
                        //默认发运数量 拿可调拨库存 根据最早缺货时间按顺序 分配默认发运数量
            //earliest_exhaust_date  备货跟踪列表 最早缺货时间字段
        }
        //将最终的数据插入数据表
        $this->insert_shipment($final_data,$shipment_sn,$track_date);

    }

    public function once_add_shipment($params)
    {
        $date = $params['date']??'';

        if (empty($date)) {
            $this->data['status']    = 0;
            $this->data['errorMess'] = '请选择日期';
            http_response($this->data);
        }


        $date = explode(',',$date);
        if (count($date) != 3){
            $this->data['status']    = 0;
            $this->data['errorMess'] = '请选择日期';
            http_response($this->data);
        }
        if ($date[0]==1){
            $this->data['status']    = 0;
            $this->data['errorMess'] = '该日期已生成发运计划';
            http_response($this->data);
        }
        $date_info['start'] = $date[1];
        $date_info['end'] = $date[2];
        $track_date = date('Y-m-d',$date_info['start']);
        define('FBA_UNIQUE_SHIPMENT','FBA_UNIQUE_SHIPMENT'.$date_info['start']);
        //通过业务线+日期
        $this->_ci->load->library('rediss');

        if ($this->_ci->rediss->getData(FBA_UNIQUE_SHIPMENT)){
            $this->data['status']    = 0;
            $this->data['errorMess'] = '服务器繁忙,请稍后重试';
            http_response($this->data);
        }
        $this->_ci->rediss->setData(FBA_UNIQUE_SHIPMENT,1,3600);


        //查询记录数
        $this->_ci->load->model('Fba_pr_track_list_model', 'm_pr_track', false, 'fba');
        $this->_ci->load->model('Fba_pr_list_model', 'm_pr', false, 'fba');
        $this->_ci->load->model('Plan_purchase_track_list_model', 'm_purchase_track', false, 'plan');
        $this->_ci->load->model('Fba_list_model', 'm_detail', false, 'shipment');
        $this->_ci->load->model('Fba_shipment_list_model', 'm_shipment_plan', false, 'shipment');
        $this->_ci->load->model('Processed_date_model', 'm_processed_date', false, 'basic');
        $count = $this->_ci->m_pr_track->dataCount($date_info);
        $date_info['limit'] = 500;
        //分批查询
        if (empty($count)){
            $this->_ci->rediss->deleteData(FBA_UNIQUE_SHIPMENT);
            throw new \RuntimeException(sprintf('数据为空'), 500);
        }
        $pr_info = [];
        $data =[];
        $shipment_sn   = $this->general_shipment_sn('fba_shipment');//发运计划编号

        //处理发运计划详情
        while(true){
            //获取数据
            $pr_info = $this->_ci->m_pr_track->getData($date_info);
            if(empty($pr_info)){
                break;
            }
            //组装数据
            $data = $this->build_data($pr_info,$shipment_sn);
            //事务执行

            $this->insert_data($data);

            //资源释放
            $pr_info = [];
            $data = [];

        }

        //最后在发运计划列表新增一条发运计划 和 将该日期标记为已使用
        $shipment_sn = $this->add_plan($shipment_sn,$track_date);
        //自动推送至物流系统
        $this->auto_push_logistics($shipment_sn);
        $this->deleteData();
        return $shipment_sn;
    }
    public function deleteData()
    {
        $this->_ci->rediss->deleteData(FBA_UNIQUE_SHIPMENT);
    }
    public function add_plan($shipment_sn,$track_date)
    {
        //生成发运计划
        $plan_data = [
            'gid'           => gen_id($this->_ci->m_shipment_plan->tableId()),
            'shipment_sn'   => $shipment_sn,
            'pr_date'       => $track_date,
            'created_at'    => time(),
            'created_uid'   => get_active_user()->staff_code,
            'is_upload' => UN_UPLOAD,
        ];


        //记录已使用的日期
        $data = [
            'modules'=> SHIPMENT_PLAN_TRACKING_DATE,
            'business_line' => BUSINESS_LINE_FBA,
            'date' => $track_date,
            'sn' => $shipment_sn,
            'created_at'    => date('Y-m-d H:i:s'),
            'created_uid'   => get_active_user()->staff_code,
        ];
        $db = $this->_ci->m_detail->getDataBase();

        $db->trans_start();
        $this->_ci->m_shipment_plan->addPlan($plan_data);
        $this->_ci->m_processed_date->add($data);
        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            $this->deleteData();
            throw new \RuntimeException(sprintf('生成发运单失败'), 500);
        } else {
            return $shipment_sn;
        }
    }



    public function insert_data($data){
        $insert_data = $data['insert_data'];
        $gids = $data['gids'];


        $db = $this->_ci->m_detail->getDataBase();

        $db->trans_start();
        $this->_ci->m_detail->batch_insert($insert_data);//插入详情表
        $this->_ci->m_pr_track->update_shipment_status($gids);//更新状态
        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            return false;
        } else {
            return true;
        }

    }

    public function build_data($pr_info,$shipment_sn){
        $warehouse_map = array_flip(WAREHOUSE_CODE);


        $pr_sn_arr     = array_filter(array_column($pr_info, 'pr_sn'));//需求单号
        $pur_sn_arr    = array_filter(array_column($pr_info, 'pur_sn'));//备货单号

        foreach ($pr_info as $key => $item){
            $skus[]=[
                'sku'=>$item['sku'],
                'warehouseCode'=> $warehouse_map[$item['purchase_warehouse_id']]??'',
                'businessLine' => BUSINESS_LINE_FBA
            ];
        }

        //调获取采购单价接口
        $purchase_price = $this->get_purchase_price($skus);

        $pur_sns       = [];
        $logistics_ids = [];
        if (!empty($pr_sn_arr)) {
            $logistics_ids = $this->_ci->m_pr->getLogistics($pr_sn_arr);
        }
        if (!empty($pur_sn_arr)) {
            $pur_sns = $this->_ci->m_purchase_track->getPurSn($pur_sn_arr);
        }

        foreach ($pr_info as $key => $item) {

            $logistics_id = $logistics_ids[$item['pr_sn']]??'';
            $final_data[] = [
                'gid'                    => $item['gid'],
                'shipment_sn'            => $shipment_sn,//系统自动生成,发运计划编号
                'pr_sn'                  => $item['pr_sn'],
                'pur_sn'                 => $pur_sns[$item['pur_sn']]['po_sn']??'',                //采购单号  备货跟踪列表
                'sku'                    => $item['sku'],
                'fnsku'                  => $item['fnsku'],
                'seller_sku'             => $item['seller_sku'],
                'asin'                   => $item['asin'],
                'account_name'           => $item['account_name'],
                'salesman'               => $item['salesman'],
                'shipment_type'          => $pur_sns[$item['pur_sn']]['shipment_type']??'',
                'business_type'          => BUSINESS_TYPE_PLATFORM,//写死平台仓
                //物流类型  站点需求列表
                'logistics_id'           => $logistics_id,
                'station_code'           => $item['station_code'],
                'warehouse_id'           => $item['purchase_warehouse_id'],
                'country_of_destination' => STATION_COUNTRY_MAP[$item['station_code']]['name']??'',
                //采购成本
                'pr_qty'                 => $item['stocked_qty'],
                'pur_cost'               => round(bcmul($purchase_price[$item['sku']]['price']??0,$item['stocked_qty'],6),4),
                'is_refund_tax'          => $item['is_refund_tax'],//是否退税
                'order_source'           => 'FBA',
                //code
                'logistics_code'         => LOGISTICS_ATTR[$logistics_id]['code']??'',
                'warehouse_code'         => $warehouse_map[$item['purchase_warehouse_id']]??'',
                'country_code'           => STATION_COUNTRY_MAP[$item['station_code']]['code']??'',
            ];
        }


        //处理的记录
        $gids = array_column($final_data,'gid');
        $data['insert_data']= $final_data;
        $data['gids']= $gids;
        return $data;

    }









    protected function insert_shipment($final_data,$shipment_sn,$track_date)
    {
        //将最终的数据插入数据表
        $this->_ci->load->model('Fba_list_model', 'm_detail', false, 'shipment');
        $this->_ci->load->model('Fba_shipment_list_model', 'm_shipment_plan', false, 'shipment');
        $this->_ci->load->model('Processed_date_model', 'm_processed_date', false, 'basic');
        $db = $this->_ci->m_detail->getDataBase();
        $db->trans_start();
        $this->_ci->m_detail->batch_insert($final_data);
        //生成发运计划
        $plan_data = [
            'gid'           => gen_id($this->_ci->m_shipment_plan->tableId()),
            'shipment_sn'   => $shipment_sn,
            'pr_date'       => $track_date,
            'created_at'    => time(),
            'created_uid'   => get_active_user()->staff_code,
        ];
        $this->_ci->m_shipment_plan->addPlan($plan_data);

        //记录已使用的日期
        $data = [
            'modules'=> SHIPMENT_PLAN_TRACKING_DATE,
            'business_line' => BUSINESS_LINE_FBA,
            'date' => $track_date,
            'sn' => $shipment_sn,
            'created_at'    => time(),
            'created_uid'   => get_active_user()->staff_code,
        ];
        $this->_ci->m_processed_date->add($data);

        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            return FALSE;
        } else {
            return TRUE;
        }
    }

    /**
     * 生成发运计划编号
     *
     */
    protected function general_shipment_sn($scene)
    {
        $this->_ci->load->service('basic/OrderSnPoolService');
        return $this->_ci->ordersnpoolservice->setScene($scene)->pop();
    }

    public function upload($batch_params)
    {
        if(empty($batch_params)){
            return false;
        }

        $this->_ci->load->model('Fba_shipment_list_model', 'm_shipment_plan', false, 'shipment');
        $this->_ci->load->model('Fba_list_model', 'shipment_detail');

        //获取所有的sku
        foreach ($batch_params as $key => $item)
        {
            $all_sku[] = [
                'sku' => $item['sku'],
                'service_name' => 'FBA'
            ];
        }
        //调物流系统接口获取是否退税
//        $drawback_info = $this->getDrawback($all_sku);

        $shipment_sn = $this->general_shipment_sn('fba_shipment');//发运计划编号
        $warehouse_map = array_flip(WAREHOUSE_CODE);

        foreach ($batch_params as $key => $item){
            $skus[]=[
                'sku'=>$item['sku'],
                'warehouseCode'=> $warehouse_map[$item['warehouse_id']]??'',
                'businessLine'=>BUSINESS_LINE_FBA
            ];
        }

        //调获取可用库存接口
//        $avail_qty = $this->get_available_inventory($skus);
        $purchase_price = $this->get_purchase_price($skus);

        foreach ($batch_params as $key => &$item)
        {

            $item['gid'] = gen_id($this->_ci->m_shipment_plan->tableId());
            $item['shipment_sn'] = $shipment_sn;
            $item['pr_sn'] =  $this->general_shipment_sn('fba_pr');
            //采购单号  调接口
            $item['pur_sn'] = $purchase_price[$item['sku']]['purchaseNumber']??'';
            $item['country_of_destination'] = STATION_COUNTRY_MAP[$item['station_code']]['name']??'';
            $item['pur_cost'] = round(bcmul($purchase_price[$item['sku']]['price']??0,$item['pr_qty'],6),4);
            $item['order_source'] = 'FBA';
            $item['country_code'] = STATION_COUNTRY_MAP[$item['station_code']]['code']??'';
            $item['warehouse_code'] = $warehouse_map[$item['warehouse_id']]??'';
            $item['logistics_code'] = FBA_LOGISTICS_ATTR[$item['logistics_id']]['code']??'';

//            $item['is_refund_tax'] = $drawback_info[$item['sku']];
            //采购成本
            //可调拨库存
//            if($item['is_refund_tax'] == 1){//退税
//                $item['available_inventory'] = $avail_qty[$item['sku']]['taxAvailQty']??0;
//            }elseif ($item['is_refund_tax'] == 2){//不退税
//                $item['available_inventory'] = $avail_qty[$item['sku']]['noTaxAvailQty']??0;
//            }
            //默认发运数量
        }
        //将导入数据插入详情表,后新增发运计划


        $db = $this->_ci->shipment_detail->getDatabase();
        $db->trans_start();
        $this->_ci->shipment_detail->batch_insert($batch_params);

        //生成发运计划
        $plan_data = [
            'gid' => gen_id($this->_ci->m_shipment_plan->tableId()),
            'shipment_sn' => $shipment_sn,
            'pr_date' => date('Y-m-d'),
            'created_at' => time(),
            'created_uid' => get_active_user()->staff_code,
            'is_upload' => UPLOAD,
        ];
        $this->_ci->m_shipment_plan->addPlan($plan_data);

        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            throw new \RuntimeException(sprintf('生成发运单失败'), 500);
        } else {
            //自动推送至物流系统
            $this->auto_push_logistics($shipment_sn);
        }
        return $shipment_sn;
    }
    /**
     * 自动推送至物流系统
     */
    public function auto_push_logistics($shipment_sn)
    {
        $data['shipmentSns'] = [$shipment_sn];
        $data['businessLine'] = BUSINESS_LINE_FBA;
        //推送至物流系统
        if (!empty($shipment_sn)){
            $result = $this->pushToLogistics($data);
        }

        //组织数据
        if ($result === true){//推送成功  状态:待物流系统回传
            $params['data'] = [
//                'push_status' => SHIPMENT_WAITING_BACK,
                'push_time' => date('Y-m-d H:i:s'),
                'push_uid'=>get_active_user()->staff_code,
            ];
            $params['where'] = $shipment_sn;
            $this->update_push_status($params);
        }else{//推送失败  状态:推送至物流系统失败
            log_message('error',sprintf('%s发运计划已生成,推送至物流系统失败',$shipment_sn),500);
        }
    }
    /**
     * 发运计划推送到WMS
     * @param $params
     *
     * @return string
     */
    public function push($params)
    {
        $this->_ci->load->model('Fba_shipment_list_model', 'm_shipment_plan',false,'shipment');
        $shipment_sn = $params['shipment_sn'];//发运计划编号

        //where_in查询出这些发运单是状态3的记录
        $result = $this->_ci->m_shipment_plan->getStatus($shipment_sn,SHIPMENT_WAITING_PUSH);
        $shipment_sn = explode(',',$shipment_sn);

        foreach ($shipment_sn as $item){
            if (!in_array($item,$result)){
                throw new \RuntimeException(sprintf('操作失败,%s该发运单不存在或推送状态不是待推送',$item), 500);
            }
        }
        //调java接口
        $data['shipmentSns'] = $shipment_sn;
        $data['businessLine'] = BUSINESS_LINE_FBA;
        $data['pushUid'] = get_active_user()->staff_code;
        $result = $this->pushToWms($data);

        //组织数据
        if ($result !== true){
            throw new \RuntimeException(sprintf('推送失败,请重试'), 500);
        }
        $params['data'] = [
//            'push_status'=>SHIPMENT_PUSHED,
            'push_time'=>date('Y-m-d H:i:s'),
            'push_uid'=>get_active_user()->staff_code,
        ];
        $params['where'] = $shipment_sn;

        $this->update_push_status($params);


        return $data;
    }

    public function send($params){
        $this->_ci->load->model('Fba_shipment_list_model', 'm_shipment_plan',false,'shipment');
        $shipment_sn = $params['shipment_sn'];//发运计划编号

        //where_in查询出这些发运单是状态5的记录
        $result = $this->_ci->m_shipment_plan->getStatus($shipment_sn,SHIPMENT_SEND_FAIL);

        $shipment_sn = explode(',',$shipment_sn);
        foreach ($shipment_sn as $item){
            if (!in_array($item,$result)){
                throw new \RuntimeException(sprintf('操作失败,%s该发运单不存在或推送状态不是发送至物流系统失败',$item), 500);
            }
        }
        //调java接口
        $data['shipmentSns'] = $shipment_sn;
        $data['businessLine'] = BUSINESS_LINE_FBA;
        $result = $this->pushToLogistics($data);
        if ($result !== true){
            throw new \RuntimeException(sprintf('推送失败,请重试,错误信息:%s',$result['msg']??''), 500);
        }

        $params['data'] = [
//            'push_status' => SHIPMENT_WAITING_BACK,
            'push_time' => date('Y-m-d H:i:s'),
            'push_uid'=>get_active_user()->staff_code,
        ];
        $params['where'] = $shipment_sn;
        $this->update_push_status($params);
        return true;
    }

    public function update_pushed($params)
    {
        $db = $this->_ci->shipment_list->getDatabase();
        $db->trans_start();
        if (!empty($params['successList'])){
            $db->where_in('shipment_sn',$params['successList']);
            $data = [
                'push_status'=>SHIPMENT_PUSHED,
                'push_time'=>date('Y-m-d H:i:s'),
                'push_uid'=>get_active_user()->staff_code,
            ];
            $db->update($this->_ci->shipment_list->getTable(),$data);
        }
        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            return FALSE;
        } else {
            return $params['successList']??'';
        }
    }


    public function update_push_status($params)
    {
        if (empty($params['where'])){
            return false;
        }elseif (empty($params['data'])){
            $params['data'] = [
                'push_time' => date('Y-m-d H:i:s'),
                'push_uid'=>get_active_user()->staff_code,
            ];
        }
        $db = $this->_ci->m_shipment_plan->getDatabase();
        $db->trans_start();
        $db->where_in('shipment_sn',$params['where']);
        $db->update($this->_ci->m_shipment_plan->getTable(),$params['data']);
        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            return FALSE;
        } else {
            return $params['where']??'';
        }
    }

    /**
     * 调物流系统接口
     * 是否退税
     */
    private function getDrawback($paramsList = [])
    {
//        pr($paramsList);exit;
        $tran_drawback = [0 => 2, 1 => 1];
//              $paramsList = [[
//                    'sku' => 'US-QC23489',
//                    'service_name'=>'海外仓',
//                ],[
//                    'sku' => 'QC07097',
//                    'service_name'=>'FBA',
//                ]];

        $update_data =[];
        $paramsList = json_encode($paramsList);
        $url        = LOGISTICS_URL . '/ordersys/services/LogisticsDrawbackManagement/checkDrawback';
        $result     = getCurlData($url, $paramsList);//发起请求

        $data = json_decode($result, true);//返回的结果

        if ($data['status'] = 0) {
            log_message('ERROR', sprintf('请求地址：/ordersys/services/LogisticsDrawbackManagement/checkDrawback，异常：%s', $data['errorMess']));
        } elseif ($data['status'] = 1) {
            if (isset($data['data_list'])) {
                foreach ($data['data_list'] as $key => $value) {
                    if ($value['msg'] == 'ok') {
                        $update_data[$value['sku']] =  $tran_drawback[$value['is_drawback']]??0;
                    } else {
                        $update_data[$value['sku']] =2;
                    }
                }
            }
        }
        return $update_data;
    }

    /**
     * 获取可用库存接口
     * @param $skus
     *
     * @return array
     */
    public function get_available_inventory($skus)
    {
        $result = RPC_CALL('YB_AVAILABLE_STOCK_001',$skus);

        if ($result['code'] != 200) {
            log_message('ERROR', sprintf('请求获取可用库存地址：mrp/invertoryInfo/getAvailableStock，异常：%s', $result['msg']));
            return [];
        }
        $fba_line = [];
        if(isset($result['data']) && !empty($result['data'])){
            foreach ($result['data'] as $key => $item){
                if ($item['bussinessLine'] == 1){
                    $fba_line[] =  $item;
                }
            }
            return array_column($fba_line,null,'sku');
        }
        return [];
    }


    /**
     * 发运单推送到wms系统
     * http://192.168.71.156/web/#/87?page_id=3538
     */
    public function pushToWms($params)
    {

        $result = RPC_CALL('YB_WMS_PUSH_01',$params);
        if ($result['code'] != 200) {
            log_message('ERROR', sprintf('调取地址：mrp/shipmentList/pushShipmentToWms,异常：%s', $result['data']['message']??''));
            throw new \RuntimeException(sprintf('%s,错误信息:%s',$result['msg']??'',$result['data']['message']??''), 500);
        }
        return true;
    }

    /**
     * 发运单推送物流系统
     * http://192.168.71.156/web/#/87?page_id=3513
     */
    public function pushToLogistics($params)
    {

        $result = RPC_CALL('YB_LOGISTICS_PUSH_01',$params);
        if ($result['code'] != 200) {
            log_message('ERROR', sprintf('调取地址：mrp/shipmentList/pushToLogistics,异常：%s', $result['msg']));
            return $result;
        }
        return true;
    }


    /**
     * 获取采购单价
     * http://192.168.71.156/web/#/87?page_id=3478
     * @param $skus
     *
     * @return array
     */
    public function get_purchase_price($skus)
    {
        $result = RPC_CALL('YB_PURCHASE_PRICE_01',$skus);

        if ($result['code'] != 200) {
            log_message('ERROR', sprintf('请求获取可用库存地址：mrp/purchaseOrderItems/getPriceBySkus,异常：%s', $result['msg']));
            return [];
        }

        if(isset($result['data']) && !empty($result['data'])){
            foreach ($result['data'] as $key => $item){
                $result[] =  $item;
            }
            return array_column($result,null,'sku');
        }
        return [];
    }

    public function pre_data($params)
    {
        //前一天需求跟踪列表的数据(默认)
        $date['start'] = $params['start']??strtotime(date("Y-m-d", strtotime("-1 day")) . '00:00:00');
        $date['end'] = $params['end']??strtotime(date("Y-m-d", strtotime("-1 day")) . '23:59:59');

        $this->_ci->load->model('Fba_pr_track_list_model', 'm_pr_track', false, 'fba');
        $this->_ci->load->model('Fba_pr_list_model', 'm_pr', false, 'fba');
        $this->_ci->load->model('Plan_purchase_track_list_model', 'm_purchase_track', false, 'plan');

        //查询需求跟踪列表
        $pr_info = $this->_ci->m_pr_track->getData($date);//创建时间(时间戳)

        if(empty($pr_info)){
            log_message('ERROR', sprintf('生成发运计划详情失败,接口:shipment/Plan/fba_pre_data,失败原因:查询需求跟踪列表返回数据为空,开始时间%s结束时间%s',$date['start'],$date['end']));
        }

        $shipment_sn   = $this->general_shipment_sn('fba_shipment');//生成发运计划编号
        $pr_sn_arr     = array_filter(array_column($pr_info, 'pr_sn'));//需求单号
        $pur_sn_arr    = array_filter(array_column($pr_info, 'pur_sn'));//备货单号
        $pur_sns       = [];
        $logistics_ids = [];
        if (!empty($pr_sn_arr)) {
            $logistics_ids = $this->_ci->m_pr->getLogistics($pr_sn_arr);
        }

        if (!empty($pur_sn_arr)) {
            $pur_track_info = $this->_ci->m_purchase_track->getPurTrackInfo($pur_sn_arr);
        }

        $final_data = [];
        foreach ($pr_info as $key => $item) {
            $earliest_exhaust_date = $pur_track_info[$item['pur_sn']]['earliest_exhaust_date']??'';
            $earliest_exhaust_date = strtotime($earliest_exhaust_date);//date转成时间戳
            $final_data[] = [
                'gid'                    => gen_id(999),
                'shipment_sn'            => $shipment_sn,//系统自动生成,发运计划编号
                'pr_sn'                  => $item['pr_sn'],
                //采购单号  备货跟踪列表
                'pur_sn'                 => $pur_track_info[$item['pur_sn']]??'',
                'sku'                    => $item['sku'],
                'fnsku'                  => $item['fnsku'],
                'seller_sku'             => $item['seller_sku'],
                'asin'                   => $item['asin'],
                'account_name'           => $item['account_name'],
                'salesman'               => $item['salesman'],
                'business_line'          => 1,//写死fba
                //物流类型  站点需求列表
                'logistics_id'           => $logistics_ids[$item['pr_sn']]??'',
                'station_code'           => $item['station_code'],
//               'is_refund_tax'          => $item['is_refund_tax'],新版不获取
                'warehouse_id'           => $item['purchase_warehouse_id'],
                'country_of_destination' => STATION_COUNTRY_MAP[$item['station_code']]['name']??'-',
                //采购成本 调java接口
                'pr_qty'                 => $item['stocked_qty'],
                //可调拨库存  调java接口
                //默认发运数量 拿可调拨库存 根据最早缺货时间按顺序 分配默认发运数量
                'add_time'               => date('Y-m-d H:i:s', time()),
                'track_time'             => date('Y-m-d', $date['start']),//需求跟踪列表的创建时间
                //最早缺货时间
                'earliest_exhaust_date' => $earliest_exhaust_date,
            ];
        }
        $db    = $this->_ci->m_pr_track->getDatabase();
        $db->trans_start();
        if (!empty($final_data)) {
            $count = $db->insert_batch('yibai_shipment_detail_fba_temp', $final_data);
        }
        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            log_message('ERROR',sprintf('生成发运计划详情失败,接口:shipment/Plan/fba_pre_data,失败原因:trans_status=false'));
            return FALSE;
        } else {
            log_message('INFO',sprintf('生成发运计划详情成功,接口:shipment/Plan/fba_pre_data,开始时间%s结束时间%s,插入数量$count=%s',$date['start'],$date['end'],$count));
            return TRUE;
        }
    }
}
