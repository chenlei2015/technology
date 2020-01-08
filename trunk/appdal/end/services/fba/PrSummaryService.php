<?php 

/**
 * FBA 需求汇总服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class PrSummaryService
{
    public static $s_system_log_name = 'FBA-SUMMARY';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_pr_summary_model', 'fba_summary', false, 'fba');
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
    public function update_remark($params)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        
        $record = ($pk_row = $this->_ci->load->fba_summary->findByPk($gid)) === null ? [] : $pk_row->toArray();
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
        $this->_ci->Record->setModel($this->_ci->fba_summary);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        
        $db = $this->_ci->fba_summary->getDatabase();
        
        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('Fba汇总列表更新备注失败'), 500);
        }
        if (!$this->add_track_remark($params))
        {
            throw new \RuntimeException(sprintf('Fba汇总列表插入备注失败'), 500);
        }
        $db->trans_complete();
        
        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('Fba汇总添加备注事务提交完成，但检测状态为false'), 500);
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
        $this->_ci->load->model('Fba_pr_summary_remark_model', 'fba_pr_summary_remark', false, 'fba');
        append_login_info($params);
        $insert_params = $this->_ci->fba_pr_summary_remark->fetch_table_cols($params);
        return $this->_ci->fba_pr_summary_remark->add($insert_params);
    }

    /**
     * 汇总
     */
    public function summary($should_addup)
    {
        $this->_ci->load->classes('fba/classes/FbaSummary');
        $this->_ci->load->model('Fba_pr_list_model', 'm_fba_list_model', false, 'fba');
        $this->_ci->load->model('Fba_pr_summary_log_model', 'm_fba_summary_log_model', false, 'fba');
        
        return $this->_ci->FbaSummary->set_by_default($this->_ci->m_fba_list_model)
                                ->set_summary_model($this->_ci->fba_summary)
                                ->set_summary_log_model($this->_ci->m_fba_summary_log_model)
                                ->run($should_addup);
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->fba_summary->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }
    
    public function get_summary_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Fba_pr_summary_remark_model', 'fba_pr_summary_remark', false, 'fba');
        return $this->_ci->fba_pr_summary_remark->get($gid, $offset, $limit);
    }
}
