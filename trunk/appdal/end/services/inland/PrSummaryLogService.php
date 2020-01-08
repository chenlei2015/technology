<?php 

/**
 * 国内 汇总日志服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-05
 * @link
 */
class PrSummaryLogService
{
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_pr_summary_log_model', 'm_pr_summary_log', false, 'inland');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }
    
    public function send($params, $context)
    {
        //附加提交者信息
        append_login_info($params);
        $params['context'] = mb_substr(strip_tags($context), 0, 300);
        $row = $this->_ci->m_pr_summary_log->fetch_table_cols($params);
        return $this->_ci->m_pr_summary_log->add($row);
    }
    
    public function get_summary_log($gid, $offset = 1, $limit = 20)
    {
        $total = 0;
        $rows = $this->_ci->m_pr_summary_log->get($gid, $total, $offset, $limit);
        $page = ceil($total / $limit);
        return [
                'page_data' => [
                        'total' => $total,
                        'offset' => $offset,
                        'limit' => $limit,
                        'pages' => $page
                ],
                'data_list'  => [
                        'value' => &$rows
                ]
        ];
    }
}
