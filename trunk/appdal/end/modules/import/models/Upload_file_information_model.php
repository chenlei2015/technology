<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/10
 * Time: 14:11
 */
class Upload_file_information_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }
    public function all($params)
    {
        $offset = (($params['offset'] > 0 ? $params['offset'] : 1) - 1) * $params['limit'];
        $limit = $params['limit'];
        $this->db->select('*')->from('yibai_upload_file_information');
        $db = clone $this->db;
        $total = $db->count_all_results();//获取总条数
        unset($db);
        $this->db->limit($limit);
        $this->db->offset($offset);
        $this->db->order_by('created_at', 'DESC');
        $data['data_list'] =  $this->db->get()->result_array();
        $data['data_page'] = array(
            'limit' => (float)$params['limit'],
            'offset' => (float)$params['offset'],
            'total' => (float)$total
        );
        return $data;
    }

    public function insert($data)
    {
        return $this->db->insert('yibai_upload_file_information',$data);
    }
}
