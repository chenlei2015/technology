<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内特殊需求列表
 *
 * @author Jason 13292
 * @since 2019-03-02
 */
class Special extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }
    
    public function list()
    {
        try
        {
            $params = $this->compatible('get');
            
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);
            
            $this->load->service('inland/PrSpecialListService');
            
            $this->prspeciallistservice->setSearchParams($params);
            //过滤hook
            $this->prspeciallistservice->setPreSearchHook(array($this->prspeciallistservice, 'hook_filter_params'), ['input' => $this->prspeciallistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->prspeciallistservice->setPreSearchHook(array($this->prspeciallistservice, 'hook_translate_params'), ['input' => &$this->prspeciallistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->prspeciallistservice->setPreSearchHook(array($this->prspeciallistservice, 'hook_format_params'), ['input' => &$this->prspeciallistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->prspeciallistservice->setAfterSearchHook(array($this->prspeciallistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->prspeciallistservice->execSearch();
            
            $cfg = $this->prspeciallistservice->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];
            
            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(
                    $this->prspeciallistservice->get_cfg()['droplist'],
                    $is_override = true
                    );
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }
            
            //取配置
            $this->load->service('basic/UsercfgProfileService');

            $result = $this->usercfgprofileservice->get_display_cfg('inland_special_list');
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
    
    public function remark()
    {
        try
        {
            $params = $this->compatible('post');
            $priv_uid = -1;
            $this->load->service('inland/PrSpecialService');
            $count = $this->prspecialservice->update_remark($params, $priv_uid);
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

    public function detail()
    {
        try
        {
            $params = $this->compatible('get');
            $gid = $params['gid'];
            $this->load->service('inland/PrSpecialService');
            $this->data['data']['pr'] = $this->prspecialservice->detail($gid);
            $this->data['data']['remark'] = $this->prspecialservice->get_pr_remark($gid);
            $this->load->service('inland/PrSpecialLogService');
            $offset = $params['offset'] ?? 1;
            $limit = $params['limit'] ?? 20;
            $this->data['data']['log'] = $this->prspeciallogservice->get_one_listing_log($gid, $offset, $limit);
            
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

    public function export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('inland/InlandSpecialExportService');
            $this->inlandspecialexportservice->setTemplate($post);
            $this->data['filepath'] = $this->inlandspecialexportservice->export('csv');
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

    public function approve()
    {
        try
        {
            $params = $this->input->post();
            $gid = $params['gid'] ?? [];
            $gid = is_string($gid) ? explode(',', $gid) : $gid;
            if (empty($gid))
            {
                throw new \InvalidArgumentException(sprintf('请选择需要审核的记录'), 412);
            }
            $result = $params['result'];
            $this->load->service('inland/PrSpecialService');
            $this->data['data'] = $this->prspecialservice->manual_approve($gid, $result);
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

    public function edit_pr()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('inland/PrSpecialService');
            $this->data['data'] = $this->prspecialservice->edit_pr($params);
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

    public function delete()
    {
        try
        {
            $params = $this->input->post();
            $gids = $params['gid'] ?? [];
            $gids = is_string($gids) ? explode(',', $gids) : $gids;
            if (empty($gids))
            {
                throw new \InvalidArgumentException(sprintf('请选择需要删除的记录'), 412);
            }
            $this->load->service('inland/PrSpecialService');
            $this->data['data'] = $this->prspecialservice->batch_delete($gids);
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
     * 导入
     */
    public function uploadExcel()
    {
        try
        {
            $info = [];
            $data = json_decode($this->input->post('data'), true);
            $count = count($data)??'';

            $this->load->service('inland/PrSpecialService');
            $result = $this->prspecialservice->batch_upload($data,$count);

            $info['total'] = $count;
            $info['processed'] = $result['success']??'';//已处理
            $info['undisposed'] = $result['fail']??'';  //未处理


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
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */