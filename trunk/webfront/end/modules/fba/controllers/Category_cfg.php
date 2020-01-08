<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FBA不可审核类目
 *  V1.2.2
 * @author Manson
 * @since 2019-09-10
 */
class Category_cfg extends MY_Controller
{

    private $_server_module = 'fba/Category_cfg/';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 已配置类目
     * http://192.168.71.170:1083/fba/Category_cfg/list
     */
    public function list()
    {
        //$api_name = 'fba/Sku_cfg/List';        //v1.2.2版本改动
        $api_name = $this->_server_module . strtolower(__FUNCTION__);

        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
//        if ($result['status'] == 1 && !empty($result['data_list']['value']))
//        {
//            tran_erp_sku_list_result($result['data_list']['value']);
//        }
        http_response($this->rsp_package($result));
    }

    /**
     * 获取类目
     * http://192.168.71.170:1083/fba/Category_cfg/get_category
     */
    public function get_category()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
    /**
     * 增加
     * http://192.168.71.170:1083/fba/Category_cfg/get_category
     */
    public function add_category()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);

        $post = $this->input->post();

//        pr($post);exit;
        $params = [];
        if (isset($post['category_id']) && count($post['category_id']) == count($post['category_cn_name']) && isset($post['category_cn_name'])){
            $category_cn_name = $post['category_cn_name'];
            foreach ($post['category_id'] as $key => $value){
                $params['category'][] = [
                    'category_id' => $value,
                    'category_cn_name' => $category_cn_name[$key],
                ];
            }
            if (!isset($post['business_line'])){
//            echo 123;exit;
                $params['business_line'] = BUSINESS_LINE_FBA;
            }else{
                $params['business_line'] =$post['business_line'];
            }
        }else{
            http_response(['status'=>0,'errorMess'=>'参数错误']);
        }


        $result = $this->_curl_request->cloud_post($api_name, $params);

        http_response($this->rsp_package($result));
    }

    /**
     * 删除
     * http://192.168.71.170:1083/fba/Category_cfg/get_category
     */
    public function del_cfg()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);

        $post = $this->input->post();
        if (isset($post['id']) && !empty($post['id'])){
            $result = $this->_curl_request->cloud_post($api_name, $post);
            http_response($this->rsp_package($result));

        }else{
            http_response(['status'=>0,'errorMess'=>'参数错误']);
        }

    }
}