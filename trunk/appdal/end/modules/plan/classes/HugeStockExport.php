<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/15
 * Time: 16:12
 */

require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';

class HugeStockExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Plan_purchase_list_model', 'purchase_pr_list', false, 'plan');
        $this->_db = $this->_ci->purchase_pr_list->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('plan/classes/StockTemplate');
        return $this->_ci->StockTemplate->get_default_template_cols();
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
        $this->col_map = [
            'station_code'          => FBA_STATION_CODE,
            'purchase_warehouse_id' => PURCHASE_WAREHOUSE,
            'is_refund_tax'         => REFUND_TAX,
            'state'                 => PUR_STATE,
            'is_pushed'             => PUR_DATA_STATE,
            'bussiness_line'        => BUSSINESS_LINE,
            'product_status'        => PLAN_PRODUCT_STATUS,
            'is_boutique'           => BOUTIQUE_STATE,
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
            $file_name = '备货列表_'.date('YmdHi');

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
                    $cols = ['created_at', 'updated_at'];
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
                        //计划系统sku状态
                        if ($col == 'sku_state')
                        {
                            $new[$col] = isset(SKU_STATE[$row[$col]])?SKU_STATE[$row[$col]]['name']:"--";
                            continue;
                        }
                        //erp系统sku状态
                        if ($col == 'product_status')
                        {
                            $new[$col] = isset(PRODUCT_STATUS_ALL[$row[$col]])?PRODUCT_STATUS_ALL[$row[$col]]['name']:"--";
                            continue;
                        }
                        if ($col == 'earliest_exhaust_date')
                        {
                            $new[$col] = $row[$col]."\t";
                            continue;
                        }
                        if (isset($row[$col])) {
                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];
                        } else {//处理调拨单号字段有null值
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