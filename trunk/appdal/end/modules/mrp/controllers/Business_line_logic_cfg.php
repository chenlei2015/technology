<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/5/20
 * Time: 16:14
 */
class Business_line_logic_cfg extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }
    public function list()
    {
        try
        {
            //接收get参数
            $get = $this->compatible('get');
            //获取配置信息
            $this->load->service('mrp/BusinessLineCfgService');
            $this->data['data_list']['value'] = $this->businesslinecfgservice->get_cfg_list($get['business_line']??1);
            //下拉列表
            $this->load->service('basic/DropdownService');
            $this->dropdownservice->setDroplist(
                ['mrp_business_line'],
                $is_override = true
            );
            $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            $this->data['data_list']['business_line'] = $get['business_line'];
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

    public function modify_cfg()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('mrp/BusinessLineCfgService');
            $this->businesslinecfgservice->edit_cfg($post);
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
