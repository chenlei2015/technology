<?php

/**
 * FBA ERPSKU属性配置服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2019-09-04
 * @link
 */
class SkuCfgService
{
    public static $s_system_log_name = 'FBA-SKU-CFG';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_sku_cfg_model', 'm_sku_cfg', false, 'fba');
        $this->_ci->load->helper('fba_helper');

        return $this;
    }

    public function detail($id)
    {
        return $this->_ci->m_sku_cfg->pk($id);
    }

    /**
     * 导入批量修改
     */
    public function modifyByExcel($params)
    {
//        $ids = implode("','",array_column($params,'id'));
//        $ids = sprintf("'%s'",$ids);
        $ids    = array_column($params, 'id');
        $result = $this->modify_cfg($params, $ids);
        if ($result['code'] == 500) {
            return 0;
        } elseif ($result['code'] == 200) {
            return $result['msg'];
        }
    }

    public function modifyOne($params)
    {
        $ids       = $params['id'];
        $arr[$ids] = $params;
        $result    = $this->modify_cfg($arr, $ids);
        if ($result['code'] == 500) {
            throw new \RuntimeException($result['msg'], $result['code']);

            return false;
        } elseif ($result['code'] == 200) {
            return true;
        }
    }

    /**
     * 可修改项:
     * 最大备货天数,最大供货周期,最大安全库存天数,备货处理周期,
     *
     * @param $params
     */
    public function modify_cfg($params, $ids)
    {
        $this->_ci->load->model('Fba_sku_cfg_history_model', 'm_cfg_history_model', false, 'fba');
        $msg['code'] = 200;
        $processed   = 0;//已处理
        $undisposed  = 0;//未处理
        //查询是否存在
        if (!$cfg_info = $this->_ci->m_sku_cfg->get_cfg($ids)) {
            $msg['code'] = 500;
            $msg['msg']  = '要修改的记录异常,请稍后重试';

            return $msg;
        };

        //组织要修改的记录
        $diff        = [];
        $count       = 0;
        $active_user = get_active_user();

        foreach ($params as $key => &$item) {
            foreach ($item as $k => $val) {
                if ($k == 'id') {
                    continue;
                }
                if ($cfg_info[$key][$k] != $val) {

//                    $diff[$key][$k] = $val;
                    $count++;
                } else {
                    unset($item[$k]);
                }
            }
            if (count($item) == 1) {
                unset($params[$key]);
            } else {
                $item['state'] = CHECK_STATE_INIT;
                $key_map       = [
                    //session => db_col
                    'userNumber' => 'updated_uid',
                    'userName'   => 'updated_zh_name',
                ];
                append_login_info($item, $key_map);
            }
        }

        if ($count == 0) {//没有修改直接返回
            $msg['code'] = 500;
            $msg['msg']  = '要修改的记录异常,请稍后重试';

            return $msg;
        }
        $ids = array_column($params, 'id');
//        pr($params);exit;
//        echo $ids;exit;
        //事务处理数据库操作
        $this->_ci->load->model('Stock_log_model', 'm_log', false, 'fba');
        $db = $this->_ci->m_sku_cfg->getDatabase();
        $db->trans_start();
        $this->_ci->m_cfg_history_model->check_approve_state_add($ids);
        $affect_rows = $db->update_batch($this->_ci->m_sku_cfg->getTable(), $params, 'id');
        $this->_ci->m_log->modifyStockLog($params);//写入修改日志
        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            log_message('ERROR', sprintf("批量修改异常%s", json_encode($params)));
        }
        $msg['msg'] = $affect_rows;

        return $msg;
    }

    public function update_column($params, $user_id = '', $user_name = '')
    {
        try {
            $result = ["processed" => 0,"undisposed" => 0];
            $column_name = $params['column'];
            $column_value = $params['column_value'];
            $update_data = [
                $column_name => $column_value,
                'updated_at' => date('Y-m-d H:i:s'),//更新时间
                'updated_uid' => $user_id,//更新uid
                'updated_zh_name' => $user_name,//更新姓名
            ];
            $db = $this->_ci->m_sku_cfg->getDatabase();
            $db->trans_start();
            $this->_ci->load->model('Stock_log_model', 'm_log', false, 'fba');
            $row_count = $this->_ci->m_sku_cfg->update_column($update_data);
            $db->trans_complete();
            $result["processed"] = $row_count;

        } catch (Exception $e) {
            log_message('ERROR', sprintf("erpsku批量修改{$params['column']}:{$params['column_value']}异常 %s", $e->getMessage()));
            throw new \RuntimeException(sprintf("erpsku批量修改{$params['column']}:{$params['column_value']}异常 %s", $e->getMessage()), 500);
        }
        finally{
            return $result;
        }
    }

}
