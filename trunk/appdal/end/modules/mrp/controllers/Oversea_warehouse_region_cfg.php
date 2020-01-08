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

    public function __construct()
    {
        parent::__construct();
        $this->load->service('mrp/OverseaRegionService');
        $this->error_info = $this->config->item('error_code');
    }

    /**
     * 获取所有地区
     * http://192.168.71.170:1084/mrp/oversea_warehouse_region_cfg/getRegionList
     */
    public function getRegionList(){
        $result = $this->oversearegionservice->getRegionList();
        $this->data['status'] = 1;
        $this->data['data'] = $result;
        http_response($this->data);
    }

    /**
     * 根据地区获取已选和可选仓库列表
     * http://192.168.71.170:1084/mrp/oversea_warehouse_region_cfg/getRelationshipList
     */
    public function getRelationshipList(){
        $region = $this->input->post_get('region') ?? '';
        if(empty($region)){
            $this->response_error('3001');
            return;
        }
        try {
            $result = $this->oversearegionservice->getRegionWarehouseList($region);
            $this->data['status'] = 1;
            $this->data['data'] = $result;
            $code = 200;
        }catch (\InvalidArgumentException $e){
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }catch (\RuntimeException $e){
            $code = 500;
            $errorMsg = $e->getMessage();
        }catch (\Throwable $e){
            $code = 500;
            $errorMsg = $e->getMessage();
        }finally{
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 取消或选中仓库
     * http://192.168.71.170:1084/mrp/oversea_warehouse_region_cfg/changeRegionWarehouse
     */
    public function changeRegionWarehouse(){
        $user_id = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $region = trim($this->input->post_get('region') ?? '');
        $warehouse_code = trim($this->input->post_get('warehouse_code') ?? '');
        $warehouse_code = str_replace(array(" ","　","\t","\n","\r"), '', $warehouse_code);

        if(empty($user_id) || empty($user_name) || empty($region) || empty($warehouse_code)  || !in_array($region,array_keys(OVERSEA_STATION_CODE))){
            $this->response_error('3001');
            return;
        }

        $warehouse_codes = explode(',',$warehouse_code);
        foreach ($warehouse_codes as $index=>$warehouse_code){
            if (empty($warehouse_code)){
                unset($warehouse_codes[$index]);
            }
        }

        if (empty($warehouse_codes)){
            $this->response_error('3001');
        }

        $result = $this->oversearegionservice->changeRegionWarehouse($region,$warehouse_codes,$user_id,$user_name);
        if($result){
            $this->data['status'] = 1;
            http_response($this->data);
        }else{
            $this->response_error('6003');
        }
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