<?php 

/**
 * 手动推送
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-21
 * @link
 * @throw 
 */
class ManualPurchasePush
{
    /**
     * 分批推送
     * 
     * @var integer
     */
    const PURCHASE_PUSH_BATCH = 1;
    
    /**
     * 勾选推送
     * 
     * @var integer
     */
    const PURCHASE_PUSH_SELECTED = 2;
    
    /**
     * 
     * @var unknown
     */
    private $_ci;
    
    /**
     * db
     * 
     * @var unknown
     */
    private $_other_db;
    
    /**
     * 
     * @var unknown
     */
    private $_model;
    
    /**
     * 执行结果
     *
     * @var unknown
     */
    private $_report;
    
    /**
     * 符合条件的推送的采购单号
     * 
     * @var unknown
     */
    private $_pur_sns;
    
    /**
     * 推送方式
     * 
     * @var unknown
     */
    private $_push_type = ManualPurchasePush::PURCHASE_PUSH_BATCH;
    
    /**
     * 
     * @return FbaApproveFirst
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        return $this;
    }
    
    /**
     * 注入一个model
     * 
     * @param unknown $db
     */
    public function set_model($model)
    {
        $this->_model = $model;
        $this->_other_db = $this->_model->getDatabase();
        return $this;
    }
    
    /**
     * 设置选择要推送的采购单, 过滤检测采购单
     * 
     * @param unknown $pur_sns
     */
    public function set_selected_pur_sn($pur_sns)
    {
        $pur_sns = is_string($pur_sns) ? explode(',', $pur_sns) : $pur_sns;
        $this->_pur_sns = $this->valid_push_pur_sns($pur_sns);
        $this->_report = [
                'total' => count($pur_sns),
                'succ' => 0,
                'fail' => 0,
                'ignore' => count($pur_sns) - count($this->_pur_sns)
        ];
        return $this;
    }
    
    /**
     * 设置推送的类型
     * 
     * @param unknown $type
     */
    public function set_push_type(bool $is_batch)
    {
        $this->_batch_clean();
        $this->_push_type = $is_batch ? ManualPurchasePush::PURCHASE_PUSH_BATCH : ManualPurchasePush::PURCHASE_PUSH_SELECTED;
        return $this;
    }
    
    /**
     * 报告
     * @return unknown
     */
    public function report()
    {
        return $this->_report;
    }
    
    /**
     * 发送系统日志
     */
    public function send_system_log($module)
    {
        $this->_ci->load->service('basic/SystemLogService');
        $log_context = sprintf('手动批量推送采购订单:%s', implode(',', $this->_pur_sns));
        $this->_ci->systemlogservice->send([], $module, $log_context . $this->_report['msg']);
    }
    
    /**
     * 批量更新成功。
     * 
     */
    public function push()
    {
        if ($this->_push_type == ManualPurchasePush::PURCHASE_PUSH_SELECTED)
        {
            $this->push_selected();
        }
        else
        {
            $this->push_batch();
        }
    }
    
    protected function push_batch()
    {
        set_time_limit(0);
        $batch_size = 500;
        
        //获取搜索的数据
        $this->_ci->load->library('rediss');
        $this->_ci->load->service('basic/SearchExportCacheService');
        $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::PLAN_PUR_LIST_SEARCH_EXPORT)->get();
        $total = intval(substr($quick_sql, 0, 10));
        $quick_sql = substr($quick_sql, 10);
        if (!$quick_sql)
        {
            throw new \RuntimeException(sprintf('搜索条件已经失效，请重新搜索'));
        }
        //对搜索返回的字段进行处理
        $new_quick_sql = sprintf('SELECT `%s`.`pur_sn` %s', $this->_model->getTable(), substr($quick_sql, stripos($quick_sql, 'FROM')));
        
        $nums = ceil($total / $batch_size);
        if ($nums == 1)
        {
            $this->_pur_sns = array_column($this->_other_db->query($quick_sql)->result_array(), 'pur_sn');
            $this->_report['total'] = count($this->_pur_sns);
            $this->push_selected();
            return;
        }
        
        for ($i = 1; $i <= $nums; $i ++)
        {
            $this->_pur_sns = array_column($this->_db->query($quick_sql.' limit '.$batch_size)->result_array(), 'pur_sn');
            $this->_report['total'] += count($this->_pur_sns);
            try {
                if (empty($this->_pur_sns))
                {
                    continue;
                }
                $this->push_selected();
            } 
            catch (\Exception $e)
            {
               //异常，继续执行
            }
        }
    }
    
    /**
     * 手动推送
     * 
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function push_selected()
    {
        //没有有效的采购单
        if (empty($this->_pur_sns))
        {
            throw new \InvalidArgumentException('没有有效的采购单，请重新选择当天未推送并且数量大于0的采购订单！');
        }
        try
        {
            $result = RPC_CALL('YB_J1_PLAN_001', $this->_pur_sns );
            //报表汇总
            $this->_report['succ'] += $result['data']['succeedNum'];
            $this->_report['fail'] += $result['data']['failureNum'];
            $this->_report['ignore'] += $result['data']['undoNum'];
            $this->_report['msg'] = sprintf('选择了%d条记录，处理成功%d条，处理失败%d条，无需处理%d条', $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
        }
        catch (\Throwable $e)
        {
            $this->_report['fail'] += count($this->_pur_sns);
            $this->_report['msg'] = sprintf('选择了%d条记录，处理成功%d条，处理失败%d条，无需处理%d条', $this->_report['total'], $this->_report['succ'], $this->_report['fail'], $this->_report['ignore']);
            log_message('ERROR', sprintf('手动批量推送采购订单：%s，抛出异常： %s', implode(',', $this->_pur_sns), $e->getMessage()));
            throw new \RuntimeException(sprintf('手动批量推送采购订单更新失败,接口异常：%s', $e->getMessage()), 500);
        }
    }

    /**
     * 对时间和状态直接给予提示， 对数量忽略
     * 
     * @param unknown $pur_sns
     */
    protected function valid_push_pur_sns($pur_sns)
    {
        if (empty($pur_sns))
        {
            return [];
        }
        $pur_sn_rows = $this->_model->get_by_pur_sns($pur_sns, 'pur_sn, push_stock_quantity, is_pushed, created_at, state');
        if (empty($pur_sn_rows))
        {
            return [];
        }
        $today_start = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
        $today_end = mktime(23, 59, 59, date('m'), date('d'), date('Y'));
        
        foreach ($pur_sn_rows as $key => $row)
        {
            $created_at = intval($row['created_at']);
            if ($created_at < $today_start || $created_at > $today_end)
            {
                unset($pur_sn_rows[$key]);
                continue;
                //throw new \InvalidArgumentException('请勾选当天的备货单进行推送！');
            }
            if ($row['is_pushed'] == PUR_DATA_PUSHED)
            {
                unset($pur_sn_rows[$key]);
                continue;
                //throw new \InvalidArgumentException('请勾选未推送的备货单进行推送！');
            }
            if (intval($row['push_stock_quantity']) == 0)
            {
                unset($pur_sn_rows[$key]);
                continue;
            }
            if ($row['state'] != PUR_STATE_ING)
            {
                unset($pur_sn_rows[$key]);
                continue;
            }
        }
        return array_column($pur_sn_rows, 'pur_sn');
    }

    private function _batch_clean()
    {
        
    }
}