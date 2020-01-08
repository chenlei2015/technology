<?php
require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

class Platform_account_model extends MY_Model
{
    use Table_behavior;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_platform_account';
        $this->primaryKey = 'id';
        $this->tableId = 200;
        parent::__construct();
    }

    /**
     * 获取平台code的接口
     */
    public function java_getPlatformCode($params)
    {
        $this->load->helper('common');
        $result   = RPC_CALL('YB_PLAN_PLATFORM_CODE',$params);
        //$result   = RPC_CALL('YB_PLAN_PLATFORM_CODE',$params, null,['debug'=>1]);
        //pr($this->rpc->debug());exit;

        if (empty($result) || !isset($result['code'])) {
            log_message('ERROR', '请求地址：/erp/platformAccount/getPlatform,无返回结果');
            return [];
        }
        if ($result['code'] == 500 || $result['code'] == 0) {
            log_message('ERROR', sprintf('请求地址：/erp/platformAccount/getPlatform,异常：%s', json_encode($result)));
            return [];
        }


//      pr($result);exit;
        if (isset($result['data']) && !empty($result['data'])) {
            return $result['data'];
        } else {
            log_message('ERROR', sprintf('请求地址：/erp/platformAccount/getPlatform,异常：%s', json_encode($result)));
            return [];
        }
    }

    /**
     * 根据平台code 获取该平台下的所有管理账号
     */
    public function java_getAccountNameByPlatformCode($params)
    {
        $this->load->helper('common');
        $result   = RPC_CALL('YB_PLAN_ACCOUNT_NAME',$params);
        //$result   = RPC_CALL('YB_PLAN_ACCOUNT_NAME',$params,null,['debug'=>1]);
        //pr($this->rpc->debug());exit;

        if (empty($result) || !isset($result['code'])) {
            log_message('ERROR', '请求地址：/erp/platformAccount/findAccountNameByPlatformCode,无返回结果');
            return [];
        }
        if ($result['code'] == 500 || $result['code'] == 0) {
            log_message('ERROR', sprintf('请求地址：/erp/platformAccount/findAccountNameByPlatformCode,异常：%s', json_encode($result)));
            return [];
        }


//      pr($result);exit;
        if (isset($result['data']) && !empty($result['data'])) {
            return $result['data'];
        } else {
            log_message('ERROR', sprintf('请求地址：/erp/platformAccount/findAccountNameByPlatformCode,异常：%s', json_encode($result)));
            return [];
        }
    }



}
