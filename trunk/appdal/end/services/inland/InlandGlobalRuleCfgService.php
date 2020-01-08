<?php

/**
 * 国内全局规则配置相关操作
 * @author W02278
 * @name InlandGlobalRuleCfgService Class
 */
class InlandGlobalRuleCfgService
{
    /**
     * client
     * @var array
     */
    public static $s_encode = ['UTF-8'] ;

    /**
     * @author W02278
     * @var Inland_global_rule_cfg_model
     */
    protected $cfgModel ;

    /**
     * @author W02278
     * @var Inland_global_rule_cfg_log_model
     */
    protected $cfgLogModel;

    /**
     * @author W02278
     * @var Inland_global_rule_cfg_remark_model
     */
    protected $cfgRemarkModel;

    /**
     * @author W02278
     * @var MY_Controller object
     */
    private $_ci;

    public $validataRes = [
        'status' => 0,
        'message' => '',
    ];

    /**
     * InlandGlobalRuleCfgService constructor.
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_global_rule_cfg_model', 'cfgModel', false, 'inland');
        $this->_ci->load->model('Inland_global_rule_cfg_log_model', 'cfgLogModel', false, 'inland');
        $this->_ci->load->model('Inland_global_rule_cfg_remark_model', 'cfgRemarkModel', false, 'inland');
    }

    /**
     * 通过id查国内配置信息
     * @param null $gid
     * @return array
     * @throws Exception
     * @author W02278
     * CreateTime: 2019/4/24 9:03
     */
    public function getCfgDetailsByGid($gid = null)
    {
        //返回数据
        $return = [
            'status' => 0,
            'data_list' => [
                'cfg' => [],
                'cfg_log' => [],
                'cfg_remark' => [],
            ],
        ];
        if (!$gid) {
            throw new \Exception('数据错误');
        }
        //配置数据
        $cfg = $this->_ci->cfgModel->getByGid($gid);
        if (!$cfg) {
            return $return;
        }
        $return['status'] = 1;
        //日志及备注
        $cfg_log = $this->_ci->cfgLogModel->getByCid($gid);
        $cfg_remark = $this->_ci->cfgRemarkModel->getByCid($gid);

        //键名
        $this->_ci->lang->load('common');
        $cfg_keys = $this->_ci->lang->myline('inland_global_cfg');
        $cfg_log_keys = $this->_ci->lang->myline('inland_global_cfg_log');
        $cfg_remark_keys = $this->_ci->lang->myline('inland_global_cfg_remark');

        //数据填充
        $return['data_list']['cfg'] = [
            'key' => $cfg_keys,
            'value' => $cfg,
        ];
        $return['data_list']['cfg_log'] = [
            'key' => $cfg_log_keys,
            'value' => $cfg_log,
        ];
        $return['data_list']['cfg_remark'] = [
            'key' => $cfg_remark_keys,
            'value' => $cfg_remark,
        ];

        return $return;
    }

    /**
     * 更新国内全局规则配置并添加日志
     * @param $params
     * @return array
     * @author W02278
     * CreateTime: 2019/4/24 19:46
     */
    public function updateCfg($params)
    {
        $datas = $this->validataCfg($params);
        if (!$datas) {
            return $this->validataRes;
        }
        $res = $this->_ci->cfgModel->updateCfg($datas);
        if ($res) {
            $this->validataRes['status'] = 1;
        }
        return $this->validataRes;
    }

    /**
     * 验证并组装数据
     * @param $params
     * @return array|null
     * @author W02278
     * CreateTime: 2019/4/24 19:02
     */
    private function validataCfg($params)
    {
        $res = [];
        //验证id
        if (!isset($params['id']) || !isset($params['bs']) || !isset($params['sp']) || !isset($params['shipment_time']) || !isset($params['sc']) || !isset($params['first_lt']) || !isset($params['sz'])) {
            $this->validataRes['status'] = '0';
            return null;
        }

        $data = [
            'bs' => $params['bs'],
            'sp' => $params['sp'],
            'shipment_time' => $params['shipment_time'],
            'sc' => $params['sc'],
            'first_lt' => $params['first_lt'],
            'sz' => $params['sz']
        ];

        $model = $this->_ci->cfgModel->pk($params['id']);

        //验证数据是否存在
        if (!$model) {
            $this->validataRes['status'] = '0';
            return null;
        }

        //验证数据是否有改动
        $diff = array_diff_assoc($data , $model);
        if (!$diff) {
            $this->validataRes['status'] = '-1';
            return null;
        }

        //组装数据
        $login_info = get_active_user()->get_user_info();
        $user_name    = is_system_call() ? $login_info['user_name'] : $login_info['oa_info']['userName'];
        $uid          = is_system_call() ? $login_info['uid'] : $login_info['oa_info']['userNumber'];
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['updated_zh_name'] = $user_name;
        $data['updated_uid'] = $uid;

        $res['where'] = ['id' => $params['id']];
        $res['data'] = $data;

        return $res;

    }

    public function addCfgRemark($params)
    {
        $datas = $this->validataCfgRemark($params);
        if (!$datas) {
            return $this->validataRes;
        }
        $res = $this->_ci->cfgRemarkModel->addCfgRemark($datas);
        if ($res) {
            $this->validataRes['status'] = 1;
        }
        return $this->validataRes;
    }

    private function validataCfgRemark($params)
    {
        $model = $this->_ci->cfgModel->pk($params['id']);
        //验证数据是否存在
        if (!$model) {
            $this->validataRes['status'] = '0';
            return null;
        }

        //组装数据
        $time = date('Y-m-d H:i:s');
        $login_info = get_active_user()->get_user_info();
        $user_name    = is_system_call() ? $login_info['user_name'] : $login_info['oa_info']['userName'];
        $uid          = is_system_call() ? $login_info['uid'] : $login_info['oa_info']['userNumber'];
        $data['remark']['gid'] = gen_id(102);
        $data['remark']['cid'] = $params['id'];
        $data['remark']['uid'] = $uid;
        $data['remark']['user_name'] = $user_name;
        $data['remark']['remark'] = $params['remark'];
        $data['remark']['created_at'] = $time;
        //日志记录
        $data['log']['gid'] = gen_id(101);
        $data['log']['cid'] = $params['id'];
        $data['log']['uid'] = $uid;
        $data['log']['user_name'] = $user_name;
        $data['log']['context'] = '添加了一条备注：' . $params['remark'];
        $data['log']['created_at'] = $time;
        //配置id
        $data['cfg']['id'] = $params['id'];
        $data['cfg']['remark'] = $params['remark'];

        return $data;

    }

    public function addRemark($data)
    {
        // todo：添加备注
        //from->file:///D:/%E8%AE%A1%E5%88%92%E7%B3%BB%E7%BB%9FV1.1.0-190418/start.html#g=1&p=%E4%BF%AE%E6%94%B9
        $value = [
            ''
        ];

        if (!is_array($data)) {
            return FALSE;
        }
        //更新附表备注
        if (!empty($data['gid'])) {
            $this->load->model('Sku_cfg_part_model', 'm_part');
            $this->m_part->updateRemark($data);
        }
        //全局规则配置表备注
        if (!empty($data['station_code'])) {
            $this->load->model('Global_rule_cfg_model', 'm_global');
            $this->m_global->updateRemark($data);
        }
        //物流属表备注
        if (!empty($data['log_id'])) {
            $this->load->model('Fba_logistics_list_model', 'm_logistics');
            $this->m_logistics->updateRemark($data);
        }
        //写入备注表
        $this->db->insert($this->table, $data);
        $rows = $this->db->affected_rows();
        if ($rows > 0) {
            return TRUE;
        } else {
            return FALSE;
        }


    }
    
    public function __destruct()
    {
        //todo:do something
    }
}