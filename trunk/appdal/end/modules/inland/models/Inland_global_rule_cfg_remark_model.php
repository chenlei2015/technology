<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 *
 * @author W02278
 * @name  Inland_global_rule_cfg_remark_model Class
 */
class Inland_global_rule_cfg_remark_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_global_rule_cfg_remark';
        $this->primaryKey = 'gid';
        $this->tableId = 102;
        parent::__construct();
    }

    /**
     * 获取列表
     * @param int $offset 页码
     * @param int $limit  每页数
     * @return array 备注列表
     * @author W02278
     * CreateTime: 2019/4/23 9:32
     */
    public function getList( $offset = 1, $limit = 20)
    {
        $query = $this->_db->from($this->table_name);
        $counter_query = clone $query;
        $total = $counter_query->count_all_results();
        $result = [];
        ( $total > 0 ) && $result = $query->select(' `gid`, `cid`, `uid`, `user_name`, `remark`, `created_at` ')
                         ->order_by('created_at', 'DESC')
                         ->limit($limit, ($offset - 1) * $limit)
                         ->get()
                         ->result_array();

        $data['data_list'] = $result;
        $data['data_page'] = array(
            'limit' => (float)$limit,
            'offset' => (float)$offset,
            'total' => (float)$total
        );
        return $data;
    }

    /**
     * @param null $gid
     * @param int $offset
     * @param int $limit
     * @return array
     * @throws Exception
     * @author W02278
     * CreateTime: 2019/4/24 10:47
     */
    public function getByCid($gid = null , $offset = 1, $limit = 20)
    {
        if (!$gid) {
            throw new \Exception('参数异常');
        }

        $query = $this->_db->from($this->table_name);
        $counter_query = clone $query;
        $total = $counter_query->count_all_results();
        $result = [];
        ( $total > 0 ) && $result = $query->select(' `user_name`, `remark`, `created_at` ')
            ->where('cid', $gid)
            ->order_by('created_at', 'DESC')
            ->limit($limit, ($offset - 1) * $limit)
            ->get()
            ->result_array();

        $data['status'] = 1;
        $data['data_list'] = $result;
        $data['data_page'] = array(
            'limit' => (float)$limit,
            'offset' => (float)$offset,
            'total' => (float)$total
        );
        return $data;
    }

    /**
     * 添加备注并记录日志
     * @param $datas
     * @return bool|null
     * @author W02278
     * CreateTime: 2019/4/25 11:06
     */
    public function addCfgRemark($datas)
    {
        if (!isset($datas['remark']) || !isset($datas['log']) || !isset($datas['cfg'])) {
            return null;
        }

        //开启事务
        $this->_db->trans_start();
        //添加备注
        $this->_db->insert($this->table_name, $datas['remark']);
        //更新主表remark
        $id = $datas['cfg']['id'];
        $this->_db->update('yibai_inland_global_rule_cfg' , ['remark' => $datas['cfg']['remark']] , " id = $id " );
        //记录日志
//        $this->_db->insert('yibai_inland_global_rule_cfg_log', $datas['log']);
        //事务结束
        $this->_db->trans_complete();

        if ($this->_db->trans_status() === FALSE)
        {
            $this->_db->trans_rollback();
            return false;
        }
        else
        {
            $this->_db->trans_commit();
            return true;
        }
    }
    
}