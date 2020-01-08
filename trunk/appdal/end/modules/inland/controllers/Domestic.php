<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 国内全局规则配置
 * @author W02278
 * @name Domestic Class
 */
class Domestic extends MY_Controller {
    
    public $data = ['status' => 0];
    
    public function __construct()
    {
        parent::__construct();
        $this->load->model('Inland_global_rule_cfg_model', 'cfgModel', false, 'inland');
        get_active_user();
    }

    /**
     * 全局规则配置列表
     * @method GET
     * @link http://192.168.71.170:84/inland/Domestic/getInlandCfgList
     * @author W02278
     * CreateTime: 2019/4/22 16:14
     */
    public function getInlandCfgList()
    {
        try {
            $this->lang->load('common');
            $column_keys = $this->lang->myline('inland_global_cfg');
            $offset = $this->input->post_get('offset');
            $limit = $this->input->post_get('limit');
            $offset = $offset ? $offset : 1;
            $limit = $limit ? $limit : 20;
            //列表数据
            $result = $this->cfgModel->getList($offset , $limit);

            if (isset($result) && count($result['data_list']) > 0) {
                //转中文
                $valueData = $this->_getView($result['data_list']);
                $this->data['status'] = 1;
                $this->data['data_list']['value'] = array(
                    'key' => array_values($column_keys),
                    'value' => $valueData,
                );
                $this->data['page_data'] = array(
                    'offset' => (int)$result['data_page']['offset'],
                    'limit' => (int)$result['data_page']['limit'],
                    'total' => $result['data_page']['total'],
                );
            } else {
                $this->data['status'] = 1;
                $this->data['data_list']['value'] = array(
                    'key' => $column_keys,
                    'value' => [],
                );
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
     * 获取配置及日志，备注
     * @throws Exception
     * @author W02278
     * @link http://192.168.71.170:84/inland/Domestic/getInlandCfgDetail?id=xxx
     * CreateTime: 2019/4/23 17:55
     */
    public function getInlandCfgDetail()
    {
        $params = $this->input->get();
        if (!isset($params['id']) || !$params['id']) {
            throw new \Exception('数据异常');
        }

        $this->load->service('inland/InlandGlobalRuleCfgService');
        $res = $this->inlandglobalrulecfgservice->getCfgDetailsByGid($params['id']);
        $res = $this->_getView($res);
        $this->data = $res;
        http_response($this->data);
    }

    /**
     * 根据配置id获取日志
     * @throws Exception
     * @author W02278
     * @link http://192.168.71.170:84/inland/Domestic/getLogs?id=xxx
     * CreateTime: 2019/4/24 10:55
     */
    public function getLogs()
    {
        $params = $this->input->get();
        if (!isset($params['id']) || !$params['id']) {
            throw new \Exception('数据异常');
        }
        $offset = $params['offset'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $this->lang->load('common');
        $cfg_log_keys = $this->lang->myline('inland_global_cfg_log');
        $this->load->model('Inland_global_rule_cfg_log_model', 'cfgLogModel', false, 'inland');
        $this->data = $this->cfgLogModel->getByCid($params['id'] , $offset , $limit);
        $this->data['key'] = array_values($cfg_log_keys);
        http_response($this->data);
    }

    /**
     * 根据配置id获取备注
     * @throws Exception
     * @author W02278
     * @link http://192.168.71.170:84/inland/Domestic/getRemarks?id=xxx
     * CreateTime: 2019/4/24 10:55
     */
    public function getRemarks()
    {
        $params = $this->input->get();
        if (!isset($params['id']) || !$params['id']) {
            throw new \Exception('数据异常');
        }
        $offset = $params['offset'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $this->lang->load('common');
        $cfg_remark_keys = $this->lang->myline('inland_global_cfg_remark');
        $this->load->model('Inland_global_rule_cfg_remark_model', 'cfgRemarkModel', false, 'inland');
        $this->data = $this->cfgRemarkModel->getByCid($params['id'] , $offset , $limit);
        $this->data['key'] = array_values($cfg_remark_keys);
        http_response($this->data);
    }


    /**
     * 更新国内全局规则配置并添加日志
     * @throws Exception
     * @author W02278
     * @link http://192.168.71.170:84/inland/Domestic/updateCfg
     * CreateTime: 2019/4/24 19:47
     */
    public function updateCfg()
    {
        try {
            if ($this->input->method() == 'post') {
                $params = $this->compatible('post');
                $this->load->service('inland/InlandGlobalRuleCfgService');
                $res = $this->inlandglobalrulecfgservice->updateCfg($params);
                $message = [
                    '1' => '更新成功',
                    '-1' => '数据没有变化，不需要进行更新',
                    '0' => '更新失败'
                ];
                $this->data['data'] = ($res['status'] != '0') ? 1 : 0;
                $this->data['errorMess'] = $message[$res['status']];
                if ($this->data['data'] == 1)
                {
                    $this->data['status'] = 1;
                    $code = 200;
                }

            }
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
     * 国内全局配置添加备注
     * @author W02278
     * @link http://192.168.71.170:84/inland/Domestic/addCfgRemark
     * CreateTime: 2019/4/25 11:19
     */
    public function addCfgRemark()
    {
        try {
            if ($this->input->method() == 'post') {
                $params = $this->input->post();
                if (!isset($params['id']) || !$params['id']) {
                    throw new \Exception('数据异常');
                }
                if (!isset($params['remark']) || !$params['remark']) {
                    throw new \Exception('数据异常');
                }
                $this->load->service('inland/InlandGlobalRuleCfgService');
                $res = $this->inlandglobalrulecfgservice->addCfgRemark($params);
                $message = [
                    '1' => '更新成功',
                    '0' => '更新失败'
                ];
                $this->data['data'] = $res['status'] ? 1 : 0;
                $this->data['errorMess'] = $message[$this->data['data']];
                if ($this->data['data'] == 1)
                {
                    $this->data['status'] = 1;
                    $code = 200;
                }

            }
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

    /**
     * 站点
     * @param $station_code
     * @return mixed
     */
    public function syncStation($station_code)
    {
        $syncStation = FBA_STATION_CODE;

        return isset($syncStation[$station_code]['name']) ? $syncStation[$station_code]['name'] : $station_code;
    }

    /**
     * 添加备注
     * http://192.168.71.170:1084/fba/Global_rule_cfg/addRemark
     */
    public function addRemarkUsed()
    {
        $this->load->model('Fba_remark_model', "m_remark");
        $station_code = $this->input->post_get('station_code');
        $remark = $this->input->post_get('remark');
        $user_id = $this->input->post_get('user_id');
        $user_name = $this->input->post_get('user_name');
        $params = [
            'op_uid' => $user_id,
            'op_zh_name' => $user_name,
            'station_code' => $station_code,
            'remark' => $remark,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $result = $this->m_remark->addRemark($params);
        if ($result) {
            $this->data['status'] = 1;
            $this->data['errorMess'] = '备注成功';
            http_response($this->data);
        } else {
            $this->data['status'] = 0;
            $this->data['errorMess'] = '备注失败';
            http_response($this->data);
        }
    }


    /**
     * 日志列表
     * http://192.168.71.170:84/fba/Global_rule_cfg/getGlobalLogList
     */
    public function getGlobalLogList()
    {
        $this->load->model('Global_cfg_log_model', "m_log");
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
        $result = $this->m_log->getGlobalLogList($station_code, $offset, $limit);
        if (isset($result) && count($result['data_list']) > 0) {
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key' => $column_keys,
                'value' => $result['data_list'],
            );
            $this->data['page_data']['log'] = array(
                'offset' => (int)$result['data_page']['offset'],
                'limit' => (int)$result['data_page']['limit'],
                'total' => $result['data_page']['total'],
            );
        } else {
            $this->data['status'] = 1;
            $this->data['data_list']['log'] = array(
                'key' => $column_keys,
                'value' => array(),
            );
            $this->data['page_data']['log'] = array(
                'offset' => (int)$offset,
                'limit' => (int)$limit,
                'total' => $result['data_page']['total']
            );
        }
        http_response($this->data);
    }
}
