<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/25
 * Time: 16:20
 */

if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内备货关系配置表
 * Class Operation_cfg
 */
class Stock_relationship_cfg extends MY_Controller {

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

            $this->load->service('inland/StockCfgListService');

            $this->stockcfglistservice->setSearchParams($params);
            //过滤hook
            $this->stockcfglistservice->setPreSearchHook(array($this->stockcfglistservice, 'hook_filter_params'), ['input' => $this->stockcfglistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->stockcfglistservice->setPreSearchHook(array($this->stockcfglistservice, 'hook_translate_params'), ['input' => &$this->stockcfglistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->stockcfglistservice->setPreSearchHook(array($this->stockcfglistservice, 'hook_format_params'), ['input' => &$this->stockcfglistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->stockcfglistservice->setAfterSearchHook(array($this->stockcfglistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->stockcfglistservice->execSearch();


            $cfg = $this->stockcfglistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->stockcfglistservice->get_cfg()['droplist'],
                    $is_override = true
                );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

            //取配置
            $this->load->service('basic/UsercfgProfileService');

            $result = $this->usercfgprofileservice->get_display_cfg('inland_stock_cfg_list');
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
            $this->load->service('inland/StockCfgService');
            $result = $this->StockCfgService->add($params);
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
     * 修改功能
     */
    public function update()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/StockCfgService');

            $result = $this->stockcfgservice->update($params);
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
            $this->load->service('inland/StockCfgService');
            $count = $this->stockcfgservice->update_remark($params);
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
     * 批量审核成功
     */
    public function batchCheckSuccess()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/StockCfgService');
            $result = $this->stockcfgservice->batch_check_success($params);
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
     * 批量审核失败
     */
    public function batchCheckFail()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/StockCfgService');
            $result = $this->stockcfgservice->batch_check_fail($params);
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
     * 批量删除功能
     */
    public function batch_delete()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/StockCfgService');
            $result = $this->StockCfgService->batch_delete($params);
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
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $gid = $params['gid'];
            $this->load->service('inland/StockCfgService');
            $this->data['data']['cfg'] = $this->stockcfgservice->detail($gid);
            $this->data['data']['remark'] = $this->stockcfgservice->get_operation_remark($gid);
            $this->load->service('inland/StockCfgLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->stockcfglogservice->get_one_listing_log($gid, $offset, $limit);


            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(['rule_type','inland_stock_up']);
                $this->data['data']['drop_down_box'] = $this->dropdownservice->get();
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
    public function stock_cfg_export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('inland/InlandStockCfgExportService');
            $this->inlandstockcfgexportservice->setTemplate($post);
            $this->data['filepath'] = $this->inlandstockcfgexportservice->export('csv');

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
     * 导入
     */
    public function uploadExcel()
    {
        try
        {
            $info = [];
            $data = json_decode($this->input->post('data_values'), true);

            $processed = 0;//已处理
            $undisposed = 0;//未处理
            $this->load->service('inland/StockCfgService');
            foreach ($data as $key => $value){
                if(in_array('',$value)){//如果数组中有空值
                    $undisposed++;
                    continue;
                }
                $result = $this->stockcfgservice->batch_update($value);
                if ($result) {
                    $processed++;
                } else {
                    $undisposed++;
                }
            }

            $info['processed'] = $processed;//已处理
            $info['undisposed'] = $undisposed;  //未处理

            $this->data['status'] = 1;
            $this->data['data_list'] = $info;
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