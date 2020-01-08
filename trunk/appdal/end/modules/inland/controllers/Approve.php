<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内自动汇总和查询
 *
 * @author Jason 13292
 * @since 2019-04-11
 */
class Approve extends MY_Controller {
    
    //protected $data = ['status' => 0];
    
    public function __construct()
    {
        parent::__construct();
    }
    
    public function expired()
    {
        try
        {
            $params = $this->compatible('get');
            if (!isset($params['date']))
            {
                $params['date'] = '*';
            }
            $this->load->service('inland/PrService');
            $this->data['data'] = $this->prservice->expired($params);
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
    
    public function auto()
    {
        try
        {
            $params = $this->compatible('get');
            $this->load->service('inland/PrService');
            $this->data['data'] = $this->prservice->auto_approve();
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
    
    public function status()
    {
        try
        {
            $params = $this->input->get();
            $params['date'] = isset($params['date']) ? $params['date'] : date('Y-m-d');
            $this->load->service('inland/PrService');
            $this->data['data'] = $this->prservice->auto_approve_check($params);
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
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */