<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/3/8
 * Time: 16:11
 */
class Oversea_region_cfg_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
        $this->table = 'yibai_store_oversea_region_cfg';
    }

    /**
     * 地区仓库关系列表
     * @param array $params
     * @return bool
     */
    public function getRegionWarehouseList($region=''){
        $where = [];
        if (!empty($region)){
            $where['region'] = $region;
        }
        $result = $this->_db->select('region,warehouse_code')
                    ->from($this->table)
                    ->where($where)
                    ->get()
                    ->result_array();
        return $result;
    }

    /**
     * 更新是否选中
     */
    public function changeRegionWarehouse($region,$warehouse_codes=[],$user_id=0,$user_name=''){
        $res = false;
        if (!empty($region)) {
            $this->db->trans_begin();
            try {
                //先删除旧的
                $this->db->where_in('warehouse_code', $warehouse_codes)->or_where(['region' => $region]);
                $res = $this->db->delete($this->table);
                if ($res) {
                    foreach ($warehouse_codes as $warehouse_code) {
                        $data = [
                            'region' => $region,
                            'warehouse_code' => $warehouse_code,
                            'op_uid' => $user_id,
                            'op_zh_name' => $user_name,
                            'update_time' => date("Y-m-d H:i:s")
                        ];
                        $res = $this->db->insert($this->table, $data);
                        if (!$res) {
                            break;
                        }
                    }
                }
                if ($res) {
                    $this->db->trans_complete();
                }else{
                    $this->db->trans_rollback();
                }

            }catch (\Exception $e)
            {
                $error_occurred = true;
            }
        }
        return $res;
    }


}