<?php

/**
 * 国内 备货关系配置
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2018-12-20
 * @link
 */
class StockCfgService
{
    public static $s_system_log_name = 'INLAND';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_sku_cfg_model', 'm_sku_cfg', false, 'inland');
        $this->_ci->load->helper('inland_helper');

        return $this;
    }

    /**
     * 添加一条备注, 成功为true，否则抛异常， 不做权限
     *
     * @param unknown $params
     *
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params)
    {
        $gid    = $params['gid'];
        $remark = $params['remark'];
        $record = $this->_ci->m_sku_cfg->find_by_pk($gid);

        if (empty($record)) {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark']) {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->m_sku_cfg);
        $this->_ci->Record->set('remark', $remark);
//        $this->_ci->Record->set('updated_at', time());
//        $this->_ci->Record->set('updated_uid', get_active_user()->staff_code);
//        $this->_ci->Record->set('updated_zh_name', get_active_user()->user_name);
//
        $db = $this->_ci->m_sku_cfg->getDatabase();

        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1) {
            throw new \RuntimeException(sprintf('国内列表更新备注失败'), 500);
        }
        if (!$this->add_list_remark($params)) {
            throw new \RuntimeException(sprintf('国内列表插入备注失败'), 500);
        }
        $db->trans_complete();

        if ($db->trans_status() === FALSE) {
            throw new \RuntimeException(sprintf('国内添加备注事务提交完成，但检测状态为false'), 500);
        }

        return true;
    }

    /**
     * 添加一条list备注
     *
     * @param unknown $params
     *
     * @return unknown
     */
    public function add_list_remark($params)
    {
        $this->_ci->load->model('Inland_sku_cfg_remark_model', 'm_sku_cfg_remark', false, 'inland');
        append_login_info($params);
        $insert_params = $this->_ci->m_sku_cfg_remark->fetch_table_cols($params);

        return $this->_ci->m_sku_cfg_remark->add($insert_params);
    }

    /**
     * 详情
     *
     * @param unknown $gid
     *
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->m_sku_cfg->findByPk($gid)) === null ? [] : $pk_row->toArray();

        $this->_ci->load->model('Inland_global_rule_cfg_model', 'cfgModel', false, 'inland');
        //将全局表所有信息查出来
        $g_result = $this->_ci->cfgModel->getOneGlobal();

        if ($record['rule_type'] == 2) {
            $record['bs']            = $g_result['bs'];
            $record['sp']            = $g_result['sp'];
            $record['shipment_time'] = $g_result['shipment_time'];
            $record['sc']            = $g_result['sc'];
            $record['first_lt']      = $g_result['first_lt'];
            $record['sz']            = $g_result['sz'];
        }

        return $record;
    }

    public function get_operation_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Inland_sku_cfg_remark_model', 'm_inland_sku_cfg_remark', false, 'inland');

        return $this->_ci->m_inland_sku_cfg_remark->get($gid, $offset, $limit);
    }

    /**
     * 新增
     */
    public function add($params)
    {

        $mess = [];

        //1.先查询改时间段的信息
        $where  = ['set_start_date >=' => $params['set_start_date'], 'set_end_date <=' => $params['set_end_date'], 'is_del' => '0'];
        $result = $this->_ci->m_sku_cfg->get_info($where);

        //2.拿设置的和在设个时间段的历史记录进行比对
        $temp_platform_code = [];
        if (!empty($result)) {
            foreach ($result as $key => $value) {
                $temp_platform_code[] = explode(',', $value['platform_code']);
            }

            $arr_platform = explode(',', $params['platform_code']);

            foreach ($arr_platform as $value) {
                foreach ($temp_platform_code as $key => $val) {
                    if (in_array($value, $val)) {
                        $mess['errorMess'] = $value . '平台已经在配置过,请勿重复配置';
                        $mess['code']      = 100;

                        return $mess;
                    }
                }
            }
        }

        $data['gid']             = $this->_ci->m_sku_cfg->gen_id();
        $data['created_uid']     = get_active_user()->staff_code;
        $data['created_zh_name'] = get_active_user()->user_name;
        $data['created_at']      = time();
        $data['set_start_date']  = $params['set_start_date'];
        $data['set_end_date']    = $params['set_end_date'];
        $data['platform_code']   = $params['platform_code'];
        $data['platform_name']   = $this->tran_platform($data['platform_code']);

        $this->_ci->m_sku_cfg->add($data);
        $mess['code'] = 200;

        return $mess;
    }


    /**
     * 将platform_code转platform_name
     *
     * @param $platform_code
     *
     * @return array|string
     */
    public function tran_platform($platform_code)
    {
        $platform_code = explode(',', $platform_code);
        $platform_name = [];
        foreach ($platform_code as $value) {
            $platform_name[] = INLAND_PLATFORM_CODE[$value]['name']??'-';
        }
        $platform_name = implode(',', $platform_name);

        return $platform_name;
    }

    /**
     *
     * 在保留历史配置表里新增一条记录
     *
     * @param string $gid
     * @param array  $old_global_cfg
     *
     * @return bool
     */
    public function check_approve_state_add($gid = '', $old_global_cfg = [])
    {
        $this->_ci->load->model('Inland_sku_cfg_history_model', 'm_cfg_history_model', false, 'inland');
        if (empty($gid)) {
            return FALSE;
        }
        $info = $this->_ci->m_sku_cfg->pk($gid);

        if (!empty($info) && $info['state'] == 2 && $info['rule_type'] == 1) {//审核成功状态保留配置
            return $this->_ci->m_cfg_history_model->insert($info);
        }
        if (!empty($info) && $info['state'] == 2 && $info['rule_type'] == 2) {//保留全局配置
            $old_global_cfg['gid']              = $info['gid'];
            $old_global_cfg['state']            = $info['state'];
            $old_global_cfg['stock_way']        = $info['stock_way'];
            $old_global_cfg['approved_at']      = $info['approved_at'];
            $old_global_cfg['approved_uid']     = $info['approved_uid'];
            $old_global_cfg['approved_zh_name'] = $info['approved_zh_name'];
            $old_global_cfg['updated_at']       = $info['updated_at'];
            $old_global_cfg['updated_uid']      = $info['updated_uid'];
            $old_global_cfg['updated_zh_name']  = $info['updated_zh_name'];
            $old_global_cfg['remark']           = $info['remark'];

            return $this->_ci->m_cfg_history_model->insert($old_global_cfg);
        } else {
            return TRUE;
        }
    }

    public function check_approve_state_delete($gid = '')
    {
        $this->_ci->load->model('Inland_sku_cfg_history_model', 'm_cfg_history_model', false, 'inland');
        if (empty($gid)) {
            return FALSE;
        }
        return $this->_ci->m_cfg_history_model->clean($gid);

    }

    /**
     * 修改
     * 全局规则 默认审核成功
     * 自定义规则 待审核 审核失败 审核成功
     * 自定义改自定义  要判断是否为审核成功状态  是:要保留  不是:不变
     * 自定义改全局    删除保留的数据 如果存在的话
     * 全局改全局        不变
     * 全局改自定义    保留全局时的配置
     */
    public function update($params)
    {
        $this->_ci->load->model('Inland_sku_cfg_history_model', 'm_cfg_history_model', false, 'inland');
        $mess      = [];
        $rule_type = $this->_ci->m_sku_cfg->check_rule_type($params['gid']);
        if (!isset($rule_type['rule_type'])) {
            $mess['errorMess'] = '此记录异常,请重试';
            $mess['code']      = 100;

            return $mess;
        }
        $temp    = [
            'stock_way'     => $params['stock_way'],
            'bs'            => $params['bs'],
            'sp'            => $params['sp'],
            'shipment_time' => $params['shipment_time'],
            'first_lt'      => $params['first_lt'],
            'sc'            => $params['sc'],
            'sz'            => $params['sz'],
            'reduce_factor' => $params['reduce_factor'],
            'refund_rate'   => $params['refund_rate'],
            'max_safe_stock_day' => $params['max_safe_stock_day'],
        ];
        $old_cfg = $this->_ci->m_sku_cfg->get_cfg($params['gid']);//自定义规则配置


        $this->_ci->load->model('Inland_global_rule_cfg_model', 'm_global_rule_cfg', false, 'inland');
        $old_global_cfg = $this->_ci->m_global_rule_cfg->get_cfg_param();//全局规则配置

        if ($rule_type['rule_type'] == 1 && $params['rule_type'] == 1) {//自定义改自定义
            $diff  = [];
            $count = 0;
            foreach ($old_cfg as $key => $value) {
                if ($old_cfg[$key] != $temp[$key]) {
                    $diff[$key] = $temp[$key];
                    $count++;
                }
            }

            if ($count == 0) {
                //没有修改
                $mess['code']      = 100;
                $mess['errorMess'] = '您未作任何修改，无法保存！';

                return $mess;
            }

            //拼上修改信息
            $temp          = $this->add_updated_info($temp);
            $temp['gid']   = $params['gid'];
            $temp['state'] = 1;
            //开启事务
            $db = $this->_ci->m_sku_cfg->getDatabase();
            $db->trans_start();

            //保留历史配置 如果状态为审核成功
            if (!$this->check_approve_state_add($params['gid'])) {
                $mess['errorMess'] = '保留历史配置失败';
                $mess['code']      = 100;

                return $mess;
            }

            $this->_ci->m_sku_cfg->modify_cfg($temp);

            //写入日志
            $this->_ci->lang->load('inland_lang');
            $this->_ci->load->service('inland/StockCfgLogService');
            $context = '修改 规则:自定义 ';
            foreach ($diff as $key => $value) {
                if ($key == 'stock_way') {
                    $context .= $this->_ci->lang->myline($key) . ':' . STOCK_UP_TYPE[$value]['name'] . ' ';
                } else {
                    $context .= $this->_ci->lang->myline($key) . ':' . $value . ' ';
                }
            }

            $this->_ci->stockcfglogservice->send(['gid' => $params['gid']], $context);
            $db->trans_complete();
            if ($db->trans_status() === FALSE) {
                $mess['errorMess'] = '数据库写入数据失败,请稍后重试';
                $mess['code']      = 100;

                return $mess;
            }
        } elseif ($rule_type['rule_type'] == 2 && $params['rule_type'] == 1) {//全局改自定义
            $diff = [];
            foreach ($old_cfg as $key => $value) {
                if ($old_cfg[$key] != $temp[$key]) {
                    $diff[$key] = $temp[$key];
                }
            }


            $temp              = $this->add_updated_info($temp);
            $temp['rule_type'] = 1;
            $temp['gid']       = $params['gid'];
            $temp['state']     = 1;
            //开启事务
            $db = $this->_ci->m_sku_cfg->getDatabase();
            $db->trans_start();
            //保留历史配置 如果状态为审核成功
            if (!$this->check_approve_state_add($params['gid'], $old_global_cfg)) {
                $mess['errorMess'] = '保留历史配置失败';
                $mess['code']      = 100;

                return $mess;
            }
            $this->_ci->m_sku_cfg->modify_cfg($temp);
            //写入日志
            $this->_ci->lang->load('inland_lang');
            $this->_ci->load->service('inland/StockCfgLogService');
            $context = '修改 规则:自定义 ';

            foreach ($diff as $key => $value) {
                if ($key == 'stock_way') {
                    $context .= $this->_ci->lang->myline($key) . ':' . STOCK_UP_TYPE[$value]['name']??'' . ' ';
                } else {
                    $context .= $this->_ci->lang->myline($key) . ':' . $value . ' ';
                }
            }
            $this->_ci->stockcfglogservice->send(['gid' => $params['gid']], $context);

            $db->trans_complete();
            if ($db->trans_status() === FALSE) {
                $mess['errorMess'] = '数据库写入数据失败,请稍后重试';
                $mess['code']      = 100;

                return $mess;
            }
        } elseif ($rule_type['rule_type'] == 2 && $params['rule_type'] == 2) {//全局改全局 只能修改备货方式
            if ($params['stock_way'] != $old_cfg['stock_way']) {
                $data['stock_way'] = $params['stock_way'];
                $data['gid']       = $params['gid'];
                $data              = $this->add_updated_info($data);
                //开启事务
                $db = $this->_ci->m_sku_cfg->getDatabase();
                $db->trans_start();

                $this->_ci->m_sku_cfg->modify_cfg($data);
                //写入日志
                $this->_ci->lang->load('inland_lang');
                $this->_ci->load->service('inland/StockCfgLogService');
                $context = '修改 规则:全局 ';
                $context .= $this->_ci->lang->myline('stock_way') . ':' . STOCK_UP_TYPE[$data['stock_way']]['name'] . ' ';

                $this->_ci->stockcfglogservice->send(['gid' => $params['gid']], $context);
                $db->trans_complete();
                if ($db->trans_status() === FALSE) {
                    $mess['errorMess'] = '数据库写入数据失败,请稍后重试';
                    $mess['code']      = 100;

                    return $mess;
                }
            } else {
                $mess['code']      = 100;
                $mess['errorMess'] = '您未作任何修改，无法保存！';

                return $mess;
            }
        } elseif ($rule_type['rule_type'] == 1 && $params['rule_type'] == 2) {//自定义改全局

            //初始化备货关系配置表数据
            $initdata = [
                'bs'            => 0,
                'sp'            => 0,
                'shipment_time' => 0,
                'first_lt'      => 0,
                'sc'            => 0,
                'sz'            => 0,
                'rule_type'     => 2,
                'gid'           => $params['gid'],
                'state'         => 2
            ];
            if ($params['stock_way'] != $old_cfg['stock_way']) {
                $old_global_cfg['stock_way'] = $params['stock_way'];
                $initdata['stock_way']       = $params['stock_way'];
            } else {
                $old_global_cfg['stock_way'] = $old_cfg['stock_way'];
            }

            $initdata = $this->add_updated_info($initdata);
            //开启事务
            $db = $this->_ci->m_sku_cfg->getDatabase();
            $db->trans_start();
            $this->_ci->m_sku_cfg->modify_cfg($initdata);


            //写入日志
            $this->_ci->lang->load('inland_lang');
            $this->_ci->load->service('inland/StockCfgLogService');
            $context = '修改 规则:全局 ';
            foreach ($old_global_cfg as $key => $value) {
                if ($key == 'stock_way') {
                    $context .= $this->_ci->lang->myline($key) . ':' . STOCK_UP_TYPE[$value]['name'] . ' ';
                } else {
                    $context .= $this->_ci->lang->myline($key) . ':' . $value . ' ';
                }
            }
            $this->_ci->stockcfglogservice->send(['gid' => $params['gid']], $context);
            $this->check_approve_state_delete($params['gid']);
            $db->trans_complete();
            if ($db->trans_status() === FALSE) {
                $mess['errorMess'] = '数据库写入数据失败,请稍后重试';
                $mess['code']      = 100;

                return $mess;
            }
        }
        $mess['code'] = 200;

        return $mess;
    }

    public function add_updated_info($params)
    {
        $params['updated_at']      = date('Y-m-d H:i:s');
        $params['updated_uid']     = get_active_user()->staff_code;
        $params['updated_zh_name'] = get_active_user()->user_name;

        return $params;
    }

    /**
     * 批量删除
     */
    public function batch_delete($params)
    {
        $data    = [];
        $success = 0;
        $fail    = 0;
        $gid_arr = explode(',', $params['gid']);
        $total   = count($gid_arr);
        foreach ($gid_arr as $key => $gid) {
            $result = $this->_ci->m_sku_cfg->batch_delete($gid);
            if (!empty($result)) {
                //写入日志
                $this->_ci->load->service('inland/StockCfgLogService');
                $context = '此记录被删除';

                $this->_ci->StockCfgLogService->send(['gid' => $gid], $context);
                $success++;
                continue;
            }
            $fail++;
        }
        $data['total']      = $total;//总操作数
        $data['processed']  = $success;//删除成功的数量
        $data['undisposed'] = $fail;//删除失败的数量

        return $data;
    }

    /**
     * 批量审核成功
     */
    public function batch_check_success($params)
    {
        $this->_ci->load->model('Inland_sku_cfg_history_model', 'm_cfg_history_model', false, 'inland');
        $data    = [];
        $success = 0;
        $fail    = 0;
        $gid_arr = explode(',', $params['gid']);
        $total   = count($gid_arr);
        $params  = [
            'approved_at'      => date('Y-m-d H:i:s'),
            'approved_uid'     => get_active_user()->staff_code,
            'approved_zh_name' => get_active_user()->user_name,
            'state'            => 2
        ];
        foreach ($gid_arr as $key => $gid) {
            $result = $this->_ci->m_sku_cfg->batch_check_success($gid, $params);
            if (!empty($result)) {
                //写入日志
                $this->_ci->load->service('inland/StockCfgLogService');
                $context = '此记录审核成功';

                $this->_ci->stockcfglogservice->send(['gid' => $gid], $context);

                //删除保留历史配置
                $this->check_approve_state_delete($gid);

                $success++;
                continue;
            }
            $fail++;
        }
        $data['total']      = $total;//总操作数
        $data['processed']  = $success;//删除成功的数量
        $data['undisposed'] = $fail;//删除失败的数量

        return $data;
    }

    /**
     * 批量审核失败
     */
    public function batch_check_fail($params)
    {
        $data    = [];
        $success = 0;
        $fail    = 0;
        $gid_arr = explode(',', $params['gid']);
        $total   = count($gid_arr);
        $params  = [
            'approved_at'      => date('Y-m-d H:i:s'),
            'approved_uid'     => get_active_user()->staff_code,
            'approved_zh_name' => get_active_user()->user_name,
            'state'            => 3
        ];
        foreach ($gid_arr as $key => $gid) {
            $result = $this->_ci->m_sku_cfg->batch_check_fail($gid, $params);
            if (!empty($result)) {
                //写入日志
                $this->_ci->load->service('inland/StockCfgLogService');
                $context = '此记录审核失败';

                $this->_ci->stockcfglogservice->send(['gid' => $gid], $context);
                $success++;
                continue;
            }
            $fail++;
        }
        $data['total']      = $total;//总操作数
        $data['processed']  = $success;//删除成功的数量
        $data['undisposed'] = $fail;//删除失败的数量

        return $data;
    }


    /**
     * 导入修改
     */
    public function batch_update($params)
    {
        try {
            $rule_type = $this->_ci->m_sku_cfg->check_rule_type($params['gid']);//查询记录原始规则
            if (!isset($rule_type['rule_type'])) {

                return FALSE;
            }
            $temp    = [
                'stock_way'     => $params['stock_way'],
                'bs'            => $params['bs'],
                'sp'            => $params['sp'],
                'shipment_time' => $params['shipment_time'],
                'first_lt'      => $params['first_lt'],
                'sc'            => $params['sc'],
                'sz'            => $params['sz'],
                'reduce_factor' => $params['reduce_factor'],
                'refund_rate'   => $params['refund_rate'],
            ];
            $old_cfg = $this->_ci->m_sku_cfg->get_cfg($params['gid']);//自定义规则配置

            if (empty($old_cfg)) {
                return FALSE;
            }

            $this->_ci->load->model('Inland_global_rule_cfg_model', 'm_global_rule_cfg', false, 'inland');
            $old_global_cfg = $this->_ci->m_global_rule_cfg->get_cfg();//全局规则配置
            if (empty($old_global_cfg)) {
                return FALSE;
            }

            if ($rule_type['rule_type'] == 1 && $params['rule_type'] == 1) {//自定义改自定义
                $diff  = [];
                $count = 0;
                foreach ($old_cfg as $key => $value) {//比较
                    if ($old_cfg[$key] != $temp[$key]) {
                        $diff[$key] = $temp[$key];
                        $count++;
                    }
                }

                if ($count == 0) {
                    //没有修改
                    return FALSE;
                }
                $db = $this->_ci->m_sku_cfg->getDatabase();
                $db->trans_start();
                //保留历史配置 如果状态为审核成功
                if (!$this->check_approve_state_add($params['gid'])) {
                    return FALSE;
                }

                $params          = $this->add_updated_info($params);//修改信息
                $params['state'] = 1;//审核状态为待审核

                if (!$this->_ci->m_sku_cfg->modify_cfg($params)) {
                    return FALSE;
                }

                //写入日志
                $this->_ci->lang->load('inland_lang');
                $this->_ci->load->service('inland/StockCfgLogService');
                $context = '修改 规则:自定义 ';
                foreach ($diff as $key => $value) {
                    if ($key == 'stock_way') {
                        $context .= $this->_ci->lang->myline($key) . ':' . STOCK_UP_TYPE[$value]['name'] . ' ';
                    } else {
                        $context .= $this->_ci->lang->myline($key) . ':' . $value . ' ';
                    }
                }

                $this->_ci->stockcfglogservice->send(['gid' => $params['gid']], $context);
                $db->trans_complete();
                if ($db->trans_status() === FALSE) {
                    return FALSE;
                } else {
                    return TRUE;
                }

            } elseif ($rule_type['rule_type'] == 2 && $params['rule_type'] == 1) {//全局改自定义
                $diff = [];
                foreach ($old_cfg as $key => $value) {
                    if ($old_cfg[$key] != $temp[$key]) {
                        $diff[$key] = $temp[$key];
                    }
                }
                $db = $this->_ci->m_sku_cfg->getDatabase();
                $db->trans_start();

                //保留历史配置 如果状态为审核成功
                if (!$this->check_approve_state_add($params['gid'], $old_global_cfg)) {
                    return FALSE;
                }
                $params          = $this->add_updated_info($params);
                $params['state'] = 1;//审核状态为待审核
                if (!$this->_ci->m_sku_cfg->modify_cfg($params)) {
                    return FALSE;
                }

                //写入日志
                $this->_ci->lang->load('inland_lang');
                $this->_ci->load->service('inland/StockCfgLogService');
                $context = '修改 规则:自定义 ';
                foreach ($diff as $key => $value) {
                    if ($key == 'stock_way') {
                        $context .= $this->_ci->lang->myline($key) . ':' . STOCK_UP_TYPE[$value]['name'] . ' ';
                    } else {
                        $context .= $this->_ci->lang->myline($key) . ':' . $value . ' ';
                    }
                }
                $this->_ci->stockcfglogservice->send(['gid' => $params['gid']], $context);
                $db->trans_complete();
                if ($db->trans_status() === FALSE) {
                    return FALSE;
                } else {
                    return TRUE;
                }
            } elseif ($rule_type['rule_type'] == 2 && $params['rule_type'] == 2) {//全局改全局 只能修改备货方式
                if ($params['stock_way'] != $old_cfg['stock_way']) {
                    $data['stock_way'] = $params['stock_way'];
                    $data['gid']       = $params['gid'];
                    $db                = $this->_ci->m_sku_cfg->getDatabase();
                    $db->trans_start();
                    $data = $this->add_updated_info($data);
                    $this->_ci->m_sku_cfg->modify_cfg($data);
                    //写入日志
                    $this->_ci->lang->load('inland_lang');
                    $this->_ci->load->service('inland/StockCfgLogService');
                    $context = '修改 规则:全局 ';
                    $context .= $this->_ci->lang->myline('stock_way') . ':' . STOCK_UP_TYPE[$data['stock_way']]['name'] . ' ';

                    $this->_ci->stockcfglogservice->send(['gid' => $params['gid']], $context);
                    $db->trans_complete();
                    if ($db->trans_status() === FALSE) {
                        return FALSE;
                    } else {
                        return TRUE;
                    }
                }

                return FALSE;
            } elseif ($rule_type['rule_type'] == 1 && $params['rule_type'] == 2) {//自定义改全局
                //初始化备货关系配置表数据
                $initdata = [
                    'bs'            => 0,
                    'sp'            => 0,
                    'shipment_time' => 0,
                    'first_lt'      => 0,
                    'sc'            => 0,
                    'sz'            => 0,
                    'rule_type'     => 2,
                    'gid'           => $params['gid'],
                    'state'         => 2,//全局状态为审核成功
                ];
                if ($params['stock_way'] != $old_cfg['stock_way']) {
                    $old_global_cfg['stock_way'] = $params['stock_way'];
                    $initdata['stock_way']       = $params['stock_way'];
                } else {
                    $old_global_cfg['stock_way'] = $old_cfg['stock_way'];
                }

                $db = $this->_ci->m_sku_cfg->getDatabase();
                $db->trans_start();
                $initdata = $this->add_updated_info($initdata);

                $this->_ci->m_sku_cfg->modify_cfg($initdata);


                //写入日志
                $this->_ci->lang->load('inland_lang');
                $this->_ci->load->service('inland/StockCfgLogService');
                $context = '修改 规则:全局 ';
                foreach ($old_global_cfg as $key => $value) {
                    if ($key == 'stock_way') {
                        $context .= $this->_ci->lang->myline($key) . ':' . STOCK_UP_TYPE[$value]['name'] . ' ';
                    } else {
                        $context .= $this->_ci->lang->myline($key) . ':' . $value . ' ';
                    }
                }
                $this->_ci->stockcfglogservice->send(['gid' => $params['gid']], $context);
                //删除保留历史配置
                $this->check_approve_state_delete($params['gid']);
                $db->trans_complete();
                if ($db->trans_status() === FALSE) {
                    return FALSE;
                } else {
                    return TRUE;
                }
            }
        } catch (Exception $e) {
            $result['status']    = 0;
            $result['errorMess'] = $e->getMessage();
            http_response($result);
        }

    }
}
