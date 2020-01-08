<?php
/**
 * 海外仓仓库配置
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/3/8
 * Time: 14:58
 */

class Oversea_warehouse_region_cfg  extends MY_Controller
{
    private $_server_module = 'mrp/oversea_warehouse_region_cfg/';


    /**
     * 获取所有地区
     * http://192.168.71.170:1083/mrp/oversea_warehouse_region_cfg/getRegionList
     */
    public function getRegionList(){
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }

    /**
     * 根据地区获取已选和可选仓库列表
     * http://192.168.71.170:1083/mrp/oversea_warehouse_region_cfg/getRelationshipList
     */
    public function getRelationshipList(){
        $region = $this->input->post_get('region') ?? '';
        if (empty($region))
        {
            $this->response_error('3001');
        }

        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, ['region'=>$region]);
        http_response($this->rsp_package($result));
    }

    /**
     * 取消或选中仓库
     * http://192.168.71.170:1083/mrp/oversea_warehouse_region_cfg/changeRegionWarehouse
     */
    public function changeRegionWarehouse(){
        $user_id = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $region = trim($this->input->post_get('region') ?? '');
        $warehouse_code = trim($this->input->post_get('warehouse_code') ?? '');

        if(empty($user_id) || empty($user_name) || empty($region) || empty($warehouse_code)){
            $this->response_error('3001');
        }

        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $params = [
            'region'=>$region,
            'user_id'=>$user_id,
            'user_name'=>$user_name,
            'warehouse_code'=>$warehouse_code,
        ];
        $result = $this->_curl_request->cloud_get($api_name,$params);
        http_response($this->rsp_package($result));
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