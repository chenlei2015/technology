<?php

/**
 * oversea 需求服务
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
    public static $s_system_log_name = 'OVERSEA';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_pr_list_model', 'oversea_pr_list', false, 'oversea');
        $this->_ci->load->helper('oversea_helper');
        return $this;
    }

    /**
     * 添加一条备注, 成功为true，否则抛异常
     *
     * @version 1.2.0 增加权限
     *
     * @param array $params
     * @param string|array $owner_privileges 权限值 * 带有所有权限， 数组为有权限范围定义
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params, $owner_privileges = [])
    {
        $gid = $params['gid'];
        $remark = $params['remark'];

        $record = ($pk_row = $this->_ci->load->oversea_pr_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }

        if (!isset($owner_privileges['*'])  && !in_array($record['station_code'], $owner_privileges))
        {
            throw new \InvalidArgumentException(sprintf('您没有权限'), 412);
        }
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->oversea_pr_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        $this->_ci->Record->set('updated_uid', get_active_user()->staff_code);

        //不同db变成跨库
        $db = $this->_ci->oversea_pr_list->getDatabase();

        $db->trans_start();

        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('海外仓站点列表更新备注失败'), 500);
        }
        if (!$this->add_list_remark($params))
        {
            throw new \RuntimeException(sprintf('海外仓站点列表插入备注失败'), 500);
        }

        $db->trans_complete();

        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('海外仓站点添加备注事务提交完成，但检测状态为false'), 500);
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
        $this->_ci->load->model('Oversea_pr_list_remark_model', 'oversea_pr_list_remark', false, 'oversea');
        append_login_info($params);
        $insert_params = $this->_ci->oversea_pr_list_remark->fetch_table_cols($params);
        return $this->_ci->oversea_pr_list_remark->add($insert_params);
    }

    /**
     * 是否触发计划审核
     *
     * ~v1.1.2: $record->get('is_trigger_pr') == TRIGGER_PR_YES &&  $record->get('bd') > 0
     *  v1.1.2: 如下， 不在判断是否触发需求
     */
    protected function is_trigger_plan_approve(Record $record)
    {
        return $record->get('bd') > 0;
    }

    /**
     * v1.0.0
     * require_qty - stocked_qty > 0
     *
     * v1.0.1 新增：
     * 可用+待上架+国际在途+已备货（新增需求）>= 订购点   触发需求为“N”
     * 可用+待上架+国际在途+已备货（新增需求）< 订购点   触发需求为“Y”
     * @param Record $record
     * @return boolean
     */
    protected function is_trigger_pr(Record $record)
    {
        return $record->available_qty + $record->oversea_up_qty + $record->oversea_ship_qty + $record->stocked_qty - $record->point_pcs < 0;
    }

    /**
     * 重新计算需求数量
     *
     * @param Record $record
     * @return number
     */
    protected function recalc_required_qty1(Record $record)
    {
        return $record->point_pcs + $record->purchase_qty + $record->bd - $record->available_qty - $record->oversea_up_qty - $record->oversea_ship_qty;
    }

    /**
     * 重新计算需求数量
     *
     * v1.3
     * 1.停售或清仓sku:
     * 需求数量=欠货量
     * 2.非停售或清仓sku:
     * 需求数量= 订购点+一次备货数量-（PR数量+采购在途+在库）*扩销系数+活动量+一次修正量+欠货量
     *
     * @param Record $record
     * @return number
     */
    protected function recalc_required_qty(Record $record)
    {
        if ($record->is_accelerate_sale == ACCELERATE_SALE_NO) {
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
     * 修改pr列表
     * v1.1.2 bd变更
     *
     * 1、FBA、海外仓跑出来的需求都需要审核
     * 2、BD》0，那么一定会触发计划审核
     * 3、汇总sku的，如果需求数量<=0, 那么汇总会当做0来计算汇总
     * 4、备货计划回写的时候，如果需求数量《0，那么一定会回写0
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return boolean 成功返回true， 失败抛出异常
     */
    public function edit_pr_listing($params)
    {
        $gid = trim($params['gid']);
        unset($params['gid']);

        //gid检测
        $record = $this->_ci->oversea_pr_list->find_by_pk($gid);
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID%s', $gid), 412);
        }
		if ($record['expired'] == FBA_PR_EXPIRED_YES)
        {
            throw new \InvalidArgumentException(sprintf('该记录已经过期无法修改'), 412);
        }

        /*if ($params['bd'] + $record['purchase_qty'] < 0)
        {
            throw new \InvalidArgumentException(sprintf('调整数量不能超过订购数量'), 412);
        }*/

        //状态检测
        $this->_ci->load->classes('oversea/classes/PrState');
        //当前状态是否可以修改操作
        $op_privilegs = $this->_ci->PrState->from($record['approve_state'])->can_action('edit_pr_listing', ['approve_state' => $record['approve_state']]);
        if (!$op_privilegs)
        {
            throw new \RuntimeException(sprintf('当前状态不允许修改BD数量'), 500);
        }
        if (!is_login())
        {
            throw new \RuntimeException(sprintf('请使用一级审核权限账号登录'), 500);
        }
        //权限检测
        $active_user = get_active_user();
        if (!$active_user->is_first_approver(BUSSINESS_OVERSEA))
        {
            throw new \RuntimeException(sprintf('请使用一级审核权限账号进行操作'), 500);
        }

        //执行修改
        //控制可以被修改的字段
        $can_edit_cols = ['bd'];
        $params = $this->_ci->oversea_pr_list->fetch_table_cols($params);

        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->oversea_pr_list);
        foreach ($params as $key => $val)
        {
            if (in_array($key, $can_edit_cols))
            {
                $this->_ci->Record->set($key, $val);
            }
        }

        $modify_count = $this->_ci->Record->report();
        if ($modify_count == 0)
        {
            throw new \RuntimeException(sprintf('没有产生任何修改，本次操作未执行任何操作'), 500);
        }
        //更新时间
        $this->_ci->Record->set('updated_at', time());
        $active_user = get_active_user();
        $this->_ci->Record->set('updated_uid', $active_user->staff_code);

        //重新计算需求数量
        $this->_ci->Record->set('require_qty', $this->recalc_required_qty($this->_ci->Record));

        //是否触发需求
        if ($this->is_trigger_pr($this->_ci->Record))
        {
            $this->_ci->Record->set('is_trigger_pr', TRIGGER_PR_YES);
        }
        else
        {
            $this->_ci->Record->set('is_trigger_pr', TRIGGER_PR_NO);
        }

        //是否触发计划审核
        if ($this->is_trigger_plan_approve($this->_ci->Record))
        {
            $this->_ci->Record->set('is_plan_approve', NEED_PLAN_APPROVAL_YES);
        }
        else
        {
            $this->_ci->Record->set('is_plan_approve', NEED_PLAN_APPROVAL_NO);
        }

        //状态变化
        $this->_ci->Record->set('approve_state', APPROVAL_STATE_FIRST);

        $this->_ci->load->service('oversea/PrLogService');
        $modify_bd = $this->_ci->Record->get('bd');

        //事务开始
        $db = $this->_ci->oversea_pr_list->getDatabase();

        try
        {
            $db->trans_start();

            //记录日志
            $log_context = sprintf('将BD调整为 %s', $modify_bd > 0 ? '+'.$modify_bd : $modify_bd);
            $this->_ci->prlogservice->send(['gid' => $gid], $log_context);
            $update_count = $this->_ci->oversea_pr_list->update_bd($this->_ci->Record);
            if ($update_count !== 1)
            {
                throw new \RuntimeException(sprintf('未修改海外仓 PR需求BD数量，该请求可能已经执行'), 500);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('修改海外仓 PR需求BD数量，事务提交成功，但状态检测为false'), 500);
            }

            //发送系统日志
            $this->_ci->load->service('basic/SystemLogService');
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context);

            return true;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('修改海外仓 PR需求BD数量，提交事务出现异常, 原因：%s', $e->getMessage()));
            throw new \RuntimeException(sprintf('修改海外仓 PR需求BD数量，提交事务出现异常'), 500);
        }
    }

    /**
     * 批量修改bd
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
    public function batch_edit_pr($params)
    {
        $report = [
                'total' => 0,
                'processed' => 0,
                'undisposed' => 0,
        ];
        $index_to_cols = array_flip($params['map']);
        $selected = json_decode($params['selected'], true);
        unset($params['selected']);

        $report['total'] = $report['undisposed'] = count($selected);
        if (empty($selected))
        {
            return $report;
        }
        $pr_sns = array_keys($selected);
        //权限检测
        $active_user = get_active_user();
        if (!$active_user->is_first_approver(BUSSINESS_OVERSEA))
        {
            return $report;
        }
        $records = $this->_ci->oversea_pr_list->get_can_bd($pr_sns);
        if (empty($records))
        {
            return $report;
        }

        //过滤掉bd相同
        foreach ($records as $key => $val)
        {
            if ($val['bd'] == $selected[$val['pr_sn']][$params['map']['bd']])
            {
                unset($records[$key]);
            }
        }
        if (empty($records))
        {
            return $report;
        }

        //需要处理的
        $batch_update_pr = $batch_insert_log = [];
        $can_edit_cols = ['bd'];
        $update_time = time();
        $updated_uid = $active_user->staff_code;
        $updated_name = $active_user->user_name;

        $this->_ci->load->classes('basic/classes/Record');

        foreach ($records as $val)
        {
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
            $this->_ci->Record->set('require_qty', $this->recalc_required_qty($this->_ci->Record));
            $this->_ci->Record->set('is_trigger_pr', $this->is_trigger_pr($this->_ci->Record) ? TRIGGER_PR_YES : TRIGGER_PR_NO);
            $this->_ci->Record->set('is_plan_approve', $this->is_trigger_plan_approve($this->_ci->Record) ? NEED_PLAN_APPROVAL_YES : NEED_PLAN_APPROVAL_NO);
            $this->_ci->Record->set('approve_state', APPROVAL_STATE_FIRST);

            $old_bd = $val['bd'];
            $modify_bd = $this->_ci->Record->get('bd');

            $update_row = $this->_ci->Record->get();
            unset($update_row['pr_sn']);
            $batch_update_pr[] = $update_row;

            $log_context = sprintf('将BD由%s调整为 %s', ($old_bd > 0 ? '+' : '').$old_bd,  $modify_bd > 0 ? '+'.$modify_bd : $modify_bd);
            $batch_insert_log[] = [
                    'gid' => $val['gid'],
                    'uid' => $updated_uid,
                    'user_name' => $updated_name,
                    'context' => $log_context,
            ];
        }

        //事务开始
        $this->_ci->load->model('Oversea_pr_list_log_model', 'oversea_pr_list_log', false, 'oversea');
        $db = $this->_ci->oversea_pr_list->getDatabase();

        try
        {
            $db->trans_start();

            //批量更新
            $update_rows = $db->update_batch($this->_ci->oversea_pr_list->getTable(), $batch_update_pr, 'gid');
            if (!$update_rows)
            {
                throw new \RuntimeException(sprintf('批量修改海外 BD数量更新列表失败'), 500);
            }

            //插入日志
            $insert_rows = $this->_ci->oversea_pr_list_log->madd($batch_insert_log);
            if (!$insert_rows)
            {
                throw new \RuntimeException(sprintf('批量修改海外BD数量插入日志失败'), 500);
            }

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
//                $db->trans_rollback();
                //@todo 回滚insert_rows
                throw new \RuntimeException(sprintf('修改海外PR需求BD数量，事务提交成功，但状态检测为false'), 500);
            }

            //发送系统日志
            $this->_ci->load->service('basic/SystemLogService');
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, '批量修改BD'.count($records).'记录');

            $report['processed'] = count($batch_update_pr);;
            $report['undisposed'] = $report['total'] - $report['processed'];

            return $report;
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', sprintf('修改海外PR需求BD数量，提交事务出现异常: %s', $e->getMessage()));
            throw new \RuntimeException(sprintf('修改海外 PR需求BD数量，提交事务出现异常'), 500);
        }
    }

    /**
     * 批量一级审核， 海外仓一级审核对应FBA 2级审核
     *
     * @param unknown $gids
     */
    public function batch_approve_first_backup($gids, $approve_result)
    {
        //gids验证
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }
        //gid数据批量获取,带选择条件，不符合条件的过滤
        $todo = $this->_ci->oversea_pr_list->get_can_approve_for_first($gids);

        $report = [
                'total' => count($gids),
                'ignore' => count($gids) - count($todo),
                'succ' => 0,
                'fail' => 0,
                'msg' => ''
        ];

        $msg        = '选择了%d条记录，处理成功%d条, 处理失败%d条，无需处理%d条';
        $next_state = $approve_result == APPROVAL_RESULT_PASS ? APPROVAL_STATE_SECOND : APPROVAL_STATE_FAIL;

        $this->_ci->load->classes('oversea/classes/PrState');

        //开始逐条处理
        foreach ($todo as $key => $row)
        {
            $can_jump = $this->_ci->PrState->from($row['approve_state'])->go($next_state)->can_jump();
            if (!$can_jump)
            {
                $report['fail'] ++;
                continue;
            }
            $this->_ci->PrState->register_handler(array($this, 'one_approve'), [$next_state, $row, '二']);
            $jump_result = $this->_ci->PrState->jump();
            $jump_result ? $report['succ'] ++ : $report['fail'] ++;
        }

        $report['msg'] = sprintf($msg, $report['total'], $report['succ'], $report['fail'], $report['ignore']);

        //发送系统日志
        $this->_ci->load->service('basic/SystemLogService');
        $log_context = '海外仓批量一级审核选择：'.implode(',', $gids);
        $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context . $report['msg']);

        return $report;
    }

    public function batch_approve_first($gids, $approve_result)
    {
        //gids验证
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }

        $todo = $this->_ci->oversea_pr_list->get_can_approve_for_first($gids);
        $this->_ci->load->classes('oversea/classes/OverseaApprove');

        $this->_ci->OverseaApprove
        ->set_approve_level('一')
        ->set_approve_result($approve_result)
        ->set_model($this->_ci->oversea_pr_list)
        ->set_selected_gids($gids)
        ->recive($todo);

        $this->_ci->OverseaApprove->run();
        $this->_ci->OverseaApprove->send_system_log(self::$s_system_log_name);
        return $this->_ci->OverseaApprove->report();

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
        try
        {
            //检测审核权限，有数据权限不一定有审核权限
            $active_user = get_active_user();
            switch ($level)
            {
                case '一':
                    if (!$active_user->is_first_approver(BUSSINESS_OVERSEA))
                    {
                        throw new \RuntimeException(sprintf('您必须设置一级审核权限'), 500);
                    }
                    break;
                case '二':
                    if (!$active_user->is_second_approver(BUSSINESS_OVERSEA))
                    {
                        throw new \RuntimeException(sprintf('您必须设置一级审核权限'), 500);
                    }
                    break;
                default:
                    throw new \RuntimeException(sprintf('您没有此操作权限'), 500);
                    break;
            }

            $params = [
                    'approve_state' => $next_state,
            ];
            $this->_ci->load->classes('basic/classes/Record');
            $this->_ci->Record->recive($record);
            $this->_ci->Record->setModel($this->_ci->oversea_pr_list);
            foreach ($params as $key => $val)
            {
                $this->_ci->Record->set($key, $val);
            }

            $modify_count = $this->_ci->Record->report();
            if ($modify_count == 0)
            {
                throw new \RuntimeException(sprintf('没有产生任何修改，本次操作未执行任何操作'), 500);
            }
            $this->_ci->load->service('oversea/PrLogService');

            //事务开始
            $db = $this->_ci->oversea_pr_list->getDatabase();

            $db->trans_start();
            $update_count = $this->_ci->Record->update();
            if ($update_count !== 1)
            {
                throw new \RuntimeException(sprintf('未修改状态，该请求可能已经执行'), 500);
            }
            //记录日志
            $log_context = sprintf('%s级审核%s', $level, $next_state == APPROVAL_STATE_FAIL ? '失败' : '成功');
            $this->_ci->prlogservice->send(['gid' => $record['gid']], $log_context);

            $db->trans_complete();

            if ($db->trans_status() === false)
            {
                throw new \RuntimeException(sprintf('执行%s级审核操作，事务提交成功，但状态检测为false', $level), 500);
            }

            return true;
        }
        catch (Exception $e)
        {
            log_message('error', $e->getMessage());
            return false;
        }
        catch (\Throwable $e)
        {
            log_message('error', $e->getMessage());
            return false;
        }
    }

    /**
     * 批量二级审核
     *
     * @param unknown $gids
     */
    public function batch_approve_second_backup($gids, $approve_result)
    {
        //gids验证
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }

        //gid数据批量获取,带选择条件，不符合条件的过滤
        $todo = $this->_ci->oversea_pr_list->get_can_approve_for_second($gids);

        $report = [
                'total' => count($gids),
                'ignore' => count($gids) - count($todo),
                'succ' => 0,
                'fail' => 0,
                'msg' => '',
                'addup' => -1,
        ];

        $msg        = '选择了%d条记录，处理成功%d条, 处理失败%d条，无需处理%d条';
        $next_state = $approve_result == APPROVAL_RESULT_PASS ? APPROVAL_STATE_SUCCESS : APPROVAL_STATE_FAIL;

        $this->_ci->load->classes('oversea/classes/PrState');

        //开始逐条处理
        foreach ($todo as $key => $row)
        {
            $can_jump = $this->_ci->PrState->from($row['approve_state'])->go($next_state)->can_jump();
            if (!$can_jump)
            {
                $report['fail'] ++;
                continue;
            }
            $this->_ci->PrState->register_handler(array($this, 'one_approve'), [$next_state, $row, '二']);
            $jump_result = $this->_ci->PrState->jump();
            $jump_result ? $report['succ'] ++ : $report['fail'] ++;
        }

        $report['msg'] = sprintf($msg, $report['total'], $report['succ'], $report['fail'], $report['ignore']);

        //发送系统日志
        $this->_ci->load->service('basic/SystemLogService');
        $log_context = '批量二级审核选择：'.implode(',', $gids);
        $this->_ci->systemlogservice->send([], self::$s_system_log_name, $log_context . $report['msg']);

        //调用汇总接口
        if ($approve_result == APPROVAL_RESULT_PASS && $report['succ'] > 0)
        {
            $log_context = '海外仓批量二级审核，有无需计划审核记录，执行自动汇总操作，返回结果：%d';
            try
            {
                $this->_ci->load->service('oversea/PrSummaryService');
                $report['addup'] = $this->_ci->prsummaryservice->summary();
            }
            catch (\Exception $e)
            {
                log_message('ERROR', sprintf('调用统计出现异常，异常信息：%s', $e->getMessage()));
                $report['addup'] = -2;
            }
            catch (\Throwable $e)
            {
                log_message('ERROR', sprintf('调用统计出现异常，异常信息：%s', $e->getMessage()));
                $report['addup'] = -2;
            }
            $this->_ci->systemlogservice->send([], self::$s_system_log_name, sprintf($log_context, intval($report['addup'])));
        }
        return $report;
    }

    public function batch_approve_second($gids, $approve_result)
    {
        //gids验证
        if (empty($gids))
        {
            throw new \InvalidArgumentException('请选择需要审核的数据', 412);
        }

        $todo = $this->_ci->oversea_pr_list->get_can_approve_for_second($gids);
        $this->_ci->load->classes('oversea/classes/OverseaApprove');

        $this->_ci->OverseaApprove
        ->set_approve_level('二')
        ->set_approve_result($approve_result)
        ->set_model($this->_ci->oversea_pr_list)
        ->set_selected_gids($gids)
        ->recive($todo);

        $this->_ci->OverseaApprove->run();
        $this->_ci->OverseaApprove->send_system_log(self::$s_system_log_name);
        return $this->_ci->OverseaApprove->report();

    }

    /**
     * 需求单详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->oversea_pr_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        return $record;
    }

    public function get_pr_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Oversea_pr_list_remark_model', 'oversea_pr_list_remark', false, 'oversea');
        return $this->_ci->oversea_pr_list_remark->get($gid, $offset, $limit);
    }

    public function rebuild_pr()
    {
        $this->_ci->load->classes('oversea/classes/OverseaRebuildPr');
        $this->_ci->load->model('Oversea_pr_list_log_model', 'oversea_pr_list_log', false, 'oversea');
        $this->_ci->load->model('Oversea_activity_list_model', 'm_oversea_activity', false, 'oversea');
        $this->_ci->load->model('Oversea_pr_list_model', 'm_oversea_pr_list', false, 'oversea');
        $this->_ci->load->model('Oversea_new_list_model', 'm_oversea_new_list_model', false, 'oversea');

        //获取有效的活动 -yibai_oversea_activity_cfg
        $future_valid_activities = $this->_ci->m_oversea_activity->get_future_valid_activities();
        //获取不参与运算的sku
        //$this->_ci->load->model('Oversea_sales_operation_cfg_model', 'm_disabled_sku', false, 'oversea');
//        /** @var Oversea_sales_operation_cfg_model $disabled_skus */
//        $disabled_skus = $this->_ci->m_disabled_sku->get_disabled_skus();

        $config_path = APPPATH . 'upload/oversea_cfg.php';
        var_dump(file_exists($config_path));

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
                    //exhausted_days 已缺货天数不参与计算，默认值0，  因改值取消 断货天数系数 默认给1， 即不参与计算
                    return 1;

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
                    //is_warehouse_days_90 是否超90天库龄不参与计算，默认值给2为不超过90天库龄，因该值取消， 库龄配置系数 默认给1 即不参与计算
                    return 1;

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
                        if ($cfg[1] == 'max') {
                            if ($amount >= $cfg[0]) {
                                return $cfg[4];
                            }
                        } else {
                            if (is_string($cfg[0]) && $amount <= $cfg[1]) {
                                return $cfg[4];
                            }
                            if ($amount >= $cfg[0] && $cfg < $cfg[1]) {
                                return $cfg[4];
                            }
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

        //设置前缀  array(2) {
        //["python"]=>
        //string(5) "__py_"
        //["php"]=>
        //string(5) "__pl_"
        //}
        $prefix = $this->_ci->OverseaRebuildPr->get_prefix();
        $diff_varx_py_prefix = $prefix['python'];
        $diff_varx_php_prefix = $prefix['php'];

        $get_php_trans_colname = function($origin_col) use ($diff_varx_php_prefix) {
            return $diff_varx_php_prefix.$origin_col;
        };
//        var_dump($get_php_trans_colname);
//        die;
        $get_py_trans_colname = function($origin_col) use ($diff_varx_py_prefix) {
            return $diff_varx_py_prefix.$origin_col;
        };

        //现在只有Python一个
        $this->_ci->OverseaRebuildPr->enable_python_cfg();

        //$use_calc_system = 'python'
        $use_calc_system = $this->_ci->OverseaRebuildPr->get_main_system();
        //解析格式，获得数据
        $this->_ci->OverseaRebuildPr
            //前置回调
            ->register_callback('before', function() {})
            //后置回调
            ->register_callback('after', function() {})
            //设置语言文件
            ->set_langage('oversea_pr_list')
            //设置更新表model
            ->set_model($this->_ci->m_oversea_pr_list)
            //日志model
            ->set_log_model($this->_ci->oversea_pr_list_log);

        $this->_ci->OverseaRebuildPr
        ->register_columns_recalc_callback(
            //将毛需求配置中的系数解析
            'min_order_qty',
            function(&$new_row) use ($get_sales_amount_factor,$get_exhaust_factor,$get_warehouse_age_factor,$get_supply_factor,$get_z_scday_expand_factor) {
                //$amount = [SALE_AMOUNT_DAYS_STEP1 => $new_row['sale_amount_15_days'], SALE_AMOUNT_DAYS_STEP2 => $new_row['sale_amount_30_days']];
                //$new_row['sales_factor'] = $get_sales_amount_factor($amount);
                //$new_row['exhaust_factor'] = $get_exhaust_factor($new_row['exhausted_days']);
                //断货天数的来源目前未确定
                //$new_row['warehouse_age_factor'] = $get_warehouse_age_factor($new_row['is_warehouse_days_90']);
                //$new_row['supply_factor'] = $get_supply_factor($new_row['supply_day']);
                $z_scday_expand_factor = $get_z_scday_expand_factor($new_row['weight_sale_pcs']);
                //$new_row['z'] = $z_scday_expand_factor[0];
                //$new_row['sc_day'] = $z_scday_expand_factor[1];
                //$new_row['expand_factor'] = $z_scday_expand_factor[2];
                $new_row['min_order_qty'] = $z_scday_expand_factor[6];

                return $new_row['expand_factor'];
            }
            )
            ->register_columns_recalc_callback(
            //备货提前期
            //备货提前周期=缓冲库存+备货处理周期+权均供货周期+发运时效
                'pre_day',
                function(&$new_row) use ($use_calc_system, $get_php_trans_colname) {
                    //丢失python配置暂当做0处理
                    if (isset($new_row['lost_python_cfg']) && true === $new_row['lost_python_cfg']) {
                        return $new_row['pre_day'];
                    }
                    return $new_row['sp'] + $new_row['bs'] + $new_row['supply_wa_day'] + $new_row['shipment_time'];
                }
            )
            ->register_columns_recalc_callback(
            //平台毛需求（根据“是否首发”，判断若是首发，根据sku+站点+平台维度直接去取新品和首发量配置表中的需求量）
                'platform_require_qty',
                function(&$new_row) use ($use_calc_system, $get_php_trans_colname, $get_py_trans_colname, $future_valid_activities, $get_z_scday_expand_factor) {
                    //首发，根据sku+站点+平台维度直接去取 新品和首发量配置表中的需求量
                    if ($new_row['is_first_sale'] == 1) {
//                      //新品和首发量配置表中的需求量
                        $id = md5($new_row['station_code'] . $new_row['platform_code'] . $new_row['sku']);
                        $new_row['platform_require_qty'] = $this->m_oversea_new_list_model->get_base_info($id)['demand_num'];
                    } else {
//                      //平台加权销量平均值 *  备货提前期 * 扩销系数（根据“平台加权日均销量”去“海外毛需求生成策咯”取）+ 活动量 +  一次修正量
                        $z_scday_expand_factor = $get_z_scday_expand_factor($new_row['weight_sale_pcs']);
                        $new_row['platform_require_qty'] = $new_row[$new_row['aggr_id']]['weight_sale_pcs'] * $new_row['pre_day'] * $z_scday_expand_factor + $new_row['bd'] + $new_row['fixed_amount'];
                    }
                }
            )
            ->register_columns_recalc_callback(
            //平台订购数量 = 平台加权销量平均值 * 一次备货天数
                'purchase_qty',
                function(&$new_row) use ($use_calc_system, $get_php_trans_colname, $get_py_trans_colname, $future_valid_activities) {
                    $new_row['purchase_qty'] = $new_row[$new_row['aggr_id']]['weight_sale_pcs'] * $new_row['sc_day'];
                }
            )
        ;

        $this->_ci->OverseaRebuildPr->run();

        return  $this->_ci->OverseaRebuildPr->report;

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
}
