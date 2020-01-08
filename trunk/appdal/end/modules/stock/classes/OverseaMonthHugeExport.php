<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/15
 * Time: 16:12
 */

require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';

class OverseaMonthHugeExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Stock_fba_condition_model', 'm_condition', false, 'stock');
        $this->_db = $this->_ci->m_condition->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('stock/classes/OverseaMonthTemplate');
        return $this->_ci->OverseaMonthTemplate->get_default_template_cols();
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
            'listing_state' => SKU_STATE,
            'station_code'  => OVERSEA_STATION_CODE,
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
                    $cols = ['created_at', 'updated_at','stockin_date','qt_date','freight_date','exchange_signed_date'];
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
                        if ($col == 'earliest_exhaust_date')
                        {
                            $new[$col] = $row[$col]."\t";
                            continue;
                        }
                        if(isset($row[$col])){                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];                        }
                    }
                    return $new;
                };
            }
            $file_name = '海外库存状况列表_月_'.date('Y_m_d_H_i_s');

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