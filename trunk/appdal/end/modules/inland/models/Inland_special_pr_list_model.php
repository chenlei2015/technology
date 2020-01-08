<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * Inland 特殊需求列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-03-04
 * @link
 */
class Inland_special_pr_list_model extends MY_Model implements Rpcable
{
    
    use Table_behavior, Rpc_imples;
    
    private $_rpc_module = 'inland';
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_inland_special_pr_list';
        $this->primaryKey = 'gid';
        $this->tableId = 37;
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
            log_message('ERROR', sprintf('Inland_special_pr_list_model 根据主键: %s获取记录失败, 当前数据库：%s', $gid, json_encode(array_keys(self::$_dbCaches))));
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
     * 获取手动审核, 目前设定为：
     * 待审核 && sku匹配 && 需求数量>0
     *
     * @param unknown $accounts
     */
    public function get_can_approve_for_manual($gids)
    {
        if (empty($gids)) return [];
        
        $query = $this->_db->from($this->table_name)
            ->where_in('gid', $gids)
            ->where('approve_state', SPECIAL_CHECK_STATE_INIT)
            ->where('is_sku_match', SKU_MATCH_STATE_TRUE)
            ->where('require_qty > ', 0)
            ->limit(count($gids));
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
            $info[$row['sku']]['sku_name'] = $row['sku_name'];
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
     * 获取今天的序列和需求单
     *
     * @return array|array
     */
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
     * 更新bd
     *
     * @desc rpc、local
     * @param Record $record
     * @return string|unknown
     */
    public function update_require_qty(?Record $record)
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
     * 获取需要删除的记录
     *
     * @param unknown $gids
     * @param string $select
     * @return unknown
     */
    public function get_delete_by_gids($gids, $select = '*')
    {
        return $this->_db->select($select)
        ->from($this->table_name)
        ->where_in('gid', $gids)
        ->where('state', SPECIAL_PR_STATE_NORMAL)
        ->get()
        ->result_array();
    }

    /**
     * 获取需要删除的记录五个特殊字段
     *
     * @param unknown $gids
     * @return unknown
     */
    public function get_unique_field($gids,$select = '*')
    {
        return $this->_db->select($select)
            ->from($this->table_name)
            ->where('approve_state !=',1)
            ->where_in('gid', $gids)
            ->get()
            ->result_array();
    }


    /**
     * 上传
     */
    public function upload($batch_params)
    {
//        pr($batch_params);exit;
        $this->_db->trans_start();
        $this->_db->insert_batch('yibai_inland_special_pr_list',$batch_params);
        $row = $this->_db->affected_rows();
        $this->_db->trans_complete();

        if ($this->_db->trans_status() === FALSE)
        {
            throw new \Exception(sprintf('手工单写入数据库失败'), 500);
        }
        return $row;

    }


}