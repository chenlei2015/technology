<?php

/**
 * 处理导出， 目前支持xls格式
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 16：21
 * @link
 */
class FbaExportService
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
     * 编码
     * @var unknown
     */
    private $_charset;

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
        $this->_template  = $post['template'] ?? __CLASS__;
        $this->_data_type = $post['data_type'] ?? VIEW_BROWSER;
        $this->_charset   = $post['charset'] ?? 'UTF-8';

        if (!isset($post['gid']))
        {
            //默认选择当前筛选
            $this->_gids = '';
        }
        else {
            $this->_gids = $post['gid'];
            $this->_ci->load->helper('fba_helper');
        }

        $this->_profile = $post['profile'] ?? '';
        if ($this->_profile == '')
        {
            //取默认
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
        $this->_ci->load->service('fba/PrListService');
        return $this->_ci->prlistservice->quick_export($this->_gids, $this->_profile, $this->_format_type, $this->_data_type, $this->_charset);
    }

    /**
     * @version v1.2.2 导出配置供重新计算需求列表
     *
     * @param string $file_type
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return downloadpath
     */
    public function activity_export($file_type = 'csv')
    {
        $this->_file_type = strtolower($file_type);
        if (!in_array($this->_file_type, $this->_allow_file_types))
        {
            throw new \InvalidArgumentException(sprintf('目前暂不支持这种格式'), 3001);
        }

        $data_type = VIEW_FILE;
        $this->_ci->load->model('Fba_pr_list_model', 'fba_pr_list', false, 'fba');

        $db = $this->_ci->fba_pr_list->getDatabase();
        $fba_table = $this->_ci->fba_pr_list->getTable();

        $this->_ci->load->dbutil();

        //$distinct_col = "DISTINCT CONCAT_WS('', seller_sku, fnsku, asin, account_id, account_num) as join_str";
        $select_cols  = ["md5(CONCAT_WS('',sku,seller_sku,fnsku,asin,account_num)) as tag", 'sale_group', 'account_name', 'salesman', 'sku', 'seller_sku', 'fnsku', 'asin', 'account_id', 'account_num', 'station_code'];

        if ($this->_gids != '')
        {
            $gids_arr = explode(',', $this->_gids);
            $total = count($gids_arr);
            $quick_sql = $db->from($this->_ci->fba_pr_list->getTable())->select(implode(',', $select_cols))->where_in('gid', $gids_arr)->get_compiled_select('', false);
            $db->reset_query();
        }
        else
        {
            //直接走导出sql
            /*$start_time = strtotime(date('Y-m-d'));
            $end_time = $start_time + 86400;
            $quick_sql = sprintf(
                'select %s from %s where created_at > %d and created_at < %d and is_boutique in (%d,%d) and listing_state = %d and approve_state = %d ',
                implode(',', $this->_profile), $fba_table,
                $start_time, $end_time,
                BOUTIQUE_YES, BOUTIQUE_NO,
                LISTING_STATE_OPERATING,
                APPROVAL_STATE_FIRST
                );*/

            //pr($quick_sql);exit;
            //走页面搜索
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $quick_sql = $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::FBA_PR_LIST_SEARCH_EXPORT)->get();
            $total = substr($quick_sql, 0, 10);
            $quick_sql = substr($quick_sql, 10);

            if (!$quick_sql)
            {
                throw new \RuntimeException(sprintf('请选择要导出的资源'));
            }

            //截断order by
            $quick_sql = substr($quick_sql, 0, stripos($quick_sql, ' ORDER BY '));
            //替换sql
            $quick_sql = 'select '.implode(',', $select_cols).substr($quick_sql, stripos($quick_sql, ' from '));

            if ($total > MAX_BROWSE_LIMIT)
            {
                //强制转文件模式
                $data_type = VIEW_FILE;
            }
            else
            {
                if ($data_type == VIEW_AUTO)
                {
                    $data_type = VIEW_BROWSER;
                }
            }
        }

        $append_cols = ['tag', 'is_pan', 'activity_name', 'amount', 'execute_purcharse_time', 'activity_start_time', 'activity_end_time'];

        $this->_ci->load->classes('fba/classes/FbaActivityConfigExport');
        $this->_ci->FbaActivityConfigExport
        ->set_format_type($this->_format_type)
        ->set_data_type($data_type)
        ->set_out_charset($this->_charset)
        ->set_title_map(array_merge($select_cols,$append_cols))
        ->set_translator()
        ->set_data_sql($quick_sql)
        ->set_export_nums($total);
        return $this->_ci->FbaActivityConfigExport->run();

    }


    public function __desctruct()
    {
        $this->_data = NULL;
        unset($this->_data);
    }
}