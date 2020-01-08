<?php

/**
 * 用户配置服务， 分配FBA, 海外仓， 管理员权限， 二级、 三级
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-03-10
 * @link
 */
class UsercfgService
{
    private $_ci;
    
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('User_config_list_model', 'm_user_cfg_list', false, 'basic');
    }
    
    /**
     * 分配用户权限， 支持批量操作
     *
     * @param unknown $params
     * @throws \RuntimeException
     * @return boolean|unknown
     */
    public function assign($form_params, $options = [])
    {
        $this->_ci->load->classes('basic/classes/UserAssign');
        if (isset($options['enable_out_trans']))
        {
            $this->_ci->UserAssign->set_out_trans();
        }
        $this->_ci->UserAssign->recive($form_params)->separate()->update();
        return $this->_ci->UserAssign->report();
        
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
        
        $record = ($pk_row = $this->_ci->load->m_user_cfg_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
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
        $this->_ci->Record->setModel($this->_ci->m_user_cfg_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', date('Y-m-d H:i:s'));
        
        $db = $this->_ci->m_user_cfg_list->getDatabase();
        
        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('用户配置更新备注失败'), 500);
        }
        if (!$this->add_config_remark($params))
        {
            throw new \RuntimeException(sprintf('用户配置插入备注失败'), 500);
        }
        $db->trans_complete();
        
        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('用户配置添加备注事务提交完成，但检测状态为false'), 500);
        }
        
        return true;
    }
    
    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_config_remark($params)
    {
        $this->_ci->load->model('user_config_remark_model', 'user_config_remark', false, 'basic');
        append_login_info($params);
        $insert_params = $this->_ci->user_config_remark->fetch_table_cols($params);
        return $this->_ci->user_config_remark->add($insert_params);
    }

    /**
     *
     * @param unknown $gid
     */
    public function detail($gid)
    {
        $list_row = ($pk_row = $this->_ci->load->m_user_cfg_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($list_row))
        {
            return [];
        }
        $uid = $list_row['staff_code'];
        $this->_ci->load->model('User_config_model', 'm_user_config', false, 'basic');
        $config_rows = $this->_ci->m_user_config->get_list_by_staff_codes([$uid]);
        return $config_rows;
    }
    
    /**
     * 获取gid的备注
     * @param unknown $gid
     * @return unknown
     */
    public function get_remark($gid)
    {
        $this->_ci->load->model('User_config_remark_model', 'm_user_config_remark', false, 'basic');
        $list_row = $this->_ci->m_user_config_remark->get($gid);
        return $list_row;
    }
    
    /**
     * 获取gid的日志
     *
     * @param unknown $gid
     * @param unknown $offset
     * @param unknown $limit
     * @return unknown
     */
    public function get_log($gid, $offset, $limit)
    {
        $total = 0;
        $this->_ci->load->model('User_config_log_model', 'm_user_config_log', false, 'basic');
        $rows = $this->_ci->m_user_config_log->get($gid, $total, $offset, $limit);
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
     * 获取用户的权限详情
     */
    public function get_my_privileges($staff_code)
    {
        $this->_ci->load->model('User_config_model', 'm_user_config', false, 'basic');
        if (is_string($staff_code))
        {
            return $this->_ci->m_user_config->get($staff_code);
        }
        else
        {
            return $this->_ci->m_user_config->mget($staff_code);
        }
        
    }
    
    /**
     * hasx -> 1 => 1,2,3
     * 1,2,3 => has_x => 1|2
     */
    public function tran_hasx_to_value_str($mixed)
    {
        $this->_ci->load->model('User_config_model', 'm_user_config', false, 'basic');
        $_map = $this->_ci->m_user_config::$s_map_value;
        
        if (is_array($mixed))
        {
            $value = [];
            foreach ($mixed as $col => $val)
            {
                if (isset($_map[$col]) && $val == GLOBAL_YES)
                {
                    $value[] = $_map[$col]['val'];
                }
            }
            sort($value);
            return implode(',', $value);
        }
        
        $id_value = array_filter(explode(',', $mixed));
        $value_to_col = array_combine(array_column($_map, 'val'), array_keys($_map));
        foreach ($value_to_col as $col => $val)
        {
            $value[$col] = in_array($val, $id_value) ? GLOBAL_YES : GLOBAL_NO;
        }
        return $value;
    }
    
}