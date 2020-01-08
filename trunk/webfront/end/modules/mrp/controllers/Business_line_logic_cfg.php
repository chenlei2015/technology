<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/5/20
 * Time: 17:23
 */
class Business_line_logic_cfg Extends MY_Controller
{
    private $_server_module = 'mrp/Business_line_logic_cfg/';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 列表
     */
    public function list()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get      = $this->input->get();
        if (!isset($get['business_line']) || empty($get['business_line'])) {
            $get['business_line'] = 1;//默认FBA
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }

    /**
     * 修改
     */
    public function modify_cfg()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post     = $this->input->post();
        $post = $this->validationData($post);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 目前只做FBA的。点击增加总共最多4行，所有输入框都为必填项且正数，单个红框内填写的数值不能相同，否则提交弹框显示错误信息。
     * v1.2.2 去掉需求数量系数
     * @param $post
     */
    private function validationData($post)
    {
//        $required_null = 0;
//        $extremum_null = 0;
        $this->lang->load('common');
//        if (4 < count($post['required']) || 4 < count($post['extremum'])) {
//            http_response(['status' => 0, 'errorMess' => '每项最多配置4行']);
//        }
        if ( 4 < count($post['extremum'])) {
            http_response(['status' => 0, 'errorMess' => '每项最多配置4行']);
        }

        /**
         * 如果全为空则设置为默认值
         */
//
//        foreach ($post['extremum'] as $key =>&$item){
//            $item = array_filter($item);
//        }
//        foreach ($post['required'] as $key =>&$item){
//            $item = array_filter($item);
//        }
//
//
//        $post['extremum'] = array_filter($post['extremum']);
//        if(empty($post['extremum'])){
//            unset($post['extremum']);
//            $post['extremum'][0]['one_day_sale'] = 999999;
//            $post['extremum'][0]['today_sale'] = 1;
//            $extremum_null = 1;
//        }
//        $post['required'] = array_filter($post['required']);
//        if(empty($post['required'])){
//            unset($post['required']);
//            $post['required'][0]['daily_avg_sale'] = 0;
//            $post['required'][0]['required_qty_multiplier'] = 1;
//            $required_null = 1;
//        }
//
//        if($required_null == 1 && $extremum_null==1){//若果全都没填,则全设置为默认值写入
//            return $post;
//        }
//pr($post);exit;
        /**
         * 验证数组类型
         */
//        if($required_null == 0){
/*            foreach ($post['required'] as $key => &$item) {
                if (empty($item['required_qty_multiplier'])) {
                    http_response(['status' => 0, 'errorMess' => $this->lang->myline('empty')]);
                }elseif ($item['daily_avg_sale']<0 ) {
                    http_response(['status' => 0, 'errorMess' => '日均销量只允许填0和正数']);
                } elseif (!positiveInteger($item['required_qty_multiplier'], 2)){
                    http_response(['status' => 0, 'errorMess' => '需求数量该项只允许填正数']);
                }
            }*/
//        }

//        if($extremum_null == 0) {
            foreach ($post['extremum'] as $key => &$item) {
                if (empty($item['one_day_sale']) || empty($item['today_sale'])) {
                    http_response(['status' => 0, 'errorMess' => $this->lang->myline('empty')]);
                } elseif (!positiveInteger($item['one_day_sale'], 3)) {
                    http_response(['status' => 0, 'errorMess' => '单个订单sku销售数量该项只允许填正整数']);
                } elseif (!positiveInteger($item['today_sale'], 2)) {
                    http_response(['status' => 0, 'errorMess' => '该单销量该项只允许填正数']);
                }
            }
//        }


        /**
         * 验证左侧框不能重复
         */
/*        $daily_avg_sales = array_column($post['required'], 'daily_avg_sale');
        foreach ($daily_avg_sales as &$daily_avg_sale){
            $daily_avg_sale = number_format($daily_avg_sale,2);
        }
        if (count(array_unique(($daily_avg_sales))) != count(($daily_avg_sales))) {
            http_response(['status' => 0, 'errorMess' => '日均销量填写的数值不能相同']);
        }*/
        $one_day_sales = array_column($post['extremum'], 'one_day_sale');
        foreach ($one_day_sales as &$one_day_sale){
            $one_day_sale = number_format($one_day_sale,2);
        }
        if (count(array_unique(($one_day_sales))) != count($one_day_sales)) {
            http_response(['status' => 0, 'errorMess' => '单个订单sku销售数量的数值不能相同']);
        }

        return $post;
    }
}