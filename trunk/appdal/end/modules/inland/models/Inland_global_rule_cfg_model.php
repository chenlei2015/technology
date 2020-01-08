<?php 

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 *
 * @author W02278
 * @name  Inland_global_rule_cfg_model Class
 */
class Inland_global_rule_cfg_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_inland_global_rule_cfg';
        $this->primaryKey = 'id';
        $this->tableId = 100;
        parent::__construct();
    }

    /**
     * @w 获取列表
     * @param int $offset 页码
     * @param int $limit  每页数
     * @return array    yibai_inland_global_rule_cfg列表
     * @author W02278
     * CreateTime: 2019/4/22 14:18
     */
    public function getList( $offset = 1, $limit = 20)
    {
        $query = $this->_db->from(' (SELECT @i := 0) AS it ,' . $this->table_name);
        $counter_query = clone $query;
        $total = $counter_query->count_all_results();
        $result = [];
        ( $total > 0 ) && $result = $query->select(' (@i :=@i + 1) AS `i`, `id`, `bs`, `sp`, `shipment_time`, `sc`, `first_lt`, `sz`, `created_at`, `updated_at`, `updated_zh_name`, `updated_uid`, `remark` ')
                         ->order_by('id', 'DESC')
                         ->limit($limit, ($offset - 1) * $limit)
                         ->get()
                         ->result_array();

        $data['data_list'] = $result;
        $data['data_page'] = array(
            'limit' => (float)$limit,
            'offset' => (float)$offset,
            'total' => (float)$total,
            'sql' => $query->last_query(),
        );
        return $data;
    }

    /**
     * @param null $gid
     * @return array
     * @throws Exception
     * @author W02278
     * CreateTime: 2019/4/23 10:38
     */
    public function getByGid($id = null)
    {
        if (!$id) {
            throw new \Exception('参数异常');
        }
        $result = $this->_db->from($this->table_name)
            ->select(' `id`, `bs`, `sp`, `shipment_time`, `sc`, `first_lt`, `sz`, `created_at`, `updated_at`, `updated_zh_name`, `updated_uid`, `remark` ')
            ->where('id', $id)
            ->limit(1)
            ->get()
            ->result_array();
        if ($result)
        {
            return $result[0];
        }

        return [];
    }

    /**
     * @param $gid
     * @return array
     * @author W02278
     * CreateTime: 2019/4/24 15:25
     */
    public function pk($gid)
    {
        $result = $this->_db->from($this->table_name)->where('id', $gid)->limit(1)->get()->result_array();
        if ($result)
        {
            return $result[0];
        }
        else
        {
            log_message('ERROR', sprintf('Inland_pr_list_model 根据主键: %s获取记录失败, 当前数据库：%s', $gid, json_encode(array_keys(self::$_dbCaches))));
            return [];
        }
    }

    /**
     * 更新国内全局规则配置并添加日志
     * @param $datas
     * @return bool|null
     * @author W02278
     * CreateTime: 2019/4/24 19:44
     */
    public function updateCfg($datas)
    {
        if (!isset($datas['where']) || !isset($datas['data'])) {
            return null;
        }
        $dataLog = array(
            'gid' => gen_id(101),
            'cid' => $datas['where']['id'],
            'uid' => $datas['data']['updated_uid'],
            'user_name' => $datas['data']['updated_zh_name'],
            'context' => '修改 缓冲库存天数(bs): ' . $datas['data']['bs']
                . ', 备货处理周期(sp): ' . $datas['data']['sp']
                . ', 发运时效(shipment_time): ' . $datas['data']['shipment_time']
                . ', 一次备货天数(sc): ' . $datas['data']['sc']
                . ', 首次供货周期(first_lt): ' . $datas['data']['first_lt']
                . ', 服务对应"z"值(sz): ' . $datas['data']['sz']
        ,
            'created_at' => $datas['data']['updated_at'],
        );

        //开启事务
        $this->_db->trans_start();
        //更新配置
        $this->_db->update($this->table_name, $datas['data'], $datas['where']);
        //记录日志
        $this->db->insert('yibai_inland_global_rule_cfg_log', $dataLog);
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


    public function getOne(){
        return $this->_db->select('*')->from($this->table_name)->where('id',1)->get()->row_array();
    }

    public function getOneGlobal(){
        return $this->_db->select('*')->from($this->table_name)->limit(1)->get()->row_array();
    }


    /**
     * 获取配置
     * @param $gid
     * @return mixed
     */
    public function get_cfg(){
        $this->_db->from($this->table_name);
        return $this->_db->select('bs,sp,shipment_time,first_lt,sc,sz')->where('id',1)->get()->row_array();
    }

    /**
     *Notes:获取国内全局规则配置表的配置数据
     *User: lewei
     *Date: 2019/11/21
     *Time: 10:57
     */
   public function get_cfg_param(){
       $this->_db->from($this->table_name);
       return $this->_db->select('bs,sp,shipment_time,first_lt,sc,sz')->limit(1)->get()->row_array();
   }
    
}