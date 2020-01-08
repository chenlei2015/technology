<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Usercfg 用户配置
 * 
 * @author Jason
 * @since 2019-03-09
 */
class Usercfg extends MY_Controller {
    
    private $_server_module = 'basic/Usercfg/';
    
    public function __construct()
    {
        parent::__construct();
        $this->lang->load(['user_lang']);
    }
    
    /**
     * 分配用户角色
     */
    public function assign()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $form_post = $this->input->post();
        if (!isset($form_post['data']))
        {
            http_response(['errorMess' => '无效的请求参数']);
        }
        $post = $form_post['data'];
        $required_cols = ['staff_code', 'bussiness_line', 'data_privilege', 'check_privilege'];
        $staff_code = '';
        $index = 0;
        foreach ($post as $key => $row)
        {
            $diff = array_diff($required_cols, array_keys($row));
            if (count($diff) > 0)
            {
                http_response(['errorMess' => sprintf('第%d条数据缺少必要字段：%s', $key, implode(',', $diff))]);
            }
            $index == 0 && $staff_code = $row['staff_code'];
            if ($staff_code != $row['staff_code']) {
                http_response(['errorMess' => '员工工号必须相同']);
            }
            if (!isset(BUSSINESS_LINE[$row['bussiness_line']]))
            {
                http_response(['errorMess' => '无效的业务线']);
            }
            $check_prv_ids = explode(',', $row['check_privilege']);
            foreach ($check_prv_ids as $id)
            {
                if (!isset(USER_ROLE[$id]) && $id != '')
                {
                    http_response(['errorMess' => '无效的审核权限']);
                }
            }
            $index++;
        }
        $result = $this->_curl_request->cloud_post($api_name, $post);
        if (!isset($result['data'][$staff_code]))
        {
            http_response(['errorMess' => '本次操作没有任何修改']);
        }
        http_response($this->rsp_package($result));
    }
    
    /**
     * 列表
     */
    public function list()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name, $this->input->get());
        http_response($this->rsp_package($result));
    }
   
    /**
     * 添加一条备注
     */
    public function remark()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $post = $this->input->post();
        if (!isset($post['gid']) || !$post['gid'])
        {
            $this->data['errorMess'] = '该用户还未设置任何权限，需要先设置权限，然后设置备注';
            http_response($this->data);
        }
        if (!isset($post['remark']) || !$post['remark'] || trim($post['remark']) == '')
        {
            $this->data['errorMess'] = '备注必须填写且不能为空';
            http_response($this->data);
        }
        $post['remark'] = mb_substr((strip_tags($post['remark'])), 0, 200);
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 详情
     */
    public function detail()
    {
        $api_name = $this->_server_module.strtolower(__FUNCTION__);
        $get = $this->input->get();
        if (!isset($get['gid']) || !$get['gid'])
        {
            $this->data['errorMess'] = '记录主键id必须填写';
            http_response($this->data);
        }
        $result = $this->_curl_request->cloud_get($api_name, $get);
        $this->load->helper('usercfg_helper');
        tran_detail_result($result['data_list']['value']);
        http_response($this->rsp_package($result));
    }

}
/* End of file Dropdown.php */
/* Location: ./application/modules/basic/controllers/Dropdown.php */