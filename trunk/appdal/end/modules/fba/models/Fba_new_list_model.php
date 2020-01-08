<?php

require_once APPPATH . 'modules/basic/traits/Table_behavior.php';

/**
 * fba新品列表model
 *
 * @author zc
 * @date 2019-10-24
 * @link
 */
class Fba_new_list_model extends MY_Model
{
    private $_ci;

    use Table_behavior;

    private $_rpc_module = 'fba';

    public function __construct()
    {
        $this->database = 'stock';
        $this->primaryKey = 'id';

        $this->_ci   =& get_instance();
        $this->table_name = 'yibai_fba_new_cfg';
        parent::__construct();
    }


    public function pk($gid)
    {
        $result = $this->_db->from($this->table_name)->where('id', $gid)->limit(1)->get()->result_array();
        if ($result)
        {
            return $result[0];
        }
        else
        {
            return [];
        }
    }

    public function get_id($data)
    {
        //md5(account_id,site,seller_sku,erp_sku,fnsku,asin)
        $id = md5(trim($data['account_id']).strtolower(trim($data['site'])).trim($data['seller_sku']).
                      trim($data['erpsku']).trim($data['fnsku']).trim($data['asin']));
        return $id;
    }

    public function get_info_by_id($id)
    {
        $this->db->select('demand_num,is_delete,approve_state');
        $this->db->from($this->table_name);
        $this->db->where('id', $id);
        $this->db->limit(1);
        //pr($this->db->get_compiled_select('',false));exit;
        return $this->db->get()->row_array();
    }

    public function get_info_by_ids($ids)
    {
        if(empty($ids)) return [];
        $ids = array_unique($ids);
        $this->db->select('id,demand_num,is_delete,approve_state');
        $this->db->from($this->table_name);
        $this->db->where_in('id', $ids);
        $this->db->limit(count($ids));
        $result =$this->db->get()->result_array();
        return key_by($result,'id');
    }

    /**
     * 获取
     * @param string|array $ids
     * @return array
     */
    public function get_demand_by_ids($ids)
    {
        if (is_string($ids)) {
            $ids = explode(',', $ids);
        }

        $sql = sprintf(
            'SELECT id, IF(demand_num > stock_num, demand_num - stock_num, 0 ) as require_qty from %s where approve_state = %d and is_delete = %d and id in (%s)',
            $this->table_name,
            NEW_APPROVAL_SUCCESS,
            0,
            array_where_in($ids)
            );

        $result = $this->_db->query($sql)->result_array();
        return empty($result) ? [] : array_column($result, 'require_qty', 'id');
    }

    public function add($params = [], $user_id = '', $user_name = '')
    {
        try {
            //1.验证是否新品,java在做
            //2.验证账号、站点、sellersku、erpsku、fnsku、asin是否存在记录
            $this->_ci->load->model('Fba_amazon_account_model', 'm_amazon_account', false, 'fba');
            $params['account_id'] = $this->_ci->m_amazon_account->get_account_id_by_name(trim($params['account_name']));
            $id = $this->get_id($params);
            $result_data = $this->get_info_by_id($id);
            $now     = date('Y-m-d H:i:s');
            $this->db->trans_start();
            if (empty($result_data)) {
                //3.1.不存在则添加
                $seach_params = [
                    'pageSize' => 1,
                    'isDel' => 0,
                    'userName' => trim($params['staff_zh_name'])
                ];
                $list = RPC_CALL('YB_J1_004', $seach_params);
                $salsman = $list['data']['records'][0]['userNumber'] ?? 0;
                $this->_ci->load->model('Fba_amazon_group_model', 'm_amazon_group', false, 'fba');
                $sale_group = $this->_ci->m_amazon_group->get_group_id(trim($params['sale_group']));
                $insert_data = [
                    'id'=> $id,
                    'sale_group' => $sale_group,//销售分组id
                    'salesman' => $salsman,
                    'staff_zh_name'   => trim($params['staff_zh_name']),
                    'site' => $params['site'],
                    'seller_sku'   => trim($params['seller_sku']),
                    'erp_sku'  => trim($params['erpsku']),
                    'fnsku'       => trim($params['fnsku']),
                    'asin'        => trim($params['asin']),
                    'demand_num' => trim($params['demand_num']),//需求数量
                    'created_at' => $now,//更新时间
                    'created_uid' => $user_id,//更新uid
                    'created_zh_name' => $user_name,//更新uid
                    'approve_state' => 0,//审核状态
                    'sale_group_name' => trim($params['sale_group']),//销售分组id
                    'account_name' => trim($params['account_name']),
                    'account_id' => $params['account_id']
                ];
                $this->db->insert($this->table_name,$insert_data);
            }
            else if($result_data['is_delete'] == 0 && $result_data['demand_num'] != $params['demand_num']){
                //3.2.存在则更新,更新需求数量，更新时间刷新，状态待审核
                $update_data = [
                    'demand_num' => $params['demand_num'],//需求数量
                    'updated_at' => $now,//更新时间
                    'updated_uid' => $user_id,//更新uid
                    'updated_zh_name' => $user_name,//更新uid
                    'approve_state' => 0,//审核状态
                ];
                $this->db->where('id', $id);
                $this->db->update($this->table_name, $update_data);
            }
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return false;
            } else {
                return true;
            }

        } catch (Exception $e) {
            log_message('ERROR', sprintf("批量修改异常 %s", $e->getMessage()));
            throw new \RuntimeException(sprintf("批量修改异常 %s", $e->getMessage()), 500);
        }
    }

    public function madd($params)
    {
        return $this->_db->insert_batch($this->table_name, $params);
    }

    public function delete_batch($ids)
    {
        $this->_db->where_in('id',array_column($ids,'id'));
        $this->_db->where('approve_state',NEW_APPROVAL_INIT);
        $this->_db->delete($this->table_name);
        return $this->_db->affected_rows();
    }

    public function updateRemark($data)
    {
        $this->db->where('id', $data['new_id']);
        $update_data = [
            'updated_at' => $data['created_at'],//更新时间
            'updated_uid' => $data['op_uid'],//更新uid
            'updated_zh_name' => $data['op_zh_name'],//更新uid
            'remark' => $data['remark'],
        ];
        $this->db->update($this->table_name, $update_data);
    }

    public function get_base_info($id)
    {
        $query = $this->_db->from($this->table_name)
            ->select('id,erp_sku,approve_state,created_at,updated_at,sale_group_name,account_name,salesman,
                      staff_zh_name,site,seller_sku,fnsku,asin,demand_num,stock_num,account_id,sale_group')
            ->where('id', $id)
            ->where('is_delete = ', 0)
            ->limit(1);
        return $query->get()->row_array();
    }

    public function modify($params = [], $user_id = '', $user_name = '')
    {
        try {
            $now = date('Y-m-d H:i:s');
            if(empty($params['id']))
            {
                return false;
            }
            $new_info = $this->get_base_info($params['id']);
            if(empty($new_info))
            {
                return false;
            }
            $this->db->trans_start();
            //3.2.存在则更新,更新需求数量，更新时间刷新，状态待审核
            $update_data = [
                'demand_num' => $params['demand_num'],//需求数量
                'updated_at' => $now,//更新时间
                'updated_uid' => $user_id,//更新uid
                'updated_zh_name' => $user_name,//更新uid
                'approve_state' => 0,//审核状态
            ];
            $this->db->where('id', $params['id']);
            $this->db->update($this->table_name, $update_data);

            $insert_log = [
                'new_id' => $params['id'],
                'uid' => $user_id,
                'user_name' => $user_name,
                'context' => "新品修改成功,原需求数量:{$new_info['demand_num']},修改后需求数量:{$params['demand_num']}",
            ];
            $this->_ci->load->model('Fba_new_log_model', 'fba_new_log', false, 'fba');
            $this->_ci->fba_new_log->add($insert_log);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return false;
            } else {
                return true;
            }

        } catch (Exception $e) {
            log_message('ERROR', sprintf("批量修改异常 %s", $e->getMessage()));
            throw new \RuntimeException(sprintf("批量修改异常 %s", $e->getMessage()), 500);
        }
    }

    public function get_approving_info($id)
    {
        $query = $this->_db->from($this->table_name)
                        ->select('id')
                        ->where('approve_state', NEW_APPROVAL_INIT)
                        ->where('is_delete = ', 0);
        return $query->get()->result_array();
    }

    public function batch_approve_all($params = [])
    {
        $result = [
            "total"=> 0,
            "processed"=>0,
            "undisposed"=>0
        ];
        try {
            //1.获取所有未审核的记录数
            $this->db->from($this->table_name);
            $this->db->where('approve_state', NEW_APPROVAL_INIT);
            $this->db->where('is_delete', 0);
            $total = $this->db->count_all_results();//获取总条数
            //2.审核所有未审核的记录
            $now = date('Y-m-d H:i:s');
            $this->db->trans_start();
            $update_data = [
                'approved_at' => $now,//更新时间
                'approved_uid' => $params['user_id'],//更新uid
                'approved_zh_name' => $params['user_name'],//更新uid
                'approve_state' => $params['result'],//审核状态
            ];
            $this->db->where('approve_state', NEW_APPROVAL_INIT);
            $this->db->where('is_delete', 0);
            $this->db->update($this->table_name, $update_data);
            $update_count = $this->db->affected_rows();
            //3.添加日志
            /*if ($update_count > 0) {
                $new_state_name = NEW_APPROVAL_STATE[$params['result'];
                $insert_log = [
                    'new_id' => 0,
                    'uid' => $user_id,
                    'user_name' => $user_name,
                    'context' => "新品批量审核所有未审核的为:{$new_state_name},总审核量为:{$update_count}",
                ];
                $this->_ci->load->model('Fba_new_log_model', 'fba_new_log', false, 'fba');
                $this->_ci->fba_new_log->add($insert_log);
            }*/
            $this->db->trans_complete();
            $result['total'] = $total;
            $result['processed'] = $update_count;
            $result['undisposed'] = $total - $update_count;
        } catch (Exception $e) {
            log_message('ERROR', sprintf("批量修改异常 %s", $e->getMessage()));
            throw new \RuntimeException(sprintf("批量修改异常 %s", $e->getMessage()), 500);
        }
        finally
        {
            return $result;
        }
    }
}
