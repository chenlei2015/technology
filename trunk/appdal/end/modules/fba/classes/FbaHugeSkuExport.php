<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 13:48
 */
require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';

class FbaHugeSkuExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_sku_cfg_model', 'm_sku_cfg', false, 'fba');
        $this->_db = $this->_ci->m_main->getDatabase();
    }

    public function join_stock_cfg_data()
    {
        //全局的属性从全局配置表获取,自定义在part表获取
        $result = $this->joinGlobal();
        //供货周期 无法从lead_time匹配到的sku取default_arrival_time
        $result = $this->joinLeadTime($result);
    }

    /**
     * 拼接未采购供货周期
     */
    public function joinLeadTime($result)
    {
        $this->db->select('default_arrival_time')->from('yibai_fba_lead_time')->limit(1);
        $lead_time = $this->db->get()->row_array();
        $lead_time = $lead_time['default_arrival_time']??'';
        foreach ($result as $key => $value) {
            if (empty($value['lt'])) {
                $result[$key]['lt'] = $lead_time;
            }
        }

        return $result;
    }


    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('fba/classes/FbaStockListTemplate');
        $template = $this->_ci->FbaStockListTemplate->get_default_template_cols();

        return $template;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::set_translator()
     */
    public function set_translator(): AbstractHugeExport
    {
        if ($this->_format_type == EXPORT_VIEW_NATIVE) {
            return $this;
        }
        $this->col_map = [
            'state'                 => CHECK_STATE,
            'rule_type'             => RULE_TYPE,
            'station_code'          => FBA_STATION_CODE,
            'purchase_warehouse_id' => PURCHASE_WAREHOUSE,
            'is_refund_tax'         => REFUND_TAX,
            'is_boutique'           => BOUTIQUE_STATE,
            'is_contraband'         => CONTRABAND_STATE,
            'sku_state'             => SKU_STATE,
            'product_status'        => INLAND_SKU_ALL_STATE,
            'provider_status'       => PROVIDER_STATUS,
        ];

        $this->_ci->load->model('Fba_sku_cfg_model', 'm_sku_cfg', false, 'fba');
//        $this->global_cfg = $this->_ci->m_fba_sku_cfg->get_all_station();//全局配置
//        $this->lead_time_map = $this->_ci->m_sku_cfg->lead_time_map();//lt供货周期

        return $this;
    }

    /**
     *
     * {@inheritDoc}
     * @see HugeExportable::run()
     */
    public function run()
    {
        try {
            $this->before();
            set_time_limit(0);
            $pick_cols = $this->_cols;
            $col_map   = $this->col_map;
//            $global_cfg = $this->global_cfg;
//            $lead_time_map = $this->lead_time_map;
            $file_name     = 'ERPSKU属性配置表_' . date('YmdHi');

            if ($this->_format_type == EXPORT_VIEW_NATIVE) {
                $trans = function ($row) use ($pick_cols) {
                    $new = [];
                    foreach ($pick_cols as $col) {
                        if ($col == 'gid') {
                            $new[$col] = $row[$col] . "\t";
                            continue;
                        }
                        $new[$col] = $row[$col];
                    }

                    return $new;
                };
            } else {
                $trans = function ($row) use ($pick_cols, $col_map) {

                    $new     = [];
                    $cols    = ['created_at', 'updated_at', 'approved_at'];
                    $special = ['sku', 'remark'];
                    foreach ($pick_cols as $col) {
                        if (in_array($col, $cols)) {
                            $new[$col] = empty($row[$col]) || $row[$col] == '0000-00-00 00:00:00' ? '' : $row[$col] . "\t";
                            continue;
                        }
                        /*                      if ($col == 'lt') {
                                                  $new[$col] = $lead_time_map[$row['sku']]??'15';
                                              }*/
                        /*                        if($col == 'rule_type'){
                                                    if($row[$col] == 2){
                                                        $row['as_up'] = $global_cfg[$row['station_code']]['as_up'];
                                                        $row['ls_shipping_full'] = $global_cfg[$row['station_code']]['ls_shipping_full'];
                                                        $row['ls_shipping_bulk'] = $global_cfg[$row['station_code']]['ls_shipping_bulk'];
                                                        $row['ls_trains_full'] = $global_cfg[$row['station_code']]['ls_trains_full'];
                                                        $row['ls_trains_bulk'] = $global_cfg[$row['station_code']]['ls_trains_bulk'];
                                                        $row['ls_air'] = $global_cfg[$row['station_code']]['ls_air'];
                                                        $row['ls_red'] = $global_cfg[$row['station_code']]['ls_red'];
                                                        $row['ls_blue'] = $global_cfg[$row['station_code']]['ls_blue'];
                                                        $row['pt_shipping_full'] = $global_cfg[$row['station_code']]['pt_shipping_full'];
                                                        $row['pt_shipping_bulk'] = $global_cfg[$row['station_code']]['pt_shipping_bulk'];
                                                        $row['pt_trains_full'] = $global_cfg[$row['station_code']]['pt_trains_full'];
                                                        $row['pt_trains_bulk'] = $global_cfg[$row['station_code']]['pt_trains_bulk'];
                                                        $row['pt_air'] = $global_cfg[$row['station_code']]['pt_air'];
                                                        $row['pt_red'] = $global_cfg[$row['station_code']]['pt_red'];
                                                        $row['pt_blue'] = $global_cfg[$row['station_code']]['pt_blue'];
                                                        $row['bs'] = $global_cfg[$row['station_code']]['bs'];
                                                        $row['sc'] = $global_cfg[$row['station_code']]['sc'];
                                                        $row['sp'] = $global_cfg[$row['station_code']]['sp'];
                                                        $row['sz'] = $global_cfg[$row['station_code']]['sz'];
                                                    }
                                                }*/
//                        if(empty($row['lt'])){
//                            $row['lt'] = $default_lead_time;
//                        }

                        if (in_array($col, $special)) {
                            $row[$col] = str_replace(["\r\n", "\r", "\n"], '', $row[$col]);//将换行
                            $row[$col] = str_replace(',', "，", $row[$col]);//将英文逗号转成中文逗号
                        }
                        if (isset($row[$col])) {
                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];
                        }

                    }

                    return $new;
                };
            }
            $this->_ci->load->dbutil();
            $db_query  = $this->_db->query_unbuffer($this->data_sql);
            $genertor  = $this->_ci->dbutil->csv_from_yeild_result($db_query, $trans, 100);
            $file_path = $this->output($file_name, $genertor);
        } catch (\Throwable $e) {
            log_message('ERROR', '导出csv出现异常：' . $e->getMessage());
            $file_path = '';
        } finally {
            $db_query && $db_query->free_result();
            $this->after();
            return $file_path;
        }
    }

    public function __destruct()
    {

    }

}