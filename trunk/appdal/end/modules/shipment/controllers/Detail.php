<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 发运计划业务线详情
 *
 * @version 1.2.0
 * @since 2019-07-09
 */
class Detail extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }
    
    /**
     * 获取一个发运计划海外站点账号的权限
     *
     * <pre> array('east' => 1, 'au' => 1) </pre>
     *
     * @return array
     */
    protected function get_oversea_owner_station()
    {
        $active_user = get_active_user();
        if ($active_user->has_all_data_privileges(BUSSINESS_OVERSEA))
        {
            return ['*' => '*'];
        }
        $account_cfg = $active_user->get_shipment_oversea_stations();
        return $account_cfg;
    }
    
    /**
     * 获取FBA的权限
     */
    protected function get_fba_account()
    {
        $active_user = get_active_user();
        if ($active_user->has_all_data_privileges(BUSSINESS_FBA))
        {
            return ['*' => '*'];
        }
        
        //加上自己作为销售员的条件
        $params['set_data_scope'] = 1;
        
        //这个账号是否是管理员
        $account_name = $active_user->get_my_manager_accounts();
        if (!empty($account_name))
        {
            //管理的账号
            $params['prev_account_name'] = implode(',', $account_name);
            //自己作为销售
            $params['prev_salesman'] = $active_user->staff_code;
        }
        else
        {
            //自己作为销售
            $params['prev_salesman'] = $active_user->staff_code;
        }
        
        return $params;
    }
    
    
    public function fba()
    {
        try
        {
            $params = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);
            
            //附加权限
            $privileges = $this->get_fba_account();
            if (!isset($privileges['*']))
            {
                $params = array_merge($params, $privileges);
            }

            $this->load->service('shipment/FbaListService', null, 'fba_list');
            
            $this->fba_list->setSearchParams($params);
            //过滤hook
            $this->fba_list->setPreSearchHook(array($this->fba_list, 'hook_filter_params'), ['input' => $this->fba_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->fba_list->setPreSearchHook(array($this->fba_list, 'hook_translate_params'), ['input' => &$this->fba_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->fba_list->setPreSearchHook(array($this->fba_list, 'hook_format_params'), ['input' => &$this->fba_list->search_params, 'update' => 'search_params']);
            $this->data = $this->fba_list->execSearch();
            
            $cfg = $this->fba_list->get_cfg();
            //取key值 和 编辑显示字段
            if($params['list'] == 2){//已推送 发运计划跟踪列表
                $this->data['data_list']['key'] = $cfg['title_track'];
                $collection = $cfg['profile_track'];
            }else{//发运计划详情列表 三种状态
                $this->data['data_list']['key'] = $cfg['title_detail'];
                $collection = $cfg['profile_detail'];
            }
            
            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->fba_list->get_cfg()['droplist']
                    );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

            $this->load->service('basic/UsercfgProfileService');
            $result = $this->usercfgprofileservice->get_display_cfg($collection);
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
            http_response($this->data);
        }
    }
    
    public function oversea()
    {
        try
        {
            $params = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);
            
            $privileges = $this->get_oversea_owner_station();
            if (empty($privileges))
            {
                throw new \Exception('您没有权限', 412);
            }
            elseif (!isset($privileges['*']))
            {
                $params['owner_station'] = $privileges;
            }

            $this->load->service('shipment/OverseaListService', null, 'oversea_list');
            
            $this->oversea_list->setSearchParams($params);
            //过滤hook
            $this->oversea_list->setPreSearchHook(array($this->oversea_list, 'hook_filter_params'), ['input' => $this->oversea_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->oversea_list->setPreSearchHook(array($this->oversea_list, 'hook_translate_params'), ['input' => &$this->oversea_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->oversea_list->setPreSearchHook(array($this->oversea_list, 'hook_format_params'), ['input' => &$this->oversea_list->search_params, 'update' => 'search_params']);
            $this->data = $this->oversea_list->execSearch();
            
            $cfg = $this->oversea_list->get_cfg();
            //取key值 和 编辑显示字段
            if($params['list'] == 2){//已推送 发运计划跟踪列表
                $this->data['data_list']['key'] = $cfg['title_track'];
                $collection = $cfg['profile_track'];
            }else{//发运计划详情列表 三种状态
                $this->data['data_list']['key'] = $cfg['title_detail'];
                $collection = $cfg['profile_detail'];
            }

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->oversea_list->get_cfg()['droplist']
                    );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }


            $this->load->service('basic/UsercfgProfileService');
             $result = $this->usercfgprofileservice->get_display_cfg($collection);
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
            http_response($this->data);
        }
    }


    public function fba_modify()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('shipment/FbaDetailService');
            $this->fbadetailservice->modify_shipment_qty($post);
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

    public function oversea_modify()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('shipment/OverseaDetailService');
            $this->overseadetailservice->modify_shipment_qty($post);
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
     * 导出功能
     */
    public function fba_export()
    {
        try {
            $post = $this->compatible('post');
            $this->load->service('shipment/FbaDetailExportService');
            $this->fbadetailexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbadetailexportservice->export('csv');
            $this->data['status']   = 1;
            $code                   = 200;
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
     * 导出功能
     */
    public function oversea_export()
    {
        try {
            $post = $this->compatible('post');
            $this->load->service('shipment/OverseaDetailExportService');
            $this->overseadetailexportservice->setTemplate($post);
            $this->data['filepath'] = $this->overseadetailexportservice->export('csv');
            $this->data['status']   = 1;
            $code                   = 200;
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
     * 导入批量修改发运数量
     */
    public function fba_upload()
    {
        try
        {
            $info = [];
            $data['data'] = json_decode($this->input->post('data_values'), true);

            $count = count($data['data']);
            $processed = 0;//已处理
            $undisposed = 0;//未处理
            $this->load->service('shipment/FbaDetailService');
            $processed = $this->fbadetailservice->modify_shipment_qty($data);
            $undisposed = $count-$processed;

            $info['processed'] = $processed;//已处理
            $info['undisposed'] = $undisposed;  //未处理

            $this->data['status'] = 1;
            $this->data['data_list'] = $info;
            http_response($this->data);
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
     * 导入批量修改发运数量
     */
    public function oversea_upload()
    {
        try
        {
            $info = [];
            $data['data'] = json_decode($this->input->post('data_values'), true);

            $count = count($data['data']);
            $processed = 0;//已处理
            $undisposed = 0;//未处理
            $this->load->service('shipment/OverseaDetailService');
            $processed = $this->overseadetailservice->modify_shipment_qty($data);
            $undisposed = $count-$processed;

            $info['processed'] = $processed;//已处理
            $info['undisposed'] = $undisposed;  //未处理

            $this->data['status'] = 1;
            $this->data['data_list'] = $info;
            http_response($this->data);
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

    public function fba_shipment_detail()
    {
        try
        {
            $get = $this->compatible('get');
            $this->load->service('shipment/FbaDetailService');
            $result = $this->fbadetailservice->shipment_detail($get);
            $this->data['data'] = $result;
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
        exit;
    }

    public function oversea_shipment_detail()
    {
        try
        {
            $get = $this->compatible('get');
            $this->load->service('shipment/OverseaDetailService');
            $result = $this->overseadetailservice->shipment_detail($get);
            $this->data['data'] = $result;
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
        exit;
    }
    
}
