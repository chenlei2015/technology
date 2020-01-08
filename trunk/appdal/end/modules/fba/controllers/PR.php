<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FBA需求列表
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class PR extends MY_Controller {

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }

    /**
     * 列表
     */
    public function list()
    {
        try
        {
            $params = $this->compatible('get');

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
            {
                //加上自己作为销售员的条件
                $params['set_data_scope'] = 1;

                //这个账号是否是子账号的管理员
                $account_name = $active_user->get_my_manager_accounts();
                if (!empty($account_name))
                {
                    $params['prev_account_name'] = implode(',', $account_name);
                    $params['prev_salesman'] = $active_user->staff_code;
                }
                else
                {
                    //只能查自己的 但选择了其他人的记录，一定没有数据返回
                    $params['prev_salesman'] = $active_user->staff_code;
                }
            }

            $this->load->service('fba/PrListService');

            $this->prlistservice->setSearchParams($params);
            //过滤hook
            $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_filter_params'), ['input' => $this->prlistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_translate_params'), ['input' => &$this->prlistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_format_params'), ['input' => &$this->prlistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->prlistservice->setAfterSearchHook(array($this->prlistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->prlistservice->execSearch();

            $cfg = $this->prlistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->prlistservice->get_cfg()['droplist'],
                    $is_override = true
                    );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

            //取配置
            $this->load->service('basic/UsercfgProfileService');

            $result = $this->usercfgprofileservice->get_display_cfg('fba_pr_list');
            $this->data['selected_data_list'] = $result['config'];
            $this->data['profile'] = $result['field'];

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
            //pr('de');exit;
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 获取高亮显示记录，可能包括促销sku和seller_sku
     * @todo 目前接口尚未完全完成。
     *
     * @throws \InvalidArgumentException
     */
    public function list_highlight()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('fba/PrService');
            $this->data['data'] = $this->prservice->get_list_highlight($params);
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
     * 扩展物流属性展现
     */
    public function extend_logistics_info()
    {
        try
        {
            $params = $this->compatible('get');
            $gid_arr = $params['gid'] ?? [];
            $this->load->service('fba/PrService');
            $this->data['data'] = $this->prservice->get_extend_logistics_info($gid_arr);
            $this->data['status'] = empty($this->data['data']) ? 0 : 1;
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
     * 全部数据权限， 记录所有者， 记录账号管理者能够添加备注
     *
     * @author Jason 13292
     * @date 2019-03-04
     * @desc fba prlist添加备注
     * @link
     */
    public function remark()
    {
        try
        {
            $params = $this->compatible('post');
            $active_user = get_active_user();
            $priv_uid = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? -1 : $active_user->staff_code;
            $this->load->service('fba/PrService');
            $count = $this->prservice->update_remark($params, $priv_uid);
            $message = [
                    '1' => '更新成功',
                    '-1' => '数据没有变化，不需要进行更新',
                    '0' => '更新失败'
            ];
            $this->data['data'] = $count;
            $this->data['errorMess'] = $message[$this->data['data']];
            if ($this->data['data'] == 1)
            {
                $this->data['status'] = 1;
                $code = 200;
            }
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
            //$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 调整BD数量
     */
    public function edit_pr_listing()
    {
        try
        {
            $params = $this->compatible('post');
            $active_user = get_active_user();
            $this->load->service('fba/PrService');
            $this->data['data'] = $this->prservice->edit_pr_listing($params, $active_user->staff_code);
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
     * 批量调整BD数量
     */
    public function batch_edit_bd()
    {
        try
        {
            $params = $this->compatible('post');
            $requir_cols = array_flip(['primary_key', 'map', 'selected']);
            if (count(array_diff_key($requir_cols, $params)) > 0 )
            {
                throw new \InvalidArgumentException('无效的参数', 412);
            }
            $this->load->service('fba/PrService');
            $this->data['data'] = $this->prservice->batch_edit_pr($params);
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
     * csv批量调整一次修订数量
     */
    public function batch_edit_fixed_amount()
    {
        try
        {
            $params = $this->compatible('post');

            $requir_cols = array_flip(['primary_key', 'map', 'selected']);
            if (count(array_diff_key($requir_cols, $params)) > 0 )
            {
                throw new \InvalidArgumentException('无效的参数', 412);
            }
            $this->load->service('fba/PrService');
            $this->data['data'] = $this->prservice->batch_edit_fixed_amount($params);
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
     * 批量一级审核
     *
     */
    public function batch_approve_first()
    {
        try
        {
            //审核权限
            $active_user = get_active_user();
            if (!$active_user->is_first_approver(BUSSINESS_FBA))
            {
                throw new \RuntimeException(sprintf('您必须设置一级审核权限'), 500);
            }
            //数据权限
            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
            {
                //gid数据批量获取,带选择条件，不符合条件的过滤
                $manager_accounts = $active_user->get_my_manager_accounts();
                if (empty($manager_accounts))
                {
                    throw new \InvalidArgumentException('请设置需要管辖的账号', 412);
                }
            }
            else
            {
                //全部权限
                $manager_accounts = '*';
            }
            $params = $this->input->post();
            $gid = $params['gid'] ?? [];
            $result = $params['result'];
            $this->load->service('fba/PrService');
            $this->prservice->check_enable_time(__FUNCTION__);
            $this->data['data'] = $this->prservice->batch_approve_first($gid, $result, $manager_accounts);
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
     * 查询估算金额进度
     */
    public function get_fba_total_money()
    {
        try {
            $active_user = get_active_user();
            if (!($active_user->has_all_data_privileges(BUSSINESS_FBA))) {
                throw new \InvalidArgumentException('必须具有全部数据权限',412);
                }
            //查询version
            $this->load->model('Fba_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'fba');
            $version = $this->m_rebuild_mvcc->get_last_version(BUSINESS_LINE_FBA, $today = true);

            if (array_key_exists('moq_purchase_money', $version)) {
                if (is_null($version['moq_purchase_money']) || $version['moq_purchase_money'] == '') {
                    $this->data['data'] = '计算中...';
                } else {
                    $this->data['data'] = sprintf('%0.2f万元', $version['moq_purchase_money']) ?? '0.00';
                }
            } else {
                $this->data['data'] = '0.00万元';
            }
            $code == 200;
            $this->data['status'] = 1;
        } catch (\Throwable $e) {
            $code = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /*
     * 一键审核调用
     */
    public function batch_approve_all()
    {
        try
        {
            //审核权限
            $active_user = get_active_user();
            if (is_cli() && func_num_args() > 0) {
                list($session_uid, $result, $level, $query_value) = func_get_args();
                if (!$session_uid || !$result || !$level) {
                    throw new \InvalidArgumentException('cli请求丢失session_uid,result,level参数');
                }

                $this->load->library('Rediss');
                $user_data = $this->rediss->getData($session_uid);
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
                $level = $params['level'];
                $query_value = $params['query'];
            }

            //设置权限
            $privilege = '';
            switch ($level) {
                case 1:
                    if (!$active_user->is_first_approver(BUSSINESS_FBA))
                    {
                        throw new \RuntimeException(sprintf('您必须设置一级审核权限'), 500);
                    }
                    //数据权限
                    if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
                    {
                        //gid数据批量获取,带选择条件，不符合条件的过滤
                        $privilege = $active_user->get_my_manager_accounts();
                        if (empty($privilege))
                        {
                            throw new \InvalidArgumentException('请设置需要管辖的账号', 412);
                        }
                    }
                    else
                    {
                        //全部权限
                                $privilege = '*';
                    }
                    break;
                case 2:
                    //审核权限
                    $active_user = get_active_user();
                    if (!$active_user->is_second_approver(BUSSINESS_FBA))
                    {
                        throw new \RuntimeException(sprintf('您必须设置二级审核权限'), 500);
                    }
                    //数据权限
                    $privilege = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? -1 : $active_user->staff_code;
                    break;
                case 3:
                    if (!$active_user->is_three_approver(BUSSINESS_FBA))
                    {
                        throw new \RuntimeException(sprintf('您必须设置三级审核权限'), 500);
                    }
                    //数据权限
                    $privilege = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? -1 : $active_user->staff_code;
                    break;
            }

            $this->load->service('fba/PrService');
            $this->prservice->check_enable_time(__FUNCTION__);
            $this->data['data'] = $this->prservice->batch_approve_all($level, $result, $privilege, $query_value);
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

    public function approve_process()
    {
        $data = [
            'data' => -1,
            'status' => 0
        ];
        $get = $this->input->get();
        $level = $get['level'] ?? '';
        $query = $get['query'] ?? '';
        $result = $get['result'] ?? -1;

        if (strlen($query) != 32) {
            $this->data['errorMess'] = '必须设置查询秘钥';
            return http_response($this->data);
        }
        if (!in_array($get['level'], [1, 2, 3])) {
            $this->data['errorMess'] = '必须设置审核等级';
            return http_response($this->data);
        }
        if (!in_array($get['result'], [1, 2])) {
            $this->data['errorMess'] = '必须设置审核结果';
            return http_response($this->data);
        }
        $this->load->service('fba/PrService');
        $data['data'] = $this->prservice->get_approve_process_summary($level, $result, $query);
        $data['status'] = 1;
        http_response($data);
    }

    /**
     * 批量二级审核
     */
    public function batch_approve_second()
    {
        try
        {
            //审核权限
            $active_user = get_active_user();
            if (!$active_user->is_second_approver(BUSSINESS_FBA))
            {
                throw new \RuntimeException(sprintf('您必须设置二级审核权限'), 500);
            }
            //数据权限
            $salesman_uid = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? -1 : $active_user->staff_code;

            $params = $this->input->post();
            $gid = $params['gid'] ?? [];
            $result = $params['result'];
            $this->load->service('fba/PrService');
            $this->prservice->check_enable_time(__FUNCTION__);
            $this->data['data'] = $this->prservice->batch_approve_second($gid, $result, $salesman_uid);
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
     * 批量三级审核
     */
    public function batch_approve_three()
    {
        try
        {
            //审核权限
            $active_user = get_active_user();
            if (!$active_user->is_three_approver(BUSSINESS_FBA))
            {
                throw new \RuntimeException(sprintf('您必须设置三级审核权限'), 500);
            }
            //数据权限
            $salesman_uid = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? -1 : $active_user->staff_code;
            $params = $this->input->post();
            $gid = $params['gid'] ?? [];
            $result = $params['result'];
            $this->load->service('fba/PrService');
            $this->prservice->check_enable_time(__FUNCTION__);
            $this->data['data'] = $this->prservice->batch_approve_three($gid, $result, $salesman_uid);
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
     * 获取详情接口
     *
     */
    public function detail()
    {
        try
        {
            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('fba/PrService');
            $this->data['data']['pr'] = $this->prservice->detail($gid);
            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA) && $this->data['data']['pr']['salesman'] != $active_user->staff_code)
            {
                //这条记录的账号是否是我管辖的
                $account_name = $active_user->get_my_manager_accounts();
                if (empty($account_name) || !in_array($this->data['data']['pr']['account_name'], $account_name))
                {
                    $this->data['data'] = [];
                    throw new \Exception('没有权限', 412);
                }
            }
            $this->data['data']['remark'] = $this->prservice->get_pr_remark($gid);
            $this->load->service('fba/PrLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->prlogservice->get_one_listing_log($gid, $offset, $limit);

            $this->load->service('basic/DropdownService');
            $this->dropdownservice->setDroplist(['eu_country']);
            $this->data['data']['eu_country'] = $this->dropdownservice->get()['eu_country'];
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
     * 获取账号管理列表
     */
    public function manager_list()
    {
        try
        {
            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }

            $params = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $this->load->service('fba/FbaManagerAccountListService', null, 'account_list');

            $this->account_list->setSearchParams($params);
            //过滤hook
            $this->account_list->setPreSearchHook(array($this->account_list, 'hook_filter_params'), ['input' => $this->account_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->account_list->setPreSearchHook(array($this->account_list, 'hook_translate_params'), ['input' => &$this->account_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->account_list->setPreSearchHook(array($this->account_list, 'hook_format_params'), ['input' => &$this->account_list->search_params, 'update' => 'search_params']);
            $this->data = $this->account_list->execSearch();

            $cfg = $this->account_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            if (isset($params['get_alone_account_nums']))
            {
                 $this->load->service('fba/FbaManagerAccountService', null, 'manager_count');
                 $this->data['data_list']['alone_nums'] =  $this->manager_count->get_alone_account_nums();
            }

            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(['fba_sales_group']);
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

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
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }
    }

    /**
     * 获取未配置管理员的账号
     */
    public function get_alone_account_nums()
    {
        $active_user = get_active_user();
        if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
        {
            http_response(['errorMess' => '您必须设置全部数据权限']);
            return;
        }

        $this->load->service('fba/FbaManagerAccountService', null, 'manager_account');
        $this->data['alone_nums'] =  $this->manager_account->get_alone_account_nums();
        $this->data['status'] = 1;
        http_response($this->data);
    }

    /**
     * 获取账号管理列表
     */
    public function set_account_manager()
    {
        try
        {
            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }

            $params = $this->compatible('post');
            $this->load->service('fba/FbaManagerAccountService', null, 'manager_account');
            $this->data['data'] = $this->manager_account->batch_set_manager($params);
            if ($this->data['data'] > 0)
            {
                $this->data['status'] = 1;
                $code = 200;
            }
            else
            {
                $code = 500;
                $errorMsg = '操作没有更新有效的记录';
            }
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
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 跟踪列表
     */
    public function track_list()
    {
        try
        {
            $params = $this->compatible('get');

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
            {
                //加上自己作为销售员的条件
                $params['set_data_scope'] = 1;

                //这个账号是否是子账号的管理员
                $account_name = $active_user->get_my_manager_accounts();
                if (!empty($account_name))
                {
                    $params['prev_account_name'] = implode(',', $account_name);
                    $params['prev_salesman'] = $active_user->staff_code;
                }
                else
                {
                    //只能查自己的 但选择了其他人的记录，一定没有数据返回
                    $params['prev_salesman'] = $active_user->staff_code;
                }
            }

            $this->load->service('fba/PrTrackListService', null, 'track_list');

            $this->track_list->setSearchParams($params);
            //过滤hook
            $this->track_list->setPreSearchHook(array($this->track_list, 'hook_filter_params'), ['input' => $this->track_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->track_list->setPreSearchHook(array($this->track_list, 'hook_translate_params'), ['input' => &$this->track_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->track_list->setPreSearchHook(array($this->track_list, 'hook_format_params'), ['input' => &$this->track_list->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->track_list->setAfterSearchHook(array($this->track_list, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->track_list->execSearch();

            $cfg = $this->track_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->track_list->get_cfg()['droplist'],
                    $is_override = true
                    );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

            //取配置
            $this->load->service('basic/UsercfgProfileService');
            $result = $this->usercfgprofileservice->get_display_cfg('fba_track_list');
            $this->data['selected_data_list'] = $result['config'];
            $this->data['profile'] = $result['field'];

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
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * @author Jason 13292
     * @date 2019-03-04
     * @desc fba track list添加备注
     * @link
     */
    public function track_remark()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('fba/PrTrackService');
            $active_user = get_active_user();
            $priv_uid = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? -1 : $active_user->staff_code;
            $count = $this->prtrackservice->update_remark($params, $priv_uid);
            $message = [
                    '1' => '更新成功',
                    '-1' => '数据没有变化，不需要进行更新',
                    '0' => '更新失败'
            ];
            $this->data['data'] = $count;
            $this->data['errorMess'] = $message[$this->data['data']];
            if ($this->data['data'] == 1)
            {
                $this->data['status'] = 1;
                $code = 200;
            }
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
            //$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 获取详情接口
     *
     */
    public function track_detail()
    {
        try
        {
            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('fba/PrTrackService');
            $this->data['data']['pr'] = $this->prtrackservice->detail($gid);
            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA) && $this->data['data']['pr']['salesman'] != $active_user->staff_code)
            {
                //这条记录的账号是否是我管辖的
                $account_name = $active_user->get_my_manager_accounts();
                if (empty($account_name) || !in_array($this->data['data']['pr']['account_name'], $account_name))
                {
                    http_response(['errorMess' => '没有权限']);
                }
            }

            $this->data['data']['remark'] = $this->prtrackservice->get_track_remark($gid);
            $this->load->service('fba/PrTrackLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->prtracklogservice->get_track_log($gid, $offset, $limit);

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
     * 跟踪列表
     */
    public function summary_list()
    {
        try
        {
            $params = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            $this->load->service('fba/PrSummaryListService', null, 'summary_list');

            $this->summary_list->setSearchParams($params);
            //过滤hook
            $this->summary_list->setPreSearchHook(array($this->summary_list, 'hook_filter_params'), ['input' => $this->summary_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->summary_list->setPreSearchHook(array($this->summary_list, 'hook_translate_params'), ['input' => &$this->summary_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->summary_list->setPreSearchHook(array($this->summary_list, 'hook_format_params'), ['input' => &$this->summary_list->search_params, 'update' => 'search_params']);
            $this->data = $this->summary_list->execSearch();

            $cfg = $this->summary_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取配置
            //$this->load->service('basic/UsercfgProfileService');
            //$this->data['selected_data_list'] = $this->usercfgprofileservice->get_display_cfg($cfg['user_profile']);

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->summary_list->get_cfg()['droplist'],
                    $is_override = true
                );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }
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
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 促销sku的列表
     */
    public function promotion_sku_list()
    {
        try
        {
            $params = $this->compatible('get');

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $active_user = get_active_user();

            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
            {
                //加上自己作为销售员的条件
                $params['set_data_scope'] = 1;

                //这个账号是否是子账号的管理员
                $account_name = $active_user->get_my_manager_accounts();
                if (!empty($account_name))
                {
                    $params['prev_account_name'] = implode(',', $account_name);
                    $params['prev_salesman'] = $active_user->staff_code;
                }
                else
                {
                    //只能查自己的 但选择了其他人的记录，一定没有数据返回
                    $params['prev_salesman'] = $active_user->staff_code;
                }
            }

            $this->load->service('fba/PrPromotionListService', null, 'prlistservice');

            $this->prlistservice->setSearchParams($params);
            //过滤hook
            $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_filter_params'), ['input' => $this->prlistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_translate_params'), ['input' => &$this->prlistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_format_params'), ['input' => &$this->prlistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->prlistservice->setAfterSearchHook(array($this->prlistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->prlistservice->execSearch();

            $cfg = $this->prlistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $cfg['droplist']
                );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

            //取配置
            $this->load->service('basic/UsercfgProfileService');

            $result = $this->usercfgprofileservice->get_display_cfg($cfg['user_profile']);
            $this->data['selected_data_list'] = $result['config'];
            $this->data['profile'] = $result['field'];

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
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * @author Jason 13292
     * @date 2019-03-07
     * @desc fba summary list添加备注
     * @link
     */
    public function promotion_remark()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('fba/PrPromotionService');
            $count = $this->prpromotionservice->update_remark($params);
            $message = [
                    '1' => '更新成功',
                    '-1' => '数据没有变化，不需要进行更新',
                    '0' => '更新失败'
            ];
            $this->data['data'] = $count;
            $this->data['errorMess'] = $message[$this->data['data']];
            if ($this->data['data'] == 1)
            {
                $this->data['status'] = 1;
                $code = 200;
            }
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
            //$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 订单导出， 预期支持不同字段的导出
     *
     */
    public function promotion_sku_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaPromotionExportService');
            $this->fbapromotionexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbapromotionexportservice->export('csv');
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
     * 促销sku删除
     */
    public function promotion_batch_delete()
    {
        try
        {
            $params = $this->compatible('post');
            $gid_arr = $params['gid'] ?? (is_array($params['gid']) ? $params['gid'] : explode(',', $params['gid']));
            $this->load->service('fba/PrPromotionService');
            $count = $this->prpromotionservice->batch_delete($gid_arr);
            $message = [
                    '1' => '更新成功',
                    '-1' => '数据没有变化，不需要进行更新',
                    '0' => '更新失败'
            ];
            $this->data['data'] = $count;
            $this->data['errorMess'] = $message[$this->data['data']];
            if ($this->data['data'] == 1)
            {
                $this->data['status'] = 1;
                $code = 200;
            }

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
    public function promotion_sku_import()
    {
        try
        {
            $params = $this->compatible('post');
            $params = array(
                'primary_key' => 'sku',
                'map' => array(
                    'sku' => '0','remark' => '1'
                ),
               'selected' => '{"JM04800-02":{"1":"wish\\u4e13\\u5356-\\u7845\\u80f6\\u53cc\\u9762\\u624b\\u52a8\\u6d17\\u8138\\u5237\\u6d01\\u9762\\u5237(\\u767d\\u8272)"},"JM02387":{"1":"\\u8eab\\u4f53\\u6309\\u6469\\u5668\\u624b\\u52a8\\u5851\\u6599\\u6309\\u6469\\u5668\\u5065\\u8eab\\u4fdd\\u5065"},"JM00075":{"1":"\\u9ad8\\u6863\\u776b\\u6bdb\\u5939 \\u5377\\u7fd8\\u776b\\u6bdb\\u5939 \\u5973\\u58eb\\u7528\\u54c1 \\u4e0d\\u9508\\u94a2\\u776b\\u6bdb\\u5939\\u8f85\\u52a9\\u5668"},"JM02847-02":{"1":"\\u8131\\u6bdb\\u5957\\u88c5\\u8131\\u6bdb\\u8721\\u7597\\u673a+100G\\u8721\\u8c46+5\\u7247\\u8c03\\u8721\\u68d2(\\u9752\\u82f9\\u679c\\u5473\\u7f8e\\u89c4110V) \\u4e0d\\u8981\\u4f7f\\u7528\\u5546\\u6807Apple"},"12ZH-CCJ01":{"1":"FBA\\u4e13\\u5356\\u9ad8\\u7ea7\\u91d1\\u73ab\\u7470\\u7eff\\u53f6 \\u7ea2\\u8272 \\uff08\\u5e26\\u5e95\\u5ea7\\uff09"},"JM03767-06":{"1":"\\u65cb\\u8f6c\\u516b\\u5366\\u8db3\\u5e95\\u4fdd\\u5065\\u6309\\u6469\\u978b \\u78c1\\u7597\\u6309\\u6469\\u62d6\\u978b \\u82f1\\u6587\\u7248(40-41\\u65cb\\u8f6c\\u5706\\u70b9)"},"JM02847-08":{"1":"\\u8131\\u6bdb\\u5957\\u88c5\\u8131\\u6bdb\\u8721\\u7597\\u673a+100G\\u8721\\u8c46+5\\u7247\\u8c03\\u8721\\u68d2(\\u5de7\\u514b\\u529b\\u5473\\u7f8e\\u89c4110V)"},"JM00609":{"1":"--"},"JM05093-03":{"1":"\\u53ef\\u6d17\\u6709\\u673a\\u68c9\\u5e03\\u536b\\u751f\\u5dfe \\u73af\\u4fdd\\u65e5\\u591c\\u7528\\u9632\\u4fa7\\u6f0f\\u4ea7\\u540e\\u536b\\u751f\\u5dfe JM04718\\u7c7b\\u4f3c\\u6b3e \\u9500\\u552e\\u4e0eJM04723\\u6346\\u7ed1\\u505a\\u9ad8\\u4f4e\\u4ef7\\u5356(#3)"},"JM05093-02":{"1":"--"},"FS00331-05":{"1":"\\u8d85\\u8584\\u6258\\u80f8\\u6536\\u8179\\u6536\\u8170\\u5851\\u8eab\\u8863\\u65e0\\u75d5\\u5973\\u5851\\u8eab\\u80cc\\u5fc3\\u80a4\\u8272(3XL)"},"JM03205":{"1":"--"},"JM03078-01":{"1":"--"},"JM01984-10":{"1":"--"},"JM01984-09":{"1":"--"},"JM01984-02":{"1":"--"},"JM01984-01":{"1":"--"},"TJOT58700LL":{"1":"FBA\\u4e13\\u5356\\u5927\\u811a\\u9aa8\\u4fdd\\u62a4\\u5957\\u62c7\\u5916\\u7ffb\\u77eb\\u6b63\\u5668\\uff08\\u4e00\\u53cc\\u5356\\uff09(\\u5927\\u780140-47)"},"JMOT41000":{"1":"\\u811a\\u8dbe\\u77eb\\u6b63--\\u5de6\\u53f3\\u77eb\\u6b63\\u5668\\uff08\\u4e00\\u5bf9\\u88c5\\uff09"},"JM00727-03":{"1":"6\\u6b3e\\u53ef\\u9009\\u773c\\u7f69+\\u51b0\\u888b1106\\u53ef\\u7231\\u5361\\u901a\\u7761\\u7720\\u906e\\u5149\\u773c\\u7f69\\u51b7\\u70ed\\u6577\\u51b0\\u773c\\u7f69 (\\u6d45\\u7eff\\u8717\\u725b)"}}'
            );
            $requir_cols = array_flip(['primary_key', 'map', 'selected']);
            if (count(array_diff_key($requir_cols, $params)) > 0 )
            {
                throw new \InvalidArgumentException('无效的参数', 412);
            }
            $this->load->service('fba/PrPromotionService');
            $this->data['data'] = $this->prpromotionservice->import($params);
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
     * @author Jason 13292
     * @date 2019-03-07
     * @desc fba summary list添加备注
     * @link
     */
    public function summary_remark()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('fba/PrSummaryService');
            $count = $this->prsummaryservice->update_remark($params);
            $message = [
                    '1' => '更新成功',
                    '-1' => '数据没有变化，不需要进行更新',
                    '0' => '更新失败'
            ];
            $this->data['data'] = $count;
            $this->data['errorMess'] = $message[$this->data['data']];
            if ($this->data['data'] == 1)
            {
                $this->data['status'] = 1;
                $code = 200;
            }
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
            //$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 获取详情接口
     *
     */
    public function summary_detail()
    {
        try
        {
            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('fba/PrSummaryService');
            $this->data['data']['pr'] = $this->prsummaryservice->detail($gid);
            $this->data['data']['remark'] = $this->prsummaryservice->get_summary_remark($gid);
            $this->load->service('fba/PrSummaryLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->prsummarylogservice->get_summary_log($gid, $offset, $limit);

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
     * 审核汇总
     */
    public function execute_summary()
    {
        try
        {
            $this->load->service('fba/PrSummaryService');
            $this->data['data'] = $this->prsummaryservice->summary();
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
            //$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 订单导出， 预期支持不同字段的导出
     *
     */
    public function fba_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaExportService');
            $this->fbaexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbaexportservice->export('csv');
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

    public function fba_track_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaTrackExportService');
            $this->fbatrackexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbatrackexportservice->export('csv');
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

    public function fba_summary_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaSummaryExportService');
            $this->fbasummaryexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbasummaryexportservice->export('csv');
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
     * 导出fba活动需求配置列表
     */
    public function fba_activity_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaExportService');
            $this->fbaexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbaexportservice->activity_export('csv');
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

    public function debug_rebuild_pr()
    {
        try
        {
            $pr_sns = $this->input->get('pr_sns');
            $version = $this->input->get('version');
            if (empty($pr_sns) || empty($version)) {
                throw new \InvalidArgumentException('请上传pr_sn, $version');
            }
            $this->load->service('fba/PrService');
            $this->data['data'] = $this->prservice->debug_rebuild_pr($version, $pr_sns);
            $this->data['status'] = 1;
            $code = 200;
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

    public function rebuild_pr()
    {
        try
        {
            $active_user = get_active_user();
            if ($active_user->uid == 0 && func_num_args() > 0) {
                $session_uid = func_get_args()[0];
                $this->load->library('Rediss');
                $user_data = $this->rediss->getData($session_uid);
                if (!empty($user_data)) {
                    $this->load->service('UserService');
                    $this->userservice::login($user_data);
                    $active_user = get_active_user(true);
                } else {
                    throw new \InvalidArgumentException('获取用户认证信息失败，该用户未登陆或者已经失效，请重新登陆');
                }
            }
            if (!($active_user->has_all_data_privileges(BUSSINESS_FBA) && $active_user->is_three_approver(BUSSINESS_FBA))) {
                throw new \InvalidArgumentException('必须具有全部数据权限和三级审核权限',412);
            }
            $this->load->service('fba/PrService');
            $this->data['data'] = $this->prservice->rebuild_pr();
            $this->data['status'] = 1;
            $code = 200;
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

    public function asyc_rebuild_pr()
    {
        $active_user = get_active_user();
        if (!($active_user->has_all_data_privileges(BUSSINESS_FBA) && $active_user->is_three_approver(BUSSINESS_FBA))) {
            $this->data['errorMess'] = '必须具有全部数据权限和三级审核权限';
            return http_response($this->data);
        }

        if (!function_exists('shell_exec')) {
            $this->data['errorMess'] = '请在php.ini或者php-fpm中开启appdal的shell_exec的函数';
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->data['errorMess'] = '该操作不能再Window操作系统下执行';
        }
        if (isset($this->data['errorMess'])) {
            return http_response($this->data);
        }
        $session_uid = get_active_user()->uid;
        $path_entry = FCPATH.'index.php';
        $cmd = sprintf('/usr/bin/php %s fba PR rebuild_pr %s > /dev/null 2>&1 &', $path_entry, $session_uid);
        shell_exec($cmd);
        log_message('INFO', '执行异步重建需求列表，命令：'.$cmd);
        $this->load->model('Fba_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'fba');
        $version_row = $this->m_rebuild_mvcc->get_last_version(BUSINESS_LINE_FBA);
        if (empty($version_row) || $version_row['state'] == REBUILD_CFG_FINISHED || date('Y-m-d', strtotime($version_row['created_at'])) != date('Y-m-d') )
        {
            $new_version = ($version_row['version'] ?? 0) + 1;
        } else {
            $new_version = $version_row['version'];
        }
        $this->data = ['status' => 1, 'data' => intval($new_version)];
        http_response($this->data);
    }

    /**
     * 执行估算采购金额
     *
     * @throws \InvalidArgumentException
     */
    public function debug_asyc_estimate_rebuild_money()
    {
        try
        {
            $version = $this->input->get()['version'] ?? 0;
            $sku = $this->input->get()['sku'] ?? '';
            if ($version == 0) {
                throw new \InvalidArgumentException('请输入版本号');
            }
            if ($sku == '') {
                throw new \InvalidArgumentException('请输入sku');
            }
            $this->load->classes('fba/classes/DebugEstimateMoqPurchaseMoney');
            $this->data['data'] = $this->DebugEstimateMoqPurchaseMoney->estimate($version, $sku);
            $this->data['status'] = 1;
            $code = 200;
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
     * 执行估算采购金额
     *
     * @throws \InvalidArgumentException
     */
    public function asyc_estimate_rebuild_money()
    {
        try
        {
            $active_user = get_active_user();
            if ($active_user->uid == 0 && func_num_args() > 0) {
                $session_uid = func_get_args()[0];
                $this->load->library('Rediss');
                $user_data = $this->rediss->getData($session_uid);
                if (!empty($user_data)) {
                    $this->load->service('UserService');
                    $this->userservice::login($user_data);
                    $active_user = get_active_user(true);
                } else {
                    throw new \InvalidArgumentException('获取用户认证信息失败，该用户未登陆或者已经失效，请重新登陆');
                }
                $version = func_get_args()[1];
            } else {
                $version = $this->input->get()['version'];
            }
            if (!($active_user->has_all_data_privileges(BUSSINESS_FBA))) {
                throw new \InvalidArgumentException('必须具有全部数据权限',412);
            }

            $this->load->classes('fba/classes/EstimateMoqPurchaseMoney');
            $this->data['data'] = $this->EstimateMoqPurchaseMoney->estimate($version);
            $this->data['status'] = 1;
            $code = 200;
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

    public function asyc_approve()
    {
        //审核权限
        $active_user = get_active_user();
        if (!($active_user->has_all_data_privileges(BUSSINESS_FBA) && $active_user->is_first_approver(BUSSINESS_FBA))) {
            $this->data['errorMess'] = '您必须设置一级审核权限';
            return http_response($this->data);
        }

        if (!function_exists('shell_exec')) {
            $this->data['errorMess'] = '请在php.ini或者php-fpm中开启appdal的shell_exec的函数';
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->data['errorMess'] = '该操作不能再Window操作系统下执行';
        }
        if (isset($this->data['errorMess'])) {
            return http_response($this->data);
        }
        $session_uid = get_active_user()->uid;
        $get = $this->input->get();
        if (!isset($get['result']) || !in_array($get['result'], [1, 2])) {
            $this->data['errorMess'] = '必须设置审核结果';
            return http_response($this->data);
        }
        if (!isset($get['level']) || !in_array($get['level'], [1, 2, 3])) {
            $this->data['errorMess'] = '必须设置审核等级';
            return http_response($this->data);
        }
        $result = intval($get['result']);
        $level = intval($get['level']);
        $salt = mt_rand(10000, 99999);

        //生成一个查询key
        $query_key = $active_user->staff_code.'.'.$level.'.'.$result;

        //检测是否已经存在进程，目前是单进程处理, 多进程更新因主被动关系导致更新失败。
        $this->load->library('Rediss');
        $query_val = $this->rediss->command(implode(' ', ['hget', 'approve_query_pool', $query_key]));

        if (!$query_val) {
            $path_entry = FCPATH.'index.php';
            $query_val = md5($session_uid.$level.$salt);
            $cmd = sprintf('/usr/bin/php %s fba PR batch_approve_all %s %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $level, $query_val);
            shell_exec($cmd);
            $this->rediss->command(implode(' ', ['hset', 'approve_query_pool', $query_key, $query_val]));
        } else {
            //第二次执行
            $path_entry = FCPATH.'index.php';
            $cmd = sprintf('/usr/bin/php %s fba PR batch_approve_all %s %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $level, $query_val);
        }
        $this->data = ['status' => 1, 'data' => $query_val, 'cmd' => $cmd];
        http_response($this->data);
    }

}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */