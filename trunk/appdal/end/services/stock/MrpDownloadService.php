<?php

/**
 * MRP 源数据下载
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-07
 * @link
 */
class MrpDownloadService
{
    public static $s_system_log_name = 'MRP-DOWNLOAD';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Mrp_source_from_model', 'm_mrp_source_from', false, 'stock');
        return $this;
    }

    /**
     * 打包成功后，添加两条记录， 目前打包在一起，不区分
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_fba_oversea_download($params)
    {
        $date = date('Y-m-d');

        $result = $this->query($date);
        if (count($result) > 0)
        {
            return count($result);
        }
        $batch_params = [
             0 => [
                     'created_date' => $date,
                     'bussiness_line' => BUSSINESS_FBA,
                     'data_type' => MRP_DOWNLOAD_TYPE_ALL,
                     'download_url' => $params['url'],
             ],
            1 => [
                    'created_date' => $date,
                    'bussiness_line' => BUSSINESS_OVERSEA,
                    'data_type' => MRP_DOWNLOAD_TYPE_ALL,
                    'download_url' => $params['url'],
            ],
        ];
        $db = $this->_ci->m_mrp_source_from->getDatabase();
        return $db->insert_batch($this->_ci->m_mrp_source_from->getTable(), $batch_params);
    }

    /**
     * 单条增加
     *
     * @param unknown $params
     * @return number|unknown
     */
    public function add_bussiness_download($params)
    {
        $date = $params['date'] ?? date('Y-m-d');
        $type = $params['data_type'] ?? MRP_DOWNLOAD_TYPE_ALL;
        $buss = $params['bussiness_line'];

        $result = $this->query($date, $options = ['bussiness_line' => $buss, 'data_type' => $type]);
        if (count($result) > 0)
        {
            return count($result);
        }

        $insert = [
                'created_date' => $date,
                'bussiness_line' => $buss,
                'data_type' => $type,
                'download_url' => $params['url'],
        ];

        $db = $this->_ci->m_mrp_source_from->getDatabase();
        return $db->insert($this->_ci->m_mrp_source_from->getTable(), $insert);
    }

    /**
     * 查询
     *
     * @param unknown $date 日期
     * @param array $options 其他参数
     * @return array
     */
    public function query($date, $options = [])
    {
        $db = $this->_ci->m_mrp_source_from->getDatabase();
        $query = $db->from($this->_ci->m_mrp_source_from->getTable())->where('created_date', $date)->where('state', GLOBAL_YES);
        if (!empty($options))
        {
            $require_cols = ['bussiness_line', 'data_type'];
            $options = array_intersect_key($options, array_flip($require_cols));

            foreach ($options as $col => $val)
            {
                $query->where($col, $val);
            }
        }
        return $query->get()->result_array();
    }
}
