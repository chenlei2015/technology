<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/5
 * Time: 13:37
 */
class Fba_logistics_list_model extends MY_Model
{
    private $_ci;
    public function __construct()
    {
        $this->table_name = 'yibai_fba_logistics_list';
        $this->table      = 'yibai_fba_logistics_list';
        $this->_ci        =& get_instance();
        parent::__construct();

    }

    /**
     * 获取物流属性配置列表
     */
    public function getLogisticsList($params = [], $id = [], $get_count = '')
    {
        if (!is_array($params)) return FALSE;
        $offset = (($params['offset'] > 0 ? $params['offset'] : 1) - 1) * $params['limit'];
        $limit = $params['limit'];

        $this->db->select('*');
        if (!empty($id)) {
            $this->db->where_in('id', $id);
            $this->db->from($this->table);
            $this->db->limit($params['length']);
            $this->db->offset($offset);
            $this->db->order_by('created_at', 'DESC');
            $result            = $this->db->get()->result_array();
            $result            = $this->joinGlobal($result);//全局规则匹配全局的配置
            $data['data_list'] = $result;
            return $data;
        }

        //加上权限限制
        if (isset($params['set_data_scope']))
        {
            //如果是管理员 就能看到他管理的账号
            if (isset($params['prev_account_name']) && isset($params['prev_salesman'])) {//销售人员只能看到自己的, 上级可以看到自己和所有下级的账号

                //salesman or account_name
                $this->db->group_start();
                $this->db->or_where("salesman_id", $params['prev_salesman']);
                //如果选择account_name那么则取交集
                if (isset($params['account_name'])) {
                    $prev_select = explode(',', $params['prev_account_name']);
                    $page_select = explode(',', $params['account_name']);
                    $account_select = array_values(array_intersect($prev_select, $page_select));
                } else {
                    $account_select = explode(',', $params['prev_account_name']);
                }

                if (count($account_select) == 1) {
                    $this->db->or_where("account_name", $account_select[0]);
                } elseif (count($account_select) > 1) {
                    $this->db->or_where_in("account_name", $account_select);
                }
                //end or group
                $this->db->group_end();
            }
            elseif (isset($params['prev_salesman']))
            {
                $this->db->where("salesman_id", $params['prev_salesman']);
            }
            elseif (isset($params['prev_account_name']))
            {
                if (count($sns = explode(',', $params['prev_account_name'])) > 1)
                {
                    $this->db->where_in("account_name", $sns);
                }
                else
                {
                    $this->db->where("account_name", $sns[0]);
                }
            }
        }


        if (isset($params['salesman_id'])) {
            $this->db->where('salesman_id', $params['salesman_id']);
        }
        if (isset($params['account_name'])) {
            $this->db->where('account_name', $params['account_name']);
        }

        if (isset($params['sale_group_id'])) {
            $this->db->where('sale_group_id', $params['sale_group_id']);
        }

        if (isset($params['station_code'])) {
            $this->db->where('station_code', $params['station_code']);
        }
        if (isset($params['logistics_id'])) {
            $this->db->where('logistics_id', $params['logistics_id']);
        }
        if (isset($params['is_first_sale'])) {
            $this->db->where('is_first_sale', $params['is_first_sale']);
        }
        if (isset($params['sku_state'])) {
            $this->db->where('sku_state', $params['sku_state']);
        }
        if (!empty($params['created_at_start']) && !empty($params['created_at_end'])) {
            $this->db->where('created_at >=', $params['created_at_start'] . ' 00:00:00');
            $this->db->where('created_at <=', $params['created_at_end'] . ' 23:59:59');
        }
        if (!empty($params['approved_at_start']) && !empty($params['approved_at_end'])) {
            $this->db->where('approve_at >=', $params['approved_at_start'] . ' 00:00:00');
            $this->db->where('approve_at <=', $params['approved_at_end'] . ' 23:59:59');
        }
        if (!empty($params['updated_at_start']) && !empty($params['updated_at_end'])) {
            $this->db->where('updated_at >=', $params['updated_at_start'] . ' 00:00:00');
            $this->db->where('updated_at <=', $params['updated_at_end'] . ' 23:59:59');
        }
        if (isset($params['listing_state'])) {
            $this->db->where('listing_state', $params['listing_state']);
        }
        if (isset($params['seller_sku'])) {
            $this->db->where_in('seller_sku', trimArray($params['seller_sku']));
        }
        if (isset($params['sku'])) {
            $this->db->where_in('sku',  trimArray($params['sku']));
        }
        if (isset($params['asin'])) {
            $this->db->where_in('asin', trimArray($params['asin']));
        }
        if (isset($params['fnsku'])) {
            $this->db->where_in('fnsku', trimArray($params['fnsku']));
        }
        if (isset($params['approve_state'])) {
            $this->db->where('approve_state', $params['approve_state']);
        }
        if (isset($params['station_code'])) {
            $this->db->where('station_code', $params['station_code']);
        }
        if (isset($params['rule_type'])) {
            $this->db->where('rule_type', $params['rule_type']);
        }
        if (isset($params['purchase_warehouse_id'])) {
            $this->db->where('purchase_warehouse_id', $params['purchase_warehouse_id']);
        }

        $this->db->from($this->table);
        $db    = clone $this->db;
        $total = $db->count_all_results();//获取总条数
        if (!empty($get_count)) {
            $data['count'] = $total;

            return $data;
        }
        unset($db);
        $this->db->order_by('created_at', 'DESC');
        $this->db->order_by('id', 'DESC');
        //暂存
        $query_export = clone $this->db;
        $this->_ci->load->library('rediss');
        $this->_ci->load->service('basic/SearchExportCacheService');
        $total_l = str_pad((string)$total, 10, '0', STR_PAD_LEFT);
        $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_LOGISTICS_LIST_SEARCH_EXPORT)->set($total_l.$query_export->get_compiled_select('',false));

        if (!empty($params['length']) && isset($params['start'])) {
            $this->db->limit($params['length'], $params['start']);
        } else {
            $this->db->limit($limit);
        }
        $this->db->offset($offset);
//        echo $this->db->get_compiled_select();exit;
        $result            = $this->db->get()->result_array();
        $result            = $this->joinGlobal($result);//全局规则匹配全局的配置
        $data['data_list'] = $result;
//        if(!empty($data['data_list'])){
//            $data['data_list'] = $this->highlight($data['data_list']);
//        }

        //同一站点、账号、asin 但seller_sku不同的需高亮显示

        $data['data_page'] = array(
            'limit' => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total' => (float)$total
        );
        return $data;
    }

    /**
     * 全局的属性从全局配置表获取
     *
     * @param $result
     *
     * @return mixed
     */
    public function joinGlobal($result = [])
    {
        //将全局表所有信息查出来
        $this->db->select('*');
        $this->db->from('yibai_fba_global_rule_cfg');
        $g_result = $this->db->get()->result_array();
        //键名为站点名
        foreach ($g_result as $k => $val) {
            $all_station[$val['station_code']] = $val;
        }

        //更新规则类型为全局的数据
        foreach ($result as $key => $value) {
            $station_code = $value['station_code'];
            //根据物流属性找对应的 配置字段
            $logistics_id = $value['logistics_id'];
            if ($value['rule_type'] == RULE_TYPE_GLOBAL && isset($all_station[$station_code])) {
                if ($logistics_id == LOGISTICS_ATTR_SHIPPING_BULK) {//海运散货
                    $value['ls'] = $all_station[$station_code]['ls_shipping_bulk'];
                    $value['pt'] = $all_station[$station_code]['pt_shipping_bulk'];

                } elseif ($logistics_id == LOGISTICS_ATTR_AIR) {//空运
                    $value['ls'] = $all_station[$station_code]['ls_air'];
                    $value['pt'] = $all_station[$station_code]['pt_air'];
                }
                $value['as_up'] = $all_station[$station_code]['as_up'];
                $value['bs']    = $all_station[$station_code]['bs'];
                $value['sc']    = $all_station[$station_code]['sc'];
                $value['sz']    = $all_station[$station_code]['sz'];
                $result[$key]   = $value;
            } else {
                continue;
            }
        }

        return $result;
    }

    public function global_cfg()
    {
        //将全局表所有信息查出来
        $this->db->select('*');
        $this->db->from('yibai_fba_global_rule_cfg');
        $g_result = $this->db->get()->result_array();
        //键名为站点名
        if (!empty($g_result)) {
            foreach ($g_result as $k => $val) {
                $all_station[$val['station_code']] = $val;
            }
        }

        return $all_station;
    }

    /**
     * 同一站点、账号、asin 但seller_sku不同的需高亮显示
     */
//    public function highlight($data = [])
//    {
///*        $data = [];
//        $data[0] = ['station_code'=>'jp','account_id'=>'111','asin'=>'aaa','seller_sku'=>'qq1'];
//        $data[1] = ['station_code'=>'jp','account_id'=>'111','asin'=>'bbb','seller_sku'=>'qq1'];
//        $data[2] = ['station_code'=>'jp','account_id'=>'111','asin'=>'aaa','seller_sku'=>'qq2'];
//        $data[3] = ['station_code'=>'jp','account_id'=>'111','asin'=>'aaa','seller_sku'=>'qq1'];*/
//
//        $count = count($data);
//        foreach ($data as $key => &$item){
//            if(!isset($item['highlight'])){
//                $item['highlight'] = 0;//默认为0
//            }
//            for ($i=$key+1;$i<$count;$i++){
//                if($data[$i]['station_code']==$item['station_code']&&$data[$i]['account_id']==$item['account_id']&&$data[$i]['asin']==$item['asin']){
//                    if($data[$i]['seller_sku'] != $item['seller_sku']){
//                        $item['highlight'] = 1;//当前高亮
//                        $data[$i]['highlight'] = 1;//其他相同的也高亮
//                    }
//                }
//            }
//        }
//        return $data;
//    }

    /**
     * 同一站点、账号、asin 但seller_sku不同的需高亮显示
     * station_code,account_name, asin
     */
    public function highlight($data = [])
    {
        $all_highlight = $this->db->select('hash')->get('yibai_fba_logistics_highlight')->result_array();

        foreach ($data as $key => &$item) {
            $hash = md5($item['station_code'].$item['account_name'].$item['asin']);
            if(in_array($hash,array_column($all_highlight,'hash'))){
                $item['highlight'] = 1;
            }else{
                $item['highlight'] = 0;
            }
        }
        return $data;
    }



    /**
     * 详情页
     */
    public function getLogisticsDetails($id = '')
    {

        $this->db->select('*');
        $this->db->where('id', $id);
        $this->db->from($this->table);
        $result[0]           = $this->db->get()->row_array();
        $result              = $this->joinGlobal($result);//全局规则匹配全局的配置
        $data['data_detail'] = $result[0];
        return $data;
    }

    /**
     * 修改物流属性
     */
    public function modify($params = [])
    {
        $this->load->model('Fba_logistics_cfg_history_model', 'm_cfg_history_model', false, 'fba');
        $this->load->model('Fba_logistics_list_log_model', 'm_log');
        $this->db->trans_start();
        //查询本来的属性
        $this->db->select('logistics_id,listing_state,approve_state,purchase_warehouse_id');
        $this->db->from($this->table);
        $this->db->where('id', $params['id']);
        $result = $this->db->get()->row_array();
        if (empty($result)) {
            $result['code'] = 0;
            $result['errorMess'] = '未找到此条记录的信息,修改失败';
            return $result;
        }
        //如果用户没有修改 则按原数据处理
        if (empty($params['logistics_id'])) {
            $params['logistics_id'] = $result['logistics_id'];
        }
        if (empty($params['listing_state'])) {
            $params['listing_state'] = $result['listing_state'];
        }
        $old = [
            'logistics_id' => $result['logistics_id'],
            'listing_state' => $result['listing_state'],
        ];

        $temp = [
            'logistics_id' => $params['logistics_id'],
            'listing_state' => $params['listing_state'],
        ];

        $diff = [];
        $count = 0;
        //查询的结果和输入的进行比较是否有修改
        foreach ($old as $key => $value) {
            if ($old[$key] != $temp[$key]) {
                $diff[$key] = $temp[$key];
                $count++;
            }
        }
        if ($count == 0) {//没有修改直接返回
            $result['code'] = 0;
            $result['errorMess'] = '您未作任何修改，无法保存！';
            return $result;
        }

        //保留历史配置 如果状态为审核成功
        if($result['approve_state'] == 2){
            if(!$this->check_approve_state_add($params['id'])){
                $result['code'] = 0;
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
            $result['errorMess'] = '修改失败';
        } else {
            $result['code'] = 1;
            $result['errorMess'] = '修改成功';
        }
        return $result;
    }

    /**
     *
     * 在保留历史配置表里新增一条记录
     * @param string $id
     * @param array $old_global_cfg
     * @return bool
     */
    public function check_approve_state_add($id = '')
    {
        if(empty($id)){
            return FALSE;
        }
        $info = $this->db->select('id,logistics_id,purchase_warehouse_id,listing_state,as_up,ls,pt,bs,sc,sz,expand_factor,approve_state,refund_rate,approve_uid,approve_zh_name,approve_at,updated_uid,updated_zh_name,updated_at,remark,created_at')->from($this->table)->where('id', $id)->get()->row_array();
        if($info['approve_state']==2){//审核成功状态保留配置
            unset($info['approve_state']);
            return $this->m_cfg_history_model->insert($info);
        }
    }

    public function check_approve_state_delete($id = '')
    {
        if(empty($id)){
            return FALSE;
        }
        return $this->m_cfg_history_model->clean($id);
    }

    /**
     * 批量审核成功
     *
     * @param array $id
     * @param string $uid
     *
     * @return mixed
     */
    public function batchCheckSuccess($id = [], $uid = '', $user_name = '')
    {
        try {
            $this->load->model('Fba_logistics_cfg_history_model', 'm_cfg_history_model', false, 'fba');
            $this->load->model('Fba_logistics_list_log_model', 'm_log', false, 'fba');
            $processed = 0;//已处理
            $undisposed = 0;//未处理
            foreach ($id as $key => $value) {
                //查询状态
                $this->db->select('id,approve_state');
                $this->db->where('id', $value);
                $this->db->from($this->table);
                $result = $this->db->get()->row_array();
                if ($result['approve_state'] == 1) {
                    $this->db->trans_start();
                    $this->db->where('id', $value);
                    $this->db->update($this->table, ['approve_state' => 2, 'approve_uid' => $uid, 'approve_zh_name' => $user_name, 'approve_at' => date('Y-m-d H:i:s')]);

                    //写入日志
                    $this->m_log->checkLog($result, $flag = 1);
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
            $data['processed'] = $processed;
            $data['undisposed'] = $undisposed;
            return $data;
        } catch (Exception $e) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $e->getMessage();
            http_response($this->data);
        }

    }

    /**
     * 批量审核失败
     * @param array $id
     * @param string $uid
     * @return mixed
     */
    public function batchCheckFail($id = [], $uid = '', $user_name = '')
    {
        try {
            $this->load->model('Fba_logistics_list_log_model', 'm_log');
            $processed = 0;//已处理
            $undisposed = 0;//未处理
            foreach ($id as $key => $value) {
                //查询状态
                $this->db->select('id,approve_state');
                $this->db->where('id', $value);
                $this->db->from($this->table);
                $result = $this->db->get()->row_array();
                if ($result['approve_state'] == 1) {
                    $this->db->trans_start();
                    $this->db->where('id', $value);
                    $this->db->update($this->table, ['approve_state' => 3, 'approve_uid' => $uid, 'approve_zh_name' => $user_name, 'approve_at' => date('Y-m-d H:i:s')]);
//                    //获取配置详情
//                    $detail = $this->getOne($value);
                    //写入日志
                    $this->m_log->checkLog($result, $flag = 2);

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
            $data['processed'] = $processed;
            $data['undisposed'] = $undisposed;
            return $data;
        } catch (Exception $e) {
            $this->data['status'] = 0;
            $this->data['errorMess'] = $e->getMessage();
            http_response($this->data);
        }
    }

    /**
     * 更新备注
     */
    public function updateRemark($data)
    {
        $this->db->where('id', $data['log_id']);
        $this->db->update($this->table, ['remark' => $data['remark']]);
    }


    /**
     * 修改物流属性
     */
    public function modifyByExcel($params = [], $user_id = '', $user_name = '')
    {
        try {
            $params['updated_uid'] = $user_id;
            $params['updated_zh_name'] = $user_name;
            $params['updated_at'] = date('Y-m-d H:i:s');
            $this->load->model('Fba_logistics_cfg_history_model', 'm_cfg_history_model', false, 'fba');
            $this->load->model('Fba_logistics_list_log_model', 'm_log');
            $this->db->trans_start();
            //查询本来的属性
            $this->db->select('logistics_id,listing_state,approve_state');
            $this->db->from($this->table);
            $this->db->where('id', $params['id']);
            $result = $this->db->get()->row_array();
            if (empty($result)) {
                return FALSE;
            }
            $old = [
                'logistics_id' => $result['logistics_id'],
                'listing_state' => $result['listing_state'],
            ];
            $temp = [
                'logistics_id' => $params['logistics_id'],
                'listing_state' => $params['listing_state'],
            ];

            $diff = [];
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
            $this->m_log->modifyLog($diff, $params);
            //保留历史配置 如果状态为审核成功
            if($result['approve_state'] == 2) {
                $this->check_approve_state_add($params['id']);
            }
            //状态变为待审核
            $params['approve_state'] = 1;
            $this->db->where('id', $params['id']);
            $this->db->update($this->table, $params);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return FALSE;
            } else {
                return TRUE;
            }

        } catch (Exception $e) {
            log_message('ERROR', sprintf('批量修改异常', $e->getMessage()));
            throw new \RuntimeException(sprintf('批量修改异常'), 500);
        }
    }

    /*
     * 更新物流方式,将excel中的数据去更新表,改为海运散货,其他为0的改为空运
     * 2019/08/14
     */
    public function update_logistics($params)
    {

        $this->db->where($params);
        $this->db->set('logistics_id', 2);
        $this->db->set('approve_state', 2);
        $sql = $this->db->get_compiled_update($this->table);

        return $sql;
    }

    public function pk($id)
    {
        $result = $this->_db->from($this->table_name)->where('id', $id)->limit(1)->get()->result_array();

        return $result ? $result[0] : [];
    }

    public function get_cfg($id)
    {
        if (empty($id)) {
            return [];
        }
        $result = $this->db->select('a.seller_sku,a.rule_type,a.expand_factor,a.ls,a.pt,a.bs,a.sc,a.sz,a.as_up,a.purchase_warehouse_id,a.listing_state,a.listing_state_text,a.logistics_id,a.id,a.refund_rate')
            ->from($this->table_name . ' a')
//            ->join('yibai_fba_sku_cfg b','a.sku = b.sku','left')
            ->where_in('a.id', $id)
            ->get()
            ->result_array();

        if (!empty($result)) {
            $result = array_column($result, NULL, 'id');
        } else {
            $result = false;
        }

        return $result;
    }

    /**
     * 刷新csv上传活动中可以出入的记录
     *
     * @param array $aggr_ids
     * @param string $salesman *|W01206
     * @param array $accounts manager
     * @param Object $csvwrite
     * @return array|NULL[]
     */
    public function get_can_update($ids, $salesman, $accounts = [], $csvwrite)
    {
        if (empty($ids)) return [];

        //sellersku目前无需做权限控制

        $query = $this->db->select('a.seller_sku,a.rule_type,a.expand_factor,a.ls,a.pt,a.bs,a.sc,a.sz,a.as_up,a.purchase_warehouse_id,a.listing_state,a.logistics_id,a.id, a.updated_uid, a.updated_zh_name,a.approve_state,a.listing_state_text,a.refund_rate')
        ->from($this->table_name . ' a')
        ->where_in('a.id', $ids);

        $result = $query->get()->result_array();

        //只检测时间是否冲突，至于有没有修改值不检测
        if (empty($result)) {
            return [];
        }

        $valide = [];

        $line_index_name = $csvwrite->get_line_index_name();

        foreach ($result as $row) {
            //可以更新的记录，记录行号
            $row[$line_index_name] = $csvwrite->selected[$row['id']][$line_index_name];
            $valide[$row['id']] = $row;
        }

        unset($result);

        return $valide;
    }

    /**
     * 查询该记录的seller_sku 下的所有sku的是否退税
     */
    public function check_is_refund_tax($seller_sku)
    {
        $result = $this->db->select('a.id,b.is_refund_tax')
            ->from($this->table_name . ' a')
            ->join('yibai_fba_sku_cfg b', 'a.sku = b.sku', 'left')
            ->where_in('a.seller_sku', $seller_sku)
//            ->where('is_refund_tax !=',REFUND_TAX_YES)
            ->get()
            ->result_array();
        if (empty($result)) {
            return false;
        } else {
            $result = array_column($result, 'is_refund_tax', 'id');

            return $result;
        }
    }


    /**
     * 获取sellersku对应的sku的退税类型，二维数组
     *
     * @param array $seller_skus
     * @return boolean|array
     */
    public function get_sellersku_sku_refundtax(array $seller_skus)
    {
        $result = $this->db->select('a.seller_sku, a.id,b.is_refund_tax')
        ->from($this->table_name . ' a')
        ->join('yibai_fba_sku_cfg b', 'a.sku = b.sku', 'left')
        ->where_in('a.seller_sku', $seller_skus)
        ->get()
        ->result_array();
        if (empty($result)) {
            return false;
        } else {
            $format = [];
            foreach ($result as $res) {
                $format[$res['seller_sku']][$res['id']] = $res['is_refund_tax'];
            }
            unset($result);
            return $format;
        }
    }

    public function get_info_by_account_sku_asin($data = [])
    {
        $this->db->select('id,is_first_sale');
        $this->db->where('account_id', $data['account_id']);
        $this->db->where('seller_sku', $data['seller_sku']);
        $this->db->where('sku', $data['sku']);
        $this->db->where('fnsku', $data['fnsku']);
        $this->db->where('asin', $data['asin']);
        $this->db->from($this->table);
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    public function update_by_id($id,$data)
    {
        $this->db->where('id', $id);
        $this->db->update($this->table, $data);
        return $this->db->affected_rows();
    }

    public function batch_update($batch_params)
    {
        return $this->_db->update_batch($this->table_name, $batch_params, 'id');
    }

    public function madd($params)
    {
        return $this->_db->insert_batch($this->table_name, $params);
    }

    public function get_product_info($data)
    {
        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库
        $batch_sku  = array_column($data, 'sku');
        $sku_str      = "'" . implode("','",$batch_sku ) . "'";
        $sql = "SELECT p.sku,descrip.title FROM yibai_product p
                       left join yibai_product_description as descrip on (descrip.sku=p.sku and descrip.language_code='Chinese')
                       WHERE p.sku IN ({$sku_str})";
        $title_map = $this->product->query($sql)->result_array();
        $product_info = [];
        foreach ($title_map as $key => $item) {
            $product_info[$item['sku']] = [
                'title' => $item['title'],
            ];
        }
        return $product_info;
    }

    public function pks($ids)
    {
        $result = $this->_db->from($this->table_name)->where_in('id', $ids)->get()->result_array();
        return $result;
    }

    public function get_infos_by_column($data = [])
    {
        $this->_db->select('id');
        $this->_db->from($this->table_name);
        return $this->_db->get()->result_array();
    }

    public function update_column($data)
    {
        $this->_db->update($this->table_name, $data);
        return $this->_db->affected_rows();
    }
}