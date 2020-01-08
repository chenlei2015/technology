<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/9/6
 * Time: 10:57
 */
class Fba_model extends MY_Model
{

    public function __construct()
    {
        parent::__construct();
        $this->stock = $this->load->database('stock', TRUE);//计划系统数据库
        $this->tag1  = 0;
    }

    /**
     * java接口,是否退税
     * http://192.168.71.156/web/#/73?page_id=3649
     */
    public function java_getProductPlanBySku($paramsList = [])
    {
        $this->load->helper('common');
        if (empty($paramsList)) {
            return [];
        }
        $all_skus = array_column($paramsList, 'sku');
        $result   = RPC_CALL('YB_PUR_PRODUCT_INFO', $all_skus);
//        pr($result);exit;

        if (empty($result) || !isset($result['code'])) {
            log_message('ERROR', '请求地址：/procurement/purProduct/getProductPlanBySku,无返回结果');

            return [];
        }

        if ($result['code'] == 500 || $result['code'] == 0) {
            log_message('ERROR', sprintf('请求地址：/procurement/purProduct/getProductPlanBySku,异常：%s', json_encode($result)));

            return [];
        }
//        pr($result);exit;
        if (isset($result['data']) && !empty($result['data'])) {
            return $result['data'];
        } else {
            log_message('ERROR', sprintf('请求地址：/procurement/purProduct/getProductPlanBySku,异常：%s', json_encode($result)));

            return [];
        }
    }

    public function pur_info_update($batch_params, $log_info)
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
            $this->pur_info_update($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('同步FBA是否退税,已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }

    public function java_avg_inventory_age($paramsList)
    {
        $this->load->helper('common');
        if (empty($paramsList)) {
            return [];
        }

//echo (json_encode($paramsList));exit;
        $result = RPC_CALL('YB_WMS_STOCK_DAY', $paramsList);
//        pr($result);exit;
        if (empty($result) || !isset($result['code'])) {
            log_message('ERROR', '请求地址：/mrp/wmsStock/getWmsStockDay,无返回结果');

            return [];
        }

        if ($result['code'] == 500 || $result['code'] == 0) {
            log_message('ERROR', sprintf('请求地址：/mrp/wmsStock/getWmsStockDay,异常：%s', json_encode($result, JSON_UNESCAPED_UNICODE)));

            return [];
        }
//        pr($result);exit;
        if (isset($result['data']) && !empty($result['data'])) {
            return $result['data'];
        } else {
            if ($result['code'] != 200) {
                log_message('ERROR', sprintf('请求地址：/mrp/wmsStock/getWmsStockDay,异常：%s', json_encode($result, JSON_UNESCAPED_UNICODE)));
            }
            return [];
        }
    }

    public function avg_inventory_age_update($batch_params, $log_info)
    {
        if (empty($batch_params)) {
            return;
        }

        $error_info   = [];
        $batch_params = array_chunk($batch_params, 200);
        $log_info     = array_chunk($log_info, 200);

        foreach ($batch_params as $key => $item) {
            $this->stock->trans_start();
            $this->stock->update_batch_more_where('yibai_fba_logistics_list', $item, 'sku');            //修改状态
            $this->stock->insert_batch('yibai_fba_logistics_list_log', $log_info[$key]);            //记录日志
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
            $this->avg_inventory_age_update($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }


    public function update_lt($batch_params, $log_info)
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
            $this->update_lt($error_info['update'], $error_info['insert']);
            $this->tag1++;
            if ($this->tag1 >= 3) {
                log_message('error', sprintf('同步更新供货周期,已重试3次,数据库操作失败,%s', json_encode($error_info)));

                return true;
            }
        }

        return true;
    }

    /**
     * 清空历史的数据
     */
    public function clean_history_lt()
    {
        while (true) {
            if ($this->tag1 > 3) {
                log_message('error', '清空表yibai_fba_lead_time失败');
                exit;
            }

            $sql = "DELETE FROM yibai_fba_lead_time WHERE last_update_time < date_sub(curdate(),interval 1 day)";

            if ($this->stock->query($sql)) {
                break;
            }
            $this->tag1++;
            sleep(5);
        }
    }

    public function selectByID()
    {
        $this->stock->select(md5())->from(0);
    }

    public function data_compare()
    {
//        file_put_contents('$all_data.txt',print_r($result,true));exit;
        // `account_id`,`seller_sku`,`sku`,`fnsku`,`asin`,`station_code`

        $this->stock = $this->load->database('test', TRUE);//计划系统数据库
        //前置条件
        $delete_data = [];
        $plan_data   = [];
        //组织计划系统的数据  因为泛欧的数据在计划系统这里进行了合并, 取pan_eu表的数据
        $sql = "SELECT md5(CONCAT(seller_sku,account_num,fnsku,asin,sku)) tag FROM yibai_fba_logistics_list";

        $result = $this->stock->query($sql)->result_array();
        if (!empty($result)) {
            $plan_data = array_column($result, 'tag');
        }
        $this->myplan = $this->load->database('myplan', TRUE);//计划系统数据库

        $result = $this->myplan->query($sql)->result_array();
        if (!empty($result)) {
            $test_data = array_column($result, 'tag', 'id');
        }

//        $plan_data = array_unique($plan_data);
//        //erp跑出维度后的数据
//        $result = $this->get_erp_data();

//        $diff = array_diff($plan_data, $test_data);
        $diff = array_diff($test_data, $plan_data);

        //查询seller_sku+account_id 通过id
//        $this->stock-
//        $diff = array_diff($result, $plan_data);
        file_put_contents('./$diff.txt', print_r($diff, true));
        exit;

        //        if (!empty($result)) {
//            $erp_data = array_column($result, 'id', 'tag');
////            $erp_data = array_column($result, 'tag', 'id');
//        }

        /*        $diff     = array_diff($plan_data, $erp_data);
        //        $add_data = array_column(array_flip($diff));
                file_put_contents('./$diff.txt', print_r($diff, true));exit;*/

        //先拿计划系统的数据去库存表的数据里判断是否存在,不存在的则为要删除的数据
        foreach ($plan_data as $key => $item) {
            if (!isset($erp_data[$item])) {
                //记录要删除的数据,去除标记
                $delete_data[] = $key;
                unset($plan_data[$key]);
            }
            /*            if (!in_array($item, $erp_data)) {
                            //记录要删除的数据,去除标记
                            $delete_data[] = $key;
                            unset($plan_data[$key]);
                        }*/
        }

        file_put_contents('./$delete_data.txt', print_r($delete_data, true));
        unset($delete_data);
        $erp_data = array_flip($erp_data);
        //数组比较出不同数据,则为新增的数据
        $diff = array_diff($erp_data, $plan_data);


//        $add_data = array_column(array_flip($diff));
        file_put_contents('./$diff.txt', print_r($diff, true));
//        file_put_contents('./$add_data.txt', print_r($add_data, true));
        //数据库操作
    }

    /**
     * 二维数组转一维数组,将sku拆分,去重,去除空
     *
     * @param $result
     *
     * @return array
     */

    function splitSku($sku_list)
    {
        $string = str_replace([';', ',', '+'], "[[[", $sku_list);//字符串换掉 拆分出多个sku
        $data   = explode('[[[', $string);//转回数组

        return $data;
    }

    function getFilterSku($sku)
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

    public function get_erp_data()
    {
        $emp                                  = 0;
        $yibai_product                        = 'yibai_product_' . date('d');
        $yibai_product_combine                = 'yibai_product_combine_' . date('d');
        $yibai_amazon_sku_map                 = 'yibai_amazon_sku_map_' . date('d');
        $yibai_amazon_fba_inventory_month_end = 'yibai_amazon_fba_inventory_month_end';
        $yibai_amazon_account                 = 'yibai_amazon_account_' . date('d');
        $yibai_amazon_group                   = 'yibai_amazon_group_' . date('d');
        $db_product                           = $this->load->database('yibai_product', TRUE);//计划系统数据库
        $db_system                            = $this->load->database('yibai_system', TRUE);//计划系统数据库
        $product_type_2_sku                   = [];
        $product_type_1_sku                   = [];
        $all_data                             = [];
        //3.先查出product_type=2需要转换的原sku 对yibai_amazon_sku_map应  真实的 sku
        $sql = "SELECT t.sku as original_sku,p1.sku from $yibai_product as p1
            INNER JOIN (
                SELECT p.sku,pc.product_id from $yibai_product p
                LEFT JOIN $yibai_product_combine pc on pc.product_combine_id=p.id
                where p.product_type=2
            ) t on t.product_id = p1.id;";

        $result = $db_product->query($sql)->result_array();
        foreach ($result as $key => $item) {
            $product_type_2_sku[$item['original_sku']][] = $item['sku'];
        }


        //4.查找product_type=1的，因为下边需要一一判断
        $sql                = "SELECT sku from $yibai_product where product_type=1;";
        $result             = $db_product->query($sql)->result_array();
        $product_type_1_sku = array_column($result, 'sku', 'sku');


        //5.获取账号id和account_num
        $sql             = "SELECT id,account_num FROM $yibai_amazon_account";
        $result          = $db_system->query($sql)->result_array();
        $account_num_map = array_column($result, 'account_num', 'id');

        //2.获取所有账号名称
        $account_arr = [];
        $sql         = "SELECT a.id,a.site as station_code
            from $yibai_amazon_account as a
            LEFT JOIN $yibai_amazon_group as g on a.group_id = g.group_id
            where a.account_name!='' and  a.account_name is not null;";

        $result      = $db_system->query($sql)->result_array();
        $account_arr = array_column($result, 'station_code', 'id');

        //组织yibai_amazon_fba_inventory表的数据
        $sql = " SELECT * FROM  (select a.sku as seller_sku,a.account_id,a.asin,a.fnsku,b.sku,c.sku as product_sku,c.product_type
from $yibai_amazon_fba_inventory_month_end a
LEFT JOIN $yibai_amazon_sku_map b on a.account_id=b.account_id and a.sku=b.seller_sku
LEFT JOIN $yibai_product c on b.sku=c.sku
where a.`month` = CURDATE() and  b.sku!='null' and b.sku!='' and  b.sku!='#N/A' and  b.sku!='#REF!' and b.sku is not null
and a.account_id is not null and a.sku is not null
and a.asin is not null and a.fnsku is not null
-- and a.sku = 'JM01896HJL-SEE-Wwz-05-FBA'
) t  ORDER BY t.account_id DESC";
        //分批查询,总记录数
        $pass_station_code = ['de', 'fr', 'it', 'sp', 'uk'];
        $pageSize          = 100000;
        $count_sql         = sprintf('SELECT  count(*) as total FROM (%s) AS a ', $sql);

        $result = $db_product->query($count_sql)->row_array();
        $page   = 1;
        $total  = $result['total']??'';
        if (!empty($total)) {
            $page = ceil($total / $pageSize);
        }
        for ($i = 1; $i <= $page; $i++) {
            $_sql = $sql . ' LIMIT ' . (($i - 1) * $pageSize) . ',' . $pageSize;
//            echo $_sql;exit;
            $result = $db_product->query($_sql)->result_array();

            foreach ($result as $key => $row) {
                $original_sku = $row['sku'];
                $asin         = $row['asin'];
                $fnsku        = $row['fnsku'];
                $product_sku  = $row['product_sku'];
                $seller_sku   = $row['seller_sku'];
                $account_id   = $row['account_id'];
                $account_num  = $account_num_map[$account_id];
                if (isset($account_arr[$row['account_id']])) {//账号id是否存在
                    $station_code = $account_arr[$row['account_id']]?? '';//不能为空
                } else {
                    $station_code = '';
                }


                if (empty($account_id) || empty($seller_sku) || empty($original_sku) || empty($asin) || empty($fnsku) || empty($station_code)) {
                    continue;
                }


                if (in_array($station_code, $pass_station_code)) {//如果是德意法西英的才会走过滤
                    //将该站点抓取的数据为欧洲站点
                    $station_code = 'eu';
                }


                $skus = [];
                //第一层，原sku存在主表直接可用
                if ($row['product_type'] == 1) {
                    $skus[] = $product_sku;
                } elseif ($row['product_type'] == 2) {
                    //判断中转的是否存在
                    if (isset($product_type_2_sku[$product_sku])) {
                        foreach ($product_type_2_sku[$product_sku] as $k => $item) {
                            if (!empty($item)) {
                                $skus[] = $item;
                            }
                        }
                    } else {//type=2没有匹配到
                        $skus[] = $product_sku;

                    }
                } else {//第一层就不存在表product中
                    //拆
                    $sku   = trim($original_sku);
                    $_skus = $this->splitSku($sku);//可能包含;,+要拆解
                    foreach ($_skus as $_sku) {
                        if (isset($product_type_1_sku[$_sku])) {//判断是否存在type_1中
                            $skus[] = $product_type_1_sku[$_sku];
                        } elseif (isset($product_type_2_sku[$_sku])) {//判断是否在中转中
                            foreach ($product_type_2_sku[$_sku] as $k => $item) {
                                if (!empty($item)) {
                                    $skus[] = $item;
                                }
                            }
                        } else {//证明不存在表product中，不需拆，过滤*
                            $_sku = $this->getFilterSku($_sku);
                            //再次判断是否存在product中
                            if (isset($product_type_1_sku[$_sku])) {
                                $skus[] = $product_type_1_sku[$_sku];
                            } elseif (isset($product_type_2_sku[$_sku])) {//判断是否在中转中
                                foreach ($product_type_2_sku[$_sku] as $k => $item) {
                                    if (!empty($item)) {
                                        $skus[] = $item;
                                    }
                                }
                            } else {
                                $emp++;
                                continue;
                            }
                        }
                    }
                }

                foreach ($skus as $_sku) {
                    $_sku = trim($_sku);
                    if (empty($_sku)) {
                        $emp++;
                        continue;
                    }

                    $key = md5($account_id . '_' . $seller_sku . '_' . $_sku . '_' . $fnsku . '_' . $asin . '_' . $station_code);
                    if (isset($unique_cache[$key])) {
                        continue;
                    }
                    $unique_cache[$key] = 1;

                    //通过sku获取的字段

                    $all_data[] = md5(sprintf('%s%s%s%s%s', $seller_sku, $account_num, $fnsku, $asin, $_sku));
                }
            }
        }

//        $all_data = array_unique($all_data);
        return $all_data;
    }


    public function sku_map_compare()
    {
        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
        $sql           = "SELECT md5(CONCAT(seller_sku,account_id)) tag FROM yibai_amazon_sku_map_24";

        $result = $this->product->query($sql)->result_array();

        if (!empty($result)) {
            foreach ($result as $key => $item) {
                $new_data[$item['tag']] = 1;
                unset($result[$key]);
            }
        }

        $sql    = "SELECT md5(CONCAT(seller_sku,account_id)) tag,seller_sku,account_id,id FROM yibai_amazon_sku_map_23";
        $result = $this->product->query($sql)->result_array();
        if (!empty($result)) {
            $old_data = array_column($result, NULL, 'tag');
        }
        echo 123;
        foreach ($old_data as $key => $item) {
            if (!isset($new_data[$key])) {
                $del_data[] = [
                    'seller_sku' => $item['seller_sku'],
                    'account_id' => $item['account_id'],
                ];
            }
            unset($old_data);
        }
        echo 456;
        $this->stock->insert_batch('sku_map_del', $del_data);
        exit;

        //erp跑出维度后的数据
        $this->get_erp_data();
        //        if (!empty($result)) {
//            $erp_data = array_column($result, 'id', 'tag');
////            $erp_data = array_column($result, 'tag', 'id');
//        }

        /*        $diff     = array_diff($plan_data, $erp_data);
        //        $add_data = array_column(array_flip($diff));
                file_put_contents('./$diff.txt', print_r($diff, true));exit;*/

        //先拿计划系统的数据去库存表的数据里判断是否存在,不存在的则为要删除的数据
        foreach ($plan_data as $key => $item) {
            if (!isset($erp_data[$item])) {
                //记录要删除的数据,去除标记
                $delete_data[] = $key;
                unset($plan_data[$key]);
            }
            /*            if (!in_array($item, $erp_data)) {
                            //记录要删除的数据,去除标记
                            $delete_data[] = $key;
                            unset($plan_data[$key]);
                        }*/
        }

        file_put_contents('./$delete_data.txt', print_r($delete_data, true));
        unset($delete_data);
        $erp_data = array_flip($erp_data);
        //数组比较出不同数据,则为新增的数据
        $diff = array_diff($erp_data, $plan_data);


//        $add_data = array_column(array_flip($diff));
        file_put_contents('./$diff.txt', print_r($diff, true));
    }


    public function test_field($params)
    {
        $result = $this->filterNotExistFields($params);
    }
    public function check_del_data()
    {
        while (true) {
            $sql      = "SELECT * FROM yibai_fba_logistics_list WHERE site IN ('de', 'fr', 'it', 'sp', 'uk') GROUP BY sku,seller_sku,asin,fnsku,account_num HAVING count(*)>1";
            $del_data = $this->stock->query($sql)->result_array();
            if (!empty($del_data)) {
                $sql    = "select column_name from information_schema.columns where table_name='yibai_fba_logistics_list_del' and table_schema='yibai_plan_stock'";
                $result = $this->stock->query($sql)->result_array();
                foreach ($result as $key => $row) {
                    if ($row['column_name'] == 'del_time') {
                        continue;
                    }
                    $column_name[] = $row['column_name'];
                }
                $ids         = array_column($del_data, 'id');
                $ids         = implode(',', $ids);
                $column_name = implode(',', $column_name);
                $this->stock->trans_start();
                $sql = "INSERT INTO yibai_fba_logistics_list_del ($column_name) SELECT $column_name  from yibai_fba_logistics_list WHERE id in ($ids)";
                $this->stock->query($sql);
                $sql = "DELETE FROM yibai_fba_logistics_list WHERE id in ($ids);";//删除
                $this->stock->query($sql);
                $this->stock->trans_complete();
                if ($this->stock->trans_status() === false) {
                    sleep(3);
                    $this->tag1++;
                } else {
                    break;
                }
                if ($this->tag1 == 10) {
                    return false;
                }
            } else {
                break;
            }
        }

        return true;
    }


    /**
     * 将要删除的记录保存到del表后删除记录
     * @author Manson
     * @param $ids
     * @return bool
     */
    public function del_logistics_list($ids)
    {
        while (true) {
            if (!empty($ids)) {
                //1.删除记录表 字段
                $sql    = "select column_name from information_schema.columns where table_name='yibai_fba_logistics_list_del' and table_schema='yibai_plan_stock'";
                $result = $this->stock->query($sql)->result_array();
                foreach ($result as $key => $row) {
                    if ($row['column_name'] == 'del_time') {
                        continue;
                    }
                    $column_name[] = $row['column_name'];
                }
                //2. 需要删除的记录ids
                $ids         = implode(',', $ids);
                $column_name = implode(',', $column_name);
                $this->stock->trans_start();
                $sql = "INSERT INTO yibai_fba_logistics_list_del ($column_name) SELECT $column_name  from yibai_fba_logistics_list WHERE id in ({$ids})";
                $this->stock->query($sql);
                $sql = "DELETE FROM yibai_fba_logistics_list WHERE id in ($ids);";//删除
                $this->stock->query($sql);
                $this->stock->trans_complete();
                if ($this->stock->trans_status() === false) {
                    sleep(3);
                    $this->tag1++;
                } else {
                    break;
                }
                if ($this->tag1 == 10) {
                    return false;
                }
            } else {
                return false;
            }
        }

        return true;
    }


    /**
     * 通过sku查询产品名称
     * @author Manson
     * @param array $skus
     * @return mixed
     */
    public function get_sku_name($skus = [])
    {
        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
        //优先使用备份表,不存在则使用实时表
        $yibai_product = 'yibai_product_' . date('d');
        if (!$this->product->table_exists($yibai_product)) {
            $yibai_product = 'yibai_product';
        }

        $result = $this->product->select('p.sku,descrip.title as sku_name')
            ->from($yibai_product . ' p')
            ->join('yibai_product_description descrip', 'descrip.sku=p.sku AND descrip.language_code="Chinese"', 'left')
            ->where_in('p.sku', $skus)
            ->get()
            ->result_array();

        return $result;
    }
}