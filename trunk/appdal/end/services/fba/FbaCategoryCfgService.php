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
class FbaCategoryCfgService
{
    public static $s_system_log_name = 'FBA-Category-CFG';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Category_cfg_model', 'm_cate', false, 'fba');
        $this->_ci->load->helper('fba_helper');
        $this->business_line = BUSINESS_LINE_FBA;
        return $this;
    }


    public function list()
    {
        return $this->_ci->db->select('*')
            ->where('business_line', $this->business_line)
            ->get($this->table)
            ->result_array();
    }


    public function get_all_category()
    {
        $this->yibai_product = $this->_ci->load->database('yibai_product', true);
        $result              = $this->yibai_product->select('id,category_cn_name')
            ->where('category_parent_id', 0)
            ->where('category_level', 1)
            ->where('category_status', 1)
            ->get('yibai_product_category')
            ->result_array();
        if (!empty($result)) {
            $result = array_column($result, 'category_cn_name', 'id');
        }

        return $result;
    }

    /**
     * 设置多条
     *
     * @param $params
     */
    public function add_category($params)
    {
        $active_user = get_active_user();
        if (!empty($params) && isset($params['category'])) {
            $data = [];
            foreach ($params['category'] as $key => $item) {
                $data[$key] = [
                    'category_id'      => $item['category_id'],
                    'category_cn_name' => $item['category_cn_name'],
                    'created_uid'      => $active_user->staff_code,
                    'business_line'    => $params['business_line']?? BUSINESS_LINE_FBA
                ];
            }

            $category_id = array_column($data, 'category_id');
            $result      = $this->_ci->m_cate->check_cfg($category_id);
            if (!empty($result)) {
                $category_cn_name = array_column($result, 'category_cn_name');
                $category_cn_name = implode(',', $category_cn_name);
                throw new RuntimeException(sprintf('配置已存在:%s', $category_cn_name));
            }
            $db = $this->_ci->m_cate->getDatabase();
            $db->trans_start();
            $db->insert_batch($this->_ci->m_cate->table, $data);
            $db->trans_complete();
            if ($db->trans_status() === FALSE) {
                return false;
            } else {
                return true;
            }
        } else {
            throw new RuntimeException('参数错误');
        }
    }

    /**
     * 单条删除
     */
    public function del_one($id)
    {

        $db = $this->_ci->m_cate->getDatabase();
        $db->trans_start();
        $db->where('id', $id);
        $db->delete($this->_ci->m_cate->table);
        $row = $db->affected_rows();
        $db->trans_complete();
        if ($db->trans_status() === FALSE) {
            return false;
        } else {
            return $row;
        }
    }
}
