<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/10
 * Time: 14:11
 */
class Logistics_order_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->common = $this->load->database('common',TRUE);
        $this->count = 0;
    }


    public function insert($data)
    {
        $today = date('Y-m-d');
        if(!is_array($data)){
            throw new \InvalidArgumentException(sprintf('数据异常,不是数组'));
        }
        //先删除后导入
        $sql = "DELETE FROM yibai_logistics_order WHERE `date` = '{$today}'";
        $this->common->query($sql);
        foreach ($data as $key => $value){
            $sql = "INSERT IGNORE INTO yibai_logistics_order (`shipment_id`,`date`) VALUES ('{$value['A']}','{$today}')";
            $this->common->query($sql);
            $row = $this->common->affected_rows();
            if($row == 1){
                $this->count++;
            }
        }
        return $this->count;
    }
}
