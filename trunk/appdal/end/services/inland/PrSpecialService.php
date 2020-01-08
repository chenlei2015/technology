<?php

/**
 * 国内 特殊需求服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class PrSpecialService
{
    public static $s_system_log_name = 'INLAND-SPECIAL';
    
    /**
     * __construct
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_special_pr_list_model', 'm_inland_special_pr_list', false, 'inland');
        $this->_ci->load->helper('inland_helper');
        return $this;
    }
    
    /**
     * 添加一条备注, 成功为true，否则抛异常， 不做权限
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params, $priv_uid = -1)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        $record = $this->_ci->m_inland_special_pr_list->find_by_pk($gid);
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->m_inland_special_pr_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        $this->_ci->Record->set('updated_uid', get_active_user()->staff_code);
        
        $db = $this->_ci->m_inland_special_pr_list->getDatabase();
        
        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('国内特殊列表更新备注失败'), 500);
        }
        if (!$this->add_list_remark($params))
        {
            throw new \RuntimeException(sprintf('国内特殊列表插入备注失败'), 500);
        }
        $db->trans_complete();
        
        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('国内添加备注事务提交完成，但检测状态为false'), 500);
        }
        
        return true;
    }
    
    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_list_remark($params)
    {
        $this->_ci->load->model('Inland_special_pr_list_remark_model', 'm_inland_special_pr_list_remark', false, 'inland');
        append_login_info($params);
        $insert_params = $this->_ci->m_inland_special_pr_list_remark->fetch_table_cols($params);
        return $this->_ci->m_inland_special_pr_list_remark->add($insert_params);
    }
    
    protected function recalc_required_qty(Record $record)
    {
        return $record->require_qty;
    }
    
    /**
     * 修改pr列表
     * v1.1.1修改需求为：
     * 待审核、sku必须匹配、当前日期小于或等于参与计算截止日期、需求数量必须大于0可以修改
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return boolean 成功返回true， 失败抛出异常
     */
    public function edit_pr($params)
    {
        $gid = trim($params['gid']);
        unset($params['gid']);
        
        //gid检测
        $record = $this->_ci->m_inland_special_pr_list->find_by_pk($gid);
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID%s', $gid), 412);
        }
        //状态检测
        $this->_ci->load->classes('inland/classes/PrSpecialState');
        
        //当前状态是否可以修改操作
        $op_privilegs = $this->_ci->PrSpecialState
        ->from($record['approve_state'])
        ->can_action('edit_pr', [
                'approve_state' => $record['approve_state'],
                'is_sku_match' => $record['is_sku_match'],
                'require_qty' => $record['require_qty'],
        ]);
        if (!$op_privilegs)
        {
            throw new \RuntimeException(sprintf('当前状态不允许修改需求数量'), 500);
        }
        
        //控制可以被修改的字段
        $can_edit_cols = ['require_qty'];
        $params = $this->_ci->m_inland_special_pr_list->fetch_table_cols($params);
        $can_edit_params = array_intersect_key($params, array_flip($can_edit_cols));
        
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->m_inland_special_pr_list);
        foreach ($params as $key => $val)
        {
            $this->_ci->Record->set($key, $val);
        }
        
        $modify_count = $this->_ci->Record->report();
        if ($modify_count == 0)
        {
            throw new \RuntimeException(sprintf('没有产生任何修改，本次操作未执行任何操作'), 500);
        }
        //更新时间
        $this->_ci->Record->set('updated_at', time());
        $active_user = get_active_user();
        $this->_ci->Record->set('updated_uid', $active_user->staff_code);
        
        //重新计算需求数量
        $this->_ci->Record->set('require_qty', $this->recalc_required_qty($this->_ci->Record));
        
        //状态变化
        if(in_array($record['approve_state'], [SPECIAL_CHECK_STATE_UNAUDITED, SPECIAL_CHECK_STATE_INIT, SPECIAL_CHECK_STATE_FAIL]))
        {
            $this->_ci->Record->set('approve_state', SPECIAL_CHECK_STATE_INIT);
        }
        
        $this->_ci->load->service('inland/PrSpecialLogService');
        $modify_require_qty = $this->_ci->Record->get('require_qty');
        
        //事务开始
        $db = $this->_ci->m_inland_special_pr_list->getDatabase();
        
        try
        {
            $db->trans_start();
            
            //记录日志
            $log_context = sprintf('将需求数量调整为 %s', '+'.$modify_require_qty);
            $this->_ci->prspeciallogservice->send(['gid' => $gid], $log_context);
            $update_count = $this->_ci->m_inland_special_pr_list->update_require_qty($this->_ci->Record);
            if ($update_count !== 1)
            {
                throw new \RuntimeException(sprintf('未修改国内特殊需求数量，该请求可能已经执行'), 500);
            }
            
            $db->trans_complete();
            
            if ($db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('修改国内特殊需求数量，事务提交成功，但状态检测为false'), 500);
            }
            
            //发送系统日志
            $this->_ci->load->service('basic/SystemLogService');
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context);
            
            return true;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('修改国内特殊需求数量，提交事务出现异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException(sprintf('修改国内特殊需求数量，提交事务出现异常'), 500);
        }
    }
    
    /**
     * 批量删除， 未转po都可以删除
     *
     * @param unknown $gid_arr gid列表
     */
    public function batch_delete($gids)
    {
        $this->_ci->load->helper('common');
        $report = [
                'total' => count($gids),
                'processed' => 0,
                'undisposed' => 0
        ];
        if (empty($gids))
        {
            $report['undisposed'] = $report['total'];
            return $report;
        }

        //五个字段 sku,calc_start_date,calc_end_date,requisition_zh_name,requisition_platform_name
        $select = 'sku,requisition_zh_name,requisition_platform_name';
        $unique_result = $this->_ci->m_inland_special_pr_list->get_unique_field($gids,$select);

        foreach ($unique_result as $value){
            $unique_arr[] = [
                'unique_str' => gen_unique_str($value),
                'sku' => $value['sku']
            ];
        }

        
        $gid_rows = $this->_ci->m_inland_special_pr_list->get_delete_by_gids($gids, 'gid, pr_sn');
        //删除yibai_inland_special_unique表的记录
        $this->_ci->load->model('Inland_special_unique_model', 'm_special_unique', false, 'inland');
        if(count($unique_result)>0){
            $this->_ci->m_special_unique->delete_batch($unique_arr);
        }
        if (empty($gid_rows))
        {
            $report['undisposed'] = $report['total'];
            return $report;
        }
        
        $prsn_to_gid          = array_column($gid_rows, 'gid', 'pr_sn');
        $pr_sns               = array_keys($prsn_to_gid);
        $has_po_sns           = $this->get_po($pr_sns);
        $report['undisposed'] = count($has_po_sns);
        if ($report['total'] == $report['undisposed'] )
        {
            return $report;
        }
        
        $time                 = time();
        $active_user_info     = get_active_user()->get_user_info();
        $uid                  = $active_user_info['oa_info']['userNumber'];
        $user_name            = $active_user_info['oa_info']['userName'];
        
        $batch_update_params = $batch_insert_log = $delete_pr_sns = [];
        foreach ($pr_sns as $pr_sn)
        {
            if (isset($has_po_sns[$pr_sn])) continue;
            
            $delete_pr_sns[] = $pr_sn;
            $gid = $prsn_to_gid[$pr_sn];
            //0的占位符只是兼容batch_update_compatible格式
            $batch_update_params[0][] = [
                    'gid'         => $gid,
                    'updated_at'  => $time,
                    'updated_uid' => $uid,
                    'state'       => SPECIAL_PR_STATE_DELETE
            ];
            $batch_insert_log[] = [
                    'gid' => $gid,
                    'uid' => $uid,
                    'user_name' => $user_name,
                    'context' => '需求单号被删除',
            ];
        }
        
        //事务开始
        $this->_ci->load->service('inland/PrSpecialLogService');
        $db = $this->_ci->m_inland_special_pr_list->getDatabase();
        try
        {
            $db->trans_start();
            
            //批量更新状态
            if (!$this->_ci->m_inland_special_pr_list->batch_update_compatible($batch_update_params))
            {
                throw new \RuntimeException(sprintf('执行批量删除操作，批量删除国内需求列表失败'), 500);
            }
            
            //批量发送日志
            $this->_ci->prspeciallogservice->multi_send($batch_insert_log);
            
            $db->trans_complete();
            
            if ($db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行批量删除操作，事务提交成功，但状态检测为false'), 500);
            }
            
            //发送系统日志
            $log_context = sprintf('批量删除：%s', implode(',', $delete_pr_sns));
            $this->_ci->load->service('basic/SystemLogService');
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context);
            
            $report['processed'] = count($delete_pr_sns);
            
            return $report;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('修改国内特殊需求数量，提交事务出现异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException(sprintf('修改国内特殊需求数量，提交事务出现异常'), 500);
        }

    }
    
    /**
     * 根据需求单号获取对应的po_sn单号
     *
     * @param unknown $pr_sns
     * @return array|string[]|unknown[]
     */
    public function get_po($pr_sns)
    {
        $this->_ci->load->model('Inland_pr_track_list_model', 'm_inland_pr_track', false, 'inland');
        $pursn_to_prsn = $this->_ci->m_inland_pr_track->get_pursn_by_prsn($pr_sns);
        if (empty($pursn_to_prsn))
        {
            //全部没有转备货
            return [];
        }
        
        $this->_ci->load->model('Plan_purchase_track_list_model', 'm_purchase_track_list', false, 'plan');
        $pursn_to_posn = $this->_ci->m_purchase_track_list->get_pursn_map_posn(array_keys($pursn_to_prsn));
        if (empty($pursn_to_posn))
        {
            //全部没有转po
            return [];
        }
        
        $prsns_has_po = [];
        foreach ($pursn_to_prsn as $pur_sn => $rows)
        {
            $po_sn = $pursn_to_posn[$pur_sn] ?? '';
            if ($po_sn == '') continue;
            foreach ($rows as $row)
            {
                $prsns_has_po[$row['pr_sn']] = $po_sn;
            }
        }
        return $prsns_has_po;
    }
    
    
    /**
     * 手动勾选审核
     *
     */
    public function manual_approve($gids, $result)
    {
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }
        
        $todo = $this->_ci->m_inland_special_pr_list->get_can_approve_for_first($gids);
        $this->_ci->load->classes('inland/classes/InlandApprove');
        
        $this->_ci->InlandApprove
        ->set_model($this->_ci->m_inland_special_pr_list)
        ->set_approve_result($result)
        ->set_selected_gids($gids)
        ->recive($todo);
        
        $this->_ci->InlandApprove->run($this->_ci->InlandApprove::INLAND_APPROVE_MANUAL);
        $this->_ci->InlandApprove->send_system_log(self::$s_system_log_name);
        return $this->_ci->InlandApprove->report();
    }
    
    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->m_inland_special_pr_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }
    
    public function get_pr_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Inland_special_pr_list_remark_model', 'm_inland_special_pr_list_remark', false, 'inland');
        return $this->_ci->m_inland_special_pr_list_remark->get($gid, $offset, $limit);
    }

    /**
     * 批量上传
     */
    public function batch_upload($data,$count)
    {
        require_once APPPATH . "third_party/PHPExcel.php";
        $this->_ci->load->helper('common');
        $fail = 0;
        $success_data = [];
        $remark_data = [];
        $this->_ci->load->model('Inland_special_pr_list_model', 'm_special_pr_list');

        $requisition_reason = ['促销','其他'];
        $uid = get_active_user()->staff_code;
        $user_name = get_active_user()->staff_code;
        $all_user = $this->get_all_saleman();
        $all_platform = $this->tran_platform();
        $this->_ci->load->model('Inland_sku_cfg_model', 'm_sku_cfg');
        //当天日期
        $today = strtotime(date('Y-m-d').'00:00:00');

        $unique_arr = [];
        $unique_str_all = [];
        $new_unique=[];
        $i=0;
        //处理数据
        foreach ($data as $key => $value){

            //规则: 除备注外全为必填项
            if(!in_array($value['requisition_reason'],$requisition_reason) ||
                empty($value['requisition_date']) ||
                empty($value['requisition_zh_name']) ||
                empty($value['requisition_platform_name']) ||
                empty($value['sku']) ||
                empty($value['require_qty']) ||
                empty($value['requisition_reason'])
            ){

            }elseif ($value['requisition_reason'] == '其他' && empty($value['remark'])){
                //如果申请原因为其他 备注必填
            }
            else{
                $i++;
                $success_data[$i] = [
                    'gid' => $this->_ci->m_special_pr_list->gen_id(),//全局主键gid
                    'pr_sn' => $this->general_sum_sn('inland_special'),//需求单号
                    'requisition_uid' => $all_user[$value['requisition_zh_name']]??'',//用户id
                    'requisition_zh_name' => $value['requisition_zh_name']??'',//用户id
                    'requisition_platform_code' =>$all_platform[$value['requisition_platform_name']]??'',//平台code
                    'requisition_platform_name' =>$value['requisition_platform_name']??'',//平台名称
                    'requisition_date' => $this->excelTime($value['requisition_date'])??'',//申请时间
                    'sku' => $value['sku'],
                    'require_qty'=>$value['require_qty'],
                    'requisition_reason'=>$value['requisition_reason'],
                    'remark' => $value['remark'],
                    'created_at' =>  time(),    //创建时间
                    'approve_state' => 2  //默认待审核
                ];
                if (!empty($value['remark'])){//备注插入处理
                    $success_data[$i]['remark'] = mb_substr((strip_tags($value['remark'])), 0, 200);
                }
                if(!empty($success_data[$i]['remark'])){//备注信息
                    $remark_data[] = [
                        'gid' => $success_data[$i]['gid'],
                        'uid' => $uid,
                        'user_name' => $user_name,
                        'remark' => $success_data[$i]['remark']
                    ] ;
                }
                    //生成唯一的字符串
                    $check_arr = [
                        $value['requisition_zh_name'],
                        $value['requisition_platform_name'],
                        $value['sku'],
                    ];
                $unique_str = gen_unique_str($check_arr);
                $success_data[$i]['unique_str'] = $unique_str;


                //1.导入的数据中有重复的 后者为无需审核
                if(in_array($unique_str,$unique_str_all)){
                    $success_data[$i]['approve_state'] = 1;// 过期无需审核
                }else{
                    $unique_str_all[] = $unique_str;
                    $unique_arr[] = [
                        'sku' => $value['sku'],
                        'unique_str' => $unique_str,
                    ];
                }
            }
        }



        //匹配备货关系配置表
        $all_sku = array_column($success_data,'sku');//要查的
        $goods_info = $this->_ci->m_sku_cfg->batch_get_GoodsInfo($all_sku);//批量查出sku的信息
        unset($all_sku);
        $batch_sku = array_column($goods_info,'sku');//匹配到的

        //如果表里已经存在
        $this->_ci->load->model('Inland_special_unique_model', 'm_special_unique');
        $batch_unique_str = array_column($unique_arr,'unique_str');//匹配到的
        $skus = array_column($unique_arr,'sku');//匹配到的
        $match = $this->_ci->m_special_unique->batch_select($batch_unique_str,$skus);
        unset($batch_unique_str);


        foreach ($success_data as $k => $row){
            foreach ($goods_info as $info){
                if(isset($row['sku']) && isset($info['sku'])){
                    if($row['sku'] == $info['sku']){//已匹配 待审核
                        $success_data[$k]['sku_name'] = $info['sku_name']??'';//产品名称
                        $success_data[$k]['is_refund_tax'] = $info['is_refund_tax']??'';//是否退税
                        $success_data[$k]['purchase_warehouse_id'] = $info['purchase_warehouse_id']??'';//采购仓库
                        $success_data[$k]['is_sku_match'] = 1;
                    }
                }
            }
            //3.未匹配  状态为无需审核
            if(!in_array($row['sku'],$batch_sku)){
                $success_data[$k]['approve_state'] = 1;// 无需审核
                $success_data[$k]['is_sku_match'] = 2; //未匹配
                $success_data[$k]['sku_name'] = '';//产品名称
                $success_data[$k]['is_refund_tax'] = '';//是否退税
                $success_data[$k]['purchase_warehouse_id'] = '';//采购仓库
            }
            //4.表里已经存在的  状态为无需审核
            if(in_array($row['unique_str'],array_column($match,'unique_str'))){
                $success_data[$k]['approve_state'] = 1;// 过期无需审核
            }else{//表里不存在的
                if($success_data[$k]['approve_state'] == 2){
                    $new_unique[] = [
                        'sku' => $row['sku'],
                        'unique_str'=>$row['unique_str'],
                    ];
                }
            }
            unset($success_data[$k]['unique_str']);
        }


        //插入特殊列表
        $chunkData = array_chunk($success_data, 5000);//将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        unset($success_data);
        $success = 0;
        $batch_count = count($chunkData);

        for ($i = 0; $i < $batch_count; $i++) {

            $succ = $this->_ci->m_special_pr_list->upload($chunkData[$i]);//批量插入数据库
            $success +=$succ;
        }
        unset($chunkData);

        //备注信息插入备注表
        $chunkRemark = array_chunk($remark_data, 5000);//将这个10W+ 的数组分割成5000一个的小数组。这样就一次批量插入5000条数据。mysql 是支持的。
        unset($remark_data);
        $batch_count = count($chunkRemark);
        $this->_ci->load->model('Inland_special_pr_list_remark_model', 'm_special_remark');
        for ($i = 0; $i < $batch_count; $i++) {
            $this->_ci->m_special_remark->insert_batch($chunkRemark[$i]);//批量插入数据库
        }
        unset($chunkData);

        //插入标记唯一表
        if(count(array_unique($new_unique, SORT_REGULAR))>0){
            $this->_ci->m_special_unique->insert_batch(array_unique($new_unique, SORT_REGULAR));
        }

        $fail = $count - $success;

        $message['success'] = $success;
        $message['fail'] = $fail;
        return $message;

    }

    /**
     * 生成汇总单号
     */
    public function general_sum_sn($scene)
    {
        $ci = CI::$APP;
        $ci->load->service('basic/OrderSnPoolService');
        return $ci->ordersnpoolservice->setScene($scene)->pop();
    }

    /**
     * 时间处理
     */
    public function excelTime($date, $time = false) {
        //如果是数字则转化，如果是有 - 或者 /，视作文本格式不作处理
        $type1 = strpos($date, '/');
        $type2 = strpos($date, '-');
        if($type1 || $type2){
            $return_date = $date;
        }else{
            $return_date=date('Y-m-d',PHPExcel_Shared_Date::ExcelToPHP($date));
        }

        return $return_date;
    }


    /**
     * 获取所有用户的姓名转换表
     */
    public function get_all_saleman()
    {
        $this->_ci->load->service('basic/DyncOptionService');
        $oa_user =  $this->_ci->dyncoptionservice->get_dync_oa_user('');

        foreach ($oa_user as $key => &$val)
        {
            $tmp = explode(' ', $val);
            $val = $tmp[1] ?? $tmp[0];
        }
        return array_flip($oa_user);
    }

    /**
     * 将platform_code转platform_name
     * @param $platform_code
     * @return array|string
     */
    public function tran_platform()
    {
        $plaform_arr = INLAND_PLATFORM_CODE;
        foreach ($plaform_arr as $key => $value){
            $plaform_arr[$key] = $plaform_arr[$key]['name'];
        }
        return array_flip($plaform_arr);
//        exit;
//        $platform_code = explode(',', $platform_code);
//        $platform_name = [];
//        foreach ($platform_code as $value) {
//            $platform_name[] = INLAND_PLATFORM_CODE[$value]['name']??'-';
//        }
//        $platform_name = implode(',', $platform_name);
//        return $platform_name;
    }
    

}
