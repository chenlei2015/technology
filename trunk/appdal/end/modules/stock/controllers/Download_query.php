<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 下载查询
 *
 * @author Jason 13292
 * @since 2019-04-11
 */
class Download_query extends MX_Controller {
    
    protected $data = ['status' => 0];

    public function __construct()
    {
        parent::__construct();
    }
    
    public function add()
    {
        try
        {
            $params = $this->input->get();
            $require_cols = array_flip(['url', 'bussiness_line']);
            if (count(array_diff_key($require_cols, $params)) > 0 )
            {
                throw new \InvalidArgumentException(sprintf('字段:%s必须设置', implode(',', $require_cols)), 412);
            }
            $this->load->service('stock/MrpDownloadService');
            $this->data['data'] = $this->mrpdownloadservice->add_bussiness_download($params);
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
    
    public function exists()
    {
        try
        {
            $params = $this->input->get();
            $date = $params['date'];
            unset($params['date']);
            $params['state'] = GLOBAL_YES;
            $this->load->service('stock/MrpDownloadService');
            $this->data['data'] = count($this->mrpdownloadservice->query($date, $params));
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