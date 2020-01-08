<?php

/**
 * 海外平台汇总服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class PlatformSummaryService
{
    public static $s_system_log_name = 'OVEASEA-PLATFORM-SUMMARY';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_pr_summary_model', 'oversea_summary', false, 'oversea');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }
    
    /**
     * 汇总
     */
    public function summary($should_addup)
    {
        $this->_ci->load->classes('oversea/classes/OverseaPlatformSummary');
        $this->_ci->load->model('Oversea_platform_list_model', 'm_oversea_platform_list', false, 'oversea');
        $this->_ci->load->model('Oversea_pr_list_log_model', 'oversea_list_log', false, 'oversea');
        $this->_ci->load->model('Oversea_pr_list_model', 'oversea_pr_list', false, 'oversea');
        
        return $this->_ci->OverseaPlatformSummary
            ->set_by_default($this->_ci->m_oversea_platform_list)
            ->set_summary_model($this->_ci->oversea_pr_list)
            ->set_summary_log_model($this->_ci->oversea_list_log)
            ->run($should_addup);
    }
    
    /**
     * 获取需要删除的汇总单号
     *
     * @return array
     */
    public function get_delete_track_pr_sn()
    {
        if (empty($this->_ci->OverseaPlatformSummary))
        {
            return [];
        }
        return $this->_ci->OverseaPlatformSummary->get_delete_track_pr_sn();
    }

}
