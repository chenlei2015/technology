<?php

/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/3/2
 * Time: 17:48
 */
class Category_cfg_model extends MY_Model
{
    public function __construct()
    {
        $this->table = 'yibai_category_cfg';
        parent::__construct();
    }

    public function list()
    {
        return $this->db->select('*')
            ->get($this->table)
            ->result_array();
    }

    /**
     * 检查是否已经配置
     */
    public function check_cfg($category_id)
    {
        $result = $this->db->select('*')->where_in('category_id', $category_id)->get($this->table)->result_array();

        return $result;
    }

    public function get_all_category()
    {
        $this->yibai_product = $this->load->database('yibai_product', true);
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

    public function get_restrict_category($business)
    {
        $result = $this->_db->select('category_id, category_cn_name')
        ->from($this->table_name)
        ->where('business_line', $business)
        ->get()
        ->result_array();
        return array_column($result, 'category_cn_name', 'category_id');
    }

    /**
     * 设置多条
     *
     * @param $params
     */
    public function add_category($params)
    {
        if (!empty($params) && isset($params['category'])) {
            $data = [];
            foreach ($params['category'] as $key => $item) {
                $data[$key] = [
                    'category_id'      => $item['category_id'],
                    'category_cn_name' => $item['category_cn_name'],
                    'business_line'    => $item['business_line']
                ];
            }
            $this->db->trans_start();
            $this->db->insert_batch($this->table, $data);
            $this->db->trans_complete();
            if ($this->db->trans_status() === FALSE) {
                return false;
            } else {
                return true;
            }
        } else {
            throw new RuntimeException('参数错误');
        }
    }
}