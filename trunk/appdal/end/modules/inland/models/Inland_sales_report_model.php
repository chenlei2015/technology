<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 *
 * @author W02278
 * @name  Inland_sales_report_model Class
 */
class Inland_sales_report_model extends MY_Model
{
    use Table_behavior;
    
    public function __construct()
    {
        $this->database = 'common';
        $this->table_name = 'yibai_inland_sales_report';
        $this->primaryKey = 'gid';
        $this->tableId = 103;
        parent::__construct();
    }

    /**
     * @param $where
     * @param int $offset
     * @param int $limit
     * @param $dates
     * @return mixed
     * @throws Exception
     * @author W02278
     * CreateTime: 2019/4/26 20:05
     */
    public function getReportList($dates, $where, $offset = 1, $limit = 20 )
    {
        if (!is_array($dates) || count($dates) != 28) {
            throw new \Exception('数据异常');
        }
        $result = [];
        $baseSql = ' select count(*) as counts from `yibai_inland_sales_report` ' . $where;
        $sql = " SELECT `gid`, (@i :=@i + 1) AS `i`, `created_at`, `sku`, `sku_name`, `sku_state`, `out_order_day`, `accumulated_sale_qty`, `sort`, `weight_sale_pcs`, `deliver_sd_day`, `supply_wa_day`, `sale_1` as `$dates[1]`, `sale_2` as `$dates[2]`, `sale_3` as `$dates[3]`, `sale_4` as `$dates[4]`, `sale_5` as `$dates[5]`, `sale_6` as `$dates[6]`, `sale_7` as `$dates[7]`, `sale_8` as `$dates[8]`, `sale_9` as `$dates[9]`, `sale_10` as `$dates[10]`, `sale_11` as `$dates[11]`, `sale_12` as `$dates[12]`, `sale_13` as `$dates[13]`, `sale_14` as `$dates[14]`, `sale_15` as `$dates[15]`, `sale_16` as `$dates[16]`, `sale_17` as `$dates[17]`, `sale_18` as `$dates[18]`, `sale_19` as `$dates[19]`, `sale_20` as `$dates[20]`, `sale_21` as `$dates[21]`, `sale_22` as `$dates[22]`, `sale_23` as `$dates[23]`, `sale_24` as `$dates[24]`, `sale_25` as `$dates[25]`, `sale_26` as `$dates[26]`, `sale_27` as `$dates[27]`, `sale_28` as `$dates[28]`
          from (SELECT @i := 0) AS it , `yibai_inland_sales_report` ". $where . " limit " . ($offset - 1) * $limit . ", $limit" ;
        $total = $this->_db->query($baseSql)->result_array();
        $total = $total[0]['counts'];

        $sqlNoLimit = " SELECT `gid`, (@i :=@i + 1) AS `i`, `created_at`, `sku`, `sku_name`, `sku_state`, `out_order_day`, `accumulated_sale_qty`, `sort`, `weight_sale_pcs`, `deliver_sd_day`, `supply_wa_day`, `sale_1` as `$dates[1]`, `sale_2` as `$dates[2]`, `sale_3` as `$dates[3]`, `sale_4` as `$dates[4]`, `sale_5` as `$dates[5]`, `sale_6` as `$dates[6]`, `sale_7` as `$dates[7]`, `sale_8` as `$dates[8]`, `sale_9` as `$dates[9]`, `sale_10` as `$dates[10]`, `sale_11` as `$dates[11]`, `sale_12` as `$dates[12]`, `sale_13` as `$dates[13]`, `sale_14` as `$dates[14]`, `sale_15` as `$dates[15]`, `sale_16` as `$dates[16]`, `sale_17` as `$dates[17]`, `sale_18` as `$dates[18]`, `sale_19` as `$dates[19]`, `sale_20` as `$dates[20]`, `sale_21` as `$dates[21]`, `sale_22` as `$dates[22]`, `sale_23` as `$dates[23]`, `sale_24` as `$dates[24]`, `sale_25` as `$dates[25]`, `sale_26` as `$dates[26]`, `sale_27` as `$dates[27]`, `sale_28` as `$dates[28]`
          from (SELECT @i := 0) AS it , `yibai_inland_sales_report` ". $where ;
        /**
         * 导出暂存sql
         */
        $this->load->library('rediss');
        $this->load->service('basic/SearchExportCacheService');
        $redisValues = [
            'total' => $total,
            'cols' => $dates,
            'sql' => $sqlNoLimit,
        ];
        $this->searchexportcacheservice->setScene($this->searchexportcacheservice::INLAND_SALES_REPORT_SEARCH_EXPORT)->set(serialize($redisValues));

        if ($total > 0 ) {
            $result = $this->_db->query($sql)->result_array();
        }
        //logger('error', 'sqlNote', $baseSql);
        //logger('error', 'sqlNote', $sql);

        $data['data_list'] = $result;
        $data['data_page'] = array(
            'limit' => (float)$limit,
            'offset' => (float)$offset,
            'total' => (float)$total,
//            'sql' => $sql,
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
            'context' => '修改 bs: ' . $datas['data']['bs']
                . ', sp: ' . $datas['data']['sp']
                . ', shipment_time: ' . $datas['data']['shipment_time']
                . ', sc: ' . $datas['data']['sc']
                . ', first_lt: ' . $datas['data']['first_lt']
                . ', sz: ' . $datas['data']['sz']
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

    
}