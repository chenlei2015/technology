<?php 

/**
 * 日志服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-05
 * @link
 */
class AllotmentLogService
{
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Allotment_list_log_model', 'm_allotment_log', false, 'plan');
        $this->_ci->load->helper('plan_helper');
        return $this;
    }
    
    public function send($params, $context)
    {
        //附加提交者信息
        append_login_info($params);
        $params['context'] = mb_substr(strip_tags($context), 0, 300);
        $row = $this->_ci->m_allotment_log->fetch_table_cols($params);
        return $this->_ci->m_allotment_log->add($row);
    }
    
    public function get_log($gid, $offset = 1, $limit = 20)
    {
        $total = 0;
        $rows = $this->_ci->m_allotment_log->get($gid, $total, $offset, $limit);
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
