<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Dropdown
 *
 * @author Jason
 * @since 2019-01-10 14：17
 */
class Dropdown extends MY_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->service('basic/DropdownService');
    }
    
    /**
     * 级联下拉选择更新
     */
    public function get_dync_fba_accounts()
    {
        try
        {
            $params = $this->compatible('get');
            $gid = $params['group_id'];
            $this->load->service('basic/DyncOptionService');
            $this->data['data'] = $this->dyncoptionservice->get_dync_fba_accounts($gid);
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
     * 根据name获取下拉列表
     */
    public function get()
    {
        $get = $this->compatible('get');
        try
        {
            $collections = is_array($get['name'])  ? $get['name'] : explode(',', $get['name']);
            $collections = array_filter($collections);
            if ($diff = array_diff($collections, $this->dropdownservice->get_names()))
            {
                $code = 412;
                $this->data['errorMess'] = '下拉列表名字'.implode(',', $diff).'错误';
                http_response($this->data);
                return;
            }
            $this->dropdownservice->setDroplist($collections);
            $this->data['data'] = $this->dropdownservice->get();
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
            //$this->data['errorCode'] = $code;
            http_response($this->data, $code);
        }
    }

    /**
     * 根据用户姓名搜索
     */
    public function get_dync_oa_user()
    {
        try
        {
            $params = $this->compatible('get');
            $user_name = $params['user_name'] ?? '';
            $this->load->service('basic/DyncOptionService');
            $this->data['data'] = $this->dyncoptionservice->get_dync_oa_user($user_name);
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
     * 根据管理员姓名搜索管理员列表
     */
    public function get_dync_manager_list()
    {
        try
        {
            $params = $this->compatible('get');
            $user_name = $params['user_name'] ?? '';
            $this->load->service('basic/DyncOptionService');
            $this->data['data'] = $this->dyncoptionservice->get_dync_manager_list($user_name);
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
     * 根据管理员姓名搜索海外仓管理员列表
     */
    public function get_dync_oversea_manager_list()
    {
        try
        {
            $params = $this->compatible('get');
            $user_name = $params['user_name'] ?? '';
            $this->load->service('basic/DyncOptionService');
            $this->data['data'] = $this->dyncoptionservice->get_dync_oversea_manager_list($user_name);
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
     * 根据管理员姓名搜索海外发运计划管理员列表
     */
    public function get_dync_shipment_oversea_manager_list()
    {
        try
        {
            $params = $this->compatible('get');
            $user_name = $params['user_name'] ?? '';
            $this->load->service('basic/DyncOptionService');
            $this->data['data'] = $this->dyncoptionservice->get_dync_shipment_oversea_manager_list($user_name);
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