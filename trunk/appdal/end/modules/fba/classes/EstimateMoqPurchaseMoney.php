<?php

/**
 * 估算MOQ采购金额
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw
 */
class EstimateMoqPurchaseMoney
{

    /**
     * @var CI
     */
    private $ci;

    /**
     * @var string
     */
    protected $errorMess = '';

    /**
     * @var Model
     */
    protected $model;

    public function __construct()
    {
        $this->init_env_resource();
    }

    /**
     * 设置使用资源
     *
     * @return CsvReader
     */
    protected function init_env_resource()
    {
        set_time_limit(-1);

        ini_set('memory_limit', '512M');

        setlocale(LC_ALL, 'zh_CN');

        $this->ci = CI::$APP;

        $this->active_user = get_active_user();

        return $this;
    }

    public function estimate($version)
    {
        property_exists($this->ci, 'fba_pr_list') OR $this->ci->load->model('Fba_pr_list_model', 'fba_pr_list', false, 'fba');

        list($start_gid, $end_gid) = $this->ci->fba_pr_list->get_top_bottom_gid();
        if ($start_gid == '' || $end_gid == '') {
            return '0.00';
        }

        $estimate_version_key = 'rebuild_fba_'.$version;
        property_exists($this->ci, 'rediss') OR $this->ci->load->library('Rediss');

        $setLock = $this->ci->rediss->command(sprintf('setnx %s 1', $estimate_version_key));
        if (!$setLock) {
            log_message('ERROR', 'MOQ金额进程估算已经进行中,该进程请求拒绝');
            return '0.00';
        } else {
            $this->ci->rediss->command(sprintf('EXPIRE %s 300', $estimate_version_key));
        }

        $purchase_old_pr_table = 'yibai_old_purchase_pr_quantity';
        $purchase_new_pr_table = 'yibai_purchase_pr_quantity_'.date('d');

        $where_deny_reason = '';
        if (date('N') == 2) {
            $where_deny_reason = sprintf('deny_approve_reason in (%d, %d)', DENY_APPROVE_NONE, DENY_APPROVE_HALT_SKU);
        } else {
            $where_deny_reason = sprintf('deny_approve_reason = %d', DENY_APPROVE_NONE);
        }

        $sql = sprintf('SELECT
            tmp.supplier_code,tmp.sku, tmp.is_refund_tax, tmp.purchase_warehouse_id,tmp.purchase_price,
            tmp.require_qty, tmp.orign_require_qty,
        	@last_suggest_qty := if(tmp.orign_require_qty >= tmp.moq_qty, tmp.orign_require_qty,
    				if(tmp.moq_qty - tmp.orign_require_qty <= tmp.max_sp * tmp.weight_sale_pcs , tmp.moq_qty, 0)
    		) as final_require_qty,
    		@last_suggest_qty * tmp.purchase_price as final_require_money
         from (
        	    select
    				list.sku, list.is_refund_tax, list.purchase_warehouse_id,list.purchase_price,
    				list.supplier_code,
    				@moq_qty := list.moq_qty as moq_qty,
    				@max_sp := max(list.max_sp) as max_sp,
    				@weight_sale_pcs = sum(list.weight_sale_pcs) as weight_sale_pcs,
    			    sum(IF(list.require_qty <= 0, 0, list.require_qty)) as require_qty,
    			    sum(IF(list.require_qty <= 0, 0, list.require_qty) - IF(ods.available_qty, ods.available_qty, 0)
                    - IF(ods.on_way_stock, ods.on_way_stock, 0) - IF(t_pr.pr_qty, t_pr.pr_qty, 0)
                    - IF(t_new_pur.pr_quantity, t_new_pur.pr_quantity, 0) - IF(t_old_pur.pr_quantity, t_old_pur.pr_quantity, 0)
                    ) as orign_require_qty
        		from yibai_fba_pr_list list
                left join yibai_warehouse_sku_stock_ods ods on list.sku = ods.sku and list.warehouse_code = ods.warehouse_code
        		left join (
        			select
        				pr.sku, pr.is_refund_tax, pur.purchase_warehouse_id, sum(pr.push_stock_quantity) as pr_qty
        			from yibai_pr_number pr
        			inner join yibai_purchase_list pur on pr.pur_sn = pur.pur_sn
        			where pr.bussiness_line = %d and pr.state = %d
        			group by pr.sku, pr.is_refund_tax, pur.purchase_warehouse_id
        		) t_pr on list.sku = t_pr.sku and list.is_refund_tax = t_pr.is_refund_tax and list.purchase_warehouse_id = t_pr.purchase_warehouse_id
                left join %s t_new_pur on list.sku = t_new_pur.sku and list.is_refund_tax = t_new_pur.is_refund_tax and t_new_pur.bussiness_line = %d
                left join %s t_old_pur on list.sku = t_old_pur.sku and list.is_refund_tax = t_old_pur.is_refund_tax and t_old_pur.bussiness_line = %d
        		where
        		-- list.sku = "01EOT35400-220" and
        		list.approve_state = %d
        		and list.sku_state != %d and list.sku_state != %d
        		and list.listing_state = %d
        		and list.require_qty > 0
                and list.purchase_price > 0
                and %s
        		and list.gid >= "%s" and list.gid <= "%s"
        		and IF(list.require_qty <= 0, 0, list.require_qty) - IF(ods.available_qty, ods.available_qty, 0) - IF(ods.on_way_stock, ods.on_way_stock, 0) - if(t_pr.pr_qty, t_pr.pr_qty, 0) > 0
        		group by list.sku, list.is_refund_tax, list.purchase_warehouse_id
        ) tmp
        where tmp.orign_require_qty > 0
        and if(tmp.orign_require_qty >= tmp.moq_qty, tmp.orign_require_qty,
				if(tmp.moq_qty - tmp.orign_require_qty <= tmp.max_sp * tmp.weight_sale_pcs , tmp.moq_qty, 0)
		) > 0 order by tmp.supplier_code asc',
            BUSSINESS_FBA, GLOBAL_YES,
            $purchase_new_pr_table, BUSSINESS_FBA,
            $purchase_old_pr_table, BUSSINESS_FBA,
            APPROVAL_STATE_FIRST,SKU_STATE_DOWN,SKU_STATE_CLEAN, LISTING_STATE_OPERATING, $where_deny_reason, $start_gid, $end_gid
       );

        //pr($sql);exit;

        $index = 0;
        $save_nums = 0;
        $supplier_nums = 0;
        $pageSize = 500;
        $current_supplier = '__init__';
        $money = '0.00';

        $supplier_skus = [];
        $insert_purchase_sku_info = [];

        $start_time = microtime(true);
        $start_memory = memory_get_usage(true);

        $db = $this->ci->fba_pr_list->getDatabase();
        $query = $db->query_unbuffer($sql);

        /**
         * 一、如果归属于同一个供应商的sku只有一个 (同一个供应商需要采购的sku只有一个）
            1）采购量（已经过moq运算）对应的采购金额 >= 供应商起订金额2时，模拟最终采购建议量= 采购建议量（已经过moq运算）
            2）采购建议量（已经过moq运算）对应的采购金额 < 供应商起订金额2时，
                                           模拟最终采购建议量 = 提高备货数量，满足供应商起订金额2的数量；
         */

        try {

            //都是需要采购的，开始检测供应商的sku
            while ($row = $query->unbuffered_row('array')) {

                $index ++;

                $row['version'] = $version;
                $row['supplier_code'] = trim($row['supplier_code']);

                $key = $row['supplier_code'].'_'.$row['sku'];

                if ($current_supplier == '__init__') {
                    $current_supplier = $row['supplier_code'];
                    //存入供应商skus
                    $supplier_skus[$row['supplier_code']][$row['sku']] = ['money' => $row['final_require_money'], 'price' => $row['purchase_price']];
                    $insert_purchase_sku_info[$key] = $row;

                } elseif($current_supplier == $row['supplier_code']) {
                    if (isset($supplier_skus[$row['supplier_code']][$row['sku']])) {
                        $supplier_skus[$row['supplier_code']][$row['sku']]['money'] += $row['final_require_money'];
                    } else {
                        $supplier_skus[$row['supplier_code']][$row['sku']] = ['money' => $row['final_require_money'], 'price' => $row['purchase_price']];
                    }
                    $insert_purchase_sku_info[$key] = $row;
                } elseif ($current_supplier != $row['supplier_code']) {

                    $this->execute_supplier_moq_check($supplier_skus, $insert_purchase_sku_info);

                    //进行分批写入
                    if (count($insert_purchase_sku_info) % 100 == 0) {
                        $supplier_nums += 100;
                        $save_nums += $this->record($insert_purchase_sku_info);
                        log_message('INFO', sprintf('重建需求列表之后计算MOQ采购金额，目前索引：%d, 当前已经处理的供应商数量：%d, 成功写入记录：%d', $index, $supplier_nums, $save_nums));
                        $insert_purchase_sku_info = [];
                        $supplier_skus = [];
                    }

                    //开始存入新的
                    $current_supplier = $row['supplier_code'];
                    $insert_purchase_sku_info[$key] = $row;
                    $supplier_skus[$row['supplier_code']][$row['sku']] = ['money' => $row['final_require_money'], 'price' => $row['purchase_price']];
                }
            }

            //剩余的供应商，只剩下最后一个供应商没有判断完全
            if (!empty($insert_purchase_sku_info)) {
                $this->execute_supplier_moq_check($supplier_skus, $insert_purchase_sku_info);
                $supplier_nums += count($insert_purchase_sku_info);
                $save_nums += $this->record($insert_purchase_sku_info);
                log_message('INFO', sprintf('重建需求列表之后计算MOQ采购金额，循环已结束，清理最受剩余 当前已经处理的供应商数量：%d, 成功写入记录：%d', $index, $supplier_nums, $save_nums));
                $insert_purchase_sku_info = [];
                $supplier_skus = [];
            }

            unset($insert_purchase_sku_info, $supplier_skus);

            //转存结果
            $this->write_rebuild_total_money($version, $money = $this->sum($version));

            $cost_time = microtime(true) - $start_time;
            $cost_memory = (memory_get_usage(true) -  $start_memory)/1024/1024;

            log_message('INFO', sprintf('重建需求列表之后计算MOQ采购金额完成：总金额：%s, 当前内存：%s, 耗费时间：%d秒， 耗费内存：%s M', $money, strval(ceil(memory_get_usage(true)/1024/1024)), intval($cost_time), strval(intval($cost_memory)) ) );

        } catch (\Throwable $e) {
            log_message('ERROR', sprintf('重建需求列表之后计算MOQ采购金额,跑出异常：%s', $e->getMessage()));
        } finally {
            $query->free_result();
            $this->ci->rediss->command('del '.$estimate_version_key);
        }

        return $money;

    }

    /**
     * @param unknown $supplier_skus 记录供应商的sku信息
     * @param unknown $supplier_moq_info
     * @param unknown $insert_purchase_sku_info
     * @return boolean
     */
    private function execute_supplier_moq_check($supplier_skus, &$insert_purchase_sku_info)
    {
        static $func_sort_money;

        //sku采购金额倒叙
        if (!$func_sort_money) {
            $func_sort_money = function($a, $b) {
                return $b['money'] <=> $a['money'];
            };
        }

        //新供应商过来，开始处理上一个供应商
        end($supplier_skus);
        $pre_supplier_code = key($supplier_skus);

        //无供应商的sku
        if (is_null($pre_supplier_code) || trim($pre_supplier_code) == '') {
            log_message('ERROR', sprintf('如下sku：%s没有供应商信息，不进行moq计算', implode(',', array_keys($supplier_skus[$pre_supplier_code]))));
            return false;
        }

        //一个供应商取一次，全部加载10W+数组，内存会爆
        property_exists($this->ci, 'm_sku_cfg') OR $this->ci->load->model('Fba_sku_cfg_model', 'm_sku_cfg', false, 'fba');
        $supplier_moq_info = $this->ci->m_sku_cfg->get_supplier_moq_info([$pre_supplier_code]);

        //供应商信息
        $this_supplier_moq_info = $supplier_moq_info[$pre_supplier_code] ?? [];

        if (empty($this_supplier_moq_info)) {
            log_message('ERROR', sprintf('没有找到供应商编码%s信息，不进行moq计算', $pre_supplier_code));
            return false;
        }

        if (count($supplier_skus[$pre_supplier_code]) == 1) {

            $first_sku = key($supplier_skus[$pre_supplier_code]);

            $row = $supplier_skus[$pre_supplier_code][$first_sku];

            if (!isset($this_supplier_moq_info[$first_sku])) {
                log_message('ERROR', sprintf('没有找到供应商编码：%s的sku：%s MOQ信息，不进行moq运算', $pre_supplier_code, $first_sku));
                return false;
            }

            //没有设置
            if (isset($this_supplier_moq_info[$first_sku]['moq_step2']) && $row['money'] < $this_supplier_moq_info[$first_sku]['moq_step2']) {
                $insert_purchase_sku_info[$pre_supplier_code.'_'.$first_sku]['final_require_qty'] = ceil(round($this_supplier_moq_info[$first_sku]['moq_step2'] / $row['price'], 2));
                $insert_purchase_sku_info[$pre_supplier_code.'_'.$first_sku]['final_require_money'] = $insert_purchase_sku_info[$pre_supplier_code.'_'.$first_sku]['final_require_qty'] * $row['price'];
            }

        } else {
            // 二、如果归属于同一个供应商的sku有2个及以上  (同一个供应商需要采购的sku超过1个）
            // 1）采购建议量（已经过moq运算）之和 >= 供应商起订金额2时，模拟最终采购建议量= 采购建议量（已经过moq运算）
            // 2）采购建议量（已经过moq运算）之和  <供应商起订金额2时，取“总需求数量*成本价”最大的SKU进行增量
            // 模拟最终采购建议量 = 提高备货数量，满足供应商起订金额2的数量；

            $suppler_purchase_skus = $supplier_skus[$pre_supplier_code];
            uasort($suppler_purchase_skus, $func_sort_money);
            $max_sku = key($suppler_purchase_skus);

            if (!isset($this_supplier_moq_info[$max_sku])) {
                log_message('ERROR', sprintf('没有找到供应商编码：%s的sku：%s MOQ信息，不进行moq运算', $pre_supplier_code, $max_sku));
                return false;
            }

            if (($sum_purchase_money = array_sum(array_column($supplier_skus[$pre_supplier_code], 'money'))) < $this_supplier_moq_info[$max_sku]['moq_step2']) {
                $inc_moq_amount = ceil(($this_supplier_moq_info[$max_sku]['moq_step2'] - $sum_purchase_money) / $suppler_purchase_skus[$max_sku]['price']);
                $insert_purchase_sku_info[$pre_supplier_code.'_'.$max_sku]['final_require_qty'] += $inc_moq_amount;
                $insert_purchase_sku_info[$pre_supplier_code.'_'.$max_sku]['final_require_money'] = $insert_purchase_sku_info[$pre_supplier_code.'_'.$max_sku]['final_require_qty'] * $insert_purchase_sku_info[$pre_supplier_code.'_'.$max_sku]['purchase_price'];
            }
        }
        return true;
    }

    /**
     * 查询记录
     *
     * @param int $version
     * @return string decimal
     */
    protected function sum($version)
    {
        property_exists($this->ci, 'm_estimate_fba') OR $this->ci->load->model('Estimate_fba_moq_purchase_list_model', 'm_estimate_fba', false, 'fba');
        return $this->ci->m_estimate_fba->sum($version);
    }

    /**
     * @param int $version
     * @param string $money 格式化0.00的金额
     * @return db execute
     */
    protected function write_rebuild_total_money($version, $money)
    {
        property_exists($this->ci, 'm_rebuild_mvcc') OR $this->ci->load->model('Fba_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'fba');
        return $this->ci->m_rebuild_mvcc->write_estimate_moq_purchase_money($version, $money);
    }

    /**
     * 保持需要采购的记录
     *
     * @param array $params
     * @return int nums
     */
    protected function record($params)
    {
        property_exists($this->ci, 'm_estimate_fba') OR $this->ci->load->model('Estimate_fba_moq_purchase_list_model', 'm_estimate_fba', false, 'fba');
        return $this->ci->m_estimate_fba->madd($params);
    }

    /**
     * 取直接存的
     */
    public function get($version) : string
    {
        property_exists($this->ci, 'm_rebuild_mvcc') OR $this->ci->load->model('Fba_rebuild_mvcc_model', 'm_estimate_fba', false, 'fba');
        return $this->ci->get_estimate_moq_purchase_money($version);
    }

}