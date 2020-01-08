<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/4
 * Time: 11:31
 */
class Fba_sku_cfg_history_model extends MY_Model
{
    public function __construct()
    {
        $this->table_name = 'yibai_fba_sku_cfg_history';
        parent::__construct();
    }

    /**
     *
     * 在保留历史配置表里新增一条记录
     * 规则改为全局时,删除历史表数据
     * 规则改为自定义时,调用改方法,判断是否为审核通过,是:保存,否:不保存
     * 审核成功时,删除历史表数据
     *
     * @param string $id
     * @param array  $old_global_cfg
     *
     * @return bool
     */
    public function check_approve_state_add($id = '')
    {
        if (empty($id)) {
            return FALSE;
        }

        $info = $this->db->select('id,is_contraband,sp,max_sp,max_lt,max_safe_stock,updated_at,updated_uid,updated_zh_name,state,approved_at,approved_uid,approved_zh_name,remark')
            ->from('yibai_fba_sku_cfg')->where_in('id', $id)->get()->result_array();
        if (!empty($info)) {
            foreach ($info as $key => &$item) {
                if ($item['state'] != CHECK_STATE_SUCCESS) {
                    unset($info[$key]);
                }
            }
        }
        return $this->insert($info);
    }

    public function check_approve_state_delete($id = '')
    {
        if (empty($id)) {

        }

        return $this->clean($id);
    }

    public function insert($info)
    {
        if (empty($info)) {
            return FALSE;
        }
        return $this->db->insert_batch($this->table_name, $info);
    }

    public function clean($id)
    {
        $this->db->where_in('id', $id);

        return $this->db->delete($this->table_name);
    }
}