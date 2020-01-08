<?php 

/**
 * 系统日志
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2018-01-03
 * @link
 */
class SystemLogService
{
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('System_log_model', 'system_log', false, 'log');
    }
    
    public function send($params, $module, $context)
    {
        //附加提交者信息
        append_login_info($params);
        $params['module'] = $module;
        $params['context'] = mb_substr(strip_tags($context), 0, 300);
        $row = $this->_ci->system_log->fetch_table_cols($params);
        return $this->_ci->system_log->add($row);
    }
    
}