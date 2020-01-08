<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * Class System
 * @desc 系统日志
 * @author zc
 * @since 20191021
 */
class System extends MY_Controller {
    private $_server_module = 'log/System/';
    private $_select_day = '15';

    public function __construct()
    {
        parent::__construct();
    }

    public function info()
    {
        $api_name = $this->_server_module . strtolower(__FUNCTION__);
        $get = $this->input->get();
        $this->load->model('System_model','system');
        $type = $get['type']??'FONT';
        $log_date = $get['log_date']??date('Y-m-d');
        $level = $get['level']??'';
        $offset = $get['offset']??1;
        $limit = $get['limit']??20;
        $data = ($type == 'FONT') ? $this->system->getLogInfoDesc($log_date,$level,$offset,$limit) :
                                    $this->_curl_request->cloud_get($api_name, $get);
        $this->lang->load(['log_lang']);
        $column_keys = array(
            $this->lang->myline('no'),                         //序号
            $this->lang->myline('desc'),                         //日志描述
            $this->lang->myline('type'),             //日志类型:前后端
            //$this->lang->myline('level'),                //日志级别
            //$this->lang->myline('log_time'),                   //日志时间
        );
        $last_day = get_last_day('','Y-m-d',$this->_select_day);
        $drop_down_box = [
            'date' => $last_day,
            'type' =>  $this->lang->myline('select_type'),
            'level' =>  $this->lang->myline('select_level')
        ];
        $this->data['status'] = 1;
        $this->data['data_list'] = array(
            'drop_down_box'=>$drop_down_box,
            'key' => $column_keys,
            'value' => $data['list']??[],
        );
        $this->data['page_data'] = array(
            'offset' => (int)$offset,
            'limit' => (int)$limit,
            'total' => $data['total']??0
        );
        http_response($this->data);
    }
}