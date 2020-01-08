<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 *
 * @author W02278
 * @name  Inland_global_rule_cfg_log_model Class
 */
class Inland_global_rule_cfg_log_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_global_rule_cfg_log';
        $this->primaryKey = 'gid';
        $this->tableId = 101;
        parent::__construct();
    }

    /**
     * @w 获取列表
     * @param int $offset 页码
     * @param int $limit  每页数
     * @return array 日志列表
     * @author W02278
     * CreateTime: 2019/4/23 9:34
     */
    public function getList( $gid , $offset = 1, $limit = 20)
    {
        $query = $this->_db->from($this->table_name);
        $counter_query = clone $query;
        $total = $counter_query->count_all_results();
        $result = [];
        ( $total > 0 ) && $result = $query->select(' `gid`, `cid`, `uid`, `user_name`, `context`, `created_at` ')
            ->where('gid', $gid)
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
        return $result;
    }

    /**
     * 国内全局规则配置日志列表
     * @param null $gid
     * @param int $offset
     * @param int $limit
     * @return mixed
     * @throws Exception
     * @author W02278
     * CreateTime: 2019/4/25 11:46
     */
    public function getByCid($gid = null , $offset = 1, $limit = 20)
    {
        if (!$gid) {
            throw new \Exception('参数异常');
        }

        $query = $this->_db->from($this->table_name);
        $counter_query = clone $query;
        $total = $counter_query->where('cid', $gid)->count_all_results();
        $result = [];
        ( $total > 0 ) && $result = $query->select(' `user_name`, `context`, `created_at` ')
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



    
}