<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 海外平台需求列表
 *
 * @author Jason 13292
 * @since 2019-03-08
 */
class Platform extends MY_Controller {

    private $_manager_cfg;

    public function __construct()
    {
        parent::__construct();
        $this->_manager_cfg = $this->get_owner_station_platform();
    }

    /**
     * 获取一个账号的权限
     *
     * <pre> array('east' => ['ail' => true, 'cd' => 1]) </pre>
     *
     * @return array
     */
    protected function get_owner_station_platform()
    {
        $active_user = get_active_user();
        if ($active_user->has_all_data_privileges(BUSSINESS_OVERSEA))
        {
            return ['*' => '*'];
        }
        //获取
        $account_cfg = $active_user->get_my_station_platforms();
        return $account_cfg;
    }

    /**
     * 列表
     */
    public function list()
    {
        try
        {
            $params = $this->compatible('get');
            if (empty($this->_manager_cfg))
            {
                throw new \Exception('您没有权限', 412);
            }
            elseif (!isset($this->_manager_cfg['*']))
            {
                $params['owner_station'] = $this->_manager_cfg;
            }

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $this->load->service('oversea/PlatformListService', null, 'oversea_list');
            $this->oversea_list->setSearchParams($params);
            //过滤hook
            $this->oversea_list->setPreSearchHook(array($this->oversea_list, 'hook_filter_params'), ['input' => $this->oversea_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->oversea_list->setPreSearchHook(array($this->oversea_list, 'hook_translate_params'), ['input' => &$this->oversea_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->oversea_list->setPreSearchHook(array($this->oversea_list, 'hook_format_params'), ['input' => &$this->oversea_list->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->oversea_list->setAfterSearchHook(array($this->oversea_list, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->oversea_list->execSearch();

            $cfg = $this->oversea_list->get_cfg();
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
     * 添加一条备注
     */
    public function remark()
    {
        try
        {
            if (empty($this->_manager_cfg))
            {
                throw new \Exception('您没有权限', 412);
            }
            $params = $this->compatible('post');
            $this->load->service('oversea/PlatformService');
            $count = $this->platformservice->update_remark($params, $this->_manager_cfg);
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
            if (empty($this->_manager_cfg))
            {
                throw new \Exception('您没有权限', 412);
            }
            $params = $this->compatible('post');
            $this->load->service('oversea/PlatformService');
            $this->data['data'] = $this->platformservice->edit_pr_listing($params, $this->_manager_cfg);
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
            if (empty($this->_manager_cfg))
            {
                throw new \Exception('您没有权限', 412);
            }
            $params = $this->compatible('post');
            $requir_cols = array_flip(['primary_key', 'map', 'selected']);
            if (count(array_diff_key($requir_cols, $params)) > 0 )
            {
                throw new \InvalidArgumentException('无效的参数', 412);
            }
            $this->load->service('oversea/PlatformService');
            $this->data['data'] = $this->platformservice->batch_edit_pr($params, $this->_manager_cfg);
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
     * 批量审核
     */
    public function approve()
    {
        try
        {
            if (empty($this->_manager_cfg))
            {
                throw new \Exception('您没有权限', 412);
            }
            $active_user = get_active_user();
            if (!$active_user->is_first_approver(BUSSINESS_OVERSEA))
            {
                throw new \RuntimeException(sprintf('请使用一级审核权限账号进行操作'), 500);
            }
            $params = $this->input->post();
            $gid = $params['gid'] ?? [];
            $result = $params['result'];
            $this->load->service('oversea/PlatformService');
            $this->platformservice->check_enable_time(__FUNCTION__);
            $this->data['data'] = $this->platformservice->approve_platform($gid, $result, $this->_manager_cfg);
            if ($this->data['data']['succ'] > 0)
            {
                $this->data['status'] = 1;
                $code = 200;
            }
            else
            {
                $code = 500;
                $errorMsg = '没有更新任何一条记录, 勾选的记录不符合汇总要求';
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
            http_response($this->data);
        }

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
        if (!in_array($get['result'], [1, 2])) {
            $this->data['errorMess'] = '必须设置审核结果';
            return http_response($this->data);
        }
        $this->load->service('oversea/PlatformService');//todo 要改
        $data['data'] = $this->platformservice->get_approve_process_summary($result, $query);
        $data['status'] = 1;
        http_response($data);
    }

    /**
     * 异步全量审核入口
     * result =1 审核通过  result= 2 审核失败
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
        if (!isset($get['result']) || !in_array($get['result'], [1, 2])) {
            $this->data['errorMess'] = '必须设置审核结果'.json_encode($get);
            return http_response($this->data);
        }

        $result = intval($get['result']);
        $salt = mt_rand(10000, 99999);
        $session_uid = $active_user->uid;
        //$user_name = $active_user->user_name;


        //生成一个查询审批进度的key 并保存到redis的hash表logistics_approve_query_pool中
        $this->load->library('Rediss');
        $query_key = $active_user->staff_code.'.'.$result;

        $command = "eval \"redis.call('hdel', 'platform_approve_query_pool', KEYS[1]);return 'SUCC';\" 1 %s";// todo 要改
        $command = sprintf($command,$query_key);
        $result_command = $result && $this->rediss->eval_command($command);

        $query_val = $this->rediss->command(implode(' ', ['hget', 'platform_approve_query_pool', $query_key]));//todo 要改
        log_message('INFO',"query_value : {$query_val}");

        if (!$query_val) {
            log_message('INFO',"第一次开始执行审批");
            $path_entry = FCPATH.'index.php';
            $query_val = md5($session_uid.$salt);
            $cmd = sprintf('/usr/bin/php %s oversea Platform batch_approve_all %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $query_val);//todo 要改
            shell_exec($cmd);
            $this->rediss->command(implode(' ', ['hset', 'platform_approve_query_pool', $query_key, $query_val]));//todo 要改
        } else {
            log_message('INFO',"第二次开始执行审批");
            //第二次执行
            $path_entry = FCPATH.'index.php';
            $cmd = sprintf('/usr/bin/php %s oversea Platform batch_approve_all %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $query_val);//todo 要改
        }

        $this->data = ['status' => 1, 'data' => $query_val, 'cmd' => $cmd];
        http_response($this->data);
    }


    /**
     * 批量审核 命令行 调用
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
                //log_message('INFO',"user_data:".json_encode($user_data));
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

            //检查权限权限
            $active_user = get_active_user();
            if ($active_user->has_all_data_privileges(BUSSINESS_OVERSEA))
            {
                $account_cfg =  ['*' => '*'];
            }else{
                $account_cfg = $active_user->get_my_station_platforms();
            }
            //获取
            if (empty($account_cfg))
            {
                throw new \Exception('您没有权限', 412);
            }

            if (!$active_user->is_first_approver(BUSSINESS_OVERSEA))
            {
                throw new \RuntimeException(sprintf('请使用一级审核权限账号进行操作'), 500);
            }

            // 调用审批方法主体
            $this->load->service('oversea/PlatformService');//todo 要改
            $this->platformservice->check_enable_time(__FUNCTION__);
            $this->data['data'] = $this->platformservice->batch_approve_all($result,$query_value,$account_cfg);//todo 要改
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
            if (empty($this->_manager_cfg))
            {
                throw new \Exception('您没有权限', 412);
            }
            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('oversea/PlatformService');
            $this->data['data']['pr'] = $this->platformservice->detail($gid);
            if (!isset($this->_manager_cfg['*']) && !isset($this->_manager_cfg[$this->data['data']['pr']['station_code']][$this->data['data']['pr']['platform_code']]))
            {
                throw new \Exception('您没有权限', 412);
            }
            $this->data['data']['remark'] = $this->platformservice->get_pr_remark($gid);
            $this->load->service('oversea/PlatformLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->platformlogservice->get_one_listing_log($gid, $offset, $limit);
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
     * 订单导出， 预期支持不同字段的导出
     *
     */
    public function export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('oversea/OverseaPlatformExportService');
            $this->overseaplatformexportservice->setTemplate($post);
            $this->data['filepath'] = $this->overseaplatformexportservice->export('csv');
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

}
/* End of file Pr.php */
/* Location: ./application/modules/oversea/controllers/Pr.php */
