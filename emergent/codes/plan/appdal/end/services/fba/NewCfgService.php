<?php

/**
 * FBA 新品服务
 *
 * @author zc
 * @since 2019-10-22
 * @link
 */
class NewCfgService
{
    public static $s_system_log_name = 'FBA';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_new_list_model', 'm_fba_new', false, 'fba');
        $this->_ci->load->helper('fba_helper');
        return $this;
    }

    /**
     * 添加备注
     *
     * @param unknown $post
     * @return unknown
     */
    public function update_remark($post)
    {
        $report = [
            'data' => false,
            'errorMess'  => ''
        ];

        $record = $this->_ci->m_fba_new->pk($post['id']);
        if (empty($record))
        {
            $report['errorMess'] = '无效的记录';
            return $report;
        }

        $active_user = get_active_user();
        //$salesman = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? '*' : $active_user->staff_code;
        //$manager_accounts = $active_user->get_my_manager_accounts();
        //$account_nums = $active_user->get_my_manager_account_nums();

        /*if (!($salesman == '*' || $record['salesman'] == $salesman || in_array($record['account_num'], $account_nums)))
        {
            $report['errorMess'] = '没有权限，需要销售本人或者账号管理员';
            return $report;
        }*/

        $updated_at =  date('Y-m-d H:i:s');
        $updated_uid = $active_user->staff_code;
        $updated_zh_name  = $active_user->user_name;

        $batch_update_pr = $batch_insert_log = [];

        $batch_update_pr[] = [
            'id' => $record['id'],
            'remark' => $post['remark'],
            'updated_uid' => $active_user->staff_code,
            'updated_at' => $updated_at,
            'updated_zh_name' => $updated_zh_name
        ];
        $batch_insert_log[] = [
            'new_id' => $record['id'],
            'uid' => $updated_uid,
            'user_name' => $updated_zh_name,
            'context' => '添加备注：'.$post['remark'],
        ];

        $this->_ci->load->model('Fba_new_log_model', 'fba_new_log', false, 'fba');

        $db = $this->_ci->m_fba_new->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->m_fba_new->getTable(), $batch_update_pr, 'id');
            if (!$update_rows)
            {
                $report['errorMess'] = '添加备注失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->fba_new_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $report['errorMess'] = '添加备注插入失败';
                throw new \RuntimeException($report['errorMess']);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '添加备注，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['data'] = true;
            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('添加备注更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = $records = null;
            unset($batch_update_pr, $batch_insert_log, $records);

            $report['errorMess'] = '添加备注抛出异常：'.$e->getMessage();
            return $report;
        }
    }


    /**
     * 批量审核
     *
     * @param unknown $post
     * @return unknown
     */
    public function batch_approve($post)
    {

        $valid_ids =is_string($post['id']) ? explode(',', $post['id']) : $post['id'];

        if (empty($valid_ids) || empty($post['id'])) {
            $report['errorMess'] = '没有有效的审核记录';
            return $report;
        }

        $report = [
            'total'      => count($valid_ids),
            'processed'  => 0,
            'undisposed' => count($valid_ids),
            'errorMess'  => ''
        ];

        $approve_state = intval($post['result']);
        $updated_at =  date('Y-m-d H:i:s');

        $batch_update_pr = $batch_insert_log = $pr_update_log = [];
        $batch_update_logistics = $batch_insert_logistics = [];
        foreach ($valid_ids as $id)
        {
            $batch_update_pr[] = [
                'id' => $id,
                'approve_state' => $approve_state,
                'approved_uid' => $post['user_id'],
                'approved_at' => $updated_at,
                'approved_zh_name' => $post['user_name'],
            ];
            $batch_insert_log[] = [
                'new_id' => $id,
                'uid' => $post['user_id'],
                'user_name' => $post['user_name'],
                'context' => '新品审核'.($approve_state == NEW_APPROVAL_FAIL ? '失败' : '成功'),
            ];

            //审核通过,根据相同的维度找到yibai_fba_pr_list匹配的记录，将is_first_sale 设置为1，
            //同时更新require_qty_second, require_qty 数量为上传的需求数量 - 已备货数量，同时更新updated_at, updated_uid字段
            if($approve_state == NEW_APPROVAL_SUCCESS)
            {
                $new_data = $this->_ci->m_fba_new->get_base_info($id);
                $this->_ci->load->model('Fba_pr_list_model', 'm_pr', false, 'fba');
                $account_num = preg_replace('/([\x80-\xff]*)/i','',$new_data['account_name']);
                if(!empty($new_data))
                {
                    $pr_data = [
                        'account_name'=>$new_data['account_name'],
                        'station_code'=>strtolower($new_data['site']),
                        'fnsku'=>$new_data['fnsku'],
                        'asin'=>$new_data['asin'],
                        'seller_sku'=>$new_data['seller_sku']
                    ];
                    $pr_info = $this->_ci->m_pr->get_info_by_dimension($pr_data);
                    $require_qty_second = $new_data['demand_num'] - $new_data['stock_num'];
                    foreach($pr_info as $pr_info_key => $pr_info_value)
                    {
                        if($pr_info_value['is_first_sale'] != IS_FIRST_SALE_YES || $pr_info_value['require_qty'] != $new_data['demand_num'])
                        {
                            $pr_update_log[] = [
                                'gid' => $pr_info_value['gid'],
                                'is_first_sale' => IS_FIRST_SALE_YES,
                                'require_qty' => $new_data['demand_num'],
                                'require_qty_second' => $require_qty_second,
                                'updated_at'=>time(),
                                'updated_uid'=>$post['user_id']
                            ];
                        }
                    }

                    //seller_sku属性表
                    //1.判断同account_id,sellersku,erpsku,fnsku，asin的字段在SELLERSKU属性配置表中是否存在,Fba_logistics_list_model
                    $this->_ci->load->model('Fba_logistics_list_model', 'm_logistic', false, 'fba');
                    $lc_select_data = [
                        'account_id'=>$new_data['account_id'],
                        'seller_sku'=>$new_data['seller_sku'],
                        'sku'=>$new_data['erp_sku'],
                        'fnsku'=>$new_data['fnsku'],
                        'asin'=>$new_data['asin']
                    ];
                    $logistic_info = $this->_ci->m_logistic->get_info_by_account_sku_asin($lc_select_data);
                    if(empty($logistic_info))
                    {
                        //如果不存在，则在SELLERSKU属性配置表中新增一条记录
                        $batch_insert_logistics[] = [
                            "account_num"=>$account_num,
                            "site"=>strtolower($new_data['site']),
                            "pan_eu"=>IS_PAN_EU_NO,//泛欧 1是 2否
                            "sale_group_id"=>$new_data['sale_group'],
                            "sale_group_zh_name"=>$new_data['sale_group_name'],
                            "salesman_id"=>$new_data['salesman'],
                            "salesman_zh_name"=>$new_data['staff_zh_name'],
                            "account_id"=>$new_data['account_id'],
                            "account_name"=>$new_data['account_name'],
                            "seller_sku"=>$new_data['seller_sku'],
                            "sku"=>$new_data['erp_sku'],
                            "fnsku"=>$new_data['fnsku'],
                            "asin"=>$new_data['asin'],
                            "station_code"=>strtolower($new_data['site']),
                            "created_at"=>date('Y-m-d H:i:s'),
                            "original_sku"=>$new_data['erp_sku'],
                            "is_first_sale" => IS_FIRST_SALE_YES
                        ];
                    }
                    else if($logistic_info['is_first_sale'] != IS_FIRST_SALE_YES)
                    {
                        //如果存在并且非首发，则将该记录的是否新品字段设置为是
                        $batch_update_logistics[] = [
                            "id" =>$logistic_info['id'],
                            "is_first_sale" => IS_FIRST_SALE_YES,
                            "updated_uid" => $post['user_id'],
                            "updated_zh_name" => $post['user_name'],
                            "updated_at" => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }
        }
        $this->set_sku_name($batch_insert_logistics);
        //echo '$batch_update_logistics:'.json_encode($batch_update_logistics).PHP_EOL;
        //echo '$batch_insert_logistics:'.json_encode($batch_insert_logistics).PHP_EOL;exit;

        $this->_ci->load->model('Fba_new_log_model', 'fba_new_log', false, 'fba');

        $db = $this->_ci->m_fba_new->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->m_fba_new->getTable(), $batch_update_pr, 'id');
            if (!$update_rows)
            {
                $report['errorMess'] = '批量审核 更新失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->fba_new_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $report['errorMess'] = '批量审核日志插入失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //fbapr需求列表批量更新
            if(!empty($pr_update_log))
            {
                $pr_update_rows =$this->_ci->m_pr->batch_update($pr_update_log);
                if (!$pr_update_rows)
                {
                    $report['errorMess'] = '批量需求列表更新失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            //SELLERSKU属性配置表批量更新
            if(!empty($batch_update_logistics))
            {
                $logistic_update_rows = $this->_ci->m_logistic->batch_update($batch_update_logistics);
                if (!$logistic_update_rows)
                {
                    $report['errorMess'] = '批量SELLERSKU属性配置表更新失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            //SELLERSKU属性配置表批量添加
            if(!empty($batch_insert_logistics))
            {
                $logistic_insert_rows = $this->_ci->m_logistic->madd($batch_insert_logistics);
                if (!$logistic_insert_rows)
                {
                    $report['errorMess'] = '批量SELLERSKU属性配置表添加失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '批量审核，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $update_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = $logistic_update_rows = $logistic_insert_rows = null;
            unset($batch_update_pr, $batch_insert_log,$logistic_update_rows,$logistic_insert_rows);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('批量审核更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            $report['errorMess'] = '批量审核抛出异常：'.$e->getMessage();
            return $report;
        }
    }


    public function batch_approve_all($post){
        $approving_info = $this->_ci->m_fba_new->get_approving_info($post);
        if(empty($approving_info) || !count($approving_info))
        {
            $report = [
                'total'      => 0,
                'processed'  => 0,
                'undisposed' => 0,
                'errorMess'  => ''
            ];
        }
        else
        {
            $post['id'] = array_column($approving_info,'id');
            $report = $this->batch_approve($post);
        }
        return $report;
    }

    public function set_sku_name(&$data)
    {
        $this->_ci->load->model('Fba_logistics_list_model', 'm_logistic', false, 'fba');
        $product_info = $this->_ci->m_logistic->get_product_info($data);
        foreach ($data as $key=>$value)
        {
            $data[$key]['sku_name'] = $product_info[$value['sku']]['title']??'';
        }
    }

    /**
     * 批量删除
     *
     * @param unknown $post
     * @return unknown
     */
    public function batch_del($post)
    {
        $valid_ids =is_string($post['id']) ? explode(',', $post['id']) : $post['id'];
        if (empty($valid_ids) || empty($post['id'])) {
            $report['errorMess'] = '没有有效的删除记录';
            return $report;
        }

        $report = [
            'total'      => count($valid_ids),
            'processed'  => 0,
            'undisposed' => count($valid_ids),
            'errorMess'  => ''
        ];

        $batch_update_pr = $batch_insert_log = [];

        $ids = '';
        foreach ($valid_ids as $id)
        {
            $batch_delete_pr[] = [
                'id' => $id,
            ];
            $batch_insert_log[] = [
                'new_id' => $id,
                'uid' => $post['user_id'],
                'user_name' => $post['user_name'],
                'context' => '新品操作删除',
            ];
        }

        $this->_ci->load->model('Fba_new_log_model', 'fba_new_log', false, 'fba');

        $db = $this->_ci->m_fba_new->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            //$update_rows = $db->update_batch($this->_ci->m_fba_new->getTable(), $batch_update_pr, 'id');
            $delete_rows = $this->_ci->m_fba_new->delete_batch($batch_delete_pr);
            if (!$delete_rows)
            {
                $report['errorMess'] = '批量删除失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->fba_new_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $report['errorMess'] = '日志批量插入失败';
                throw new \RuntimeException($report['errorMess']);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '批量删除，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $delete_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('批量删除更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            $report['errorMess'] = '批量删除抛出异常：'.$e->getMessage();
            return $report;
        }
    }

    public function import_batch_old($params = [], $user_id = '', $user_name = '')
    {
        $num = count($params);
        $report = [
            'total'      => $num,
            'processed'  => 0,
            'undisposed' => $num,
            'errorMess'  => ''
        ];
        $batch_update_new = $batch_insert_new = $batch_insert_ids = [];
        $batch_insert_row_num = $batch_update_row_num = [];
        $this->_ci->load->model('Fba_new_list_model', 'm_fba_new', false, 'fba');//
        //1.验证是否新品
        $this->_ci->load->model('Fba_old_model', 'm_fba_old', false, 'fba');//
        $new_params = $line_num = [];
        //echo "params:".json_encode($params).PHP_EOL;
        foreach ($params as $key => $value) {
            $this->_ci->load->model('Fba_amazon_account_model', 'm_amazon_account', false, 'fba');
            //$value['account_id'] = $this->_ci->m_amazon_account->get_account_id_by_name(trim($value['account_name']));
            $old_id = $this->_ci->m_fba_old->get_id($value);
            $old_exist = $this->_ci->m_fba_old->exists_id($old_id);
            if(!$old_exist)
            {
                $new_params[] = $value;
            }
            else
            {
                array_push($line_num,$value['line_num']);
            }
        }
        //echo "new_params:".json_encode($new_params).PHP_EOL;exit;

        $now = date('Y-m-d H:i:s');
        foreach ($new_params as $key => $value) {
            //2.验证账号、站点、sellersku、erpsku、fnsku、asin是否存在记录
            $id = $this->_ci->m_fba_new->get_id($value);
            $result_data = $this->_ci->m_fba_new->get_info_by_id($id);
            if (empty($result_data)) {
                //3.1.不存在则添加
                $seach_params = [
                    'pageSize' => 1,
                    'isDel' => 0,
                    'userName' => trim($value['staff_zh_name'])
                ];
                $list = RPC_CALL('YB_J1_004', $seach_params);
                $salsman = $list['data']['records'][0]['userNumber'] ?? 0;
                $this->_ci->load->model('Fba_amazon_group_model', 'm_amazon_group', false, 'fba');
                $sale_group = $this->_ci->m_amazon_group->get_group_id(trim($value['sale_group']));
                if(!in_array($id,$batch_insert_ids))
                {
                    $batch_insert_new[] = [
                        'id'=> $id,
                        'sale_group' => $sale_group,//销售分组id
                        'salesman' => $salsman,
                        'staff_zh_name'   => trim($value['staff_zh_name']),
                        'site' => strtolower($value['site']),
                        'seller_sku'   => trim($value['seller_sku']),
                        'erp_sku'  => trim($value['erpsku']),
                        'fnsku'       => trim($value['fnsku']),
                        'asin'        => trim($value['asin']),
                        'demand_num' => trim($value['demand_num']),//需求数量
                        'created_at' => $now,//更新时间
                        'created_uid' => $user_id,//更新uid
                        'created_zh_name' => $user_name,//更新uid
                        'approve_state' => NEW_APPROVAL_INIT,//审核状态
                        'sale_group_name' => trim($value['sale_group']),//销售分组id
                        'account_name' => trim($value['account_name']),
                        'account_id' => $value['account_id']
                    ];
                    array_push($batch_insert_ids,$id);
                    array_push($batch_insert_row_num,$value['line_num']);
                }
                else
                {
                    array_push($line_num,$value['line_num']);
                    array_push($batch_update_row_num,$value['line_num']);
                }
            }
            else if($result_data['is_delete'] == 0 && $result_data['demand_num'] != $value['demand_num'] && !$result_data['approve_state']){
                //3.2.存在并且处于待审核则更新,更新需求数量，更新时间刷新，状态待审核
                $batch_update_new[] = [
                    'id'=> $id,
                    'demand_num' => $value['demand_num'],//需求数量
                    'updated_at' => date('Y-m-d H:i:s'),//更新时间
                    'updated_uid' => $user_id,//更新uid
                    'updated_zh_name' => $user_name,//更新uid
                    'approve_state' => 0,//审核状态
                ];
            }
            else
            {
                array_push($line_num,$value['line_num']);
            }

        }

        $db = $this->_ci->m_fba_new->getDatabase();
        try
        {
            $db->trans_start();

            //批量更新
            $update_rows = 0;
            if(!empty($batch_update_new))
            {
                $update_rows = $db->update_batch($this->_ci->m_fba_new->getTable(), $batch_update_new, 'id');
                if (!$update_rows)
                {
                    $report['errorMess'] = '批量导入,更新失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            //批量添加
            $insert_rows = 0;
            if(!empty($batch_insert_new))
            {
                $insert_rows = $this->_ci->m_fba_new->madd($batch_insert_new);
                if (!$insert_rows)
                {
                    $report['errorMess'] = '批量导入,插入失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '批量导入，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $update_rows+$insert_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];
            $report['line_num'] = json_encode($line_num);

            //释放资源
            $batch_update_new = $batch_insert_new = null;
            unset($batch_update_new, $batch_insert_new);

            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('新品导入批量添加%s，提交事务出现异常: %s', json_encode($batch_insert_new), $e->getMessage()));
            log_message('ERROR', sprintf('新品导入批量更新%s，提交事务出现异常: %s', json_encode($batch_update_new), $e->getMessage()));
            $batch_insert_new = $batch_update_new = null;
            unset($batch_insert_new, $batch_update_new);

            $report['errorMess'] = '批量审核抛出异常：'.$e->getMessage();
            return $report;
        }
    }

    public function import_batch($params = [], $user_id = '', $user_name = ''){
        $num = count($params);
        $report = [
            'total'      => $num,
            'processed'  => 0,
            'undisposed' => $num,
            'errorMess'  => ''
        ];
        $this->_ci->load->model('Fba_new_list_model', 'm_fba_new', false, 'fba');
        //1.验证是否新品
        $this->_ci->load->model('Fba_old_model', 'm_fba_old', false, 'fba');
        $this->_ci->load->model('Fba_amazon_account_model', 'm_amazon_account', false, 'fba');

        $accounts = $this->_ci->m_amazon_account->all();
        $accounts = array_column($accounts,'id','account_name');

        $list = RPC_CALL('YB_J1_004', ['pageSize'=>10000,'pageNumber'=>1]);
        $users = array_column($list['data']['records'],'userNumber','userName');

        $this->_ci->load->service('basic/DropdownService');
        $groups = $this->_ci->dropdownservice->dropdown_fba_sales_group();
        $groups = array_flip($groups);

        //第一次筛选数据
        $batch_insert_row_num = $batch_update_row_num = [];
        $old_ids = $line_num = $batch_insert_row_num = $batch_insert_ids = $params_pre = $line_num_repeat= [];
        foreach ($params as $key => $value) {
            $value['account_id'] = $accounts[trim($value['account_name'])]??0;
            $old_id = $this->_ci->m_fba_old->get_id($value);
            if(!in_array($old_id,$old_ids)){
                $old_ids[] = $old_id ;
                $params_pre[$old_id] = $value;
            }else{
                array_push($line_num_repeat,$value['line_num']);
            }

        }

        $old_exist_data = $this->_ci->m_fba_old->exists_ids($old_ids);
        $old_exist_ids = array_column($old_exist_data,'seller_sku','id');
        //无需处理的行号
        array_push($line_num,['line_num'=>array_column(array_values(array_intersect_key($params_pre,$old_exist_ids)),'line_num'),'reason'=>'mrp_py数据库中的老表old_goods已存在这几行数据']);
        //需要处理的数据
        $new_params = array_diff_key($params_pre,$old_exist_ids);



        //第二次筛选数据
        $now = date('Y-m-d H:i:s');
        $ids = $new_params_pre = [];
        foreach ($new_params as $key => $value) {
            $id = $this->_ci->m_fba_new->get_id($value);
            if(!in_array($id,$ids)){
                $ids[] = $id;
                $new_params_pre[$id] = $value;
                array_push($batch_insert_ids,$id);
                array_push($batch_insert_row_num,$value['line_num']);
            }else{
                array_push($line_num_repeat,$value['line_num']);
            }
        }

        if(!empty($line_num_repeat)){
            array_push($line_num,['line_num'=>$line_num_repeat,'reason'=>'新增时,EXCEL表中account_id,site,seller_sku,erpsku,fnsku,asi; 有一条以上的数据这几字段的值相同']);
            $line_num_repeat = [];
        }

        $result_data = $this->_ci->m_fba_new->get_info_by_ids($ids);
        //新增的数据
        $batch_insert_new = [];
        foreach(array_diff_key($new_params_pre,$result_data) as  $key => $value){
            $batch_insert_new[] = [
                'id'=> $key,
                'sale_group' => $groups[trim($value['sale_group'])]??0,//销售分组id
                'salesman' =>  isset($users[$value['staff_zh_name']])?$users[$value['staff_zh_name']]:0,
                'staff_zh_name'   => trim($value['staff_zh_name']),
                'site' => strtolower($value['site']),
                'seller_sku'   => trim($value['seller_sku']),
                'erp_sku'  => trim($value['erpsku']),
                'fnsku'       => trim($value['fnsku']),
                'asin'        => trim($value['asin']),
                'demand_num' => trim($value['demand_num']),//需求数量
                'created_at' => $now,//更新时间
                'created_uid' => $user_id,//更新uid
                'created_zh_name' => $user_name,//更新uid
                'approve_state' => NEW_APPROVAL_INIT,//审核状态
                'sale_group_name' => trim($value['sale_group']),//销售分组id
                'account_name' => trim($value['account_name']),
                'account_id' => $value['account_id']
            ];
        }

        //更新的数据
        $update_condition = array_intersect_key($result_data,$new_params_pre);
        $line_num_update = $batch_update_new=[];
        foreach($update_condition as $id =>$condition){
            //if($condition['is_delete'] == 0 && $condition['demand_num'] != $new_params_pre[$id]['demand_num'] && !$condition['approve_state']){
            if($condition['is_delete'] == 0 && !$condition['approve_state'] && isset($new_params_pre[$id])){
                $batch_update_new[] = [
                    'id'=> $id,
                    'demand_num' => $new_params_pre[$id]['demand_num'],//需求数量
                    'updated_at' => date('Y-m-d H:i:s'),//更新时间
                    'updated_uid' => $user_id,//更新uid
                    'updated_zh_name' => $user_name,//更新uid
                    'approve_state' => 0,//审核状态
                ];
                array_push($batch_update_row_num, $new_params_pre[$id]['line_num']);
            }else{
                array_push($line_num_update,$new_params_pre[$id]['line_num']);
            }
        }

        if(!empty($line_num_update)){
            array_push($line_num,['line_num'=>$line_num_update,'reason'=>'该条数仅为更新，但是不满足yibai_fba_new_cfg表数据更新条件:1）已审核的记录不能更新 2）已被软删除的不能更新)']);
            $line_num_update = [];
        }



        //保存和更新数据
        $db = $this->_ci->m_fba_new->getDatabase();
        try
        {
            $db->trans_start();

            //批量更新
            $update_rows = 0;
            if(!empty($batch_update_new))
            {
                $update_rows = $db->update_batch($this->_ci->m_fba_new->getTable(), $batch_update_new, 'id');
                if (!$update_rows)
                {
                    $report['errorMess'] = '批量导入,更新失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            //批量添加
            $insert_rows = 0;
            if(!empty($batch_insert_new))
            {
                $insert_rows = $this->_ci->m_fba_new->madd($batch_insert_new);
                if (!$insert_rows)
                {
                    $report['errorMess'] = '批量导入,插入失败';
                    throw new \RuntimeException($report['errorMess']);
                }
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                $report['errorMess'] = '批量导入，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $update_rows+$insert_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];

            $summary_line_num =[] ;
            foreach ($line_num as $info){
                array_push($summary_line_num,implode(',',$info['line_num']));
            }
            $report['line_num_info'] = json_encode($line_num);
            $report['line_num'] = json_encode(explode(',',implode(',',$summary_line_num)));
            //释放资源
            $batch_update_new = $batch_insert_new = null;
            unset($batch_update_new, $batch_insert_new);
            return $report;
        }
        catch (\Throwable $e)
        {
            $db->trans_rollback();

            log_message('ERROR', sprintf('新品导入批量添加%s，提交事务出现异常: %s', json_encode($batch_insert_new), $e->getMessage()));
            log_message('ERROR', sprintf('新品导入批量更新%s，提交事务出现异常: %s', json_encode($batch_update_new), $e->getMessage()));
            $batch_insert_new = $batch_update_new = null;
            unset($batch_insert_new, $batch_update_new);

            $report['errorMess'] = '批量审核抛出异常：'.$e->getMessage();
            return $report;
        }
    }



}
