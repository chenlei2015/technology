<?php

/**
 * FBA 促销sku服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class PrPromotionService
{
    public static $s_system_log_name = 'FBA-PROMOTION';
    
    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_promotion_sku_model', 'fba_promotion_list', false, 'fba');
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
    public function update_remark($params)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        
        $record = ($pk_row = $this->_ci->fba_promotion_list->findByPk($gid)) === null ? [] : $pk_row->toArray();
        if (empty($record))
        {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark'])
        {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->fba_promotion_list);
        $this->_ci->Record->set('remark', $remark);
        $this->_ci->Record->set('updated_at', time());
        
        $count = $this->_ci->Record->update();
        if ($count !== 1)
        {
            throw new \RuntimeException(sprintf('更新备注失败'), 500);
        }
        
        return true;
    }

    /**
     * 批量删除
     *
     * @param unknown $gid_arrs
     * @return unknown
     */
    public function batch_delete($gid_arrs)
    {
        if (empty($gid_arrs))
        {
            return 0;
        }
        return $this->_ci->fba_promotion_list->batch_delete($gid_arrs);
    }
    
    /**
     * 批量导入
     *
     * @param array $params <pre>
     *  primary_key,
     *  map, <array> ['sku' => 0, 'remark' => 1]
     *  selected
     *  </pre> 复用以前的实现。 参数格式沿用以前
     * @param mixed $priv_uid -1, staff_code
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return boolean
     */
    public function import($params)
    {
        $report = [
                'total' => 0,
                'processed' => 0,
                'undisposed' => 0,
        ];
        $selected = json_decode($params['selected'], true);
        unset($params['selected']);
        
        $report['total'] = $report['undisposed'] = count($selected);
        if (empty($selected))
        {
            return $report;
        }
        
        $common_cols = [
                'created_at' => date('Y-m-d H:i:s'),
                'created_time' => strtotime(date('Y-m-d')),
                'created_uid' => get_active_user()->staff_code
        ];
        $updated_at = time();
        
        $all_skus = array_keys($selected);
        //对sku进行检测， 存在update
        $running_skus_arr = $this->_ci->fba_promotion_list->get_running_skus($all_skus);
        if (!empty($running_skus_arr)) {
            foreach ($running_skus_arr as $key => &$row)
            {
                $row += $common_cols;
                $row['remark'] = $selected[$row['sku']][$params['map']['remark']];
                $row['updated_at'] = $updated_at;
            }
        }
        //pr($params['map']['remark']);
        //pr($selected);exit;
        //不存在insert， 生成gid
        $running_skus = array_column($running_skus_arr, 'sku');
        $new_skus = array_diff($all_skus, $running_skus);
        $insert_skus_arr = [];
        if (!empty($new_skus)) {
            foreach ($new_skus as $sku)
            {
                $tmp = [
                        'gid' => $this->_ci->fba_promotion_list->gen_id(),
                        'sku' => $sku,
                        'remark' => $selected[$sku][$params['map']['remark']]
                ];
                $tmp += $common_cols;
                $insert_skus_arr[] = $tmp;
            }
        }
        
        $report['undisposed'] = 0;
        
        try
        {
            if (!empty($running_skus_arr))
            {
                $report['processed'] += $this->_ci->fba_promotion_list->update_exists_running_skus($running_skus_arr);
            }
        }
        catch (\Throwable $e)
        {
            $report['undisposed']  += count($running_skus_arr);
        }
        
        try
        {
            if (!empty($insert_skus_arr))
            {
                $report['processed'] += $this->_ci->fba_promotion_list->insert_running_skus($running_skus_arr);
            }
        }
        catch (\Throwable $e)
        {
            $report['undisposed']  += count($insert_skus_arr);
        }
        
        unset($insert_skus_arr, $running_skus_arr, $params);
        
        return $report;
    }
    
    /**
     * 设置促销sku过期
     * 1、从创建时间当日0点算起，15天为进行中状态，过期状态自动变为已结束。
     */
    public function promotion_sku_expire()
    {
        return $this->_ci->fba_promotion_list->expired();
    }
    

}
