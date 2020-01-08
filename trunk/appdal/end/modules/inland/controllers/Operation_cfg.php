<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内销量运算配置
 * Class Operation_cfg
 */
class Operation_cfg extends MY_Controller {

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

            $this->load->service('inland/OperationListService');

            $this->operationlistservice->setSearchParams($params);
            //过滤hook
            $this->operationlistservice->setPreSearchHook(array($this->operationlistservice, 'hook_filter_params'), ['input' => $this->operationlistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->operationlistservice->setPreSearchHook(array($this->operationlistservice, 'hook_translate_params'), ['input' => &$this->operationlistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->operationlistservice->setPreSearchHook(array($this->operationlistservice, 'hook_format_params'), ['input' => &$this->operationlistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->operationlistservice->setAfterSearchHook(array($this->operationlistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->operationlistservice->execSearch();


            $cfg = $this->operationlistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->operationlistservice->get_cfg()['droplist'],
                    $is_override = true
                );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

            //取配置
            $this->load->service('basic/UsercfgProfileService');

            $result = $this->usercfgprofileservice->get_display_cfg('inland_operation_cfg_list');
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
     * 新增功能
     */
    public function add()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/OperationService');
            $result = $this->operationservice->add($params);
            if(isset($result['code']) && $result['code'] == 100 ){
                $code = $result['code'];
                $this->data['errorMess'] = $result['errorMess'];
                return;
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }

    /**
     *Notes: 导入
     *User: lewei
     *Date: 2019/11/7
     *Time: 17:15
     */
    public function import(){
        try{
            $params = $this->compatible('post');
            $this->load->service('inland/OperationService');
            $result = $this->operationservice->import($params);
            if(isset($result['code']) && $result['code'] == 100 ){
                $code = $result['code'];
                $this->data['errorMess'] = $result['errorMess'];
                return;
            }
            $this->data['data_list']['processed'] = $result['processed'];
            $this->data['data_list']['undisposed'] = $result['undisposed'];
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
     * 修改功能
     */
    public function update()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/OperationService');
            $result = $this->operationservice->update($params);
            if(isset($result['code']) && $result['code'] == 100 ){
                $code = $result['code'];
                $this->data['errorMess'] = $result['errorMess'];
                return;
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }

    /**
     * 备注
     */
    public function remark()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/OperationService');
            $count = $this->operationservice->update_remark($params);
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
     * 批量删除功能
     */
    public function batch_delete()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/OperationService');
            $result = $this->operationservice->batch_delete($params);
            $this->data['data_list'] = $result;
            if (!empty($result))
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
     */
    public function detail()
    {
        try
        {
            $params = $this->compatible('get');

                $gid = $params['gid'];
                $this->load->service('inland/OperationService');
                $this->data['data']['cfg'] = $this->operationservice->detail($gid);
                $this->data['data']['remark'] = $this->operationservice->get_operation_remark($gid);
                $this->load->service('inland/OperationLogService');
                $offset = $params['offset'] ?? 1;
                $limit = $params['limit'] ?? 20;
                $this->data['data']['log'] = $this->operationlogservice->get_one_listing_log($gid, $offset, $limit);
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(['inland_platform_code']);
                $this->data['select_list'] = $this->dropdownservice->get();

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

    public function get_platform_info()
    {
        try
        {
            $this->load->service('basic/DropdownService');
            $this->dropdownservice->setDroplist(['inland_platform_code']);
            $this->data['select_list'] = $this->dropdownservice->get();
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
     * 订单导出， 预期支持不同字段的导出
     */
    public function operation_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('inland/InlandOperationExportService');
            $this->inlandoperationexportservice->setTemplate($post);
            $this->data['filepath'] = $this->inlandoperationexportservice->export('csv');
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