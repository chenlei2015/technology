<?php

/**
 * 发运计划
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2018-12-20
 * @link
 */
class OverseaDetailService
{
    public static $s_system_log_name = 'SHIPMENT';

    /**
     * __construct
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_list_model', 'm_detail', false, 'shipment');
//        $this->_ci->load->helper('inland_helper');
        return $this;
    }

    /**
     * 生成发运计划编号
     *
     */

    protected function general_shipment_sn($scene)
    {
        $this->_ci->load->service('basic/OrderSnPoolService');
        return $this->_ci->ordersnpoolservice->setScene($scene)->pop();
    }
//    public function general_shipment_sn()
//    {
//        $this->_ci->rediss->incr('PLAN_FBA_SHIPMENT_SN_INCR');
//        $num = $this->_ci->rediss->get('PLAN_FBA_SHIPMENT_SN_INCR');
//
//        $length = strlen($num);
//        if(strlen($num)<5){
//            $time = 5-$length;
//            for ($i=0;$i<$time;$i++){
//                $num = '0'.$num;
//            }
//        }
//        $shipment_sn = sprintf('FYFBA-%s-%s',date('YmdHis'),$num);
//        return $shipment_sn;
//    }


    /**
     * 修改发运数量
     */
//    public function modify_shipment_qty($params)
//    {
//        $gid = $params['gid'];
//        $shipment_qty = $params['shipment_qty'];
//        $record = $this->_ci->m_detail->pk($gid);
//        if (empty($record))
//        {
//            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
//        }
//        $this->_ci->load->classes('basic/classes/Record');
//        $this->_ci->Record->recive($record);
//        $this->_ci->Record->setModel($this->_ci->m_detail);
//        $this->_ci->Record->set('shipment_qty', $shipment_qty);
//        $this->_ci->Record->set('updated_at', date('Y-m-d H:i:s'));
//        $this->_ci->Record->set('updated_uid', get_active_user()->staff_code);
//
//        if($shipment_qty>$record['available_inventory']){
//            throw new \RuntimeException(sprintf('sku:%s修改发运数量失败,失败原因:sku的发运数量大于可调拨库存',$record['sku']), 500);
//        }
//        $db = $this->_ci->m_detail->getDatabase();
//
//        $db->trans_start();
//        $count = $this->_ci->Record->update();
//        if ($count !== 1)
//        {
//            throw new \RuntimeException(sprintf('修改失败'), 500);
//        }
//        $db->trans_complete();
//
//        if ($db->trans_status() === FALSE)
//        {
//            throw new \RuntimeException(sprintf('修改失败'), 500);
//        }
//
//        return true;
//    }

    /**
     * 修改发运数量
     */
    public function modify_shipment_qty($params)
    {
        $params = $params['data'];
        $gids_arr = array_column($params,'gid');
        $gids = sprintf("'%s'",implode("','",$gids_arr));
        $record = $this->_ci->m_detail->check_gid($gids);
        if(empty($record)){
            throw new \RuntimeException(sprintf('未查询对应的记录'), 500);
        }
        $push_status = array_unique(array_column($record,'push_status'));
        if (count($push_status)>1){
            throw new \RuntimeException(sprintf('只能修改待推送状态的发运数量'), 500);
        }

        //sku相同
        $all_sku = array_unique(array_column($record,'sku'));

        $data['all_sku'] = $all_sku;
        $data['shipment_sn'] = $record[0]['shipment_sn'];
        $avail_qty = $this->_ci->m_detail->get_available_inventory($data);

        $data = [];
        foreach ($all_sku as $sku){
            $data['sku'] = $sku;
            $data['shipment_sn'] = $record[0]['shipment_sn'];
            $shipment_qtys = $this->_ci->m_detail->get_shipment_qty($data);//除去修改项的所有发运数量

            foreach ($params as $key => $item){
                if(isset($shipment_qtys[$item['gid']])){
                    $shipment_qtys[$item['gid']] = $item['shipment_qty'];
                }
            }

            $sum = array_sum($shipment_qtys);
            if($sum > $avail_qty[$sku]){//修改失败, sku的发运数量之和大于可调拨库存
                $error_sku[] = $sku;
            }
        }

        if (isset($error_sku)){
            throw new \RuntimeException(sprintf('SKU:%s修改发运数量失败,失败原因:sku的发运数量之和大于可调拨库存',implode(',',$error_sku)), 500);
        }

        $updated_at = date('Y-m-d H:i:s');
        $updated_uid = get_active_user()->staff_code;
        //组织更新字段
        foreach ($params as $key => &$item){
            $item['updated_at'] = $updated_at;
            $item['updated_uid'] = $updated_uid;
        }

        $db = $this->_ci->m_detail->getDatabase();

        $db->trans_start();
        $count = $this->_ci->m_detail->batch_update($params);

        $db->trans_complete();

        if ($db->trans_status() === FALSE)
        {
            throw new \RuntimeException(sprintf('修改失败'), 500);
        }

        return $count;
    }

    /**
     * 在发运跟踪详情列表里的发运详情
     */
    public function shipment_detail($params)
    {
        $this->_ci->load->model('Oversea_shipment_logistics_model', 'm_shipment_detail', false, 'shipment');
        if (!isset($params['pr_sn'])||empty($params['pr_sn'])){
            throw new \InvalidArgumentException(sprintf('参数错误'), 3001);
        }
        $pr_sn = $params['pr_sn'];
        $result = $this->_ci->m_shipment_detail->get_shipment_detail($pr_sn);
        if (empty($result)){
            return [];
        }
        return $result;
    }

}
