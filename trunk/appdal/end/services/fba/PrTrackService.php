<?php

/**
 * FBA 需求跟踪服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class PrTrackService
{
    public static $s_system_log_name = 'FBA-TRACK';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_pr_track_list_model', 'fba_track_list', false, 'fba');
        $this->_ci->load->helper('fba_helper');
        return $this;
    }
    
    /**
     * 添加一条备注, 成功为true，否则抛异常
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params, $priv_uid = -1)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        
        $record = ($pk_row = $this->_ci->load->fba_track_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        $active_user = get_active_user();
        //如果我是记录的管理员，可以添加备注
        if ($priv_uid != -1)
        {
            //这条记录的账号是否是我管辖的
            $account_name = $active_user->get_my_manager_accounts();
            if (empty($account_name) || !in_array($record['account_name'], $account_name))
            {
                //不是我管辖
            }
            else
            {
                $priv_uid = -1;
            }
        }
        if ($priv_uid != -1 && $priv_uid != $record['salesman'])
        {
            throw new \InvalidArgumentException(sprintf('您无权限操作他人的记录'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->fba_track_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        
        $db = $this->_ci->fba_track_list->getDatabase();
        
        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('Fba跟踪列表更新备注失败'), 500);
        }
        if (!$this->add_track_remark($params))
        {
            throw new \RuntimeException(sprintf('Fba跟踪列表插入备注失败'), 500);
        }
        $db->trans_complete();
        
        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('Fba跟踪添加备注事务提交完成，但检测状态为false'), 500);
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
        $this->_ci->load->model('Fba_pr_track_remark_model', 'fba_pr_track_remark', false, 'fba');
        append_login_info($params);
        $insert_params = $this->_ci->fba_pr_track_remark->fetch_table_cols($params);
        return $this->_ci->fba_pr_track_remark->add($insert_params);
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->fba_track_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }
    
    public function get_track_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Fba_pr_track_remark_model', 'fba_pr_track_remark', false, 'fba');
        return $this->_ci->fba_pr_track_remark->get($gid, $offset, $limit);
    }

    /**
     * 生成插入备货记录
     *
     * v1.0.1
     * 增加“已备货数量”删除“备货数量”和“多余库存”
     *
     * v1.2.0
     * 增加logistics_code， warehouse_code， country_code
     *
     */
    public function create_track_list($pr_rows)
    {
        if (empty($pr_rows))
        {
            return 0;
        }
        $active_login_info = get_active_user()->get_user_info();
        $batch_insert = $batch_log_insert = [];
        //跟踪备货字段要与需求列表相同字段名字也要相同
        foreach ($pr_rows as $row)
        {
            $row['purchase_order_qty'] = $row['purchase_qty'];
            $row['purchase_qty']       = 0;
            $insert_row                = $this->_ci->fba_track_list->fetch_table_cols($row);
            
            //重置参数
            $insert_row['gid']        = $this->_ci->fba_track_list->gen_id();
            $insert_row['created_at'] = time();
            $insert_row['updated_at'] = 0;
            $insert_row['remark']     = '';
            
            //code转换
            $insert_row['logistics_code'] = tran_logistics_code($row['logistics_id']);
            $insert_row['warehouse_code'] = tran_warehouse_code($row['purchase_warehouse_id']);
            $insert_row['country_code']   = $row['country_code'] != '' ? $row['country_code'] : tran_fba_country_code($row['station_code']);
            
            $batch_insert[] = $insert_row;
            
            $batch_log_insert[] = [
                    'gid'       => $insert_row['gid'],
                    'uid'       => $active_login_info['oa_info']['userNumber'],
                    'user_name' => $active_login_info['oa_info']['userName'],
                    'context'   => sprintf('创建需求跟踪记录')
            ];
        }
        
        return [$batch_insert, $batch_log_insert];
    }
    
    /**
     * 批量插入
     * @param unknown $batch_params
     * @return unknown
     */
    public function insert_batch($batch_params)
    {
        $db = $this->_ci->fba_track_list->getDatabase();
        return $db->insert_batch($this->_ci->fba_track_list->getTable(), $batch_params);
    }

    /**
     * 回写汇总单号
     * @param string $date
     * @return unknown
     */
    public function rewrite_unassign_sumsn($date = '')
    {
        $date = $date == '' ? date('Y-m-d') : $date;
        return $this->_ci->fba_track_list->rewrite_unassign_sumsn($date);
    }
}
