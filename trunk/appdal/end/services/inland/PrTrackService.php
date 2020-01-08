<?php

/**
 * 国内 需求跟踪服务
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
    public static $s_system_log_name = 'INLAND-TRACK';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_pr_track_list_model', 'm_inland_pr_track', false, 'plan');
        $this->_ci->load->helper('oversea_helper');
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
    public function update_remark($params)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        
        $record = ($pk_row = $this->_ci->load->m_inland_pr_track->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->m_inland_pr_track);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        
        $db = $this->_ci->m_inland_pr_track->getDatabase();
        
        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('国内跟踪列表更新备注失败'), 500);
        }
        if (!$this->add_track_remark($params))
        {
            throw new \RuntimeException(sprintf('国内跟踪列表插入备注失败'), 500);
        }
        $db->trans_complete();
        
        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('国内跟踪添加备注事务提交完成，但检测状态为false'), 500);
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
        $this->_ci->load->model('Inland_pr_track_remark_model', 'm_inland_pr_track_remark', false, 'inland');
        append_login_info($params);
        $insert_params = $this->_ci->m_inland_pr_track_remark->fetch_table_cols($params);
        return $this->_ci->m_inland_pr_track_remark->add($insert_params);
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->m_inland_pr_track->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }
     
    public function get_track_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Inland_pr_track_remark_model', 'm_inland_pr_track_remark', false, 'inland');
        return $this->_ci->m_inland_pr_track_remark->get($gid, $offset, $limit);
    }

    /**
     * 生成插入备货记录, 和日志
     */
    public function create_special_track_list($pr_rows)
    {
        if (empty($pr_rows))
        {
            return 0;
        }
        $active_login_info = get_active_user()->get_user_info();
        $batch_insert = $batch_log_insert = [];
        
        foreach ($pr_rows as $row)
        {
            $insert_row = $this->_ci->m_inland_pr_track->fetch_table_cols($row);
            //重置参数
            $insert_row['gid']        = $this->_ci->m_inland_pr_track->gen_id();
            $insert_row['created_at'] = time();
            $insert_row['updated_at'] = 0;
            $insert_row['remark']     = '';
            $batch_insert[] = $insert_row;
            
            $batch_log_insert[] = [
                    'gid' => $insert_row['gid'],
                    'uid' => $active_login_info['oa_info']['userNumber'],
                    'user_name' => $active_login_info['oa_info']['userName'],
                    'context' => sprintf('创建需求跟踪记录')
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
        $db = $this->_ci->m_inland_pr_track->getDatabase();
        return $db->insert_batch($this->_ci->m_inland_pr_track->getTable(), $batch_params);
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
        $batch_insert = $batch_log_insert = [];
        foreach ($pr_rows as $row)
        {
            $insert_row = $this->_ci->m_inland_pr_track->fetch_table_cols($row);
            //重置参数
            $insert_row['gid']        = $this->_ci->m_inland_pr_track->gen_id();
            $insert_row['created_at'] = time();
            $insert_row['updated_at'] = 0;
            $insert_row['remark']     = '';
            $batch_insert[] = $insert_row;
            
            $batch_log_insert[] = [
                    'gid' => $insert_row['gid'],
                    'uid' => $active_login_info['oa_info']['userNumber'],
                    'user_name' => $active_login_info['oa_info']['userName'],
                    'context' => sprintf('创建需求跟踪记录')
            ];
        }
        
        return [$batch_insert, $batch_log_insert];
    }
}
