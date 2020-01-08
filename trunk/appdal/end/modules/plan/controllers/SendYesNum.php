<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 模拟发运表
 *
 * @author lewei
 * @since 2019-10-24
 */
class SendYesNum extends MY_Controller {
    
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
            $this->load->service('plan/SendYesNumListService', null, 'send_yes_num_list');
            $this->send_yes_num_list->setSearchParams($params);
            //过滤hook
            $this->send_yes_num_list->setPreSearchHook(array($this->send_yes_num_list, 'hook_filter_params'), ['input' => $this->send_yes_num_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->send_yes_num_list->setPreSearchHook(array($this->send_yes_num_list, 'hook_translate_params'), ['input' => &$this->send_yes_num_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->send_yes_num_list->setPreSearchHook(array($this->send_yes_num_list, 'hook_format_params'), ['input' => &$this->send_yes_num_list->search_params, 'update' => 'search_params']);
            $this->data = $this->send_yes_num_list->execSearch();
            $cfg = $this->send_yes_num_list->get_cfg();
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
     * 发运实时调拨
     */
    public function real_time_transfer(){
        try{
            $params = $this->compatible('get');
            $six_code = $params['six_code'];
            if (!isset($six_code)){
                throw new RuntimeException("请输入验证码");
            }
            if ($six_code != "yballot"){
                throw new RuntimeException("验证码错误");
            }

            $data = json_encode(['six-code'=>'yballot']);
            $curl = get_curl();
            $http_result = $curl->http_post(PYTHON_MRP_API.'/sendfba/toallot/execute', $data);
            if ($http_result != "code_ok"){
                throw new RuntimeException("请求调拨失败");
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
     *Notes:查看调拨结果列表
     *User: lewei
     *Date: 2019/11/4
     *Time: 20:14
     */
    public function real_allot_result_list(){
        try{
            $params = $this->compatible('get');
            $this->load->service('plan/RealAllotStatusListService', null, 'real_allot_status_list');
            $this->real_allot_status_list->setSearchParams($params);
            //过滤hook
            $this->real_allot_status_list->setPreSearchHook(array($this->real_allot_status_list, 'hook_filter_params'), ['input' => $this->real_allot_status_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->real_allot_status_list->setPreSearchHook(array($this->real_allot_status_list, 'hook_translate_params'), ['input' => &$this->real_allot_status_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->real_allot_status_list->setPreSearchHook(array($this->real_allot_status_list, 'hook_format_params'), ['input' => &$this->real_allot_status_list->search_params, 'update' => 'search_params']);
            $this->data = $this->real_allot_status_list->execSearch();
            $cfg = $this->real_allot_status_list->get_cfg();
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