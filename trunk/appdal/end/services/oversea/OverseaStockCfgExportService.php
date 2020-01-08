<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/7
 * Time: 19:15
 */

class OverseaStockCfgExportService
{
    /**
     * client
     *
     * @var array
     */
    public static $s_encode = ['UTF-8'] ;

    private $_entity;

    private $_ci;

    /**
     * 模板
     * @var unknown
     */
    private $_template;

    /**
     * 文件类型
     * @var unknown
     */
    private $_file_type;

    /**
     * 前端展示类型
     *
     * @var unknown
     */
    private $_data_type;

    /**
     * 指定的文件类型
     *
     * @var array
     */
    private $_allow_file_types = ['csv', 'xls', 'xlsx', 'pdf'];

    private $_gids;

    private $_data;

    /**
     * 导出选择的字段
     * @var unknown
     */
    private $_profile;

    /**
     * 导出格式  1 原生  2 可视化
     * @var unknown
     */
    private $_format_type;

    /**
     *
     * @param unknown $template
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
    }

    public function setTemplate($post)
    {
        $this->_template = $post['template'] ?? __CLASS__;
        $this->_data_type = $post['data_type'] ?? VIEW_BROWSER;
        $this->_charset   = $post['charset'] ?? 'UTF-8';
        //csv
        $this->_data_type = $post['data_type'] ?? 3;
        if (!isset($post['gid']))
        {
            //默认选择当前筛选
            $this->_gids = '';
        }
        else {
            $this->_gids = $post['gid'];
        }
        $this->_profile = $post['profile'] ?? '';
        if ($this->_profile == '')
        {
            $this->_profile = '*';
        }
        else
        {
            $this->_profile = is_array($this->_profile) ? $this->_profile : explode(',', $this->_profile);
        }
        $this->_format_type = $post['format_type'] ?? EXPORT_VIEW_PRETTY;
    }

    /**
     * 导出
     *
     * @param unknown $post
     */
    public function export($file_type = 'csv')
    {
        $this->_file_type = strtolower($file_type);
        if (!in_array($this->_file_type, $this->_allow_file_types))
        {
            throw new \InvalidArgumentException(sprintf('目前暂不支持这种格式'), 3001);
        }
        return $this->quick_export($this->_gids, $this->_profile, $this->_format_type, $this->_data_type, $this->_charset);
    }


    /**
     *
     * @param string $gids 页面选择的gids，为空表示从搜索条件获取
     * @param string $profile 用户选择导出的列
     * @param string $format_type 导出csv的格式， 可读还是用于修改的原生字段
     * @throws \RuntimeException
     * @throws \OverflowException
     * @return unknown
     */
    public function quick_export($gids = '', $profile = '*', $format_type = EXPORT_VIEW_PRETTY, $data_type = VIEW_BROWSER, $charset = 'UTF-8')
    {
        $this->_ci->load->model('Oversea_sku_cfg_main_model', 'm_main', false, 'oversea');
        $db = $this->_ci->m_main->getDatabase();
        $this->_ci->load->dbutil();

        if ($gids != '')
        {
            $gids_arr = explode(',', $gids);
            $gids_arr = explode(',', $gids);
//            $quick_sql = $db->from($this->_ci->m_main->getTable())->where_in('gid', $gids_arr)->get_compiled_select('', false);
            $clone_db = clone $db;

            $clone_db->select('a.*,b.as_up,b.ls_shipping_full,b.ls_shipping_bulk,b.ls_trains_full,b.ls_trains_bulk,b.ls_land,b.ls_air,b.ls_red,b.ls_blue,
                b.pt_shipping_full,b.pt_shipping_bulk,b.pt_trains_full,b.pt_trains_bulk,b.pt_land,b.pt_air,b.pt_red,b.pt_blue,b.original_min_start_amount,b.min_start_amount,
                b.bs,b.sc,b.sp,b.sz,lt.lead_time as lt,b.updated_at,b.updated_uid,b.updated_zh_name,b.state,b.approved_at,b.approved_uid,b.approved_zh_name,b.remark');
            $clone_db->where_in('a.gid', $gids_arr);
            $clone_db->from('yibai_oversea_sku_cfg_part b');
            $clone_db->join('yibai_oversea_sku_cfg_main a', 'a.gid=b.gid', 'LEFT');
            $clone_db->join('yibai_oversea_lead_time lt', 'a.sku=lt.sku', 'LEFT');
            $clone_db->order_by('a.created_at', 'DESC');
            $clone_db->order_by('a.gid', 'DESC');
            $quick_sql = $clone_db->get_compiled_select();

            //pr($db->from($this->_ci->m_main->getTable())->where_in('gid', $gids_arr)->get()->result_array());
            //echo $quick_sql;exit;

        }
        else
        {
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT)->get();

            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);

            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }

            if ($total > MAX_EXCEL_LIMIT)
            {
                throw new \OverflowException(sprintf('最多只能导出%d条数据，请筛选相关条件导出；如需导出30W条以上的数据，请找相关负责人', MAX_EXCEL_LIMIT), 500);
                $quick_sql .= ' limit '.MAX_EXCEL_LIMIT;
            }
        }
        $this->_ci->load->classes('oversea/classes/OverseaHugeStockExport');
        $this->_ci->OverseaHugeStockExport
            ->set_format_type($format_type)
            ->set_data_type($data_type)
            ->set_out_charset($charset)
            ->set_title_map($profile)
            ->set_translator()
            ->set_data_sql($quick_sql)
            ->set_export_nums($total);
        return $this->_ci->OverseaHugeStockExport->run();

    }



    public function __desctruct()
    {
    }
}
