<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * FBA毛需求
 *
 * @author zc
 * @since 2019-10-24
 */
class Demand extends MY_Controller {

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

            $this->load->service('shipment/DemandListService', null, 'demand_list');
            $this->demand_list->setSearchParams($params);
            //过滤hook
            $this->demand_list->setPreSearchHook(array($this->demand_list, 'hook_filter_params'), ['input' => $this->demand_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->demand_list->setPreSearchHook(array($this->demand_list, 'hook_translate_params'), ['input' => &$this->demand_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->demand_list->setPreSearchHook(array($this->demand_list, 'hook_format_params'), ['input' => &$this->demand_list->search_params, 'update' => 'search_params']);
            $this->data = $this->demand_list->execSearch();
            $cfg = $this->demand_list->get_cfg();

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
            http_response($this->data);
        }

    }
}