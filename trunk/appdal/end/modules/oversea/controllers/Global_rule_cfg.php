<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/1
 * Time: 17:14
 */
class Global_rule_cfg extends MY_Controller
{
    const IS_EXPORT = 1;

    public function __construct()
    {
        parent::__construct();
        get_active_user();
        $this->_manager_cfg = $this->get_owner_station();
    }
    
    /**
     * 获取一个账号的权限
     *
     * @return array ['au', 'east']
     */
    protected function get_owner_station()
    {
        $active_user = get_active_user();
        if ($active_user->has_all_data_privileges(BUSSINESS_OVERSEA))
        {
            return ['*' => '*'];
        }
        //获取
        $account_cfg = $active_user->get_my_stations();
        return $account_cfg;
    }

    /**
     * 全局规则配置列表
     * http://192.168.71.170:1084/oversea/Global_rule_cfg/globalRuleList
     */
    public function globalRuleList()
    {
        if (empty($this->_manager_cfg))
        {
            $this->data['errorMess'] = '您没有权限';
            return http_response($this->data);
        }
        elseif (!isset($this->_manager_cfg['*']))
        {
            $params['owner_station'] = $this->_manager_cfg;
        }

        $this->load->model('Oversea_global_rule_cfg_model', "m_global");
        $this->lang->load('common');
        $offset = $this->input->post_get('offset');
        $limit = $this->input->post_get('limit');
        $offset = $offset ? $offset : 1;
        $limit = $limit ? $limit : 20;

        $column_keys = array(
            $this->lang->myline('item'),                        //序号
            $this->lang->myline('oversea_station'),             //海外仓站点

            $this->lang->myline('ls_air'),                      //物流时效LS_空运
            $this->lang->myline('ls_shipping_bulk'),            //物流时效LS_海运散货
            $this->lang->myline('ls_shipping_full'),            //物流时效LS_海运整柜
            $this->lang->myline('ls_trains_bulk'),              //物流时效LS_铁运散货
            $this->lang->myline('ls_trains_full'),              //物流时效LS_铁运整柜
            $this->lang->myline('ls_land'),                     //物流时效LS_陆运
            $this->lang->myline('ls_blue'),                     //物流时效LS_蓝单
            $this->lang->myline('ls_red'),                      //物流时效LS_红单
            $this->lang->myline('pt_air'),                      //打包时效PT_空运
            $this->lang->myline('pt_shipping_bulk'),            //打包时效PT_海运散货
            $this->lang->myline('pt_shipping_full'),            //打包时效PT_海运整柜
            $this->lang->myline('pt_trains_bulk'),              //打包时效PT_铁运散货
            $this->lang->myline('pt_trains_full'),              //打包时效PT_铁运整柜
            $this->lang->myline('pt_land'),                     //打包时效PT_陆运
            $this->lang->myline('pt_blue'),                     //打包时效PT_蓝单
            $this->lang->myline('pt_red'),                      //打包时效PT_红单

//            $this->lang->myline('ls_shipping_sea'),                      //物流时效--海运
//            $this->lang->myline('ls_shipping_land'),                      //物流时效--陆运
//            $this->lang->myline('ls_shipping_air'),                      //物流时效--空运
//            $this->lang->myline('ls_shipping_rail'),                      //物流时效--铁运
//            $this->lang->myline('pt_shipping_sea'),                      //打包时效--海运
//            $this->lang->myline('pt_shipping_land'),                      //打包时效--陆运
//            $this->lang->myline('pt_shipping_air'),                      //打包时效--空运
//            $this->lang->myline('pt_shipping_rail'),                      //打包时效--铁运

            $this->lang->myline('as_up'),                       //上架时效(AS)
            $this->lang->myline('bs'),                          //缓冲库存(BS)
            $this->lang->myline('sc'),                          //备货周期(SC)
            $this->lang->myline('sp'),                          //备货处理周期(SP)
            $this->lang->myline('sz'),                          //服务对应"Z"值
            $this->lang->myline('modify'),                      //修改信息
            $this->lang->myline('remark'),                      //备注
            $this->lang->myline('operation'),                   //操作
        );

        $params = [
            'offset' => $offset,
            'limit' => $limit
        ];

        //列表数据
        $result = $this->m_global->globalRuleList($params);

        if(isset($result) && count($result['data_list'])>0){
            //转中文
            $valueData = $this->_getView($result['data_list']);
            $this->data['status'] = 1;
            $this->data['data_list']['value'] = array(
                'key'=>$column_keys,
                'value'=>$valueData,
            );
            $this->data['page_data'] = array(
                'offset'=>(int)$result['data_page']['offset'],
                'limit'=>(int)$result['data_page']['limit'],
                'total'=>$result['data_page']['total'],
            );
        }else{
            $this->data['status'] =1;
            $this->data['data_list']['value'] =array(
                'key'=>$column_keys,
                'value'=>array(),
            );
            $this->data['page_data'] = array(
                'offset'=>(int)$offset,
                'limit'=>(int)$limit,
                'total'=>0
            );
        }
        http_response($this->data);
    }

    /**
     * 转中文
     */
    public function _getView($valueData)
    {
        foreach ($valueData as $key => &$value) {
            if(isset($value['station_code'])){
                $valueData[$key]['station_code_cn'] = $this->syncStation($value['station_code']);
                data_format_filter($value,['created_at','updated_at']);
            }
        }
        return $valueData;
    }

    /**
     * 转换站点
     * @param $value
     * @return mixed
     */
    public function syncStation($value)
    {
        $data = OVERSEA_STATION_CODE;
        return isset($data[$value]['name']) ? $data[$value]['name'] : $value;
    }


    /**
     * 全局规则配置预览
     * http://192.168.71.170:1084/oversea/Global_rule_cfg/getRuleDetails
     */
    public function getRuleDetails(){
        $this->load->model('Oversea_global_rule_cfg_model','m_global');
        $this->load->model('Oversea_remark_model','m_remark');
        $station_code = $this->input->post_get('station_code') ?? '';
        $result = $this->m_global->getRuleDetails($station_code);
        $remark = $this->m_remark->getRemarkList($station_code);
        $result = $this->_getView($result);
        $this->data['status'] = 1;
        $this->data['data_list']['value'] = $result;
        $this->data['data_list']['remark'] = $remark;
        http_response($this->data);
    }


    /**
     * 全局规则配置修改
     * http://192.168.71.170:1084/oversea/Global_rule_cfg/modifyRule
     */
    public function modifyRule(){
        $this->load->model('Oversea_global_rule_cfg_model','m_global');
        $user_id = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $station_code     = $this->input->post_get('station_code') ?? '';//站点
        $as_up            = $this->input->post_get('as_up') ?? '';             //上架时效(AS)
        $ls_air           = $this->input->post_get('ls_air') ?? '';         //物流时效LS_空运
        $ls_shipping_bulk = $this->input->post_get('ls_shipping_bulk') ?? '';           //物流时效LS_海运散货
        $ls_shipping_full = $this->input->post_get('ls_shipping_full') ?? '';       //物流时效LS_海运整柜
        $ls_trains_bulk   = $this->input->post_get('ls_trains_bulk') ?? '';         //物流时效LS_铁运散货
        $ls_trains_full   = $this->input->post_get('ls_trains_full') ?? '';           //物流时效LS_铁运整柜
        $ls_land          = $this->input->post_get('ls_land') ?? '';            //物流时效LS_陆运
        $ls_blue          = $this->input->post_get('ls_blue') ?? '';         //物流时效LS_蓝单
        $ls_red           = $this->input->post_get('ls_red') ?? '';           //物流时效LS_红单
        $pt_air           = $this->input->post_get('pt_air') ?? '';       //打包时效PT_空运
        $pt_shipping_bulk = $this->input->post_get('pt_shipping_bulk') ?? '';         //打包时效PT_海运散货
        $pt_shipping_full = $this->input->post_get('pt_shipping_full') ?? '';           //打包时效PT_海运整柜
        $pt_trains_bulk   = $this->input->post_get('pt_trains_bulk') ?? '';           //打包时效PT_铁运散货
        $pt_trains_full   = $this->input->post_get('pt_trains_full') ?? '';           //打包时效PT_铁运整柜
        $pt_land          = $this->input->post_get('pt_land') ?? '';            //打包时效LS_陆运
        $pt_blue          = $this->input->post_get('pt_blue') ?? '';           //打包时效PT_蓝单
        $pt_red           = $this->input->post_get('pt_red') ?? '';           //打包时效PT_红单
        $bs               = $this->input->post_get('bs') ?? '';       //缓冲库存(BS)
        $sc               = $this->input->post_get('sc') ?? '';       //备货周期(SC)
        $sp               = $this->input->post_get('sp') ?? '';       //备货处理周期(SP)
        $sz               = $this->input->post_get('sz') ?? '';       //服务对应"Z"值

        $params = [
            'op_uid'=>$user_id,
            'op_zh_name'=>$user_name,
            'station_code'=>$station_code,
            'as_up'            => $as_up,
            'ls_air'           => $ls_air,
            'ls_shipping_bulk' => $ls_shipping_bulk,
            'ls_shipping_full' => $ls_shipping_full,
            'ls_trains_bulk'   => $ls_trains_bulk,
            'ls_trains_full'   => $ls_trains_full,
            'ls_land'          => $ls_land,
            'ls_blue'          => $ls_blue,
            'ls_red'           => $ls_red,
            'pt_air'           => $pt_air,
            'pt_shipping_bulk' => $pt_shipping_bulk,
            'pt_shipping_full' => $pt_shipping_full,
            'pt_trains_bulk'   => $pt_trains_bulk,
            'pt_trains_full'   => $pt_trains_full,
            'pt_land'          => $pt_land,
            'pt_blue'          => $pt_blue,
            'pt_red'           => $pt_red,
            'bs'               => $bs,
            'sc'               => $sc,
            'sp'               => $sp,
            'sz'               => $sz,
            'updated_at'=>date('Y-m-d H:i:s')
        ];
        $result = $this->m_global->modifyRule($params);
        if($result['code'] == 1){
            $this->data['status'] = 1;
            $this->data['errorMess'] = '修改成功';
            http_response($this->data);
        }else{
            $this->data['status'] = 0;
            $this->data['errorMess'] = $result['errorMess'];
            http_response($this->data);
        }
    }

    /**
     * 添加备注
     * http://192.168.71.170:1084/oversea/Global_rule_cfg/addRemark
     */
    public function addRemark(){
        $this->load->model('Oversea_remark_model', "m_remark");
        $station_code = $this->input->post_get('station_code');
        $remark = $this->input->post_get('remark');
        $user_id = $this->input->post_get('user_id');
        $user_name = $this->input->post_get('user_name');
        $params = [
            'op_uid'=>$user_id,
            'op_zh_name'=>$user_name,
            'station_code'=>$station_code,
            'remark'=>$remark,
            'created_at'=>date('Y-m-d H:i:s')
        ];
        $result = $this->m_remark->addRemark($params);
        if($result){
            $this->data['status'] = 1;
            $this->data['errorMess'] = '备注成功';
            http_response($this->data);
        }else{
            $this->data['status'] = 0;
            $this->data['errorMess'] = '备注失败';
            http_response($this->data);
        }
    }


    /**
     * 日志列表
     * http://192.168.71.170:1084/oversea/Global_rule_cfg/getGlobalLogList
     */
    public function getGlobalLogList(){
        $this->load->model('Oversea_global_cfg_log_model', "m_log");
        $this->lang->load('common');
        $station_code = $this->input->post_get('station_code') ?? '';
        $offset = $this->input->post_get('offset') ?? '';
        $limit = $this->input->post_get('limit') ?? '';
        $offset = $offset ? $offset : 1;
        $limit = $limit ? $limit : 20;

        $column_keys = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $result = $this->m_log->getGlobalLogList($station_code,$offset,$limit);
        if(isset($result) && count($result['data_list'])>0){
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key'=>$column_keys,
                'value'=>$result['data_list'],
            );
            $this->data['page_data']['log'] = array(
                'offset'=>(int)$result['data_page']['offset'],
                'limit'=>(int)$result['data_page']['limit'],
                'total'=>$result['data_page']['total'],
            );
        }else{
            $this->data['status'] =1;
            $this->data['data_list']['log']=array(
                'key'=>$column_keys,
                'value'=>array(),
            );
            $this->data['page_data']['log'] = array(
                'offset'=>(int)$offset,
                'limit'=>(int)$limit,
                'total'=>$result['data_page']['total']
            );
        }
        http_response($this->data);
    }
}