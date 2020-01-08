<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 用户级别设置
 *
 * @author Jason
 * @since 2019-01-10 14：17
 */
class Usercfg extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();
    }
    
    /**
     * 用户指派
     */
    public function assign()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('basic/UsercfgService');
            foreach ($params as $key => $val)
            {
                if (!is_array($val))
                {
                    unset($params[$key]);
                }
            }
            $this->data['data'] = $this->usercfgservice->assign($params);
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
     * 列表
     */
    public function list()
    {
        try
        {
            $params = $this->compatible('get');
            
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);
            
            $this->load->service('basic/UsercfgListService');
            
            $this->usercfglistservice->setSearchParams($params);
            //过滤hook
            $this->usercfglistservice->setPreSearchHook(array($this->usercfglistservice, 'hook_filter_params'), ['input' => $this->usercfglistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->usercfglistservice->setPreSearchHook(array($this->usercfglistservice, 'hook_translate_params'), ['input' => &$this->usercfglistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->usercfglistservice->setPreSearchHook(array($this->usercfglistservice, 'hook_format_params'), ['input' => &$this->usercfglistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->usercfglistservice->setAfterSearchHook(array($this->usercfglistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->usercfglistservice->execSearch();
            
            $cfg = $this->usercfglistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
            
            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->usercfglistservice->get_cfg()['droplist'],
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

    /**
     * 备注
     */
    public function remark()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('basic/UsercfgService');
            $count = $this->usercfgservice->update_remark($params);
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
     * 详情
     */
    public function detail()
    {
        try
        {
            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('basic/UsercfgService');
            $this->data['data_list']['value'] = $this->usercfgservice->detail($gid);
            $this->data['data_list']['remark'] = $this->usercfgservice->get_remark($gid);
            
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data_list']['log'] = $this->usercfgservice->get_log($gid, $offset, $limit);
            
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
/* End of file Preparetype.php */
/* Location: ./application/modules/basic/controllers/Preparetype.php */