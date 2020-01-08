<?php 

/**
 * FBA 跟踪日志服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-05
 * @link
 */
class PrTrackLogService
{
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_pr_list_log_model', 'fba_pr_list_log', false, 'fba');
        $this->_ci->load->helper('fba_helper');
        return $this;
    }
    
    public function send($params, $context)
    {
        //附加提交者信息
        append_login_info($params);
        $params['context'] = mb_substr(strip_tags($context), 0, 300);
        $row = $this->_ci->fba_pr_list_log->fetch_table_cols($params);
        return $this->_ci->fba_pr_list_log->add($row);
    }
    
    public function get_track_log($gid, $offset = 1, $limit = 20)
    {
        $total = 0;
        $rows = $this->_ci->fba_pr_list_log->get($gid, $total, $offset, $limit);
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
        $db = $this->_ci->fba_pr_list_log->getDatabase();
        return $db->insert_batch($this->_ci->fba_pr_list_log->getTable(), $batch_params);
    }
}
