<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';
/**
 * 大文件导出csv
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2018-12-28
 * @link
 * @throw
 */

class InlandHugeStockCfgExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_sku_cfg_model', 'm_sku_cfg_model', false, 'inland');
        $this->_db = $this->_ci->m_sku_cfg_model->getDatabase();
    }

    /**
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('inland/classes/StockCfgTemplate');
        $template = $this->_ci->StockCfgTemplate->get_default_template_cols();
        $template['gid'] = ['col' => 'gid'];
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
            'rule_type'             => RULE_TYPE,
            'state'                 =>CHECK_STATE,
            'stock_way'             => STOCK_UP_TYPE,
            'purchase_warehouse_id' => PURCHASE_WAREHOUSE,
            'is_refund_tax'         => REFUND_TAX,
            'sku_state'             => SKU_STATE,
            'provider_status'       =>PROVIDER_STATUS,
            'quality_goods'         =>INLAND_QUALITY_GOODS,
        ];
        $this->_ci->load->model('Inland_global_rule_cfg_model', 'cfgModel', false, 'inland');
        //将全局表所有信息查出来
        $g_result = $this->_ci->cfgModel->get_cfg();
        $this->global_cfg = $g_result;
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
                $trans = function($row) use ($pick_cols, $col_map,$global_cfg) {
                    $new = [];
                    $cols = ['created_at', 'updated_at', 'approved_at','deved_time','published_time'];
                    $special = ['sku','sku_name','path_name_first','remark'];
                    foreach ($pick_cols as $col){
                        if ($row[$col] == null || $row[$col] == NULL) {
                            $row[$col] = '';
                        }
                        if ($row['rule_type'] == 2) {
                            $row['bs'] = $global_cfg['bs']??'';
                            $row['sp'] = $global_cfg['sp']??'';
                            $row['shipment_time'] = $global_cfg['shipment_time']??'';
                            $row['sc'] = $global_cfg['sc']??'';
                            $row['first_lt'] = $global_cfg['first_lt']??'';
                            $row['sz'] = $global_cfg['sz']??'';
                        }
                        //退款率
                        if ($col == 'refund_rate'){
                            $new[$col] = $row[$col] . '%';
                            continue;
                        }

                        if (in_array($col, $cols))
                        {
                            $new[$col] = empty($row[$col]) || $row[$col]=='0000-00-00 00:00:00'? '' : $row[$col]."\t";
                            continue;
                        }
                        if ($col == "product_status"){
                            $new[$col] = isset(PRODUCT_STATUS_ALL[$row[$col]])?PRODUCT_STATUS_ALL[$row[$col]]['name']:"--";
                            continue;
                        }
                        if ($col == "purchase_warehouse_id"){
                            $new[$col] = isset(PURCHASE_WAREHOUSE[$row[$col]])?PURCHASE_WAREHOUSE[$row[$col]]['name']:"--";
                            continue;
                        }

                        if($col == 'provider_status'){
                            $new[$col] = empty($row[$col]) ? '-' : $col_map[$col][$row[$col]]['name'] ?? '-';
                            continue;
                        }

                        if(in_array($col, $special)){
                            $row[$col] = str_replace(array("\r\n", "\r", "\n"), '', $row[$col]);//将换行
                            $row[$col] = str_replace(',',"，",$row[$col]);//将英文逗号转成中文逗号
                        }
                        if(isset($row[$col])){
                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];
                        }
                    }
                    return $new;
                };
            }
            $file_name = '国内关系配置表'.date('YmdHi');
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