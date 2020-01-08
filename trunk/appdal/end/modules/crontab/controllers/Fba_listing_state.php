<?php
///*
///**
// *  拿计划系统的数据去erp上匹配如果是已停售，则lisitng_state改为清仓品3
// *
// * 将erp上status为2的sku查出来,
// * 将计划系统里的sku查出来,in_array(计划,erp)
// */
//class Fba_listing_state extends MY_Controller
//{
//
//    public function __construct()
//    {
//        parent::__construct();
//        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
//        $this->stock   = $this->load->database('stock', TRUE);//计划系统数据库
//        $this->_debug = $this->input->post_get('debug') ?? '';
//    }
//
//    private function show_log($str)
//    {
//        if (!empty($this->_debug)) {
//            echo $str;
//        }
//    }
//
//    public function updateListingState(){
////        die('此接口暂停使用');
//        ini_set('memory_limit', '3072M');
//        ini_set('max_execution_time', '0');
//        set_time_limit(0);
//        $sql = "SELECT map.sku FROM yibai_amazon_listing_alls ls LEFT JOIN yibai_amazon_sku_map map ON ls.seller_sku = map.seller_sku AND ls.account_id = map.account_id
//WHERE ls.status=2";
//        //erp
//        $erp_sku = $this->product->query($sql)->result_array();//status=2的sku
//        $erp_sku = array_column($erp_sku,'sku');
//        $erp_sku = array_values(array_flip(array_flip(array_filter($erp_sku))));
//
//        //plan
//        $plan_sku = $this->stock->select('original_sku')->get('yibai_fba_logistics_list')->result_array();
//        $plan_sku = array_column($plan_sku,'original_sku');
//        $plan_sku = array_values(array_flip(array_flip(array_filter($plan_sku))));
//
//        //交集
//        $update_sku = array_intersect($erp_sku,$plan_sku);
//        $number = count($update_sku);
//
//        //分批
//        $update_sku    = array_chunk($update_sku, 500);
//        $size          = count($update_sku);
//        $affected_rows = 0;
//        for ($i=0;$i<$size;$i++){
//            $this->stock->where_in('original_sku',$update_sku[$i]);
//            $this->stock->update('yibai_fba_logistics_list',['listing_state'=>3]);
//            $this->show_log( "----------$i------------".PHP_EOL);
//            $a = $this->stock->affected_rows();
//            $affected_rows += $a;
//            $this->show_log( '受影响行数:'.$a.PHP_EOL);
//        }
//        $this->show_log( "--------end--------".PHP_EOL);
//        $this->show_log( "总受影响行数: ".$affected_rows);
//        $this->show_log( '需要更新的sku:'.$number.PHP_EOL);
//    }
//}*/