<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 调拨列表
 *
 * @author Jason 13292
 * @since 2019-03-08
 */
class Allot extends MY_Controller {
    
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
            $active_user = get_active_user();
            if (!$active_user->get_all_data_privilege_buss_lines())
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }
            
            $params = $this->compatible('get');
            
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);
            $isExcel = $params['isExcel']??'';
            if ($isExcel){
                $params['offset'] = 1;
                $idsArr = params_ids_to_array($params['gids']??'',false);
                if (!empty($idsArr)){
                    $params['gidsArr'] = $idsArr;
                }
            }
            
            $this->load->service('plan/AllotmentListService', null, 'allot_list');
            
            $this->allot_list->setSearchParams($params);
            //过滤hook
            $this->allot_list->setPreSearchHook(array($this->allot_list, 'hook_filter_params'), ['input' => $this->allot_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->allot_list->setPreSearchHook(array($this->allot_list, 'hook_translate_params'), ['input' => &$this->allot_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->allot_list->setPreSearchHook(array($this->allot_list, 'hook_format_params'), ['input' => &$this->allot_list->search_params, 'update' => 'search_params']);
            $this->data = $this->allot_list->execSearch();
            
            $cfg = $this->allot_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
            
            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->allot_list->get_cfg()['droplist']
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
     * 添加一条备注
     */
    public function remark()
    {
        try
        {
            $active_user = get_active_user();
            if (!$active_user->get_all_data_privilege_buss_lines())
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }
            
            $params = $this->compatible('post');
            $this->load->service('plan/AllotmentService');
            $count = $this->allotmentservice->update_remark($params);
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
            $active_user = get_active_user();
            if (!$active_user->get_all_data_privilege_buss_lines())
            {
                throw new \Exception('您必须设置全部数据权限', 412);
            }
            
            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('plan/AllotmentService');
            $this->data['data']['detail'] = $this->allotmentservice->detail($gid);
            $this->data['data']['remark'] = $this->allotmentservice->get_remark($gid);
            
            $this->load->service('plan/AllotmentLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->allotmentlogservice->get_log($gid, $offset, $limit);
            
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

    
}
/* End of file Pr.php */
/* Location: ./application/modules/oversea/controllers/Pr.php */