<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * 海外仓 需求列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-08
 * @link
 */
class Oversea_pr_list_model extends MY_Model implements Rpcable
{
    use Table_behavior, Rpc_imples;

    private $_rpc_module = 'oversea';

    private $date_gid_key = '';

    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_oversea_pr_list';
        $this->primaryKey = 'gid';
        $this->tableId = 72;
        $this->date_rebuild_gid_key = 'oversea_rebuild_end_gid_'.date('Y_m_d');
        parent::__construct();
    }

    /**
     *
     * @return unknown
     */
    public function all()
    {
        $query = $this->_db->get($this->table_name);
        $result = $query->result_array();
        return $result;
    }

    /**
     * 获取可以一级审核的列表记录
     *
     * @param unknown $accounts
     */
    public function get_can_approve_for_first($gids)
    {
        if (empty($gids)) return [];

        if ($this->is_rpc($this->_rpc_module))
        {
            $cb = function($result, $map) {
                $my = [];
                if (isset($result['data']['items']) && $result['data']['items'])
                {
                    foreach ($result['data']['items'] as $res)
                    {
                        $tmp = [];
                        foreach ($res as $col => $val)
                        {
                            $tmp[$map[$col] ?? $col] = $val;
                        }
                        $my[] = $tmp;
                    }
                }
                return $my;
            };
            $params = [
                    'gid' => $gids,
                    'approve_state' => APPROVAL_STATE_FIRST,
                    'expired' => FBA_PR_EXPIRED_NO,
                    /*'is_trigger_pr' => TRIGGER_PR_YES,*/
            ];
            return RPC_CALL('YB_J2_OVERSEA_002', $params, $cb, ['debug' => 1]);
        }
        $result = $this->_db->from($this->table_name)
        ->where_in('gid', $gids)
        ->where('approve_state', APPROVAL_STATE_FIRST)
        ->where('expired', FBA_PR_EXPIRED_NO)
        /*->where('is_trigger_pr', TRIGGER_PR_YES)*/
        ->limit(count($gids))
        ->get()
        ->result_array();
        return $result;
    }

    public function get_today_pr_total()
    {
        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $query = $this->_db->from($this->table_name)
            ->select('count(*) as nums')
            ->where('created_at >', $today_start)
            ->where('created_at <', $today_end)
            ->where('approve_state', OVERSEA_APPROVAL_STATE_INIT);

        $result = $query->get()->result_array();
        return $result[0]['nums'];
    }

    /**
     * 因追加了头程虚拟仓记录，并且不需要重建。
     *
     * @return unknown|unknown[]
     */
    public function get_rebuild_top_bottom_gid()
    {
        $ci = CI::$APP;
        $ci->load->library('Rediss');
        $gid_scope = $ci->rediss->getData($this->date_rebuild_gid_key);

        if ($gid_scope) {
            return $gid_scope;
        }

        $first_virtual_warehouse_gid = $this->get_begin_virtual_warehouse_gid();

        $gidArr = $this->get_top_bottom_gid();
        if ($first_virtual_warehouse_gid == '') {
            return $gidArr;
        } else {
            $ar = str_split($first_virtual_warehouse_gid, strlen($first_virtual_warehouse_gid) - 9);
            $ar[1] = str_pad(intval($ar[1]) - 1, 9, '0', STR_PAD_LEFT);
            $max_normal_warehouse_gid = implode('', $ar);

            $gid_scope = ['min_gid' => $gidArr['min_gid'], 'max_gid' => $max_normal_warehouse_gid];
            $ci->rediss->setData($this->date_rebuild_gid_key, $gid_scope, strtotime(date('Y-m-d').' 23:59:59') - time());
            return $gid_scope;
        }
    }

    /**
     * 获取第一条虚拟仓的gid，但是有可能没有
     *
     * @return string
     */
    public function get_begin_virtual_warehouse_gid()
    {
        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $result = $this->_db->from($this->table_name)
            ->select('gid')
            ->where('purchase_warehouse_id', PURCHASE_WAREHOUSE_TCXNC)
            ->where('created_at >', $today_start)
            ->where('created_at <', $today_end)
            ->limit(1)
            ->get()
            ->result_array();

        return empty($result) ? '' : $result[1]['gid'];

    }

    /**
     * 批量更新
     */
    public function batch_update_compatible($batch_params)
    {
        $collspac_batch_params = [];
        foreach($batch_params as $state => $rows)
        {
            $collspac_batch_params = array_merge($collspac_batch_params, $rows);
        }
        if ($this->is_rpc($this->_rpc_module))
        {
            $cb = function($result, $map) {
                if ($result['code'] != '200')
                {
                    log_message('ERROR', sprintf('RPC_CALL ERROR, 错误信息：%s', $result['message']));
                    throw new \RuntimeException('Java接口执行失败', 500);
                }
                return true;
            };

            return RPC_CALL('YB_J2_OVERSEA_002', $batch_params, $cb);
        }
        return $this->_db->update_batch($this->table_name, $collspac_batch_params, 'gid');
    }

    /**
     * 根据主键获取记录，支持两种模式
     * @desc rpc, local
     * @param unknown $gid
     * @return string|array
     */
    public function find_by_pk($gid)
    {
        if ($this->is_rpc($this->_rpc_module))
        {
            $cb = function($result, $map) {
                if (isset($result['data']) && $result['data'])
                {
                    $my = [];
                    foreach ($result['data'] as $col => $val)
                    {
                        $my[$map[$col] ?? $col] = $val;
                    }
                }
                return $my;
            };

            return RPC_CALL('YB_J2_OVERSEA_002', ['gid' => $gid], $cb);
        }
        return $this->pk($gid);
    }


    public function pk($gid)
    {
        $result = $this->_db->from($this->table_name)->where('gid', $gid)->limit(1)->get()->result_array();
        if ($result)
        {
            return $result[0];
        }
        else
        {
            log_message('ERROR', sprintf('Oversea_pr_list_model 根据主键: %s获取记录失败, 当前数据库：%s', $gid, json_encode(array_keys(self::$_dbCaches))));
            return [];
        }
    }

    /**
     * 更新bd
     *
     * @desc rpc、local
     * @param Record $record
     * @return string|unknown
     */
    public function update_bd(?Record $record)
    {
        if ($this->is_rpc($this->_rpc_module))
        {
            $cb = function($result, $map) {
                if ($result['respCode'] != '0000')
                {
                    log_message('ERROR', sprintf('RPC_CALL ERROR, 错误信息：%s', $result['message']));
                }
                return $result['respCode'] == '0000' ? 1 : 0;
            };
            return RPC_CALL('YB_J2_OVERSEA_003', $this->_ci->Record->report($this->_ci->Record::REPORT_FULL_ARR), $cb);
        }
        return $record->update();
    }

    /**
     * @version v1.1.2 拆分为平台和站点列表之后，获取审核的都采用sql
     * @version v1.1.7 对触发需求的记录进行汇总,
     * @version v1.2.0 需求数量>0触发需求审核, 增加权限控制,
     *
     * @param string $create_date 创建时间
     * @param array $owner_privileges 权限值
     */
    public function get_can_approve_for_second($create_date = '', $owner_privileges = [])
    {
        $created_start = $create_date == '' ? strtotime(date('Y-m-d')) : strtotime($create_date);
        $created_end = $created_start + 86400;

        $query = $this->_db->from($this->table_name)
        ->where('display', OVERSEA_STATION_DISPLAY_YES)
        ->where('created_at >', $created_start)
        ->where('created_at <', $created_end)
        ->where_in('is_boutique', [BOUTIQUE_YES, BOUTIQUE_NO])
        ->where('approve_state', APPROVAL_STATE_SECOND)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('is_addup', PR_IS_ADDUP_NO)
        ->where('require_qty >', 0);

        if (!isset($owner_privileges['*']))
        {
            $query->where_in('station_code', isset($owner_privileges[0]) ? $owner_privileges : array_keys($owner_privileges));
        }

        return $query->get()->result_array();
    }

    /**
     * 查找sku信息
     * @param unknown $gids
     */
    public function find_sku_info_by_gids($gids)
    {

        $rows = $this->_db->select('sku, sku_name')->from($this->table_name)->where_in('gid', $gids)->order_by('gid', 'asc')->get()->result_array();
        $info = [];
        foreach ($rows as $row)
        {
            $info[$row['sku']] = $row['sku_name'];
        }
        return $info;
    }

    /**
     * 超过昨天最后时间，修改为过期
     */
    public function handel_expired(){
        //小于前一天23:59:59 且未过期的数据都修改为过期
        $yesterday_start = strtotime(date('Ymd',strtotime('-2day')));
        $yesterday_end = strtotime(date('Ymd'))-1;
        $this->_db->from($this->table_name);
        $this->_db->where_in('display', [OVERSEA_STATION_DISPLAY_NO, OVERSEA_STATION_DISPLAY_YES]);
        $this->_db->where('created_at >=',$yesterday_start);
        $this->_db->where('created_at <=', $yesterday_end);
        $this->_db->where('expired',FBA_PR_EXPIRED_NO);
        $this->_db->update($this->table_name,['expired'=>FBA_PR_EXPIRED_YES]);
    }

    /**
     * 获取可以修改的记录
     *
     * @param array $pr_sns 需求列表
     * @param array $accounts 账号列表  传值则查询
     * @param string $salesman 传值则查询，与上面是union
     * @return array
     */
    public function get_can_bd($pr_sns)
    {
        if (empty($pr_sns)) return [];

        $query = $this->_db->from($this->table_name)
        ->select('gid,pr_sn,bd,require_qty,point_pcs,purchase_qty,available_qty,exchange_up_qty,oversea_ship_qty,stocked_qty,station_code')
        ->where_in('pr_sn', $pr_sns)
        ->where_in('approve_state', [APPROVAL_STATE_FIRST, APPROVAL_STATE_FAIL])
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->limit(count($pr_sns));

        $result = $query->get()->result_array();

        return $result;
    }

    /**
     * 物流类型:在需求列表中,根据备货跟踪单号获取物流属性
     */
    public function getLogistics($pr_sn)
    {
        $result =  $this->_db->select('pr_sn,logistics_id')->from($this->table_name)
            ->where_in('pr_sn',$pr_sn)
            ->get()->result_array();
        return array_column($result,'logistics_id','pr_sn');
    }

    public function get_today_used_pr_sns($sn_prefix, $cut_length)
    {
        $today_start = mktime(0, 0, 0, intval(date('m')), intval(date('d')), intval(date('y')));
        $today_end = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('y')));

        $max_sum_sn = $this->_db->select('pr_sn')
            ->from($this->table_name)
            ->where('created_at >= ', $today_start)
            ->where('created_at <= ', $today_end)
            ->order_by('pr_sn', 'desc')
            ->limit(1)
            ->get()
            ->result_array();

        if (empty($max_sum_sn))
        {
            return [];
        }
        $start = strlen($sn_prefix);
        $seq_char = substr($max_sum_sn[0]['pr_sn'], $start, $cut_length);
        $result = $this->_db->select('pr_sn')->from($this->table_name)->like('pr_sn', $sn_prefix.$seq_char.'%')->get()->result_array();
        if (empty($result)) {
            return [];
        }
        $pr_sns = array_column($result, 'pr_sn');
        sort($pr_sns);
        return [$seq_char, $pr_sns];
    }

    public function exists_today_data() : bool
    {
        list($min_gid, $max_gid) = $this->get_top_bottom_gid();
        return $min_gid != '';
    }

    public function get_rebuild_gid($start_gid, $chunk_size = 500)
    {
        //gid偏移
        $query = $this->_db->from($this->table_name)
            ->select('*')
            ->where('gid >=', $start_gid)
            ->limit($chunk_size);

        //pr($query->get_compiled_select('', false));exit;
        $result = $query->get()->result_array();

        return $result;
    }

    public function get_top_bottom_gid()
    {
        $ci = CI::$APP;
        $ci->load->library('Rediss');
//        $gid_scope = $ci->rediss->getData($this->date_gid_key);

//        if ($gid_scope) {
//            return $gid_scope;
//        }

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $result = $this->_db->from($this->table_name)
            ->select('min(gid) as min_gid, max(gid) as max_gid')
            ->where('created_at >', $today_start)
            ->where('created_at <', $today_end)
            ->get()
            ->result_array();
//var_dump($this->_db->queries);
//die;
        $gid_scope = [$result[0]['min_gid'], $result[0]['max_gid']];
        if ($result[0]['min_gid'] == '' || $result[0]['max_gid'] == '') {
            return $gid_scope;
        }

        $ci->rediss->setData($this->date_gid_key, $gid_scope, strtotime(date('Y-m-d').' 23:59:59') - time());
        return $gid_scope;
    }

    public function sampling($end_point, $chunk_size = 500)
    {
        $this->_db->query('set @index=-1');
        $sql = sprintf('select gid
            from %s
            where gid >= "%s" and gid <= "%s" and ((@INDEX := @INDEX + 1) > -1) and (@INDEX %% %d = 0)
            order by gid asc',
            $this->table_name,
            $end_point[0], $end_point[1], $chunk_size
        );
        $result = $this->_db->query($sql)->result_array();
        return $result;
    }
}