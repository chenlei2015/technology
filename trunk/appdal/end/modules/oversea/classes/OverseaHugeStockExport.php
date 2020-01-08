<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 13:48
 */
require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';

class OverseaHugeStockExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_sku_cfg_main_model', 'm_main', false, 'oversea');
        $this->_db = $this->_ci->m_main->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('oversea/classes/OverseaStockListTemplate');
        $template = $this->_ci->OverseaStockListTemplate->get_default_template_cols();

//        $template['gid'] = ['col' => 'gid'];
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
        $this->col_map = [
            'state'                 => CHECK_STATE,
            'rule_type'             => RULE_TYPE,
            'station_code'          => OVERSEA_STATION_CODE,
            'purchase_warehouse_id' => PURCHASE_WAREHOUSE,
            'is_refund_tax'         => REFUND_TAX,
            'sku_state'             => SKU_STATE,
        ];

        $this->global_cfg = $this->_ci->m_main->get_all_station();//全局配置
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
            $global_cfg = $this->global_cfg;
            $file_name = '海外_备货关系配置表_'.date('YmdHi');

            if ($this->_format_type == EXPORT_VIEW_NATIVE)
            {
                $trans = function($row) use ($pick_cols) {
                    $new = [];
                    foreach ($pick_cols as $col)
                    {
                        if ($col == 'gid')
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
                $trans = function($row) use ($pick_cols, $col_map,$global_cfg) {
                    $new = [];
                    $cols = ['created_at', 'updated_at', 'approved_at'];
                    $special = ['sku','remark'];
                    foreach ($pick_cols as $col)
                    {
                        if (in_array($col, $cols))
                        {
                            $new[$col] = empty($row[$col]) || $row[$col]=='0000-00-00 00:00:00'? '' : $row[$col]."\t";
                            continue;
                        }
                        if ($col == 'gid')
                        {
                            $new[$col] = $row[$col]."\t";
                            continue;
                        }
                        if(empty($row['lt']))
                        {
                            $new[$col] = 42;
                        }
                        //是否退税
                        if ($col == 'is_refund_tax'){
                            $new[$col] = isset(REFUND_TAX[$row[$col]])?REFUND_TAX[$row[$col]]['name']:"--";
                            continue;
                        }
                        //采购仓库
                        if ($col == 'purchase_warehouse_id'){
                            $new[$col] = isset(PURCHASE_WAREHOUSE[$row[$col]])?PURCHASE_WAREHOUSE[$row[$col]]['name']:"--";
                            continue;
                        }
                        //计划系统sku状态
                        if ($col == 'sku_state'){
                            $new[$col] = isset(SKU_STATE[$row[$col]])?SKU_STATE[$row[$col]]['name']:"--";
                            continue;
                        }
                        //erp系统sku状态
                        if ($col == 'product_status'){
                            $new[$col] = isset(PRODUCT_STATUS_ALL[$row[$col]])?PRODUCT_STATUS_ALL[$row[$col]]['name']:"--";
                            continue;
                        }
                        if($col == 'rule_type'){
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
                        }

                        if(in_array($col, $special)){
                            $row[$col] = str_replace(array("\r\n", "\r", "\n"), '', $row[$col]??'');//将换行
                            $row[$col] = str_replace(',',"，",$row[$col]??'');//将英文逗号转成中文逗号
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
            return $file_path;
        }
    }

    public function __destruct()
    {

    }

}