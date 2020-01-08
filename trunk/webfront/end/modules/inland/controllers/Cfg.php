<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/10/11
 * Time: 14:19
 */
class Cfg Extends MY_Controller
{
    private $_server_module = 'inland/Cfg/';

    public function __construct()
    {
        parent::__construct();
        $this->load->helper('inland_helper');
    }

    /**
     * 列表
     */
    public function list()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get      = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        //pr($result);
        if ($result['status'] == 1 && !empty($result['data_list']['value']))
        {
            inland_cfg_list_result($result['data_list']['value']);
        }
        //pr($result);exit;
        http_response($this->rsp_package($result));
    }

    /**
     *Notes:   编辑国内毛需求生成策略
     *User: lewei
     *Date: 2019/12/4
     *Time: 18:55
     */
    public function edit(){
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $post      = $this->input->post();
        $result = $this->_curl_request->cloud_post($api_name, $post);
        http_response($this->rsp_package($result));
    }

    /**
     * 导入
     */
    public function upload()
    {
        $api_name = $this->_server_module . strtolower('templateDownload');
        $result = $this->_curl_request->cloud_get($api_name);

        if (isset($result['file_path'])) {
            $file_path = $result['file_path'];
        }else{
            http_response(['status'=>0,'errorMess'=>'返回的文件路径异常']);
        }
        // 允许上传的图片后缀
        $allowedExts = array("php");
        $temp = explode(".", $_FILES["file"]["name"]);
        $extension = end($temp);     // 获取文件后缀名
        if (!in_array($extension, $allowedExts))
        {
            http_response(['status'=>0,'errorMess'=>'非法的文件格式']);
        }

        if ($_FILES["file"]["error"] > 0)
        {
            http_response(['status'=>0,'errorMess' => $_FILES["file"]["error"]]);
        }
        else
        {
            if (move_uploaded_file($_FILES["file"]["tmp_name"], $file_path)){
                http_response(['status'=>1,'errorMess' => '上传成功']);
            }
        }
    }

    /**
     * 导出文件
     */
    public function templateDownload()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $result = $this->_curl_request->cloud_get($api_name);
        if (isset($result['file_path'])) {
            $file_path = $result['file_path'];
        }else{
            http_response(['status'=>0,'errorMess'=>'返回的文件路径异常']);
        }
        $filename  = 'inland_cfg.php';
        if (!file_exists($file_path)) {
            echo "文件找不到";
            exit ();
        } else {
            header("Content-Type: application/force-download");
            header("Content-Disposition: attachment; filename=" . $filename);
            ob_clean();
            flush();
            readfile($file_path);
        }
    }

    /**
     * 查询毛需求策略原始配置数据
     */
    public function get_cfg_data()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get      = $this->input->get();
        $result = $this->_curl_request->cloud_get($api_name, $get);
        http_response($this->rsp_package($result));
    }
}