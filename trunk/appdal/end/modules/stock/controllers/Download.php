<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 下载列表
 *
 * @author Jason 13292
 * @since 2019-04-11
 */
class Download extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }
    
    private function search($params, $is_get_base)
    {
        try
        {
            $this->load->service('stock/MrpDownloadListService');
            
            $this->mrpdownloadlistservice->setSearchParams($params);
            //过滤hook
            $this->mrpdownloadlistservice->setPreSearchHook(array($this->mrpdownloadlistservice, 'hook_filter_params'), ['input' => $this->mrpdownloadlistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->mrpdownloadlistservice->setPreSearchHook(array($this->mrpdownloadlistservice, 'hook_translate_params'), ['input' => &$this->mrpdownloadlistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->mrpdownloadlistservice->setPreSearchHook(array($this->mrpdownloadlistservice, 'hook_format_params'), ['input' => &$this->mrpdownloadlistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->mrpdownloadlistservice->setAfterSearchHook(array($this->mrpdownloadlistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->mrpdownloadlistservice->execSearch();
            
            $cfg = $this->mrpdownloadlistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
            
            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->mrpdownloadlistservice->get_cfg()['droplist'],
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
    
    public function fba()
    {
        $params = $this->compatible('get');
        $is_get_base = intval($params['is_get_base'] ?? 0);
        unset($params['is_get_base']);
        $params['line'] = BUSSINESS_FBA;
        //$params['data_type'] = MRP_DOWNLOAD_TYPE_ALL;
        $params['state'] = GLOBAL_YES;
        $this->search($params, $is_get_base);
    }

    public function oversea()
    {
        $params = $this->compatible('get');
        $is_get_base = intval($params['is_get_base'] ?? 0);
        unset($params['is_get_base']);
        $params['line'] = BUSSINESS_OVERSEA;
        //$params['data_type'] = MRP_DOWNLOAD_TYPE_ALL;
        $params['state'] = GLOBAL_YES;
        $this->search($params, $is_get_base);
    }

    public function inland()
    {
        $params = $this->compatible('get');
        $is_get_base = intval($params['is_get_base'] ?? 0);
        unset($params['is_get_base']);
        $params['line'] = BUSSINESS_IN;
        //$params['data_type'] = MRP_DOWNLOAD_TYPE_ALL;
        $params['state'] = GLOBAL_YES;
        $this->search($params, $is_get_base);
    }
}
/* End of file Download.php */
/* Location: ./application/modules/stock/controllers/Download.php */