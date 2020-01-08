<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 不可审核类目配置表
 *
 * @version 1.2.2
 * @since 2019-09-10
 */
class Category_cfg extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }

    public function list()
    {
        try {
            $params = $this->compatible('get');
            $this->load->model('Category_cfg_model', 'm_cate');
            $result                           = $this->m_cate->list();
            $this->data['data_list']['value'] = $result;
            $this->data['status']             = 1;
            $code                             = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    public function get_category()
    {
        try {
            $params = $this->compatible('get');
            $this->load->model('Category_cfg_model', 'm_cate');
            $result                                  = $this->m_cate->get_all_category();
            $this->data['data_list']['all_category'] = $result;
            $this->data['status']                    = 1;
            $code                                    = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    public function add_category()
    {
        try {
            $params = $this->compatible('post');
            $this->load->service('fba/FbaCategoryCfgService');
            $result = $this->fbacategorycfgservice->add_category($params);
            if ($result) {
                $this->data['status'] = 1;
                $code                 = 200;
            }
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    public function del_cfg()
    {
        try {
            $params = $this->compatible('post');
            $this->load->service('fba/FbaCategoryCfgService');
            $result = $this->fbacategorycfgservice->del_one($params['id']);
            if (!empty($result)) {
                $this->data['status'] = 1;
                $code                 = 200;
            }
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }
}
