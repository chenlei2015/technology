<?php

/**
 * FBA 需求服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class PrService
{
    public static $s_system_log_name = 'FBA';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_pr_list_model', 'fba_pr_list', false, 'fba');
        $this->_ci->load->helper('fba_helper');
        return $this;
    }

    /**
     * 添加一条备注, 成功为true，否则抛异常
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params, $priv_uid = -1)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        $record = $this->_ci->fba_pr_list->find_by_pk($gid);
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        //如果我是记录的管理员，可以添加备注
        $active_user = get_active_user();
        if ($priv_uid != -1)
        {
            //这条记录的账号是否是我管辖的
            $account_name = $active_user->get_my_manager_accounts();
            if (empty($account_name) || !in_array($record['account_name'], $account_name))
            {
                //不是我管辖
            }
            else
            {
                $priv_uid = -1;
            }
        }
        if ($priv_uid != -1 && $priv_uid != $record['salesman'])
        {
            throw new \InvalidArgumentException(sprintf('您无权限操作他人的记录'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->fba_pr_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        $this->_ci->Record->set('updated_uid', get_active_user()->staff_code);

        $db = $this->_ci->fba_pr_list->getDatabase();

        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('FBA列表更新备注失败'), 500);
        }
        if (!$this->add_list_remark($params))
        {
            throw new \RuntimeException(sprintf('FBA列表插入备注失败'), 500);
        }
        $db->trans_complete();

        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('FBA添加备注事务提交完成，但检测状态为false'), 500);
        }

        return true;
    }

    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_list_remark($params)
    {
        $this->_ci->load->model('Fba_pr_list_remark_model', 'fba_pr_list_remark', false, 'fba');
        append_login_info($params);
        $insert_params = $this->_ci->fba_pr_list_remark->fetch_table_cols($params);
        return $this->_ci->fba_pr_list_remark->add($insert_params);
    }

    /**
     * 是否触发计划审核
     *
     * ~v1.1.2: $record->get('is_trigger_pr') == TRIGGER_PR_YES &&  $record->get('bd') > 0
     *  v1.1.2: 如下， 不在判断是否触发需求
     *
     *  v1.2.2 修改一次修正量
     */
    protected function is_trigger_plan_approve(Record $record)
    {
        return  $record->get('fixed_amount') > 0;
    }

    /**
     * v1.0.0
     * require_qty - stocked_qty > 0
     *
     * v1.0.1 新增：
     * FBA可用+FBA待上架+国际在途+已备货（新增需求）>=订购点   触发需求为“N”
     * FBA可用+FBA待上架+国际在途+已备货（新增需求）< 订购点   触发需求为“Y”
     *
     * v1.1.2 新增：
     *
     * 主动：1、FBA可用 + FBA待上架 + FBA国际在途 + 国内已准备数量 >= 订购点 + BD ? “N” : “Y”
     * 被动：FBA可用 + FBA待上架 + FBA国际在途+ 国内已准备数量 - （订购点 + BD） >= 加权日均销量 * 发货周期 ? “N” : “Y”
     *
     * @param Record $record
     * @return boolean
     */
    protected function is_trigger_pr(Record $record)
    {
        if ($record->is_accelerate_sale == ACCELERATE_SALE_YES) {
            return false;
        } elseif ($record->is_accelerate_sale == ACCELERATE_SALE_NO) {
            //否，正常， 是否处于7天内，时间戳
            $accelerate_sale_end_time = strtotime($record->accelerate_sale_end_time);
            if (false === $accelerate_sale_end_time) {
                $end_time = 0;
            } else {
                $end_time = $accelerate_sale_end_time + 86400;
            }
            $in_seven_days = $end_time + 86400 * 7 > time();
            if ($in_seven_days) {
                return false;
            }
        }

        if ($record->trigger_mode == TRIGGER_MODE_INACTIVE)
        {
            $trigger_info = self::parse_ext_trigger_info($record->ext_trigger_info);
            return $record->available_qty + $record->exchange_up_qty + $record->oversea_ship_qty + $record->stocked_qty - $record->point_pcs - $record->bd - $record->fixed_amount < $record->weight_sale_pcs * $trigger_info['delivery_cycle'];
        }
        elseif ($record->trigger_mode == TRIGGER_MODE_ACTIVE)
        {
            return $record->available_qty + $record->exchange_up_qty + $record->oversea_ship_qty + $record->stocked_qty - $record->point_pcs - $record->bd - $record->fixed_amount < 0;
        }
        else
        {
            return false;
        }
    }

    /**
     * 解析主/被动触发的扩展属性
     *
     * @param unknown $trigger_info
     */
    public static function parse_ext_trigger_info($trigger_info)
    {
        if (!is_string($trigger_info) || $trigger_info == '')
        {
            return [];
        }
        $columns = [
            'delivery_cycle', 'sales_15_day', 'sales_30_day'
        ];

        $trigger_arr    = explode(',', $trigger_info);
        $trigger_arr[1] = $trigger_arr[1]??'';
        $trigger_arr[2] = $trigger_arr[2]??'';
        if (count($trigger_arr) != count($columns))
        {
            log_message('ERROR', sprintf('FBA字段ext_trigger_info记录delivery_cycle信息错误，预期一个字段，实际：%s', $trigger_info));
            throw new \InvalidArgumentException(sprintf('ext_trigger_infoMRP初始写入错误，实际写入：%s', $trigger_info), 500);
        }

        return array_combine($columns, $trigger_arr);
    }

    /**
     * 解析一条扩展物流属性信息
     *
     * @param string $logistics_info
     * @return array[]
     */
    public static function parse_ext_logistics_info($logistics_info)
    {
        if (!$logistics_info) return [];
        $columns = [
                'logistics_id',
                'require_qty',
                'is_trigger_pr',
                'pre_day',
                'safe_stock_pcs',
                'point_pcs'
        ];
        $columns_counter = count($columns);
        $logistics_list_data = [];
        $logistics_arr = explode(';', $logistics_info);

        foreach ($logistics_arr as $one)
        {
            $one_logistics = explode(',', $one);
            if ($columns_counter != count($one_logistics))
            {
                throw new \InvalidArgumentException('该需求单的扩展物流属性MRP初始写入错误，需按照 “物流方式id,需求数量,是否触发需求,备货提前期,安全库存,订购点”的顺序写入，实际写入“'.$one.'”', 500);
            }
            if (intval($one_logistics[0]) == LOGISTICS_ATTR_LAND)
            {
                continue;
            }
            $one_row = array_combine($columns, $one_logistics);
            $logistics_list_data[$one_row['logistics_id']] = $one_row;
        }
        return $logistics_list_data;
    }

    public static function parse_ext_plan_rebuild_vars($ext_plan_rebuild_vars, $prefix = '')
    {
        if (empty($ext_plan_rebuild_vars)) {
            return [];
        }
        $rebuild_vars = json_decode($ext_plan_rebuild_vars, true);
        if ($prefix != '') {
            $rebuild_arr = [];
            foreach ($rebuild_vars as $col => $val) {
                $rebuild_arr[$prefix.$col] = $val;
            }
            unset($rebuild_vars);
            return $rebuild_arr;
        }
        return $rebuild_vars;
    }

    /**
     * 重新计算需求数量
     *
     * v1.1.2
     * 主动触发：需求数量 = （订购点 + BD - （可用库存 + 待上架 + 国际在途） + 订购数量） * 扩销系数（可配置，依据销量设置）
     *
     * 被动触发：需求数量 = 订购数量 * 扩销系数（可配置）
     *
     * 不触发： 需求数量 0
     *
     * v1.2.1 需求数量不用处理负值
     *
     * v1.2.2 新版本计算
     *
     * @param Record $record
     * @return number
     */
    protected function recalc_required_qty(Record $record)
    {
        if ($record->is_accelerate_sale == ACCELERATE_SALE_YES) {
            return 0;
        } elseif ($record->is_accelerate_sale == ACCELERATE_SALE_NO) {
            //否，正常， 是否处于7天内，时间戳
            $accelerate_sale_end_time = strtotime($record->accelerate_sale_end_time);
            if (false === $accelerate_sale_end_time) {
                $end_time = 0;
            } else {
                $end_time = $accelerate_sale_end_time + 86400;
            }
            $in_seven_days = $end_time + 86400 * 7 > time();
            if ($in_seven_days) {
                return 0;
            }
        }

        $sale_factor = $record->expand_factor;

        if ($record->trigger_mode == TRIGGER_MODE_INACTIVE)
        {
            return ceil($record->purchase_qty * $sale_factor);
        }
        elseif ($record->trigger_mode == TRIGGER_MODE_ACTIVE)
        {
            return ceil(($record->point_pcs + $record->purchase_qty + $record->bd + $record->fixed_amount - $record->available_qty - $record->exchange_up_qty - $record->oversea_ship_qty) * $sale_factor);
        }
        else
        {
            return ceil(($record->point_pcs + $record->purchase_qty + $record->bd + $record->fixed_amount - $record->available_qty - $record->exchange_up_qty - $record->oversea_ship_qty) * $sale_factor);
        }
    }

    /**
     *
     * 检测bd修改是否有原主动触发修改为不触发
     *
     * v1.1.2
     *
     * 主动触发A通过修改BD后变为不触发，则被动触发B也同步更改为不触发
     *
     * @param Record $record
     * @return bool
     */
    protected function check_trigger_active_to_unactive(Record $record)
    {
        return $record->origin()['is_trigger_pr'] == TRIGGER_PR_YES &&
            $record->origin()['trigger_mode'] == TRIGGER_MODE_ACTIVE &&
            $record->trigger_mode != TRIGGER_MODE_ACTIVE;
    }

    /**
     * v1.1.2
     *
     * 非主动修改为主动的时候，需要拉取因主动取消而设置的被动sku
     *
     * @param Record $record
     * @return bool
     */
    protected function check_trigger_become_active(Record $record)
    {
        return $record->origin()['trigger_mode'] != TRIGGER_MODE_ACTIVE &&
            $record->trigger_mode == TRIGGER_MODE_ACTIVE &&
            $record->is_trigger_pr = TRIGGER_PR_YES;
    }

    /**
     * 判断修改bd之后的主/被动情况
     *
     * 主动：1、FBA可用 + FBA待上架 + FBA国际在途 + 国内已准备数量 < 订购点 + BD
     * 被动：FBA可用 + FBA待上架 + FBA国际在途+ 国内已准备数量 - （订购点 + BD） < 加权日均销量 * 发货周期
     *
     * TRIGGER_MODE_UNKOWN状态包含TRIGGER_MODE_INACTIVE, TRIGGER_MODE_NONE模式。
     *
     * v1.2.2
     *
     * @param Record $record
     * @param bool $exists_active 存在主动触发记录
     * @param bool $is_batch_edit true 批量修改 false 单个修改
     *
     * @return int 触发模式
     */
    protected function get_trigger_mode_after_bd(Record $record, $existed_active, $is_batch_edit)
    {
        if ($record->is_accelerate_sale == ACCELERATE_SALE_YES) {
            return TRIGGER_MODE_NONE;
        } elseif ($record->is_accelerate_sale == ACCELERATE_SALE_NO) {
            //否，正常， 是否处于7天内，时间戳
            $accelerate_sale_end_time = strtotime($record->accelerate_sale_end_time);
            if (false === $accelerate_sale_end_time) {
                $end_time = 0;
            } else {
                $end_time = $accelerate_sale_end_time + 86400;
            }
            $in_seven_days = $end_time + 86400 * 7 > time();
            if ($in_seven_days) {
                return TRIGGER_MODE_NONE;
            }
        }

        //首先判断主动情况
        $active_mode = $record->available_qty + $record->exchange_up_qty + $record->oversea_ship_qty + $record->stocked_qty - $record->point_pcs - $record->bd - $record->fixed_amount  < 0;
        if ($active_mode) return TRIGGER_MODE_ACTIVE;

        $trigger_info = self::parse_ext_trigger_info($record->ext_trigger_info);
        $inactive_mode = $record->available_qty + $record->exchange_up_qty + $record->oversea_ship_qty + $record->stocked_qty - $record->point_pcs - $record->bd - $record->fixed_amount < $record->weight_sale_pcs * $trigger_info['delivery_cycle'];
        if (!$inactive_mode)
        {
            return TRIGGER_MODE_NONE;
        }

        //是否存在任意一个主动触发的记录, 只有主动触发的存在，被动触发才有意义， 但主动触发有可能存在于接下来的批量操作中。
        //所以，本批次不处理完，是不确定这时的状态是被动还是为空的
        if ($existed_active)
        {
            return TRIGGER_MODE_INACTIVE;
        }
        else
        {
            if (!$is_batch_edit)
            {
                return TRIGGER_MODE_NONE;
            }

            //取值后面出现主动为被动，没有则变为空
            return TRIGGER_MODE_UNKOWN;
        }
    }

    /**
     * 检测除了指定gid_row之外是否还存在主动记录
     *
     * @depend Fba_pr_list_model get_trigger_sku_summary_info
     *
     * @param string $gid
     * @return bool
     */
    protected function is_sku_only_active_row($gid_row, $sku_active_summary)
    {
        $require_cols = array_flip(['gid', 'sku', 'trigger_mode', 'is_trigger_pr']);
        if (count($diff = array_diff_key($require_cols, $gid_row)) > 0)
        {
            throw new \InvalidArgumentException('检测是否是唯一主动记录缺少必要字段:'.implode(',', array_keys($diff)), 500);
        }

        $yes =
            $gid_row['is_trigger_pr'] == TRIGGER_PR_YES &&
            $gid_row['trigger_mode'] == TRIGGER_MODE_ACTIVE &&
            ($sku_active_summary[$gid_row['sku']][$gid_row['trigger_mode']]['num'] ?? 0) == 1 &&
            ($sku_active_summary[$gid_row['sku']][$gid_row['trigger_mode']]['gids'] ?? '') == $gid_row['gid'];

        return $yes;
    }

    /**
     * 检测gid_row是不是唯一的主动记录
     *
     * @param unknown $gid_row
     * @param unknown $sku_active_summary
     * @throws \InvalidArgumentException
     * @return boolean
     */
    protected function is_only_active_is_mine($gid_row, $sku_active_summary)
    {
        $require_cols = array_flip(['gid', 'sku', 'trigger_mode', 'is_trigger_pr']);
        if (count($diff = array_diff_key($require_cols, $gid_row)) > 0)
        {
            throw new \InvalidArgumentException('检测是否是唯一主动记录缺少必要字段:'.implode(',', array_keys($diff)), 500);
        }

        $yes =
        $gid_row['is_trigger_pr'] == TRIGGER_PR_YES &&
        $gid_row['trigger_mode'] == TRIGGER_MODE_ACTIVE;

        if (!$yes) return $yes;

        return ($sku_active_summary[$gid_row['sku']][$gid_row['trigger_mode']]['gids'] ?? '') == $gid_row['gids'];

    }

    /**
     * 排除$gid_row是否还存在主动记录
     *
     * @param unknown $gid_row
     * @param unknown $sku_active_summary
     * @throws \InvalidArgumentException
     * @return boolean
     */
    protected function has_other_active_rows($gid_row, $sku_active_summary)
    {
        $require_cols = array_flip(['gid', 'sku', 'trigger_mode', 'is_trigger_pr']);
        if (count($diff = array_diff_key($require_cols, $gid_row)) > 0)
        {
            throw new \InvalidArgumentException('检测是否是唯一主动记录缺少必要字段:'.implode(',', array_keys($diff)), 500);
        }

        $active_gids = $sku_active_summary[$gid_row['sku']][TRIGGER_MODE_ACTIVE]['gids'] ?? '';
        return $active_gids == $gid_row['gid'] ? false : $active_gids != '';
    }


    /**
     * 全局检测sku是否满足 主动记录消失 或 重新生成主动记录 因此触发被动记录的升级和降级操作
     *
     * @param string $sku 有变化的sku
     * @param array $trigger_sku_summary 批量操作所有sku的汇总
     * @param array $batch_edit_sku_info 批量操作出现模式变化的记录
     * @return string up 拉取空值为被动模式， down 被动模式为无  '' 没有变化
     */
    protected function check_trigger_change($sku, $trigger_sku_summary, $batch_edit_sku_info)
    {
        $history_actived_num = $trigger_sku_summary[$sku][TRIGGER_MODE_ACTIVE]['num'] ?? 0;
        $decr = $batch_edit_sku_info[$sku]['to_unactive'] ?? 0;
        $incr = $batch_edit_sku_info[$sku]['to_active'] ?? 0;

        //有主动变成了无主动
        if ($history_actived_num > 0 && $incr == 0 && $history_actived_num <= $decr)
        {
            return 'down';
        }
        //无主动有了主动
        elseif($history_actived_num == 0 && $incr > 0)
        {
            return 'up';
        }
        //无需变化
        return '';
    }

    /**
     * csv导入修改
     *
     * @param unknown $params
     * @return unknown
     */
    public function csv_edit_fixed_amount($params)
    {
        $this->_ci->load->classes('fba/classes/CsvWrite', BUSINESS_LINE_FBA);
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->load->model('Fba_pr_list_log_model', 'fba_pr_list_log', false, 'fba');

        //解析格式，获得数据
        $this->_ci->CsvWrite->decode_csv_reader($params)
        //注册过滤参数，包含权限控制
        ->bind_filter_cvs_rows(array($this->_ci->fba_pr_list, 'get_can_fixed_amount'))
        //前置回调
        ->register_callback('before', function() {})
        //后置回调
        ->register_callback('after', function() {})
        //列回调
        //->register_columns_recalc_callback('fixed_amount', function(){})
        ->register_columns_recalc_callback('updated_at', function($new_row, $old_row){
            return time();
        })
        ->register_columns_recalc_callback('updated_uid', function($new_row, $old_row){
            return get_active_user()->staff_code;
        })
        //设置语言文件
        ->set_langage('fba_pr_list')
        //设置更新字段
        ->set_can_edit_cols(['fixed_amount'])
        //设置更新表model
        ->set_model($this->_ci->fba_pr_list)
        //日志model
        ->set_log_model($this->_ci->fba_pr_list_log);

        $this->_ci->CsvWrite->run();

        return $this->_ci->CsvWrite->report;
    }

    /**
     * 批量修改一次修正量, 待需求数量和主被动触发，区别于batch_edit_fixed_amount仅仅修改fixed_amount
     *
     * @param array $params <pre>
     *  primary_key,
     *  map,
     *  selected
     *  </pre> 参数这样设置主要是为了减少传输数据大小
     * @param mixed $priv_uid -1, staff_code
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return boolean
     */
    public function batch_edit_fixed_amount($params)
    {
        $report = [
            'total'      => 0,
            'processed'  => 0,
            'undisposed' => 0,
            'errorMess'  => ''
        ];
        $index_to_cols = array_flip($params['map']);
        $selected = json_decode($params['selected'], true);
        unset($params['selected']);

        $report['total'] = $report['undisposed'] = $params['total'];
        $is_batch = $report['total'] == 1 ? false : true;

        if (empty($selected))
        {
            $report['errorMess'] = '没有有效的记录';
            return $report;
        }

        $active_user = get_active_user();
        $salesman = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? '*' : $active_user->staff_code;
        $manager_accounts = $active_user->get_my_manager_accounts();
        $records = $this->_ci->fba_pr_list->get_can_fixed_amount(array_keys($selected), $salesman, $manager_accounts);

        if (empty($records)) {
            $report['errorMess'] = '没有有效的记录';
            return $report;
        }

        //获取待处理sku的主动状态
        $trigger_sku_summary =& $this->_ci->fba_pr_list->get_trigger_sku_summary_info(array_unique(array_column($records, 'sku')));

        foreach ($records as $key => $val)
        {
            if ($val['fixed_amount'] == $selected[$val['pr_sn']][$params['map']['fixed_amount']])
            {
                unset($records[$key]);
                continue;
            }

            //扣除自己作为主动记录的
            $active_trigger_gids = $trigger_sku_summary[$val['sku']][TRIGGER_MODE_ACTIVE]['other_gids'] ?? '';
            if ($active_trigger_gids != '' && $val['trigger_mode'] == TRIGGER_MODE_ACTIVE)
            {
                if (false !== ($index = array_search($val['gid'], $gid_arr = explode(',', $active_trigger_gids))))
                {
                    unset($gid_arr[$index]);
                    $trigger_sku_summary[$val['sku']][$val['trigger_mode']]['other_gids'] = implode(',', $gid_arr);
                    $trigger_sku_summary[$val['sku']][$val['trigger_mode']]['other_num'] -= 1;
                }
            }
        }

        if (empty($records))
        {
            $report['errorMess'] = '没有修改任何一次修正量';
            return $report;
        }

        $batch_update_pr = $batch_insert_log = $gids = [];
        $trigger_change_skus = $batch_inactive_params = $trigger_to_active = $trigger_unknown = [];
        $become_unactive_skus = $become_active_skus = [];

        $can_edit_cols   = ['fixed_amount', 'require_qty', 'is_plan_approve', 'is_trigger_pr', 'trigger_mode', 'is_lost_active_trigger'];
        $update_time     = time();
        $bd_start_time   = strtotime(date('Y-m-d'));
        $updated_uid     = $active_user->staff_code;
        $updated_name    = $active_user->user_name;
        $lang            = [];
        $old_row         = [];

        $this->_ci->lang->load('common');
        $all_lines = $this->_ci->lang->line('fba_pr_list');
        foreach ($all_lines as $lan)
        {
            $lang[$lan['field']] = $lan['label'];
        }

        $this->_ci->load->classes('basic/classes/Record');

        reset($records);
        foreach ($records as $val)
        {
            foreach ($can_edit_cols as $col)
            {
                $old_row[$val['pr_sn']][$col] = $val[$col];
            }

            $this->_ci->Record->recive($val)->enable_extra_property();
            foreach ($selected[$val['pr_sn']] as $index => $edit_val)
            {
                $edit = $index_to_cols[$index];
                if (in_array($edit, $can_edit_cols))
                {
                    $this->_ci->Record->set($edit, $edit_val);
                }
            }

            $this->_ci->Record->set('updated_at', $update_time);
            $this->_ci->Record->set('updated_uid', $updated_uid);

            //记录修改了bd的gid
            $gids[$val['gid']] = 1;

            //设置自己的主被动状态
            $running_trigger_mode = $this->get_trigger_mode_after_bd(
                $this->_ci->Record,
                //是否存在选择记录之外的主动记录
                ($trigger_sku_summary[$val['sku']][TRIGGER_MODE_ACTIVE]['other_num'] ?? 0) > 0,
                $is_batch
             );

            if ($running_trigger_mode == TRIGGER_MODE_UNKOWN)
            {
                //记录并观察后面是否出现同sku且变为主动的情况
                $running_trigger_mode = isset($trigger_to_active[$val['sku']]) ? TRIGGER_MODE_INACTIVE : $running_trigger_mode;
            }
            elseif ($running_trigger_mode ==  TRIGGER_MODE_ACTIVE)
            {
                $trigger_to_active[$val['sku']] = true;
            }
            $this->_ci->Record->set('trigger_mode', $running_trigger_mode);

            //受主/被动模式影响的数据
            if ($running_trigger_mode != TRIGGER_MODE_UNKOWN)
            {
                $this->_ci->Record->set('is_trigger_pr', $this->is_trigger_pr($this->_ci->Record) ? TRIGGER_PR_YES : TRIGGER_PR_NO);
                $this->_ci->Record->set('require_qty', $this->recalc_required_qty($this->_ci->Record));
            }

            $this->_ci->Record->set('is_plan_approve', $this->is_trigger_plan_approve($this->_ci->Record) ? NEED_PLAN_APPROVAL_YES : NEED_PLAN_APPROVAL_NO);
            $this->_ci->Record->set('approve_state', APPROVAL_STATE_FIRST);
            $this->_ci->Record->set('is_lost_active_trigger', TRIGGER_LOST_ACTIVE_NORMAL);

            //记录主动模式下触发变更为不触发的sku, 非主动变为主动
            if ($this->check_trigger_active_to_unactive($this->_ci->Record))
            {
                $trigger_change_skus[$val['sku']]['to_unactive'] = ($trigger_change_skus[$val['sku']]['to_unactive'] ?? 0 ) + 1;
            }
            elseif ($this->check_trigger_become_active($this->_ci->Record))
            {
                $trigger_change_skus[$val['sku']]['to_active'] = ($trigger_change_skus[$val['sku']]['to_active'] ?? 0 ) + 1;
            }

            $update_row = $this->_ci->Record->get();

            //记录TRIGGER_MODE_UNKOWN的情况
            if ($running_trigger_mode == TRIGGER_MODE_UNKOWN)
            {
                $trigger_unknown[$val['sku']] = $update_row;
            }
            else
            {
                $batch_update_pr[] = $update_row;
            }
        }

        //v1.1.2 检测完成之后再确定unkonwn状态
        if (!empty($trigger_unknown))
        {
            //重新检测
            foreach ($trigger_unknown as $sku => &$update)
            {
                $update['trigger_mode'] = isset($trigger_to_active[$sku]) ? TRIGGER_MODE_INACTIVE : TRIGGER_MODE_NONE;

                //重新计算受影响的值
                $this->_ci->Record->recive($val)->enable_extra_property();
                $this->_ci->Record->set('is_trigger_pr', $this->is_trigger_pr($this->_ci->Record) ? TRIGGER_PR_YES : TRIGGER_PR_NO);
                $this->_ci->Record->set('require_qty', $this->recalc_required_qty($this->_ci->Record));
                $update['require_qty'] = $this->_ci->Record->get('require_qty');
                $update['is_trigger_pr'] = $this->_ci->Record->get('is_trigger_pr');
            }
            $batch_update_pr = array_merge($batch_update_pr, $trigger_unknown);
        }

        if (!empty($trigger_change_skus))
        {
            foreach ($trigger_change_skus as $sku => $info)
            {
                switch ($this->check_trigger_change($sku, $trigger_sku_summary, $trigger_change_skus))
                {
                    case 'up':
                        //获取被降级的记录
                        $become_active_skus[] = $sku;
                        break;
                    case 'down':
                        //获取需要降级的记录
                        $become_unactive_skus[] = $sku;
                        break;
                    default:
                        break;
                }
            }
        }

        //检测主动触发变更为不触发需求时并且唯一时，被动触发同样设置为不触发需求
        if (!empty($become_unactive_skus))
        {
            $become_unactive_skus = array_unique($become_unactive_skus);
            $inactive_rows = $this->_ci->fba_pr_list->get_inactive_trigger_list($become_unactive_skus);

            if (!empty($inactive_rows))
            {
                foreach ($inactive_rows as $row)
                {
                    //优先级 手动修改 > 强制更新
                    if (isset($gids[$row['gid']]))
                    {
                        continue;
                    }
                    //进行更新操作
                    $batch_inactive_params[] = [
                            'gid' => $row['gid'],
                            'updated_at' => time(),
                            'updated_uid' => $active_user->staff_code,
                            'is_trigger_pr' => TRIGGER_PR_NO,
                            'trigger_mode' => TRIGGER_MODE_NONE,
                            'is_lost_active_trigger' => TRIGGER_LOST_ACTIVE_YES,
                    ];
                    //写入一条日志
                    $batch_insert_log[] = [
                            'gid' => $row['gid'],
                            'uid' => $updated_uid,
                            'user_name' => $updated_name,
                            'context' => sprintf('主动触发记录一次修正量修改为不触发，被动触发被强制修改为不触发')
                    ];
                }
            }
        }

        //非主动变更为主动，拉取因主动取消而取消的记录
        if (!empty($become_active_skus))
        {
            $become_active_skus = array_unique($become_active_skus);
            $trigger_down_rows = $this->_ci->fba_pr_list->get_trigger_down_list($become_active_skus);

            if (!empty($trigger_down_rows))
            {
                foreach ($trigger_down_rows as $row)
                {
                    //优先级 手动修改 > 强制更新
                    if (isset($gids[$row['gid']]))
                    {
                        continue;
                    }
                    //进行更新操作
                    $batch_inactive_params[] = [
                            'gid' => $row['gid'],
                            'updated_at' => time(),
                            'updated_uid' => $active_user->staff_code,
                            'is_trigger_pr' => TRIGGER_PR_YES,
                            'trigger_mode' => TRIGGER_MODE_INACTIVE,
                            'is_lost_active_trigger' => TRIGGER_LOST_ACTIVE_NORMAL,
                    ];
                    //写入一条日志
                    $batch_insert_log[] = [
                            'gid' => $row['gid'],
                            'uid' => $updated_uid,
                            'user_name' => $updated_name,
                            'context' => sprintf('一次修正量修改重新出现主动记录，原被动触发记录因失去主动记录而被取消的记录被重新唤醒')
                    ];
                }
            }
        }

        $filter_cols = array_merge(['gid' => 1], array_flip($can_edit_cols));
        foreach ($batch_update_pr as $key => &$row)
        {
            $context = [];
            foreach ($can_edit_cols as $col)
            {
                $context[] = sprintf('%s由%s修改为%s', $lang[$col] ?? $col, (string)$old_row[$row['pr_sn']][$col], (string)$row[$col]);
            }

            $row = array_intersect_key($row, $filter_cols);

            $batch_insert_log[] = [
                'gid' => $row['gid'],
                'uid' => $updated_uid,
                'user_name' => $updated_name,
                'context' => mb_substr(implode(',', $context), 0, 300),
            ];
        }

        //事务开始
        $this->_ci->load->model('Fba_pr_list_log_model', 'fba_pr_list_log', false, 'fba');
        $this->_ci->load->model('Bd_list_model', 'm_bd_list', false, 'fba');
        $db = $this->_ci->fba_pr_list->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新主记录
            $update_rows = $db->update_batch($this->_ci->fba_pr_list->getTable(), $batch_update_pr, 'gid');
            if (!$update_rows)
            {
                throw new \RuntimeException(sprintf('批量修改一次修正量列表失败'), 500);
            }

            //批量更新被动记录
            if (!empty($batch_inactive_params))
            {
                $update_rows = $db->update_batch($this->_ci->fba_pr_list->getTable(), $batch_inactive_params, 'gid');
                if (!$update_rows)
                {
                    throw new \RuntimeException(sprintf('批量修改一次修正量关联更新列表失败'), 500);
                }
            }

            //插入bd修改日志
            if (!empty($gids))
            {
                $replace_bd_sql = sprintf(
                    'REPLACE INTO %s '.
                    'SELECT MD5(CONCAT(sku,station_code,asin,seller_sku)) as hash, %d, \'%s\', %d FROM %s WHERE gid in (%s)',
                    $this->_ci->m_bd_list->getTable(),
                    BUSSINESS_FBA,
                    date('Y-m-d H:i:s'),
                    $bd_start_time,
                    $this->_ci->fba_pr_list->getTable(),
                    array_where_in(array_keys($gids))
                    );
                if (!$db->query($replace_bd_sql))
                {
                    throw new \RuntimeException(sprintf('批量修改BD记录失败'), 500);
                }
            }

            //插入日志
            $insert_rows = $this->_ci->fba_pr_list_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                throw new \RuntimeException(sprintf('批量修改一次修正量插入日志失败'), 500);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('修改一次修正量，事务提交成功，但状态检测为false'), 500);
            }

            //发送系统日志
            //$this->_ci->load->service('basic/SystemLogService');
            //$this->_ci->systemlogservice->send([], self::$s_system_log_name, '批量修改BD'.count($records).'条记录， 关联记录'.count($batch_inactive_params).'条');

            $report['processed'] = count($batch_update_pr);;
            $report['undisposed'] = $report['total'] - $report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = $gids = $become_unactive_skus = $batch_inactive_params = $records = null;
            unset($batch_update_pr, $batch_insert_log, $gids, $become_unactive_skus, $batch_inactive_params, $records);

            return $report;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('修改一次修正量，提交事务出现异常: %s', $e->getMessage()));

            //释放资源
            $batch_update_pr = $batch_insert_log = $gids = $become_unactive_skus = $batch_inactive_params = $records = null;
            unset($batch_update_pr, $batch_insert_log, $gids, $become_unactive_skus, $batch_inactive_params, $records);

            throw new \RuntimeException(sprintf('修改一次修正量，提交事务出现异常'), 500);
        }
    }

    public function rebuild_pr()
    {

        $this->_ci->load->classes('fba/classes/FbaRebuildPr');
        $this->_ci->load->model('Fba_pr_list_log_model', 'fba_pr_list_log', false, 'fba');
        $this->_ci->load->model('Fba_activity_list_model', 'm_fba_activity', false, 'fba');

        //获取有效的活动
        $future_valid_activities = $this->_ci->m_fba_activity->get_future_valid_activities();

        //获取配置的受禁类目
        $this->_ci->load->model('Category_cfg_model', 'm_cate', false, 'fba');
        $restrict_categories = $this->_ci->m_cate->get_restrict_category(BUSSINESS_FBA);

        $config_path = APPPATH . 'upload/rebuild_cfg.php';
        if (file_exists($config_path)) {
            include($config_path);

            if (isset($sales_amount_cfg) && isset($exhaust_cfg) && isset($in_warehouse_age_cfg) &&
                isset($supply_day_cfg) && isset($sale_category_cfg)) {

                //$amount =  [15 => 2, 30 => 38888];
                $get_sales_amount_factor = function($amount) use ($sales_amount_cfg) {
                    foreach($sales_amount_cfg as $cfg) {
                        $find = true;
                        foreach($cfg['cfg'] as $step => $set) {
                            if (!$find) continue;
                            $num = $amount[$step];
                            switch ($set[0]) {
                                case '<=':
                                    $find = $find && $num <= $set[1];
                                    break;
                                case '<':
                                    $find = $find && $num < $set[1];
                                    break;
                                case '>=':
                                    $find = $find && $num >= $set[1];
                                    break;
                                case '<=':
                                    $find = $find && $num <= $set[1];
                                    break;
                                case '==':
                                    $find = $find && $num == $set[1];
                                    break;
                            }
                        }
                        if ($find) {
                            return $cfg['factor'];
                        }
                    }
                    log_message('ERROR', '销售系数表未匹配任何值, amount='.json_encode($amount).' 配置：'.json_encode($sales_amount_cfg));
                    return false;
                };


                $get_exhaust_factor = function($amount) use ($exhaust_cfg) {
                    //$min_max = [
                    //    'min' => PHP_INT_MIN,
                    //    'max' => PHP_INT_MAX,
                    //];
                    foreach($exhaust_cfg as $cfg) {
                        if (is_string($cfg[0]) && $amount <= $cfg[1]) {
                            return $cfg[2];
                        } elseif (is_string($cfg[1]) && $amount >= $cfg[1]) {
                            return $cfg[2];
                        } elseif ($amount >= $cfg[0] && $amount <= $cfg[1]) {
                            return $cfg[2];
                        }
                    }
                    log_message('ERROR', '断货天数系数未匹配任何值, amount='.$amount.' 配置：'.json_encode($exhaust_cfg));
                    return false;
                };


                $get_warehouse_age_factor = function($warehouse_age_cfg) use ($in_warehouse_age_cfg) {
                    foreach($in_warehouse_age_cfg as $cfg) {
                        if ($cfg[0] == $warehouse_age_cfg) {
                            return $cfg[1];
                        }
                    }
                    log_message('ERROR', '库龄配置系数未匹配任何值, amount='.$amount.' 配置：'.json_encode($in_warehouse_age_cfg));
                    return false;
                };

                $get_supply_factor = function($amount) use ($supply_day_cfg) {
                    foreach($supply_day_cfg as $cfg) {
                        if (is_string($cfg[0]) && $amount <= $cfg[1]) {
                            return $cfg[2];
                        } elseif (is_string($cfg[1]) && $amount >= $cfg[1]) {
                            return $cfg[2];
                        } elseif ($amount >= $cfg[0] && $amount <= $cfg[1]) {
                            return $cfg[2];
                        }
                    }
                    log_message('ERROR', '可售卖天数系数未匹配任何值, amount='.$amount.' 配置：'.json_encode($supply_day_cfg));
                    return false;
                };


                $get_z_scday_expand_factor = function($amount) use ($sale_category_cfg) {
                    foreach($sale_category_cfg as $cfg) {
                        if (is_string($cfg[0]) && $amount <= $cfg[1]) {
                            return array_slice($cfg, 2);
                        } elseif (is_string($cfg[1]) && $amount >= $cfg[1]) {
                            return array_slice($cfg, 2);
                        } elseif ($amount >= $cfg[0] && $amount < $cfg[1]) {
                            return array_slice($cfg, 2);
                        }
                    }
                    log_message('ERROR', '销量分类系数未匹配任何值, amount='.$amount.' 配置：'.json_encode($sale_category_cfg));
                    return false;
                };
            }
            //log_message('INFO', '销售系数表存在, 请检查路径：'.$config_path.' 现在开始无配置下的重建需求列表');
        } else {
            throw new \InvalidArgumentException('销售系数表不存在, 请检查路径：'.$config_path.' 请上传之后重新运行');
            log_message('ERROR', '销售系数表不存在, 请检查路径：'.$config_path.' 现在开始无配置下的重建需求列表');
        }

        //设置前缀
        $prefix = $this->_ci->FbaRebuildPr->get_prefix();
        $diff_varx_py_prefix = $prefix['python'];
        $diff_varx_php_prefix = $prefix['php'];

        $get_php_trans_colname = function($origin_col) use ($diff_varx_php_prefix) {
            return $diff_varx_php_prefix.$origin_col;
        };
        $get_py_trans_colname = function($origin_col) use ($diff_varx_py_prefix) {
            return $diff_varx_py_prefix.$origin_col;
        };

        //解析格式，获得数据
        $this->_ci->FbaRebuildPr
        //前置回调
        ->register_callback('before', function() {})
        //后置回调
        ->register_callback('after', function() {})
        //设置语言文件
        ->set_langage('fba_pr_list')
        //设置更新表model
        ->set_model($this->_ci->fba_pr_list)
        //日志model
        ->set_log_model($this->_ci->fba_pr_list_log);

        $this->_ci->load->model('Fba_python_cfg_model', 'm_python_cfg', false, 'fba');

        if ($this->_ci->m_python_cfg->is_exists_python_config()) {
            $this->_ci->FbaRebuildPr->enable_python_cfg();
        }

        $use_calc_system = $this->_ci->FbaRebuildPr->get_main_system();

        $this->_ci->FbaRebuildPr->register_columns_recalc_callback(
            //在开始备货时间之后，活动开始时间之前，活动量有效；活动开始后，活动量失效
            'bd',
            function($new_row) use ($future_valid_activities) {
                $bd = 0;
                $current_timestamp = time();
                if ($new_row['station_code'] == 'eu') {
                    $aggr_id = md5(trim($new_row['sku']).trim($new_row['seller_sku']).trim($new_row['fnsku']).trim($new_row['asin']).trim($new_row['account_num']));
                } else {
                    $aggr_id = md5(trim($new_row['account_id']).trim($new_row['seller_sku']).trim($new_row['sku']).trim($new_row['fnsku']).trim($new_row['asin']));
                }

                foreach ($future_valid_activities[$aggr_id] ?? [] as $act)
                {
                    if ($current_timestamp > strtotime($act['execute_purcharse_time']) && $current_timestamp < strtotime($act['activity_start_time'])) {
                        $bd += $act['amount'];
                    }
                }
                return $bd;
            }
            )
            ->register_columns_recalc_callback(
                //仓库编码
                'warehouse_code',
                function($new_row) {
                    return PURCHASE_WAREHOUSE[$new_row['purchase_warehouse_id']]['code'];
                }
                )
            ->register_columns_recalc_callback(
                //是否计划审核 在一级审核通过的情况下，若fixed_amount>0，就要触发计划审核，“Y”，否则为“N”。
                'is_plan_approve',
                function($new_row) {
                    return $new_row['fixed_amount'] > 0 ? NEED_PLAN_APPROVAL_YES : NEED_PLAN_APPROVAL_NO;
                }
           )
        ->register_columns_recalc_callback(
        //交付周期, 这里会重新计算
        'ext_trigger_info',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname, $get_sales_amount_factor,$get_exhaust_factor,$get_warehouse_age_factor,$get_supply_factor,$get_z_scday_expand_factor) {

                $ext_cfg = explode(',', $new_row['ext_trigger_info']);
                if (!isset($ext_cfg[1]) || !isset($ext_cfg[2]) || '' == $ext_cfg[1] || '' == $ext_cfg[2]) {
                    throw new \RuntimeException('重建需求列表失败，ext_trigger_info未保存15天，30天销量值，需求单号：'.$new_row['pr_sn'].' ext_trigger_info值：'.$new_row['ext_trigger_info'], 500);
                }
                $new_row['delivery_cycle'] = $ext_cfg[0];

                if ($use_calc_system == 'python') {
                    //php版本
                    $new_row[$get_php_trans_colname('delivery_cycle')] = $ext_cfg[0];
                    $new_row[$get_php_trans_colname('sale_amount_15_days')] = $ext_cfg[1];
                    $new_row[$get_php_trans_colname('sale_amount_30_days')] = $ext_cfg[2];
                    $amount = [SALE_AMOUNT_DAYS_STEP1 => $ext_cfg[1], SALE_AMOUNT_DAYS_STEP2 => $ext_cfg[2]];
                    $new_row[$get_php_trans_colname('sales_factor')] = $get_sales_amount_factor($amount);
                    $z_scday_expand_factor = $get_z_scday_expand_factor($new_row[$get_php_trans_colname('weight_sale_pcs')]);

                    $new_row[$get_php_trans_colname('z')] = $z_scday_expand_factor[0];
                    $new_row[$get_php_trans_colname('sc_day')] = $z_scday_expand_factor[1];
                    $new_row[$get_php_trans_colname('expand_factor')] = $z_scday_expand_factor[2];
                    $new_row[$get_php_trans_colname('min_order_qty')] = $z_scday_expand_factor[3];

                    //缓存天数
                    $new_row[$get_php_trans_colname('actual_bs')] = round($new_row['bs'] * $new_row[$get_php_trans_colname('sales_factor')] * $new_row['exhaust_factor'] * $new_row['warehouse_age_factor'] * $new_row['supply_factor']);
                    //最大安全天数
                    $new_row[$get_php_trans_colname('actual_safe_stock')] = round($new_row['max_safe_stock'] * $new_row[$get_php_trans_colname('sales_factor')] * $new_row['exhaust_factor'] * $new_row['warehouse_age_factor'] * $new_row['supply_factor']);
                    //php版本的ext_trigger_info
                    $new_row[$get_php_trans_colname('ext_trigger_info')] = implode(',', [$ext_cfg[0], $amount[SALE_AMOUNT_DAYS_STEP1], $amount[SALE_AMOUNT_DAYS_STEP2]]);

                    //python版本 - 默认主系统
                    $amount = [SALE_AMOUNT_DAYS_STEP1 => $new_row['sale_amount_15_days'], SALE_AMOUNT_DAYS_STEP2 => $new_row['sale_amount_30_days']];
                }
                else
                {
                    $amount = [SALE_AMOUNT_DAYS_STEP1 => $ext_cfg[1], SALE_AMOUNT_DAYS_STEP2 => $ext_cfg[2]];
                }

                $new_row['sales_factor'] = $get_sales_amount_factor($amount);
                $new_row['exhaust_factor'] = $get_exhaust_factor($new_row['exhausted_days']);
                $new_row['warehouse_age_factor'] = $get_warehouse_age_factor($new_row['is_warehouse_days_90']);
                $new_row['supply_factor'] = $get_supply_factor($new_row['supply_day']);
                $z_scday_expand_factor = $get_z_scday_expand_factor($new_row['weight_sale_pcs']);
                $new_row['z'] = $z_scday_expand_factor[0];
                $new_row['sc_day'] = $z_scday_expand_factor[1];
                $new_row['expand_factor'] = $z_scday_expand_factor[2];
                $new_row['min_order_qty'] = $z_scday_expand_factor[3];
                //缓存天数
                $new_row['actual_bs'] = floor($new_row['bs'] * $new_row['sales_factor'] * $new_row['exhaust_factor'] * $new_row['warehouse_age_factor'] * $new_row['supply_factor']);
                //最大安全天数
                $new_row['actual_safe_stock'] = floor($new_row['max_safe_stock'] * $new_row['sales_factor'] * $new_row['exhaust_factor'] * $new_row['warehouse_age_factor'] * $new_row['supply_factor']);

                return implode(',', [$new_row['delivery_cycle'], $amount[SALE_AMOUNT_DAYS_STEP1], $amount[SALE_AMOUNT_DAYS_STEP2]]);
        }
        )
        ->register_columns_recalc_callback(
            //备货提前期(day) = 备货处理周期+供货周期+打包时效+物流时效+上架时效+缓冲库存+发货周期
            'pre_day',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {
                if ($use_calc_system == 'python') {
                    //php
                    $new_row[$get_php_trans_colname('pre_day')] = $new_row['sp'] + $new_row['lt'] + $new_row['pt'] + $new_row['as_up'] + $new_row['ls'] + $new_row['bs'] + $new_row[$get_php_trans_colname('delivery_cycle')];
                }
                return $new_row['sp'] + $new_row['lt'] + $new_row['pt'] + $new_row['as_up'] + $new_row['ls'] + $new_row['actual_bs'] + $new_row['delivery_cycle'];
            }
        )
        ->register_columns_recalc_callback(
            //安全库存 = Z*（（备货提前期*销量标准偏差的平方+加权日均销量的平方*交付标准偏差的平方）的平方根）
            'safe_stock_pcs',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {

                $acutal_safe_stock = ceil($new_row['actual_safe_stock'] * $new_row['weight_sale_pcs']);
                $safe_stock_pcs = ceil($new_row['z'] * sqrt( $new_row['pre_day'] * pow($new_row['sale_sd_pcs'], 2) + pow($new_row['weight_sale_pcs'], 2) * pow($new_row['avg_deliver_day'], 2) ) );

                if ($use_calc_system == 'python') {
                    $php_actual_safe_stock =
                    ceil(
                        $new_row['z'] * sqrt(
                            $new_row[$get_php_trans_colname('pre_day')] * pow($new_row[$get_php_trans_colname('sale_sd_pcs')], 2)
                            +
                            pow($new_row[$get_php_trans_colname('weight_sale_pcs')], 2) * pow($new_row[$get_php_trans_colname('avg_deliver_day')], 2)
                            )
                        );
                    $new_row[$get_php_trans_colname('safe_stock_pcs')] = min($acutal_safe_stock, $php_actual_safe_stock);
                }

                $safe_stock_pcs = min($safe_stock_pcs, $acutal_safe_stock);

                return $safe_stock_pcs;
            }
            )
        ->register_columns_recalc_callback(
        //订购点 = （加权日均销量 * 备货提前期）+ 安全库存
        'point_pcs',
        function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {
            if ($use_calc_system == 'python') {
                //php
                $new_row[$get_php_trans_colname('point_pcs')] = ceil($new_row[$get_php_trans_colname('weight_sale_pcs')] * $new_row[$get_php_trans_colname('pre_day')]) + $new_row['safe_stock_pcs'];
            }
            return ceil($new_row['weight_sale_pcs'] * $new_row['pre_day']) + $new_row['safe_stock_pcs'];
        }
        )
        ->register_columns_recalc_callback(
            //可支撑天数  = （FBA可用库存 + FBA待上架数 + FBA国际在途数量） / 加权日均销量
            //库存为0， 加权销量为0， 支撑为0
            //库存>0, 加权销量为0， 10000
            'supply_day',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname)  {
                if ($use_calc_system == 'python') {
                    //php版本
                    $stock = $new_row[$get_php_trans_colname('available_qty')] + $new_row[$get_php_trans_colname('exchange_up_qty')] + $new_row[$get_php_trans_colname('oversea_ship_qty')];
                    if ($stock == 0 && $new_row[$get_php_trans_colname('weight_sale_pcs')] == 0) {
                        $new_row[$get_php_trans_colname('supply_day')] = 0;
                    } elseif ($stock > 0 && $new_row[$get_php_trans_colname('weight_sale_pcs')] == 0) {
                        $new_row[$get_php_trans_colname('supply_day')] = 10000;
                    } else {
                        $new_row[$get_php_trans_colname('supply_day')] = floor( $stock / $new_row[$get_php_trans_colname('weight_sale_pcs')]);
                    }
                }

                //python
                $stock = $new_row['available_qty'] + $new_row['exchange_up_qty'] + $new_row['oversea_ship_qty'];
                if ($stock == 0 && $new_row['weight_sale_pcs'] == 0) {
                    return 0;
                } elseif ($stock > 0 && $new_row['weight_sale_pcs'] == 0) {
                    return 10000;
                }
                return floor( $stock / $new_row['weight_sale_pcs']);
            }
            )
        ->register_columns_recalc_callback(
            //订购数量 = 加权日均销量 * 一次备货天数
            'purchase_qty',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {
                if ($use_calc_system == 'python') {
                    //php版本
                    $new_row[$get_php_trans_colname('purchase_qty')] = $new_row[$get_php_trans_colname('weight_sale_pcs')] * $new_row['sc_day'];
                }
                return $new_row['weight_sale_pcs'] * $new_row['sc_day'];
            }
        )
        ->register_columns_recalc_callback(
            //触发需求模式
            'require_qty',
            function(&$new_row) use ($use_calc_system, $get_php_trans_colname, $get_py_trans_colname, $future_valid_activities) {
                //丢失python配置暂当做0处理
                if (isset($new_row['lost_python_cfg']) && 1 === $new_row['lost_python_cfg']) {
                    $new_row['is_trigger_pr'] = TRIGGER_PR_NO;
                    $new_row['trigger_mode'] = TRIGGER_MODE_NONE;
                    $new_row['require_qty_second'] = 0;
                    return 0;
                }
                //加快动销 清仓
                if ($new_row['is_accelerate_sale'] == ACCELERATE_SALE_YES) {
                    $new_row['is_trigger_pr'] = TRIGGER_PR_NO;
                    $new_row['trigger_mode'] = TRIGGER_MODE_NONE;
                    $new_row['require_qty_second'] = 0;
                    return 0;
                } elseif ($new_row['is_accelerate_sale'] == ACCELERATE_SALE_NO) {
                    //否，正常， 是否处于7天内，时间戳
                    $accelerate_sale_end_time = strtotime($new_row['accelerate_sale_end_time']);
                    if (false === $accelerate_sale_end_time) {
                        $end_time = 0;
                    } else {
                        $end_time = $accelerate_sale_end_time;
                    }
                    $in_seven_days = $end_time + 86400 * 7 > time();
                    if ($in_seven_days) {
                        $new_row['is_trigger_pr'] = TRIGGER_PR_NO;
                        $new_row['trigger_mode'] = TRIGGER_MODE_NONE;
                        $new_row['require_qty_second'] = 0;
                        return 0;
                    }
                }

                //bd使用量的判断 在开始备货时间之后，活动开始时间之前，活动量有效；活动开始后，活动量失效, bd已判斷
                $bd = $new_row['bd'];

                if ($use_calc_system == 'python') {

                    $stocked_qty      = $new_row['stocked_qty'];
                    $fixed_amount     = $new_row['fixed_amount'];

                    $available_qty    = $new_row[$get_php_trans_colname('available_qty')];
                    $exchange_up_qty  = $new_row[$get_php_trans_colname('exchange_up_qty')];
                    $oversea_ship_qty = $new_row[$get_php_trans_colname('oversea_ship_qty')];
                    $point_pcs        = $new_row[$get_php_trans_colname('point_pcs')];
                    $purchase_qty     = $new_row[$get_php_trans_colname('purchase_qty')];
                    $expand_factor    = $new_row[$get_php_trans_colname('expand_factor')];

                    //php计算情况
                    //全部按照主动计算
                    $new_row['require_qty_second'] = ($point_pcs + $bd + $fixed_amount + $purchase_qty - $available_qty - $exchange_up_qty - $oversea_ship_qty) * $expand_factor;
                }

                //首先判断主动情况
                $active_mode = $new_row['available_qty'] + $new_row['exchange_up_qty'] + $new_row['oversea_ship_qty'] + $new_row['stocked_qty']  < $new_row['point_pcs'] + $bd + $new_row['fixed_amount'];

                //判断符合被动触发条件
                $inactive_mode = $new_row['available_qty'] + $new_row['exchange_up_qty'] + $new_row['oversea_ship_qty'] + $new_row['stocked_qty']
                - $new_row['point_pcs'] - $bd - $new_row['fixed_amount'] < $new_row['weight_sale_pcs'] * $new_row['delivery_cycle'];

                //log_message('INFO', sprintf('需求单号：%s gid:%s 触发模式 %d 主动检测:%s 被动检测：%s, 行信息：%s', $new_row['pr_sn'], $new_row['gid'], $new_row['trigger_mode'], $active_mode?'主动':'继续', $inactive_mode?'被动待确认':'都不是', json_encode($new_row)));

                if ($active_mode) {
                    $new_row['is_lost_active_trigger'] = TRIGGER_LOST_ACTIVE_NORMAL;
                    $new_row['is_trigger_pr'] = TRIGGER_PR_YES;
                    $new_row['trigger_mode'] = TRIGGER_MODE_ACTIVE;
                    //需求数量 = （订购点 + 活动量+一次修正量 - （可用库存 + 待上架 + 国际在途） + 订购数量）* 扩销系数（可配置，依据销量设置）
                    $require_qty = ($new_row['point_pcs'] + $bd + $new_row['fixed_amount'] + $new_row['purchase_qty'] - $new_row['available_qty'] - $new_row['exchange_up_qty'] - $new_row['oversea_ship_qty']) * $new_row['expand_factor'];
                    $require_qty = floor($require_qty);
                    return $require_qty;
                } elseif ($inactive_mode) {
                    //符合被动触发的设置
                    //$new_row['is_lost_active_trigger'] = TRIGGER_LOST_ACTIVE_YES;
                    //还要回归判断是否有主动的才行，如果没有主动，则为
                    //需求数量 = 订购数量 * 扩销系统（可配置）
                    //$new_row['inactive_require_qty'] =  $new_row['purchase_qty'] * $new_row['expand_factor'];
                    //$new_row['active_require_qty'] = ($new_row['point_pcs'] + $new_row['bd'] + $new_row['fixed_amount'] + $new_row['purchase_qty'] - $new_row['available_qty'] - $new_row['exchange_up_qty'] - $new_row['oversea_ship_qty']) * $new_row['expand_factor'];
                    $new_row['trigger_mode'] = TRIGGER_MODE_REBUILD_TEMP;
                    return 0;
                } else {
                    $new_row['is_lost_active_trigger'] = TRIGGER_LOST_ACTIVE_NORMAL;
                    //即不主动又不被动
                    $new_row['trigger_mode'] = TRIGGER_MODE_NONE;
                    $new_row['is_trigger_pr'] = TRIGGER_PR_NO;
                    //需求数量 = （订购点 + 活动量+一次修正量 -（可用库存 + 待上架 + 国际在途） + 订购数量）  * 扩销系数（可配置，依据销量设置）
                    return ($new_row['point_pcs'] + $bd + $new_row['fixed_amount'] + $new_row['purchase_qty'] - $new_row['available_qty'] - $new_row['exchange_up_qty'] - $new_row['oversea_ship_qty']) * $new_row['expand_factor'];
                }
            }
            )
            ->register_columns_recalc_callback(
                //健康度
                'inventory_health',
                function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {

                    if ($use_calc_system == 'python') {
                        //php计算
                        $available_qty    = $new_row[$get_php_trans_colname('available_qty')];
                        $exchange_up_qty  = $new_row[$get_php_trans_colname('exchange_up_qty')];
                        $oversea_ship_qty = $new_row[$get_php_trans_colname('oversea_ship_qty')];
                        $stocked_qty      = $new_row['stocked_qty'];
                        $point_pcs        = $new_row[$get_php_trans_colname('point_pcs')];
                        $purchase_qty     = $new_row[$get_php_trans_colname('purchase_qty')];
                        $expand_factor    = $new_row[$get_php_trans_colname('expand_factor')];
                        $weight_sale_pcs  = $new_row[$get_php_trans_colname('weight_sale_pcs')];
                        $delivery_cycle   = $new_row[$get_php_trans_colname('delivery_cycle')];

                        $stock = $available_qty + $oversea_ship_qty;

                        if ($stock == 0 && (bccomp($weight_sale_pcs, 0, 2) == 0)) {
                            $new_row['inventory_health'] = INVENTORY_HEALTH_WELL;
                        } elseif ($stock > 0 && (bccomp($weight_sale_pcs, 0, 2) == 0)) {
                            $new_row['inventory_health'] = INVENTORY_HEALTH_EMERGENT;
                        } else {
                        //严重积压
                        //（1）平均库龄超过60天
                        //（1）可用库存数超过90天（可用库存=（在途+在库）/（日销*扩销系数））
                        $supply_days = ($stock ) / ($weight_sale_pcs * $expand_factor);
                        if ($new_row['avg_inventory_age'] > SERIOUT_UNSALABLE_INVENTORY_DAYS || $supply_days > SERIOUT_SUPPLY_INVENTORY_DAYS) {
                            $new_row['inventory_health'] = INVENTORY_HEALTH_EMERGENT;
                        }
                        //积压风险
                        //（2）平均库龄超过30天小于等于60天
                        //（3）可用库存数超过60天小于等于90天（可用库存=（在途+在库）/（日销*扩销系数））
                            elseif (
                            $new_row['avg_inventory_age'] > WARNNING_UNSALABLE_INVENTORY_DAYS && $new_row['avg_inventory_age'] <= SERIOUT_UNSALABLE_INVENTORY_DAYS &&
                            $supply_days > WARNNING_SUPPLY_INVENTORY_DAYS && $supply_days <= SERIOUT_SUPPLY_INVENTORY_DAYS
                            )
                        {
                            $new_row['inventory_health'] = INVENTORY_HEALTH_WARNNING;
                        }

                        //采购过量
                            elseif ($new_row['require_qty'] > PURCHASE_ONCE_SUPPLY_DAYS * $weight_sale_pcs * $expand_factor) {
                            $new_row['inventory_health'] = INVENTORY_HEALTH_ENOUGH;
                        }
                            else {
                        //健康
                        $new_row['inventory_health'] = INVENTORY_HEALTH_WELL;
                            }
                        }
                    }

                    $stock = $new_row['available_qty'] + $new_row['oversea_ship_qty'];

                    if ($stock == 0 && ($new_row['weight_sale_pcs'] == 0)) {
                        return INVENTORY_HEALTH_WELL;
                    } elseif ($stock > 0 && ($new_row['weight_sale_pcs'] == 0)) {
                        return INVENTORY_HEALTH_EMERGENT;
                    }

                    //严重积压
                    //（1）平均库龄超过60天
                    //（1）可用库存数超过90天（可用库存=（在途+在库）/（日销*扩销系数））
                    $supply_days = ($new_row['available_qty'] + $new_row['oversea_ship_qty'] ) / ($new_row['weight_sale_pcs'] * $new_row['expand_factor']);
                    if ($new_row['avg_inventory_age'] > SERIOUT_UNSALABLE_INVENTORY_DAYS || $supply_days > SERIOUT_SUPPLY_INVENTORY_DAYS) {
                        return INVENTORY_HEALTH_EMERGENT;
                    }
                    //积压风险
                    //（2）平均库龄超过30天小于等于60天
                    //（3）可用库存数超过60天小于等于90天（可用库存=（在途+在库）/（日销*扩销系数））
                    if (
                        $new_row['avg_inventory_age'] > WARNNING_UNSALABLE_INVENTORY_DAYS && $new_row['avg_inventory_age'] <= SERIOUT_UNSALABLE_INVENTORY_DAYS &&
                        $supply_days > WARNNING_SUPPLY_INVENTORY_DAYS && $supply_days <= SERIOUT_SUPPLY_INVENTORY_DAYS
                        )
                    {
                        return INVENTORY_HEALTH_WARNNING;
                    }

                    //采购过量
                    if ($new_row['require_qty'] > PURCHASE_ONCE_SUPPLY_DAYS * $new_row['weight_sale_pcs'] * $new_row['expand_factor']) {
                        return INVENTORY_HEALTH_ENOUGH;
                    }

                    //健康
                    return INVENTORY_HEALTH_WELL;
                }
                )
            ->register_columns_recalc_callback(
                //1 无 2 “违禁”sku不能审核 3 停产/断货sku不能审核 4 Sku所属类目不能审核 5 严重积压/有积压风险的sku不能审核 6 停售sku不能审核
                'deny_approve_reason',
                function($new_row) use ($restrict_categories) {
                    if ($new_row['is_contraband'] == CONTRABAND_STATE_YES) {
                        return DENY_APPROVE_CONTRABAND;
                    }
                    if ($new_row['provider_status'] == PROVIDER_STATUS_STOP || $new_row['provider_status'] == PROVIDER_STATUS_BROKEN) {
                        return DENY_APPROVE_SUPPLIER_SUSPENDED_LACK;
                    }
                    if (isset($restrict_categories[$new_row['product_category_id']])) {
                        return DENY_APPROVE_RESTRICT_CATEGORY;
                    }
                    if ($new_row['inventory_health'] == INVENTORY_HEALTH_WARNNING || $new_row['inventory_health'] == INVENTORY_HEALTH_EMERGENT) {
                        return DENY_APPROVE_INVENTORY_HEALTH_EMERGENT_WARNNING;
                    }
                    if ($new_row['product_status'] == 7) {
                        return DENY_APPROVE_HALT_SKU;
                    }
                    return DENY_APPROVE_NONE;
                }
           );

        $this->_ci->FbaRebuildPr->run();

        return  $this->_ci->FbaRebuildPr->report;

    }

    public function batch_edit_only_fixed_amount($post)
    {
        $report = [
                'total'      => count($post),
                'processed'  => 0,
                'undisposed' => count($post),
                'errorMess'  => ''
        ];
        $active_user = get_active_user();
        $salesman = $active_user->has_all_data_privileges(BUSSINESS_FBA) ? '*' : $active_user->staff_code;
        $manager_accounts = $active_user->get_my_manager_accounts();
        $valid_prs = $this->_ci->fba_pr_list->get_can_fixed_amount(array_keys($post), $salesman, $manager_accounts);

        if (empty($valid_prs)) {
            $report['errorMess'] = '没有有效的记录';
            return $report;
        }

        $updated_at =  time();
        $updated_uid = $active_user->staff_code;
        $updated_zh_name  = $active_user->user_name;

        $batch_update_pr = $batch_insert_log = [];

        foreach ($valid_prs as $row)
        {
            $selected = $post[$row['pr_sn']];

            $amount = $selected['fixed_amount'];
            if ($row['fixed_amount'] == $amount) {
                continue;
            }

            $batch_update_pr[] = [
                    'gid' => $row['gid'],
                    'fixed_amount' => $amount,
                    'is_plan_approve' => $amount > 0 ? NEED_PLAN_APPROVAL_YES : NEED_PLAN_APPROVAL_NO,
                    'updated_uid' => $active_user->staff_code,
                    'updated_at' => $updated_at,
            ];
            $batch_insert_log[] = [
                    'gid' => $row['gid'],
                    'uid' => $updated_uid,
                    'user_name' => $updated_zh_name,
                    'context' => '修改一次修正量由'.$row['fixed_amount'].'为'.$amount,
            ];
        }

        if (empty($batch_update_pr)) {
            $report['errorMess'] = '没有任何修改';
            return $report;
        }

        $this->_ci->load->model('Fba_pr_list_log_model', 'fba_pr_list_log', false, 'fba');

        $stock_db = $this->_ci->fba_pr_list_log->getDatabase();
        $common_db = $this->_ci->fba_pr_list->getDatabase();

        try
        {
            $common_db->trans_start();
            $stock_db->trans_start();

            //批量更新主记录
            $update_rows = $common_db->update_batch($this->_ci->fba_pr_list->getTable(), $batch_update_pr, 'gid');
            if (!$update_rows)
            {
                $report['errorMess'] = '批量修改一次修正量更新失败';
                throw new \RuntimeException($report['errorMess']);
            }

            //插入日志
            $insert_rows = $this->_ci->fba_pr_list_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                $report['errorMess'] = '批量修改一次修正量插入失败';
                throw new \RuntimeException($report['errorMess']);
            }

            $common_db->trans_complete();
            $stock_db->trans_complete();

            if ($common_db->trans_status() === false || $stock_db->trans_status() === false)
            {
                $report['errorMess'] = '批量修改一次修正量，事务提交成功，但状态检测为false';
                throw new \RuntimeException($report['errorMess']);
            }

            $report['processed'] = $update_rows;
            $report['undisposed'] = $report['total'] - $report['processed'];

            //释放资源
            $batch_update_pr = $batch_insert_log = null;
            unset($batch_update_pr, $batch_insert_log);

            return $report;
        }
        catch (\Throwable $e)
        {
            $common_db->trans_rollback();
            $stock_db->trans_rollback();

            log_message('ERROR', sprintf('批量修改一次修正量更新%s，提交事务出现异常: %s', json_encode($batch_update_pr), $e->getMessage()));

            $batch_update_pr = $batch_insert_log = $records = null;
            unset($batch_update_pr, $batch_insert_log, $records);

            $report['errorMess'] = '批量修改一次修正量抛出异常：'.$e->getMessage();
            return $report;
        }
    }


    /**
     * v1.1.2 变更：
     * 所有需求单必须一级审核
     *
     * @param unknown $gids
     * @param unknown $approve_result
     * @param unknown $manager_accounts
     * @throws \InvalidArgumentException
     * @return unknown
     */
    public function batch_approve_first($gids, $approve_result, $manager_accounts)
    {
        //gids验证
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }

        $todo = $this->_ci->fba_pr_list->get_can_approve_for_first($gids, $manager_accounts);
        $this->_ci->load->classes('fba/classes/FbaApproveFirst');

        $this->_ci->FbaApproveFirst
            ->set_approve_level('一')
            ->set_approve_result($approve_result)
            ->set_model($this->_ci->fba_pr_list)
            ->set_selected_gids($gids)
            ->recive($todo);

        $this->_ci->FbaApproveFirst->run();
        $this->_ci->FbaApproveFirst->send_system_log(self::$s_system_log_name);
        return $this->_ci->FbaApproveFirst->report();
    }

    private function fetch_approve_start_gid($lock_key, $start_gid_key)
    {
        log_message('INFO', sprintf('进程：%d 开始获取可读记录锁', getmygid()));

        $counter = 60;
        while ($counter > 0 && !($set_lock = $this->_ci->rediss->command(sprintf('setnx %s 1', $lock_key))))
        //while ($counter > 0 && !($set_lock = $this->_ci->rediss->command(sprintf('set %s 1 EX 300', $lock_key))))
        {
            sleep(1);
            $counter --;
            log_message('INFO', sprintf('进程：%d 获取锁，获取锁失败， 计数器：%d 结果：%s', getmygid(), $counter, strval($set_lock)));
        }
        if ($set_lock) {
            $this->_ci->rediss->command('expire '.$lock_key.' 300');
            $gid = $this->_ci->rediss->command('get '.$start_gid_key);
            $gid = (string)$gid;

            if (!$gid) {
                //没有设置，获取
                list($gid, $max_gid) = $this->_ci->fba_pr_list->get_top_bottom_gid();
                $gid = substr($gid, 0, strlen($gid)-1).'0';
            } elseif ($gid == 'finish') {
                //有人到达终点 或执行捞取工作，自己退出，让先到达的进程将余下事情做完
                log_message('INFO', sprintf('进程：%d 获取锁，但已有进程先到达，开始准备退出', getmygid()));
                return false;
            }
            log_message('INFO', sprintf('进程：%d 获取锁成功，当前最大gid：%s，开始准备获取列表', getmygid(), $gid));
            return $gid;
        }

        log_message('INFO', sprintf('进程：%d 60秒抢锁失败，将退出', getmygid()));
        return false;
    }

    /**
     * 获取一键审核执行信息
     *
     * @param int $level
     * @param string $query
     * @throws \Exception
     * @return unknown
     */
    public function get_approve_process_summary($level, $result, $query)
    {
        $approve_query_pool = get_active_user()->staff_code.'.'.$level.'.'.$result;
        property_exists($this->_ci, 'rediss') OR $this->_ci->load->library('Rediss');

        //state: 1 运行中 2 新增进程只查询 3 结束
        $summary = [
            'state' => 1,
            'processed' => 0,
            'nums' => 0
        ];

        //检测是否有已经执行全部执行完成的标记。
        $finish_key = 'approve_finish_'.$approve_query_pool.'.'.date('Ymd');

        $finish_info = $this->_ci->rediss->command('get '.$finish_key);
        if (null !== $finish_info) {
            $info = explode('.', $finish_info);
            $summary = [
                'state' => 4,
                'processed' => intval($info[0]),
                'nums' => intval($info[1] ?? 0),
            ];
            return $summary;
        }

        //运行中
        $value = $this->_ci->rediss->command('hget approve_query_pool '.$approve_query_pool);
        if (!$value) {

            //检验key必须存在，如果不存在，做已经执行处理
            $summary = [
                'state' => 3,
                'processed' => 0,
                'nums' => 0
            ];
            return $summary;
        } elseif ($value != $query) {
            //新起了一个进程，正在运行中，只显示进度
            $summary = [
                'state' => 2
            ];
        }

        $key_map = [
            1 => 'approve_first_all_summary_fba_',
            2 => 'approve_second_all_summary_fba_',
            3 => 'approve_three_all_summary_fba_',
        ];

        property_exists($this->_ci, 'rediss') OR $this->_ci->load->library('Rediss');
        $staff_code = get_active_user()->staff_code;
        $summary_key = $key_map[$level].$staff_code;

        $hash_node = $this->_ci->rediss->command('hgetall '.$summary_key);
        log_message('INFO', sprintf('查询统计sumary_key:%s, hash_node取值：%s', $summary_key, json_encode($hash_node)));
        if ($hash_node) {
            for ($i=0, $j=count($hash_node); $i <= $j - 2; $i += 2)
            {
                $summary[$hash_node[$i]] = $hash_node[$i+1];
            }
        }
        log_message('INFO', sprintf('查询统计summary'.json_encode($summary)));

        return $summary;
    }

    public function set_approve_process_summary($level, $summary_key, $counter, $result)
    {
        property_exists($this->_ci, 'rediss') OR $this->_ci->load->library('Rediss');
        $command = "eval \"redis.call('hincrby', KEYS[1], 'nums', 1); if(tonumber(KEYS[2]) == 1) then redis.call('hincrby', KEYS[1], 'processed', KEYS[3]); return 'SUCC'; else redis.call('hincrby', KEYS[1], 'undisposed', KEYS[3]); end; \" 3 %s %d %d";
        $result = $this->_ci->rediss->eval_command(sprintf($command, $summary_key, $result, $counter));
        log_message('INFO', sprintf('审核统计key： %s 执行结果: %s, eval执行状态：%s', $summary_key, $result ? '成功' : '失败', $result));
        return $result;
    }

    private function register_running_cache($lock_key, $gid, $gid_key)
    {
        $command = "eval \"redis.call('del', KEYS[3]); local gid = redis.call('get', KEYS[1]); if (gid) then if (type(gid) == 'string' and gid < KEYS[2]) then redis.call('set', KEYS[1], KEYS[2], 'EX', 60); return 'SUCC'; else return 'FAIL'; end; else redis.call('set', KEYS[1], KEYS[2], 'EX', 60); return 'SUCC'; end;\" 3 %s %s %s";
        $result = $this->_ci->rediss->eval_command(sprintf($command, $gid_key, $gid, $lock_key));
        log_message('INFO', sprintf('删除锁： %s 写入gid key: %s, gid：%s, 状态：%s', $lock_key, $gid_key, $gid, $result));
    }

    private function clear_approve_cache($finish_params)
    {
        try {
            $params = [
                'key' => $finish_params['summary_key'],
                'processed' => $finish_params['info']['processed'] ?? 0,
                'nums' => $finish_params['info']['nums'] ?? 0,
                'business_line' => $finish_params['business_line'],
                'result' => $finish_params['result'],
            ];
            property_exists($this->_ci, 'm_auto_approve_log') OR $this->_ci->load->model('Fba_auto_approve_log_model', 'm_auto_approve_log', false, 'fba');
            $db = $this->_ci->m_auto_approve_log->getDatabase();
            $result = $db->replace($this->_ci->m_auto_approve_log->getTable(), $params);
            if ($result)
            {
                $command = "eval \"redis.call('del', KEYS[1]); redis.call('del', KEYS[2]);redis.call('del', KEYS[4]); redis.call('hdel', 'approve_query_pool', KEYS[3]); redis.call('set', KEYS[5], KEYS[7], 'EX', KEYS[6]); return 'SUCC';\" 7 %s %s %s %s %s %d %d";
                $command = sprintf($command, $finish_params['lock_key'], $finish_params['summary_key'], $finish_params['query_key'], $finish_params['start_gid_key'], $finish_params['finish_key'], $finish_params['finish_key_expired_time'], ($finish_params['info']['processed'] ?? 0).'.'.($finish_params['info']['nums'] ?? 0));
                $result = $result && $this->_ci->rediss->eval_command($command);
                log_message('INFO', sprintf('执行结束，清理缓存, 状态：%s, 参数：%s', $result, json_encode($finish_params)));
            }
            return $result;
        } catch (\Throwable $e) {
            log_message('ERROR', '执行clear_approve_cache失败，异常：'.$e->getMessage());
            return false;
        }
    }

    public function batch_approve_all($level, $approve_result, $privileges, $query_value)
    {
        set_time_limit(-1);
        ini_set('memory_limit', '1024M');
        ini_set('max_execution_time', '0');

        $key_map = [
            1 => [
                'lock_key' => 'approve_first_all_lock_fba_',
                'start_gid_key' => 'approve_first_all_start_gid_fba_',
                'summary_key' => 'approve_first_all_summary_fba_',
                'level' => '一',
                'func' => 'get_can_approve_for_first_all',
            ],
            2 => [
                'lock_key' => 'approve_second_all_lock_fba_',
                'start_gid_key' => 'approve_second_all_start_gid_fba_',
                'summary_key' => 'approve_second_all_summary_fba_',
                'level' => '二',
                'func' => 'get_can_approve_for_second_all',
            ],
            3 => [
                'lock_key' => 'approve_three_all_lock_fba_',
                'start_gid_key' => 'approve_three_all_start_gid_fba_',
                'summary_key' => 'approve_three_all_summary_fba_',
                'level' => '三',
                'func' => 'get_can_approve_for_three_all',
            ],
        ];

        $staff_code = get_active_user()->staff_code;

        $nums = 0;
        $chunk_size = 300;

        //锁key
        $lock_key = $key_map[$level]['lock_key'].$staff_code;
        //起始gid
        $start_gid = '';
        //起始gid key
        $start_gid_key = $key_map[$level]['start_gid_key'].$staff_code;
        //汇总信息
        $summary_key = $key_map[$level]['summary_key'].$staff_code;
        //进程pid
        $pid = getmypid();

        $this->_ci->load->classes('fba/classes/FbaApproveAll');
        $this->_ci->load->library('Rediss');

        $this->_ci->FbaApproveAll
        ->set_approve_level($key_map[$level]['level'])
        ->set_approve_result($approve_result)
        ->set_fba_model($this->_ci->fba_pr_list);

        $fetch_before_execute_time = microtime(true);
        $fetch_before_execute_memory = memory_get_usage(true);
        log_message('INFO', sprintf('pid:%d 重建需求列表时间， 起始内存：%s, 起始时间：%s', $pid, ($fetch_before_execute_memory/1024/1024).'M', $fetch_before_execute_time.'s'));

        //获取锁之后才能取值，多进程情况下会导致重复取值
        $func = $key_map[$level]['func'];

        while ( ($start_gid = $this->fetch_approve_start_gid($lock_key, $start_gid_key)) && ($todo = $this->_ci->fba_pr_list->{$func}($start_gid, $privileges, $chunk_size)))
        {
            $counter = count($todo);

            $fetch_after_execute_time = microtime(true);
            $fetch_after_execute_memory = memory_get_usage(true);

            log_message('INFO', sprintf('pid:%d，第%d次取值， 取列表消耗内存：%s, 总内存：%s, 取列表消耗时间:%s', $pid, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s'));

            $fetch_before_execute_time = $fetch_after_execute_time;
            $fetch_before_execute_memory = $fetch_after_execute_memory;

            //释放锁，写入gid
            $my_max_gid = $todo[$counter - 1]['gid'];
            $this->register_running_cache($lock_key, $my_max_gid, $start_gid_key);

            $result = $this->_ci->FbaApproveAll->recive($todo)->run();
            log_message('INFO', sprintf('pid:%d 第%d次取值， 审核执行结果：%s', $pid, $nums, $result ? '成功' : '失败'));

            //设置
            $this->set_approve_process_summary($level, $summary_key, $counter, $result ? 1 : -1);

            $fetch_after_execute_time = microtime(true);
            $fetch_after_execute_memory = memory_get_usage(true);

            log_message('INFO', sprintf('pid:%d 第%d次取值， 生成执行数据消耗内存：%s, 总内存：%s, 生成执行数据消耗时间:%s', $pid, $nums, (($fetch_after_execute_memory - $fetch_before_execute_memory)/1024/1024).'M', ($fetch_after_execute_memory/1024/1024).'M', ($fetch_after_execute_time - $fetch_before_execute_time).'s'));

            $todo = [];

            $nums ++;
        }

        if (false === $start_gid) {
            //抢锁失败，进程退出，
            log_message('ERROR', sprintf('pid:%d 第%d次取值， 抢锁失败，进程退出', $pid, $nums));
            return false;
        } else {
            //处理完成, 正常， 异常reload
            $info = $this->get_approve_process_summary($level, $approve_result, $query_value);

            if (isset($info['state']) && $info['state'] == 1 && $info['processed'] == 0) {
                //异常，重新reload
                $this->_ci->load->model('Fba_auto_approve_log_model', 'm_auto_approve_log', false, 'fba');
                $logs = $this->_ci->m_auto_approve_log->get_today_logs(BUSINESS_LINE_FBA);
                if (!empty($logs)) {
                    foreach ($logs as $log) {
                        if ($log['key'] == $summary_key && $log['result'] == $approve_result && $log['business_line'] == BUSINESS_LINE_FBA) {
                            $this->_ci->rediss->command('hmset '.$log['summary_key'].' processed '.$log['processed'].' nums '.$log['nums']);
                            $info = ['processed' => $log['processed'], 'nums' => $log['nums']];
                        }
                    }
                }
            }

            $query_key = get_active_user()->staff_code.'.'.$level.'.'.$approve_result;
            //注册一个完成key
            $finish_params = [
                'lock_key' => $lock_key,
                'summary_key' => $summary_key,
                'query_key' => $query_key,
                'start_gid_key' => $start_gid_key,
                'finish_key' => 'approve_finish_'.$query_key.'.'.date('Ymd'),
                'finish_key_expired_time' => strtotime(date('Y-m-d').' 23:59:59') - time(),
                'info' => $info,
                'business_line' => BUSSINESS_FBA,
                'result' => $approve_result
            ];
            $this->clear_approve_cache($finish_params);
            log_message('INFO', sprintf('pid:%d 第%d次取值， 全部审核完成，返回：%s', $pid, $nums, json_encode($info)));
            return $info;
        }
    }


    /**
     *
     * @param string $salesman_uid 销售个人
     * @param string|array $privileges 管理的账号
     * @return number
     */
    public function get_fba_total_money($date, $salesman_uid, $manager_accounts)
    {
        $float_money = $this->_ci->fba_pr_list->get_fba_total_money($date, $salesman_uid, $manager_accounts);
        return sprintf('%0.2f', round($float_money/10000, 2));
    }

    /**
     * 执行审核状态变更
     *
     * @param unknown $next_state
     * @param unknown $record
     * @throws \RuntimeException
     * @return boolean
     */
    public function one_approve($params)
    {
        list ($next_state, $record, $level) = $params;
        $active_user = get_active_user();
        try
        {
            $params = [
                    'approve_state' => $next_state,
                    'approved_at' => time(),
                    'approved_uid' => $active_user->staff_code,
            ];
            $this->_ci->load->classes('basic/classes/Record');
            $this->_ci->Record->recive($record);
            $this->_ci->Record->setModel($this->_ci->fba_pr_list);
            foreach ($params as $key => $val)
            {
                $this->_ci->Record->set($key, $val);
            }

            $modify_count = $this->_ci->Record->report();
            if ($modify_count == 0)
            {
                throw new \RuntimeException(sprintf('没有产生任何修改，本次操作未执行任何操作'), 500);
            }
            $this->_ci->load->service('fba/PrLogService');

            //事务开始
            $db = $this->_ci->fba_pr_list->getDatabase();

            $db->trans_start();

            //记录日志
            $log_context = sprintf('%s级审核需求单：%s 审核%s', $level, $record['pr_sn'], $next_state == APPROVAL_STATE_FAIL ? '失败' : '通过');
            $this->_ci->prlogservice->send(['gid' => $record['gid']], $log_context);

            //兼容更新
            $update_count = $this->_ci->fba_pr_list->update_compatible($this->_ci->Record);
            if ($update_count !== 1)
            {
                throw new \RuntimeException(sprintf('未修改FBA PR需求BD数量，该请求可能已经执行'), 500);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，事务提交成功，但状态检测为false', $level), 500);
            }

            return true;
        }
        catch (\Exception $e)
        {
            log_message('ERROR', sprintf('执行%s级审核操作，抛出异常： %s', $level, $e->getMessage()));
            return false;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('执行%s级审核操作，抛出异常： %s', $level, $e->getMessage()));
            return false;
        }
    }

    public function batch_approve_second($gids, $approve_result, $salesman_uid)
    {
        //gids验证
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }

        $todo = $this->_ci->fba_pr_list->get_can_approve_for_second($gids, $salesman_uid);
        $this->_ci->load->classes('fba/classes/FbaApproveFirst');

        $this->_ci->FbaApproveFirst
        ->set_approve_level('二')
        ->set_approve_result($approve_result)
        ->set_model($this->_ci->fba_pr_list)
        ->set_selected_gids($gids)
        ->recive($todo);

        $this->_ci->FbaApproveFirst->run();
        $this->_ci->FbaApproveFirst->send_system_log(self::$s_system_log_name);
        return $this->_ci->FbaApproveFirst->report();
    }

    public function batch_approve_three($gids, $approve_result, $salesman_uid)
    {
        //gids验证
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }

        $todo = $this->_ci->fba_pr_list->get_can_approve_for_three($gids, $salesman_uid);
        $this->_ci->load->classes('fba/classes/FbaApproveFirst');

        $this->_ci->FbaApproveFirst
        ->set_approve_level('三')
        ->set_approve_result($approve_result)
        ->set_model($this->_ci->fba_pr_list)
        ->set_selected_gids($gids)
        ->recive($todo);

        $this->_ci->FbaApproveFirst->run();
        $this->_ci->FbaApproveFirst->send_system_log(self::$s_system_log_name);
        return $this->_ci->FbaApproveFirst->report();
    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->fba_pr_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }

    public function get_pr_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Fba_pr_list_remark_model', 'fba_pr_list_remark', false, 'fba');
        return $this->_ci->fba_pr_list_remark->get($gid, $offset, $limit);
    }

    /**
     * 获取列表需要高亮显示
     * 目前高亮暂时做到了列表数据一起返回，如果后续耗时较长，就放到列表返回后再次请求。
     * @todo
     * @param unknown $params
     * @return array
     */
    public function get_list_highlight($params)
    {
        if (empty($params) || !isset($params['gid']))
        {
            return [];
        }

        $gid_highlight = [];

        // 传值hash传值
        $seller_sku_hashs = array_column($params['gid'], 'hash');
        $this->_ci->load->model('Fba_diff_seller_sku_model', 'm_diff_seller_sku', false, 'fba');
        $seller_sku_hashs = key_by($this->_ci->m_diff_seller_sku->get_exists_hash($seller_sku_hashs), 'hash');

        //获取促销高亮
        return $gid_highlight;
    }

    /**
     * 获取需要高亮显示seller_sku
     *
     * @return int
     */
    public function general_diff_seller_sku()
    {
        $end_time = strtotime(date('Y-m-d'));
        $start_time = $end_time - DIFF_SELLER_SKU_DAYS * 86400;

        $this->_ci->load->model('Fba_diff_seller_sku_model', 'm_diff_seller_sku', false, 'fba');
        $query = sprintf(
           'INSERT INTO %s
            SELECT md5(CONCAT(station_code,account_name, asin)) as hash FROM %s FORCE index(union_created_boutique_state_tax)
            WHERE created_at >= %d AND created_at <= %d
            GROUP BY station_code, account_name, asin
            HAVING count(DISTINCT seller_sku) > 1
            ORDER BY NULL',
            $this->_ci->m_diff_seller_sku->getTable(),
            $this->_ci->fba_pr_list->getTable(),
            $start_time,
            $end_time
        );
        $db = $this->_ci->m_diff_seller_sku->getDatabase();
        $delete_result = $db->query('delete from '.$this->_ci->m_diff_seller_sku->getTable());
        if ($delete_result && ($insert_result = $db->query($query)))
        {
            return $db->affected_rows();
        }
        log_message('ERROR', sprintf('生成最近%d天不同seller_sku天数失败, delete结果：%s, 插入结果：%s', DIFF_SELLER_SKU_DAYS, (string)$delete_result, (string)$insert_result));
        return 0;
    }

    /**
     * 第一次执行fba无主动sku但符合被动条件的记录为TRIGGER_LOST_ACTIVE_YES
     * @return int
     */
    public function sync_first_fba_inactive()
    {
        $start_time = strtotime(date('Y-m-d'));
        $end_time = $start_time + 86400;

        $query = sprintf(
            'UPDATE %s set is_lost_active_trigger = %d '.
            'where created_at >= %d and created_at <= %d '.
            'and expired = %d and bd = 0 and approve_state = %d '.
            'and available_qty+exchange_up_qty+oversea_ship_qty+stocked_qty-point_pcs-bd < weight_sale_pcs * CAST(ext_trigger_info AS UNSIGNED) '.
            'and is_lost_active_trigger = %d',
            $this->_ci->fba_pr_list->getTable(), TRIGGER_LOST_ACTIVE_YES,
            $start_time, $end_time,
            FBA_PR_EXPIRED_NO, APPROVAL_STATE_FIRST,
            TRIGGER_LOST_ACTIVE_NORMAL
            );
        $db = $this->_ci->fba_pr_list->getDatabase();
        if ($db->query($query))
        {
            return $db->affected_rows();
        }
        log_message('ERROR', sprintf('初始化%d的fba无主动记录符合被动条件的记录设置为TRIGGER_LOST_ACTIVE_YES失败，sql:%s', date('Y-m-d'), $query));
        return 0;
    }

    /**
     * 获取扩展的物流数据，支持多个, 前端返回列表
     *
     * @param array $gids
     * @param bool $assoc_key 返回key=>value数组
     * @return array
     */
    public function get_extend_logistics_info($gids, $assoc_key = false)
    {
        $gids_arr = is_string($gids) ? explode(',', $gids) : $gids;
        if (empty($gids_arr))
        {
            return [];
        }
        $extend_info = $this->_ci->fba_pr_list->get_extends_logistics_info($gids_arr);
        if (empty($extend_info))
        {
            return [];
        }
        //解析
        $logistics_list_data = [];
        foreach ($extend_info as $row)
        {
            //id => arr
            $logistics_arr = self::parse_ext_logistics_info($row['ext_logistics_info']);
            foreach ($logistics_arr as $logistics_id => $one_row)
            {
                $one_row['is_trigger_pr'] = TRIGGER_PR[intval($one_row['is_trigger_pr'])]['name'] ?? '-';
                $one_row['logistics_id_text'] = LOGISTICS_ATTR[intval($one_row['logistics_id'])]['name'] ?? '-';
                $assoc_key ?
                $logistics_list_data[$row['gid']][$logistics_id] = $one_row :
                $logistics_list_data[$row['gid']][] = $one_row;

            }
        }
        unset($extend_info);
        return $logistics_list_data;
    }

    /**
     * 判断操作的截止时间
     *
     * @return true
     * @throws \OutOfRangeException
     */
    public function check_enable_time($operate = '')
    {
        $register_operate = [
            /*'edit_pr_listing',
            'batch_edit_bd',*/
            'batch_approve_first',
            'batch_approve_first_all',
            'batch_approve_second',
            'batch_approve_three'
        ];
        if ($operate == '' || !in_array(strtolower($operate), $register_operate))
        {
            return true;
        }
        list($start, $end) = explode('~', PLAN_EDIT_PUSH_CLOSE_TIME);
        $now = date('H:i');
        $disabled = $now >= $start && $now <= $end;
        if ($disabled)
        {
            throw new \OutOfRangeException('备货列表生成中，请0点后再审核', 500);
        }
        return true;
    }


}
