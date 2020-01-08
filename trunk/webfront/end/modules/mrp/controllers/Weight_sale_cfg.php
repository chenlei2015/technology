<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/5/20
 * Time: 17:23
 */
class Weight_sale_cfg Extends MY_Controller
{
    private $_server_module = 'mrp/Weight_sale_cfg/';

    public function __construct()
    {
        parent::__construct();
//        $this->lang->load(['mrp_lang']);
//        $this->load->helper('mrp_helper');
    }

    /**
     * 列表
     */
    public function list()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get      = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
//        if ($result['status'] == 1 && !empty($result['data_list']['value']))
//        {
//            tran_weight_cfg_list_result($result['data_list']['value']);
//        }
        http_response($this->rsp_package($result));
    }

    /**
     * 修改
     */
    public function modify_cfg()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post     = $this->input->post();
        $this->validationData($post);

        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 。点击增加总共最多4行，天数最高90天，所有输入框都为必填项且正数，所有权重加起来必须等于1，否则提交弹框显示错误信息。
     * @param $post
     */
    private function validationData($post){
        $this->lang->load('common');
        if (!isset($post['logistics_id']) || !isset($post['cfginfo'])) {
            http_response(['status' => 0, 'errorMess' => '参数错误']);
        }
        if (empty($post['logistics_id']) || empty($post['cfginfo'])) {
            http_response(['status' => 0, 'errorMess' => $this->lang->myline('empty')]);
        }
        if (count($post['cfginfo']) > 4) {
            http_response(['status' => 0, 'errorMess' => '最多配置4行']);
        }



        /**
         * 最多4项加权，销量最多取90天
         */
        foreach ($post['cfginfo'] as $key => $value) {
            if(count(array_diff(array_keys($value),['number_of_days','weight']))>0){
                http_response(['status' => 0, 'errorMess' => '参数错误']);
            }
            if (empty($value['weight']) || empty($value['number_of_days'])) {
                http_response(['status' => 0, 'errorMess' => $this->lang->myline('empty')]);
            } elseif (!positiveInteger($value['number_of_days'],3)){
                http_response(['status' => 0, 'errorMess' => '天数只允许填正整数(不包含0)']);
            } elseif(!positiveInteger($value['weight'],5)){
                http_response(['status' => 0, 'errorMess' => '权重只允许填大于0且小于等于1的数']);
            } elseif ($value['number_of_days'] > 90) {
                http_response(['status' => 0, 'errorMess' => '销量最大设置为90天']);
            }

        }

        /**
         * 天数不能重复
         */
        $days = array_column($post['cfginfo'],'number_of_days');
        if(count($days) != count(array_unique($days))){
            http_response(['status' => 0, 'errorMess' => '天数不能重复!']);
        }


        /**
         * 说明：请确保所有权重加起来等于1，否则无法提交！
         */
        $weight_sum = 0;
        foreach (array_column($post['cfginfo'], 'weight') as $weight){
            $weight_sum = (bcadd($weight_sum,$weight,2));
        }
        if($weight_sum != 1){
            http_response(['status' => 0, 'errorMess' => '请确保所有权重加起来等于1，否则无法提交!']);
        }
    }
}