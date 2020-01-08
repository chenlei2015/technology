<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内需求列表
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
     * 列表, 做目录权限， 不做数据权限
     */
    public function list()
    {
        try
        {
            $params = $this->compatible('get');

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $this->load->service('inland/PrListService');

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

            $result = $this->usercfgprofileservice->get_display_cfg('inland_pr_list');
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
            $priv_uid = -1;
            $this->load->service('inland/PrService');
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
     * 获取详情接口
     *
     */
    public function detail()
    {
        try
        {
            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('inland/PrService');
            $this->data['data']['pr'] = $this->prservice->detail($gid);
            $this->data['data']['remark'] = $this->prservice->get_pr_remark($gid);
            $this->load->service('inland/PrLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->prlogservice->get_one_listing_log($gid, $offset, $limit);

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
    public function track_list()
    {
        try
        {
            $params = $this->compatible('get');

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $this->load->service('inland/PrTrackListService', null, 'track_list');

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
            $result = $this->usercfgprofileservice->get_display_cfg('inland_pr_track_list');
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
            $this->load->service('inland/PrTrackService');
            $count = $this->prtrackservice->update_remark($params, -1);
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
            $this->load->service('inland/PrTrackService');
            $this->data['data']['pr'] = $this->prtrackservice->detail($gid);
            $this->data['data']['remark'] = $this->prtrackservice->get_track_remark($gid);
            $this->load->service('inland/PrTrackLogService');
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
            $this->load->service('inland/PrSummaryListService', null, 'summary_list');

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
//            $this->load->service('basic/UsercfgProfileService');
//            $this->data['selected_data_list'] = $this->usercfgprofileservice->get_display_cfg($cfg['user_profile']);

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
            $this->load->service('inland/PrSummaryService');
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
            $this->load->service('inland/PrSummaryService');
            $this->data['data']['pr'] = $this->prsummaryservice->detail($gid);
            $this->data['data']['remark'] = $this->prsummaryservice->get_summary_remark($gid);
            $this->load->service('inland/PrSummaryLogService');
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
    public function inland_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('inland/InlandExportService');
            $this->inlandexportservice->setTemplate($post);
            $this->data['filepath'] = $this->inlandexportservice->export('csv');
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


    public function inland_track_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('inland/InlandTrackExportService');
            $this->inlandtrackexportservice->setTemplate($post);
            $this->data['filepath'] = $this->inlandtrackexportservice->export('csv');
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

    public function inland_summary_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('inland/InlandSummaryExportService');
            $this->inlandsummaryexportservice->setTemplate($post);
            $this->data['filepath'] = $this->inlandsummaryexportservice->export('csv');
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

    public function rebuild_pr()
    {
        try
        {
            $active_user = get_active_user();
            if (is_cli() && func_num_args() > 0) {
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
            } else {
                throw new \InvalidArgumentException('重建需求列表初始化环境错误');
            }
            $this->load->service('inland/PrService');
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

        if (!function_exists('shell_exec')) {
            $this->data['errorMess'] = '请在php.ini或者php-fpm中开启appdal的shell_exec的函数';
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->data['errorMess'] = '该操作不能再Window操作系统下执行';
        }
        if (isset($this->data['errorMess'])) {
            return http_response($this->data);
        }
        $this->load->model('Inland_pr_list_model', 'm_inland_pr_list', false, 'inland');
        if (!$this->m_inland_pr_list->exists_today_data()) {
            $this->data = ['status' => 0, 'errorMess' => '需求列表还未生成，无法重建需求列表'];
            return http_response($this->data);
        }

        $session_uid = $active_user->uid;
        $path_entry = FCPATH.'index.php';
        $cmd = sprintf('/usr/bin/php %s inland PR rebuild_pr %s > /dev/null 2>&1 &', $path_entry, $session_uid);
        shell_exec($cmd);
        log_message('INFO', '执行异步重建需求列表，命令：'.$cmd);
        $this->load->model('Inland_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'inland');
        $version_row = $this->m_rebuild_mvcc->get_last_version(BUSINESS_LINE_INLAND);
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
     * 修改
     * http://192.168.71.170:1084/inland/pr/modify
     */
    public function modify()
    {
        $gid = $this->input->post_get('gid') ?? '';
        $fixed_amount = $this->input->post_get('fixed_amount') ?? '';
        $active_user = get_active_user();
        $user_id = $active_user->staff_code;
        $user_name = $active_user->user_name;
        $params = [
            'gid' => $gid,
            'fixed_amount' => $fixed_amount
        ];
        $this->load->service('inland/PrService');
        $result = $this->prservice->modify($params,$user_id,$user_name);
        $this->data['status']    = 0;
        $this->data['errorMess'] = '修改失败';
        if ($result) {
            $this->data['status']    = 1;
            $this->data['errorMess'] = '修改成功';
        }
        http_response($this->data);
    }

    public function import()
    {
        try {
            $data_values = json_decode(str_replace(" ",'',$this->input->post_get('data_values')), true);
            $active_user = get_active_user();
            $this->load->service('inland/PrService');
            $result = $this->prservice->import_batch($data_values,$active_user->staff_code,$active_user->user_name);
            $this->data['status']    = 1;
            $this->data['data_list'] = $result;
            $code = 200;
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
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     *
     * @author zc
     * @date 2019-10-22
     * @desc 批量审核
     */
    public function batch_approve()
    {
        try
        {
            $params = $this->input->post();
            $this->load->service('inland/PrService');
            $active_user = get_active_user();
            $params['user_id'] = $active_user->staff_code;
            $params['user_name'] = $active_user->user_name;
            $this->data['data'] = $this->prservice->batch_approve($params);
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

    /*
     * 一键审核调用 异步方式
     */
    public function batch_auto_approve()
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

            $this->load->service('inland/PrService');
            $this->data['data'] = $this->prservice->auto_approve($level, $result, $query_value);
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
     *
     * @author zc
     * @date 2019-10-22
     * @desc 批量审核全部未审核
     */
    public function batch_approve_all()
    {
        try
        {
            $params = $this->input->post();
            $this->load->service('inland/PrService');
            $active_user = get_active_user();
            $params['user_id'] = $active_user->staff_code;
            $params['user_name'] = $active_user->user_name;
            $this->data['data'] = $this->prservice->batch_approve_all($params);
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

            $this->load->classes('inland/classes/EstimateMoqPurchaseMoney');
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

    /**
     * 查询估算金额进度
     */
    public function get_inland_total_money()
    {
        try {
            //查询version
            $this->load->model('Inland_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'inland');
            $version = $this->m_rebuild_mvcc->get_last_version(BUSINESS_LINE_INLAND, $today = true);

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

    public function asyc_approve()
    {
        if (!function_exists('shell_exec')) {
            $this->data['errorMess'] = '请在php.ini或者php-fpm中开启appdal的shell_exec的函数';
        }
        /*if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $this->data['errorMess'] = '该操作不能再Window操作系统下执行';
        }*/
        if (isset($this->data['errorMess'])) {
            return http_response($this->data);
        }
        $active_user = get_active_user();
        $session_uid = $active_user->uid;
        $get = $this->input->get();
        if (!isset($get['result']) || !in_array($get['result'], [1, 2])) {
            $this->data['errorMess'] = '必须设置审核结果';
            return http_response($this->data);
        }
        //这里只有等级1，代码复用fba，这里保留而已
        $get['level'] = 1;
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
        $query_val = $this->rediss->command(implode(' ', ['hget', 'inland_approve_query_pool', $query_key]));

        if (!$query_val) {
            $path_entry = FCPATH.'index.php';
            $query_val = md5($session_uid.$level.$salt);
            $cmd = sprintf('/usr/bin/php %s inland PR batch_approve_all %s %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $level, $query_val);
            //shell_exec($cmd);
            $this->rediss->command(implode(' ', ['hset', 'inland_approve_query_pool', $query_key, $query_val]));
        } else {
            //第二次执行
            $path_entry = FCPATH.'index.php';
            $cmd = sprintf('/usr/bin/php %s inland PR batch_auto_approve %s %d %d %s > /dev/null 2>&1 &', $path_entry, $session_uid, $result, $level, $query_val);
        }
        $this->data = ['status' => 1, 'data' => $query_val, 'cmd' => $cmd];
        http_response($this->data);
    }

    public function approve_process()
    {
        $data = [
                'data' => -1,
                'status' => 0
        ];
        $get = $this->input->get();
        $level = $get['level'] ?? 1;
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
        $this->load->service('inland/PrService');
        $data['data'] = $this->prservice->get_approve_process_summary($level, $result, $query);
        $data['status'] = 1;
        http_response($data);
    }

}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */