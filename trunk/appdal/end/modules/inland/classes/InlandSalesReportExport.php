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

class InlandSalesReportExport extends AbstractHugeExport
{
    private $_db;

    private $_cb;

    private $addClos;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_sales_report_model', 'salesReportModel', false, 'inland');
        $this->_db = $this->_ci->salesReportModel->getDatabase();
    }

    public function addTemplateCols(array $addClos)
    {
        foreach ($addClos as $v) {
            $this->addClos[$v."\t"] = [
                'col' => $v,
            ];
        }
        return $this;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('inland/classes/InlandSalesReportTemplate');
        $temps = $this->_ci->InlandSalesReportTemplate->get_default_template_cols();
        if ($this->addClos) {
            $temps = array_merge($temps, $this->addClos);
        }
        return $temps;
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
                'sku_state'      => INLAND_SKU_ALL_STATE,
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
            $pick_cols = $this->_cols;
            $col_map = $this->col_map;

            if ($this->_format_type == EXPORT_VIEW_NATIVE)
            {
                $trans = function($row) use ($pick_cols) {
                    $new = [];
                    $cols = ['gid', 'created_at'/*, 'updated_at', 'approved_at'*/];
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
                    $cols = ['created_at'/*, 'updated_at', 'approved_at'*/];
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
                            $new[$col] = empty($row[$col]) ? '' : $row[$col]."\t";
                            continue;
                        }
                        if ($col == 'expect_exhaust_date')
                        {
                            $new[$col] = $row[$col]."\t";
                            continue;
                        }
                        if(isset($row[$col])){                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];                        }
                    }
                    return $new;
                };
            }

            $file_name = '国内销量列表_'.date('Y_m_d_H_i_s');
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