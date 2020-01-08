<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 备货列表
 *
 * @author Jason 13292
 * @since 2019-03-08
 */
class Purchase extends MY_Controller {

    /**
     *
     */
    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }

    /**
     * 列表, 默认给国内仓权限
     */
    public function list()
    {
        try
        {
            $active_user = get_active_user();
            $grant_lines = $active_user->get_all_data_privilege_buss_lines();
            $grant_lines[] = BUSSINESS_IN;
            if (is_string($grant_lines) && $grant_lines == '')
            {
                throw new \Exception('您没有任何业务线的全部数据权限', 412);
            }

            $params = $this->compatible('get');
            $isExcel = $params['isExcel']??'';
            if ($isExcel){
                $params['offset'] = 1;
                $idsArr = params_ids_to_array($params['gids']??'',false);
                if (!empty($idsArr)){
                    $params['gidsArr'] = $idsArr;
                }
            }

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            if (isset($params['bussiness_line']) && !empty($params['bussiness_line']))
            {
                $grant_lines = array_intersect($grant_lines, explode(',', (string)$params['bussiness_line']));
                if (empty($grant_lines))
                {
                    throw new \Exception('您没有选择业务线的全部数据权限', 412);
                }
                $params['bussiness_line'] = implode(',', $grant_lines);
            }


            $this->load->service('plan/PlanListService', null, 'purchase_list');

            $this->purchase_list->setSearchParams($params);
            //过滤hook
            $this->purchase_list->setPreSearchHook(array($this->purchase_list, 'hook_filter_params'), ['input' => $this->purchase_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->purchase_list->setPreSearchHook(array($this->purchase_list, 'hook_translate_params'), ['input' => &$this->purchase_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->purchase_list->setPreSearchHook(array($this->purchase_list, 'hook_format_params'), ['input' => &$this->purchase_list->search_params, 'update' => 'search_params']);
            $this->data = $this->purchase_list->execSearch();

            $cfg = $this->purchase_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->purchase_list->get_cfg()['droplist']
                    );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }
            $this->load->service('basic/UsercfgProfileService');

            $result = $this->usercfgprofileservice->get_display_cfg('stock_list');
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
            $params = $this->compatible('post');
            $this->load->service('plan/PlanService');
            $count = $this->planservice->update_remark($params);
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
            $this->load->service('plan/PlanService');
            $this->data['data']['detail'] = $this->planservice->detail($gid);
            $active_user = get_active_user();
            if (!$active_user->get_all_data_privilege_buss_lines())
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }
            $this->data['data']['remark'] = $this->planservice->get_remark($gid);

            $this->load->service('plan/PlanLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->planlogservice->get_log($gid, $offset, $limit);

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
            $active_user = get_active_user();
            $grant_lines = $active_user->get_all_data_privilege_buss_lines();
            $grant_lines[] = BUSSINESS_IN;
            if (is_string($grant_lines) && $grant_lines == '')
            {
                throw new \Exception('您没有任何业务线的全部数据权限', 412);
            }

            $params = $this->compatible('get');
            $isExcel = $params['isExcel']??'';
            if ($isExcel){
                $params['offset'] = 1;
                $idsArr = params_ids_to_array($params['gids']??'',false);
                if (!empty($idsArr)){
                    $params['gidsArr'] = $idsArr;
                }
            }

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            if (isset($params['bussiness_line']))
            {
                $grant_lines = array_intersect($grant_lines, explode(',', (string)$params['bussiness_line']));
                if (empty($grant_lines))
                {
                    throw new \Exception('您没有选择业务线的全部数据权限', 412);
                }
                $params['bussiness_line'] = implode(',', $grant_lines);
            }

            $this->load->service('plan/PlanSummaryListService', null, 'plan_summary_list');

            $this->plan_summary_list->setSearchParams($params);
            //过滤hook
            $this->plan_summary_list->setPreSearchHook(array($this->plan_summary_list, 'hook_filter_params'), ['input' => $this->plan_summary_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->plan_summary_list->setPreSearchHook(array($this->plan_summary_list, 'hook_translate_params'), ['input' => &$this->plan_summary_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->plan_summary_list->setPreSearchHook(array($this->plan_summary_list, 'hook_format_params'), ['input' => &$this->plan_summary_list->search_params, 'update' => 'search_params']);
            $this->data = $this->plan_summary_list->execSearch();

            $cfg = $this->plan_summary_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->plan_summary_list->get_cfg()['droplist']
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
     * 添加一条汇总备注
     */
    public function summary_remark()
    {
        try
        {
            $active_user = get_active_user();
            if (!$active_user->get_all_data_privilege_buss_lines())
            {
                //throw new \Exception('您必须设置全部数据权限', 412);
            }

            $params = $this->compatible('post');
            $this->load->service('plan/PlanSummaryService');
            $count = $this->plansummaryservice->update_remark($params);
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
            $active_user = get_active_user();
            if (!$active_user->get_all_data_privilege_buss_lines())
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }

            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('plan/PlanSummaryService');
            $this->data['data']['detail'] = $this->plansummaryservice->detail($gid);
            $this->data['data']['remark'] = $this->plansummaryservice->get_summary_remark($gid);
            $this->load->service('plan/PlanSummaryLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->plansummarylogservice->get_summary_log($gid, $offset, $limit);

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
     * 跟踪列表, 默认给国内仓权限
     */
    public function track_list()
    {
        try
        {
            $active_user = get_active_user();
            $grant_lines = $active_user->get_all_data_privilege_buss_lines();
            $grant_lines[] = BUSSINESS_IN;
            if (is_string($grant_lines) && $grant_lines == '')
            {
                throw new \Exception('您没有任何业务线的全部数据权限', 412);
            }

            $params = $this->compatible('get');
            $isExcel = $params['isExcel']??'';
            if ($isExcel){
                $params['offset'] = 1;
                $idsArr = params_ids_to_array($params['gids']??'',false);
                if (!empty($idsArr)){
                    $params['gidsArr'] = $idsArr;
                }
            }

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            if (isset($params['bussiness_line']))
            {
                $grant_lines = array_intersect($grant_lines, explode(',', (string)$params['bussiness_line']));
                if (empty($grant_lines))
                {
                    throw new \Exception('您没有选择业务线的全部数据权限', 412);
                }
                $params['bussiness_line'] = implode(',', $grant_lines);
            }

            $this->load->service('plan/PlanTrackListService', null, 'plan_track_list');

            $this->plan_track_list->setSearchParams($params);
            //过滤hook
            $this->plan_track_list->setPreSearchHook(array($this->plan_track_list, 'hook_filter_params'), ['input' => $this->plan_track_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->plan_track_list->setPreSearchHook(array($this->plan_track_list, 'hook_translate_params'), ['input' => &$this->plan_track_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->plan_track_list->setPreSearchHook(array($this->plan_track_list, 'hook_format_params'), ['input' => &$this->plan_track_list->search_params, 'update' => 'search_params']);
            $this->data = $this->plan_track_list->execSearch();

            $cfg = $this->plan_track_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->plan_track_list->get_cfg()['droplist']
                    );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }
            $this->load->service('basic/UsercfgProfileService');

            $result = $this->usercfgprofileservice->get_display_cfg('track_list');
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
     * 添加一条跟踪备注
     */
    public function track_remark()
    {
        try
        {
            $active_user = get_active_user();
            if (!$active_user->get_all_data_privilege_buss_lines())
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }

            $params = $this->compatible('post');
            $this->load->service('plan/PlanTrackService');
            $count = $this->plantrackservice->update_remark($params);
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
            $active_user = get_active_user();
            if (!$active_user->get_all_data_privilege_buss_lines())
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }

            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('plan/PlanTrackService');
            $this->data['data']['detail'] = $this->plantrackservice->detail($gid);
            $this->data['data']['remark'] = $this->plantrackservice->get_track_remark($gid);

            $this->load->service('plan/PlanTrackLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->plantracklogservice->get_track_log($gid, $offset, $limit);

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
     * 采购回传计划系统数据 (采购系统改变备货单状态)
     * http://192.168.71.170:1084/plan/purchase/track_check_update
     */
    public function track_check_update()
    {
        $params = null;
         try
         {
             $params = $this->compatible('post');
             if (empty($params['data_list'])){
                 $code = 301;
                 $errorMsg = '参数错误';
             }else{
                 $data_list = $params['data_list'];
                 if(is_string($data_list)){
                     $data_list = json_decode($data_list,true);
                 }

                 if (count($data_list)>0){
                     $this->load->service('plan/PlanTrackService');
                     $this->plantrackservice->track_check_update($data_list);
                     $this->data['status'] = 1;
                     $code = 200;
                 }else{
                     $code = 301;
                     $errorMsg = 'data_list参数列表为空';
                 }
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
             isset($errorMsg) and $errorMsg = 'params:'.json_encode($params).'=>'.$errorMsg;
             $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
             isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
             http_response($this->data);
         }

    }

    /**
     * 采购回传计划系统数据:（采购系统）需求单采购单状态变更=》（计划系统）备货跟踪状态的备货状态同步变更     (采购系统改变采购单状态)-------
     * http://192.168.71.170:1084/plan/purchase/track_state_update
     */
    public function track_state_update()
    {
        $params = null;
        try
        {
            $params = $this->compatible('post');
            if (empty($params['data_list'])){
                $code = 301;
                $errorMsg = '参数错误';
            }else{
                $data_list = $params['data_list'];
                if(is_string($data_list)){
                    $data_list = json_decode($data_list,true);
                }

                if (count($data_list)>0){
                    $this->load->service('plan/PlanTrackService');
                    $this->plantrackservice->track_state_update($data_list);
                    $this->data['status'] = 1;
                    $code = 200;
                }else{
                    $code = 301;
                    $errorMsg = 'datas参数列表为空';
                }
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
            isset($errorMsg) and $errorMsg = 'params:'.json_encode($params).'=>'.$errorMsg;
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    /**
     * author:Manson
     * 采购回传计划系统数据:（采购系统）需求单过期=》计划系统备货列表过期    (采购系统备货单过期)
     * http://192.168.71.170:1084/plan/purchase/purchase_approve_state_update
     */
    public function purchase_approve_state_update()
    {
        try
        {
            $params = $this->compatible('post');
            if (empty($params['data_list'])){
                $code = 301;
                $errorMsg = '参数错误';
            }else{
                $data_list = $params['data_list'];

                if(is_string($data_list)){
                    $data_list = json_decode($data_list,true);
                }
                if (count($data_list)>0){
                    $this->load->service('plan/PlanPrNumberService');
                    $this->planprnumberservice->update_state($data_list);
                    $this->data['status'] = 1;
                    $code = 200;
                }else{
                    $code = 301;
                    $errorMsg = 'data_list参数列表为空';
                }
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
     * 采购回传计划系统数据:（采购系统）审核不通过=》推送采购数量过期
     * http://192.168.71.170:1084/plan/purchase/purchase_state_update
     */
    public function purchase_state_update()
    {
        try
        {
            $params = $this->compatible('post');
            if (empty($params['data_list'])){
                $code = 301;
                $errorMsg = '参数错误';
            }else{
                $data_list = $params['data_list'];
                if(is_string($data_list)){
                    $data_list = json_decode($data_list,true);
                }

                if (count($data_list)>0){
                    $this->load->service('plan/PlanService');
                    $this->planservice->batch_update_state($data_list);
                    $this->data['status'] = 1;
                    $code = 200;
                }else{
                    $code = 301;
                    $errorMsg = 'data_list参数列表为空';
                }
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

    public function List_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('plan/PlanListExportService');
            $this->planlistexportservice->setTemplate($post);
            $this->data['filepath'] = $this->planlistexportservice->export('csv');
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

    public function track_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('plan/PlanTrackExportService');
            $this->plantrackexportservice->setTemplate($post);
            $this->data['filepath'] = $this->plantrackexportservice->export('csv');
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

    public function summary_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('plan/PlanSummaryExportService');
            $this->plansummaryexportservice->setTemplate($post);
            $this->data['filepath'] = $this->plansummaryexportservice->export('csv');
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

    public function edit_push_stock_quantity()
    {
        try
        {
            $post = $this->compatible('post');
            $post['selected'] = json_decode($post['selected'], true);
            $this->load->service('plan/PlanService');
            if ($this->planservice->check_edit_push_close_time())
            {
                throw new \OutOfRangeException('当前时间段不允许执行此操作', 500);
            }
            $report = $this->planservice->batch_update_push_stock_quantity($post);
            $this->data['data'] = ['processed' => $report['succ'], 'undisposed' => $report['fail'] + $report['ignore'] ];
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
     * 三条业务线的汇总列表手动调用推送的备货列表
     */
    public function push_summary()
    {
        $get = $this->input->get();
        if (!isset($get['business_line']) || !isset(BUSSINESS_LINE[$get['business_line']]))
        {
            return http_response(['errorMess' => '请选择推送的业务线']);
        }
        $business_line = intval($get['business_line']);
        $this->load->model('Push_log_model', 'm_push_log', false, 'plan');
        if ($this->m_push_log->is_summary_pushed($business_line)) {
            $this->data['errorMess'] = '业务线今天已经推送';
            return http_response($this->data);
        }
        $result = RPC_CALL('YB_PLAN_PUSH_SUMMARY', ['businessLine' => intval($get['business_line'])]);
        if (isset($result['code']) && $result['code'] == 200) {
            $this->data['status'] = 1;
            $this->data['data'] = '备货列表生成成功';
        } else {
            $result['code'] = 500;
            $this->data['errorMess'] = $result['msg'] ?? '备货列表生成失败';
        }
        $this->m_push_log->add_push_summary($result['code'], $business_line, $result['msg'] ?? '');
        http_response($this->data);
    }

    /**
     * 采购推送
     *
     * @return unknown
     */
    public function push_purchase()
    {
        $get = $this->input->get();
        if (!isset($get['business_line']))
        {
            return http_response(['errorMess' => '请选择推送的业务线']);
        }
        $business_line = $get['business_line'];
        $this->load->model('Push_log_model', 'm_push_log', false, 'plan');
        $check = $this->m_push_log->check_purchase_pushed($business_line);
        if (!empty($check['pushed'])) {
            $line_str = '';
            foreach ($check['pushed'] as $line) {
                $line_str .= BUSINESS_LINE[$line]['name'].',';
            }
            $this->data['errorMess'] = sprintf('业务线%s今天已经推送', $line_str);
            return http_response($this->data);
        }
        $result = RPC_CALL('YB_PLAN_PUSH_PURCHASE', ['bussinessLine' => is_array($business_line) ? $business_line : explode(',', $business_line)]);
        if (isset($result['code']) && $result['code'] == 200) {
            $this->data['status'] = 1;
            $this->data['data'] = '推送备货列表到采购系统成功';
        } else {
            $result['code'] = 500;
            $this->data['errorMess'] = $result['msg'] ?? '推送备货列表到采购系统失败';
        }
        $this->m_push_log->add_push_purchase($result['code'], $business_line, $result['msg'] ?? '');
        http_response($this->data);
    }


    public function manual_push()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('plan/PlanService');
            if ($this->planservice->check_edit_push_close_time())
            {
                throw new \OutOfRangeException('当前时间段不允许执行此操作', 500);
            }
            $this->data['data'] = $this->planservice->manual_push($post);
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
     * 通过调java接口,查询erp的调拨申请单数据
     * 调拨申请单列表
     * 传order_id
     */
    public function warehouse_transfer_list()
    {
        try {
            $post = $this->compatible('post');
            $this->load->service('plan/PlanService');
            $result                           = $this->planservice->java_warehouse_transfer($post);
            $this->data['data_list']['value'] = $result;
            $this->data['status']             = 1;
            $code                             = 200;
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


}
/* End of file Pr.php */
/* Location: ./application/modules/oversea/controllers/Pr.php */