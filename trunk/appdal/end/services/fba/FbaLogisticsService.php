<?php

/**
 * FBA ERPSKU属性配置服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2019-09-04
 * @link
 */
class FbaLogisticsService
{
    public static $s_system_log_name = 'FBA-LOGISTICS';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_logistics_list_model', 'm_logistics', false, 'fba');
        $this->_ci->load->helper('fba_helper');

        return $this;
    }

    public function detail($id)
    {
        return $this->_ci->m_logistics->pk($id);
    }

    /**
     * 导入批量修改
     */
    public function modifyByExcel($params)
    {
//        $ids = implode("','",array_column($params,'id'));
//        $ids = sprintf("'%s'",$ids);
        $ids    = array_column($params, 'id');
        $result = $this->modify_cfg($params, $ids);
        if ($result['code'] == 500) {
            return 0;
        } elseif ($result['code'] == 200) {
            return $result['msg'];
        }
    }

    public function modifyOne($params)
    {
        $ids       = $params['id'];
        $arr[$ids] = $params;
        $result    = $this->modify_cfg($arr, $ids);
        if ($result['code'] == 500) {
            throw new \RuntimeException($result['msg'], $result['code']);

            return false;
        } elseif ($result['code'] == 200) {
            return true;
        }
    }

    public function import($params)
    {
        $this->_ci->load->classes('fba/classes/CsvWrite', BUSINESS_LINE_FBA);
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->load->model('Fba_logistics_list_log_model', 'm_logistics_log', false, 'fba');
        $this->_ci->load->model('Fba_logistics_cfg_history_model', 'm_cfg_history_model', false, 'fba');

        //全局规则的字段
        $global_field = ['ls', 'pt', 'bs', 'sc', 'sz', 'as_up'];

        //解析格式，获得数据
        $this->_ci->CsvWrite
        ->decode_csv_reader($params)
        ->bind_filter_cvs_rows(array($this->_ci->m_logistics, 'get_can_update'), $this->_ci->CsvWrite);

        $sellersku_map_refund_tax = $force_change_refundtax = $need_approve_ids = [];
        //获取修改的
        if (!empty($this->_ci->CsvWrite->valid_records)) {
            $sellersku_map_refund_tax = $this->_ci->m_logistics->get_sellersku_sku_refundtax(array_column($this->_ci->CsvWrite->valid_records, 'seller_sku'));
        }

        $this->_ci->CsvWrite
        ->set_can_edit_cols(['logistics_id', 'purchase_warehouse_id', 'rule_type', 'expand_factor', 'as_up', 'ls', 'pt', 'bs', 'sc', 'sz', 'listing_state'])
        ->register_columns_recalc_callback('updated_uid', function($new_row, $old_row){
            return get_active_user()->staff_code;
        })
        ->register_columns_recalc_callback('updated_zh_name', function($new_row, $old_row){
            return get_active_user()->user_name;
        })
        ->register_columns_recalc_callback('updated_zh_name', function($new_row, $old_row){
            return get_active_user()->user_name;
        })
        ->register_columns_recalc_callback('purchase_warehouse_id', function(&$new_row, $old_row) use ($sellersku_map_refund_tax, $force_change_refundtax) {
            //没有变更
            if ($new_row['purchase_warehouse_id'] == $old_row['purchase_warehouse_id']) {
                return $new_row['purchase_warehouse_id'];
            }
            /**
             * 退税仓 判断所有erpsku是否都退税
             */
            if ($new_row['purchase_warehouse_id'] == PURCHASE_WAREHOUSE_FBA_TAX_YES) {
                $sku_refundtax_info = $sellersku_map_refund_tax[$new_row['seller_sku']] ?? [];
                if (empty($sku_refundtax_info)) {
                    throw new \InvalidArgumentException(sprintf('sellersku:%s 没有对应的sku退税信息', $new_row['seller_sku']), 500);
                }
                if (!array_same_value($sku_refundtax_info, '', REFUND_TAX_YES)) {
                    throw new \InvalidArgumentException(sprintf('sellersku:%s 对应的sku不全是退税，不能修改', $new_row['seller_sku']), 500);
                }
                //将其他的修改为退税仓
                foreach ($sku_refundtax_info as $id => $refund_tax) {
                    if ($id != $new_row['id']) {
                        $force_change_refundtax[$id] = ['id' => $id, 'purchase_warehouse_id' => PURCHASE_WAREHOUSE_FBA_TAX_YES ];
                    }
                }
            } else {
                //修改为不退税仓， 同sellersku也关联修改
                foreach ($sku_refundtax_info as $id => $refund_tax) {
                    if ($id != $new_row['id']) {
                        $force_change_refundtax[$id] = ['id' => $id, 'purchase_warehouse_id' => $new_row['purchase_warehouse_id'] ];
                    }
                }
            }
            return $new_row['purchase_warehouse_id'];
        })
        ->register_columns_recalc_callback('expand_factor', function($new_row, $old_row) {
            return $new_row['expand_factor'];
        })
        ->register_columns_recalc_callback('refund_rate', function($new_row, $old_row) {
            if (!$new_row['refund_rate'])
                {
                    $new_row['refund_rate'] = 0.00;
                } else {
                $new_row['refund_rate'] = number_format(floatval($new_row['refund_rate']), 2);
                }
            return $new_row['refund_rate'];
        })
        ->register_columns_recalc_callback('rule_type', function(&$new_row, $old_row) use ($global_field, &$need_approve_ids) {
            /**
                'as_up' => '上架时效(AS)',
                'ls' => '物流时效(LS)',
                'pt' => '打包时效(PT)',
                'bs' => '缓冲库存(BS)',
                'sc' => '一次备货天数(SC)',
                'sz' => '服务对应"Z"值',
             */
            if ($new_row['rule_type'] == $old_row['rule_type']) {
                return $new_row['rule_type'];
            }

            $new_row['approve_state'] = CHECK_STATE_INIT;
            //修改为自定义
            if ($new_row['rule_type'] == RULE_TYPE_GLOBAL) {
                //修改为全局
                foreach ($global_field as $col) {
                    $new_row[$col] = 0;
                }
            } else {
                $need_approve_ids[] = $new_row['id'];
                log_message('INFO', sprintf('记录id:%d由全局修改为自定义', $new_row['id']));
            }
            return $new_row['rule_type'];
        })
        //因其他字段关联产生的变化，必须注册
        ->register_columns_recalc_callback('approve_state', function($new_row, $old_row) {
            return $new_row['approve_state'];
        })
        /*->register_columns_recalc_callback('listing_state', function(&$new_row, $old_row) {
            return $new_row['listing_state'];
        })
        ->register_columns_recalc_callback('listing_state_text', function($new_row, $old_row) {
            return LISTING_STATE[$new_row['listing_state']]['name'];
        })*/
        ;

        $m_cfg_history_model = $this->_ci->m_cfg_history_model;
        //前置回调
        //->register_columns_recalc_callback('fixed_amount', function(){})
        //设置语言文件
        $this->_ci->CsvWrite->set_langage('fba_logistics_list')
        //设置更新表model
        ->set_model($this->_ci->m_logistics)
        //日志model
        ->set_log_model($this->_ci->m_logistics_log)
        ->set_log_func(function($primary, $active_user, $context) {
            return [
                'log_id' => $primary,
                'op_uid' => $active_user->staff_code,
                'op_zh_name' => $active_user->user_name,
                'context' => $context,
                'created_at' => date('Y-m-d H:i:s')
            ];
        })
        ->set_tran_relation_func('trans_approve_history', function() use ($m_cfg_history_model, &$need_approve_ids) {
            log_message('INFO', '规则由全局变为自定义，写入记录到历史记录表：id为'.implode(',', $need_approve_ids));
            return $m_cfg_history_model->modify_save_to_history($need_approve_ids);
        })
        ;

        unset($params, $sellersku_map_refund_tax, $force_change_refundtax, $need_approve_ids);

        $this->_ci->CsvWrite->run();

        return $this->_ci->CsvWrite->report;
    }

    /**
     * 可修改项:物流属性,listing_状态,采购仓库,规则,扩销系数,上架时效,物流时效,打包时效,缓冲库存,一次备货天数,服务对应的Z值
     *
     * @param $params
     */
    public function modify_cfg($params, $ids)
    {
//        pr($params);exit;
        $this->_ci->load->model('Fba_logistics_cfg_history_model', 'm_cfg_history_model', false, 'fba');
        $msg['code'] = 200;
        $processed   = 0;//已处理
        $undisposed  = 0;//未处理
        //查询是否存在
        if (!$cfg_info = $this->_ci->m_logistics->get_cfg($ids)) {
            throw new InvalidArgumentException('要修改的记录异常,请稍后重试', 3001);
        }
        if (is_array($ids)) {
            $plan_ids = array_column($cfg_info, 'id');
            $diff     = array_diff($ids, $plan_ids);
            $diff     = implode(',', $diff);
            if (!empty($diff)) {
                throw new InvalidArgumentException(sprintf('要修改的记录异常,%s 不存在', $diff), 3001);
            }
        }

        $check_ids = [];
        //组织要修改的记录
        $diff         = [];
        $global_field = ['ls', 'pt', 'bs', 'sc', 'sz', 'as_up'];//全局规则的字段
        $count        = 0;
        $update_data  = [];

        foreach ($params as $key => &$item) {//有一项修改不符合规则 就continue
            //修改采购仓库判断,开始
            $result = $this->_ci->m_logistics->check_is_refund_tax($cfg_info[$key]['seller_sku']);

            if (!$result) {
                $msg['code'] = 500;
                $msg['msg']  = '修改失败,该记录异常';
                unset($params[$key]);
                continue;
            } else {
                if ($item['purchase_warehouse_id'] == PURCHASE_WAREHOUSE_FBA_TAX_YES) {//如果要修改为退税仓则需判断 改记录的seller_sku下的所有sku是否全都为退税,是:可以修改,否:不能修改
                    $error = false;
                    foreach ($result as $tax) {
                        if ($tax != REFUND_TAX_YES) {
                            $msg['code'] = 500;
                            $msg['msg']  = '修改失败,该记录的seller_sku下的记录都为退税才能修改为退税仓';
                            $error       = true;
                            continue;
                        }
                    }
                    if ($error) {
                        unset($params[$key]);
                        continue;
                    } else {
                        foreach ($result as $id => $tax) {
                            if ($id == $item['id']) {
                                continue;
                            }
                            $update_data[] = [//其他的也相应的改成退税仓
                                'id'                    => $id,
                                'purchase_warehouse_id' => PURCHASE_WAREHOUSE_FBA_TAX_YES,
                            ];
                        }
                    }
                } else {
                    foreach ($result as $id => $tax) {
                        if ($id == $item['id']) {
                            continue;
                        }
                        $update_data[] = [//同seller_sku的也要修改采购仓库
                            'id'                    => $id,
                            'purchase_warehouse_id' => $item['purchase_warehouse_id'],
                        ];
                    }
                }
            }
            //修改采购仓库判断,结束
            //修改规则判断,开始

            /*            if ($item['rule_type'] == RULE_TYPE_DIY && $cfg_info[$key['rule_type']] == RULE_TYPE_DIY){//自定义改自定义
                            foreach ($item as $k => $val){
                                if ($k == 'id'){
                                    continue;
                                }
                                if ($cfg_info[$key][$k] != $val){
            //                    $diff[$key][$k] = $val;
                                    $count++;
                                }else{
                                    unset($item[$k]);
                                }
                            }
                            if (count($item) == 1){//没有要更新的
                                unset($params[$key]);
                            }
                        }elseif ($item['rule_type'] == RULE_TYPE_GLOBAL && $cfg_info[$key['rule_type']] == RULE_TYPE_DIY){//自定义改全局
                            foreach ($item as $k => &$val){
                                if ($k == 'id'){
                                    continue;
                                }
                                if (in_array($k,$global_field)){
                                    $val = 0;
                                }
                                if ($cfg_info[$key][$k] != $val){
            //                    $diff[$key][$k] = $val;
                                    $count++;
                                }else{
                                    unset($item[$k]);
                                }
                            }
                            if (count($item) == 1){//没有要更新的
                                unset($params[$key]);
                            }
                        }elseif ($item['rule_type'] == RULE_TYPE_GLOBAL && $cfg_info[$key['rule_type']] == RULE_TYPE_GLOBAL){//全局改全局

                        }elseif ($item['rule_type'] == RULE_TYPE_DIY && $cfg_info[$key['rule_type']] == RULE_TYPE_GLOBAL){//全局改自定义

                        }*/
            if ($item['rule_type'] == RULE_TYPE_DIY) {//修改为自定义
                foreach ($item as $k => &$val) {
                    if ($k == 'id' || $k == 'seller_sku') {
                        continue;
                    }
                    if ($cfg_info[$key][$k] != $val) {
                        //                    $diff[$key][$k] = $val;
                        $count++;
                    } else {
                        unset($item[$k]);
                    }
                }
                if (count($item) == 1) {//没有要更新的
                    unset($params[$key]);
                } else {//需要更新的
                    $check_ids[]           = $item['id'];
                    $item['approve_state'] = CHECK_STATE_INIT;
                    $key_map               = [
                        //session => db_col
                        'userNumber' => 'updated_uid',
                        'userName'   => 'updated_zh_name',
                    ];
                    append_login_info($item, $key_map);

                }
            } else if ($item['rule_type'] == RULE_TYPE_GLOBAL) {//修改为全局
                foreach ($item as $k => &$val) {
                    if ($k == 'id' || $k == 'seller_sku') {
                        continue;
                    }
                    if (in_array($k, $global_field)) {//全局规则的字段,更新为0
                        $val = 0;
                    }
                    if ($cfg_info[$key][$k] != $val) {
                        //                    $diff[$key][$k] = $val;
                        $count++;
                    } else {
                        unset($item[$k]);
                    }
                }
                if (count($item) == 1) {//没有要更新的
                    unset($params[$key]);
                } else {
                    $check_ids[]           = $item['id'];
                    $item['approve_state'] = CHECK_STATE_INIT;
                    $key_map               = [
                        //session => db_col
                        'userNumber' => 'updated_uid',
                        'userName'   => 'updated_zh_name',
                    ];
                    append_login_info($item, $key_map);
                }
            }
        }
        if ($count == 0) {//没有修改直接返回
            $msg['code'] = 500;
            $msg['msg']  = '您未作任何修改，无法保存！';

            return $msg;
        }
//        pr($params);exit;
        //事务处理数据库操作
        $this->_ci->load->model('Fba_logistics_list_log_model', 'm_log', false, 'fba');
        $db = $this->_ci->m_logistics->getDatabase();
        $db->trans_start();
        $this->_ci->m_cfg_history_model->check_approve_state_add($check_ids);
        $affect_rows = $db->update_batch($this->_ci->m_logistics->getTable(), $params, 'id');
        $this->_ci->m_log->modifyLog($params);//写入修改日志
        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            log_message('ERROR', sprintf("批量修改异常%s", json_encode($params)));
        }
        $msg['msg'] = $affect_rows;

        return $msg;
    }

    public function update_column($params, $user_id = '', $user_name = '')
    {
        try {
            $result = ["processed" => 0,"undisposed" => 0];
            $update_data = [
                $params['column'] => $params['column_value'],//需求数量,
                'updated_at' => date('Y-m-d H:i:s'),//更新时间
                'updated_uid' => $user_id,//更新uid
                'updated_zh_name' => $user_name,//更新姓名
            ];
            $db = $this->_ci->m_logistics->getDatabase();
            $db->trans_start();
            $row_count = $this->_ci->m_logistics->update_column($update_data);
            $db->trans_complete();
            $result["processed"] = $row_count;

        } catch (Exception $e) {
            log_message('ERROR', sprintf("sellersku批量修改{$params['column']}:{$params['column_value']}异常 %s", $e->getMessage()));
            throw new \RuntimeException(sprintf("sellersku批量修改{$params['column']}:{$params['column_value']}异常 %s", $e->getMessage()), 500);
        }
        finally{
            return $result;
        }
    }

    public function import_listing_state($params,$user_id,$user_name)
    {
        //$primary_key = $params['primary_key']??'id';
        $selected = json_decode($params['selected'],true) ?? [];
        $total = $params['total']??0;
        $result = [
            'total'      => $total,
            'processed'  => 0,
            'undisposed' => 0,
            'errorMess'  => '',
            'succLines' => [],
            'errorLines' => []
        ];
        $map = $params['map'];//json_decode($params['map'],true)
        $ids = array_keys($selected);
        $logistics_data = $this->_ci->m_logistics->pks($ids);
        $logistics_update_data = $log_insert_data =$pan_eu_data = [];
        $now = date('Y-m-d H:i:s', time());
        if(empty($logistics_data))
        {
            $result['errorLines'] = array_reduce($selected,create_function('$result, $v', '$result[] = $v[0]["line_index"];return $result;'));
            $result['undisposed'] = $total;
            return $result;
        }
        foreach ($selected as $select_key => $select_value)
        {
            if(!in_array($select_key,array_column($logistics_data,'id')))
            {
                array_push($result['errorLines'],$select_key);
            }
            foreach ($logistics_data as $logistics_value)
            {
                $pan_eu = $logistics_value['pan_eu'];
                if($select_key == $logistics_value['id']){
                    if($pan_eu == LISTING_STATE_OPERATING)
                    {
                        $pan_eu_data[$logistics_value['id']] = [
                            'updated_uid'=>$logistics_value['updated_uid'],
                            'updated_at'=>$logistics_value['updated_at'],
                            'listing_state'=>$logistics_value['listing_state'],
                            'listing_state_text'=>$logistics_value['listing_state_text']
                        ];
                        foreach ($select_value as $site_value){
                            $site = $site_value[$map['site']];
                            $listing_state = $site_value[$map['listing_state']];
                            $pan_eu_data[$logistics_value['id']]['data'][] = [
                                'site'=>$site,
                                'listing_state'=>$listing_state
                            ];
                            array_push($result['succLines'],$site_value['line_index']);
                        }
                    }
                    else
                    {
                        $listing_state = $select_value[0][$map['listing_state']];
                        $old_state = empty(LISTING_STATE[$logistics_value['listing_state']]) ? LISTING_STATE[LISTING_STATE_OPERATING]['name']: LISTING_STATE[$logistics_value['listing_state']]['name'];
                        $new_state = LISTING_STATE[$listing_state]['name'];
                        if($old_state == $new_state && $logistics_value['listing_state_text'] == LISTING_STATE[$listing_state]['name'])
                        {
                            array_push($result['succLines'],$select_value[0]['line_index']);
                            continue;
                        }
                        $logistics_update_data[] = [
                            'id'=>$logistics_value['id'],
                            'listing_state'=>$listing_state,
                            'listing_state_text'=>LISTING_STATE[$listing_state]['name'],
                            'updated_at' => $now,//更新时间
                            'updated_uid' => $user_id,//更新uid
                            'updated_zh_name' => $user_name//更新姓名
                        ];
                        $log_insert_data[] = [
                            'log_id' => $logistics_value['id'],
                            'op_uid' => $user_id,
                            'op_zh_name' => $user_name,
                            'context' => "导入（设置listing状态）:原状态:".$old_state.";现状态:".$new_state,
                            'created_at' => $now
                        ];
                        array_push($result['succLines'],$select_value[0]['line_index']);
                    }

                }
            }
/*            if(!in_array($select_key,array_column($logistics_data,'id')))
            {
                array_push($result['errorLines'],$select_value['line_index']);
            }*/
        }
        //echo '$logistics_update_data:'.json_encode($logistics_update_data).PHP_EOL;
        //echo '$log_insert_data:'.json_encode($log_insert_data).PHP_EOL;
        //echo '$pan_eu_data:'.json_encode($pan_eu_data).PHP_EOL;exit;
        //范欧数据处理
        $logistics_update_data_pan = $log_insert_data_pan = [];
        foreach($pan_eu_data as $pan_eu_key=>$pan_eu_value)
        {
            $listing_state_text = "";//英国:运营;德国:不运营;法国:运营;
            $listing_state_pan = LISTING_STATE_STOP_OPERATE;
            $pan_eu_value_data = $pan_eu_value['data'];
            $listing_state_pan_old = $pan_eu_value['listing_state'];
            $listing_state_text_pan_old = $pan_eu_value['listing_state_text'];

            $updated_uid_old = $pan_eu_value['updated_uid'];
            $updated_at_old = $pan_eu_value['updated_at'];
            $last_update_time = time() - strtotime($updated_at_old);
            $listing_state_text_old = "";
            if($updated_uid_old == $user_id && $last_update_time < 20 * 60)
            {
                $listing_state_text_old = $pan_eu_value['listing_state_text'];
            }
            $uk = $de = $fr = $it = $sp = 0;
            foreach ($pan_eu_value_data as $pan_eu_value_info)
            {
                if($pan_eu_value_info['listing_state'] == LISTING_STATE_OPERATING)
                {
                    $listing_state_pan = LISTING_STATE_OPERATING;
                }
                $is_site_exits = strstr($listing_state_text_old,FBA_STATION_CODE[$pan_eu_value_info['site']]['name']);
                if(empty($listing_state_pan_old) || (!empty($listing_state_pan_old) && $is_site_exits === false))
                {
                    $listing_state_text .= FBA_STATION_CODE[$pan_eu_value_info['site']]['name'].':'.LISTING_STATE[$pan_eu_value_info['listing_state']]['name'].';';
                }
                switch ($pan_eu_value_info['site'])
                {
                    case "uk":
                        $uk = $pan_eu_value_info['listing_state'];
                        break;
                    case "de":
                        $de = $pan_eu_value_info['listing_state'];
                        break;
                    case "fr":
                        $fr = $pan_eu_value_info['listing_state'];
                        break;
                    case "it":
                        $it = $pan_eu_value_info['listing_state'];
                        break;
                    case "sp":
                        $sp = $pan_eu_value_info['listing_state'];
                        break;
                }
            }
            $listing_state_text .= $listing_state_text_old;
            if($listing_state_pan_old == $listing_state_pan && $listing_state_text_pan_old == $listing_state_text)
            {
                continue;
            }
            $logistics_update_data_pan[] = [
                'id'=>$pan_eu_key,
                'listing_state'=>$listing_state_pan,
                'listing_state_text'=>$listing_state_text,
                'uk'=>$uk,
                'de'=>$de,
                'fr'=>$fr,
                'it'=>$it,
                'sp'=>$sp,
                'updated_at' => $now,//更新时间
                'updated_uid' => $user_id,//更新uid
                'updated_zh_name' => $user_name//更新姓名
            ];
            $log_text = "导入（设置listing状态）:原状态:".LISTING_STATE[$listing_state_pan_old]['name'].";现状态:".LISTING_STATE[$listing_state_pan]['name'].".原明细:".
                        $listing_state_text_pan_old.",现明细:{$listing_state_text}";
            $log_insert_data_pan[] = [
                'log_id' => $pan_eu_key,
                'op_uid' => $user_id,
                'op_zh_name' => $user_name,
                'context' => $log_text,
                'created_at' => $now,
            ];
        }
        //echo '$logistics_update_data_pan:'.json_encode($logistics_update_data_pan).PHP_EOL;
        //echo '$log_insert_data_pan:'.json_encode($log_insert_data_pan).PHP_EOL;exit;

        $logistics_update_data_all = array_merge($logistics_update_data,$logistics_update_data_pan);
        unset($logistics_update_data,$logistics_update_data_pan);
        $log_insert_data_all = array_merge($log_insert_data,$log_insert_data_pan);
        unset($log_insert_data,$log_insert_data_pan);
        //echo '$logistics_update_data_all:'.json_encode($logistics_update_data_all).PHP_EOL;
        //echo '$log_insert_data_all:'.json_encode($log_insert_data_all).PHP_EOL;exit;
        $db = $this->_ci->m_logistics->getDatabase();
        try
        {
            if(count($logistics_update_data_all))
            {
                $db->trans_start();
                $update_rows = $db->update_batch($this->_ci->m_logistics->getTable(), $logistics_update_data_all, 'id');
                if (!$update_rows)
                {
                    $report['errorMess'] = '导入（设置listing状态）失败';
                    throw new \RuntimeException($report['errorMess']);
                }

                $this->_ci->load->model('Fba_logistics_list_log_model', 'fba_log_list_log', false, 'fba');
                $insert_rows = $this->_ci->fba_log_list_log->madd($log_insert_data_all);
                if (!$insert_rows)
                {
                    $report['errorMess'] = '导入日志插入失败';
                    throw new \RuntimeException($report['errorMess']);
                }

                unset($logistics_update_data_all, $log_insert_data_all);
                $db->trans_complete();
                if ($db->trans_status() === false)
                {
                    $report['errorMess'] = '导入（设置listing状态），事务提交成功，但状态检测为false';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            $result['processed'] = $result['total'] - count($result['errorLines']);
            $result['undisposed'] = count($result['errorLines']);
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();
            log_message('ERROR', sprintf('导入（设置listing状态）%s，提交事务出现异常: %s', json_encode($logistics_update_data_all), $e->getMessage()));
            unset($logistics_update_data_all, $log_insert_data_all);
            $result['undisposed'] = $result['total'];
            $result['errorMess'] = '批量审核抛出异常：'.$e->getMessage();
        }
        finally
        {
            return $result;
        }
    }
}
