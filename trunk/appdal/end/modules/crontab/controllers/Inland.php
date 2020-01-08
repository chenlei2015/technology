<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *  FBA备货关系配置表
 */
class Inland extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->stock = $this->load->database('stock', TRUE);//计划系统数据库
        $this->_debug = $this->input->post_get('debug') ?? '';
    }

    private function show_log($str){
        if(!empty($this->_debug)){
            echo $str;
        }
    }

    private function getWareHouse($skuArr = [])
    {

        $list = [];
        if (!empty($skuArr)) {
            foreach ($skuArr as $sku) {
                $list[] = [
                    'sku' => $sku,
                    'serviceTypeId' => 1
                ];
            }
        } else {
            return [];
        }

        $result = TMS_RPC_CALL('YB_LOGISTICS_01', ['list' => $list]);

        if (!empty($result['code']) && $result['code'] > 0) {
            log_message('ERROR', sprintf('请求仓库地址：logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo，批量获取sku仓库异常：%s', $result['msg']));
            return [];
        }
        return !empty($result['data']) ? $result['data'] : [];
    }

    /**
     * 获取sku并插入备货配置表
     * 需要传递$time
     * http://192.168.71.170:1084/crontab/oversea/update_sku_cfg_warehouse_id
     */
    public function update_sku_cfg_warehouse_id()
    {
        ini_set('memory_limit', '2048M');
        ini_set('max_execution_time', '3600');
        set_time_limit(3600);

        $pageCount = $this->input->post_get('page_count') ?? 4;
        $page = $this->input->post_get('page') ?? 1;
        $sku = $this->input->post_get('sku') ?? '';



        if (empty($page) || $page < 0) {
            $page = 1;
        }

        $starttime = explode(' ', microtime());

        $this->load->model('Inland_sku_cfg_model', 'sku_cfg', false, 'inland');
        $table_name = $this->sku_cfg->tableName();
        //获取sku

        $warehouseCodeArr = ['SZ_AA' => PURCHASE_WAREHOUSE_INLAND_DG, 'CX' => PURCHASE_WAREHOUSE_CX];

        $_where="";
        if (!empty($sku)){
            $sku = explode(",",$sku);
            $_where = "where sku in ('" . implode("','", $sku) . "')";
        }

        $db = clone $this->stock;
        $sql = "SELECT count(1) as c from (select sku from {$table_name} $_where GROUP BY sku,purchase_warehouse_id) t";
        $result = $db->query($sql)->row_array();
        $total = $result['c'] ?? 0;//超过24万条数据

        $chunk_size = 350;//每次拉取多少个
        /*$limit = 25000;
        $pageCount = ceil($total / $limit);*/

        //$limit = ceil($total / $pageCount);;


        $handle_count = 0;
        $fali_batch = [];
        $fail_count = 0;
        $no_change_count_all = 0;

        if ($page > $pageCount) {
            exit("没有更多数据了");
        }

        //$_skuArr2 = ['00EDS20500GB','00EDS20500IT','03EJM02700US','00EDS12807AU','00EDS12807HW','00EDS12807US'];

        //for ($page = 1; $page <= $pageCount; $page++) {
        //$offset = ($page - 1) * $limit;

        if (empty($sku)){
            $limit = ceil($total / $pageCount);
            $offset = ($page - 1) * $limit;
            $limit = "LIMIT  $offset,$limit";
        }else{
            $limit = "";
        }

        $sql = "SELECT * FROM
                (
                    SELECT sku,purchase_warehouse_id,created_at 
                    from {$table_name}
                    $_where
                    GROUP BY sku,purchase_warehouse_id
                ) as t 
                ORDER BY t.created_at desc 
                $limit";

        $list = $this->stock->query($sql)->result_array();


        if (!empty($list)) {
            $skuArr = [];
            $listSku = [];
            foreach ($list as $row) {
                $skuArr[] = $row['sku'];

                $listSku[$row['sku']][] = $row;
            }
            $skuArr = array_unique($skuArr);

            $skuArr_chunk = array_chunk($skuArr, $chunk_size);

            $this->show_log( '$listSku:' . count($listSku) . "\r\n<br>");

            foreach ($skuArr_chunk as $_i => $_skuArr) {
                $dataList = $this->getWareHouse($_skuArr);
                $matchSku = [];
                $collspac_batch_params = [];

                $handle_count_1 = 0;
                $handle_count_2 = 0;
                $no_change_count = 0;

                $this->show_log( '$_skuArr:' . count($_skuArr) . ',chunk:' . $_i . ',dataList:' . count($dataList) . "\r\n<br>");
                /*var_dump(json_encode($dataList));
                echo '</pre>';
                continue;*/

                /*if (in_array('00EDS20500GB',$_skuArr)){
                    echo 'has:00EDS20500GB-------------<br>';
                    var_dump(json_encode($_skuArr));
                    var_dump(json_encode($dataList));
                    echo '-------------<br>';
                }*/

                if (!empty($dataList)) {
                    foreach ($dataList as $wareHouse) {//每个sku
                        $_sku = $wareHouse['sku'];
                        $_warehouseInfoList = $wareHouse['warehouseInfoList'];
                        /*echo $_sku.'<br>';
                        continue;*/
                        /*if (in_array($_sku,$_skuArr2)){
                            var_dump($wareHouse);
                            echo '<br>';
                        }*/


                        //只修改1.仓库有修改的，2.仓库列表为空，对比默认值，不同才修改，减少没必要的更新
                        if (!empty($_warehouseInfoList) && count($_warehouseInfoList) > 0) {
                            //查找中转仓
                            foreach ($_warehouseInfoList as $ware) {
                                $_warehouseCode = $ware['warehouseCode'];
                                if (isset($warehouseCodeArr[$_warehouseCode])) {
                                    if (isset($listSku[$_sku])) {
                                        foreach ($listSku[$_sku] as $skuItem) {
                                            //判断是否有匹配要修改的,有则这个sku全改为统一的
                                            if ($skuItem['purchase_warehouse_id'] != $warehouseCodeArr[$_warehouseCode]) {
                                                $handle_count_1++;
                                                $matchSku[] = $_sku;
                                                /*$_skuKey = array_search($_sku,$_skuArr);

                                                if ($_skuKey!==false && isset($_skuArr[$_skuKey])){
                                                    unset($_skuArr[$_skuKey]);
                                                }*/

                                                $collspac_batch_params[] = ['sku' => $_sku, 'purchase_warehouse_id' => $warehouseCodeArr[$_warehouseCode]];
                                                $this->show_log( 'sku:' . $_sku . ' 匹配变更采购仓:' . $_warehouseCode . "\r\n<br>");
                                                break;
                                            }
                                        }
                                    }

                                }
                            }
                        }
                    }
                }

                //计算还有多少sku是没有匹配到的,取差值
                $diff = array_diff($_skuArr, $matchSku);

                //echo '==>$_skuArr:'.count($_skuArr).'-$matchSku:'.count($matchSku).'='.count($diff)."<br>";

                if (!empty($diff)) {
                    foreach ($diff as $sku) {
                        //如果不是默认值,则修改回默认值
                        $hasMatch = false;
                        foreach ($listSku[$sku] as $skuItem) {
                            if ($skuItem['purchase_warehouse_id'] != PURCHASE_WAREHOUSE_INLAND_DG) {
                                $handle_count_2++;
                                $collspac_batch_params[] = ['sku' => $sku, 'purchase_warehouse_id' => PURCHASE_WAREHOUSE_INLAND_DG];
                                $hasMatch = true;
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

                $handle_count += $handle_count_1 + $handle_count_2;
                $no_change_count_all+=$no_change_count;

                $this->show_log( '==>$_skuArr:' . count($_skuArr) . '-$matchSku:' . count($matchSku) . '=' . count($diff) . ",handle_count_1:$handle_count_1,handle_count_2:$handle_count_2 ----->no_change_count:$no_change_count". "\r\n<br>");

                if (!empty($collspac_batch_params)) {
                    $res = $this->stock->update_batch($table_name, $collspac_batch_params, 'sku');
                    if (!$res) {
                        //批量不成功，则保存起来
                        $fali_batch = array_merge($fali_batch, $collspac_batch_params);
                    }
                }
            }

            if (!empty($fali_batch)) {
                $suc_count = 0;
                foreach ($fali_batch as $item) {
                    //循环多次，确保都能成功
                    for ($i = 1; $i <= 10; $i++) {
                        $res = $this->stock->update($table_name, ['purchase_warehouse_id' => $item['purchase_warehouse_id']], ['sku' => $item['sku']]);
                        if ($res !== FALSE) {
                            $suc_count++;
                            break;
                        }
                        sleep(rand(1, 3));
                    }
                }
                $fail_count = count($fali_batch) - $suc_count;
            }
        }
        //}
        $endtime = explode(' ', microtime());
        $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
        $thistime = round($thistime, 3);

        $this->show_log( "inland=>操作完成耗时：" . $thistime . " 秒,共需处理：$handle_count 条记录，失败：$fail_count 条,无需处理:$no_change_count_all 条,共：$pageCount 页,第;$page 页");
        exit;
    }

    function test()
    {
        $skus = $this->input->post_get('sku') ?? '';
        if (empty($skus)) {
            exit('sku参数不能为空');
        }

        $_skuArr = explode(',', $skus);

        //$_skuArr = ['00EDS20500GB','00EDS20500IT','03EJM02700US','00EDS12807AU','00EDS12807HW','00EDS12807US'];
        //$_skuArr = ["DE-JYA01876-02","US-10ZH-DZG05-1","FR-JM16457","US-JYA02128","AU-JM12502","GB-GS01320","GB-QC09558","US-TJOT38800-50","US-XDOT0909","DE-MXOT1400","US-FSMZ3108","DE-QC05010","US-GS06836","AU-JYA03024","AU-QCOT1219-10","GB-GY01648","GB-JM11858-01","YOTOWN2018113822","DE-TJ05770-02","US-JM02998","AU-YQ00934","GB-JY06910-03","US-JYOT18300","DE-JM05015-02","GB-TJOT32505","DE-YSB00226-02","US-JMHZ6700","12EDS82009GB","CA-TJA02878-02","FR-XD01340","GB-JYA03309-04","US-QC08708","DE-JY05826","IT-TJOT48992","ES-YQ00903-01","US-JY07227","AU-GS02662-04","DE-GS02635-03","GB-BG210-2","GB-OT91300","US-TJ05208-02","DE-JYA03479-02","US-BB01612-03","FR-JY08960","US-JYA03025","AU-JY00742","AU-JYJJ9900-200","GB-GS02927","GB-QC13252","US-WJ05300","US-YQ01332","DE-QC00775","US-GS01311","DE-QC07476","US-GS54300","AU-JYGJ1400","AU-TJ00220","GB-JM01850","GB-JM14515-01","US-JYA03309-04","DE-GS07026-02","GB-QCQP01000-48","DE-TJA00811-05","US-JM04889-02","00EDS20500GB"];
        //$_skuArr = ["DE-JYA01876-02","US-10ZH-DZG05-1","FR-JM16457","US-JYA02128","AU-JM12502","GB-GS01320","GB-QC09558","US-TJOT38800-50","US-XDOT0909","DE-MXOT1400","US-FSMZ3108","DE-QC05010","US-GS06836","AU-JYA03024","AU-QCOT1219-10","GB-GY01648","GB-JM11858-01","YOTOWN2018113822","DE-TJ05770-02","US-JM02998","AU-YQ00934","GB-JY06910-03","US-JYOT18300","DE-JM05015-02","GB-TJOT32505","DE-YSB00226-02","US-JMHZ6700","12EDS82009GB","CA-TJA02878-02","FR-XD01340","GB-JYA03309-04","US-QC08708","DE-JY05826","IT-TJOT48992","ES-YQ00903-01","US-JY07227","AU-GS02662-04","DE-GS02635-03","GB-BG210-2","GB-OT91300","US-TJ05208-02","DE-JYA03479-02","US-BB01612-03","FR-JY08960","US-JYA03025","AU-JY00742","AU-JYJJ9900-200","GB-GS02927","GB-QC13252","US-WJ05300","US-YQ01332","DE-QC00775","US-GS01311","DE-QC07476","US-GS54300","AU-JYGJ1400","AU-TJ00220","GB-JM01850","GB-JM14515-01","US-JYA03309-04","DE-GS07026-02","GB-QCQP01000-48","DE-TJA00811-05","US-JM04889-02","00EDS20500GB","AU-ZM01417","FR-JY10817-02","GB-JY08508-01","US-OT62000","DE-JM08355","GB-TJOT68100","DE-ZP11943","US-JY00851","AU-01ZH-DQF01","DE-04ZH-DRH04MM","FR-ZM00013","GB-JYB00649-01","US-QC10228","DE-JY07036","SOCIALME2018110752","FR-BBA00278-02","US-JY08944-19","AU-GS06302","DE-GS05839","GB-BG511-A","GB-QC01723","US-TJ06141-02","DE-JYB00622","US-BBB00209-03","AU-JY03709","AU-OT89600","GB-GS05205","US-XD02002","US-YXA00180-02","DE-QC02859-08","US-GS04015","DE-QC08716","US-GSOT3100-16","AU-JYJJ31200-7","AU-TJ06025","GB-JM07778","GB-JY00258","US-JYA03884-01","DE-GS52600","GB-TJ00312","DE-TJHW9605-SY","US-JM06021-01","04EOT88553GB","CA-GY00020","FR-JYA02608","GB-JY10207","US-QC01333","DE-JM13159","GB-XD05144","ES-GS02633-05","US-JY02264","AU-11ZH-DMP02","DE-AF00415","GB-04ZH-DUV01","GB-JYJJ132000","US-QC12852","DE-JY08944-07","SOCIALME2018111801","FR-GS01227","US-JY11068-02","AU-GS97800","GB-CW00983","GB-QC04328-01","US-TJ10687-01","DE-JYJJ15400-500","US-CW01295","AU-JY06002","AU-QC01947","GB-GS07190","US-ZPZS48708-12","DE-QC10817","US-GY02173","AU-TJHN0323","GB-JY02266","US-JYB01731-04","DE-GSGJ7508","GB-TJ05574","DE-TJOT46205","US-JM07778","07EOT45019-20US","CA-JM04045-01","FR-JYOT17300-200","GB-JY12797","US-QC04101","DE-JMOT7800","GB-YB00082","ES-JM11397","US-JY03917","AU-CWA00461-03","DE-CW02121","GB-09ZH-DXV04-4","GB-JYJJ2700-4","US-QCQP0900-27","DE-JY10916","SOCIALME2018112348","FR-GS08401","US-JY14394-02","AU-GYBJ1400-12","GB-CWOT2905-48","GB-QC06436","US-TJA02177-06","DE-JYJJ49001","US-CWOT1000-24","AU-JY09451-03","AU-QC04904","GB-GS95400","YOTOWN2018111907","DE-QCQP4201","US-JM01363-04","AU-WJ04600","GB-JY04684-03","US-JYJJ131401","DE-GY00791","GB-TJ06888-02","DE-XD02609-01","US-JM11308-02","09EXJ45204US","CA-TJ00310-03","FR-QC08650-04","GB-JYA00990","US-QC05625","DE-JY02672","GB-YSOT22600","ES-JYA03216-02","US-JY05310-02","AU-DS03900-04","DE-DS94400","GB-BG048","GB-JYJJ5908","US-SJ02541-01","DE-JY15268","SOCIALME201932713","FR-JM02257-01","US-JYA01133","AU-JM04956","GB-DSOT5401","GB-QC07971","US-TJHN0108","DE-JYOT14800-7","US-DS97500","AU-JYA01125-01","AU-QC07933","GB-GSOT12300-4","YOTOWN2018112701","DE-TJ03916","US-JM02102-03","AU-XD07163-01","GB-JY05796-02","US-JYJJ49007","DE-JM01812-02","GB-TJA02478","DE-XDOT40200","US-JM13912","11EOT72300AU","CA-TJ06908-05","FR-TJ00442","GB-JYA02248","US-QC07424-03","DE-JY04882-02","GB-ZP13145","ES-QC04160","US-JY06144","AU-GS00058","DE-GS00086","GB-BG130","GB-JYOT17400-183","US-TJ03433-04","DE-JYA02075","US-11ZH-DED02","FR-JMOT7500","US-JYA02273-02","AU-JM13015-02","GB-GS01503","GB-QC09731","US-TJOT44205","US-XDOT12307","DE-MXOT6100","US-FSXW2800-60","DE-QC05749","US-GS07024","AU-JYA03215-01","AU-QCOT9300","GB-GY03275","GB-JM12200-02","YOTOWN2018113838","DE-TJ05917-03","US-JM03076-MG","AU-YS01212","GB-JY06976-01","US-JYOT55100A","DE-JM05194-02","GB-TJOT32802","DE-YSOT1900","US-JMOT20800","12EDS82906GB","CA-TJB00004-02","FR-XD04396-03","GB-JYA03479-01","US-QC08813","DE-JY05985","JMHZ2300-3","FR-03EGS51400","US-JY07306-02","AU-GS02882","DE-GS02658","GB-BG221","GB-OT98800","US-TJ05295","DE-JYA03572-02","US-BB01887","FR-JY09532","US-JYA03179","AU-JY00996","AU-JYOT11200-4","GB-GS03251","GB-QC31200","US-WJOT2904-10","US-YS00728","DE-QC00913-05","US-GS01363X2","DE-QC07726","US-GS95000","AU-JYJJ12200","AU-TJ03584-01","GB-JM02322-03","GB-JM14725","US-JYA03350-MG","DE-GS07269","GB-QCQP13800","DE-TJA01563-01","US-JM04993-01","01EDS85308US","AU-ZP08722","FR-JY11214","GB-JY08626","US-OT89200","DE-JM09099-01","GB-WJ04600","DE-ZPZS48708-12","US-JY00912","AU-04ZH-DUI01","DE-05ZH-DRY05-300","FR-ZM00109","GB-JYB01228","US-QC10278","DE-JY07262","SOCIALME2018110803","FR-BBB00125","US-JY08991","AU-GS07019","DE-GS05958-02","GB-BG561","GB-QC01914","US-TJ06309-02","DE-JYB01193","US-BBBB16408","AU-JY04037","AU-QC00426","GB-GS05520","US-XD02807","US-ZM00091","DE-QC03290","US-GS04261-04","DE-QC08919","US-GSOT50900","AU-JYJJ4100","AU-TJ06572","GB-JM08125","GB-JY00416-03","US-JYA03936-02","DE-GS90400","GB-TJ03604","DE-TJOT11800-OG","US-JM06126","05EOT38400DE","CA-GY00645-05","FR-JYA03486-01","GB-JY10574","US-QC01396","DE-JM13612","GB-XD05543","ES-GS05601-01","US-JY02378-03","AU-AF00196","DE-BB01886-01"];
        $dataList = $this->getWareHouse($_skuArr);

        echo '<pre>';
        var_dump(($dataList));
        echo '</pre>';
    }


    /**
     * 更新是否精品字段
     */
    public function update_is_boutique()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        $starttime = explode(' ', microtime());
        $this->load->helper('crontab');
        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
        //查询上个步骤是否执行完成
//        check_script_state(FBA_LOGISTICS_CFG_LIST);

        $skus = $this->stock->select('sku')->from('yibai_inland_sku_cfg')->group_by('sku')->get()->result_array();

        if (empty($skus)) {
            log_message('error', sprintf('接口:crontab/FBA/update_is_boutique执行失败,查询yibai_fba_sku_cfg_main表sku为空,'));
        }
        //优先使用备份表,不存在则使用实时表
        $yibai_product = 'yibai_product_' . date('d');
        if (!$this->product->table_exists($yibai_product)) {
            $yibai_product = 'yibai_product';
        }
        $sql = "select sku, is_boutique from $yibai_product WHERE is_boutique =1";

        $result = $this->product->query($sql)->result_array();
        $result = array_column($result, NULL, 'sku');

        foreach ($skus as $key => &$item) {
            if (isset($result[$item['sku']])) {
                $item['quality_goods'] = INLAND_QUALITY_GOODS_TRUE;
            } else {
                $item['quality_goods'] = INLAND_QUALITY_GOODS_FALSE;
            }
        }

        $this->stock->trans_start();
        $count = $this->stock->update_batch('yibai_inland_sku_cfg', $skus, 'sku');
        $this->stock->trans_complete();
        if ($this->stock->trans_status() === FALSE) {
            return FALSE;
        } else {
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('执行成功,耗时:%s,更新记录:%s', $thistime, $count);
            echo $message;
            //执行结束,记录状态
//            $this->load->helper('crontab');
//            $params = FBA_PLAN_SCRIPT[FBA_IS_BOUTIQUE];
//            $params['step'] = FBA_IS_BOUTIQUE;
//            $params['message'] = $message;
//            record_script_state($params);

        }
    }
}

