<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/9
 * Time: 9:32
 */
class Logistics_attr_cfg extends MY_Controller
{
    public function __construct()
    {
        parent::__construct();
        get_active_user();
        $this->_manager_cfg = $this->get_owner_station();
    }

    /**
     * 获取一个账号的权限
     *
     * @return array ['au', 'east']
     */
    protected function get_owner_station()
    {
        $active_user = get_active_user();
        if ($active_user->has_all_data_privileges(BUSSINESS_OVERSEA))
        {
            return ['*' => '*'];
        }
        //获取
        $account_cfg = $active_user->get_my_stations();
        return $account_cfg;
    }

    /**
     * 物流属性配置列表
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/getLogisticsList
     */
    public function getLogisticsList()
    {
        if (empty($this->_manager_cfg))
        {
            $this->data['errorMess'] = '您没有权限';
            return http_response($this->data);
        }
        elseif (!isset($this->_manager_cfg['*']))
        {
            $params['owner_station'] = $this->_manager_cfg;
        }
        $this->lang->load('common');
        $this->load->model('Oversea_logistics_list_model','m_logistics');
        $this->load->service('basic/DropdownService');
        $logistics_id = $this->input->post_get('logistics_id');
        $details_of_first_way_transportation = $this->input->post_get('details_of_first_way_transportation');
        $oversea_station_code = $this->input->post_get('oversea_station_code');
        $check_attr_time_start = $this->input->post_get('check_attr_time_start');
        $check_attr_time_end = $this->input->post_get('check_attr_time_end');
        $sku_state = $this->input->post_get('sku_state');
        $sku = $this->input->post_get('sku');
        $created_at_start = $this->input->post_get('created_at_start');
        $created_at_end = $this->input->post_get('created_at_end');
        $updated_at_start = $this->input->post_get('updated_at_start');
        $updated_at_end = $this->input->post_get('updated_at_end');
        $check_attr_state = $this->input->post_get('check_attr_state');
        $product_status = $this->input->post_get('product_status');
        $offset = $this->input->post_get('offset');
        $limit = $this->input->post_get('limit');
        $isExcel = $this->input->get_post('isExcel')??'';
        $get_count = $this->input->get_post('get_count')??'';
        $length = $this->input->get_post('length')??'';
        $start = $this->input->get_post('start')??'';
        $id = [];
        $column_keys = array(
            $this->lang->myline('item'),                         //序号
            $this->lang->myline('sku'),                         //SKU
            $this->lang->myline('oversea_station'),             //海外仓站点
            $this->lang->myline('product_name'),                //产品名称
            $this->lang->myline('sku_state'),                   //计划系统sku状态
            $this->lang->myline('product_status'),              //erp系统sku状态
//            $this->lang->myline('logistics_attr'),              //物流属性
            $this->lang->myline('first_way_transportation'),              //头程运输方式大类
            $this->lang->myline('details_of_first_way_transportation'),   //头程运输方式明细
            $this->lang->myline('state'),                       //审核状态
            $this->lang->myline('mix_hair'),          //是否允许空海混发
            $this->lang->myline('infringement_state'),          //是否侵权
            $this->lang->myline('contraband_state'),            //是否违禁
            $this->lang->myline('listing_states'),              //运营状态
            $this->lang->myline('created_at'),                  //创建时间
            $this->lang->myline('update_info'),                 //修改信息
            $this->lang->myline('check_info'),                  //审核信息
            $this->lang->myline('remark'),                       //备注
        );
//        var_dump($column_keys);
//        die;
        if ($isExcel == 1) {
            if(!empty($this->input->get_post('id'))){
                $id = json_decode($this->input->get_post('id'),true);           //勾选的id导出
            }
        }else{
            $offset = $offset ? $offset : 1;
            $limit = $limit ? $limit : 20;
            $column_keys[] = $this->lang->myline('operation');
        }
//1海运, 2海运, 3铁运, 4铁运, 5陆运, 6空运, 7空运, 8空运
        $params = [
            'logistics_id' => $logistics_id,
            'details_of_first_way_transportation' => $details_of_first_way_transportation,
            'oversea_station_code' => $oversea_station_code,
            'check_attr_time_start' => $check_attr_time_start,
            'check_attr_time_end' => $check_attr_time_end,
            'sku_state' => $sku_state,
            'product_status' => $product_status,
            'sku' => $sku,
            'check_attr_state' => $check_attr_state,
            'updated_at_start' => $updated_at_start,
            'updated_at_end' => $updated_at_end,
            'created_at_start' => $created_at_start,
            'created_at_end' => $created_at_end,
            'offset' => $offset,
            'limit' => $limit,
            'length' => $length,
            'start' => $start
        ];
        $result = $this->m_logistics->getLogisticsList($params,$id,$get_count);
        //导出前查询数量
        if (isset($result['count'])) {
            $result = $result['count'];
            $this->data['status'] = 1;
            $this->data['total'] = $result;
            http_response($this->data);
            return;
        }

        //过滤时间

        tran_time_result($result['data_list'],['approve_at','created_at','updated_at']);

        //导出
        if (isset($isExcel) && $isExcel == 1) {
            $column_keys[] = $this->lang->myline('tag');                    //标记gid
            $this->data = [];
            $this->data['status'] = 1;
            $this->data['data_list'] = ['key' => $column_keys, 'value' =>  $result['data_list']];
            http_response($this->data);
            return;
        }

        $data_list_value = array();
        $total = 0;
        if (!empty($result) && count($result['data_list']) > 0) {
            $data_list_value = $result['data_list'];
            $offset = $result['data_page']['offset'];
            $limit = $result['data_page']['limit'];
            $total = $result['data_page']['total'];
        }

        $this->data['status'] = 1;
        $this->data['data_list'] = array(
            'key' => $column_keys,
            'value' => $data_list_value,
        );
        $this->data['page_data'] = array(
            'offset' => (int)$offset,
            'limit' => (int)$limit,
            'total' => $total
        );

        $this->dropdownservice->setDroplist(['check_state']);               //审核状态下拉列表
        $this->dropdownservice->setDroplist(['logistics_attr_unknown']);    //物流属性下拉列表
        $this->dropdownservice->setDroplist(['os_station_code']);           //站点下拉列表
        $this->dropdownservice->setDroplist(['sku_state']);                 //sku状态下拉列表
        $this->dropdownservice->setDroplist(['listing_state']);                 //运营状态

        $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
        $this->load->service('basic/UsercfgProfileService');

        $result = $this->usercfgprofileservice->get_display_cfg('oversea_logistics_list');
        $this->data['selected_data_list'] = $result['config'];
        $this->data['profile'] = $result['field'];
        http_response($this->data);
    }

    /**
     * 物流属性详情预览
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/getLogisticsDetails
     */
    public function getLogisticsDetails()
    {
        $this->load->service('basic/DropdownService');
        $this->load->model('Oversea_logistics_list_model','m_logistics');
        $this->load->model('Oversea_logistics_list_remark_model','m_remark');
        $id = $this->input->post_get('id') ?? '';
        $result = $this->m_logistics->getDetail($id);
        $remark = $this->m_remark->getRemarkList('','',$id);
        if (!empty($result)){
            $sku_state                 = SKU_STATE;//SKU_STATE[$result['sku_state']],直接这样写idea报语法错误
            $result['sku_state_cn']    = isset($sku_state[$result['sku_state']]) ? $sku_state[$result['sku_state']]['name'] : '未知状态';
            $oversea_station_code      = OVERSEA_STATION_CODE;
            $result['station_code_cn'] = isset($oversea_station_code[$result['station_code']]) ? $oversea_station_code[$result['station_code']]['name'] : $result['station_code'];
            $mix_hair_state = MIX_HAIR_STATE;//是否允许空海混发
            $result['mix_hair_cn'] = isset($mix_hair_state[$result['mix_hair']]) ? $mix_hair_state[$result['mix_hair']]['name'] : '未知状态';

            $infringement_state = INFRINGEMENT_STATE;//是否侵权
            $result['infringement_state_cn'] = isset($infringement_state[$result['infringement_state']]) ? $infringement_state[$result['infringement_state']]['name'] : '未知状态';
            $contraband_state = CONTRABAND_STATE;//是否违禁
            $result['contraband_state_cn'] = isset($contraband_state[$result['contraband_state']]) ? $contraband_state[$result['contraband_state']]['name'] : '未知状态';
            $listing_state = LISTING_STATE;//运营状态
            $result['listing_state_cn'] = isset($listing_state[$result['listing_state']]) ? $listing_state[$result['listing_state']]['name'] : '未知状态';
            $result['refund_rate'] .= '%';
        }
        $this->data['status'] = 1;
        $this->data['data_list']['value'] = $result;
        $this->data['data_list']['remark'] = $remark;
        $this->dropdownservice->setDroplist(['logistics_attr','is_infringement','is_contraband','listing_state','mix_hair_state']);               //物流属性下拉/是否侵权下拉、是否违禁下拉、运营状态下拉
        $this->data['select_list'] =  $this->dropdownservice->get();
        http_response($this->data);
    }

    /**
     * 物流属性修改
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/modify
     */
    public function modify()
    {
        $this->load->model('Oversea_logistics_list_model','m_logistics');
        $id = $this->input->post_get('id');
        $logistics_id = $this->input->post_get('logistics_id');
        $sku_state = $this->input->post_get('sku_state');
        $user_id = $this->input->post_get('user_id');
        $user_name = $this->input->post_get('user_name');
        $infringement_state = $this->input->post_get('infringement_state');
        $contraband_state = $this->input->post_get('contraband_state');
//        $listing_state = $this->input->post_get('listing_state');
        $refund_rate = $this->input->post_get('refund_rate');
        $mix_hair = $this->input->post_get('mix_hair');

        if (!in_array($sku_state, array_keys(SKU_STATE))) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '参数错误';
            http_response($this->data);
            return;
        }

        $params = [
            'id' => $id,
            'logistics_id' => $logistics_id,
            'refund_rate' => $refund_rate,
            'sku_state' => $sku_state,
            'mix_hair' => $mix_hair,
            'infringement_state' => $infringement_state,
            'contraband_state' => $contraband_state,
//            'listing_state' => $listing_state,
            'updated_uid' => $user_id,
            'updated_zh_name' => $user_name,
            'updated_at' => date('Y-m-d H:i:s', time())
        ];

        $result = $this->m_logistics->modify($params);
        if ($result['code']==1) {
            $this->data['status'] = 1;
            $this->data['errorMess'] = '修改成功';
            http_response($this->data);
            return;
        } else {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $result['code']==2?'您未作任何修改，无法保存！':'修改失败';
            http_response($this->data);
            return;
        }
    }


    /*
     * 批量修改运营状态
     */
    public function edit_listing_state(){
        $this->load->model('Oversea_logistics_list_model','m_logistics');
        $ids = $this->input->post_get('ids');
        $listing = $this->input->post_get('listing');
        $id_arr = explode(',',$ids);
        if (empty($id_arr)|| !in_array($listing,array(LISTING_STATE_OPERATING,LISTING_STATE_STOP_OPERATE))){
            $this->data['status'] = 0;
            $this->data['msg'] = '未选需要修改的数据或未选择需要修改的状态';
            http_response($this->data);
            return;
        }
        $this->data['status'] = 1;
        $this->data['msg'] = 1;
        $result = $this->m_logistics->edit_listing_state($id_arr,$listing);
        if ($result == false){
            $this->data['status'] = 0;
            $this->data['msg'] = '异常原因';
            http_response($this->data);
            return;
        }
        $this->data['data_list']['total'] = $result['total'];
        $this->data['data_list']['processed'] = $result['total']-$result['undisposed'];
        $this->data['data_list']['undisposed'] = $result['undisposed'];
        $this->data['data_list']['filepath'] = $result['filepath'];
        http_response($this->data);
        return;
    }


    /**
     * 添加备注
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/addRemark
     */
    public function addRemark()
    {
        $this->load->model('Oversea_logistics_list_remark_model', "m_remark");
        $id = $this->input->post_get('id') ?? '';
        $remark = $this->input->post_get('remark') ?? '';
        $user_id = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $params = [
            'log_id' => $id,
            'op_uid' => $user_id,
            'op_zh_name' => $user_name,
            'remark' => $remark,
            'created_at' => date('Y-m-d H:i:s', time())
        ];
        $res = $this->m_remark->addRemark($params);
        if($res){
            $this->data['status'] = 1;
            http_response($this->data);
        }else{
            $this->response_error(6004);
        }
    }

    private function batchCheck($approve_state){
        $this->load->model('Oversea_logistics_list_model','m_logistics');
        $ids = explode(',',trim($this->input->post_get('id')));
        $uid = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';

        $idsArr = [];
        foreach ($ids as $key=>$value) {
            $value = trim($value);
            if (preg_match("/^[1-9][0-9]*$/", $value)) {
                $idsArr[] = intval($value);
            }
        }

        if (empty($idsArr)){
            $this->response_error(3001);
        }else{
            $result = $this->m_logistics->batchCheck($idsArr,$approve_state,$uid,$user_name);
            if($result){
                $this->data['status'] = 1;
                $this->data['data_list'] = $result;
                http_response($this->data);
            }
        }
    }

    /**
     * 单条或批量审核成功
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/batchCheckSuccess
     */
    public function batchCheckSuccess()
    {
        $this->batchCheck(2);
    }

    /**
     * 单条或批量审核失败
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/batchCheckFail
     */
    public function batchCheckFail()
    {
        $this->batchCheck(3);
    }


    /**
     * 获取全量审批过程当中的汇总信息
     * state 1 开始审批 2:全量审批中 3 审批结束 4 数据都已审批完毕
     */
    public function approve_process()
    {
        // state 1 开始审批 2:全量审批中 3 审批结束 4 数据都已审批完毕
        $data = [
            'data' => -1,
            'status' => 0
        ];
        $get = $this->input->get();
        $query = $get['query'] ?? '';
        $result = $get['result'] ?? -1;

        if (strlen($query) != 32) {
            $this->data['errorMess'] = '必须设置查询秘钥';
            return http_response($this->data);
        }
        if (!in_array($get['result'], [2, 3])) {
            $this->data['errorMess'] = '必须设置审核结果';
            return http_response($this->data);
        }
        $this->load->service('oversea/LogisticsCfgService');
        $data['data'] = $this->logisticscfgservice->get_approve_process_summary($result, $query);
        $data['status'] = 1;
        http_response($data);
    }

    /**
     * 异步全量审核入口
     * result =2 审核通过  result= 3 审核失败
     */
    public function asyc_approve(){
        $active_user = get_active_user();
        if (!function_exists('shell_exec')) {
            $this->data['errorMess'] = '请在php.ini或者php-fpm中开启appdal的shell_exec的函数';
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->data['errorMess'] = '该操作不能再Window操作系统下执行';
        }

        if (isset($this->data['errorMess'])) {
            return http_response($this->data);
        }

        $get = $this->input->get();
        if (!isset($get['result']) || !in_array($get['result'], [2, 3])) {
            $this->data['errorMess'] = '必须设置审核结果';
            return http_response($this->data);
        }

        $result = intval($get['result']);
        $salt = mt_rand(10000, 99999);
        $session_uid = $active_user->uid;
        //$user_name = $active_user->user_name;

        //生成一个查询审批进度的key 并保存到redis的hash表logistics_approve_query_pool中
        $this->load->library('Rediss');
        $query_key = $active_user->staff_code.'.'.$result;

        $command = "eval \"redis.call('hdel', 'logistics_approve_query_pool', KEYS[1]);return 'SUCC';\" 1 %s";// todo 要改
        $command = sprintf($command,$query_key);
        $result_command = $result && $this->rediss->eval_command($command);

        $query_val = $this->rediss->command(implode(' ', ['hget', 'logistics_approve_query_pool', $query_key]));



        if (!$query_val) {
            log_message('INFO',"第一次开始执行审批");
            $path_entry = FCPATH.'index.php';
            $query_val = md5($session_uid.$salt);
            $cmd = sprintf('/usr/bin/php %s oversea Logistics_attr_cfg batch_approve_all %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $query_val);
            shell_exec($cmd);
            $this->rediss->command(implode(' ', ['hset', 'logistics_approve_query_pool', $query_key, $query_val]));
        } else {
            log_message('INFO',"第二次开始执行审批");
            //第二次执行
            $path_entry = FCPATH.'index.php';
            $cmd = sprintf('/usr/bin/php %s oversea Logistics_attr_cfg batch_approve_all %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $query_val);
        }

        $this->data = ['status' => 1, 'data' => $query_val, 'cmd' => $cmd];
        http_response($this->data);
    }


    /**
     *  批量审核 命令行 调用
     */
    public function batch_approve_all()
    {
        try
        {
            //审核权限
            if (is_cli() && func_num_args() > 0) {
                list($session_uid, $result, $query_value) = func_get_args();
                log_message('INFO',"session_uid:{$session_uid}, result:{$result}, query_value:{$query_value}");
                if (!$session_uid || !$result) {
                    throw new \InvalidArgumentException('cli请求丢失session_uid,result参数');
                }
                $this->load->library('Rediss');
                $user_data = $this->rediss->getData($session_uid);
                log_message('INFO',"user_data:".json_encode($user_data));
                if (!empty($user_data)) {
                    $this->load->service('UserService');
                    $this->userservice::login($user_data);
                    $active_user = get_active_user(true);
                } else {
                    throw new \InvalidArgumentException('获取用户认证信息失败，该用户未登陆或者已经失效，请重新登陆');
                }
            } else {
                $params = $this->input->get();
                $result = $params['result'];
                $query_value = $params['query'];
            }

            //设置权限
            $this->load->service('oversea/LogisticsCfgService');
            $this->data['data'] = $this->logisticscfgservice->batch_approve_all($result,$query_value);
            $this->data['status'] = 1;
            $code = 200;

        }
        catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 获取日志列表
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/getLogList
     */
    public function getLogList(){
        $this->lang->load('common');
        $this->load->model('Oversea_logistics_list_log_model', "m_log");
        $id = $this->input->post_get('id') ?? '';
        $offset = $this->input->post_get('offset') ?? '';
        $limit = $this->input->post_get('limit') ?? '';
        $offset = $offset ? $offset : 1;
        $limit = $limit ? $limit : 20;
        $column_keys = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $result = $this->m_log->getLogList($id,$offset,$limit);
        if(isset($result) && count($result['data_list'])>0){
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key'=>$column_keys,
                'value'=>$result['data_list'],
            );
            $this->data['page_data']['log'] = array(
                'offset'=>(int)$result['data_page']['offset'],
                'limit'=>(int)$result['data_page']['limit'],
                'total'=>$result['data_page']['total'],
            );
        }else{
            $this->data['status'] =1;
            $this->data['data_list']['log']=array(
                'key'=>$column_keys,
                'value'=>array(),
            );
            $this->data['page_data']['log'] = array(
                'offset'=>(int)$offset,
                'limit'=>(int)$limit,
                'total'=>$result['data_page']['total']
            );
        }

        http_response($this->data);
    }

    /**
     * 物流属性
     * @param $value
     * @return mixed
     */
    private function syncLogisticsAttr($value)
    {
        $data = LOGISTICS_ATTR;
        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
    }

    /**
     * 默认物流配置页面
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/defaultCfDetail
     */
    public function defaultCfDetail(){
        $this->load->service('basic/DropdownService');
        $this->load->model('fba/Default_cfg_model','m_default');
        $active_user = get_active_user();

        $result = $this->m_default->getOverseaDetail($active_user->staff_code);
        $result['default_value_cn'] = $this->syncLogisticsAttr($result['default_value']);
        if(!empty($result)){
            unset($result['default_key']);
        }
        $this->data['status'] = 1;
        $this->data['data_list']['value'] = $result;
        $this->dropdownservice->setDroplist(['logistics_attr']);               //物流属性下拉
        $this->data['select_list'] = $this->dropdownservice->get();
        http_response($this->data);
    }

    /**
     * 修改默认物流配置
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/defaultCfModify
     */
    public function defaultCfModify(){
        $logistics_id = $this->input->post_get('logistics_id');
//        $op_uid = $this->input->post_get('user_id');
//        $op_zh_name = $this->input->post_get('user_name');
        $this->load->model('fba/Default_cfg_model','m_default');
        $active_user = get_active_user();
        $params = [
            'default_value' => $logistics_id,
            'op_uid' => $active_user->staff_code,
            'op_zh_name' => $active_user->user_name
        ];

        $this->m_default->overseaModify($params);

        //写入日志
        $this->load->model('fba/Default_cfg_log_model','m_log');
        $params['default_key'] = 2;
        $this->m_log->modifyLog($params);
        $this->data['status'] = 1;
        $this->data['errorMess'] = '修改成功';
        http_response($this->data);
    }

    /**
     * 默认物流配置日志列表
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/getDefaultCfLogList
     */
    public function getDefaultCfLogList(){
        $this->lang->load('common');
        $offset = $this->input->post_get('offset') ?? '';
        $limit = $this->input->post_get('limit') ?? '';
        $offset = $offset ? $offset : 1;
        $limit = $limit ? $limit : 20;
        $column_keys = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $this->load->model('fba/Default_cfg_log_model','m_log');
        $active_user = get_active_user();
        $result = $this->m_log->getLogList($offset,$limit,2,$active_user->staff_code);
        if (isset($result) && count($result['data_list']) > 0) {
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key' => $column_keys,
                'value' => $result['data_list'],
            );
            $this->data['page_data']['log'] = array(
                'offset' => (int)$result['data_page']['offset'],
                'limit' => (int)$result['data_page']['limit'],
                'total' => $result['data_page']['total'],
            );
        } else {
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key' => $column_keys,
                'value' => array(),
            );
            $this->data['page_data']['log'] = array(
                'offset' => (int)$offset,
                'limit' => (int)$limit,
                'total' => $result['data_page']['total']
            );
        }
        http_response($this->data);
    }

    /**
     * 批量修改导入功能
     */
    public function uploadExcel()
    {

        $user_id = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $data_values = json_decode($this->input->post_get('data_values'), true);
        $this->load->model('Oversea_logistics_list_model', 'm_logistics');
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        foreach ($data_values as $key => $value) {
            if(in_array('',$value)){//如果数组中有空值
                $undisposed++;
                continue;
            }
            $result = $this->m_logistics->modifyByExcel($value, $user_id, $user_name);
            if ($result) {
                $processed++;
            } else {
                $undisposed++;
            }
        }
        $this->data['status'] = 1;
        $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
        http_response($this->data);
    }


    /**
     * 导出
     * http://192.168.71.170:1084/oversea/logistics_attr_cfg/export
     */
    public function export(){
        try
        {
            $post = $this->compatible('post');
            $this->load->service('oversea/OverseaLogisticsExportService');
            $this->oversealogisticsexportservice->setTemplate($post);
            $this->data['filepath'] = $this->oversealogisticsexportservice->export('csv');
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    /**
     * 导入
     */
    public function upload_add()
    {
        try {
            $processed  = 0;//已处理
            $undisposed = 0;//未处理
            $data       = json_decode($this->input->post_get('data_values'), true);
            $error_data = json_decode($this->input->post_get('error_data'), true);
            $this->load->service('oversea/LogisticsCfgService');
            $data = $this->logisticscfgservice->check_sku_is_exist($data, $error_data);
//var_dump($data);
//die;
            if (!empty($data)) {
                $data = array_chunk($data, 500);
                foreach ($data as $key => $item) {
                    $processed += $this->logisticscfgservice->add_sku($item);
                }
                $this->logisticscfgservice->del_part_data();
            }
            $this->data['data_list'] = ['processed' => $processed];
            $this->data['status']    = 1;
            $code                    = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    public function undisposed_data_download()
    {
        try {
            $this->load->service('oversea/LogisticsCfgService');
            $result               = $this->logisticscfgservice->get_undisposed_data();
            $this->data['data']   = $result;
            $this->data['status'] = 1;
            $code                 = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
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
