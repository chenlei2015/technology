<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba重建需求多版本控制model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @date 2019-09-05
 * @link
 */
class Fba_rebuild_mvcc_model extends MY_Model
{

    use Table_behavior;

    private $_rpc_module = 'fba';

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_fba_rebuild_mvcc';
        $this->primaryKey = 'version';
        $this->tableId = 0;
        parent::__construct();
    }

    /**
     * 获取最近的版本记录
     * @param unknown $business_line
     * @param bool $must_today 今天
     * @return array|unknown
     */
    public function get_last_version($business_line, $must_today = false)
    {
        $query =  $this->_db->from($this->table_name)->where('business_line', $business_line)->order_by('version', 'desc')->limit(1);
        if ($must_today) {
            $today_start = date('Y-m-d').' 00:00:00';
            $today_end = date('Y-m-d').' 23:59:59';
            $query->where("created_at between '{$today_start}' and '{$today_end}'");
        }
        $result = $query->get()->result_array();
        return empty($result) ? [] : $result[0];
    }

    /**
     * 构建一个mvcc记录
     *
     * @param unknown $business_line
     * @param unknown $total
     * @return array
     */
    public function add($business_line)
    {
        //检测文件
        if (!file_exists(APPPATH . 'upload/rebuild_cfg.php')){
            throw new \RuntimeException('rebuild_cfg.php'."配置文件不存在");
        }
        require APPPATH . 'upload/rebuild_cfg.php';//引入文件
        $rebuild_cfg = compact(['sales_amount_cfg', 'exhaust_cfg', 'in_warehouse_age_cfg', 'supply_day_cfg',  'sale_category_cfg']);
        //变量检测
        foreach (['sales_amount_cfg', 'exhaust_cfg', 'in_warehouse_age_cfg', 'supply_day_cfg',  'sale_category_cfg'] as $k => $v){
            if(!isset($rebuild_cfg[$v])){
                throw new \RuntimeException('rebuild_cfg.php'."配置文件中缺少：".$v);
            }
        }
        $rebuild_cfg = json_encode($rebuild_cfg);
        $version_row = $this->get_last_version($business_line);

        if (empty($version_row) || $version_row['state'] == REBUILD_CFG_FINISHED || date('Y-m-d', strtotime($version_row['created_at'])) != date('Y-m-d'))
        {
            $ci = CI::$APP;
            $ci->load->model('Fba_pr_list_model', 'fba_pr_list', false, 'fba');

            $staff_code = get_active_user()->staff_code;

            $new_version = ($version_row['version'] ?? 0) + 1;

            $this->_db->reset_query();

            $insert = [
                    'version' => $new_version,
                    'created_at' => date('Y-m-d H:i:s'),
                    'created_uid' => $staff_code,
                    'total' => $ci->fba_pr_list->get_today_pr_total(),
                    'business_line' => $business_line,
                    'state' => REBUILD_CFG_BACKUP_OVER,
                    'processed' => 0,
                    'offset' => 0,
                    'strategy'  => $rebuild_cfg
            ];

            if ($insert['total'] == 0) {
                throw new \RuntimeException('今天没有有效的需求单，无法重新计算需求列表');
            }
            $result = $this->_db->insert($this->table_name, $insert);
            if ($result) {
                return [true, $insert];
            }
        }
        else
        {
            return [false, $version_row];
        }
    }

    public function get_process($business_line, $version = -1)
    {
        if ($version == -1) {
            $row = $this->get_last_version($business_line);
        } else {
            $row = $this->_db->from($this->table_name)->where('version', $version)->where('business_line', $business_line)->get()->result_array();
        }
        if (empty($row)) return 0;
        if ($row[0]['state'] == REBUILD_CFG_FINISHED) {
            return 100;
        } else {
            return intval($row[0]['processed'] * 100 / $row[0]['total']);
        }
    }

    public function get_versions($date)
    {
        $date_start = $date.' 00:00:00';
        $date_end = $date.' 23:59:59';
        $result = $this->_db->from($this->table_name)
        ->select('version,state')
        ->where("created_at between '${date_start}' and '${date_end}'")
        ->order_by('version desc')
        ->get()
        ->result_array();
        return $result;
    }

    /**
     * 每日凌晨清理昨天配置表
     *
     * @throws \RuntimeException
     * @return int
     */
    public function clean()
    {
        $yesterday = date('Y-m-d', strtotime('-1 days'));
        $records = $this->get_versions($yesterday);
        if (empty($records)) {
            return 0;
        }

        $fail_version = $delete_version = [];

        $max_succ_version = 0;
        foreach ($records as $row)
        {
            if ($max_succ_version == 0 && $row['state'] != REBUILD_MVCC_FINISH) {
                $fail_version[] = $row['version'];
                $delete_version[] = $row['version'];
                continue;
            }
            if ($max_succ_version == 0 && $row['state'] == REBUILD_MVCC_FINISH) {
                $max_succ_version = $row['version'];
                continue;
            }
            $delete_version[] = $row['version'];
        }

        if (empty($delete_version)) {
            return 0;
        }

        $clean_sql = sprintf('delete from %s where version in (%s)', 'yibai_fba_rebuild_backup_sku_cfg', implode(',', $delete_version));
        $clean_estimate_sql = sprintf('delete from %s where version in (%s)', 'yibai_estimate_fba_moq_purchase_list', implode(',', $delete_version));

        if ($this->_db->query($clean_sql) && $this->_db->query($clean_estimate_sql)) {
            $delete_row = $this->_db->affected_rows();
            return $delete_row;
        } else {
            throw new \RuntimeException('清理重建需求配置表执行sql失败', 500);
        }
    }

/**
     * 写入已经订好的金额
     *
     * @param int $version
     * @param string $money
     * @return db execute result
     */
    public function write_estimate_moq_purchase_money($version, $money)
    {
        $this->_db->where('version', $version);
        return $this->_db->update($this->table_name, ['moq_purchase_money' => $money]);
    }

    /**
     * 写入已经订好的金额
     *
     * @param int $version
     * @param string $money
     * @return db execute result
     */
    public function get_estimate_moq_purchase_money($version)
    {
        $result = $this->_db->from($this->table_name)->select('moq_purchase_money')->where('version', $version)->get()->result_array();
        return empty($result) ? '0.00' : sprintf('%0.2f', $result[0]['moq_purchase_money']);
    }



}