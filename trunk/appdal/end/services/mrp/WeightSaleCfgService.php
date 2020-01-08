<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/5/20
 * Time: 16:25
 */

class WeightSaleCfgService
{
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Weight_sale_cfg_model', 'm_weight_sale_cfg', false, 'mrp');
        return $this;
    }

    public function get_cfg_list($logistics_id)
    {
        return $this->_ci->m_weight_sale_cfg->getCfgInfo($logistics_id);
    }

    public function edit_cfg($params)
    {
        $cfginfo = $params['cfginfo'];
        //降序排序天数,方便BI处理数据
        array_multisort(array_column($cfginfo,'number_of_days'),SORT_ASC,$cfginfo);
        foreach ($cfginfo as $key => &$value )
        {
            $value['logistics_id'] = $params['logistics_id'];
        }
        $db = $this->_ci->m_weight_sale_cfg->getDatabase();
        $db->trans_start();
        //先删除
        $this->_ci->m_weight_sale_cfg->delete_cfg($params['logistics_id']);
        //后插入
        $this->_ci->m_weight_sale_cfg->add_cfg($cfginfo);
        $db->trans_complete();
        if ($db->trans_status() === false)
        {
            throw new \RuntimeException(sprintf('事务提交成功，但状态检测为false'), 500);
        }else{
            return TRUE;
        }

    }
}