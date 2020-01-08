<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内全局规则配置
 * @author W02278
 * @name Sales_report Class
 */
class Sales_report extends MY_Controller {
    
    public $data = ['status' => 0];
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Inland_sales_report_model', 'salesReportModel', false, 'inland');
        get_active_user();
    }


    /**
     * 销量列表
     * @author W02278
     * @link http://192.168.31.160:83/index.php/inland/Sales_report/getReportList
     * CreateTime: 2019/4/29 16:11
     */
    public function getReportList()
    {
        try {
            $params = $this->input->get();
            $offset = $this->input->post_get('offset');
            $limit = $this->input->post_get('limit');
            $offset = $offset ? $offset : 1;
            $limit = $limit ? $limit : 20;

            $this->load->service('basic/DropdownService');
            $this->dropdownservice->setDroplist(['inland_sku_all_state']);
            $skuStates = $this->dropdownservice->get();
            $this->load->service('inland/InlandSalesReportService');

            //列表数据
            $result = $this->inlandsalesreportservice->getReportList($params ,$offset , $limit);
            if ($result['status'] == 1) {
                //转中文
                $valueData = $this->_getView($result['data_list']);
                $this->data['status'] = 1;
                $this->data['data'] = 1;
                $this->data['data_list']['value'] = [
                    'list' => [
                        'key' => $result['data_list']['key'],
                        'value' => $result['data_list']['value'],
                    ],
                    'sku_states' => [
                        'sku_states' => current($skuStates),
                    ]
                ];
                $this->data['data'] = $this->data['data_list'];
                $this->data['page_data'] = array(
                    'offset' => (int)$result['data_page']['offset'],
                    'limit' => (int)$result['data_page']['limit'],
                    'total' => $result['data_page']['total'],
                );
            } else {
                $this->data['status'] = 1;
                $this->data['data'] = 1;
                $this->data['data_list'] = $result['data_list'];
                $this->data['page_data'] = array(
                    'offset' => (int)$offset,
                    'limit' => (int)$limit,
                    'total' => $result['data_page']['total']
                );
            }
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
     * 转中文
     */
    public function _getView($valueData)
    {
        foreach ($valueData as $key => &$value) {
            data_format_filter($value,['created_at','updated_at']);
        }
        return $valueData;
    }

    public function export1()
    {
//        $this->salesReportModel->export();
        $post = $this->input->post();
        $this->load->service('inland/InlandSalesReportExportService');
        $this->inlandsalesreportexportservice->setTemplate([]);
        $this->data = $this->inlandsalesreportexportservice->export('csv');
        $this->data['status'] = 1;
        $code = 200;
//        $this->inlandsalesreportexportservice->export();


    }

    /**
     * 订单导出， 预期支持不同字段的导出
     *
     */
    public function export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('inland/InlandSalesReportExportService');
            $this->inlandsalesreportexportservice->setTemplate($post);
            $gid = [];
            if (isset($post['gid'])) {
                $gid = $post['gid'];
            }
            $this->data['filepath'] = $this->inlandsalesreportexportservice->export('csv' , $gid);
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

    public function test()
    {
        $this->load->helper(array('form', 'url'));

        $this->load->library('form_validation');

        $this->form_validation->set_rules('username', 'Username', 'required');
        $this->form_validation->set_rules('password', 'Password', 'required',
            array('required' => 'You must provide a %s.')
        );
        $this->form_validation->set_rules('passconf', 'Password Confirmation', 'required');
        $this->form_validation->set_rules('email', 'Email', 'required');

        if ($this->form_validation->run() == FALSE)
        {
            echo '<pre>';
            var_dump($this->form_validation);die;
            $this->load->view('myform');
        }
        else
        {
            $this->load->view('formsuccess');
        }
    }




}
