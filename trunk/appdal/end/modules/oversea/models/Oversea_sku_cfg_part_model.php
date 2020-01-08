<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 9:34
 */
class Oversea_sku_cfg_part_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_oversea_sku_cfg_part';

    }

    /**
     * 查询单个配置详情
     *
     * @param $gid
     */
    public function getOne($gid)
    {
        $this->db->where('gid', $gid);
        $this->db->from($this->table);
        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_land,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_land,pt_air,pt_red,pt_blue,bs,sc,sp,sz');

        return $this->db->get()->row_array();
    }

    /**
     * 查询规则类型
     */
    public function getRuleType($gid)
    {
        $this->db->where('gid', $gid);
        $this->db->from('yibai_oversea_sku_cfg_main');
        $this->db->select('rule_type');
        $result = $this->db->get()->row_array();

        return $result['rule_type'];
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
    public function check_approve_state_add($gid = '', $old_global_cfg = [], $old_rule_type = '')
    {
        if (empty($gid)) {
            return FALSE;
        }
        $info = $this->db->select('*')->from($this->table)->where('gid', $gid)->get()->row_array();
        if (!empty($info) && $info['state'] == 2 && $old_rule_type == 1) {//审核成功状态保留配置
            return $this->m_cfg_history_model->insert($info);
        }
        if (!empty($info) && $info['state'] == 2 && $old_rule_type == 2) {//保留全局配置
            $old_global_cfg['gid']              = $info['gid'];
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

    public function check_approve_state_delete($gid = '')
    {
        $this->load->model('Oversea_sku_cfg_history_part_model', 'm_cfg_history_model', false, 'oversea');
        if (empty($gid)) {
            return FALSE;
        }

        return $this->m_cfg_history_model->clean($gid);
    }


    public function check_approve_state_delete_all($gid = [])
    {
        $this->load->model('Oversea_sku_cfg_history_part_model', 'm_cfg_history_model', false, 'oversea');
        if (empty($gid)) {
            return FALSE;
        }

        return $this->m_cfg_history_model->clean_all($gid);
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
            $this->load->model('Oversea_sku_cfg_history_part_model', 'm_cfg_history_model', false, 'oversea');
            $this->db->select('rule_type');
            $this->db->where('gid', $params['gid']);
            $this->db->from('yibai_oversea_sku_cfg_main');
            $old_rule = $this->db->get()->row_array();
            //全局状态改成全局,无法修改
            if ($old_rule['rule_type'] != $rule_type || $rule_type == 1) {
                $this->load->model('Oversea_stock_log_model', 'm_log');

                //全局规则类型
                if ($rule_type == 2) {
                    //自定义改全局
                    //获取站点
                    $this->db->select('station_code');
                    $this->db->from('yibai_oversea_sku_cfg_main');
                    $this->db->where('gid', $params['gid']);
                    $station_code = $this->db->get()->row_array();
                    if (empty($station_code)) {
                        $result['code']      = 0;
                        $result['errorMess'] = '未查询到对应的全局站点';

                        return $result;
                    }
                    //查询时效信息
                    $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_land,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_land,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
                    $this->db->from('yibai_oversea_global_rule_cfg');
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
                        'ls_land'          => 0,
                        'ls_air'           => 0,
                        'ls_red'           => 0,
                        'ls_blue'          => 0,
                        'pt_shipping_full' => 0,
                        'pt_shipping_bulk' => 0,
                        'pt_trains_full'   => 0,
                        'pt_trains_bulk'   => 0,
                        'pt_land'          => 0,
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
                    $this->db->where('gid', $params['gid']);
                    $this->db->update($this->table, $data);
                    //更新主表  规则类型 一旦修改 审核状态变成待审核
                    $this->db->where('gid', $params['gid']);
                    $this->db->update('yibai_oversea_sku_cfg_main', ['rule_type' => $rule_type]);
                    //新增日志
                    $this->m_log->modifyStockLog($temp, $params, $rule_type);
                    //删除保留的历史配置
                    $this->check_approve_state_delete($params['gid']);
                    $this->db->trans_complete();
                    if ($this->db->trans_status() === FALSE) {
                        $result['code']      = 0;
                        $result['errorMess'] = '修改失败';
                    } else {
                        $result['code'] = 1;
                    }

                    return $result;
                } elseif ($rule_type == 1) {
                    //全局改自定义 或自定义改自定义
                    $temp = [
                        'as_up'            => $params['as_up'],
                        'ls_shipping_full' => $params['ls_shipping_full'],
                        'ls_shipping_bulk' => $params['ls_shipping_bulk'],
                        'ls_trains_full'   => $params['ls_trains_full'],
                        'ls_trains_bulk'   => $params['ls_trains_bulk'],
                        'ls_land'          => $params['ls_land'],
                        'ls_air'           => $params['ls_air'],
                        'ls_red'           => $params['ls_red'],
                        'ls_blue'          => $params['ls_blue'],
                        'pt_shipping_full' => $params['pt_shipping_full'],
                        'pt_shipping_bulk' => $params['pt_shipping_bulk'],
                        'pt_trains_full'   => $params['pt_trains_full'],
                        'pt_trains_bulk'   => $params['pt_trains_bulk'],
                        'pt_land'          => $params['pt_land'],
                        'pt_air'           => $params['pt_air'],
                        'pt_red'           => $params['pt_red'],
                        'pt_blue'          => $params['pt_blue'],
                        'bs'               => $params['bs'],
                        'sc'               => $params['sc'],
                        'sp'               => $params['sp'],
                        'sz'               => $params['sz'],
                        'original_min_start_amount'      => $params['original_min_start_amount'],
                        'min_start_amount'               => $params['min_start_amount'],
                    ];
                    if ($old_rule['rule_type'] == 1) {//自定义改自定义
                        //先查询原来附表,找出修改什么
                        $result = $this->getDetail($params['gid']);
                        if (empty($result)) {
                            $result['code']      = 0;
                            $result['errorMess'] = '未找到此条记录的信息,修改失败';

                            return $result;
                        }
                    } elseif ($old_rule['rule_type'] == 2) {//全局改自定义
                        //获取站点
                        $this->db->select('station_code');
                        $this->db->from('yibai_oversea_sku_cfg_main');
                        $this->db->where('gid', $params['gid']);
                        $station_code = $this->db->get()->row_array();
                        if (empty($station_code)) {
                            $result['code']      = 0;
                            $result['errorMess'] = '此记录原本为全局配置,找不到对应的站点配置信息,修改失败';

                            return $result;
                        }
                        //查询时效信息
                        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_land,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_land,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
                        $this->db->from('yibai_oversea_global_rule_cfg');
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
                    //没有修改且是自定义改自定义
                    if ($count == 0 && $old_rule['rule_type'] == 1) {
                        //没有修改
                        $result['code']      = 0;
                        $result['errorMess'] = '您未作任何修改，无法保存！';

                        return $result;
                    }
                    $this->db->trans_start();
                    //保留历史配置 如果状态为审核成功
                    if (!$this->check_approve_state_add($params['gid'], $result, $old_rule['rule_type'])) {
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
                    $this->db->where('gid', $params['gid']);
                    $this->db->update($this->table, $temp);
                    //更新规则类型 一旦修改 审核状态变成待审核
                    $this->db->where('gid', $params['gid']);
                    $this->db->update('yibai_oversea_sku_cfg_main', ['rule_type' => $rule_type]);
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
                } else {//全局改全局
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
     * 查询配置详情
     *
     * @param $station_code
     * @param $sku
     *
     * @return mixed
     */
    public function getDetail($gid)
    {
        $this->db->where('gid', $gid);
        $this->db->from($this->table);
        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_air,pt_red,pt_blue,bs,sc,sp,sz,original_min_start_amount,min_start_amount');

        return $this->db->get()->row_array();
    }


    /**
     * 更新审核人审核时间
     *
     * @param $gid
     * @param $uid
     */
    public function check($gid, $uid, $user_name)
    {
        $this->db->where('gid', $gid);
        $this->db->update($this->table, ['approved_at' => date('Y-m-d H:i:s', time()), 'approved_uid' => $uid, 'approved_zh_name' => $user_name]);
    }

    /**
     * 批量审核成功
     *
     * @param array  $gid
     * @param string $uid
     *
     * @return mixed
     */
    public function batchCheckSuccess($gid = [], $uid = '', $user_name = '')
    {
        try {
            $this->load->model('Oversea_sku_cfg_history_part_model', 'm_cfg_history_model', false, 'oversea');
            $this->load->model('Oversea_stock_log_model', 'm_log');
            $processed  = 0;//已处理
            $undisposed = 0;//未处理

            foreach ($gid as $key => $value) {
                //查询状态
                $this->db->select('state');
                $this->db->where('gid', $value);
                $this->db->from($this->table);
                $state = $this->db->get()->row_array();
                if ($state['state'] == 1) {
                    $this->db->trans_start();
                    $this->db->where('gid', $value);
                    $this->db->update($this->table, ['state' => 2]);

                    //更新审核人审核时间
                    $this->check($value, $uid, $user_name);
                    //获取配置详情
                    $detail = $this->getOne($value);
                    //获取规则类型
                    $rule_type = $this->getRuleType($value);
                    //写入日志
                    $this->m_log->checkStockLog($detail, $rule_type, $value, $uid, $user_name, $flag = 1);
                    $this->check_approve_state_delete($value);
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
            $result['status']    = 0;
            $result['errorMess'] = $e->getMessage();
            http_response($result);
        }
    }

    /**
     * 批量审核失败
     *
     * @param array  $gid
     * @param string $uid
     *
     * @return mixed
     */
    public function batchCheckFail($gid = [], $uid = '', $user_name = '')
    {
        try {
            $this->load->model('Oversea_stock_log_model', 'm_log');
            $processed  = 0;//已处理
            $undisposed = 0;//未处理
            foreach ($gid as $key => $value) {
                //查询状态
                $this->db->select('state');
                $this->db->where('gid', $value);
                $this->db->from($this->table);
                $state = $this->db->get()->row_array();
                //如果为待审核状态
                if ($state['state'] == 1) {
                    $this->db->trans_start();
                    $this->db->where('gid', $value);
                    $this->db->update($this->table, ['state' => 3]);
                    //更新审核人审核时间
                    $rows = $this->db->affected_rows();
                    //更新审核人审核时间
                    $this->check($value, $uid, $user_name);
                    //获取配置详情
                    $detail = $this->getOne($value);
                    //获取规则类型
                    $rule_type = $this->getRuleType($value);
                    //写入日志
                    $this->m_log->checkStockLog($detail, $rule_type, $value, $uid, $user_name, $flag = 2);
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
            $result['status']    = 0;
            $result['errorMess'] = $e->getMessage();
            http_response($result);
        }
    }

    /**
     * 更新备注
     */
    public function updateRemark($data)
    {
        $this->db->where('gid', $data['gid']);
        $this->db->update($this->table, ['remark' => $data['remark']]);
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
            $this->load->model('Oversea_sku_cfg_history_part_model', 'm_cfg_history_model', false, 'oversea');
            $this->db->select('rule_type');
            $this->db->where('gid', $params['gid']);
            $this->db->from('yibai_oversea_sku_cfg_main');
            $old_rule = $this->db->get()->row_array();
            if ($old_rule['rule_type'] != $params['rule_type'] || $old_rule['rule_type'] == 1) {//修改了规则或者是自定义改自定义
                $this->load->model('Oversea_stock_log_model', 'm_log');
                //全局规则类型
                if ($params['rule_type'] == '2') {
                    //自定义改全局
                    //获取站点
                    $this->db->select('station_code');
                    $this->db->from('yibai_oversea_sku_cfg_main');
                    $this->db->where('gid', $params['gid']);
                    $station_code = $this->db->get()->row_array();
                    if (empty($station_code)) {
                        return FALSE;
                    }
                    //查询时效信息
                    $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_land,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_land,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
                    $this->db->from('yibai_oversea_global_rule_cfg');
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
                        'ls_land'          => 0,
                        'ls_air'           => 0,
                        'ls_red'           => 0,
                        'ls_blue'          => 0,
                        'pt_shipping_full' => 0,
                        'pt_shipping_bulk' => 0,
                        'pt_trains_full'   => 0,
                        'pt_trains_bulk'   => 0,
                        'pt_land'          => 0,
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
                    $this->db->where('gid', $params['gid']);
                    $this->db->update($this->table, $data);
                    //更新主表  规则类型 一旦修改 审核状态变成已审核
                    $this->db->where('gid', $params['gid']);
                    $this->db->update('yibai_oversea_sku_cfg_main', ['rule_type' => 2]);
                    //新增日志
                    $params['op_uid']     = $user_id;
                    $params['op_zh_name'] = $user_name;
                    $this->m_log->modifyStockLog($temp, $params, $params['rule_type']);
                    //删除保留的历史配置
                    $this->check_approve_state_delete($params['gid']);
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
                        'ls_land'          => $params['ls_land'],
                        'ls_air'           => $params['ls_air'],
                        'ls_red'           => $params['ls_red'],
                        'ls_blue'          => $params['ls_blue'],
                        'pt_shipping_full' => $params['pt_shipping_full'],
                        'pt_shipping_bulk' => $params['pt_shipping_bulk'],
                        'pt_trains_full'   => $params['pt_trains_full'],
                        'pt_trains_bulk'   => $params['pt_trains_bulk'],
                        'pt_land'          => $params['pt_land'],
                        'pt_air'           => $params['pt_air'],
                        'pt_red'           => $params['pt_red'],
                        'pt_blue'          => $params['pt_blue'],
                        'bs'               => $params['bs'],
                        'sc'               => $params['sc'],
                        'sp'               => $params['sp'],
                        'sz'               => $params['sz'],
                        'original_min_start_amount'      => $params['original_min_start_amount'],
                        'min_start_amount'               => $params['min_start_amount'],
                    ];
                    if ($old_rule['rule_type'] == 1) {
                        //先查询原来附表,找出修改什么
                        $result = $this->getDetail($params['gid']);
                        if (empty($result)) {
                            return FALSE;
                        }
                    } elseif ($old_rule['rule_type'] == 2) {
                        //获取站点
                        $this->db->select('station_code');
                        $this->db->from('yibai_oversea_sku_cfg_main');
                        $this->db->where('gid', $params['gid']);
                        $station_code = $this->db->get()->row_array();
                        if (empty($station_code)) {
                            return FALSE;
                        }
                        //查询时效信息
                        $this->db->select('as_up,ls_shipping_full,ls_shipping_bulk,ls_trains_full,ls_trains_bulk,ls_land,ls_air,ls_red,ls_blue,pt_shipping_full,pt_shipping_bulk,pt_trains_full,pt_trains_bulk,pt_land,pt_air,pt_red,pt_blue,bs,sc,sp,sz');
                        $this->db->from('yibai_oversea_global_rule_cfg');
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
                    if (!$this->check_approve_state_add($params['gid'], $result, $old_rule['rule_type'])) {
                        return FALSE;
                    }

                    //更新附表
                    $temp['updated_at']      = date('Y-m-d H:i:s');
                    $temp['updated_uid']     = $user_id;
                    $temp['updated_zh_name'] = $user_name;
                    //一旦修改 审核状态变成待审核
                    $temp['state'] = CHECK_STATE_INIT;
                    $this->db->where('gid', $params['gid']);
                    $this->db->update($this->table, $temp);
                    //更新规则类型
                    $this->db->where('gid', $params['gid']);
                    $this->db->update('yibai_oversea_sku_cfg_main', ['rule_type' => 1]);
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
     * 获取可以审批的数据的ids
     */
    public function get_can_approve_data($limit=300){
        $approve_data = $this->db->select('gid,state')->from($this->table)->where('state',1)->limit($limit)->get()->result_array();
        return $approve_data;
    }

    /**
     * 批量更新数据的审批状态
     */
    public function batch_update_approve_status($approve_data,$approve_result,$nums)
    {
        $ids = empty($approve_data)?[]:array_column($approve_data,'gid');
        $active_user = get_active_user();
        $uid = $active_user->staff_code;
        $user_name = $active_user->user_name;
        $this->db->trans_start();
        //更改审批状态
        $update_data = [
            'state'=>$approve_result,
            'approved_uid'=>$uid,
            'approved_zh_name'=>$user_name,
            'approved_at'=>date('Y-m-d H:i:s')
        ];
        $this->db->where_in('gid', $ids);
        $this->db->update($this->table, $update_data);

        //添加审批日志
        $this->load->model('Oversea_stock_log_model', 'm_log');
        $log_data = [];
        foreach ($approve_data as $key => $detail) {
            //获取规则类型
            $rule_type = $this->getRuleType($detail['gid']);
            //写入日志
            $log_data[$key] = $this->m_log->getStockLogData($detail, $rule_type, $detail['gid'], $uid, $user_name, $approve_result);
        }

        //删除历史数据
        if($approve_result ==2){
            $this->check_approve_state_delete_all($ids);
        }

        $this->m_log->batchInsertLogData($log_data);
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            log_message('Info', "第{$nums}次审批异常");
            return false;
        }
        return true;
    }

}
