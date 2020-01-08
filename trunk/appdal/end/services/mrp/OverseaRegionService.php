<?php 
/**
 * MRP 海外仓仓库配置服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author bigfong
 * @since 2019-3-8
 * @link
 */
class OverseaRegionService
{
    private $_ci;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_region_cfg_model', 'oversea_region_cfg', false, 'mrp');
    }

    /**
     * 获取所有地区
     * @return array
     */
    public function getRegionList(){
        $list = [];
        foreach (OVERSEA_STATION_CODE as $code=>$item){
            $list[] = ['code'=>$code,'name'=>$item['name']];
        }
        return $list;
    }

    /**
     * 根据地区获取已选和可选仓库列表
     * @param string $region
     * @return array
     */
    public function getRegionWarehouseList($region=''){
        if (empty($region)){
            throw new \InvalidArgumentException(sprintf('无效的发区参数'), 412);
        }

        $cb = function($result, $map) {
            $my = [];
            if (isset($result['data']) && $result['data'])
            {
                foreach ($result['data'] as $res)
                {
                    $tmp = [];
                    foreach ($res as $col => $val)
                    {
                        $tmp[$map[$col] ?? $col] = $val;
                    }
                    $my[] = $tmp;
                }
            }
            return $my;
        };

        $result =  RPC_CALL('YB_ERP_01', [], $cb);
        if (!empty($result['errorMess'])){
            log_message('ERROR', sprintf('获取erp系统有效仓库接口返回无效数据：%s', $result['errorMess']));
            return [];
        }

        //1.从service-erp系统获取所有有效仓库
        /*$curl = get_curl();
        $rsp = $curl->http_post(cloud_service_erp_api_url(SERVICE_ERP_GET_ALL_WAREHOUSE), '', ['Content-type:application/json;charset=UTF-8']);

        $rspData = json_decode($rsp, true);
        if (!$rspData){
            log_message('ERROR', sprintf('获取erp系统有效仓库接口返回无效数据：%s', $rsp));
            return [];
        }
        $relist = $rspData['data'];*/
        
        $relist = $result;
        if(empty($relist)){
            return [];
        }
        $list = [];
        foreach ($relist as $item){
            if (!empty($item['region']) && $item['region']!=null && $item['region']!='null'){
                $list[] = $item;
            }
        }

        //2.获取计划系统中地区与仓库关系列表
        $relationships = $this->_ci->oversea_region_cfg->getRegionWarehouseList();
        $canSleList = [];
        $hasSleList = [];
        $othersSleList = [];
        if(empty($relationships)){
            foreach ($list as $item){
                $canSleList[] = [
                    'warehouse_code'=>$item['warehouseCode'],
                    'warehouse_name'=>$item['warehouseName'],
                    'selected'=>0
                ];
            }
        }else{
            $current = [];
            $others = [];
            foreach ($relationships as $item){
                if($item['region'] == $region) {//3.当前仓库选中
                    $current[] = $item['warehouse_code'];
                }else{//4.其他仓库选中
                    $others [] = $item['warehouse_code'];
                }
            }
            //5.返回列表，其他包含本仓库已选中，本仓库可选（但未选中的,已关联其他仓库的不显示）
            foreach ($list as $item){
                $selected = 0;
                if(in_array($item['warehouseCode'],$current)) {//已在当前仓库中选中
                    $selected = 1;
                }elseif(in_array($item['warehouseCode'],$others)) {//已在其他仓库中选中,不显示
                    $othersSleList[] = [
                        'warehouse_code'=>$item['warehouseCode'],
                        'warehouse_name'=>$item['warehouseName'],
                        'selected'=>1
                    ];
                   continue;
                }
                $v = [
                    'warehouse_code'=>$item['warehouseCode'],
                    'warehouse_name'=>$item['warehouseName'],
                    'selected'=>$selected
                ];
                if (empty($selected)){
                    $canSleList[] = $v;
                }else{
                    $hasSleList[] = $v;
                }
            }
        }
        return ['had_select'=>$hasSleList,'can_select'=>$canSleList,'others_select'=>$othersSleList];
    }

    /**
     * 更新是否选中
     * @param $region
     * @param $warehouse_code
     * @param bool $selected
     * @return mixed
     */
    public function changeRegionWarehouse($region,$warehouse_codes=[],$user_id=0,$user_name=''){
        return $this->_ci->oversea_region_cfg->changeRegionWarehouse($region,$warehouse_codes,$user_id,$user_name);
    }
}