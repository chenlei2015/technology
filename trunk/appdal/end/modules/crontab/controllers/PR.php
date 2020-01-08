<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * PR
 *
 * @author Bigfong
 * @since 2019-03-24 16:35
 */
class PR extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
    }


    /**
     * 更新fba需求计划过期状态
     * http://192.168.71.170:1084/crontab/pr/fba_update_expired
     */
    public function fba_update_expired()
    {
        try
        {
            //直接操作Model，不走service了
            $this->load->model('Fba_pr_list_model', 'pr_list', false, 'fba');
            $this->pr_list->handel_expired();

        }
        catch (\InvalidArgumentException $e)
        {
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $errorMsg = $e->getMessage();
        }
        finally
        {
           !empty($errorMsg) && logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
        }
        exit;
    }

    /**
     * 更新海外仓需求计划过期状态
     * http://192.168.71.170:1084/crontab/pr/oversea_update_expired
     */
    public function oversea_update_expired()
    {
        try
        {
            //直接操作Model，不走service了
            $this->load->model('Oversea_pr_list_model', 'pr_list', false, 'oversea');
            $this->pr_list->handel_expired();
        }
        catch (\InvalidArgumentException $e)
        {
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $errorMsg = $e->getMessage();
        }
        finally
        {
            !empty($errorMsg) && logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
        }
        exit;
    }

    /**
     * 设置促销sku过期
     */
    public function promotion_sku_expired()
    {
        try
        {
            $params = $this->compatible('get');
            $this->load->service('fba/PrPromotionService');
            $this->data['data'] = $this->prpromotionservice->expired($params);
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
        exit;
    }

    public function general_diff_seller_sku()
    {
        try
        {
            $this->load->service('fba/PrService');
            $this->data['data']['general_diff_seller_sku'] = $this->prservice->general_diff_seller_sku();
            $this->data['data']['sync_first_fba_inactive'] = $this->prservice->sync_first_fba_inactive();
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

    public function sync_first_fba_inactive()
    {
        try
        {
            $this->load->service('fba/PrService');
            $this->data['data'] = $this->prservice->sync_first_fba_inactive();
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
