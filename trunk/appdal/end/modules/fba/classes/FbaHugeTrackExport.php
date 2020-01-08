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

class FbaHugeTrackExport extends AbstractHugeExport
{
    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_pr_track_list_model', 'fba_track_list', false, 'fba');
        $this->_db = $this->_ci->fba_track_list->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('fba/classes/TrackTemplate');
        return $this->_ci->TrackTemplate->get_default_template_cols();
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
                'salesman' => $staff_map,
                'sale_group' => $fba_group,
                'updated_uid' => $staff_map,
                'approved_uid' => $staff_map,
                'approve_state' => APPROVAL_STATE,
                'is_trigger_pr' => TRIGGER_PR,
                'is_plan_approve' => NEED_PLAN_APPROVAL,
                'expired' => FBA_PR_EXPIRED,
                'station_code' => FBA_STATION_CODE,
                'pur_state' => PUR_STATE,
                'logistics_id' => LOGISTICS_ATTR,
                'purchase_warehouse_id' => PURCHASE_WAREHOUSE,
                'is_refund_tax' => REFUND_TAX,
                'push_status_logistics' => PUSH_STATUS_LOGISTICS
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
            $file_name = 'FBA需求跟踪列表_'.date('YmdHi');

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
                    $cols = ['created_at', 'updated_at', 'approved_at','push_time_logistics'];
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
            $db_query->free_result();
            $this->after();
            return $file_path;
        }
    }

    public function __destruct()
    {

    }

}