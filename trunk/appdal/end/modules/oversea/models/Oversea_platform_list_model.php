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
class Oversea_platform_list_model extends MY_Model implements Rpcable
{
    use Table_behavior, Rpc_imples;

    private $_rpc_module = 'oversea';

    private $date_gid_key = '';

    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_oversea_platform_list';
        $this->primaryKey = 'gid';
        $this->tableId = 31;
        $this->date_gid_key = 'oversea_start_end_gid_'.date('Y_m_d');
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
     * @version v1.2.0 增加退税未知不纳入审核
     *
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
        ->where('is_refund_tax !=', REFUND_TAX_UNKNOWN)
        ->limit(count($gids))
        ->get()
        ->result_array();
        return $result;
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


    public function batch_update_caslock($batch_params, $batch_size = 500)
    {
        //构建sql语句执行
        $total = count($batch_params);
        if ($total > $batch_size)
        {
            $step_batch_params = array_chunk($batch_params, $batch_size);
        }
        foreach ($step_batch_params as $one_batch)
        {
            $batch_sql = [];
            foreach ($one_batch as $one_row)
            {
                $batch_sql[] = $this->_db->update_string($this->table_name, $one_row['update'], $one_row['where']).';';
            }

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
     * 未过期，审核状态为初始化
     *
     * @version 1.2.0 增加权限判断
     *          1.2.0 增加退税检测，
     *          1.2.1 增加listing状态 2 下架品 、3 清仓品 不能审核 （1 在售品、2 下架品 、3 清仓品4 新产品 ）
     *
     * @param array $gids
     * @param array $owner_privileges
     */
    public function get_can_approve($gids, $owner_privileges, $created_date = '')
    {
        if (empty($gids)) return [];

        $query = $this->_db->from($this->table_name)
            ->where_in('gid', $gids)
            ->where('approve_state', APPROVAL_STATE_FIRST)
            ->where('expired', FBA_PR_EXPIRED_NO)
            ->where('is_refund_tax !=', REFUND_TAX_UNKNOWN)
            ->where('sku_state !=', SKU_STATE_DOWN)
            ->where('sku_state !=', SKU_STATE_CLEAN)
            ->where('require_qty >', 0)
            ->limit(count($gids));

        if (!isset($owner_privileges['*']))
        {
            //构造权限where条件
            $where_station_platform = [];

            $stations = array_keys($owner_privileges);

            $where_station_platform[] = count($stations) == 1 ?
            sprintf('station_code = "%s"', $stations[0]) :
            sprintf('station_code in (%s)', array_where_in($stations));

            $where_station_platform[] = ' and (case ';

            foreach ($owner_privileges as $c_station => $info)
            {
                $where_station_platform[] = count($info) == 1 ?
                sprintf('when station_code = "%s" then platform_code = "%s"', $c_station, array_keys($info)[0]) :
                sprintf('when station_code = "%s" then platform_code in (%s)', $c_station, array_where_in(array_keys($info)));
            }
            $where_station_platform[] = ' end)';

            $query->where(implode('', $where_station_platform), NULL, false);
        }

        $created_date = $created_date == '' ? date('Y-m-d') : $created_date;
        $approved_skus = $this->get_foreign_table_model()->get_skus_by_date($created_date, '!', OVERSEA_SUMMARY_APPROVAL_STATE_FIRST);

        if (!empty($approved_skus))
        {
            $query->where_not_in('sku', $approved_skus);
        }

        return $query->get()->result_array();
    }


    protected function get_foreign_table_model()
    {
        $ci = CI::$APP;
        $ci->load->model('Oversea_pr_summary_model', 'oversea_summary', false, 'oversea');
        return $ci->oversea_summary;
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
            $today_start = strtotime(date('Y-m-d'));
            $yesterday_start = $today_start - 86400;
            $this->_db->where('created_at <= ', $today_start)->where('created_at >=', $yesterday_start);
        }
        $this->_db->where('expired', FBA_PR_EXPIRED_NO);
        $this->_db->update($this->table_name, ['expired' => FBA_PR_EXPIRED_YES]);
        return $this->_db->affected_rows();
    }

    /**
     * 获取可以修改的记录
     *
     * @version 1.2.0 增加权限设置
     *
     * @param array $pr_sns 需求列表
     * @param array $accounts 账号列表  传值则查询
     * @param string $salesman 传值则查询，与上面是union
     * @return array
     */
    public function get_can_bd($pr_sns, $owner_privileges)
    {
        if (empty($pr_sns)) return [];

        $query = $this->_db->from($this->table_name)
        ->select('gid,pr_sn,bd,require_qty,point_pcs,purchase_qty,available_qty,oversea_up_qty,oversea_ship_qty,stocked_qty,weight_sale_pcs,pre_day')
        ->where_in('pr_sn', $pr_sns)
        ->where_in('approve_state', [OVERSEA_PLATFORM_APPROVAL_STATE_FIRST, OVERSEA_PLATFORM_APPROVAL_STATE_FAIL])
        ->where('expired', FBA_PR_EXPIRED_NO)
        ->limit(count($pr_sns));

        if (!isset($owner_privileges['*']))
        {
            //构造权限where条件
            $where_station_platform = [];

            $stations = array_keys($owner_privileges);

            $where_station_platform[] = count($stations) == 1 ?
            sprintf('station_code = "%s"', $stations[0]) :
            sprintf('station_code in (%s)', array_where_in($stations));

            $where_station_platform[] = ' and (case ';

            foreach ($owner_privileges as $c_station => $info)
            {
                $where_station_platform[] = count($info) == 1 ?
                sprintf('when station_code = "%s" then platform_code = "%s"', $c_station, array_keys($info)[0]) :
                sprintf('when station_code = "%s" then platform_code in (%s)', $c_station, array_where_in(array_keys($info)));
            }
            $where_station_platform[] = ' end)';

            $query->where(implode('', $where_station_platform), NULL, false);
        }

        $result = $query->get()->result_array();

        return $result;
    }

    /**
     * 获取勾选审核的记录中对应站点列表的数据
     *
     * @param unknown $created_date
     * @return unknown
     */
    public function get_map_station_list($gids)
    {
        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return implode(',', $arr);
        };

        $station_cols = [
                'gid', 'pr_sn', 'sku', 'station_code', 'is_refund_tax', 'available_qty', 'oversea_up_qty', 'oversea_ship_qty', 'approve_state',
                'sc_day', 'safe_stock_pcs', 'weight_sale_pcs', 'require_qty', 'purchase_qty', 'bd', 'display', 'stocked_qty', 'is_trigger_pr',
                'updated_at',  'created_at', 'platform_require_qty'
        ];

        $today_start = strtotime(date('Y-m-d'));
        $today_end = $today_start + 86400;
        CI::$APP->load->model('Oversea_pr_list_model', 'oversea_pr_list', false, 'oversea');
        $station_table = CI::$APP->oversea_pr_list->getTable();

        $sql = sprintf(
            'SELECT %s FROM ( SELECT sku,station_code,is_refund_tax FROM %s where gid in (%s) group by sku, station_code, is_refund_tax) platform INNER JOIN %s station   '.
            ' on station.sku = platform.sku and station.station_code = platform.station_code and station.is_refund_tax = platform.is_refund_tax '.
            ' where station.created_at > %d and station.created_at < %d',
            $append_table_prefix($station_cols, 'station.'),
            $this->table_name,
            array_where_in($gids),
            $station_table,
            $today_start,
            $today_end
       );
       return $this->_db->query($sql)->result_array();
    }

    /**
     * 获取可以审批的数据的ids
     */
    public function get_can_approve_data($owner_privileges,$limit=300, $created_date = '')
    {
        $query = $this->_db->from($this->table_name)->select('gid,approve_state')
            ->where('approve_state', APPROVAL_STATE_FIRST)
            ->where('expired', FBA_PR_EXPIRED_NO)
            ->where('is_refund_tax !=', REFUND_TAX_UNKNOWN)
            ->where('sku_state !=', SKU_STATE_DOWN)
            ->where('sku_state !=', SKU_STATE_CLEAN)
            ->where('require_qty >', 0)
            ->limit($limit);

        if (!isset($owner_privileges['*']))
        {
            //构造权限where条件
            $where_station_platform = [];

            $stations = array_keys($owner_privileges);

            $where_station_platform[] = count($stations) == 1 ?
                sprintf('station_code = "%s"', $stations[0]) :
                sprintf('station_code in (%s)', array_where_in($stations));

            $where_station_platform[] = ' and (case ';

            foreach ($owner_privileges as $c_station => $info)
            {
                $where_station_platform[] = count($info) == 1 ?
                    sprintf('when station_code = "%s" then platform_code = "%s"', $c_station, array_keys($info)[0]) :
                    sprintf('when station_code = "%s" then platform_code in (%s)', $c_station, array_where_in(array_keys($info)));
            }
            $where_station_platform[] = ' end)';

            $query->where(implode('', $where_station_platform), NULL, false);
        }

        $created_date = $created_date == '' ? date('Y-m-d') : $created_date;
        $approved_skus = $this->get_foreign_table_model()->get_skus_by_date($created_date, '!', OVERSEA_SUMMARY_APPROVAL_STATE_FIRST);

        if (!empty($approved_skus))
        {
            $query->where_not_in('sku', $approved_skus);
        }
        return $query->get()->result_array();
    }

    public function get_pcs($data)
    {
        $query = $this->_db->from($this->table_name)
            ->select('weight_sale_pcs')
            ->where('station_code', $data['site'])
            ->where('platform_code', $data['platform'])
            ->where('sku', $data['sku'])
            ->limit(1);
        return $query->get()->row_array();
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
//        $query = $this->_db->from($this->table_name.' force index(union_rebuild_pr)')
        $query = $this->_db->from($this->table_name)
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

    /**
     * 1.4.0添加能否发运字段,取yibai_oversea_sku_cfg_main(sku,station_code关联)
     * 获取重新生成需求列表基础数据
     * @param $start_gid
     * @param int $chunk_size
     * @return array
     */
    public function get_rebuild_gid($start_gid, $chunk_size = 500)
    {
        /**
         * @var CI_DB_query_builder $db
         */
        $db = $this->_db;
        //gid偏移 关联表 yibai_oversea_sku_cfg_main,取对应can_ship
        $query = $db->from($this->table_name . ' a')
            ->select('a.*,c.sc,a.can_ship as cfg_can_ship')
            ->join('`yibai_plan_stock`.`yibai_oversea_sku_cfg_main` b', 'a.sku = b.sku and a.station_code = b.station_code', 'left')
            ->join('`yibai_plan_stock`.`yibai_oversea_sku_cfg_part` c', 'b.gid = c.gid', 'left')
            ->where('a.gid >=', $start_gid)
            ->limit($chunk_size);

        //pr($query->get_compiled_select('', false));exit;
        $result = $query->get()->result_array();
//        var_dump($this->_db->last_query(), $result);
//        die;

        return $result;
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

    public function exists_today_data() : bool
    {
        list($min_gid, $max_gid) = $this->get_top_bottom_gid();
        return $min_gid != '';
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


}
