<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/5/20
 * Time: 16:25
 */
class BusinessLineCfgService
{
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Business_required_qty_coefficient_model', 'm_required_cfg', false, 'mrp');
        $this->_ci->load->model('Business_sales_extremum_model', 'm_extremum_cfg', false, 'mrp');

        return $this;
    }

    public function get_cfg_list($business_line = 1)
    {


        $data['required'] = $this->_ci->m_required_cfg->getCfgInfo($business_line);
        $data['extremum'] = $this->_ci->m_extremum_cfg->getCfgInfo($business_line);
        /**
         * 业务线逻辑配置
         * 初始值
         */
        if (empty($data['required'])) {
            $init_required[0] = [
                'business_line'           => $business_line,
                'daily_avg_sale'          => '0.00',
                'required_qty_multiplier' => '1.00',
                'created_at'              => '0000-00-00 00:00:00',
            ];
            $data['required'] = $init_required;
        }
        if (empty($data['extremum'])) {
            $init_extremum[0] = [
                'business_line' => $business_line,
                'one_day_sale'  => '999999',
                'today_sale'    => '1.00',
                'created_at'    => '0000-00-00 00:00:00',
            ];
            $data['extremum'] = $init_extremum;
        }

        return $data;
    }

    public function edit_cfg($params)
    {

//        $required = $params['required'];//需求数量系数
//        foreach ($required as $key => &$value) {
//            $value['business_line'] = $params['business_line'];
//        }


        //需求数量系数配置
        /*        $db = $this->_ci->m_required_cfg->getDatabase();
                $db->trans_start();
                //先删除
                $this->_ci->m_required_cfg->delete_cfg($params['business_line']);
                //后插入
                $this->_ci->m_required_cfg->add_cfg($required);
                $db->trans_complete();
                if ($db->trans_status() === false) {
                    throw new \RuntimeException(sprintf('需求数量系数事务异常'), 500);
                    exit;
                }*/
        $extremum = $params['extremum'];//销量去极值

        foreach ($extremum as $key => &$value) {
            $value['business_line'] = $params['business_line'];
        }

        //销量去极值配置
        $db = $this->_ci->m_extremum_cfg->getDatabase();
        $db->trans_start();
        //先删除
        $this->_ci->m_extremum_cfg->delete_cfg($params['business_line']);
        //后插入
        $this->_ci->m_extremum_cfg->add_cfg($extremum);
        $db->trans_complete();
        if ($db->trans_status() === false) {
            throw new \RuntimeException(sprintf('销量去极值事务异常'), 500);
        }

        return true;
    }

    /**
     * 根据销量获取配置中的扩销因子
     *
     * @param unknown $bussiness_line
     * @param unknown $sale_qty
     *
     * @return number|unknown
     */
    public function get_require_factor_by_daysale($bussiness_line, $sale_qty)
    {
        static $cfg;
        if (!$cfg) {
            $cfg = array_column($this->_ci->m_required_cfg->getCfgInfo($bussiness_line), 'required_qty_multiplier', 'daily_avg_sale');
            ksort($cfg);
        }

        // 没有匹配的记录时取默认因子
        $default_factor = 1;
        $index          = 0;
        $pre_factor     = 0;
        $max            = count($cfg) - 1;
        $sale_qty       = intval($sale_qty);

        foreach ($cfg as $qty => $factor) {
            if ($sale_qty < $qty) {
                if ($index == 0) {
                    return $default_factor;
                } elseif ($index == $max) {
                    return $factor;
                } else {
                    return $pre_factor;
                }
            } else {
                $pre_factor = $factor;
            }
            $index++;
        }

        return $default_factor;
    }
}