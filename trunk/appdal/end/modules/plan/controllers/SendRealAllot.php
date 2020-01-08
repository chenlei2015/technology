<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 实际发运表
 *
 * @author lewei
 * @since 2019-10-24
 */
class SendRealAllot extends MY_Controller {
    
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
//            var_dump($params);exit;
            $this->load->service('plan/SendRealAllotListService', null, 'send_real_allot_list');
            $this->send_real_allot_list->setSearchParams($params);
            //过滤hook
            $this->send_real_allot_list->setPreSearchHook(array($this->send_real_allot_list, 'hook_filter_params'), ['input' => $this->send_real_allot_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->send_real_allot_list->setPreSearchHook(array($this->send_real_allot_list, 'hook_translate_params'), ['input' => &$this->send_real_allot_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->send_real_allot_list->setPreSearchHook(array($this->send_real_allot_list, 'hook_format_params'), ['input' => &$this->send_real_allot_list->search_params, 'update' => 'search_params']);
            $this->data = $this->send_real_allot_list->execSearch();
            $cfg = $this->send_real_allot_list->get_cfg();
            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist($cfg['droplist']);
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }
            //取key值
            $this->load->service('basic/UsercfgProfileService');
            $result = $this->usercfgprofileservice->get_display_cfg($cfg['user_profile']);
            $this->data['selected_data_list'] = $result['config'];
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
     * 推送erp
     */
    public function push_erp(){
        try{
            $params = $this->compatible('get');
            $six_code = $params['six_code'];
            $execute_type = $params['execute_type'];
            if (!isset($execute_type) || !in_array($execute_type,array('by-order','by-need'))){
                throw new RuntimeException("请输入正确格式的execute_type");
            }
            if ($six_code != "ybreal"){
                throw new RuntimeException("验证码错误");
            }

            $data = json_encode(['six-code'=>$six_code,'execute-type'=>$execute_type]);
            $curl = get_curl();
            $http_result = $curl->http_post(PYTHON_MRP_API."/sendfba/toerp/real_execute", $data);
            if ($http_result != "code_ok"){
                throw new RuntimeException("推送erp失败");
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
     *Notes: 查看推送结果列表
     *User: lewei
     *Date: 2019/11/4
     *Time: 17:27
     */
    public function push_erp_result_list(){
        try{
            $params = $this->compatible('get');
            $this->load->service('plan/PushErpStatusListService', null, 'push_erp_status_list');
            $this->push_erp_status_list->setSearchParams($params);
            //过滤hook
            $this->push_erp_status_list->setPreSearchHook(array($this->push_erp_status_list, 'hook_filter_params'), ['input' => $this->push_erp_status_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->push_erp_status_list->setPreSearchHook(array($this->push_erp_status_list, 'hook_translate_params'), ['input' => &$this->push_erp_status_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->push_erp_status_list->setPreSearchHook(array($this->push_erp_status_list, 'hook_format_params'), ['input' => &$this->push_erp_status_list->search_params, 'update' => 'search_params']);
            $this->data = $this->push_erp_status_list->execSearch();
            $cfg = $this->push_erp_status_list->get_cfg();
            //取key值
            $this->load->service('basic/UsercfgProfileService');
            $result = $this->usercfgprofileservice->get_display_cfg($cfg['user_profile']);
            $this->data['selected_data_list'] = $result['config'];

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

    
}
/* End of file Pr.php */
/* Location: ./application/modules/oversea/controllers/Pr.php */