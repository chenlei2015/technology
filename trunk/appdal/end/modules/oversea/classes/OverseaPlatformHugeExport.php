<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';
/**
 * 大文件导出csv
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw
 */

class OverseaPlatformHugeExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_platform_list_model', 'm_oversea_platform_list', false, 'oversea');
        $this->_db = $this->_ci->m_oversea_platform_list->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('oversea/classes/PlatformListTemplate');
        return $this->_ci->PlatformListTemplate->get_default_template_cols();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::set_translator()
     */
    public function set_translator() : AbstractHugeExport
    {
        if ($this->_format_type == EXPORT_VIEW_NATIVE)
        {
            return $this;
        }
        $staff_map = $this->get_all_saleman();
        $this->col_map = [
            'updated_uid'           => $staff_map,
            'approved_uid'          => $staff_map,
            'approve_state'         => OVERSEA_PLATFROM_APPROVAL_STATE,
            'expired'               => FBA_PR_EXPIRED,
            'station_code'          => OVERSEA_STATION_CODE,
            'platform_code'         => INLAND_PLATFORM_CODE,
            'sku_state'             => SKU_STATE,
            'logistics_id'          => LOGISTICS_ATTR,
            'purchase_warehouse_id' => PURCHASE_WAREHOUSE,
            'is_refund_tax'         => REFUND_TAX
        ];
        return $this;
    }

    /**
     *
     * {@inheritDoc}
     * @see HugeExportable::run()
     */
    public function run()
    {
        try
        {
            $this->before();
            set_time_limit(0);
            ini_set('memory_limit', '-1');

            $pick_cols = $this->_cols;
            $col_map   = $this->col_map;
            $file_name = '海外平台需求列表_'.date('YmdHi');

            if ($this->_format_type == EXPORT_VIEW_NATIVE)
            {
                $trans = function($row) use ($pick_cols) {
                    $new = [];
                    $cols = ['gid', 'created_at', 'updated_at', 'approved_at'];
                    foreach ($pick_cols as $col)
                    {
                        if (in_array($col, $cols))
                        {
                            $new[$col] = $row[$col]."\t";
                            continue;
                        }
                        $new[$col] = $row[$col];
                    }
                    return $new;
                };
            }
            else
            {
                $trans = function($row) use ($pick_cols, $col_map) {
                    $new = [];
                    $cols = ['created_at', 'updated_at', 'approved_at'];
                    $user_cols = ['salesman', 'updated_uid', 'approved_uid'];
                    //$zero_number = ['deviate_28_pcs', 'avg_weight_sale_pcs', 'avg_deliver_day', 'z', 'weight_sale_pcs'];
                    foreach ($pick_cols as $col)
                    {
                        //丢弃gid
                        if ($col == 'gid')
                        {
                            continue;
                        }
                        if (in_array($col, $cols))
                        {
                            $new[$col] = empty($row[$col]) ? '' : date('Y-m-d H:i:s', $row[$col])."\t";
                            continue;
                        }
                        if ($col == 'expect_exhaust_date')
                        {
                            $new[$col] = $row[$col]."\t";
                            continue;
                        }
                        //是否精品
                        if ($col == 'is_boutique'){
                            $new[$col] = isset(BOUTIQUE_STATE[$row[$col]])?BOUTIQUE_STATE[$row[$col]]['name']:"-";
                            continue;
                        }
                        //erp系统sku状态
                        if ($col == 'product_status'){
                            $new[$col] = isset(PRODUCT_STATUS_ALL[$row[$col]])?PRODUCT_STATUS_ALL[$row[$col]]['name']:"-";
                            continue;
                        }
                        if (in_array($col, $user_cols))
                        {
                            $new[$col] = empty($row[$col]) ? '' : $col_map[$col][$row[$col]] ?? $row[$col];
                            continue;
                        }
                        if ($col == 'sale_group')
                        {
                            $new[$col] = $col_map[$col][$row[$col]] ?? $row[$col];
                            continue;
                        }
                        if(isset($row[$col])){
                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];
                        }
                    }
                    return $new;
                };
            }
            $this->_ci->load->dbutil();
            $db_query = $this->_db->query_unbuffer($this->data_sql);
            $genertor = $this->_ci->dbutil->csv_from_yeild_result($db_query, $trans, 100);
            $file_path = $this->output($file_name, $genertor);
        }
        catch (\Throwable $e)
        {
            log_message('ERROR', '导出csv出现异常：'.$e->getMessage());
            $file_path = '';
        }
        finally
        {
            $db_query && $db_query->free_result();
            $this->after();
            return $file_path;
        }
    }

    public function __destruct()
    {

    }

}