<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * Inland 需求列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link
 */
class Inland_pr_list_model extends MY_Model implements Rpcable
{

    use Table_behavior, Rpc_imples;

    private $_rpc_module = 'inland';

    private $date_gid_key = '';

    private $date_rebuild_gid_key = '';

    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_inland_pr_list';
        $this->primaryKey = 'gid';
        $this->tableId = 45;
        $this->date_gid_key = 'inland_start_end_gid_'.date('Y_m_d');
        $this->date_rebuild_gid_key = 'inland_rebuild_end_gid_'.date('Y_m_d');
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

    public function pk($gid)
    {
        $result = $this->_db->from($this->table_name)->where('gid', $gid)->limit(1)->get()->result_array();
        if ($result)
        {
            return $result[0];
        }
        else
        {
            log_message('ERROR', sprintf('Inland_pr_list_model 根据主键: %s获取记录失败, 当前数据库：%s', $gid, json_encode(array_keys(self::$_dbCaches))));
            return [];
        }
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

            return RPC_CALL('YB_J2_INLAND_002', ['gid' => $gid], $cb);
        }
        return $this->pk($gid);
    }

    /**
     * 兼容rpc更新
     *
     * @desc rpc、local
     * @param Record $record
     * @return string|unknown
     */
    public function update_compatible(?Record $record)
    {
        if ($this->is_rpc($this->_rpc_module))
        {
            $cb = function($result, $map) {
                if ($result['code'] != '200')
                {
                    log_message('ERROR', sprintf('RPC_CALL ERROR, 错误信息：%s', $result['message']));
                    throw new \RuntimeException('Java接口执行失败', 500);
                }
                return $result['respCode'] == '0000' ? 1 : 0;
            };
            $input_params = $this->_ci->Record->report($this->_ci->Record::REPORT_FULL_ARR);
            $input_params['gid'] = $record->gid;

            return RPC_CALL('YB_J2_INLAND_002', $input_params, $cb);
        }
        return $record->update();
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

            return RPC_CALL('YB_J2_INLAND_002', $batch_params, $cb);
        }
        return $this->_db->update_batch($this->table_name, $collspac_batch_params, 'gid');
    }

    /**
     * 默认自动审核， 获取当天全部的数据
     *
     * @param unknown $accounts
     */
    public function get_can_approve_for_first($gids, $accounts)
    {
        if (empty($gids)) return [];

        if ($this->is_rpc($this->_rpc_module))
        {
            $cb = function($result, $map) {
                $my = [];
                if (isset($result['data']) && $result['data'])
                {
                    foreach ($result['data'] as $res)
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
                    'is_trigger_pr' => TRIGGER_PR_YES,
            ];
            if (is_array($accounts))
            {
                $params['account_name'] = $accounts;
            }
            return RPC_CALL('YB_J3_INLAND_003', $params, $cb);
        }

        $query = $this->_db->from($this->table_name)
            ->where_in('gid', $gids)
            ->where('approve_state', INLAND_APPROVAL_STATE_INIT)
            ->where('expired', FBA_PR_EXPIRED_NO)
            ->where('is_trigger_pr', TRIGGER_PR_YES)
            ->limit(count($gids));
        if (is_array($accounts) && !empty($accounts))
        {
            $query->where_in('account_name', $accounts);
        }
        $result = $query->get()->result_array();

        return $result;
    }

    /**
     * 根据gid获取自动审核记录， 主要是用与生成跟踪列表
     *
     * @param unknown $gids
     * @param string $select 选择的列表
     * @return unknown
     */
    public function get_automatic_pr_by_gids($gids, $select = '*')
    {
        $select = 'gid,pr_sn,sku,is_refund_tax,purchase_warehouse_id, sku_name,require_qty,expect_exhaust_date,stocked_qty,is_trigger_pr';
        return $this->_db->select($select)->from($this->table_name)->where_in('gid', $gids)->get()->result_array();
    }

    public function get_info_by_sn($pr_sn)
    {
        $select = 'gid,fixed_amount,approve_state';
        return $this->_db->select($select)->from($this->table_name)->where('pr_sn', $pr_sn)->limit(1)->get()->row_array();
    }

    /**
     * 指定日期是否自动审核的记录
     *
     * @param string $date
     */
    public function has_automatic_pr($date = '')
    {
        if ($date != '')
        {
            $parse_date = date_parse_from_format('Y-m-d', $date);
            $today_start = mktime(0, 0, 0, intval($parse_date['month']), intval($parse_date['day']), intval($parse_date['year']));
            $today_end = mktime(23, 59, 59, intval($parse_date['month']), intval($parse_date['day']), intval($parse_date['year']));
        }
        else
        {
            $today_start = mktime(0, 0, 0, intval(date('m')), intval(date('d')), intval(date('y')));
            $today_end = mktime(23, 59, 59, intval(date('m')), intval(date('d')), intval(date('y')));
        }

        $query = $this->_db->select('count(gid) as count')
            ->from($this->table_name)
            ->where('is_addup', PR_IS_ADDUP_NO)
            ->where('created_at >= ', $today_start)
            ->where('created_at <= ', $today_end)
            ->where('is_trigger_pr', TRIGGER_PR_YES);
        $result = $query->get()->result_array();
        return $result[0]['count'];
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
            $info[$row['sku']]['sku_name'] = $row['sku_name'];
        }
        return $info;
    }

    /**
     * 超过昨天最后时间，修改为过期, 针对的对象是没有触发需求的。
     *
     * @param unknown $date * 全部日志， 指定日期
     * @return unknown
     */
    public function update_expired($date)
    {

        if ($date != '*')
        {
            $timestamp = strtotime($date);
            //设置指定日期
            $today_start = mktime(0, 0, 0, intval(date('m', $timestamp)), intval(date('d', $timestamp)), intval(date('Y', $timestamp)));
            $today_end = mktime(23, 59, 59, intval(date('m', $timestamp)), intval(date('d', $timestamp)), intval(date('Y', $timestamp)));
            $this->_db->where('created_at >= ', $today_start)->where('created_at <= ', $today_end);
        }
        else
        {
            //除了今天
            $today_start = mktime(0, 0, 0, intval(date('m')), intval(date('d')), intval(date('Y')));
            $this->_db->where('created_at < ', $today_start);
            $this->_db->where('created_at > ', $today_start - 86400);
        }
        $this->_db->where('expired', FBA_PR_EXPIRED_NO);
        //pr($this->_db->set(['expired' => FBA_PR_EXPIRED_YES])->get_compiled_update($this->table_name));exit;
        $this->_db->update($this->table_name, ['expired' => FBA_PR_EXPIRED_YES]);
        return $this->_db->affected_rows();
    }

    public function get_today_pr_total()
    {
        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $query = $this->_db->from($this->table_name)
        ->select('count(*) as nums')
        ->where('created_at >', $today_start)
        ->where('created_at <', $today_end)
        ->where('approve_state', INLAND_APPROVAL_STATE_INIT);

        $result = $query->get()->result_array();
        return $result[0]['nums'];
    }

    public function get_today_rebuild_done_active_skus($version)
    {
        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $query = $this->_db->from($this->table_name)
        ->select('distinct sku')
        ->where('created_at >', $today_start)
        ->where('created_at <', $today_end)
        ->where('version', $version);

        $result = $query->get()->result_array();
        return array_column($result, 'sku');
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


    public function get_top_bottom_gid()
    {
        $ci = CI::$APP;
        $ci->load->library('Rediss');
        $gid_scope = $ci->rediss->getData($this->date_gid_key);

        if ($gid_scope) {
            return $gid_scope;
        }

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $result = $this->_db->from($this->table_name)
        ->select('min(gid) as min_gid, max(gid) as max_gid')
        ->where('created_at >', $today_start)
        ->where('created_at <', $today_end)
        ->get()
        ->result_array();

        $gid_scope = [$result[0]['min_gid'], $result[0]['max_gid']];
        if ($result[0]['min_gid'] == '' || $result[0]['max_gid'] == '') {
            return $gid_scope;
        }

        $ci->rediss->setData($this->date_gid_key, $gid_scope, strtotime(date('Y-m-d').' 23:59:59') - time());
        return $gid_scope;
    }

    /**
     * 检测今天是否有记录
     * @return bool
     */
    public function exists_today_data() : bool
    {
        list($min_gid, $max_gid) = $this->get_top_bottom_gid();
        return $min_gid != '';
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

    public function get_checking_num()
    {
        return $this->_db->from($this->table_name)->where('approve_state', INLAND_APPROVAL_STATE_INIT)->count_all_results();
    }

    public function update_fixed_amount($data)
    {
        $update_data = [
            'fixed_amount' => $data['fixed_amount'],//需求数量
            'updated_at' => $data['updated_at'],//更新时间
            'updated_uid' => $data['updated_uid'],//更新uid
            'approve_state' => $data['approve_state']//审核状态
        ];
        $this->_db->where('gid', $data['gid']);
        $this->_db->update($this->table_name, $update_data);
        return $this->_db->affected_rows();
    }

    public function update_approving_state($data)
    {
        $update_data = [
            'approved_at' => $data['approved_at'],//更新时间
            'approved_uid' => $data['user_id'],//更新uid
            'approved_zh_name' => $data['user_name'],//更新uid
            'approve_state' => $data['result'],//审核状态
        ];
        $this->_db->where('approve_state', INLAND_APPROVAL_STATE_INIT);
        $this->_db->update($this->table_name, $update_data);
        return $this->_db->affected_rows();
    }
}