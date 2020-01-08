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

class FbaHugeExport extends AbstractHugeExport
{
    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_pr_list_model', 'fba_pr_list', false, 'fba');
        $this->_db = $this->_ci->fba_pr_list->getDatabase();
        $this->_ci->load->service('fba/PrService');
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->service('basic/UsercfgProfileService');
//        $no_allow_label = ['15天销量','30天销量','修改信息','审核信息','创建信息'];
        $special_field = [
            '15天销量' =>
                [
                    'col'   => 'sales_15_day',
                    'width' => 18,
                ],
            '30天销量' =>
                [
                    'col'   => 'sales_30_day',
                    'width' => 18,
                ],
        ];
        $template = $this->_ci->usercfgprofileservice->export_temple('fba_pr_list', [], $special_field, []);

//        pr($template);exit;
        return $template;
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
        $fba_group = $this->get_all_fba_group();

        $this->col_map = [
            'salesman'              => $staff_map,
            'sale_group'            => $fba_group,
            'updated_uid'           => $staff_map,
            'approved_uid'          => $staff_map,
            'approve_state'         => APPROVAL_STATE,
            'is_trigger_pr'         => TRIGGER_PR,
            'is_plan_approve'       => NEED_PLAN_APPROVAL,
            'expired'               => FBA_PR_EXPIRED,
            'station_code'          => FBA_STATION_CODE,
            'listing_state'         => LISTING_STATE,
            'sku_state'             => SKU_STATE,
            'logistics_id'          => LOGISTICS_ATTR,
            'purchase_warehouse_id' => PURCHASE_WAREHOUSE,
            'is_refund_tax'         => REFUND_TAX,
            'provider_status'       => PROVIDER_STATUS,
            'is_contraband'         => CONTRABAND_STATE,
            'is_first_sale'         => FBA_FIRST_SALE_STATE,
            'is_accelerate_sale'    => ACCELERATE_SALE_STATE,
            'inventory_health'      => INVENTORY_HEALTH_DESC,
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
            $file_name = 'FBA需求列表_'.date('YmdHi');

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
                $prservice = $this->_ci->prservice;
                $parse_ext_trigger_info = function (&$row) use ($prservice)
                {
                    $sale_info           = $prservice->parse_ext_trigger_info($row['ext_trigger_info']);
                    $row['sales_15_day'] = $sale_info['sales_15_day']??'';
                    $row['sales_30_day'] = $sale_info['sales_30_day']??'';
                };

                $trans = function($row) use ($pick_cols, $col_map, $parse_ext_trigger_info) {
                    $new = [];
                    $cols = ['created_at', 'updated_at', 'approved_at'];
                    $user_cols = ['salesman', 'updated_uid', 'approved_uid'];
                    //$zero_number = ['deviate_28_pcs', 'avg_weight_sale_pcs', 'avg_deliver_day', 'z', 'weight_sale_pcs'];
                    $parse_ext_trigger_info($row);
                    foreach ($pick_cols as $col)
                    {
                        //丢弃gid
                        if ($col == 'gid')
                        {
                            continue;
                        }
                        if ($col == 'accelerate_sale_end_time') {
                            $new[$col] = empty($row[$col]) ? '' : $row[$col]."\t";
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
                        if ($col == 'product_status') {
                            $new[$col] = INLAND_SKU_ALL_STATE[$row['product_status']]['name'] ?? '-';
                            continue;
                        }
                        if ('country_code' == $col)
                        {
                            $new[$col] = empty($row[$col]) ? ( FBA_STATION_CODE[strtolower($row['station_code'])]['name'] ?? '' ) : FBA_STATION_CODE[strtolower($row[$col])]['name'];
                            continue;
                        }
                        if ($col == 'ext_trigger_info') {
                            continue;
                        }

                        if(isset($row[$col])){
                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];
                        } else {//处理有null值
                            $new[$col] = empty($row[$col]) ? '' : $row[$col];
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