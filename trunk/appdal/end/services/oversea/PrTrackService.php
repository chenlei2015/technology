<?php

/**
 * 海外仓 需求跟踪服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-08
 * @link
 */
class PrTrackService
{
    public static $s_system_log_name = 'OVERSEA-TRACK';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_pr_track_list_model', 'oversea_track_list', false, 'oversea');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }
    
    /**
     * 添加一条备注, 成功为true，否则抛异常
     *
     * @version 1.2.0 增加权限
     *
     * @param array $params
     * @param string|array $owner_privileges 权限值 * 带有所有权限， 数组为有权限范围定义
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params, $owner_privileges = [])
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        
        $record = ($pk_row = $this->_ci->load->oversea_track_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        if (!isset($owner_privileges['*'])  && !in_array($record['station_code'], $owner_privileges))
        {
            throw new \InvalidArgumentException(sprintf('您没有权限'), 412);
        }
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->oversea_track_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        
        $db = $this->_ci->oversea_track_list->getDatabase();
        
        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('海外仓跟踪列表更新备注失败'), 500);
        }
        if (!$this->add_track_remark($params))
        {
            throw new \RuntimeException(sprintf('海外仓跟踪列表插入备注失败'), 500);
        }
        $db->trans_complete();
        
        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('海外仓跟踪添加备注事务提交完成，但检测状态为false'), 500);
        }
        
        return true;
    }
    
    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_track_remark($params)
    {
        $this->_ci->load->model('Oversea_pr_track_remark_model', 'oversea_pr_track_remark', false, 'oversea');
        append_login_info($params);
        $insert_params = $this->_ci->oversea_pr_track_remark->fetch_table_cols($params);
        return $this->_ci->oversea_pr_track_remark->add($insert_params);
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->oversea_track_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }
     
    public function get_track_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Oversea_pr_track_remark_model', 'oversea_pr_track_remark', false, 'oversea');
        return $this->_ci->oversea_pr_track_remark->get($gid, $offset, $limit);
    }

    /**
     * 生成插入备货记录, 和日志
     */
    public function create_track_list($pr_rows)
    {
        if (empty($pr_rows))
        {
            return 0;
        }
        $active_login_info = get_active_user()->get_user_info();
        //更新操作
        $station_pr_sn = array_column($pr_rows, 'pr_sn');
        $exists_pr_sns = array_column($this->_ci->load->oversea_track_list->get_exists_track($station_pr_sn), 'pr_sn');
        //$unexists_pr_sns = array_diff($station_pr_sn, $exists_pr_sns);
        
        $exists_pr_sns = array_flip($exists_pr_sns);
        //$unexists_pr_sns = array_flip($unexists_pr_sns);
        
        //新插入的汇总
        $batch_insert = $batch_log_insert = $batch_update = [];
        $update_colums = array_flip(['pr_sn', 'stocked_qty', 'purchase_order_qty', 'bd', 'require_qty', 'expect_exhaust_date', 'updated_at']);
        
        foreach ($pr_rows as $key => &$row)
        {
            $log_gid = '';
            if (isset($exists_pr_sns[$row['pr_sn']]))
            {
                //update, 复制
                $row['purchase_order_qty'] = $row['purchase_qty'];
                $row['purchase_qty'] = 0;
                $batch_update[] = array_intersect_key($this->_ci->oversea_track_list->fetch_table_cols($row), $update_colums);
                $context = sprintf('汇总更新海外站点需求跟踪记录');
                $log_gid = $row['gid'];
            }
            else
            {
                //new, insert
                $row['purchase_order_qty'] = $row['purchase_qty'];
                $row['purchase_qty'] = 0;
                $insert_row = $this->_ci->oversea_track_list->fetch_table_cols($row);
                //重置参数
                $insert_row['gid']        = $this->_ci->oversea_track_list->gen_id();
                $insert_row['created_at'] = time();
                $insert_row['updated_at'] = 0;
                $insert_row['remark']     = '';
                
                //code转换
                $insert_row['logistics_code'] = tran_logistics_code($row['logistics_id']);
                $insert_row['warehouse_code'] = tran_warehouse_code($row['purchase_warehouse_id']);
                $insert_row['country_code']   = tran_oversea_country_code($row['station_code']);
                
                $batch_insert[] = $insert_row;
                $context = sprintf('汇总创建海外站点需求跟踪记录');
                $log_gid = $insert_row['gid'];
            }
            $batch_log_insert[] = [
                    'gid' => $log_gid,
                    'uid' => $active_login_info['oa_info']['userNumber'],
                    'user_name' => $active_login_info['oa_info']['userName'],
                    'context' => $context
            ];
        }
        
        return [&$batch_insert, &$batch_log_insert , &$batch_update];
    }
    
    /**
     * 批量插入
     * @param unknown $batch_params
     * @return unknown
     */
    public function insert_batch($batch_params)
    {
        $db = $this->_ci->oversea_track_list->getDatabase();
        return $db->insert_batch($this->_ci->oversea_track_list->getTable(), $batch_params);
    }
    
    /**
     * 批量更新
     *
     * @param unknown $batch_params
     * @param unknown $primary_key
     * @return unknown
     */
    public function update_batch($batch_params, $primary_key)
    {
        $db = $this->_ci->oversea_track_list->getDatabase();
        return $db->update_batch($this->_ci->oversea_track_list->getTable(), $batch_params, $primary_key);
    }

    /**
     * 回写汇总单号
     * @param string $date
     * @return unknown
     */
    public function rewrite_unassign_sumsn($date = '')
    {
        $date = $date == '' ? date('Y-m-d') : $date;
        return $this->_ci->oversea_track_list->rewrite_unassign_sumsn($date);
    }
}
