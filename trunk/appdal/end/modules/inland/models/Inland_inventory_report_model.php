<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';
require_once APPPATH . 'modules/basic/classes/contracts/Rpcable.php';
require_once APPPATH . 'modules/basic/traits/Rpc_imples.php';

/**
 * Inland 销量运算配置列表model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @date 2019-03-04
 * @link
 */
class Inland_inventory_report_model extends MY_Model implements Rpcable
{

    use Table_behavior, Rpc_imples;

    private $_rpc_module = 'inland';

    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_inland_inventory_report';
        $this->primaryKey = 'gid';
        $this->tableId = 131;
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

    public function add($params)
    {
        return $this->_db->insert($this->table_name, $params);
    }

    public function modify_info($params)
    {
        $this->_db->where('gid',$params['gid']);
        $this->_db->update($this->table_name, $params);
        return $this->_db->affected_rows();
    }

    /**
     * 根据查询条件查询出所有结果
     */
    public function get_info($where)
    {
//        print_r($where);exit;
        $this->_db->from($this->table_name);
        $this->_db->where($where);
        return $this->_db->select('*')->get()->result_array();
    }

    /**
     * 单条删除
     * @param $gid
     * @return mixed
     */
    public function batch_delete($gid)
    {
        $this->_db->where('gid',$gid);
        $this->_db->update($this->table_name, ['is_del'=>1]);
        return $this->_db->affected_rows();
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

}