<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内库存报表
 * Class Operation_cfg
 */
class Report_inventory extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
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

            $this->load->service('inland/InventoryListService');

            $this->inventorylistservice->setSearchParams($params);
            //过滤hook
            $this->inventorylistservice->setPreSearchHook(array($this->inventorylistservice, 'hook_filter_params'), ['input' => $this->inventorylistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->inventorylistservice->setPreSearchHook(array($this->inventorylistservice, 'hook_translate_params'), ['input' => &$this->inventorylistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->inventorylistservice->setPreSearchHook(array($this->inventorylistservice, 'hook_format_params'), ['input' => &$this->inventorylistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->inventorylistservice->setAfterSearchHook(array($this->inventorylistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->inventorylistservice->execSearch();


            $cfg = $this->inventorylistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->inventorylistservice->get_cfg()['droplist'],
                    $is_override = true
                );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

            //取配置
            $this->load->service('basic/UsercfgProfileService');

            $result = $this->usercfgprofileservice->get_display_cfg('inland_inventory_report_list');
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
     * 订单导出， 预期支持不同字段的导出
     */
    public function inventory_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('inland/InlandInventoryExportService');
            $this->inlandinventoryexportservice->setTemplate($post);
            $this->data['filepath'] = $this->inlandinventoryexportservice->export('csv');
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