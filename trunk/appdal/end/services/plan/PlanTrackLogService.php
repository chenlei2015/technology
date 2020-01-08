<?php 

/**
 * OVERSEA 跟踪日志服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-05
 * @link
 */
class PlanTrackLogService
{
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Plan_purchase_track_log_model', 'm_purchase_track_log', false, 'plan');
        $this->_ci->load->helper('plan_helper');
        return $this;
    }
    
    public function send($params, $context)
    {
        //附加提交者信息
        append_login_info($params);
        $params['context'] = mb_substr(strip_tags($context), 0, 300);
        $row = $this->_ci->oversea_pr_track_log->fetch_table_cols($params);
        return $this->_ci->oversea_pr_track_log->add($row);
    }
    
    public function get_track_log($gid, $offset = 1, $limit = 20)
    {
        $total = 0;
        $rows = $this->_ci->m_purchase_track_log->get($gid, $total, $offset, $limit);
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

    /**
     * 批量插入
     * @param unknown $batch_params
     * @return unknown
     */
    public function insert_batch($batch_params)
    {
        $db = $this->_ci->m_purchase_track->getDatabase();
        return $db->insert_batch($this->_ci->m_purchase_track->getTable(), $batch_params);
    }
}
