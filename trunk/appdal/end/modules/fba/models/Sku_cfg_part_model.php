<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 9:34
 */
class Sku_cfg_part_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_fba_sku_cfg_part';

    }

    /**
     * 查询单个配置详情
     *
     * @param $id
     */
    public function getOne($id)
    {
        $this->db->where('id', $id);
        $this->db->from($this->table);
        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
        $result = $this->db->get()->row_array();

        return $result;
//        if(!empty($result)){
//            $result['lt'] = $this->get_lead_time($result['sku']);
//        }
//        unset($result['sku']);
//        return $result;
    }

    /**
     * 如果用户没有修改供货周期
     * 取lead_time表的数据
     */
    public function get_lead_time($sku)
    {
        $lt = $this->db->select('arrival_time')->where('sku', $sku)->get('yibai_fba_lead_time')->row_array();
        if (!isset($lt['arrival_time'])) {
            $this->db->select('default_arrival_time')->from('yibai_fba_lead_time')->limit(1);
            $lt = $this->db->get()->row_array();

            return $lt['default_arrival_time'];
        } else {
            return $lt['arrival_time'];
        }
    }


    /**
     * 查询规则类型
     */
    public function getRuleType($id)
    {
        $this->db->where('id', $id);
        $this->db->from('yibai_fba_sku_cfg_main');
        $this->db->select('rule_type');
        $result = $this->db->get()->row_array();

        return $result['rule_type'];
    }

    /**
     *
     * 在保留历史配置表里新增一条记录
     *
     * @param string $id
     * @param array  $old_global_cfg
     *
     * @return bool
     */
    public function check_approve_state_add($id = '', $old_global_cfg = [], $old_rule_type = '')
    {
        if (empty($id)) {
            return FALSE;
        }
        $info = $this->db->select('*')->from($this->table)->where('id', $id)->get()->row_array();
        if (!empty($info) && $info['state'] == 2 && $old_rule_type == 1) {//审核成功状态保留配置
            return $this->m_cfg_history_model->insert($info);
        }
        if (!empty($info) && $info['state'] == 2 && $old_rule_type == 2) {//保留全局配置
            $old_global_cfg['id']               = $info['id'];
            $old_global_cfg['updated_at']       = $info['updated_at'];
            $old_global_cfg['updated_uid']      = $info['updated_uid'];
            $old_global_cfg['updated_zh_name']  = $info['updated_zh_name'];
            $old_global_cfg['state']            = $info['state'];
            $old_global_cfg['approved_at']      = $info['approved_at'];
            $old_global_cfg['approved_uid']     = $info['approved_uid'];
            $old_global_cfg['approved_zh_name'] = $info['approved_zh_name'];
            $old_global_cfg['remark']           = $info['remark'];

            return $this->m_cfg_history_model->insert($old_global_cfg);
        } else {
            return TRUE;
        }
        exit;
    }

    public function check_approve_state_delete($id = '')
    {
        if (empty($id)) {
            return FALSE;
        }

        return $this->m_cfg_history_model->clean($id);
    }

    /**
     * 备货关系修改
     *
     * @param $params
     *
     * @return bool
     */
    public function modifyStock($params, $rule_type)
    {
        try {
            $this->load->model('Fba_sku_cfg_history_part_model', 'm_cfg_history_model', false, 'fba');
            $this->load->model('Stock_log_model', 'm_log');
            $this->db->select('rule_type');
            $this->db->where('id', $params['id']);
            $this->db->from('yibai_fba_sku_cfg_main');
            $old_rule = $this->db->get()->row_array();//旧规则
            $result   = [];
            //全局状态改成全局,无法修改
            if ($old_rule['rule_type'] != $rule_type || $rule_type == 1) {
                //修改为全局规则类型
                if ($rule_type == 2) {
                    //自定义改全局
                    //获取站点
                    $this->db->select('station_code');
                    $this->db->from('yibai_fba_sku_cfg_main');
                    $this->db->where('id', $params['id']);
                    $station_code = $this->db->get()->row_array();
                    if (empty($station_code)) {
                        $result['code']      = 0;
                        $result['errorMess'] = '未查询到对应的全局站点';

                        return $result;
                    }
                    //查询时效信息
                    $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
                    $this->db->from('yibai_fba_global_rule_cfg');
                    $this->db->where('station_code', $station_code['station_code']);
                    $data = $this->db->get()->row_array();
                    if (empty($data)) {
                        $result['code']      = 0;
                        $result['errorMess'] = '该全局站点尚未配置';

                        return $result;
                    }
                    $temp                    = $data;
                    $data                    = [
                        'as_up'            => 0,
                        'ls_shipping_full' => 0,
                        'ls_shipping_bulk' => 0,
                        'ls_trains_full'   => 0,
                        'ls_trains_bulk'   => 0,
                        'ls_air'           => 0,
                        'ls_red'           => 0,
                        'ls_blue'          => 0,
                        'pt_shipping_full' => 0,
                        'pt_shipping_bulk' => 0,
                        'pt_trains_full'   => 0,
                        'pt_trains_bulk'   => 0,
                        'pt_air'           => 0,
                        'pt_red'           => 0,
                        'pt_blue'          => 0,
                        'bs'               => 0,
                        'sc'               => 0,
                        'sp'               => 0,
                        'sz'               => 0,
                    ];
                    $data['updated_at']      = $params['updated_at'];
                    $data['updated_uid']     = $params['op_uid'];
                    $data['updated_zh_name'] = $params['op_zh_name'];
                    $data['state']           = CHECK_STATE_SUCCESS; //审核成功
                    $this->db->trans_start();
                    //更新主表  规则类型 一旦修改 审核状态变成已审核
                    $this->db->where('id', $params['id']);
                    $this->db->update('yibai_fba_sku_cfg_main', ['rule_type' => $rule_type]);
                    //更新附表
                    $this->db->where('id', $params['id']);
                    $this->db->update($this->table, $data);

                    //新增日志
                    $this->m_log->modifyStockLog($temp, $params, $rule_type);

                    //删除保留的历史配置
                    $this->check_approve_state_delete($params['id']);

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $result['code']      = 0;
                        $result['errorMess'] = '修改失败';
                    } else {
                        $result['code'] = 1;
                    }

                    return $result;
                } elseif ($rule_type == 1) {//改为自定义
                    $temp = [
                        'as_up'            => $params['as_up'],
                        'ls_shipping_full' => $params['ls_shipping_full'],
                        'ls_shipping_bulk' => $params['ls_shipping_bulk'],
                        'ls_trains_full'   => $params['ls_trains_full'],
                        'ls_trains_bulk'   => $params['ls_trains_bulk'],
                        'ls_air'           => $params['ls_air'],
                        'ls_red'           => $params['ls_red'],
                        'ls_blue'          => $params['ls_blue'],
                        'pt_shipping_full' => $params['pt_shipping_full'],
                        'pt_shipping_bulk' => $params['pt_shipping_bulk'],
                        'pt_trains_full'   => $params['pt_trains_full'],
                        'pt_trains_bulk'   => $params['pt_trains_bulk'],
                        'pt_air'           => $params['pt_air'],
                        'pt_red'           => $params['pt_red'],
                        'pt_blue'          => $params['pt_blue'],
                        'bs'               => $params['bs'],
                        'sc'               => $params['sc'],
                        'sp'               => $params['sp'],
                        'sz'               => $params['sz'],
                    ];
                    if ($old_rule['rule_type'] == 1) {//自定义改自定义
                        //先查询原来附表,找出修改什么
                        $result = $this->getDetail($params['id']);
                        if (empty($result)) {
                            $result['code']      = 0;
                            $result['errorMess'] = '未找到此条记录的信息,修改失败';

                            return $result;
                        }
                    } elseif ($old_rule['rule_type'] == 2) {//全局改自定义
                        //获取站点
                        $this->db->select('station_code');
                        $this->db->from('yibai_fba_sku_cfg_main');
                        $this->db->where('id', $params['id']);
                        $station_code = $this->db->get()->row_array();
                        if (empty($station_code)) {
                            $result['code']      = 0;
                            $result['errorMess'] = '此记录原本为全局配置,找不到对应的站点配置信息,修改失败';

                            return $result;
                        }
                        //查询时效信息
                        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
                        $this->db->from('yibai_fba_global_rule_cfg');
                        $this->db->where('station_code', $station_code['station_code']);
                        $result = $this->db->get()->row_array();
                        if (empty($result)) {
                            $result['code']      = 0;
                            $result['errorMess'] = '未找到' . $station_code['station_code'] . '全局配置信息,修改失败';

                            return $result;
                        }
                    }

                    $diff  = [];
                    $count = 0;
                    foreach ($result as $key => $value) {
                        if ($result[$key] != $temp[$key]) {
                            $diff[$key] = $temp[$key];
                            $count++;
                        }
                    }
                    if ($count == 0 && $old_rule['rule_type'] == 1) {
                        //没有修改
                        $result['code']      = 0;
                        $result['errorMess'] = '您未作任何修改，无法保存！';

                        return $result;
                    }
                    $this->db->trans_start();
                    //保留历史配置 如果状态为审核成功
                    if (!$this->check_approve_state_add($params['id'], $result, $old_rule['rule_type'])) {
                        $result['errorMess'] = '保留历史配置失败';
                        $result['code']      = 100;

                        return $result;
                    }

                    //更新附表
                    $temp['updated_at']      = $params['updated_at'];
                    $temp['updated_uid']     = $params['op_uid'];
                    $temp['updated_zh_name'] = $params['op_zh_name'];
                    //一旦修改 审核状态变成待审核
                    $temp['state'] = CHECK_STATE_INIT;
                    $this->db->where('id', $params['id']);
                    $this->db->update($this->table, $temp);
                    //更新规则类型 一旦修改 审核状态变成待审核
                    $this->db->where('id', $params['id']);
                    $this->db->update('yibai_fba_sku_cfg_main', ['rule_type' => $rule_type]);
                    //新增日志
                    $this->m_log->modifyStockLog($diff, $params, $rule_type);

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $result['code']      = 0;
                        $result['errorMess'] = '修改失败';

                        return $result;
                    } else {
                        $result['code'] = 1;

                        return $result;
                    }
                } else {
                    $result['code']      = 0;
                    $result['errorMess'] = '规则类型异常,修改失败!';

                    return $result;
                }
            } else {
                $result['code']      = 0;
                $result['errorMess'] = '修改失败!';

                return $result;
            }
        } catch (Exception $e) {
            $result['status']    = 0;
            $result['errorMess'] = $e->getMessage();
            http_response($result);
        }
    }

    /**
     * 备货关系导入修改
     *
     * @param $params
     *
     * @return bool
     */
    public function modifyStockByExcel($params, $user_id, $user_name)
    {
        try {
            $this->load->model('Fba_sku_cfg_history_part_model', 'm_cfg_history_model', false, 'fba');
            $this->load->model('Stock_log_model', 'm_log');
            $this->db->select('rule_type');
            $this->db->where('id', $params['id']);
            $this->db->from('yibai_fba_sku_cfg_main');
            $old_rule = $this->db->get()->row_array();
            if ($old_rule['rule_type'] != $params['rule_type'] || $old_rule['rule_type'] == 1) {//修改了规则或者是自定义改自定义
                //全局规则类型
                if ($params['rule_type'] == '2') {
                    //自定义改全局
                    //获取站点
                    $this->db->select('station_code');
                    $this->db->from('yibai_fba_sku_cfg_main');
                    $this->db->where('id', $params['id']);
                    $station_code = $this->db->get()->row_array();
                    if (empty($station_code)) {
                        return FALSE;
                    }
                    //查询时效信息
                    $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
                    $this->db->from('yibai_fba_global_rule_cfg');
                    $this->db->where('station_code', $station_code['station_code']);
                    $data = $this->db->get()->row_array();
                    if (empty($data)) {
                        return FALSE;
                    }
                    $temp = $data;
                    $data = [
                        'as_up'            => 0,
                        'ls_shipping_full' => 0,
                        'ls_shipping_bulk' => 0,
                        'ls_trains_full'   => 0,
                        'ls_trains_bulk'   => 0,
                        'ls_air'           => 0,
                        'ls_red'           => 0,
                        'ls_blue'          => 0,
                        'pt_shipping_full' => 0,
                        'pt_shipping_bulk' => 0,
                        'pt_trains_full'   => 0,
                        'pt_trains_bulk'   => 0,
                        'pt_air'           => 0,
                        'pt_red'           => 0,
                        'pt_blue'          => 0,
                        'bs'               => 0,
                        'sc'               => 0,
                        'sp'               => 0,
                        'sz'               => 0,
                    ];
                    //更新附表
                    $data['updated_at']      = date('Y-m-d H:i:s');
                    $data['updated_uid']     = $user_id;
                    $data['updated_zh_name'] = $user_name;
                    $data['state']           = CHECK_STATE_SUCCESS;
                    $this->db->trans_start();
                    $this->db->where('id', $params['id']);
                    $this->db->update($this->table, $data);
                    //更新主表  规则类型 一旦修改 审核状态改为已审核
                    $this->db->where('id', $params['id']);
                    $this->db->update('yibai_fba_sku_cfg_main', ['rule_type' => 2]);
                    //新增日志
                    $params['op_uid']     = $user_id;
                    $params['op_zh_name'] = $user_name;
                    $this->m_log->modifyStockLog($temp, $params, $params['rule_type']);
                    //删除保留的历史配置
                    $this->check_approve_state_delete($params['id']);
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        return FALSE;
                    } else {
                        return TRUE;
                    }
                } elseif ($params['rule_type'] == 1) {
                    //全局改自定义 或自定义改自定义
                    $temp = [
                        'as_up'            => $params['as_up'],
                        'ls_shipping_full' => $params['ls_shipping_full'],
                        'ls_shipping_bulk' => $params['ls_shipping_bulk'],
                        'ls_trains_full'   => $params['ls_trains_full'],
                        'ls_trains_bulk'   => $params['ls_trains_bulk'],
                        'ls_air'           => $params['ls_air'],
                        'ls_red'           => $params['ls_red'],
                        'ls_blue'          => $params['ls_blue'],
                        'pt_shipping_full' => $params['pt_shipping_full'],
                        'pt_shipping_bulk' => $params['pt_shipping_bulk'],
                        'pt_trains_full'   => $params['pt_trains_full'],
                        'pt_trains_bulk'   => $params['pt_trains_bulk'],
                        'pt_air'           => $params['pt_air'],
                        'pt_red'           => $params['pt_red'],
                        'pt_blue'          => $params['pt_blue'],
                        'bs'               => $params['bs'],
                        'sc'               => $params['sc'],
                        'sp'               => $params['sp'],
                        'sz'               => $params['sz'],
                    ];
                    if ($old_rule['rule_type'] == 1) {
                        //先查询原来附表,找出修改什么
                        $result = $this->getDetail($params['id']);
                        if (empty($result)) {
                            return FALSE;
                        }
                    } elseif ($old_rule['rule_type'] == 2) {
                        //获取站点
                        $this->db->select('station_code');
                        $this->db->from('yibai_fba_sku_cfg_main');
                        $this->db->where('id', $params['id']);
                        $station_code = $this->db->get()->row_array();
                        if (empty($station_code)) {
                            return FALSE;
                        }
                        //查询时效信息
                        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
                        $this->db->from('yibai_fba_global_rule_cfg');
                        $this->db->where('station_code', $station_code['station_code']);
                        $result = $this->db->get()->row_array();
                        if (empty($result)) {
                            return FALSE;
                        }
                    }


                    $diff  = [];
                    $count = 0;
                    foreach ($result as $key => $value) {
                        if ($result[$key] != $temp[$key]) {
                            $diff[$key] = $temp[$key];
                            $count++;
                        }
                    }
                    //没有修改且是自定义改自定义
                    if ($count == 0 && $old_rule['rule_type'] == 1) {
                        return FALSE;
                    }
                    $this->db->trans_start();

                    //保留历史配置 如果状态为审核成功
                    if (!$this->check_approve_state_add($params['id'], $result, $old_rule['rule_type'])) {
                        return FALSE;
                    }

                    //更新附表
                    $temp['updated_at']      = date('Y-m-d H:i:s');
                    $temp['updated_uid']     = $user_id;
                    $temp['updated_zh_name'] = $user_name;
                    //一旦修改 审核状态变成待审核
                    $temp['state'] = CHECK_STATE_INIT;
                    $this->db->where('id', $params['id']);
                    $this->db->update($this->table, $temp);
                    //更新规则类型
                    $this->db->where('id', $params['id']);
                    $this->db->update('yibai_fba_sku_cfg_main', ['rule_type' => 1]);
                    //新增日志
                    $params['op_uid']     = $user_id;
                    $params['op_zh_name'] = $user_name;
                    $this->m_log->modifyStockLog($diff, $params, $params['rule_type']);

                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        return FALSE;
                    } else {
                        return TRUE;
                    }
                } else {//全局改全局
                    return FALSE;
                }
            } else {//没有rule_type 或是自定义
                return FALSE;
            }
        } catch (Exception $e) {
            log_message('ERROR', sprintf('批量修改异常', $e->getMessage()));
            throw new \RuntimeException(sprintf('批量修改异常'), 500);
        }
    }

    /**
     * 查询配置详情
     *
     * @param $station_code
     * @param $sku
     *
     * @return mixed
     */
    public function getDetail($id)
    {
        $this->db->where('id', $id);
        $this->db->from($this->table);
        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sp,sz');

        return $this->db->get()->row_array();
    }


    /**
     * 更新审核人审核时间
     *
     * @param $id
     * @param $uid
     */
    public function check($id, $uid, $user_name)
    {
        $this->db->where('id', $id);
        $this->db->update($this->table, ['approved_at' => date('Y-m-d H:i:s', time()), 'approved_uid' => $uid, 'approved_zh_name' => $user_name]);
    }

    /**
     * 批量审核成功
     *
     * @param array  $id
     * @param string $uid
     *
     * @return mixed
     */
    public function batchCheckSuccess($id = [], $uid = '', $user_name = '')
    {
        try {
            $this->load->model('Fba_sku_cfg_history_part_model', 'm_cfg_history_model', false, 'fba');
            $this->load->model('Stock_log_model', 'm_log');
            $processed  = 0;//已处理
            $undisposed = 0;//未处理

            foreach ($id as $key => $value) {
                //查询状态
                $this->db->select('state');
                $this->db->where('id', $value);
                $this->db->from($this->table);
                $state = $this->db->get()->row_array();
                if ($state['state'] == 1) {
                    $this->db->trans_start();
                    $this->db->where('id', $value);
                    $this->db->update($this->table, ['state' => 2]);

                    //更新审核人审核时间
                    $this->check($value, $uid, $user_name);
                    //获取配置详情
                    $detail = $this->getOne($value);
                    //写入日志
                    $this->m_log->checkStockLog($detail, $flag = 1);
                    $this->check_approve_state_delete($value);
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $undisposed++;
                        log_message('ERROR', "{$value}审核异常");
                    }
                    $processed++;
                } else {
                    $undisposed++;
                }
            }
            $data['processed']  = $processed;
            $data['undisposed'] = $undisposed;

            return $data;
        } catch (Exception $e) {
            log_message('ERROR', sprintf('批量审核异常', $e->getMessage()));
            throw new \RuntimeException(sprintf('批量审核异常'), 500);
        }
    }

    /**
     * 批量审核失败
     *
     * @param array  $id
     * @param string $uid
     *
     * @return mixed
     */
    public function batchCheckFail($id = [], $uid = '', $user_name = '')
    {
        try {
            $this->load->model('Stock_log_model', 'm_log');
            $processed  = 0;//已处理
            $undisposed = 0;//未处理
            foreach ($id as $key => $value) {
                //查询状态
                $this->db->select('state');
                $this->db->where('id', $value);
                $this->db->from($this->table);
                $state = $this->db->get()->row_array();
                //如果为待审核状态
                if ($state['state'] == 1) {
                    $this->db->trans_start();
                    $this->db->where('id', $value);
                    $this->db->update($this->table, ['state' => 3]);
                    //更新审核人审核时间

                    //更新审核人审核时间
                    $this->check($value, $uid, $user_name);
                    //获取配置详情
                    $detail = $this->getOne($value);

                    //写入日志
                    $this->m_log->checkStockLog($detail, $flag = 2);
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $undisposed++;
                        log_message('ERROR', "{$value}修改异常");
                    }
                    $processed++;
                } else {
                    $undisposed++;
                }
            }
            $data['processed']  = $processed;
            $data['undisposed'] = $undisposed;

            return $data;
        } catch (Exception $e) {
            log_message('ERROR', sprintf('批量审核异常', $e->getMessage()));
            throw new \RuntimeException(sprintf('批量审核异常'), 500);
        }
    }

    /**
     * 更新备注
     */
    public function updateRemark($data)
    {
        $this->db->where('id', $data['id']);
        $this->db->update($this->table, ['remark' => $data['remark']]);
    }
}