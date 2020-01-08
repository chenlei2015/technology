<?php 

/**
 * 海外仓 日志服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-05
 * @link
 */
class PrLogService
{
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_pr_list_log_model', 'oversea_pr_list_log', false, 'oversea');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }
    
    public function send($params, $context)
    {
        //附加提交者信息
        append_login_info($params);
        $params['context'] = mb_substr(strip_tags($context), 0, 300);
        $row = $this->_ci->oversea_pr_list_log->fetch_table_cols($params);
        return $this->_ci->oversea_pr_list_log->add($row);
    }
    
    public function get_one_listing_log($gid, $offset = 1, $limit = 20)
    {
        $total = 0;
        $rows = $this->_ci->oversea_pr_list_log->get($gid, $total, $offset, $limit);
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
     * 批量发送
     * @param unknown $params
     */
    public function multi_send($params)
    {
        $active_login_info = get_active_user()->get_user_info();
        foreach ($params as $row => &$par)
        {
            $par['context'] = mb_substr(strip_tags($par['context']), 0, 300);
            $par['uid'] = $active_login_info['oa_info']['userNumber'];
            $par['user_name'] = $active_login_info['oa_info']['userName'];
        }
        return $this->_ci->oversea_pr_list_log->madd($params);
    }
}
