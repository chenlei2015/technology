<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * fba 需求列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link
 */
class Fba_pr_list_model extends MY_Model implements Rpcable
{

    use Table_behavior, Rpc_imples;

    private $_rpc_module = 'fba';

    private $date_gid_key = '';

    private $date_pr_nums_key = '';

    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_fba_pr_list';
        $this->primaryKey = 'gid';
        $this->tableId = 92;
        $this->date_gid_key = 'fba_start_end_gid_'.date('Y_m_d');
        $this->date_pr_nums_key = 'fba_pr_nums_'.date('Y_m_d');

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
            log_message('ERROR', sprintf('Fba_pr_list_model 根据主键: %s获取记录失败, 当前数据库：%s', $gid, json_encode(array_keys(self::$_dbCaches))));
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

            return RPC_CALL('YB_J2_OVERSEA_002', ['gid' => $gid], $cb);
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

            return RPC_CALL('YB_J2_OVERSEA_002', $input_params, $cb);
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

            return RPC_CALL('YB_J2_OVERSEA_002', $batch_params, $cb);
        }
        return $this->_db->update_batch($this->table_name, $collspac_batch_params, 'gid');
    }

    /**
     * 不带兼容方式的批量更新
     *
     * @param unknown $batch_params
     * @return unknown
     */
    public function batch_update($batch_params)
    {
        return $this->_db->update_batch($this->table_name, $batch_params, 'gid');
    }

    /**
     * 获取可以一级审核的列表记录
     *
     * v1.1.2 变更：
     * 所有需求单必须一级审核
     *
     * @version 1.2.0 增加欧洲站必须设置目的国
     *          1.2.0 增加退税检测，
     *          1.2.0 增加listing状态 2 下架品 、3 清仓品 不能审核 （1 在售品、2 下架品 、3 清仓品4 新产品 ）
     *          1.2.2 增加不能审核的原因
     * @param unknown $accounts
     */
    public function get_can_approve_for_first($gids, $accounts)
    {
        if (empty($gids) || empty($accounts)) return [];

        $query = $this->_db->from($this->table_name)
            ->where_in('gid', $gids)
            ->where('approve_state', APPROVAL_STATE_FIRST)
            ->where('expired', FBA_PR_EXPIRED_NO)
            ->where('is_refund_tax !=', REFUND_TAX_UNKNOWN)
            ->where('sku_state !=', SKU_STATE_DOWN)
            ->where('sku_state !=', SKU_STATE_CLEAN)
            ->where('listing_state', LISTING_STATE_OPERATING)
            ->where('require_qty >', 0)
            ->limit(count($gids));
        if (is_array($accounts))
        {
            $query->where_in('account_name', $accounts);
        }

        //5、停售SKU除了每周二其他时间不能审核。
        if (date('N') == 2)
        {
            $query->where_in('deny_approve_reason', [DENY_APPROVE_NONE, DENY_APPROVE_HALT_SKU]);
        }
        else
        {
            $query->where('deny_approve_reason', DENY_APPROVE_NONE);
        }

        $result = $query->get()->result_array();

        return $result;
    }


    /**
     * 获取可以批量审核的记录
     *
     * @param unknown $start_gid
     * @param unknown $accounts
     * @param number $chunk_size
     * @return array|unknown
     */
    public function get_can_approve_for_first_all($start_gid, $accounts, $chunk_size = 300)
    {
        if (empty($accounts) || empty($accounts)) return [];

        $query = $this->_db->from($this->table_name)
        ->select(
            'gid, pr_sn, sku, sku_name, weight_sale_pcs, expect_exhaust_date, require_qty, is_trigger_pr, is_plan_approve, approve_state, is_addup, is_refund_tax, purchase_warehouse_id, purchase_price, max_sp, moq_qty, purchase_qty,'.
            'logistics_id, country_code, is_boutique, station_code,fnsku,seller_sku,asin,account_id, account_name,bd,stocked_qty,sale_group,salesman, is_first_sale,supplier_code'
            )
        ->where('approve_state', APPROVAL_STATE_FIRST)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('is_refund_tax !=', REFUND_TAX_UNKNOWN)
        ->where('sku_state !=', SKU_STATE_DOWN)
        ->where('sku_state !=', SKU_STATE_CLEAN)
        ->where('listing_state', LISTING_STATE_OPERATING)
        ->where('require_qty >', 0)
        ->where('gid >', $start_gid)
        ->limit($chunk_size);
        if (is_array($accounts))
        {
            $query->where_in('account_name', $accounts);
        }

        //5、停售SKU除了每周二其他时间不能审核。
        if (date('N') == 2)
        {
            $query->where_in('deny_approve_reason', [DENY_APPROVE_NONE, DENY_APPROVE_HALT_SKU]);
        }
        else
        {
            $query->where('deny_approve_reason', DENY_APPROVE_NONE);
        }
        //pr($query->get_compiled_select('', false));exit;
        $result = $query->get()->result_array();
        return $result;
    }

    /**
     * 必须具备全部数据权限
     *
     * @return number
     */
    public function get_fba_total_money($date, $salesman_uid = -1, $accounts = '*')
    {
        return $this->get_fba_approve_first_money($date, $accounts) + $this->get_fba_approve_second_money($date, $salesman_uid) + $this->get_fba_approve_three_money($date, $salesman_uid);
    }

    /**
     * @param string $start_gid
     * @param array $accounts
     * @return float
     */
    public function get_fba_approve_first_money($date, $accounts)
    {
        list($start_gid, $end_gid) = $this->get_top_bottom_gid();
        if ($start_gid == '' || $end_gid == '') {
            return 0;
        }

        //获取所有一级审核金额
        $query = $this->_db->from($this->table_name)
        ->select('sum(purchase_price * require_qty) as total')
            ->where('approve_state', APPROVAL_STATE_FIRST)
            //->where('expired', FBA_PR_EXPIRED_NO)
            ->where('is_refund_tax !=', REFUND_TAX_UNKNOWN)
            ->where('sku_state !=', SKU_STATE_DOWN)
            ->where('sku_state !=', SKU_STATE_CLEAN)
            ->where('listing_state', LISTING_STATE_OPERATING)
            ->where('require_qty >', 0)
            ->where('gid >=', $start_gid)
            ->where('gid <=', $end_gid);

            if (is_array($accounts))
            {
                $query->where_in('account_name', $accounts);
            }

            //5、停售SKU除了每周二其他时间不能审核。
            if (date('N') == 2)
            {
                $query->where_in('deny_approve_reason', [DENY_APPROVE_NONE, DENY_APPROVE_HALT_SKU]);
            }
            else
            {
                $query->where('deny_approve_reason', DENY_APPROVE_NONE);
            }
            //pr($query->get_compiled_select('', false));exit;
            $result = $query->get()->result_array();
            return $result[0]['total'];
    }



    public function get_total_can_approve_for_first($start_gid, $accounts)
    {
        if (empty($accounts) || empty($accounts)) return 0;

        $query = $this->_db->from($this->table_name)
            ->select('count(*) as nums')
            ->where('approve_state', APPROVAL_STATE_FIRST)
            //->where('expired', FBA_PR_EXPIRED_NO)
            ->where('is_refund_tax !=', REFUND_TAX_UNKNOWN)
            ->where('sku_state !=', SKU_STATE_DOWN)
            ->where('sku_state !=', SKU_STATE_CLEAN)
            ->where('listing_state', LISTING_STATE_OPERATING)
            ->where('require_qty >', 0)
            ->where('gid >', $start_gid);

            if (is_array($accounts))
            {
                $query->where_in('account_name', $accounts);
            }
            //5、停售SKU除了每周二其他时间不能审核。
            if (date('N') == 2)
            {
                $query->where_in('deny_approve_reason', [DENY_APPROVE_NONE, DENY_APPROVE_HALT_SKU]);
            }
            else
            {
                $query->where('deny_approve_reason', DENY_APPROVE_NONE);
            }
            //pr($query->get_compiled_select('', false));exit;
            $result = $query->get()->result_array();
            return $result[0]['nums'];
    }

    public function get_total_approve_first_succ($accounts)
    {
        if (empty($accounts) || empty($accounts)) return 0;

        list($min_gid, $max_gid) = $this->get_top_bottom_gid();

        $query = $this->_db->from($this->table_name)
        ->select('count(*) as nums')
        ->where('approve_state', APPROVAL_STATE_SECOND)
        //->where('expired', FBA_PR_EXPIRED_NO)
        ->where('is_refund_tax !=', REFUND_TAX_UNKNOWN)
        ->where('sku_state !=', SKU_STATE_DOWN)
        ->where('sku_state !=', SKU_STATE_CLEAN)
        ->where('listing_state', LISTING_STATE_OPERATING)
        ->where('require_qty >', 0)
        ->where('gid >=', $min_gid);

        if (is_array($accounts))
        {
            $query->where_in('account_name', $accounts);
        }
        //5、停售SKU除了每周二其他时间不能审核。
        if (date('N') == 2)
        {
            $query->where_in('deny_approve_reason', [DENY_APPROVE_NONE, DENY_APPROVE_HALT_SKU]);
        }
        else
        {
            $query->where('deny_approve_reason', DENY_APPROVE_NONE);
        }
        $result = $query->get()->result_array();
        return $result[0]['nums'];
    }

    /**
     * 获取可以二级审核列表, 如果只有个人数据权限，那么只能查看自己的
     *
     * @param unknown $pr_sns
     */
    public function get_can_approve_for_second_all($start_gid, $salesman_uid = -1, $chunk_size = 300)
    {
        $query = $this->_db->from($this->table_name)
        ->where('approve_state', APPROVAL_STATE_SECOND)
        ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('gid >', $start_gid)
        ->limit($chunk_size);

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        //pr($query->get_compiled_select('', false));exit;
        $result = $query->get()->result_array();

        return $result;
    }

    public function get_fba_approve_second_money($date, $salesman_uid)
    {
        list($start_gid, $end_gid) = $this->get_top_bottom_gid();
        if ($start_gid == '' || $end_gid == '') {
            return 0;
        }
        $query = $this->_db->from($this->table_name)
        ->select('sum(purchase_price * require_qty) as total')
        ->where('approve_state', APPROVAL_STATE_SECOND)
        ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('gid >=', $start_gid)
        ->where('gid <=', $end_gid);

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        //pr($query->get_compiled_select('', false));exit;
        $result = $query->get()->result_array();

        return $result[0]['total'];
    }

    public function get_total_can_approve_for_second($start_gid, $salesman_uid = -1)
    {
        $query = $this->_db->from($this->table_name)
        ->select('count(*) as nums')
        ->where('approve_state', APPROVAL_STATE_SECOND)
        ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('gid >', $start_gid);

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        //pr($query->get_compiled_select('', false));exit;
        $result = $query->get()->result_array();

        return $result[0]['nums'];
    }


    public function get_total_approve_second_succ($salesman_uid = -1)
    {
        list($min_gid, $max_gid) = $this->get_top_bottom_gid();

        $query = $this->_db->from($this->table_name)
        ->select('count(*) as nums')
        ->where('approve_state', APPROVAL_STATE_THREE)
        ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('gid >=', $min_gid);

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        $result = $query->get()->result_array();

        return $result[0]['nums'];
    }

    public function get_can_approve_for_three_all($start_gid, $salesman_uid = -1, $chunk_size = 300)
    {
        $query = $this->_db->from($this->table_name)
        ->where('approve_state', APPROVAL_STATE_THREE)
        ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('gid >', $start_gid)
        ->limit($chunk_size);

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        $result = $query->get()->result_array();

        return $result;
    }

    public function get_total_can_approve_for_three($start_gid, $salesman_uid = -1)
    {
        $query = $this->_db->from($this->table_name)
        ->select('count(*) as nums')
        ->where('approve_state', APPROVAL_STATE_THREE)
        ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('gid >', $start_gid);

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        $result = $query->get()->result_array();

        return $result[0]['nums'];
    }

    public function get_total_approve_three_succ($salesman_uid = -1)
    {
        list($min_gid, $max_gid) = $this->get_top_bottom_gid();

        $query = $this->_db->from($this->table_name)
        ->select('count(*) as nums')
        ->where('approve_state', APPROVAL_STATE_SUCCESS)
        ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('gid >', $min_gid);

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        $result = $query->get()->result_array();

        return $result[0]['nums'];
    }

    public function get_fba_approve_three_money($date, $salesman_uid)
    {
        list($start_gid, $end_gid) = $this->get_top_bottom_gid();
        if ($start_gid == '' || $end_gid == '') {
            return 0;
        }

        $query = $this->_db->from($this->table_name)
        ->select('sum(purchase_price * require_qty) as total')
        ->where('approve_state', APPROVAL_STATE_SUCCESS)
        ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
        //->where('expired', FBA_PR_EXPIRED_NO)
        ->where('gid >=', $start_gid)
        ->where('gid <=', $end_gid);

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        //pr($query->get_compiled_select('', false));exit;
        $result = $query->get()->result_array();

        return $result[0]['total'];
    }

    /**
     * 获取可以二级审核列表, 如果只有个人数据权限，那么只能查看自己的
     *
     * @param unknown $pr_sns
     */
    public function get_can_approve_for_second($gids, $salesman_uid = -1)
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
                    'approve_state' => APPROVAL_STATE_SECOND,
                    'is_plan_approve' => NEED_PLAN_APPROVAL_YES,
                    'expired' => FBA_PR_EXPIRED_NO
            ];
            if ($salesman_uid != -1)
            {
                $params['salesman'] = $salesman_uid;
            }
            return RPC_CALL('YB_J3_FBA_004', $params, $cb);
        }

        $query = $this->_db->from($this->table_name)
            ->where_in('gid', $gids)
            ->where('approve_state', APPROVAL_STATE_SECOND)
            ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
            ->where('expired', FBA_PR_EXPIRED_NO)
            ->limit(count($gids));

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        $result = $query->get()->result_array();

        return $result;
    }

    /**
     * 获取可以三级审核列表, 如果只有个人数据权限，那么只能查看自己的
     *
     * @param unknown $pr_sns
     */
    public function get_can_approve_for_three($gids, $salesman_uid = -1)
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
                    'approve_state' => APPROVAL_STATE_THREE,
                    'is_plan_approve' => NEED_PLAN_APPROVAL_YES,
                    'expired' => FBA_PR_EXPIRED_NO
            ];
            if ($salesman_uid != -1)
            {
                $params['salesman'] = $salesman_uid;
            }
            return RPC_CALL('YB_J3_FBA_005', $params, $cb);
        }

        $query = $this->_db->from($this->table_name)
            ->where_in('gid', $gids)
            ->where('approve_state', APPROVAL_STATE_THREE)
            ->where('is_plan_approve', NEED_PLAN_APPROVAL_YES)
            ->where('expired', FBA_PR_EXPIRED_NO)
            ->limit(count($gids));

        if ($salesman_uid != -1)
        {
            $query->where('salesman', $salesman_uid);
        }
        $result = $query->get()->result_array();

        return $result;
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
        $this->_db->where('created_at >=',$yesterday_start);
        $this->_db->where('created_at <=', $yesterday_end);
        $this->_db->where('expired',FBA_PR_EXPIRED_NO);
        $this->_db->update($this->table_name,['expired'=>FBA_PR_EXPIRED_YES]);

    }

    /**
     * 获取可以修改的记录
     *
     * @v1.2.0 只有销售才能修改自己的记录
     *
     * @param array $pr_sns 需求列表
     * @param array $accounts 账号列表  传值则查询
     * @param string $salesman 传值则查询，与上面是union
     * @return array
     */
    public function get_can_bd($pr_sns, $salesman)
    {
        if (empty($pr_sns)) return [];

        $query = $this->_db->from($this->table_name)
            ->select('gid,pr_sn,sku,bd,require_qty,point_pcs,purchase_qty,available_qty,exchange_up_qty,oversea_ship_qty,stocked_qty,ext_trigger_info,weight_sale_pcs,is_trigger_pr, trigger_mode, is_lost_active_trigger,country_code, station_code')
            ->where_in('pr_sn', $pr_sns)
            ->where_in('approve_state', [APPROVAL_STATE_FIRST, APPROVAL_STATE_FAIL])
            ->where('expired', FBA_PR_EXPIRED_NO)
            ->where('salesman', $salesman)
            ->limit(count($pr_sns));

        $result = $query->get()->result_array();

        return $result;
    }


    /**
     * 获取可以修改的记录
     *
     * @v1.2.2 上级可修改
     *
     * @param array $pr_sns 需求列表
     * @param array $accounts 账号列表  传值则查询
     * @param string $salesman 传值则查询，与上面是union
     * @return array
     */
    public function get_can_fixed_amount($pr_sns, $salesman, $accounts = [])
    {
        if (empty($pr_sns)) return [];

        $query = $this->_db->from($this->table_name)
        ->select('gid,pr_sn,sku,fixed_amount,bd,require_qty,point_pcs,purchase_qty,available_qty,exchange_up_qty,oversea_ship_qty,stocked_qty,ext_trigger_info,weight_sale_pcs,is_trigger_pr, trigger_mode, is_lost_active_trigger, expand_factor, is_plan_approve, is_accelerate_sale,accelerate_sale_end_time')
        ->where_in('pr_sn', $pr_sns)
        ->where_in('approve_state', [APPROVAL_STATE_FIRST, APPROVAL_STATE_FAIL])
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->limit(count($pr_sns));

        if ($salesman != '*')
        {
            //salesman or account_name
            if (!empty($accounts))
            {
                $query->group_start();
                $query->or_where("salesman", $salesman);

                if (count($accounts) == 1 )
                {
                    $query->or_where("account_name", $accounts[0]);
                }
                elseif(count($accounts) > 1 )
                {
                    $query->or_where_in("account_name", $accounts);
                }
                $query->group_end();
            }
            else
            {
                $query->where('salesman', $salesman);
            }
        }
        $result = $query->get()->result_array();
        return $result;
    }

    /**
     * 根据gid获取扩展属性
     *
     * @param unknown $gids
     * @return unknown
     */
    public function get_extends_logistics_info($gids)
    {
        $query = $this->_db->from($this->table_name)->select('gid,ext_logistics_info');
        if (count($gids) == 1)
        {
            $query->where('gid', $gids[0]);
        }
        else
        {
            $query->where_in('gid', $gids);
        }
        return $query->get()->result_array();
    }

    /**
     * 获取指定sku中存在主动记录的sku
     *
     * @param string|array $skus
     * @return array
     */
    public function get_active_sku($skus)
    {
        $sku_arrs = is_string($skus) ? explode(',', $skus) : $skus;

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $query = $this->_db->from($this->table_name)
        ->select('sku')
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('created_at >= ', $today_start)
        ->where('created_at <=', $today_end)
        ->where_in('approve_state', [APPROVAL_STATE_FIRST, APPROVAL_STATE_SUCCESS]);

        if (count($sku_arrs) == 1)
        {
            $query->where('sku', $sku_arrs[0]);
        }
        else
        {
            $query->where('sku', $sku_arrs);
        }
        return key_by($query->get()->result_array(), 'sku');
    }

    /**
     * 获取因失去主动记录对应sku关联被动触发的列表, 排除BD的在外
     *
     * @param string|array $skus
     * @return array
     */
    public function get_inactive_trigger_list($skus)
    {
        $sku_arrs = is_string($skus) ? explode(',', $skus) : $skus;

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;
        $query = $this->_db->from($this->table_name)
        ->select('gid')
        ->where('trigger_mode', TRIGGER_MODE_INACTIVE)
        ->where('created_at >= ', $today_start)
        ->where('created_at <=', $today_end)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('is_trigger_pr', TRIGGER_PR_YES)
        ->where('fixed_amount', 0)
        ->where('approve_state', APPROVAL_STATE_FIRST);

        if (count($sku_arrs) == 1)
        {
            $query->where('sku', $sku_arrs[0]);
        }
        else
        {
            $query->where_in('sku', $sku_arrs);
        }
        return $query->get()->result_array();
    }

    /**
     * 获取sku因失去主动记录而被降级的列表, 排除手动BD修改（不管增还是减）的和审核失败的
     *
     * @param string|array $skus
     * @return array
     */
    public function get_trigger_down_list($skus)
    {
        $sku_arrs = is_string($skus) ? explode(',', $skus) : $skus;

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;
        $query = $this->_db->from($this->table_name)
        ->select('gid')
        ->where('trigger_mode', TRIGGER_MODE_NONE)
        ->where('is_lost_active_trigger', TRIGGER_LOST_ACTIVE_YES)
        ->where('created_at >= ', $today_start)
        ->where('created_at <=', $today_end)
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->where('is_trigger_pr', TRIGGER_PR_NO)
        ->where('fixed_amount', 0)
        ->where('approve_state', APPROVAL_STATE_FIRST);

        if (count($sku_arrs) == 1)
        {
            $query->where('sku', $sku_arrs[0]);
        }
        else
        {
            $query->where_in('sku', $sku_arrs);
        }
        return $query->get()->result_array();
    }

    /**
     * sku的统计信息
     *
     * @param array $skus
     * @return array
     */
    public function &get_trigger_sku_summary_info($skus)
    {
        $format_trigger_sku_rows = [];

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        //SQL查询只存在被动记录的
        $sql = sprintf(
            'select sku, trigger_mode, group_concat(gid) as gids, count(*) as num from %s where sku in (%s) and expired = %d and created_at >= %d and created_at <= %d and approve_state != %d '.
            'group by sku, trigger_mode order by null',
            $this->table_name,
            array_where_in($skus),
            FBA_PR_EXPIRED_NO,
            $today_start,
            $today_end,
            APPROVAL_STATE_FAIL
        );

        $trigger_sku_rows = $this->_db->query($sql)->result_array();

        if (empty($trigger_sku_rows)) return $format_trigger_sku_rows;

        foreach ($trigger_sku_rows as $row)
        {
            $format_trigger_sku_rows[$row['sku']][$row['trigger_mode']] = [
                    'num' => $row['num'],
                    'gids' => $row['gids'],
            ];
            if ($row['trigger_mode'] == TRIGGER_MODE_ACTIVE)
            {
                $format_trigger_sku_rows[$row['sku']][$row['trigger_mode']]['other_num'] = $row['num'];
                $format_trigger_sku_rows[$row['sku']][$row['trigger_mode']]['other_gids'] = $row['gids'];
            }
        }
        $trigger_sku_rows = NULL;
        unset($trigger_sku_rows);
        return $format_trigger_sku_rows;
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

    /**
     * 获取重建数量
     *
     * @param string $version 上一次执行的偏移 gid和sku
     * @param number $chunk_size 分批数量
     * @return array
     */
    public function get_rebuild_chunk($version, $chunk_size = 1000, $start_gid = '')
    {
        $start_gid = strval($start_gid);
        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        //gid偏移
        $query = $this->_db->from($this->table_name.' force index(union_rebuild_pr)')
        ->select('*')
        ->where('version !=', $version)
        ->where('created_at >', $today_start)
        ->where('created_at <', $today_end)
        ->where('approve_state', APPROVAL_STATE_FIRST)
        ->limit($chunk_size);

        if ($start_gid != '') {
            $query->where('gid >', $start_gid);
        }
        //pr($query->get_compiled_select('', false));
        //log_message('ERROR', sprintf('获取重建记录列表：%s', $query->get_compiled_select('', false)));
        $result = $query->get()->result_array();

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

    public function get_debug_rebuild_by_sns($pr_sns)
    {
        return $this->_db->from($this->table_name)->where_in('pr_sn', $pr_sns)->get()->result_array();
    }

    public function get_today_pr_total()
    {

        $ci = CI::$APP;
        $ci->load->library('Rediss');
        $total = $ci->rediss->getData($this->date_pr_nums_key);
        $total = intval($total);

        if (intval($total) > 0) {
            return $total;
        }

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;

        $query = $this->_db->from($this->table_name)
        ->select('count(*) as nums')
        ->where('created_at >', $today_start)
        ->where('created_at <', $today_end);

        //->where_in('is_boutique', [BOUTIQUE_YES, BOUTIQUE_NO])
        //->where_in('listing_state', [LISTING_STATE_OPERATING, LISTING_STATE_STOP_OPERATE])
        //->where('approve_state', APPROVAL_STATE_FIRST);

        $result = $query->get()->result_array();

        $total = intval($result[0]['nums']);

        if ($total > 0) {
            $ci->rediss->setData($this->date_pr_nums_key, $total, strtotime(date('Y-m-d').' 23:59:59') - time());
        }
        return $total;
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

    public function update_rebuld_trigger_unknown_mode($version)
    {
        list($min_gid, $max_gid) = $this->get_top_bottom_gid();

        //$today_start = strtotime(date('Y-m-d'));
        //$today_end = $today_start + 86400;

        //被动模式未定的模式被更新为了5， 这是一个临时状态。
        /*$sql = sprintf(
            'update %s pr
        left join (
        	 SELECT
        		distinct sku
        	 from %s
        	 where gid >= "%s" and gid <= "%s" and trigger_mode = %d and version = %d
        ) active on pr.sku = active.sku
        set pr.is_lost_active_trigger = IF(active.sku, %d, %d),
            pr.is_trigger_pr = IF(active.sku, %d, %d),
            pr.trigger_mode = IF(active.sku, %d, %d),
            pr.require_qty = IF(active.sku, pr.purchase_qty * pr.expand_factor, (pr.point_pcs + pr.bd + pr.fixed_amount + pr.purchase_qty - pr.available_qty - pr.exchange_up_qty - pr.oversea_ship_qty ) * expand_factor)
        where pr.gid >= "%s" and pr.gid < "%s" and pr.trigger_mode = %d and pr.version = %d',
            $this->table_name,
            $this->table_name,
            $min_gid, $max_gid, TRIGGER_MODE_ACTIVE, $version,
            TRIGGER_LOST_ACTIVE_NORMAL, TRIGGER_LOST_ACTIVE_YES,
            TRIGGER_PR_YES, TRIGGER_PR_NO,
            TRIGGER_MODE_INACTIVE, TRIGGER_MODE_NONE,
            $min_gid, $max_gid, TRIGGER_MODE_REBUILD_TEMP, $version
            );*/

        //$this->_db->trans_start();

        $update_inactive_rows = $update_rows = 0;

        $current_time = time();

        //更新主动的
        $update_inactive_sql = sprintf(
            'update yibai_fba_pr_list pr
             set pr.is_lost_active_trigger = %d,
            		pr.is_trigger_pr = %d,
            		pr.trigger_mode = %d,
            	    pr.updated_at = %d,
            		pr.require_qty = floor(pr.purchase_qty * pr.expand_factor)
             where  pr.gid >= "%s" and pr.gid < "%s" and pr.trigger_mode = %d and pr.version = %d and approve_state = %d
                and pr.sku in (
                	select wp.sku from (
                		SELECT active.sku from yibai_fba_pr_list active
                		where active.gid >= "%s" and active.gid <= "%s" and active.trigger_mode = %d and active.version = %d
                	) wp )',
            TRIGGER_LOST_ACTIVE_NORMAL, TRIGGER_PR_YES, TRIGGER_MODE_INACTIVE, $current_time,
            $min_gid, $max_gid, TRIGGER_MODE_REBUILD_TEMP, $version, APPROVAL_STATE_FIRST,
            $min_gid, $max_gid,TRIGGER_MODE_ACTIVE,$version
            );

        $this->_db->trans_start();
        if (false === $this->_db->query($update_inactive_sql)) {
            $this->_db->rollback();
            log_message('ERROR', '更新符合被动的记录失败, 需要手动介入修复：sql '.$update_inactive_sql);
            return false;
        }

        $update_inactive_rows = $this->_db->affected_rows();
        $this->_db->trans_complete();
        log_message('INFO', '更新符合被动的记录条数成功'.$update_inactive_rows);

        $update_none_sql = sprintf(
            'update yibai_fba_pr_list pr
             set pr.is_lost_active_trigger = %d,
            		pr.is_trigger_pr = %d,
            		pr.trigger_mode = %d,
            	    pr.updated_at = %d,
            		pr.require_qty = (pr.point_pcs + pr.bd + pr.fixed_amount + pr.purchase_qty - pr.available_qty - pr.exchange_up_qty - pr.oversea_ship_qty ) * expand_factor
             where  pr.gid >= "%s" and pr.gid < "%s" and pr.trigger_mode = %d and pr.version = %d and approve_state = %d',
            TRIGGER_LOST_ACTIVE_YES, TRIGGER_PR_NO, TRIGGER_MODE_NONE, $current_time,
            $min_gid, $max_gid, TRIGGER_MODE_REBUILD_TEMP, $version, APPROVAL_STATE_FIRST
            );

        $this->_db->trans_start();
        if (false === $this->_db->query($update_none_sql)) {
            $this->_db->rollback();
            log_message('ERROR', '更新被动但无主动记录的记录失败， 需要手动介入修复：sql '.$update_none_sql);
            return false;
        }

        $update_rows = $this->_db->affected_rows();
        $this->_db->trans_complete();
        log_message('INFO', '更新被动的记录但无主动记录条数成功'.$update_rows);

        return $update_inactive_rows + $update_rows;

        /*$sql = sprintf(
            ' update %s pr
        left join (
        	SELECT
        		trg_5.gid
        	from (
        			select
        					gid, sku
        			from %s
        			where gid >= "%s" and gid <= "%s" and trigger_mode = %d and version = %d
        	) trg_5
        	where exists(
        		select sku from %s main  where gid > "%s" and gid < "%s" and trigger_mode = %d and version = %d
            and trg_5.sku = main.sku
        	)
        ) active on pr.gid = active.gid
        set pr.is_lost_active_trigger = IF(active.gid, %d, %d),
        		pr.is_trigger_pr = IF(active.gid, %d, %d),
        		pr.require_qty = IF(active.gid, (pr.point_pcs + pr.bd + pr.fixed_amount + pr.purchase_qty - pr.available_qty - pr.exchange_up_qty - pr.oversea_ship_qty ) * expand_factor, pr.purchase_qty * pr.expand_factor),
        	  pr.trigger_mode = IF(active.gid, %d, %d)
        where pr.gid >= "%s" and pr.gid <= "%s" and pr.trigger_mode = %d and pr.version = %d
            ',
            $this->table_name,
            $this->table_name,
            $min_gid, $max_gid, TRIGGER_MODE_REBUILD_TEMP, $version,
            $this->table_name,$min_gid, $max_gid, TRIGGER_MODE_ACTIVE, $version,
            TRIGGER_LOST_ACTIVE_NORMAL, TRIGGER_LOST_ACTIVE_YES,
            TRIGGER_PR_YES, TRIGGER_PR_NO,
            TRIGGER_MODE_INACTIVE,TRIGGER_MODE_NONE,
            $min_gid, $max_gid, TRIGGER_MODE_REBUILD_TEMP, $version
            );*/

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

    public function get_info_by_dimension($data)
    {
        $result = $this->_db->from($this->table_name)
                            ->select('gid,is_first_sale,require_qty_second,require_qty')
                            ->where('account_name', $data['account_name'])
                            ->where('station_code', $data['station_code'])
                            ->where('fnsku', $data['fnsku'])
                            ->where('asin', $data['asin'])
                            ->where('seller_sku', $data['seller_sku'])
                            ->get()->result_array();
        //echo $this->_db->last_query();die;
        return $result;
    }
}