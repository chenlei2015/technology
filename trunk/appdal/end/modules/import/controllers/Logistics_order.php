<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/10
 * Time: 11:52
 */
class Logistics_order extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('Upload_file_information_model', 'm_file');
        get_active_user();
    }

    public function list()
    {
        $offset = $this->input->post_get('offset');
        $limit = $this->input->post_get('limit');
        $this->lang->load('import_lang');

        $params = [
            'offset' => $offset ? $offset : 1,
            'limit' => $limit ? $limit : 20,
        ];

        $result = $this->m_file->all($params);
        $column_keys = $this->lang->myline('upload_file_information');
        $this->data['status'] = 1;
        $this->data['data_list'] = array(
            'key' => $column_keys,
            'value' => $result['data_list']??[],
        );
        $this->data['page_data'] = array(
            'offset' => (int)$result['data_page']['offset'],
            'limit' => (int)$result['data_page']['limit'],
            'total' => $result['data_page']['total'],
        );

        http_response($this->data);
    }

    public function uploadExcel()
    {
        try {
            $active_user = get_active_user();
            $this->load->model('Logistics_order_model', 'm_Logistics');
            $data = json_decode($this->input->post_get('data'), true);
            $file_name = $this->input->post_get('file_name');
            $count = count($data);
            $result = $this->m_Logistics->insert($data);
            $info_data = [
                'created_at' => date('Y-m-d H:i:s'),
                'uploader_staff_code' => $active_user->staff_code,
                'uploader_name' => $active_user->user_name,
                'file_name' => $file_name
            ];
            $this->m_file->insert($info_data);
            $this->data['status'] = 1;
            $this->data['errorMess'] = '文件总数据: ' . $count . ',' . '导入数据:' . $result;
            $code = 200;
        } catch (\InvalidArgumentException $e) {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }
        

    }
}