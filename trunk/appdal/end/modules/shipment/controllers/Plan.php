<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 发运计划
 *
 * @version 1.2.0
 * @since 2019-07-09
 */
class Plan extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }
    
    public function fba_list()
    {
        try
        {
            $params = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);
            
            $this->load->service('shipment/PlanFbaListService', null, 'shipment_list');
            
            $this->shipment_list->setSearchParams($params);
            //过滤hook
            $this->shipment_list->setPreSearchHook(array($this->shipment_list, 'hook_filter_params'), ['input' => $this->shipment_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->shipment_list->setPreSearchHook(array($this->shipment_list, 'hook_translate_params'), ['input' => &$this->shipment_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->shipment_list->setPreSearchHook(array($this->shipment_list, 'hook_format_params'), ['input' => &$this->shipment_list->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->shipment_list->setAfterSearchHook(array($this->shipment_list, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->shipment_list->execSearch();
            
            $cfg = $this->shipment_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
            
            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist($cfg['droplist']);
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
            http_response($this->data);
        }
    }


    public function oversea_list()
    {
        try
        {
            $params = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $this->load->service('shipment/PlanOverseaListService', null, 'shipment_list');

            $this->shipment_list->setSearchParams($params);
            //过滤hook
            $this->shipment_list->setPreSearchHook(array($this->shipment_list, 'hook_filter_params'), ['input' => $this->shipment_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->shipment_list->setPreSearchHook(array($this->shipment_list, 'hook_translate_params'), ['input' => &$this->shipment_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->shipment_list->setPreSearchHook(array($this->shipment_list, 'hook_format_params'), ['input' => &$this->shipment_list->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->shipment_list->setAfterSearchHook(array($this->shipment_list, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->shipment_list->execSearch();

            $cfg = $this->shipment_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->shipment_list->get_cfg()['droplist']
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
            http_response($this->data);
        }
    }

    /**
     * 导入
     */
    public function fba_upload()
    {
        try
        {
//            $info = [];
            $data = json_decode($this->input->post('data_values'), true);

//            $count = count($data);
//            $processed = 0;//已处理
//            $undisposed = 0;//未处理
            $this->load->service('shipment/FbaPlanService');
            $result = $this->fbaplanservice->upload($data);
//            $undisposed = $count-$processed;

//            $info['processed'] = $processed;//已处理
//            $info['undisposed'] = $undisposed;  //未处理

            $this->data['status'] = 1;
            $this->data['shipment_sn'] = $result;
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
     * 导入生成发运计划
     */
    public function oversea_upload()
    {
        try
        {
//            $info = [];
            $data = json_decode($this->input->post('data_values'), true);
//            $count = count($data);
//            $processed = 0;//已处理
//            $undisposed = 0;//未处理
            $this->load->service('shipment/OverseaPlanService');
            $result = $this->overseaplanservice->upload($data);
//            $undisposed = $count-$processed;

//            $info['processed'] = $processed;//已处理
//            $info['undisposed'] = $undisposed;  //未处理

            $this->data['status'] = 1;
            $this->data['shipment_sn'] = $result;
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
     * 选择跟踪列表 日期
     */
    public function getDate()
    {
        try
        {
            $params = $this->compatible('get');

            if ($params['business_line'] == BUSSINESS_FBA){
                $this->load->service('shipment/FbaPlanService');
                $this->data['date'] = $this->fbaplanservice->get_tracking_date();

            }elseif ($params['business_line'] == BUSSINESS_OVERSEA){
                $this->load->service('shipment/OverseaPlanService');
                $this->data['date'] = $this->overseaplanservice->get_tracking_date();
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
     * 根据选择的需求跟踪日期 生成发运计划
     */
    public function fbaPlanByTracking()
    {
        try
        {
            $params = $this->compatible('post');

            $this->load->service('shipment/FbaPlanService');
            $this->data['shipment_sn'] = $this->fbaplanservice->once_add_shipment($params);

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
            $this->fbaplanservice->deleteData();
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }
        
    }

    /**
     * 根据选择的需求跟踪日期 生成发运计划
     */
    public function overseaPlanByTracking()
    {
        try
        {
            $params = $this->compatible('post');

            $this->load->service('shipment/OverseaPlanService');
            $this->data['shipment_sn'] = $this->overseaplanservice->once_add_shipment($params);

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
            $this->overseaplanservice->deleteData();
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }
        
    }

    /**
     * 导出功能
     */
    public function export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('oversea/OverseaStockCfgExportService');
            $this->overseastockcfgexportservice->setTemplate($post);
            $this->data['filepath'] = $this->overseastockcfgexportservice->export('csv');
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
     * 获取站点与平台的管理列表
     */
    public function oversea_manager_list()
    {
        try
        {
            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_OVERSEA))
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }
            
            $params = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);
            
            $this->load->service('shipment/OverseaManagerAccountListService', null, 'account_list');
            
            $this->account_list->setSearchParams($params);
            //过滤hook
            $this->account_list->setPreSearchHook(array($this->account_list, 'hook_filter_params'), ['input' => $this->account_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->account_list->setPreSearchHook(array($this->account_list, 'hook_translate_params'), ['input' => &$this->account_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->account_list->setPreSearchHook(array($this->account_list, 'hook_format_params'), ['input' => &$this->account_list->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->account_list->setAfterSearchHook(array($this->account_list, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->account_list->execSearch();
            
            $cfg = $this->account_list->get_cfg();
            
            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $cfg['droplist']
                    );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }
            
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
            
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
    
    public function set_station_manager()
    {
        try
        {
            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_OVERSEA))
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }
            
            $params = $this->compatible('post');
            $this->load->service('shipment/OverseaManagerAccountService', null, 'manager_account');
            $this->data['data'] = $this->manager_account->set_manager($params);
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

    public function fba_push()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('shipment/FbaPlanService');
            $result = $this->fbaplanservice->push($post);
            if ($result === true){
                $this->data['errorMess'] = '已成功推送至仓库系统！';
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
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }

    public function oversea_push()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('shipment/OverseaPlanService');
            $result = $this->overseaplanservice->push($post);
            if ($result === true){
                $this->data['errorMess'] = '已成功推送至仓库系统！';
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
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }


    /**
     * 推送状态为:发送至物流系统失败
     * 手动发送到物流系统
     */
    public function fba_send()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('shipment/FbaPlanService');
            $result = $this->fbaplanservice->send($post);
            if ($result === true){
                $this->data['errorMess'] = '已成功推送至物流系统！';
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
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }

    /**
     * 推送状态为:发送至物流系统失败
     * 手动发送到物流系统
     */
    public function oversea_send()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('shipment/OverseaPlanService');
            $result = $this->overseaplanservice->send($post);
//            $this->data['successList'] = sprintf('推送成功:%s',$result['successList']??'');
//            $this->data['failList'] = sprintf('推送失败:%s',$result['failList']??'');
            if ($result === true){
                $this->data['errorMess'] = '已成功推送至物流系统！';
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
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }
    
}
