<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * fba促销sku列表
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-06-01
 * @link
 */
class Fba_promotion_sku_model extends MY_Model implements Rpcable
{
    
    use Table_behavior, Rpc_imples;
    
    private $_rpc_module = 'fba';
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_fba_promotion_sku';
        $this->primaryKey = 'gid';
        $this->tableId = 34;
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
            log_message('ERROR', sprintf('%s 根据主键: %s获取记录失败, 当前数据库：%s', __CLASS__, $gid, json_encode(array_keys(self::$_dbCaches))));
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
     * 批量删除
     *
     * @param array $gid_arrs
     * @return int 影响条数
     */
    public function batch_delete($gid_arrs)
    {
        $update_cols = [
                'state' => PROMOTION_SKU_DELETED,
                'updated_at' => time()
        ];
        $this->_db->where_in('gid', $gid_arrs);
        return $this->_db->update($this->table_name, $update_cols);
    }
    
    /**
     * 获取运行中的sku
     *
     * @param unknown $skus
     * @return unknown
     */
    public function get_running_skus($skus)
    {
        return $this->_db->from($this->table_name)->select('gid, sku')->where_in('sku', $skus)->where('state', PROMOTION_SKU_RUNNING)->get()->result_array();
    }
    
    /**
     * 更新已经存在的记录
     *
     * @param unknown $update_params
     * @return unknown
     */
    public function update_exists_running_skus($update_params)
    {
        return $this->_db->update_batch($this->table_name, $update_params, 'gid');
    }
    
    /**
     * 插入已经存在的记录
     * @param unknown $insert_params
     * @return unknown
     */
    public function insert_running_skus($insert_params)
    {
        return $this->_db->insert_batch($this->table_name, $insert_params);
    }
    
    /**
     * 获取高亮sku
     *
     * @param array $skus 不传取所有
     * @return array 需要高亮的sku
     */
    public function get_last_promotion_skus($skus = [])
    {
        $where_in = empty($skus) ? '' : ' AND sku in ('.array_where_in($skus).')';
        $query_sql = sprintf(
            'SELECT sku, created_time from %s a INNER JOIN (SELECT max(gid) AS mgid FROM %s GROUP BY sku) b '.
            'WHERE a.gid = b.mgid '.$where_in,
            $this->table_name,
            $this->table_name
            );
        return $this->_db->query($query_sql)->result_array();
    }
    
    /**
     * 设置过期
     *
     * @return unknown
     */
    public function expired()
    {
        $time = now();
        $today_start = strtotime(date('Y-m-d'))+1;
        //created_time + 15 * 86400 <= now()
        $this->_db->reset_query();
        $this->_db->where('state', PROMOTION_SKU_RUNNING)->where('created_time <=', $today_start - HIGHLIGHT_VALID_DAYS * 86400);
        return $this->_db->update($this->table_name, ['state' => PROMOTION_SKU_FINISHED, 'updated_at' => $time]);
    }
}