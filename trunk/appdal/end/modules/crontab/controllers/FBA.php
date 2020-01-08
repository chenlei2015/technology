<?php

/**
 *  FBA备货关系配置表
 */
class FBA extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
        $this->stock   = $this->load->database('stock', TRUE);//计划系统数据库
        $this->load->helper('crontab');
        $this->count   = 0;
        $this->tag1    = 0;
        define('FILTER_SKU', '/(.)+\*\d*$/');
        $this->_debug = $this->input->post_get('debug') ?? '';
    }

    private function show_log($str)
    {
        if (!empty($this->_debug)) {
            echo $str;
        }
    }


    /**
     * 将seller_sku属性配置表的sku维度,插入到erpsku属性配置表中
     */

    public function erp_sku()
    {
        try {
            global $argv;
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $this->system = $this->load->database('yibai_system', TRUE);
            check_script_state(FBA_CHECK_PAN_EU);
            $this->tag1 = 0;
            $condition  = '';
            $rows       = $this->insert_sku($condition);
            $endtime    = explode(' ', microtime());
            $thistime   = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime   = round($thistime, 3);
            $message    = sprintf('执行完成,耗时:%s,ERPSKU属性配置表,本次插入%s条记录', $thistime, $rows);
            echo $message;
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[FBA_STOCK_CFG_LIST];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_STOCK_CFG_LIST;
            record_script_state($params);
            exit;
        }
    }

    public function insert_sku($condition)
    {
        $rows = 0;
        $sql  = "INSERT INTO yibai_fba_sku_cfg (sku,original_sku) SELECT sku,original_sku  from yibai_fba_logistics_list GROUP BY sku  HAVING sku not in (select sku from yibai_fba_sku_cfg);";
        $sql  = sprintf("INSERT INTO yibai_fba_sku_cfg (sku,original_sku) SELECT sku,original_sku  from yibai_fba_logistics_list %s GROUP BY sku  HAVING sku not in (select sku from yibai_fba_sku_cfg);", $condition);
        $this->stock->trans_start();
        $this->stock->query($sql);
        $rows = $this->stock->affected_rows();
        $this->stock->trans_complete();
        if ($this->stock->trans_status() === FALSE) {
            $this->tag1++;
            if ($this->tag1 >= 3) {
                $message = '已重试3次,数据库操作失败';
                log_message('error', $message);
                throw new \Exception($message, 500);

                return false;
            }
            sleep(3);
            $this->insert_sku();
        } else {
            return $rows;
        }
    }

    /**
     * 已废弃
     */
//    public function fba_stock()
//    {
//        global $argv;
//        $starttime = explode(' ', microtime());
//        ini_set('memory_limit', '3072M');
//        ini_set('max_execution_time', '0');
//        set_time_limit(0);
//        $this->system = $this->load->database('yibai_system', TRUE);
//
//        //查询数据的时间
//        $number    = $argv[4]??'';
//        $time_type = $argv[5]??'';
//        $all_data  = false;
//        if ($number == 'all_data') {
//            $all_data = true;
//        } elseif (!empty($time_type) && !empty($number)) {
//            $day        = sprintf('-%s %s', $number, $time_type);
//            $start_time = date("Y-m-d", strtotime($day));
//            $end_time   = date("Y-m-d");
//
//        } else {
//            $start_time = date("Y-m-d", strtotime("-1 day"));
//            $end_time   = date("Y-m-d");
//        }
//
//
//        $batch_params       = [];
//        $emp                = 0;
//        $all                = 0;
//        $sku_cache          = [];
//        $_empty_sku         = [];
//        $unique_sku_station = [];
//        $created_at         = date('Y-m-d H:i:s');
//        //英德意法西(erp上的西班牙为sp)  统一为欧洲
//        $pass_station_code = ['de', 'fr', 'it', 'sp', 'uk'];
//        //优先使用备份表,不存在则使用实时表
//        $yibai_product                        = 'yibai_product_' . date('d');
//        $yibai_product_combine                = 'yibai_product_combine_' . date('d');
//        $yibai_amazon_listing_alls            = 'yibai_amazon_listing_alls_' . date('d');
//        $yibai_amazon_sku_map                 = 'yibai_amazon_sku_map_' . date('d');
//        $yibai_amazon_fba_inventory_month_end = 'yibai_amazon_fba_inventory_month_end';
////        $yibai_product              = 'yibai_product_22';
////        $yibai_product_combine      = 'yibai_product_combine_22';
////        $yibai_amazon_listing_alls  = 'yibai_amazon_listing_alls_22';
////        $yibai_amazon_sku_map       = 'yibai_amazon_sku_map_22';
////        $yibai_amazon_fba_inventory = 'yibai_amazon_fba_inventory_22';
////        var_dump($this->product->table_exists($yibai_product_combine));
//        if (!$this->product->table_exists($yibai_product)) {
//            $yibai_product = 'yibai_product';
//        }
//        if (!$this->product->table_exists($yibai_product_combine)) {
//            $yibai_product_combine = 'yibai_product_combine';
//        }
//        if (!$this->product->table_exists($yibai_amazon_listing_alls)) {
//            $yibai_amazon_listing_alls = 'yibai_amazon_listing_alls';
//        }
//        if (!$this->product->table_exists($yibai_amazon_sku_map)) {
//            $yibai_amazon_sku_map = 'yibai_amazon_sku_map';
//        }
//
//
//        $sql    = "SELECT a.id,a.account_name,a.site as station_code ,a.group_id,g.group_name
//            from yibai_amazon_account as a
//            LEFT JOIN yibai_amazon_group as g on a.group_id = g.group_id
//            where a.account_name!='' and  a.account_name is not null;";
//        $result = $this->system->query($sql)->result_array();
//
//        $account_arr = array_column($result, NULL, 'id');
//
//        //先查出product_type=2需要转换的原sku 对应  真实的 sku
//        $sql = "SELECT t.sku as original_sku,p1.sku from $yibai_product as p1
//            INNER JOIN (
//                SELECT p.sku,pc.product_id from $yibai_product p
//                LEFT JOIN $yibai_product_combine pc on pc.product_combine_id=p.id
//                where p.product_type=2
//            ) t on t.product_id = p1.id;";
//
//        $result = $this->product->query($sql)->result_array();
//        foreach ($result as $key => $item) {
//            $product_type_2_sku[$item['original_sku']][] = $item['sku'];
//        }
//
//        //查找product_type=1的，因为下边需要一一判断
//        $sql                = "SELECT sku from $yibai_product where product_type=1;";
//        $product_type_1_sku = $this->product->query($sql)->result_array();
//        if (!empty($product_type_1_sku)) {
//            $product_type_1_sku = array_column($product_type_1_sku, 'sku', 'sku');
//        }
//
//        //是否精品
////        $sql          = "SELECT sku,product_status,is_boutique FROM $yibai_product";
////        $sku_map_info = $this->product->query($sql)->result_array();
////        if (!empty($sku_map_info)) {
////            $sku_map_info = array_column($sku_map_info, NULL, 'sku');
////        }
//
//
//        /*
// * 需求:销售那边想看到哪些是listing表里通过seller_sku和account_id去sku_map表里找不到的数据
// * 知道后,销售对这样的数据进行处理,sku_map表里就生成了,
// * 再去将这样新增的数据,跑到备货关系配置表和物流属性配置表
// * 获取sku_map表里新增的记录
// * 所以下面通过union增量拉取
// */
//        //主查询
//        if ($all_data) {//查询所有的数据
//            $sql = "SELECT
//            sku_map.sku ,product.sku as product_sku,product.product_type,l_all.account_id
//            FROM $yibai_amazon_listing_alls as l_all
//            INNER JOIN $yibai_amazon_sku_map as sku_map on (l_all.seller_sku=sku_map.seller_sku and l_all.account_id=sku_map.account_id)
//            left join $yibai_product as product on (product.sku=sku_map.sku)
//            where  l_all.fulfillment_channel='AMA' and  sku_map.sku!='' and  sku_map.sku!='#N/A' and  sku_map.sku!='#REF!' and sku_map.sku is not null
//            GROUP BY l_all.account_id,sku_map.sku ORDER BY product.record_change_time,product.id ASC";
//        } else {//listing表增量的数据和sku_map新增的数据
//            $sql = "
//(SELECT
//            sku_map.sku ,product.sku as product_sku,product.product_type,l_all.account_id
//            FROM $yibai_amazon_listing_alls as l_all
//            INNER JOIN $yibai_amazon_sku_map as sku_map on (l_all.seller_sku=sku_map.seller_sku and l_all.account_id=sku_map.account_id)
//            left join $yibai_product as product on (product.sku=sku_map.sku)
//            where  l_all.create_date  BETWEEN '{$start_time}' and '{$end_time}' and  l_all.fulfillment_channel='AMA' and  sku_map.sku!='' and  sku_map.sku!='#N/A' and  sku_map.sku!='#REF!' and sku_map.sku is not null
//            GROUP BY l_all.account_id,sku_map.sku ORDER BY product.record_change_time,product.id ASC)
//						UNION
//
//(SELECT
//            sku_map.sku ,product.sku as product_sku,product.product_type,l_all.account_id
//            FROM $yibai_amazon_listing_alls as l_all
//            INNER JOIN $yibai_amazon_sku_map as sku_map on (l_all.seller_sku=sku_map.seller_sku and l_all.account_id=sku_map.account_id)
//            left join $yibai_product as product on (product.sku=sku_map.sku)
//            where  sku_map.create_date  BETWEEN '{$start_time}' and '{$end_time}' and  l_all.fulfillment_channel='AMA' and  sku_map.sku!='' and  sku_map.sku!='#N/A' and  sku_map.sku!='#REF!' and sku_map.sku is not null
//            GROUP BY l_all.account_id,sku_map.sku ORDER BY product.record_change_time,product.id ASC)";
//        }
//        //分批查询,总记录数
//        $pageSize  = 300000;
//        $count_sql = sprintf('SELECT  count(*) as total FROM (%s) AS a ', $sql);
//
//        $count = $this->product->query($count_sql)->row_array();
//        $total = $count['total']??'';
//        if (empty($total)) {
//            log_message('error', sprintf('开始时间:%s,结束时间:%s,该时间段未查找到数据', $start_time, $end_time));
//            exit;
//        }
//        $page = 1;
//        if (!empty($total)) {
//            $page = ceil($total / $pageSize);
//        }
//        for ($i = 1; $i <= $page; $i++) {
//            $_sql      = $sql . ' LIMIT ' . (($i - 1) * $pageSize) . ',' . $pageSize;
//            $data_info = $this->product->query($_sql)->result_array();
//            foreach ($data_info as $key => $row) {
//                if (isset($account_arr[$row['account_id']])) {
//                    $station_code = $account_arr[$row['account_id']]['station_code']??'';//不能为空
//                } else {
//                    $station_code = '';
//                }
//
//                if (in_array($station_code, $pass_station_code)) {//如果是德意法西英的才会走过滤
//                    $station_code = 'eu';
//                }
//
//                $original_sku = $row['sku'];
//                $product_sku  = $row['product_sku'];
//
//                $skus = [];
//                //第一层，原sku存在主表直接可用
//                if ($row['product_type'] == 1) {
//                    $skus[] = $product_sku;
//                } elseif ($row['product_type'] == 2) {
//                    //判断中转的是否存在
//
//                    if (isset($product_type_2_sku[$product_sku])) {
//                        foreach ($product_type_2_sku[$product_sku] as $k => $item) {
//                            if (!empty($item)) {
//                                $skus[] = $item;
//                            }
//                        }
////                    $skus[] = $product_type_2_sku[$original_sku];
//                    } else {//type=2没有匹配到
//                        $skus[] = $product_sku;
//                    }
//                } else {//第一层就不存在表product中
//                    //拆
//                    $sku   = trim($original_sku);
//                    $_skus = $this->splitSku($sku);//可能包含;,+要拆解
//
//                    foreach ($_skus as $_sku) {
//                        if (isset($product_type_1_sku[$_sku])) {//判断是否存在type_1中
//                            $skus[] = $product_type_1_sku[$_sku]??$_sku;
//                        } elseif (isset($product_type_2_sku[$_sku])) {//判断是否在中转中
//                            foreach ($product_type_2_sku[$_sku] as $k => $item) {
//                                if (!empty($item)) {
//                                    $skus[] = $item;
//                                }
//                            }
//                        } else {//证明不存在表product中，不需拆，过滤*
//                            $_sku = $this->getFilterSku($_sku);
//                            //再次判断是否存在product中
//                            if (isset($product_type_1_sku[$_sku])) {//判断是否存在type_1中
//                                $skus[] = $product_type_1_sku[$_sku]??$_sku;
//                            } elseif (isset($product_type_2_sku[$_sku])) {//判断是否在中转中
//                                foreach ($product_type_2_sku[$_sku] as $k => $item) {
//                                    if (!empty($item)) {
//                                        $skus[] = $item;
//                                    }
//                                }
//                            } else {
//                                $_empty_sku[] = $original_sku;
//                                $emp++;
//                                continue;
//                            }
//                        }
//                    }
//                }
//
//                foreach ($skus as $_sku) {
//                    if (empty($_sku)) {
//                        $_empty_sku[] = $original_sku;
//                        $emp++;
//                        continue;
//                    }
//                    $_sku = trim($_sku);
//                    if (empty($_sku)) {
//                        $_empty_sku[] = $original_sku;
//                        $emp++;
//                        continue;
//                    }
//                    //判断是否存在, sku+station_code 唯一索引
//                    $sku_station = sprintf('%s%s', $_sku, $station_code);
//                    if (isset($unique_sku_station[$sku_station])) {
//                        $emp++;
//                        continue;
//                    } else {
//                        $unique_sku_station[$sku_station] = 1;
//                    }
//
//
//                    $gid = gen_id(random_int(10, 99));
//
//                    $batch_params[] = [
//                        'sql' => "INSERT IGNORE INTO yibai_fba_sku_cfg (gid,original_sku,sku,station_code,created_at) VALUES ('{$gid}','{$original_sku}','{$_sku}','{$station_code}','{$created_at}')",
////                    'sql' => "INSERT IGNORE INTO yibai_fba_sku_cfg (gid,original_sku,sku,station_code,sale_state,is_boutique,created_at) VALUES ('{$gid}','{$original_sku}','{$_sku}','{$station_code}',$sale_state,$is_boutique,'{$created_at}')",
//                    ];
//                    $params_gid[]   = [
//                        'gid' => $gid
//                    ];
//                    $all++;
//                    if ($all % 500 == 0) {
//                        $this->transform($batch_params, $params_gid);
//                        $all          = 0;
//                        $batch_params = [];
//                        $params_gid   = [];
//                    }
//                }
//            }
//            $data_info = [];
//        }
//        unset($data_info);
//        unset($product_type_1_sku);
//        unset($product_type_2_sku);
//        unset($sku_map_info);
////        file_put_contents('./testfba.txt',print_r($sku_cache,true));exit;
//
//
//        $this->transform($batch_params, $params_gid);          //插入数据库
//
//
//        //删除因重复导不进去导致part表数据多出
//        $sql = "DELETE from yibai_fba_sku_cfg_part where gid not in (select gid from yibai_fba_sku_cfg);";
//        $this->stock->query($sql);
//        $unique_count = $this->stock->affected_rows();
//        $this->count  = $this->count - $unique_count;
//
//        $endtime  = explode(' ', microtime());
//        $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
//        $thistime = round($thistime, 3);
//        $message  = sprintf('执行完成,耗时:%s,备货关系配置表,本次插入%s条记录,异常数据sku为空:%s条,original_sku:%s', $thistime, $this->count, $emp, json_encode($_empty_sku));
//        log_message('error', $message);
//        echo '本次插入' . $this->count . '条记录,异常数据:' . $emp;
//        echo('时间:' . $thistime);
//        //执行结束,记录状态
//        $params            = FBA_PLAN_SCRIPT[FBA_STOCK_CFG_LIST];
//        $params['step']    = FBA_STOCK_CFG_LIST;
//        $params['message'] = $message;
//        record_script_state($params);
//    }


    private function transform($batch_params, $params_gid)
    {
        if (empty($batch_params) || empty($params_gid)) {
            return FALSE;
        }
        $this->stock->insert_batch('yibai_fba_sku_cfg_part', $params_gid);
        foreach ($batch_params as $key => $item) {
            $this->stock->query($item['sql']);
            //只有当主表插入成功才执行插入附表的sql
            $this->count++;
            unset($batch_params[$key]);
        }

        return true;
    }

    /**
     * 二维数组转一维数组,将sku拆分,去重,去除空
     *
     * @param $result
     *
     * @return array
     */
    public function splitSku_array($result)
    {

        foreach ($result as $key => $value) {
            $data[$key] = $value['original_sku'];
        }
        $string = implode('[[[', array_filter($data));//转为字符串处理

        $string = str_replace([';', ',', '+'], "[[[", $string);//字符串换掉 拆分出多个sku
        $data   = explode('[[[', $string);//转回数组
        $data   = array_unique(array_filter($data));

        return $data;
    }

    function splitSku($sku)
    {
        $string = str_replace([';', ',', '+'], "[[[", $sku);//字符串换掉 拆分出多个sku
        $data   = explode('[[[', $string);//转回数组
        $data   = array_unique(array_filter($data));

        return $data;
    }

    /**
     * 处理*号
     *
     * @param $sku
     *
     * @return bool|string
     */
    public function getFilterSku($sku)
    {
        $sku = trim($sku);
        $end = strpos($sku, '*');
        if ($end !== false) {
            //类似这种结构过虑  "JYJJ69617*20"
            if (preg_match(FILTER_SKU, $sku)) {
                $sku = substr($sku, 0, $end);
            }
        }

        return $sku;
    }


    /**
     * 查询返回已存在的数量
     */
//    public function check($start_time,$end_time){
//        $this->stock->select('count(*) as count')
//            ->where('created_at >=',$start_time)->where('created_at <=',$end_time)
//            ->from('yibai_fba_sku_cfg');
//        $count = $this->stock->get()->row_array();
//        return $count;
//    }

    /**
     * 更新所有的退税和采购仓库字段 从erp (已废弃)
     * http://192.168.71.170:1084/crontab/FBA/updateRefundTax
     */
//    public function updateRefundTax()
//    {
//        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
//        $sql           = 'SELECT sku FROM yibai_product WHERE `ticketed_point`-`tax_rate`>1';
//        $result        = $this->product->query($sql)->result_array();
//        foreach ($result as $key => $value) {
//            $data[] = $value['sku'];
//        }
//        $temp = ['is_refund_tax' => 1, 'purchase_warehouse_id' => 2];
//        $i    = 0;
//        foreach ($data as $sku) {
//
//            $this->stock->where('sku', $sku);
//            $this->stock->update('yibai_fba_sku_cfg', $temp);
//            if ($this->stock->affected_rows()) {
//                $i++;
//            }
//        }
//        echo '更新了' . $i;
//        exit;
//
//    }

//    public function updateOverseaRefundTax()
//    {
//        $this->product = $this->load->database('f_yibai_product', TRUE);//ERP系统数据库
//        $sql           = 'SELECT sku FROM yibai_product WHERE `ticketed_point`-`tax_rate`>1';
//        $result        = $this->product->query($sql)->result_array();
//        foreach ($result as $key => $value) {
//            $data[] = $value['sku'];
//        }
//        $temp = ['is_refund_tax' => 1];
//        $i    = 0;
//        foreach ($data as $sku) {
//            $i++;
//            $this->stock->where('sku', $sku);
//            $this->stock->update('yibai_oversea_sku_cfg_main', $temp);
//        }
//        echo '更新了' . $i;
//        exit;
//    }


    /**
     * 更新所有的退税和采购仓库字段   从数据中心(已废弃)
     * http://192.168.71.170:1084/crontab/fba/updateByDataCenter
     */
//    public function updateByDataCenter()
//    {
//        $this->data_center = $this->load->database('data_center', TRUE);//ERP系统数据库
//        $sql               = "SELECT sku FROM yb_product_stock WHERE `warehouse_code` = 'TS'";
//        $result            = $this->data_center->query($sql)->result_array();
//        foreach ($result as $key => $value) {
//            $data[] = $value['sku'];
//        }
//        $temp = ['is_refund_tax' => 1, 'purchase_warehouse_id' => 2];
//        $i    = 0;
//        foreach ($data as $sku) {
//
//            $this->stock->where('sku', $sku);
//            $this->stock->update('yibai_fba_sku_cfg', $temp);
//            if ($this->stock->affected_rows()) {
//                $i++;
//            }
//        }
//        echo '更新了' . $i;
//        exit;
//
//    }


    /**
     * 回写延迟的汇总单
     */
    public function rewrite_unassign_sumsn()
    {
        try {
            $get = $this->compatible('get');
            $this->load->service('fba/PrTrackService');
            $this->data['data']   = $this->prtrackservice->rewrite_unassign_sumsn($get['date'] ?? '');
            $this->data['status'] = 1;
            $code                 = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        exit;
    }


//

//
//    public function update_warehouse_id(){
////     ------------  配置开始 ------------
//        ini_set('memory_limit', '2048M');
//        ini_set('max_execution_time', '0');
//        set_time_limit(0);
//
//        $warehouseCodeArr = ['AFN' => PURCHASE_WAREHOUSE_EXCHANGE, 'shzz' => PURCHASE_WAREHOUSE_SHZZ];//采购仓库映射
//        /*
//         * LOGISTICS_ATTR_MAP 计划=>物流,
//         * $logistic_id_map 物流=>计划
//         */
//        $this->logistic_id_map = array_flip(LOGISTICS_ATTR_MAP);//计划系统和物流系统物流属性映射
//
//        $size = 2; //请求java接口一次传多少条记录
////     ------------  配置结束 ------------
//
//        $page                           = $this->input->post_get('page') ?? 1;
//        $sku                            = $this->input->post_get('sku') ?? '';
//        if (empty($page) || $page < 0) {
//            $page = 1;
//        }
//
//        //数量
//        $count = $this->stock->select('gid,purchase_warehouse_id,sku')->from('yibai_fba_sku_cfg')->count_all_results();
//        if(empty($count)){
//            echo 'yibai_fba_sku_cfg表里无数据';exit;
//        }
//        $sql = "SELECT main.gid,main.purchase_warehouse_id,main.sku, log.logistics_id FROM yibai_fba_sku_cfg as main
//RIGHT JOIN yibai_fba_logistics_list as log
//ON main.station_code = log.station_code AND main.sku = log.sku;
//";
//        $info = $this->stock->query($sql)->result_array();//匹配到有物流属性的
//
//        //组织数据
//
//        $data = [];
//        foreach ($info as $key => $item){
//            $data[] = [
//                'sku' => $item['sku'],
//                'serviceTypeId'=> 1,
//                'transportType' => LOGISTICS_ATTR_MAP[$item['logistics_id']],
//                'platformCode'  => 'AMAZON',
//            ];
//        }
//
//        //将数据分批次
//        $chunk_array = array_chunk($data,$size);
//        unset($data);
//
//
//        foreach ($chunk_array as $key => $item){
//            $result = $this->getWareHouse($item);//调java的接口(一次性最大支持1000条)
//            $update_data[] = $this->organize_data($result);//组织数据
//        }
////        foreach ($update_data as $item){
////            $a[] = $item;
////        }
//        pr($update_data);exit;
//    }
//
//    public function organize_data($result){
//        if(0 == count($result)){
//            echo '0 == count($result)';exit;
//        }
//        $update_data = [];
//        foreach ($result as $key => $value){
//            if(empty($value['warehouseInfo'])){
//                continue;
//            }
//            $update_data[] = [
//                'sku'=>$value['sku'],//sku
//                'logistics_id'=>$this->logistic_id_map['transportType'],//计划系统物流属性id
//                'purchase_warehouse_id' => LOGISTICS_ATTR_MAP[$value['warehouseInfo']['warehouseCode']],//采购仓库
//            ];
//        }
//        return $update_data;
//    }
//
//
//
//
//
//
//    /**
//     * http://192.168.71.170:1084/crontab/FBA/update_sku_cfg_warehouse_id
//     * 更新采购仓库字段
//     * java_api文档:http://192.168.71.156/web/#/73?page_id=2339
//     * 通过sku,物流属性id,业务线,调用java接口获取需要更新的记录
//     */
//    public function update_sku_cfg_warehouse_id()
//    {
//        ini_set('memory_limit', '2048M');
//        ini_set('max_execution_time', '0');
//        set_time_limit(0);
//
//        $page                           = $this->input->post_get('page') ?? 1;
//        $sku                            = $this->input->post_get('sku') ?? '';
//        $logistics_id_to_transport_type = [
//            LOGISTICS_ATTR_SHIP => 14,
//            LOGISTICS_ATTR_LAND => 12,
//            LOGISTICS_ATTR_AIR  => 8,
//            LOGISTICS_ATTR_BLUE => 10,
//            LOGISTICS_ATTR_RED  => 10
//        ];
//
//        if (empty($page) || $page < 0) {
//            $page = 1;
//        }
//
//        $starttime = explode(' ', microtime());
//
//        $this->load->model('Sku_cfg_main_model', 'cfg_main', false, 'fba');
//        $this->load->model('Fba_logistics_list_model', 'logistics', false, 'fba');
//
////获取sku
//
//        $warehouseCodeArr = ['AFN' => PURCHASE_WAREHOUSE_EXCHANGE, 'shzz' => PURCHASE_WAREHOUSE_SHZZ];
//
////如果传了sku
//        $_where = "";
//        if (!empty($sku)) {
//            $sku    = explode(",", $sku);
//            $_where = "where log.sku in ('" . implode("','", $sku) . "')";
//        }
//
//
//        $sql    = "SELECT COUNT(1) as c from
//(
//SELECT log.sku, log.logistics_id
//FROM {$this->logistics->table} log
//LEFT JOIN {$this->cfg_main->table} main ON log.sku = main.sku AND log.station_code = main.station_code
//$_where
//
//)t  ";//GROUP BY log.sku, log.logistics_id
//        $result = $this->stock->query($sql)->row_array();
////        print_r($result);exit;
//
//        $total = $result['c'] ?? 0;
//
//        $chunk_size = 350;//每次拉取多少个
////$limit = 20000;
//
//        $pageCount = 4;//ceil($total / $limit);
//
//        if (empty($sku)) {
//            $limit  = ceil($total / $pageCount);
//            $offset = ($page - 1) * $limit;
//            $limit  = "LIMIT  $offset,$limit";
//        } else {
//            $limit = "";
//        }
//
//        $handle_count        = 0;
//        $fali_batch          = [];
//        $fail_count          = 0;
//        $no_change_count_all = 0;
//
//
////查询数据 物流属性表和备货配置表对应
//        $sql = "SELECT * from
//(
//SELECT log.sku,log.station_code, log.logistics_id, main.purchase_warehouse_id, log.created_at
//FROM {$this->logistics->table} log
//LEFT JOIN {$this->cfg_main->table} main ON log.sku = main.sku AND log.station_code = main.station_code
//$_where
//$limit
//)t ORDER BY t.created_at asc
//";//GROUP BY log.sku, log.logistics_id
//
//        $list = $this->stock->query($sql)->result_array();
////        print_r($list);exit;
//
//        if (!empty($list)) {
//            $skuArr     = [];
//            $listSku    = [];
//            $listSkuLog = [];
//            foreach ($list as $row) {
//                $skuArr[] = $row['sku'];
//
//                $listSku[$row['sku']][] = [
//                    'sku'                   => $row['sku'],
//                    'station_code'          => $row['station_code'],
//                    'logistics_id'          => $row['logistics_id'],
//                    'purchase_warehouse_id' => $row['purchase_warehouse_id'],
//                ];
//
//                $_key = $row['sku'] . '||' . $row['logistics_id'];
//                if (!isset($listSkuLog[$_key])) {
//                    $listSkuLog[$_key] = $row;
//                }
//            }
//            $skuArr = array_unique($skuArr);
//
//            $skuArr_chunk = array_chunk($skuArr, $chunk_size);
//
////            print_r($listSku);exit;
//
//            echo '$listSku:' . count($listSku) . PHP_EOL;
//
//            foreach ($skuArr_chunk as $_i => $_skuArr) {
//                $paramsList      = [];
//                $paramsListCache = [];
//                foreach ($_skuArr as $_sku) {
//                    if (!empty($listSku[$_sku])) {
//                        foreach ($listSku[$_sku] as $item) {
//                            $_key = $item['sku'] . '||' . $item['logistics_id'];
//                            if (!isset($paramsListCache[$_key])) {
//                                $paramsListCache[$_key] = 1;
//                                if (isset($logistics_id_to_transport_type[$item['logistics_id']])) {
//                                    $paramsList[] = [
//                                        'sku'           => $_sku,
//                                        'serviceTypeId' => 1,
//                                        'transportType' => $logistics_id_to_transport_type[$item['logistics_id']],
//                                        'platformCode'  => 'AMAZON',
//                                    ];
//                                }
//                            }
//                        }
//
//                    }
//                }
//                if (empty($paramsList)) {
//                    continue;
//                }
//
//                $dataList = $this->getWareHouse($paramsList);
//
//
//                $matchSku              = [];
//                $collspac_batch_params = [];
//                $handle_count_1        = 0;
//                $handle_count_2        = 0;
//                $no_change_count       = 0;
//
//                $this->show_log('$_skuArr:' . count($_skuArr) . ',chunk:' . $_i . ',dataList:' . count($dataList) . PHP_EOL);
//
//                if (!empty($dataList)) {
//
//                    foreach ($dataList as $wareHouse) {//每个sku
//                        $_sku               = $wareHouse['sku'];
//                        $transportType      = $wareHouse['transportType'];
//                        $_warehouseInfoList = $wareHouse['warehouseInfoList'];
//
//
////只修改1.仓库有修改的，2.仓库列表为空，对比默认值，不同才修改，减少没必要的更新
//                        if (!empty($_warehouseInfoList) && count($_warehouseInfoList) > 0) {
////查找中转仓
//                            foreach ($_warehouseInfoList as $ware) {
//                                $_warehouseCode = $ware['warehouseCode'];
//                                if (isset($warehouseCodeArr[$_warehouseCode])) {
//                                    $logistics_id_arr = [];
//                                    if ($transportType == 8) {
//                                        $logistics_id_arr[] = LOGISTICS_ATTR_AIR;
//                                    } elseif ($transportType == 12) {
//                                        $logistics_id_arr[] = LOGISTICS_ATTR_LAND;
//                                    } elseif ($transportType == 10) {
//                                        $logistics_id_arr[] = LOGISTICS_ATTR_BLUE;
//                                        $logistics_id_arr[] = LOGISTICS_ATTR_RED;
//                                    } elseif ($transportType == 14) {
//                                        $logistics_id_arr[] = LOGISTICS_ATTR_SHIP;
//                                    }
//
//                                    if (!empty($logistics_id_arr)) {
//                                        foreach ($logistics_id_arr as $_lid) {
//                                            $_key = $_sku . '||' . $_lid;
//
//                                            foreach ($listSku[$_sku] as $_main_item) {
////if (isset($listSkuLog[$_key])) {
////$_main_item = $listSkuLog[$_key];
//
////判断是否有匹配要修改的,有则这个sku全改为统一的
//                                                if ($_main_item['logistics_id'] == $_lid) {
//
//                                                    if (!isset($matchSku[$_sku])) {
//                                                        $matchSku[$_sku] = 1;
//                                                    }
//
//                                                    if ($_main_item['purchase_warehouse_id'] != $warehouseCodeArr[$_warehouseCode]) {
//                                                        $handle_count_1++;
//                                                        $collspac_batch_params[] = [
//                                                            'purchase_warehouse_id' => $warehouseCodeArr[$_warehouseCode],
//                                                            'where'                 => [
//                                                                'sku'          => $_sku,
//                                                                'station_code' => $_main_item['station_code'],
//                                                            ]
//                                                        ];
////$collspac_batch_params[] = ['sku' => $_sku, 'purchase_warehouse_id' => $warehouseCodeArr[$_warehouseCode]];
//                                                        $this->show_log('sku:' . $_sku . ',station_code:' . $_main_item['station_code'] . ' 匹配变更采购仓:' . $_warehouseCode . PHP_EOL);
//                                                    } else {
//                                                        $no_change_count_all++;
//                                                    }
//
//                                                }
////}
//                                            }
//                                        }
//                                    }
//                                }
//                            }
//                        } else {
////$no_change_count_all+=count($listSku[$_sku]);
//                        }
//                    }
//                }
//
//
////计算还有多少sku是没有匹配到的,取差值
//                $matchSku = array_keys($matchSku);
//                $diff     = array_diff($_skuArr, $matchSku);
////                print_r($diff);exit;
//
////echo '==>$_skuArr:'.count($_skuArr).'-$matchSku:'.count($matchSku).'='.count($diff)."<br>";
//
//
//                if (!empty($diff)) {
//                    foreach ($diff as $sku) {
////如果不是默认值,则修改回默认值
//                        $hasMatch = false;
//                        foreach ($listSku[$sku] as $skuItem) {
//                            if ($skuItem['purchase_warehouse_id'] != PURCHASE_WAREHOUSE_EXCHANGE) {
//                                $handle_count_2++;
//                                $collspac_batch_params[] = [
//                                    'purchase_warehouse_id' => PURCHASE_WAREHOUSE_EXCHANGE,
//                                    'where'                 => [
//                                        'sku' => $sku
//                                    ]
//                                ];
//                                $hasMatch                = true;
//                                break;
//                            }
//                        }
//
//                        if (!$hasMatch) {//没有匹配上的
//                            /* echo '-------$sku:' . $sku . '---------<br>';
//                            var_dump($listSku[$sku]);
//                            echo '----------------<br>';*/
//                            $no_change_count++;
//                        }
//                    }
//                }
//
//                $handle_count        += $handle_count_1 + $handle_count_2;
//                $no_change_count_all += $no_change_count;
//
//                $this->show_log('==>$_skuArr:' . count($_skuArr) . '-$matchSku:' . count($matchSku) . '=' . count($diff) . ",handle_count_1:$handle_count_1,handle_count_2:$handle_count_2 ----->no_change_count:$no_change_count" . PHP_EOL);
//
//
//                $suc_count = 0;
//                if (!empty($collspac_batch_params)) {
//                    foreach ($collspac_batch_params as $collspac) {
////循环多次，确保都能成功
//                        for ($i = 1; $i <= 5; $i++) {
//                            $res = $this->stock->update($this->cfg_main->table, ['purchase_warehouse_id' => $collspac['purchase_warehouse_id']], $collspac['where']);
//                            if ($res !== FALSE) {
//                                $suc_count++;
//                                break;
//                            }
//                            sleep(rand(1, 3));
//                        }
//                    }
//                }
//                $fail_count = count($collspac_batch_params) - $suc_count;
//            }
//        }
//        $endtime  = explode(' ', microtime());
//        $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
//        $thistime = round($thistime, 3);
//
//        echo "操作完成耗时：" . $thistime . " 秒,limit:$limit,共需处理：$handle_count 条记录，失败：$fail_count 条,无需处理:$no_change_count_all 条,共：$pageCount 页,第;$page 页";
//        exit;
//    }


    /**
     * 调物流系统接口
     * 是否退税
     */
    /*    private function getDrawback($paramsList = [])
        {
            $tran_drawback = ['1' => REFUND_TAX_YES, '0' => REFUND_TAX_NO];
            $all_skus      = array_column($paramsList, 'sku');
    //              $paramsList = [[
    //                    'sku' => '04EGS54200',
    //                    'service_name'=>'FBA',
    //                ]];

            $update_data = [];
            $paramsList  = json_encode($paramsList);
            $url         = LOGISTICS_URL . '/ordersys/services/LogisticsDrawbackManagement/checkDrawback';
            $result      = getCurlData($url, $paramsList);//发起请求
            $data        = json_decode($result, true);//返回的结果

    //pr($data);exit;
            if ($data['status'] != 1) {
                log_message('ERROR', sprintf('请求地址：/ordersys/services/LogisticsDrawbackManagement/checkDrawback，异常：%s', json_encode($data)));
            }
            if (isset($data['data_list']) && !empty($data['data_list'])) {

                $skus = array_column($data['data_list'], 'sku');
                $diff = array_diff($all_skus, $skus);

                foreach ($diff as $item) {
                    $update_data[] = ['sku' => $item, 'is_refund_tax' => 3];
                }
                foreach ($data['data_list'] as $key => $value) {
                    $update_data[] = ['sku' => $value['sku'], 'is_refund_tax' => $tran_drawback[$value['is_drawback']]??3];
                }

                return $update_data;
            } else {
                log_message('ERROR', sprintf('请求地址：/ordersys/services/LogisticsDrawbackManagement/checkDrawback，异常：data_list为空%s', $paramsList));
            }
        }*/

    /**
     * 更新是否退税字段(v1.1.1 调物流系统接口)
     * 接口文档:http://192.168.71.156/web/#/73?page_id=3225
     * http://192.168.71.170:1084/crontab/FBA/updateDrawback
     */
    /* public function updateDrawback()
     {
         try {
             ini_set('memory_limit', '3072M');
             ini_set('max_execution_time', '0');
             set_time_limit(0);
             $starttime = explode(' ', microtime());

             //查询上个步骤是否执行完成
             check_script_state(FBA_DELIVERY_CYCLE);
             $count = 0;
             // 组织数据
             $all_sku = $this->stock->select('sku')->distinct()->get('yibai_fba_sku_cfg')->result_array();
             $data    = [];
             foreach ($all_sku as $key => $value) {
                 $data[] = [
                     'sku'          => $value['sku'],
                     'service_name' => 'FBA',
                 ];
             }
             //将数据进行分割
             $data = array_chunk($data, 200);
             foreach ($data as $key => $value) {
                 $insert_info = $this->getDrawback($value);
                 if (!empty($insert_info)) {
                     $result = $this->refresh_data($insert_info);
                     $count += $result;
                 }
                 unset($data[$key]);
             }
             $code = 200;
             $endtime  = explode(' ', microtime());
             $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
             $thistime = round($thistime, 3);
             $message = sprintf('执行成功,耗时:%s,更新记录:%s',$thistime,$count);
             //执行结束,记录状态

             $params = FBA_PLAN_SCRIPT[FBA_IS_REFUND_TAX];
             $params['step'] = FBA_IS_REFUND_TAX;
             $params['message'] = $message;
             record_script_state($params);
         } catch (\Throwable $e) {
             $code = $e->getCode();
             $errorMsg = $e->getMessage();
         } finally {
             $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
         }

     }*/

    /**
     * 数据库操作,批量更新是否退税字段
     *
     * @param $data
     */
    /*    private function refresh_data($data)
        {
            return $this->stock->update_batch('yibai_fba_sku_cfg', $data, 'sku');
        }*/

    /**
     * 同一站点、账号、asin 但seller_sku不同的需高亮显示(v1.1.1)
     * http://192.168.31.145:8026/index.php/crontab/FBA/fba_logistics_highlight
     */
    /*    public function fba_logistics_highlight()
        {
            try {
                $sql = 'REPLACE INTO yibai_fba_diff_seller_sku SELECT md5(CONCAT(station_code,account_name, asin)) as hash from yibai_fba_logistics_highlight
                group by station_code, account_name, asin
                HAVING count(DISTINCT seller_sku) > 1';

                $this->stock->query($sql);
                $rows = $this->stock->affected_rows();
                echo $rows;
                exit;
            } catch (\Throwable $e) {
                $code = $e->getCode();
                $errorMsg = $e->getMessage();
            } finally {
                $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            }
        }*/

    public function getWareHouse($paramsList = [])
    {

//        $paramsList[] = [
//            'sku'=> 'AF00154',
//            'businessType' => '3',
//            'isDrawback' => 1,
//            'platformCode' => 'AMAZON',
//        ];
//        $paramsList = json_encode($paramsList);
//echo json_encode(['list' => $paramsList]);exit;
//file_put_contents('./FBA采购仓库入参.txt',json_encode(['list' => $paramsList]));
        $result = TMS_RPC_CALL('YB_LOGISTICS_01', ['list' => $paramsList]);
//pr($result);exit;
        if (!empty($result['code']) && $result['code'] > 0) {
            log_message('ERROR', sprintf('请求仓库地址：logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo，批量获取sku仓库异常：%s', $result['msg']));
            echo '获取失败';
        }

        return !empty($result['data']) ? $result['data'] : [];
    }

    /**
     * 中转仓规则(v1.1.1)
     * 接口文档:http://192.168.71.156/web/#/73?page_id=2339
     * FBA需要传的参数: sku,业务线类型id,是否退税,平台code
     * businessType:3,业务线
     * platformCode:AMAZON,平台code
     * 所调的接口一次最大支持传1000条
     *
     * $pageCount 设置查询结果分为几页
     * $page 第几页 从1开始
     * $sku
     */

    public function warehouse_id()
    {
        try {
            $starttime = explode(' ', microtime());

//            $pageCount = $this->input->post_get('page_count') ?? 4;//默认分页为4
//            $page = $this->input->post_get('page') ?? 1;

            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            // 所调的接口一次最大支持传1000条,目前设置一次传800

            // 总记录数
//            $sql = 'SELECT count(1) as t FROM (SELECT sku,is_refund_tax FROM yibai_fba_sku_cfg GROUP BY sku)s';
//            $total = $this->stock->query($sql)->row_array();
//            if(empty($total['t'])){
//                echo '查询记录为0';
//                exit;
//            }else{
//                $total = $total['t'];
//            }

//            $limit = ceil($total / $pageCount);
//            $offset = ($page - 1) * $limit;
//            $limit = "LIMIT  $offset,$limit";


//            $sql = 'SELECT sku,is_refund_tax FROM yibai_fba_sku_cfg GROUP BY sku '.$limit;
            //退税的所有sku
            $sql    = "SELECT sku,is_refund_tax,is_boutique FROM yibai_fba_sku_cfg GROUP BY sku HAVING is_refund_tax = 1";
            $result = $this->stock->query($sql)->result_array();
            if (empty($result)) {
                log_message('ERROR', sprintf('/crontab/FBA/warehouse_id,查询退税的结果为空,%s', $sql));
            }
            $this->build_data($result, PURCHASE_WAREHOUSE_FBA_TAX_YES);

            //不退税的所有sku
            $sql    = 'SELECT sku,is_refund_tax,is_boutique FROM yibai_fba_sku_cfg GROUP BY sku HAVING is_refund_tax = 2';
            $result = $this->stock->query($sql)->result_array();
            if (empty($result)) {
                log_message('ERROR', sprintf('/crontab/FBA/warehouse_id,查询不退税的结果为空,%s', $sql));
            }
            $this->build_data($result, PURCHASE_WAREHOUSE_FBA_TAX_NO);

//            $final_total = count($data);//去除空值后
//
//                // 数据库操作
//            if (0 != $final_total){
//
//                $rows = $this->update_warehouse($data);
//
//                echo "执行成功,接口返回结果数:$result_total".PHP_EOL;
//                echo "array_filter后:$final_total".PHP_EOL;
//                echo "数据表更新受影响行数:$rows".PHP_EOL;
//            }else{
//                echo "执行失败,接口返回结果数:$result_total".PHP_EOL;
//                echo "array_filter后:$final_total".PHP_EOL;
//            }
            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            log_message('error', sprintf('ok,执行耗时:%s', $thistime));
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
        }
    }

    public function build_data($result, $warehouse_id)
    {
        $size      = 800;
        $rows      = 0;
        $data      = [];
        $match_sku = [];

        $unknownWarehouse = [];


        //组织传参
        foreach ($result as $key => $item) {
            if ($item['is_boutique'] == 2) {//是否精品：0否，1是（平台仓必传）
                $item['is_boutique'] = 0;
            }
            if ($item['is_refund_tax'] == 3) {
                continue;
            }
            $data[] = [
                'sku'          => $item['sku'],
                'businessType' => '3',//写死的FBA为3
                'isDrawback'   => $item['is_refund_tax'],
                'platformCode' => 'AMAZON',//写死的平台为AMAZON
                'isBoutique'   => $item['is_boutique']
            ];
        }
        $result = [];
        if (empty($data)) {
            log_message('ERROR', '/crontab/FBA/warehouse_id,入参为空');
        } else {
            $all_sku = array_column($data, 'sku');//所有的sku
            $data    = array_chunk($data, $size);
            $n       = count($data);//分成n批

            $result_total = 0;
            for ($i = 0; $i < $n; $i++) {
                $result[] = $this->getWareHouse($data[$i]);
            }
//            pr($result);exit;
            $result_total = count($result);

            unset($data);
            $data = [];

            if (empty($result_total)) {
                log_message('ERROR', '/crontab/FBA/warehouse_id,返回的结果异常');
            }
            foreach ($result as $key => $item) {
                foreach ($item as $value) {
                    $match_sku[] = $value['sku'];
                }
//                $match_sku = array_column($result,'sku');//匹配到的sku
            }

            $diff = array_diff($all_sku, $match_sku);//未匹配到的

            unset($all_sku);
            unset($match_sku);
//            pr($diff);exit;
            $this->update_unmatch_warehouse($diff, $warehouse_id);
            unset($diff);

            //组织更新表的数据
            foreach ($result as $key => $item) {
                $item = array_filter($item);
                if (empty($item)) {
                    continue;
                }
                foreach ($item as $row) {
                    if (!isset(WAREHOUSE_CODE[$row['warehouseCode']])) {
                        $unknown_Warehouse[] = $row['warehouseCode'];//记录接口返回的未知warehouse_code
                        continue;
                    }

                    $data[] = [
                        'sku'                   => $row['sku'],
                        'purchase_warehouse_id' => WAREHOUSE_CODE[$row['warehouseCode']],
                    ];
                }

                $rows += $this->update_warehouse($data);
                $data = [];
                unset($result[$key]);
            }


        }


        if (!empty($unknown_Warehouse)) {
            $unknown_Warehouse = array_unique($unknown_Warehouse);
            log_message('error', '/crontab/FBA/warehouse_id,返回的结果存在未知warehouseCode,%s', json_encode($unknown_Warehouse));
        }
        echo "执行成功,接口返回结果数:$result_total" . PHP_EOL;
        echo "数据表更新受影响行数:$rows" . PHP_EOL;
    }

    /**
     * FBA更新未匹配到中转仓的sku
     * 默认:
     * 退税的,PURCHASE_WAREHOUSE_FBA_TAX_YES
     * 不退税的,PURCHASE_WAREHOUSE_FBA_TAX_NO
     *
     * @param $skus
     */
    private function update_unmatch_warehouse($skus, $warehouse_id)
    {
//            pr($diff);exit;
        $skus = array_chunk($skus, 200);//where_in 一次200条
        foreach ($skus as $key => $item) {
            if (count($item) > 1) {
                $this->stock->set('purchase_warehouse_id', $warehouse_id);
                $this->stock->where_in('sku', $item);
                $this->stock->update('yibai_fba_sku_cfg');
            }
        }
    }

    /**
     * 更新采购仓库字段
     *
     * @param $arr
     *
     * @return mixed
     */
    private function update_warehouse($arr)
    {
        $this->stock->update_batch('yibai_fba_sku_cfg', $arr, 'sku');
        $rows = $this->stock->affected_rows();

        return $rows;
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
            $starttime = explode(' ', microtime());

            //查询上个步骤是否执行完成
            check_script_state(FBA_STOCK_CFG_LIST);

            $skus = $this->stock->select('sku')->from('yibai_fba_sku_cfg')->group_by('sku')->get()->result_array();

            if (empty($skus)) {
                $message = sprintf('接口:crontab/FBA/update_is_boutique执行失败,查询yibai_fba_sku_cfg表sku为空,');
                log_message('error', $message);
                throw new \Exception($message, 500);
            }
            //优先使用备份表,不存在则使用实时表
            $yibai_product = 'yibai_product_' . date('d');
            if (!$this->product->table_exists($yibai_product)) {
                $yibai_product = 'yibai_product';
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
            $count = $this->stock->update_batch('yibai_fba_sku_cfg', $skus, 'sku');
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
            }
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[FBA_IS_BOUTIQUE];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_IS_BOUTIQUE;
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
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);

            //查询上个步骤是否执行完成
            check_script_state(FBA_STOCK_CFG_LIST);
            $count = 0;
            // 组织数据
            $tran_drawback = ['1' => REFUND_TAX_YES, '0' => REFUND_TAX_NO];
            $update_data   = [];
            $log_info      = [];
            $all_sku       = $this->stock->select('is_refund_tax as isDrawback,sku,id')->group_by('sku')->get('yibai_fba_sku_cfg')->result_array();
            if (empty($all_sku)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,查询结果为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
                exit;
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
                        $TMS_isDrawback      = $tran_drawback[$result[$item['sku']]] ?? REFUND_TAX_NO;
                        $TMS_isDrawback_name = REFUND_TAX[$TMS_isDrawback]['name'];
                        if ($item['isDrawback'] != $TMS_isDrawback) {
                            $update_data[] = [
                                'sku'           => $item['sku'],
                                'is_refund_tax' => $TMS_isDrawback,
                            ];
                            $log_info[]    = [
                                'sku_id'     => $item['id'],
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
            log_message('error', $message);
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[FBA_IS_REFUND_TAX];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_IS_REFUND_TAX;
            record_script_state($params);
        }
        exit;
    }

    public function isDrawback_update($batch_params, $log_info)
    {
        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {

            $this->stock->trans_start();
            $this->stock->update_batch('yibai_fba_sku_cfg', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_fba_stock_log', $log_info[$key]);            //记录日志
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
                $message = sprintf('同步FBA是否退税,已重试3次,数据库操作失败,%s', json_encode($error_info));
                log_message('error', $message);
                throw new \Exception($message, 500);
            }
        }
        return true;
    }

    public function check()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);

        $this->local = $this->load->database('local', true);
        $dev_sku     = $this->stock->select('original_sku')->from('yibai_fba_sku_cfg')->get()->result_array();
        $local_sku   = $this->local->select('original_sku')->from('yibai_fba_sku_cfg')->get()->result_array();
        $diff        = array_diff(array_column($dev_sku, 'original_sku'), array_column($local_sku, 'original_sku'));

        file_put_contents('./diff_sku', print_r($diff, true));
    }


    public function check_oversea()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);

        $this->local = $this->load->database('local', true);
        $dev_sku     = $this->stock->select('CONCAT(sku,station_code) as sku')->from('yibai_oversea_sku_cfg_main')->get()->result_array();
        $local_sku   = $this->local->select('CONCAT(sku,station_code) as sku')->from('yibai_oversea_sku_cfg_main')->get()->result_array();
        $diff        = array_diff(array_column($dev_sku, 'sku'), array_column($local_sku, 'sku'));

        file_put_contents('./diff_sku', print_r($diff, true));
    }

    public function check_oversea_2()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);

        $this->local = $this->load->database('local', true);
        $dev_sku     = $this->stock->select('CONCAT(sku,station_code) as sku')->from('yibai_oversea_sku_cfg_main')->get()->result_array();
        $local_sku   = $this->stock->select('CONCAT(sku,station_code) as sku')->from('yibai_oversea_logistics_list')->get()->result_array();
        $diff        = array_diff(array_column($dev_sku, 'sku'), array_column($local_sku, 'sku'));

        file_put_contents('./diff_sku', print_r($diff, true));
    }

    /**
     * ERPSKU属性配置表
     * erp:yibai_order.yibai_clear_warehouse_sku的is_stop,discount_period_end
     * 同步字段:是否停止加快动销,加快动销结束时间
     */
    public function is_accelerate_sale()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
//
            check_script_state(FBA_STOCK_CFG_LIST);
            $this->order = $this->load->database('yibai_order', TRUE);
            //优先使用备份表,不存在则使用实时表
            $yibai_clear_warehouse_sku           = 'yibai_clear_warehouse_sku_' . date('d');
            $yibai_order_stop_clearwarehouse_sku = 'yibai_order_stop_clearwarehouse_sku_' . date('d');
            if (!$this->order->table_exists($yibai_clear_warehouse_sku)) {
                $yibai_clear_warehouse_sku = 'yibai_clear_warehouse_sku';
            }
            if (!$this->order->table_exists($yibai_order_stop_clearwarehouse_sku)) {
                $yibai_order_stop_clearwarehouse_sku = 'yibai_order_stop_clearwarehouse_sku';
            }

            $number = 0;
            $total  = $this->stock->select('*')->from('yibai_fba_sku_cfg')->count_all_results();
            if (empty($total)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                throw new \Exception($message, 500);
            }
            //取创建时间最新的一条数据
            $sql = "(SELECT b.sku,b.is_stop,order_stop.create_date as 'accelerate_sale_end_time' FROM (SELECT a.sku,a.is_stop,a.discount_period_end,a.stage_id FROM (SELECT sku,is_stop,discount_period_end,stage_id FROM $yibai_clear_warehouse_sku  ORDER BY discount_period_end DESC,discount_period_start DESC) a GROUP BY a.sku) b
LEFT JOIN (SELECT sku,stage_id,create_date FROM $yibai_order_stop_clearwarehouse_sku ORDER BY create_date DESC) order_stop ON
order_stop.sku =b.sku AND order_stop.stage_id = b.stage_id
WHERE is_stop = 1)
 UNION ALL
(SELECT b.sku,b.is_stop,b.discount_period_end as 'accelerate_sale_end_time' FROM (SELECT a.sku,a.is_stop,a.discount_period_end FROM (SELECT sku,is_stop,discount_period_end FROM $yibai_clear_warehouse_sku  ORDER BY discount_period_end DESC,discount_period_start DESC) a GROUP BY a.sku) b WHERE is_stop = 0)";

            $product_map = $this->order->query($sql)->result_array();
            $product_map = array_column($product_map, NULL, 'sku');

            if (empty($product_map)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                throw new \Exception($message, 500);
            }

            $sql        = "SELECT sku,accelerate_sale_end_time,is_accelerate_sale,id FROM yibai_fba_sku_cfg LIMIT ";
            $tran_state = ['0' => '2', '1' => '1'];
            $start      = 1;//第N页减一
            $pageSize   = 3000;
            $page       = ceil($total / $pageSize);
            for ($i = $start; $i <= $page; $i++) {
                $_sql     = $sql . (($i - 1) * $pageSize) . ',' . $pageSize;
                $sku_info = $this->stock->query($_sql)->result_array();
                if (empty($sku_info)) {
                    continue;
                }
                $created_at = date('Y-m-d H:i:s');
                foreach ($sku_info as $key => $item) {
                    if (isset($product_map[$item['sku']])) {//不同的才进行update
                        $is_stop             = $product_map[$item['sku']]['is_stop'];
                        $is_stop             = $tran_state[$is_stop];
                        $is_stop_name        = ACCELERATE_SALE_STATE[$is_stop]['name'];
                        $discount_period_end = $product_map[$item['sku']]['accelerate_sale_end_time'];

                        if ($item['is_accelerate_sale'] != $is_stop || $item['accelerate_sale_end_time'] != $discount_period_end) {
                            $update_info[] = [
                                'sku'                      => $item['sku'],
                                'is_accelerate_sale'       => $is_stop,
                                'accelerate_sale_end_time' => $discount_period_end,
                            ];
                            $log_info[]    = [
                                'sku_id'     => $item['id'],
                                'op_zh_name' => 'system',
                                'context'    => sprintf('修改是否加快动销:%s,加快动销结束时间:%s', $is_stop_name, $discount_period_end),
                                'created_at' => $created_at,
                            ];
                            $number++;
                        }
                    } else {//没有找到对应的sku则状态为'-'
                        if ($item['is_accelerate_sale'] != ACCELERATE_SALE_UNKNOWN) {//如果计划系统这边的状态不是'-',则要进行更新
                            $unknown_time       = '0000-00-00 00:00:00';
                            $is_accelerate_sale = '-';
                            $update_info[]      = [
                                'sku'                      => $item['sku'],
                                'is_accelerate_sale'       => ACCELERATE_SALE_UNKNOWN,
                                'accelerate_sale_end_time' => $unknown_time,
                            ];
                            $log_info[]         = [
                                'sku_id'     => $item['id'],
                                'op_zh_name' => 'system',
                                'context'    => sprintf('修改是否加快动销:%s,加快动销结束时间:%s', $is_accelerate_sale, $unknown_time),
                                'created_at' => $created_at,
                            ];
                        }
                    }
                }
            }
            unset($product_map);
            if (empty($update_info)) {
                $message = sprintf('文件: %s 方法: %s 执行成功,无需更新', __FILE__, __METHOD__);
                throw new \Exception($message, 200);
            }
            $this->update_accelerate_sale($update_info, $log_info);

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s,需更新的记录:%s', $thistime, $number);
            log_message('error', $message);
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $params = FBA_PLAN_SCRIPT[FBA_ACCELERATE_SALE_STATE];
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_ACCELERATE_SALE_STATE;
            record_script_state($params);
            exit;
        }
    }

    public function update_accelerate_sale($batch_params, $log_info)
    {

        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {

            $this->stock->trans_start();
            $this->stock->update_batch('yibai_fba_sku_cfg', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_fba_stock_log', $log_info[$key]);            //记录日志
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
            $this->update_accelerate_sale($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }


    /**
     * 计划系统拉取采购产品数据(v1.2.2)
     * 同步字段:成本价,货源状态,MOQ数量,供应商编码
     * 接口文档:http://192.168.71.156/web/#/84?page_id=3988
     * http://192.168.71.170:1084/crontab/FBA/pur_product_info
     */
    public function pur_product_info()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);

            //查询上个步骤是否执行完成
            check_script_state(FBA_STOCK_CFG_LIST);
            $this->load->model('Fba_model', 'm_fba', false, 'crontab');
            $this->lang->load('common');
            $lang_info = array_column($this->lang->myline('fba_stock_relationship_cfg'), 'label', 'field');
            $count     = 0;
            // 组织数据
            $update_data = [];
            $log_info    = [];
            $all_sku     = $this->stock->select('id,sku,purchase_price,supplier_code,provider_status,moq_qty')->get('yibai_fba_sku_cfg')->result_array();
            if (empty($all_sku)) {
                log_message('error', '执行失败,查询结果为空');
            }

//            $all_sku = array_column($all_sku,'sku');
            //将数据进行分割
            $all_sku = array_chunk($all_sku, 200);
            foreach ($all_sku as $key => $value) {
                $result = $this->m_fba->java_getProductPlanBySku($value);

                if (empty($result)) {
                    continue;
                }
                $result     = array_column($result, NULL, 'sku');
                $created_at = date('Y-m-d H:i:s');
                foreach ($value as $key => $item) {//遍历要匹配的sku
                    if (isset($result[$item['sku']])) {//只更新匹配到的,且是否退税不同的
                        $pur_moq_qty              = $result[$item['sku']]['startingQty'];//MOQ
                        $pur_provider_status      = $result[$item['sku']]['supplyStatus'];//货源状态
                        $pur_provider_status_name = PROVIDER_STATUS[$pur_provider_status]['name']??'';
                        $pur_supplier_code        = $result[$item['sku']]['supplierCode'];//供应商编码
                        $pur_purchase_price       = $result[$item['sku']]['avgPurchaseCost'];//成本价
                        if ($item['moq_qty'] != $pur_moq_qty || $item['provider_status'] != $pur_provider_status || $item['supplier_code'] != $pur_supplier_code || $item['purchase_price'] != $pur_purchase_price) {
                            $update_data[] = [
                                'sku'             => $item['sku'],
                                'moq_qty'         => $pur_moq_qty,
                                'provider_status' => $pur_provider_status,
                                'supplier_code'   => $pur_supplier_code,
                                'purchase_price'  => $pur_purchase_price,
                            ];
                            $log_info[]    = [
                                'sku_id'     => $item['id'],
                                'op_zh_name' => 'system',
                                'context'    => sprintf('修改%s:%s,%s:%s,%s:%s,%s:%s', $lang_info['moq_qty'], $pur_moq_qty, $lang_info['provider_status'], $pur_provider_status_name, $lang_info['supplier_code'], $pur_supplier_code, $lang_info['purchase_price'], $pur_purchase_price),
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

            $this->m_fba->pur_info_update($update_data, $log_info);
            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s', $thistime);
            log_message('error', $message);
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[FBA_PUR_PRODUCT_INFO];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_PUR_PRODUCT_INFO;
            record_script_state($params);
            exit;
        }
    }

    /**
     * 接口文档:http://192.168.71.156/web/#/87?page_id=3999
     * 同步字段:平均库龄
     * //wms返回的code 和计划系统的不一致
     */
    public function avg_inventory_age()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);

            //查询上个步骤是否执行完成
            check_script_state(FBA_STOCK_CFG_LIST);
            $this->load->model('Fba_model', 'm_fba', false, 'crontab');
            $this->lang->load('common');
            $lang_info     = array_column($this->lang->myline('fba_logistics_list'), 'label', 'field');
            $warehouse_map = array_column(PURCHASE_WAREHOUSE, 'w_age_code', 'code');
            $count         = 0;
            $i             = 0;
            $update_data   = [];
            $log_info      = [];
            $res           = [];
            //查询数据
//            $all_sku = $this->stock->select('id,sku,purchase_warehouse_id,avg_inventory_age')->group_by('sku', 'purchase_warehouse_id')
//                ->where_in('sku',['AF00542','AF00543-01','AF00543-02','AF00544-01','AF00544-02','AF00544-03'])
//                ->get('yibai_fba_logistics_list')->result_array();
            $this->load->dbutil();
            $sql      = "SELECT id,sku,purchase_warehouse_id,avg_inventory_age FROM yibai_fba_logistics_list  GROUP BY sku,purchase_warehouse_id;";
            $db_query = $this->stock->query_unbuffer($sql);
            $all_sku  = $this->dbutil->yeild_result($db_query);

            if (empty($all_sku)) {
                $message = '执行失败,查询结果为空';
                log_message('error', $message);
                throw new \Exception($message, 500);
            }

            foreach ($all_sku as $key => $item) {
                //组织数据
                $sku            = $item['sku'];
                $warehouse_code = PURCHASE_WAREHOUSE[$item['purchase_warehouse_id']]['code'];                //计划系统的仓库code

                $data[]         = [
                    'sku'           => $sku,
                    'warehouseCode' => $warehouse_code,
                ];
                $sku_ware       = sprintf('%s%s', $sku, $warehouse_map[$warehouse_code]);
                $old[$sku_ware] = [
                    'avg_inventory_age'     => $item['avg_inventory_age'],
                    'id'                    => $item['id'],
                    'sku'                   => $item['sku'],
                    'purchase_warehouse_id' => $item['purchase_warehouse_id']
                ];
                $i++;
                if ($i > 0 && $i % 200 === 0) {
                    $i      = 0;
                    $result = $this->m_fba->java_avg_inventory_age($data);//调java接口
                    if (!empty($result)) {
                        foreach ($result as $k => $val) {//接口返回数据组织, sku+仓库为key,平均库龄为键值
                            $sku_ware       = sprintf('%s%s', $val['sku'], $val['warehouseCode']);
                            $res[$sku_ware] = $val['inStockDay'];
                        }
                    }

                    $created_at = date('Y-m-d H:i:s');
                    foreach ($data as $k => $row) {//遍历要匹配的sku
                        $sku_ware = sprintf('%s%s', $row['sku'], $warehouse_map[$row['warehouseCode']]);
                        if (isset($res[$sku_ware])) {//只更新匹配到的,且平均库龄不同的
                            $avg_inventory_age = $res[$sku_ware];//接口返回的平均库龄
                            $plan_age          = $old[$sku_ware]['avg_inventory_age'];//计划系统平均库龄

                            if ($plan_age != $avg_inventory_age) {
                                $update_data[] = [
                                    'where'  => [
                                        'sku'                   => $old[$sku_ware]['sku'],
                                        'purchase_warehouse_id' => $old[$sku_ware]['purchase_warehouse_id'],

                                    ],
                                    'update' => [
                                        'avg_inventory_age' => $avg_inventory_age
                                    ]
                                ];
                                $log_info[]    = [
                                    'log_id'     => $old[$sku_ware]['id'],
                                    'op_zh_name' => 'system',
                                    'context'    => sprintf('修改%s:%s', $lang_info['avg_inventory_age'], $avg_inventory_age),
                                    'created_at' => $created_at,
                                ];
                            }
                            unset($old[$sku_ware]);
                        }
                    }
                    $res  = [];
                    $data = [];
                    $old  = [];
                }
            }
            unset($all_sku);
            if ($i > 0) {
                $result = $this->m_fba->java_avg_inventory_age($data);//调java接口
                if (!empty($result)) {
                    foreach ($result as $k => $val) {//接口返回数据组织, sku+仓库为key,平均库龄为键值
                        $sku_ware       = sprintf('%s%s', $val['sku'], $val['warehouseCode']);
                        $res[$sku_ware] = $val['inStockDay'];
                    }
                }
                $created_at = date('Y-m-d H:i:s');
                foreach ($data as $k => $row) {//遍历要匹配的sku
                    $sku_ware = sprintf('%s%s', $row['sku'], $warehouse_map[$row['warehouseCode']]);
                    if (isset($res[$sku_ware])) {//只更新匹配到的,且平均库龄不同的
                        $avg_inventory_age = $res[$sku_ware];//接口返回的平均库龄
                        $plan_age          = $old[$sku_ware]['avg_inventory_age'];//计划系统平均库龄

                        if ($plan_age != $avg_inventory_age) {
                            $update_data[] = [
                                'where'  => [
                                    'sku'                   => $old[$sku_ware]['sku'],
                                    'purchase_warehouse_id' => $old[$sku_ware]['purchase_warehouse_id'],

                                ],
                                'update' => [
                                    'avg_inventory_age' => $avg_inventory_age
                                ]
                            ];
                            $log_info[]    = [
                                'log_id'     => $old[$sku_ware]['id'],
                                'op_zh_name' => 'system',
                                'context'    => sprintf('修改%s:%s', $lang_info['avg_inventory_age'], $avg_inventory_age),
                                'created_at' => $created_at,
                            ];
                        }
                        unset($old[$sku_ware]);
                    }
                }
                unset($res);
                unset($old);
                unset($result);
                $data = [];
            }

            $this->m_fba->avg_inventory_age_update($update_data, $log_info);

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            echo $thistime;
            $message  = sprintf('ok,执行耗时:%s', $thistime);
            log_message('error', $message);
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[FBA_AVG_INVENTORY_AGE];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_AVG_INVENTORY_AGE;
            record_script_state($params);
            exit;
        }
    }


    /**
     * 检查yibai_fba_logistics_lis 和 yibai_fba_pan_eu
     * 判断出是否泛欧
     */
    public function check_is_pan_eu()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);

            //查询上个步骤是否执行完成
            check_script_state(FBA_LOGISTICS_CFG_LIST_UP);
            $this->load->model('Fba_model', 'm_fba', false, 'crontab');
            $this->lang->load('common');
            $station_map  = ['sp' => 'es'];
            $data         = [];
            $this->system = $this->load->database('yibai_system', true);
            //处理删除失败的情况,存在泛欧未合并的情况
            if (!$this->m_fba->check_del_data()) {
                throw new \Exception('存在异常数据,泛欧未合并的情况', 500);
            }

            $sql          = "SELECT id,account_name FROM yibai_amazon_account";
            $result       = $this->system->query($sql)->result_array();
            if (!empty($result)) {
                $account_name_map = array_column($result, 'account_name', 'id');
            }

            $db     = $this->m_fba->getDatabase();
            $sql    = "SELECT tag,account_id FROM yibai_fba_pan_eu  GROUP BY tag,account_id";
            $result = $db->query($sql)->result_array();
            if (!empty($result)) {
                foreach ($result as $key => $item) {
                    $tags[$item['tag']][] = $item['account_id'];

                }
            }


            $sql    = "SELECT MD5(CONCAT(sku,seller_sku,fnsku,asin,account_num)) as tag,id,account_id,site FROM yibai_fba_logistics_list WHERE pan_eu =1";
            $result = $db->query($sql)->result_array();
            if (!empty($result)) {
                $data = array_column($result, NULL, 'id');
            }
            $data = array_chunk($data, 1000);
            foreach ($data as $i => $value) {
                foreach ($value as $key => $item) {
                    if (!isset($tags[$item['tag']])) {
                        $no_pan_eu[] = [
                            'id'           => $item['id'],
                            'account_name' => $account_name_map[$item['account_id']]??'',
                            'station_code' => $station_map[$item['site']]??$item['site'],
                            'pan_eu'       => 2
                        ];
                    } elseif (isset($tags[$item['tag']]) && count($tags[$item['tag']]) == 1 && $tags[$item['tag']][0] == $item['account_id']) {
                        $no_pan_eu[] = [
                            'id'           => $item['id'],
                            'account_name' => $account_name_map[$item['account_id']]??'',
                            'station_code' => $station_map[$item['site']]??$item['site'],
                            'pan_eu'       => 2
                        ];
                    }
                }
                if (!empty($no_pan_eu)) {
                    $db->update_batch('yibai_fba_logistics_list', $no_pan_eu, 'id');
                }
            }

            $sql    = "SELECT MD5(CONCAT(sku,seller_sku,fnsku,asin,account_num)) as tag,id,account_num,site,account_id FROM yibai_fba_logistics_list WHERE pan_eu =2 and site IN ('de', 'fr', 'it', 'sp', 'uk')";
            $result = $db->query($sql)->result_array();
//            print_r($result);exit;
            if (!empty($result)) {
                $data = array_column($result, NULL, 'id');
            }
            $data = array_chunk($data, 1000);
            foreach ($data as $i => $value) {
                foreach ($value as $key => $item) {

                    if (isset($tags[$item['tag']])) {
                        $all = $tags[$item['tag']];
                        if (count($all) == 1 && $all[0] == $item['account_id']) {
                            continue;
                        } else {
                            $pan_eu[] = [
                                'id'           => $item['id'],
                                'account_name' => $item['account_num'] . '欧洲',
                                'station_code' => 'eu',
                                'pan_eu'       => 1
                            ];
                        }
                    }
                }
//                pr($no_pan_eu);exit;
                if (!empty($pan_eu)) {
                    $db->update_batch('yibai_fba_logistics_list', $pan_eu, 'id');
                }
            }

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s', $thistime);
            log_message('error', $message);
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[FBA_CHECK_PAN_EU];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_CHECK_PAN_EU;
            record_script_state($params);
            exit;
        }
    }

    /**
     * 更新供货周期字段
     */
    public function update_lead_time()
    {
        try {
            $starttime = explode(' ', microtime());
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);

            $last_step = FBA_SKU_STATE_A;
            $step      = FBA_SKU_STATE_B;
            //查询上个步骤是否执行完成
            check_script_state($last_step);

            $number = 0;
            $total  = $this->stock->select('*')->from('yibai_fba_sku_cfg')->count_all_results();
            if (empty($total)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }

            $sql    = "SELECT sku,arrival_time FROM yibai_fba_lead_time WHERE last_update_time=CURDATE()";
            $lt_map = $this->stock->query($sql)->result_array();
            $lt_map = array_column($lt_map, 'arrival_time', 'sku');

            if (empty($lt_map)) {
                $message = sprintf('文件: %s 方法: %s 执行失败,表数据为空', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 500);
            }

            $sql = "SELECT sku,lt,id FROM yibai_fba_sku_cfg LIMIT ";

            $start    = 1;//第N页减一
            $pageSize = 5000;
            $page     = ceil($total / $pageSize);
            for ($i = $start; $i <= $page; $i++) {
                $_sql     = $sql . (($i - 1) * $pageSize) . ',' . $pageSize;
                $sku_info = $this->stock->query($_sql)->result_array();
                if (empty($sku_info)) {
                    continue;
                }
                $created_at = date('Y-m-d H:i:s');
                foreach ($sku_info as $key => $item) {
                    if (isset($lt_map[$item['sku']])) {//不同的才进行update
                        $lt = $lt_map[$item['sku']]??20;
                        if ($item['lt'] != $lt) {
                            $update_info[] = [
                                'sku' => $item['sku'],
                                'lt'  => $lt
                            ];
                            $log_info[]    = [
                                'sku_id'     => $item['id'],
                                'op_zh_name' => 'system',
                                'context'    => sprintf('修改供货周期:%s', $lt),
                                'created_at' => $created_at,
                            ];
                            $number++;
                        }
                    }
                }
            }
            unset($lt_map);
            if (empty($update_info)) {
                $message = sprintf('文件: %s 方法: %s 执行成功,无需更新', __FILE__, __METHOD__);
                log_message('error', $message);
                throw new \Exception($message, 200);
            }
            $this->load->model('Fba_model', 'm_fba', false, 'crontab');

            $this->m_fba->update_lt($update_info, $log_info);

            //程序运行时间
            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s,需更新的记录:%s', $thistime, $number);
            log_message('error', $message);
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[$step];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = $step;
            record_script_state($params);
            exit;
        }
    }


    public function clean_lead_time()
    {
        $this->load->model('Fba_model', 'm_fba', false, 'crontab');

        $this->m_fba->clean_history_lt();
    }

    /**
     * 对比计划系统和新库存表的seller_sku+account_id
     */
    public function data_compare()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        $this->load->model('Fba_model', 'm_fba', false, 'crontab');
        $this->m_fba->data_compare();
    }

    public function sku_map_compare()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        $this->load->model('Fba_model', 'm_fba', false, 'crontab');
        $this->m_fba->sku_map_compare();
    }

    public function test_field()
    {
        $params = $this->compatible('get');
        $this->load->model('Fba_model', 'm_fba', false, 'crontab');
        $this->m_fba->sku_map_compare($params);
    }


    public function ttt()
    {
        try {
            if (true) {
                throw new \Exception('执行成功', 200);

            }
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[FBA_STOCK_CFG_LIST];

            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = FBA_STOCK_CFG_LIST;
            record_script_state($params);
            exit;
        }
    }

    /**
     * 对比全量表进行增删操作,
     * 拉取产品名称字段
     * @author Manson
     */
    public function compare_all_data()
    {
        try {
            ini_set('memory_limit', '3072M');
            ini_set('max_execution_time', '0');
            set_time_limit(0);
            $this->load->model('Fba_model', 'm_fba', false, 'crontab');
            $starttime = explode(' ', microtime());
            $step      = FBA_LOGISTICS_CFG_LIST_UP;

            while (true) {
                //1.要删除的数据
                $sql = "SELECT a.id FROM yibai_fba_logistics_list a
LEFT JOIN yibai_fba_logistics_list_all b
ON a.`account_id` = b.`account_id` AND a.`seller_sku` = b.`seller_sku`
AND a.`sku` = b.`sku` AND a.`fnsku` = b.`fnsku` AND a.`asin` = b.`asin`
AND a.`station_code` = b.`station_code`
WHERE b.id is null;";
                $ids = $this->stock->query($sql)->result_array();
                if (!empty($ids)) {
                    $ids = array_column($ids, 'id');
                    $this->m_fba->del_logistics_list($ids);
                } else {
                    break;
                }
            }
            unset($ids);
            echo '删除历史数据,执行完毕;';

            while (true) {
                //2.要新增的数据
                $sql      = "SELECT a.account_num, a.site, a.pan_eu,a.sale_group_id,a.sale_group_zh_name,a.salesman_id,a.salesman_zh_name,a.account_id,
a.account_name,a.seller_sku,a.sku,a.fnsku,a.asin,a.station_code,a.approve_state,a.created_at,a.original_sku FROM yibai_fba_logistics_list_all a
LEFT JOIN yibai_fba_logistics_list b
ON a.`account_id` = b.`account_id` AND a.`seller_sku` = b.`seller_sku`
AND a.`sku` = b.`sku` AND a.`fnsku` = b.`fnsku` AND a.`asin` = b.`asin`
AND a.`station_code` = b.`station_code`
WHERE b.id is null LIMIT 500;";
                $add_data = $this->stock->query($sql)->result_array();
                if (!empty($add_data)) {
                    $this->stock->insert_ignore_batch('yibai_fba_logistics_list', $add_data);
                } else {
                    break;
                }
            }
            unset($add_data);
            echo '新增数据,执行完毕;';

            while (true) {
                //3.处理产品名称
                $skus = $this->stock->select('sku')->where('sku_name', '')->limit(200)->get('yibai_fba_logistics_list')->result_array();
                if (!empty($skus)) {
                    $skus     = array_column($skus, 'sku');
                    $sku_name = $this->m_fba->get_sku_name($skus);
                    foreach ($sku_name as $key => &$item) {
                        $item['sku_name'] = empty($item['sku_name']) ? '-' : $item['sku_name'];
                    }
                    $this->stock->update_batch('yibai_fba_logistics_list', $sku_name, 'sku');
                } else {
                    break;
                }
            }
            echo '处理为空的产品名称,执行完毕;';

            $endtime  = explode(' ', microtime());
            $thistime = $endtime[0] + $endtime[1] - ($starttime[0] + $starttime[1]);
            $thistime = round($thistime, 3);
            $message  = sprintf('ok,执行耗时:%s;', $thistime);
            log_message('error', $message);
            echo $message;
            $code = 200;
        } catch (\Throwable $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            $params = FBA_PLAN_SCRIPT[$step];
            if (isset($errorMsg) && $code != 200) {
                $params['message'] = $errorMsg;
                $params['status']  = 2;
            }
            if (isset($message) && $code == 200) {
                $params['message'] = $message;
                $params['status']  = 1;
            }
            $params['step'] = $step;
            record_script_state($params);
            exit;
        }
        exit;

    }
}