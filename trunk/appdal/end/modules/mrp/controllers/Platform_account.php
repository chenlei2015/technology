<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/18
 * Time: 18:52
 */

class Platform_account extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }

    /**
     * 平台账号列表
     */
    public function account_list()
    {
        try
        {
            $params = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $this->load->service('mrp/PlatformAccountListService', null, 'account_list');

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
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

}
