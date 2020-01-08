<?php 

/**
 * Inland 国内特殊日志服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-05
 * @link
 */
class PrSpecialLogService
{
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_special_pr_list_log_model', 'm_special_inland_pr_list_log', false, 'inland');
        $this->_ci->load->helper('inland_helper');
        return $this;
    }
    
    public function send($params, $context)
    {
        //附加提交者信息
        append_login_info($params);
        $params['context'] = mb_substr(strip_tags($context), 0, 300);
        $row = $this->_ci->m_special_inland_pr_list_log->fetch_table_cols($params);
        return $this->_ci->m_special_inland_pr_list_log->add($row);
    }
    
    /**
     * 批量发送
     * @param unknown $params
     */
    public function multi_send($params)
    {
        if (empty($params))
        {
            return false;
        }
        
        $login_info = get_active_user()->get_user_info();
        $user_name    = is_system_call() ? $login_info['user_name'] : $login_info['oa_info']['userName'];
        $uid          = is_system_call() ? $login_info['uid'] : $login_info['oa_info']['userNumber'];
        
        foreach ($params as $row => &$par)
        {
            $par['context'] = mb_substr(strip_tags($par['context']), 0, 300);
            $par['uid'] = $uid;
            $par['user_name'] = $user_name;
        }
        return $this->_ci->m_special_inland_pr_list_log->madd($params);
    }
    
    public function get_one_listing_log($gid, $offset = 1, $limit = 20)
    {
        $total = 0;
        $rows = $this->_ci->m_special_inland_pr_list_log->get($gid, $total, $offset, $limit);
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
