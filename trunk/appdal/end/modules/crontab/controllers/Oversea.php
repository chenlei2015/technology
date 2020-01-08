<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  海外
 */
class Oversea extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        //$this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
        $this->stock = $this->load->database('stock', TRUE);//计划系统数据库
        //$this->station = ['au', 'ca', 'de', 'fr', 'it', 'jp', 'mx', 'es', 'uk', 'us', 'eu'];//FBA的所有站点
        $this->count = 0;
        $this->tag1  = 0;
        //define('FILTER_SKU', '/(.)+\*\d*$/');
        $this->_debug = $this->input->post_get('debug') ?? '';
    }

    private function show_log($str){
        if(!empty($this->_debug)){
            echo $str;
        }
    }

    private function getWareHouse($paramsList = [])
    {
//        $paramsList = json_encode($paramsList);
//        file_put_contents('入参.txt', json_encode(['list' => $paramsList]));exit;

        $result = TMS_RPC_CALL('YB_LOGISTICS_01', ['list' => $paramsList]);

        if (!empty($result['code']) && $result['code'] > 0) {
            log_message('ERROR', sprintf('请求仓库地址：logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo，批量获取sku仓库异常：%s,入参:%s', $result['msg'],json_encode(['list' => $paramsList])));
            return [];
        }
//        $fs = fopen('./info.txt','a+');
//        fwrite($fs,print_r($result,true));

        return !empty($result['data']) ? $result['data'] : [];
    }

    /**
     * 中转仓规则
     *
     * http://192.168.71.170:1084/crontab/oversea/update_sku_cfg_warehouse_id
     */
    public function update_sku_cfg_warehouse_id()
    {

        try {
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $starttime = explode(' ', microtime());
            $this->load->helper('crontab');
//        check_script_state(OVERSEA_IS_REFUND_TAX);
            $pageCount = $this->input->post_get('page_count') ?? 4;
            $page      = $this->input->post_get('page') ?? 1;
            $sku       = $this->input->post_get('sku') ?? '';

            $logistics_id_to_transport_type = LOGISTICS_ATTR_MAP;

            if (empty($page) || $page < 0) {
                $page = 1;
            }

            $this->load->model('Oversea_sku_cfg_main_model', 'cfg_main', false, 'oversea');
            $this->load->model('Oversea_logistics_list_model', 'logistics', false, 'oversea');
            //获取sku

            $warehouseCodeArr = WAREHOUSE_CODE;
//
//        $_where="";
//        if (!empty($sku)){
//            $sku = explode(",",$sku);
//            $_where = "where log.sku in ('" . implode("','", $sku) . "')";
//        }
//        $limit = '所有';

//
//        $db = clone $this->stock;
//        $sql = "SELECT COUNT(1) as c from
//                (
//                    SELECT log.sku, log.logistics_id
//                    FROM {$this->logistics->table} log
//                    LEFT JOIN {$this->cfg_main->table} main ON log.sku = main.sku AND log.station_code = main.station_code
//                    $_where
//                )t  ";//GROUP BY log.sku, log.logistics_id
//        $result = $db->query($sql)->row_array();
//        $total = $result['c'] ?? 0;

            $chunk_size = 350;//每次拉取多少个
            //$limit = 20000;

//        $pageCount = 4;//ceil($total / $limit);
//
//
//        if (empty($sku)){
//            $limit = ceil($total / $pageCount);
//            $offset = ($page - 1) * $limit;
//            $limit = "LIMIT  $offset,$limit";
//        }else{
//            $limit = "";
//        }
//
//
            $handle_count = 0;
//        $fali_batch = [];
//        $fail_count = 0;
            $no_change_count_all = 0;

//        if ($page > $pageCount) {
//            exit("没有更多数据了");
//        }

            //$_skuArr2 = ['00EDS20500GB','00EDS20500IT','03EJM02700US','00EDS12807AU','00EDS12807HW','00EDS12807US'];

            //for ($page = 1; $page <= $pageCount; $page++) {


            $sql = "SELECT * from
                (
                    SELECT log.sku,log.station_code, log.logistics_id, main.purchase_warehouse_id, log.created_at
                    FROM {$this->logistics->table} log
                    LEFT JOIN {$this->cfg_main->table} main ON log.sku = main.sku AND log.station_code = main.station_code
                )t ORDER BY t.created_at asc
                ";//GROUP BY log.sku, log.logistics_id

            $list = $this->stock->query($sql)->result_array();


            if (!empty($list)) {
                $skuArr     = [];
                $listSku    = [];
                $listSkuLog = [];
                foreach ($list as $row) {
//                if (preg_match("/([\x81-\xfe][\x40-\xfe])/", $row['sku'], $match)) {
//                    log_message('error',sprintf('带有中文的sku:%s',$row['sku']));
//                    $cn_sku[] =  $row['sku'];
//                    continue;
//                }
                    $skuArr[] = $row['sku'];

                    $listSku[$row['sku']][] = [
                        'sku'                   => $row['sku'],
                        'station_code'          => $row['station_code'],
                        'logistics_id'          => $row['logistics_id'],
                        'purchase_warehouse_id' => $row['purchase_warehouse_id'],
                    ];

                    $_key = $row['sku'] . '||' . $row['logistics_id'];
                    if (!isset($listSkuLog[$_key])) {
                        $listSkuLog[$_key] = $row;
                    }
                }
                $skuArr = array_unique($skuArr);

                $skuArr_chunk = array_chunk($skuArr, $chunk_size);

                $this->show_log('$listSku:' . count($listSku) . "\r\n<br>");

                foreach ($skuArr_chunk as $_i => $_skuArr) {
                    $paramsList      = [];
                    $paramsListCache = [];
                    foreach ($_skuArr as $_sku) {
                        if (!empty($listSku[$_sku])) {
                            foreach ($listSku[$_sku] as $item) {
                                $_key = $item['sku'] . '||' . $item['logistics_id'];
                                if (!isset($paramsListCache[$_key])) {
                                    $paramsListCache[$_key] = 1;
                                    if (isset($logistics_id_to_transport_type[$item['logistics_id']])) {
                                        $paramsList[] = [
                                            'sku'           => $_sku,
                                            'businessType'  => 2,
                                            'transportType' => $logistics_id_to_transport_type[$item['logistics_id']],
                                            'countryCode'   => STATION_COUNTRY_MAP[$item['station_code']]['code'],
                                        ];
                                    }
                                }
                            }

                        }
                    }
                    if (empty($paramsList)) {
                        continue;
                    }
                    //$this->vp($paramsList);
                    $dataList = $this->getWareHouse($paramsList);

                    //$this->vp($dataList);

                    $matchSku              = [];
                    $collspac_batch_params = [];
                    $handle_count_1        = 0;
                    $handle_count_2        = 0;
                    $no_change_count       = 0;

                    $this->show_log('$_skuArr:' . count($_skuArr) . ',chunk:' . $_i . ',dataList:' . count($dataList) . "\r\n<br>");
                    /*var_dump(json_encode($dataList));
                    echo '</pre>';
                    continue;*/

                    /*if (in_array('00EDS20500GB',$_skuArr)){
                        echo 'has:00EDS20500GB-------------<br>';
                        var_dump(json_encode($_skuArr));
                        var_dump(json_encode($dataList));
                        echo '-------------<br>';
                    }*/
//pr($dataList);exit;
                    if (!empty($dataList)) {

                        foreach ($dataList as $wareHouse) {//每个sku
                            $_sku           = $wareHouse['sku'];
                            $transportType  = $wareHouse['transportType']??'';
                            $_warehouseCode = $wareHouse['warehouseCode']??'';
                            /*echo $_sku.'<br>';
                            continue;*/
                            /*if (in_array($_sku,$_skuArr2)){
                                var_dump($wareHouse);
                                echo '<br>';
                            }

                            LOGISTICS_ATTR_SHIP => 14,
                LOGISTICS_ATTR_LAND => 12,
                LOGISTICS_ATTR_AIR => 8,
                LOGISTICS_ATTR_BLUE => 10,
                LOGISTICS_ATTR_RED => 10

                            */

                            //只修改1.仓库有修改的，2.仓库列表为空，对比默认值，不同才修改，减少没必要的更新
//                        if (!empty($_warehouseInfoList) && count($_warehouseInfoList) > 0) {
//                            //查找中转仓
//                            foreach ($_warehouseInfoList as $ware) {
//                                $_warehouseCode = $ware['warehouseCode'];
                            if (isset($warehouseCodeArr[$_warehouseCode])) {
                                $logistics_id_arr = [];
                                if ($transportType == 29) {
                                    $logistics_id_arr[] = LOGISTICS_ATTR_SHIPPING_FULL;
                                } elseif ($transportType == 28) {
                                    $logistics_id_arr[] = LOGISTICS_ATTR_SHIPPING_BULK;
                                } elseif ($transportType == 30) {
                                    $logistics_id_arr[] = LOGISTICS_ATTR_TRAINS_FULL;
                                } elseif ($transportType == 12) {
                                    $logistics_id_arr[] = LOGISTICS_ATTR_TRAINS_BULK;
                                } elseif ($transportType == 26) {
                                    $logistics_id_arr[] = LOGISTICS_ATTR_LAND;
                                } elseif ($transportType == 8) {
                                    $logistics_id_arr[] = LOGISTICS_ATTR_AIR;
                                } elseif ($transportType == 27) {
                                    $logistics_id_arr[] = LOGISTICS_ATTR_BLUE;
                                    $logistics_id_arr[] = LOGISTICS_ATTR_RED;
                                }


                                if (!empty($logistics_id_arr)) {
                                    foreach ($logistics_id_arr as $_lid) {
                                        $_key = $_sku . '||' . $_lid;

                                        foreach ($listSku[$_sku] as $_main_item) {
                                            //if (isset($listSkuLog[$_key])) {
                                            //$_main_item = $listSkuLog[$_key];

                                            //判断是否有匹配要修改的,有则这个sku全改为统一的
                                            if ($_main_item['logistics_id'] == $_lid) {

                                                if (!isset($matchSku[$_sku])) {
                                                    $matchSku[$_sku] = 1;
                                                }

                                                if ($_main_item['purchase_warehouse_id'] != $warehouseCodeArr[$_warehouseCode]) {
                                                    $handle_count_1++;
                                                    $collspac_batch_params[] = [
                                                        'purchase_warehouse_id' => $warehouseCodeArr[$_warehouseCode],
                                                        'where'                 => [
                                                            'sku'          => $_sku,
                                                            'station_code' => $_main_item['station_code'],
                                                        ]
                                                    ];
                                                    //$collspac_batch_params[] = ['sku' => $_sku, 'purchase_warehouse_id' => $warehouseCodeArr[$_warehouseCode]];
                                                    $this->show_log('sku:' . $_sku . ',station_code:' . $_main_item['station_code'] . ' 匹配变更采购仓:' . $_warehouseCode . "\r\n<br>");
                                                } else {
                                                    $no_change_count_all++;
                                                }

                                            }
                                            //}
                                        }
                                    }
                                }
                            }
                        }
//                        }else{
//                            //$no_change_count_all+=count($listSku[$_sku]);
//                        }
//                    }
                    }

                    //计算还有多少sku是没有匹配到的,取差值
                    $matchSku = array_keys($matchSku);
                    $diff     = array_diff($_skuArr, $matchSku);


                    if (!empty($diff)) {
                        foreach ($diff as $sku) {
                            //如果不是默认值,则修改回默认值
                            $hasMatch = false;
                            foreach ($listSku[$sku] as $skuItem) {
                                if ($skuItem['purchase_warehouse_id'] != PURCHASE_WAREHOUSE_EXCHANGE) {
                                    $handle_count_2++;
                                    $collspac_batch_params[] = [
                                        'purchase_warehouse_id' => PURCHASE_WAREHOUSE_EXCHANGE,
                                        'where'                 => [
                                            'sku' => $sku
                                        ]
                                    ];
                                    $hasMatch                = true;
                                    break;
                                }
                            }

                            if (!$hasMatch) {//没有匹配上的
                                /* echo '-------$sku:' . $sku . '---------<br>';
                                 var_dump($listSku[$sku]);
                                 echo '----------------<br>';*/
                                $no_change_count++;
                            }
                        }
                    }

//                if (!empty($cn_sku)){
//                    foreach ($cn_sku as $item){
//                        $collspac_batch_params[] = [
//                            'purchase_warehouse_id'=>PURCHASE_WAREHOUSE_EXCHANGE,
//                            'where'=>[
//                                'sku'=>$item
//                            ]
//                        ];
//                    }
//                }

                    $handle_count        += $handle_count_1 + $handle_count_2;
                    $no_change_count_all += $no_change_count;

                    $this->show_log('==>$_skuArr:' . count($_skuArr) . '-$matchSku:' . count($matchSku) . '=' . count($diff) . ",handle_count_1:$handle_count_1,handle_count_2:$handle_count_2 ----->no_change_count:$no_change_count" . "\r\n<br>");

                    $suc_count = 0;
                    if (!empty($collspac_batch_params)) {
                        foreach ($collspac_batch_params as $collspac) {
                            //循环多次，确保都能成功
                            for ($i = 1; $i <= 3; $i++) {
                                $res = $this->stock->update($this->cfg_main->table, ['purchase_warehouse_id' => $collspac['purchase_warehouse_id']], $collspac['where']);
                                if ($res !== FALSE) {
                                    $suc_count++;
                                    break;
                                }
                                sleep(1);
                            }
                        }
                    }
                    $fail_count = count($collspac_batch_params) - $suc_count;
                }
            }
            //}
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = "oversea=>操作完成耗时：" . $thistime . " 秒,,共需处理：$handle_count 条记录，失败：$fail_count 条,无需处理:$no_change_count_all 条,";
            echo $message;
            //执行结束,记录状态
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = OVERSEA_PLAN_SCRIPT[OVERSEA_UPDATE_WAREHOUSE];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = OVERSEA_UPDATE_WAREHOUSE;
            record_script_state($params);
            exit;
        }
    }

    function test()
    {
        $skus = $this->input->post_get('sku') ?? '';

        $logistics_id = $this->input->post_get('logistics_id') ?? '';

        if (empty($skus)) {
            exit('sku参数不能为空');
        }

        $_skuArr = explode(',', $skus);

        $data = [];

        foreach ($_skuArr as $sku){
            $data[] = ['sku'=>$sku,'serviceTypeId'=>2,'transportType'=>$logistics_id];
        }

        $dataList = $this->getWareHouse($data);
        echo '<pre>';
        var_dump(($dataList));
        echo '</pre>';
    }

    function vp($dataList){
        echo '<pre>';
        var_dump(($dataList));
        echo '</pre>';
    }

    /**
     * 回写延迟的汇总单
     */
    public function rewrite_unassign_sumsn()
    {
        try
        {
            $get = $this->compatible('get');
            $this->load->service('oversea/PrTrackService');
            $this->data['data'] = $this->prtrackservice->rewrite_unassign_sumsn($get['date'] ?? '');
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        exit;
    }
    
    /**
     * 重试审核平台时生成站点跟踪和汇总失败操作
     */
    public function retry_station_track_summary()
    {
        try
        {
            $this->load->service('oversea/PlatformService');
            $this->data['data'] = $this->platformservice->station_general_track_summary();
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        exit;
    }
    
    /**
     * 平台列表一天过期
     */
    public function platform_expired()
    {
        try
        {
            $params = $this->compatible('get');
            if (!isset($params['date']))
            {
                $params['date'] = '*';
            }
            $this->load->service('oversea/PlatformService');
            $this->data['data'] = $this->platformservice->expired($params);
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
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }
        exit;
    }
    
    /**
     * 删除没有跟踪单的汇总单
     */
    public function delete_disapear_track_summary()
    {
        try
        {
            $this->load->service('oversea/PrSummaryService');
            $this->data['data'] = $this->prsummaryservice->delete_disapear_track_summary();
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        exit;
    }


    /**
     * 调物流系统接口
     * 是否退税
     * http://dp.yibai-it.com:33344/web/#/73?page_id=3225
     */
    /*    public function getDrawback($paramsList = [])
        {

            $tran_drawback = ['1' => REFUND_TAX_YES, '0' => REFUND_TAX_NO];

            $update_data =[];
            $all_skus = array_column($paramsList,'sku');
    //              $paramsList = [[
    //                    'sku' => '00EDS12807AU',
    //                    'service_name'=>'海外仓',
    //                ]];
            $paramsList = json_encode($paramsList);
    //pr($paramsList);exit;
    //        $starttime= explode(' ', microtime());
            $url = LOGISTICS_URL.'/ordersys/services/LogisticsDrawbackManagement/checkDrawback';
            $result = getCurlData($url,$paramsList);//发起请求
    //        $endtime = explode(' ', microtime());
    //        $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
    //        $thistime = round($thistime, 3);
    //        echo($thistime);
            $data = json_decode($result,true);//返回的结果
    //pr($data);exit;
            if ($data['status'] != 1) {
                log_message('ERROR', sprintf('请求地址：/ordersys/services/LogisticsDrawbackManagement/checkDrawback，异常：%s', json_encode($data)));
            }
            if(isset($data['data_list']) && !empty($data['data_list'])){

                $skus = array_column($data['data_list'],'sku');
                $diff = array_diff($all_skus,$skus);

                foreach ($diff as $item){
                    $update_data[] = ['sku'=>$item,'is_refund_tax'=>3];
                }
                foreach ($data['data_list'] as $key => $value){
                    $update_data[] = ['sku'=>$value['sku'],'is_refund_tax'=>$tran_drawback[$value['is_drawback']]??3];
                }
            }else{
                foreach ($all_skus as $item) {
                    $update_data[] = ['sku' => $item, 'is_refund_tax' => 3];
                }
                log_message('ERROR', sprintf('请求地址：/ordersys/services/LogisticsDrawbackManagement/checkDrawback，异常：data_list为空%s',$paramsList));
            }

            return $update_data;
        }*/

    /**
     * 更新是否退税字段(调物流系统接口)
     * http://192.168.71.170:1084/crontab/Oversea/updateDrawback
     */
    /*    public function updateDrawback(){
            try {
                ini_set('memory_limit', '3072M');
                ini_set('max_execution_time', '0');
                set_time_limit(0);
                $starttime = explode(' ', microtime());
                $this->load->helper('crontab');
                //查询上个步骤是否执行完成,上一个是拉取物流属性配置表数据
                check_script_state(OVERSEA_IS_BOUTIQUE);
                // 组织数据
                $all_sku = $this->stock->select('sku')->group_by('sku')->from('yibai_oversea_sku_cfg_main')->get()->result_array();
                $data = [];
                foreach ($all_sku as $key => $value){
                    $data[] = [
                        'sku' => $value['sku'],
                        'service_name'=>'海外',
                    ];
                }
                //将数据进行分割
                $data = array_chunk($data,200);

                foreach ($data as $key => $value){
                    $insert_info = $this->getDrawback($value);
                        if(!empty($insert_info)){
                            $this->refresh_data($insert_info);
                        }
                    unset($data[$key]);
                }
                //程序运行时间
                $endtime = explode(' ', microtime());
                $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
                $thistime = round($thistime, 3);
                //执行结束,记录状态
                $params = OVERSEA_PLAN_SCRIPT[OVERSEA_IS_REFUND_TAX];
                $params['step'] = OVERSEA_IS_REFUND_TAX;
                $params['message'] = sprintf('执行成功,耗时:%s',$thistime);
                record_script_state($params);
                $code =200;
            } catch (\Throwable $e) {
                $code     = 500;
                $errorMsg = $e->getMessage();
            } finally {
                $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            }

        }*/

    /**
     * 数据库操作,批量更新是否退税字段
     * @param $data
     */
    private function refresh_data($data){
        $this->stock->update_batch('yibai_oversea_sku_cfg_main',$data,'sku');
    }


    /**
     * 08:30开始执行
     * 从common库里同步海外供货周期表
     */
    public function sync_lead_time(){
        $starttime = explode(' ', microtime());
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        $this->common = $this->load->database('common',true);
        $limit        = 500;//查询限制数量300
        $i            =0;
        $code         = 200;
        $result       = [];
        $data         = [];
        //先检查表是否存在需要同步的数据,如果不存在则停止操作
        $sql    = 'SELECT count(*) as total FROM yibai_oversea_lead_time WHERE sync_status = 2';
        $result = $this->common->query($sql)->row_array();
        if (empty($result['total'])) {
            log_message('error', '未找到需要同步的数据');
            exit;
        }
        while(true){
            if ($i > 3) {
                log_message('error', '清空表yibai_oversea_lead_time失败');
                exit;
            }
            if($this->stock->empty_table('yibai_oversea_lead_time')){
                break;
            }
            $i++;
        }

        while(true){
            //查询数据
            $result = $this->common->select('sku,lead_time,last_update_time')->from('yibai_oversea_lead_time')
                ->where('sync_status',2)//未同步
                ->limit($limit)->get()->result_array();
            if(empty($result)){
                break;
            }
            foreach ($result as $key => $item){
                $data[] = [
                    'sku' => $item['sku']??'',
                    'lead_time' => $item['lead_time']??'',
                    'last_update_time' => $item['last_update_time']??'',
                ];
            }
            $skus = array_column($result, 'sku');
            $this->insert_lead_time($data, $skus);


            $data = [];
            $result = [];
        }
        $endtime  = explode(' ', microtime());
        $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
        $thistime = round($thistime, 3);
        echo('同步成功,耗时:' . $thistime);
    }
    /**
     * 同步供货周期数据
     */
    public function insert_lead_time($data, $skus)
    {
        $this->common->trans_begin();
        $this->stock->trans_begin();
        $this->stock->insert_batch('yibai_oversea_lead_time',$data);//同步数据

        $this->common->where_in('sku', $skus);
        $this->common->update('yibai_oversea_lead_time',['sync_status'=>1]);

        if ($this->stock->trans_status() === FALSE || $this->common->trans_status() === FALSE) {
            $this->stock->trans_rollback();
            $this->common->trans_rollback();
        } else {
            $this->stock->trans_commit();
            $this->common->trans_commit();
        }
    }



    /**
     * 更新是否精品字段
     */
    public function update_is_boutique()
    {
        try {
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $starttime     = explode(' ', microtime());
            $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
            $this->load->helper('crontab');
            //查询上个步骤是否执行完成
//        check_script_state(OVERSEA_INCREMENTAL_DATA_SOURCE);
            //优先使用备份表,不存在则使用实时表
            $yibai_product = 'yibai_product_' . date('d');
            if (!$this->product->table_exists($yibai_product)) {
                $yibai_product = 'yibai_product';
            }
            $skus = $this->stock->select('sku')->from('yibai_oversea_sku_cfg_main')->group_by('sku')->get()->result_array();

            if (empty($skus)) {
                $message = sprintf('接口:crontab/Oversea/update_is_boutique执行失败,查询yibai_fba_sku_cfg_main表sku为空,');
                log_message('error', $message);
                throw new \Exception($message, 500);
            }
            $sql = "select sku, is_boutique from $yibai_product WHERE is_boutique =1";

            $result = $this->product->query($sql)->result_array();
            $result = array_column($result, null, 'sku');

            foreach ($skus as $key => &$item) {
                if (isset($result[$item['sku']])) {
                    $item['is_boutique'] = INLAND_QUALITY_GOODS_TRUE;
                } else {
                    $item['is_boutique'] = INLAND_QUALITY_GOODS_FALSE;
                }
            }

            $this->stock->trans_start();
            $skus = array_chunk($skus,2000);
            $count=0;
            foreach ($skus as $sku){
                $count += $this->stock->update_batch('yibai_oversea_sku_cfg_main', $sku, 'sku');
            }
            $this->stock->trans_complete();
            if ($this->stock->trans_status() === FALSE) {
                return FALSE;
            } else {
                //程序运行时间
                $endtime  = explode(' ', microtime());
                $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
                $thistime = round($thistime, 3);
                $message  = sprintf('执行成功,耗时:%s,更新记录:%s', $thistime, $count);
                echo $message;
                //执行结束,记录状态
            }
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = OVERSEA_PLAN_SCRIPT[OVERSEA_IS_BOUTIQUE];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = OVERSEA_IS_BOUTIQUE;
            record_script_state($params);
            exit;
        }

    }


    /**
     * java接口,是否退税
     * http://192.168.71.156/web/#/73?page_id=3649
     */
    private function getDrawback($paramsList = [])
    {
        if (empty($paramsList)) {
            return [];
        }
        $all_skus = array_column($paramsList, 'sku');
        $data     = [
            'sku' => implode(',', $all_skus),
        ];


        $result = RPC_CALL('YB_TMS_IS_DRAWBACK_01', $data);
//        pr($result);exit;

        if (empty($result) || !isset($result['code'])) {
            log_message('ERROR', '请求地址：/logistics/logisticsAttr/batchGetIsDrawback,无返回结果');

            return [];
        }

        if ($result['code'] == 1001 || $result['code'] == 1002) {
            log_message('ERROR', sprintf('请求地址：/logistics/logisticsAttr/batchGetIsDrawback,异常：%s', json_encode($result)));

            return [];
        }
//        pr($result);exit;
        if (isset($result['data']) && !empty($result['data'])) {
            return $result['data'];
        } else {
            log_message('ERROR', sprintf('请求地址：/logistics/logisticsAttr/batchGetIsDrawback,异常：%s', json_encode($result)));

            return [];
        }
    }

    /**
     * 更新是否退税字段(v1.1.1 调物流系统接口)
     * 接口文档:http://192.168.71.156/web/#/73?page_id=3225
     * http://192.168.71.170:1084/crontab/FBA/updateDrawback
     */
    public function updateDrawback()
    {
        try {
            $this->tag1 = 0;
            $starttime  = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $this->load->helper('crontab');
            //查询上个步骤是否执行完成
//            check_script_state(OVERSEA_IS_BOUTIQUE);
            $count = 0;
            // 组织数据
            $tran_drawback = ['1' => REFUND_TAX_YES, '0' => REFUND_TAX_NO];
            $update_data   = [];
            $log_info      = [];
            $all_sku       = $this->stock->select('is_refund_tax as isDrawback,sku,gid')->group_by('sku')->get('yibai_oversea_sku_cfg_main')->result_array();
            if (empty($all_sku)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,查询结果为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }

            //将数据进行分割
            $all_sku = array_chunk($all_sku, 200);
            foreach ($all_sku as $key => $value) {
                $result = $this->getDrawback($value);
                if (empty($result)) {
                    continue;
                }
                $result     = array_column($result, 'isDrawback', 'sku');
                $created_at = date('Y-m-d H:i:s');
                foreach ($value as $key => $item) {//遍历要匹配的sku
                    if (isset($result[$item['sku']])) {//只更新匹配到的,且是否退税不同的
                        $TMS_isDrawback      = $tran_drawback[$result[$item['sku']]]??REFUND_TAX_UNKNOWN;
                        $TMS_isDrawback_name = REFUND_TAX[$TMS_isDrawback]['name'];
                        if ($item['isDrawback'] != $TMS_isDrawback) {
                            $update_data[] = [
                                'sku'           => $item['sku'],
                                'is_refund_tax' => $TMS_isDrawback,
                            ];
                            $log_info[]    = [
                                'gid'        => $item['gid'],
                                'op_zh_name' => 'system',
                                'context'    => '修改是否退税为' . $TMS_isDrawback_name,
                                'created_at' => $created_at,
                            ];
                        }
                    }
                }
                unset($all_sku[$key]);
                $result = [];
            }
            unset($all_sku);
            if (empty($update_data)) {
                $message = sprintf('文件: %s 方法: %s 执行成功,无需更新', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 200);
            }

            $this->isDrawback_update($update_data, $log_info);
            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s', $thistime);
            echo $message;
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = OVERSEA_PLAN_SCRIPT[OVERSEA_IS_REFUND_TAX];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = OVERSEA_IS_REFUND_TAX;
            record_script_state($params);
            exit;

        }
    }

    public function isDrawback_update($batch_params, $log_info)
    {

        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {

            $this->stock->trans_start();
            $this->stock->update_batch('yibai_oversea_sku_cfg_main', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_oversea_stock_log', $log_info[$key]);            //记录日志
            $this->stock->trans_complete();
            if ($this->stock->trans_status() === false) {
                $error_info['update'][] = $item;
                $error_info['insert'][] = $log_info[$key];
            } else {
                unset($batch_params[$key]);
                unset($log_info[$key]);
            }
        }
        if (!empty($error_info)) {
            $this->isDrawback_update($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('同步FBA物流属性配置表产品状态,已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }
}

