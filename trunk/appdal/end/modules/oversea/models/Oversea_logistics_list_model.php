<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/9
 * Time: 11:01
 */
class Oversea_logistics_list_model extends MY_Model
{
    private $_ci;

    public function __construct()
    {
        $this->_ci   =& get_instance();
        $this->table = 'yibai_oversea_logistics_list';
        parent::__construct();
    }

    /**
     * 获取物流属性配置列表
     */
    public function getLogisticsList($params = [], $id = [], $get_count = '')
    {
        if (!is_array($params)) return FALSE;
        //权限控制
        if (isset($params['owner_station'])) {
            $my_station = $params['owner_station'];
            if (isset($params['oversea_station_code'])) {//查询
                if (!in_array($params['oversea_station_code'], $my_station)) {//查询的站点 没有权限
                    return false;
                }
            } else {//不查询
                $params['oversea_station_code'] = $params['owner_station'];
            }
        }
        $offset = (($params['offset'] > 0 ? $params['offset'] : 1) - 1) * $params['limit'];
        $limit  = $params['limit'];
        $this->db->select('*');
        if (!empty($id)) {
            $this->db->where_in('id', $id);
            $this->db->from($this->table);
            $db    = clone $this->db;
            $total = $db->count_all_results();//获取总条数
            if (!empty($get_count)) {
                $data['count'] = $total;

                return $data;
            }
            unset($db);
            $this->db->limit($params['length']);
            $this->db->offset($offset);
            $this->db->order_by('created_at', 'DESC');
            $list              = $this->db->get()->result_array();
            $data['data_list'] = $list = $this->_getView($list);

            return $data;
        }

        if (isset($params['logistics_id'])) {
            $this->db->where('logistics_id', $params['logistics_id']);
        }
        if (isset($params['details_of_first_way_transportation'])) {
            $this->db->where('details_of_first_way_transportation', $params['details_of_first_way_transportation']);
        }
        if (isset($params['oversea_station_code'])) {
            $this->db->where('station_code', $params['oversea_station_code']);
        }
        if (!empty($params['check_attr_time_start']) && !empty($params['check_attr_time_end'])) {
            $this->db->where('approve_at >=', $params['check_attr_time_start'] . ' 00:00:00');
            $this->db->where('approve_at <=', $params['check_attr_time_end'] . ' 23:59:59');
        }
        if (!empty($params['updated_at_start']) && !empty($params['updated_at_end'])) {
            $this->db->where('updated_at >=', $params['updated_at_start'] . ' 00:00:00');
            $this->db->where('updated_at <=', $params['updated_at_end'] . ' 23:59:59');
        }
        if (!empty($params['created_at_start']) && !empty($params['created_at_end'])) {
            $this->db->where('created_at >=', $params['created_at_start'] . ' 00:00:00');
            $this->db->where('created_at <=', $params['created_at_end'] . ' 23:59:59');
        }
        if (isset($params['sku_state'])) {
            $this->db->where('sku_state', $params['sku_state']);
        }
        if (isset($params['product_status'])) {
            $this->db->where('product_status', $params['product_status']);
        }
        if (isset($params['sku'])) {
            $this->db->where_in('sku', trimArray($params['sku']));
        }

        if (isset($params['check_attr_state'])) {
            $this->db->where('approve_state', $params['check_attr_state']);
        }

        if (isset($params['idsArr'])) {
            $this->db->where_in('id', $params['idsArr']);
        }

        $this->db->from($this->table);
        $db    = clone $this->db;
        $total = $db->count_all_results();//获取总条数

        //暂存
        $query_export = clone $this->db;
        $this->_ci->load->library('rediss');
        $this->_ci->load->service('basic/SearchExportCacheService');
        $total_l = str_pad((string)$total, 10, '0', STR_PAD_LEFT);
        $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_LOGISTICS_LIST_SEARCH_EXPORT)->set($total_l . $query_export->get_compiled_select('', false));


        if (!empty($get_count)) {
            $data['count'] = $total;

            return $data;
        }
        unset($db);
        if (!empty($params['length']) && isset($params['start'])) {
            //$this->db->limit($params['start'],$params['length']);
            $this->db->limit($params['length']);
            $this->db->offset($params['start']);
        } else {
            $this->db->limit($limit);
            $this->db->offset($offset);
        }

        $this->db->order_by('created_at', 'DESC');
        $this->db->order_by('id', 'DESC');
        $list = $this->db->get()->result_array();
        $list = $this->_getView($list);

        $data['data_list'] = $list;
        $data['data_page'] = [
            'limit'  => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total'  => (float)$total
        ];

        return $data;
    }

    /**
     * 转中文
     */
    public function _getView($list)
    {
        $this->load->service('basic/DropdownService');
        $station_code   = $this->dropdownservice->dropdown_oversea_station_code();
        $sku_state      = $this->dropdownservice->dropdown_sku_state();
        $product_status      = $this->dropdownservice->dropdown_product_status();
        $logistics_attr = $this->dropdownservice->dropdown_logistics_attr_unknown();
        $details_of_first_way_transportation = $this->dropdownservice->dropdown_details_of_first_way_transportation();

        $check_state    = $this->dropdownservice->dropdown_check_state();
        $mix_hair_state  = $this->dropdownservice->dropdown_mix_hair_state(); //允许空海混发状态
        $listing_state  = $this->dropdownservice->dropdown_listing_state(); //运营状态
        $is_contraband  = $this->dropdownservice->dropdown_is_contraband(); //违禁状态
        $is_infringement  = $this->dropdownservice->dropdown_is_infringement(); //侵权状态
        $this->load->model('Oversea_logistics_list_remark_model', 'm_remark');
        if (!empty($list)) {
            $log_id      = array_keys(key_by($list, 'id'));
            $remark_list = $this->m_remark->getNewByGroupList($log_id);
            $remark_list = key_by($remark_list, 'log_id');
            foreach ($list as &$item) {
                if (!empty($item['station_code'])) {
                    $item['station_code'] = $station_code[$item['station_code']]??'未知地区';
                }
                if (!empty($item['platform_code'])) {
                    $item['platform_code'] = INLAND_PLATFORM_CODE[strtoupper($item['platform_code'])]['name'] ?? '-';
                }
                $item['sku_state_zh']     = $sku_state[$item['sku_state']]??'未知状态';
                $item['product_status_zh']     = $product_status[$item['product_status']]??'未知状态';
                $item['logistics_id_zh']  = $logistics_attr[$item['logistics_id']]??'未知';
                $item['details_of_first_way_transportation_zh']  = $details_of_first_way_transportation[$item['details_of_first_way_transportation']]??'未知';
                $item['approve_state_zh'] = $check_state[$item['approve_state']]??'未知状态';
                $item['is_import_text']   = isset(IS_IMPORT[$item['is_import']]) ? IS_IMPORT[$item['is_import']]['name'] : '';

                //$item['approve_at'] =  $item['approve_zh_name'].' '.$item['approve_at'];
                //$item['updated_at'] = $item['updated_zh_name'].' '.$item['updated_at'];
                $item['mix_hair_zh'] = $mix_hair_state[$item['mix_hair']]??'未知状态';//允许空海混发状态
                $item['listing_state_zh'] = $listing_state[$item['listing_state']]??'未知状态';//运营状态
                $item['contraband_state_zh'] = $is_contraband[$item['contraband_state']]??'未知状态'; //违禁状态
                $item['infringement_state_zh'] = $is_infringement[$item['infringement_state']]??'未知状态';//侵权状态
                $item['remark'] = isset($remark_list[$item['id']]) ? $remark_list[$item['id']]['remark'] : '';
            }
        }

        return $list;
    }

    /**
     * 详情页
     */
    public function getDetail($id = '')
    {
        $this->db->select('id,sku,sku_state,station_code,sku_name,logistics_id,infringement_state,contraband_state,listing_state,refund_rate,mix_hair');
        $this->db->where('id', $id);
        $this->db->from($this->table);
        $result = $this->db->get()->row_array();

        if (!empty($result)) {
            $this->load->model('Oversea_logistics_list_remark_model', 'm_remark');
            $remark_list = $this->m_remark->getNewByGroupList([$id]);
            if (!empty($remark_list)) {
                $result['op_zh_name'] = $remark_list[0]['op_zh_name'];
                $result['created_at'] = $remark_list[0]['created_at'];
                $result['remark']     = $remark_list[0]['remark'];
            } else {
                $result['op_zh_name'] = '';
                $result['created_at'] = '';
                $result['remark']     = '';
            }
        }

        return $result;
    }

    /**
     * 修改物流属性
     */
    public function modify($params = [])
    {
        $this->load->model('Oversea_logistics_cfg_history_model', 'm_cfg_history_model', false, 'oversea');
        $this->load->model('Oversea_logistics_list_log_model', 'm_log');
        $this->db->trans_start();
        //查询本来的属性
        $this->db->select('logistics_id,details_of_first_way_transportation,sku_state,approve_state,infringement_state,contraband_state,listing_state,refund_rate,mix_hair');
        $this->db->from($this->table);
        $this->db->where('id', $params['id']);
        $result_data = $this->db->get()->row_array();
        if (empty($result_data)) {
            return FALSE;
        }
        //如果用户没有修改 则按原数据处理
        if (empty($params['logistics_id'])) {
            $params['logistics_id'] = $result_data['logistics_id'];
        }
        if (empty($params['sku_state'])) {
            $params['sku_state'] = $result_data['sku_state'];
        }
        //退款率
        if (empty($params['refund_rate'])) {
            $params['refund_rate'] = $result_data['refund_rate'];
        }
        //是否允许空海混发
        if (empty($params['mix_hair'])){
            $params['mix_hair'] = $result_data['mix_hair'];
        }
        //是否侵权
        if (empty($params['infringement_state'])){
            $params['infringement_state'] = $result_data['infringement_state'];
        }
        //是否违禁
        if (empty($params['contraband_state'])){
            $params['contraband_state'] = $result_data['contraband_state'];
        }
        //运营状态
        if (empty($params['listing_state'])){
            $params['listing_state'] = $result_data['listing_state'];
        }
        $old  = [
            'logistics_id' => $result_data['logistics_id'],
            'refund_rate' => $result_data['refund_rate'],
            'details_of_first_way_transportation' => $result_data['details_of_first_way_transportation'],
            'sku_state'    => $result_data['sku_state'],
            'mix_hair'    => $result_data['mix_hair'],
            'infringement_state'    => $result_data['infringement_state'],
            'contraband_state'    => $result_data['contraband_state'],
//            'listing_state'    => $result_data['listing_state'],
        ];
        $temp = [
            'logistics_id' => $params['logistics_id'],
            'refund_rate' => $params['refund_rate'],
            'details_of_first_way_transportation' => LOGISTICS_ATTR_TO_DETAILS_OF_FIRST_WAY_TRANSPORTATION[$params['logistics_id']],
            'sku_state'    => $params['sku_state'],
            'mix_hair'    => $params['mix_hair'],
            'infringement_state'    => $params['infringement_state'],
            'contraband_state'    => $params['contraband_state'],
//            'listing_state'    => $params['listing_state'],
        ];

        $diff  = [];
        $count = 0;
        //查询的结果和输入的进行比较是否有修改
        foreach ($old as $key => $value) {
            if ($old[$key] != $temp[$key]) {
                $diff[$key] = $temp[$key];
                $count++;
            }
        }

        $result = [];
        if ($count == 0) {
            $result['code'] = 2;
        } else {
            //保留历史配置 如果状态为审核成功
            if ($result_data['approve_state'] == 2) {
                if (!$this->check_approve_state_add($params['id'])) {
                    $result['code']      = 0;
                    $result['errorMess'] = '保留历史配置失败';

                    return $result;
                }
            }
            //状态变为待审核
            $params['approve_state'] = 1;
            $this->db->where('id', $params['id']);
            $this->db->update($this->table, $params);
            //写入日志
            $this->m_log->modifyLog($diff, $params);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                $result['code'] = 0;
            } else {
                $result['code'] = 1;
            }
        }

        return $result;
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
    public function check_approve_state_add($id = '')
    {
        $this->load->model('Oversea_logistics_cfg_history_model', 'm_cfg_history_model', false, 'oversea');
        if (empty($id)) {
            return FALSE;
        }
        $info = $this->db->select('id,sku_state,logistics_id,approve_state')->from($this->table)->where('id', $id)->get()->row_array();
//        print_r($info);exit;
        if ($info['approve_state'] == 2) {//审核成功状态保留配置
            unset($info['approve_state']);

            return $this->m_cfg_history_model->insert($info);
        }
    }

    public function check_approve_state_delete($id = '')
    {
        $this->load->model('Oversea_logistics_cfg_history_model', 'm_cfg_history_model', false, 'oversea');
        if (empty($id)) {
            return FALSE;
        }

        return $this->m_cfg_history_model->clean($id);
    }


    public function check_approve_state_delete_all($ids = '')
    {
        $this->load->model('Oversea_logistics_cfg_history_model', 'm_cfg_history_model', false, 'oversea');
        if (empty($ids)) {
            return FALSE;
        }

        return $this->m_cfg_history_model->clean_all($ids);
    }

    /**
     * 批量审核
     *
     * @param array  $gid
     * @param string $uid
     *
     * @return mixed
     */
    public function batchCheck($id = [], $approve_state = 0, $uid = '', $user_name = '')
    {
        try {
            if (empty($approve_state)) return false;
            $this->load->model('Oversea_logistics_cfg_history_model', 'm_cfg_history_model', false, 'oversea');
            $this->load->model('Oversea_logistics_list_log_model', 'm_log');
            $processed  = 0;//已处理
            $undisposed = 0;//未处理
            $flag       = 0;
            if ($approve_state == 2) {
                $flag = 1;
            } elseif ($approve_state == 3) {
                $flag = 2;
            }

            foreach ($id as $key => $value) {
                //查询状态
                $this->db->select('approve_state,logistics_id');
                $this->db->where('id', $value);
                $this->db->from($this->table);
                $result = $this->db->get()->row_array();
                if ($result['approve_state'] == 1) {
                    $this->db->trans_start();
                    $this->db->where('id', $value);
                    $this->db->update($this->table, ['approve_state' => $approve_state, 'approve_uid' => $uid, 'approve_zh_name' => trim($user_name), 'approve_at' => date('Y-m-d H:i:s')]);
                    //写入日志
                    $this->m_log->checkLog($result['logistics_id'], $value, $uid, $user_name, $flag);
                    if ($approve_state == 2) {
                        $this->check_approve_state_delete($value);
                    }
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
            throw new \RuntimeException(sprintf('批量审核异常'), 500);
        }
    }

    /**
     * 批量修改
     */
    public function modifyByExcel($params = [], $user_id = '', $user_name = '')
    {
        try {
            $params['updated_uid']     = $user_id;
            $params['updated_zh_name'] = $user_name;
            $params['updated_at']      = date('Y-m-d H:i:s');
            $this->load->model('Oversea_logistics_cfg_history_model', 'm_cfg_history_model', false, 'oversea');
            $this->load->model('Oversea_logistics_list_log_model', 'm_log');
            //查询本来的属性
            $this->db->select('logistics_id,details_of_first_way_transportation,sku_state,approve_state,infringement_state,contraband_state,listing_state,product_status,mix_hair');
            $this->db->from($this->table);
            $this->db->where('id', $params['id']);
            $result_data = $this->db->get()->row_array();
            if (empty($result_data)) {
                return FALSE;
            }
            $old  = [
                'logistics_id' => $result_data['logistics_id'],
                'details_of_first_way_transportation' => $result_data['details_of_first_way_transportation'],
                'sku_state'    => $result_data['sku_state'],
                'product_status'    => $result_data['product_status'],
                'infringement_state'    => $result_data['infringement_state'],
                'mix_hair'    => $result_data['mix_hair'],
                'contraband_state'    => $result_data['contraband_state'],
//                'listing_state'    => $result_data['listing_state'],
                'refund_rate'    => $result_data['refund_rate'],
            ];
            $temp = [
                'logistics_id' => $params['logistics_id'],
                'details_of_first_way_transportation' => LOGISTICS_ATTR_TO_DETAILS_OF_FIRST_WAY_TRANSPORTATION[$params['logistics_id']],
                'sku_state'    => $params['sku_state'],
                'product_status'    => $params['product_status'],
                'mix_hair'    => $params['mix_hair'],
                'infringement_state'    => $params['infringement_state'],
                'contraband_state'    => $params['contraband_state'],
//                'listing_state'    => $params['listing_state'],
                'refund_rate'    => $params['refund_rate'],
            ];

            $diff  = [];
            $count = 0;
            //查询的结果和输入的进行比较是否有修改
            foreach ($old as $key => $value) {
                if ($old[$key] != $temp[$key]) {
                    $diff[$key] = $temp[$key];
                    $count++;
                }
            }

            if ($count == 0) {
                return FALSE;
            }
            //写入日志

            $this->db->trans_start();
            $this->m_log->modifyLog($diff, $params);
            //保留历史配置 如果状态为审核成功
            if ($result_data['approve_state'] == 2) {
                $this->check_approve_state_add($params['id']);
            }

            //状态变为待审核
            $params['approve_state'] = 1;
            $this->db->where('id', $params['id']);
            $this->db->update($this->table, $params);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return false;
            } else {
                return true;
            }

        } catch (Exception $e) {
            log_message('ERROR', sprintf('批量修改异常', $e->getMessage()));
            throw new \RuntimeException(sprintf('批量修改异常'), 500);
        }
    }

    /**
     *  获取可以审批的数据的ids
     */
    public function get_can_approve_data($limit=300){
        $approve_data = $this->db->select('id,approve_state,logistics_id')->from($this->table)->where('approve_state',1)->limit($limit)->get()->result_array();
        return $approve_data;
        //return empty($approve_data)?[]:array_column($approve_data,'id');
    }

    /**
     * 批量更新数据的审批状态
     */
    public function batch_update_approve_status($approve_data,$approve_result,$nums){
        $ids = empty($approve_data)?[]:array_column($approve_data,'id');
        $active_user = get_active_user();
        $uid = $active_user->staff_code;
        $user_name = $active_user->user_name;
        $this->db->trans_start();
        $update_data = [
            'approve_state'=>$approve_result,
            'approve_uid'=>$uid,
            'approve_zh_name'=>$user_name,
            'approve_at'=>date('Y-m-d H:i:s')
        ];
        $this->db->where_in('id', $ids);
        $this->db->update($this->table, $update_data);
        //添加审批日志
        $this->load->model('Oversea_logistics_list_log_model', 'm_log');
        $this->m_log->batchInsertLogData($approve_data,$uid,$user_name,$approve_result);

        //删除历史数据
        if($approve_result ==2){
            $this->check_approve_state_delete_all($ids);
        }
        $this->db->trans_complete();
        if ($this->db->trans_status() === FALSE) {
            log_message('Info', "第{$nums}次审批异常");
            return false;
        }
        return true;
    }

    /**
     * 批量修改运营状态
     */
    public function edit_listing_state($arr = array(),$listing=1){
        //提供所需修改总数
        $total = count($arr);
        //返回给前段下载的地址
        $web_file = "";
        //不成功的数据总数
        $undisposed = 0;
        //不符合修改条件的数据
        $sql_error = sprintf("SELECT 
                    `id`,
                    `sku`,
                    `infringement_state`,
                    `contraband_state`,
                    `station_code`,
                    `platform_code`,
                    `listing_state` 
                  FROM
                    `yibai_oversea_logistics_list` 
                  WHERE `id` IN ('%s') 
                    AND (`infringement_state` = %d 
                    OR `contraband_state` = %d )",implode("','", $arr),INFRINGEMENT_STATE_YES,CONTRABAND_STATE_YES);
        $result_error_data = $this->getDatabase()->query($sql_error)->result_array();

        //不符合条件的写入csv文件中提供下载
        if (!empty($result_error_data)){
            $reletive_path = date('Ymd') . DIRECTORY_SEPARATOR .date('Hi').'_错误文件.csv';
            $file_path = get_export_path() . $reletive_path;
            $dir_path = get_export_path().date('Ymd') . DIRECTORY_SEPARATOR;
            is_dir($dir_path) or mkdir($dir_path,0700);
            //需要转换映射
            $this->load->service('basic/DropdownService');
            $station_code   = $this->dropdownservice->dropdown_oversea_station_code();//海外站点映射
            $listing_state  = $this->dropdownservice->dropdown_listing_state(); //运营状态映射
            $platform_state  = $this->dropdownservice->dropdown_inland_platform_code(); //平台
            //不符合条件的数据总数
            $undisposed = count($result_error_data);
            //写入文件
            $handle = fopen($file_path, 'w+');
            if (!$handle) {
                return array(
                    'msg'   => '导出目录没有写入权限',
                    'filepath' => ''
                );
            }

            fputcsv($handle, ['sku','海外站点','平台','运营状态','失败原因']);
            $current = 2;

            foreach ($result_error_data as $v){
                $error_sea = "";
                if ($v['infringement_state'] == INFRINGEMENT_STATE_YES || $v['contraband_state'] == CONTRABAND_STATE_YES){
                    $error_sea = "侵权和违禁的sku不能修改成运营中";
                }
                fputcsv($handle, [$v['sku'],$station_code[$v['station_code']],$platform_state[strtoupper($v['platform_code'])],$listing_state[$v['listing_state']],$error_sea]);
                $current++;
            }
            fclose($handle);
            $web_file = EXPORT_DOWNLOAD_URL . '/' . $reletive_path;
        }


        //修改符合条件的数据
        $sql = sprintf("
            UPDATE `yibai_oversea_logistics_list` SET listing_state='%d' WHERE `id` IN ('%s') AND  `infringement_state` = %d AND `contraband_state` = %d;
",$listing,implode("','", $arr),INFRINGEMENT_STATE_NO,CONTRABAND_STATE_NO);

        $res = $this->getDatabase()->query($sql);
        if ($res){
            return array(
                'total' => $total,
                'undisposed'  =>$undisposed,
                'filepath'   =>  $web_file,
            );
        }else{
            return false;
        }
    }


}
