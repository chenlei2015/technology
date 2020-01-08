<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 13:48
 */
require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';

class OverseaHugeLogisticsExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_logistics_list_model', 'm_logistics', false, 'oversea');
        $this->_db = $this->_ci->m_logistics->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('oversea/classes/OverseaLogisticsTemplate');
        $template = $this->_ci->OverseaLogisticsTemplate->get_default_template_cols();
        $template['请勿改动此标记'] = ['col' => 'id'];
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
            'approve_state' => CHECK_STATE,
            'station_code'  => OVERSEA_STATION_CODE,
            'logistics_id'  => LOGISTICS_ATTR,
            'sku_state'     => SKU_STATE,
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
            $file_name = '海外_物流属性配置列表_'.date('YmdHi');
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
                $trans = function($row) use ($pick_cols, $col_map) {
                    $new = [];
                    $special = ['sku','sku_name','remark'];
                    $cols = ['created_at', 'updated_at', 'approve_at'];
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
                        if ($col == 'product_status')
                        {
                            $new[$col] = isset(PRODUCT_STATUS_ALL[$row[$col]])?PRODUCT_STATUS_ALL[$row[$col]]['name']:'';
                            continue;
                        }
                        if ($col == 'is_import')
                        {
                            $new[$col] = isset(IS_IMPORT[$row[$col]])?IS_IMPORT[$row[$col]]['name']:'';
                            continue;
                        }
                        if ($col == 'mix_hair')
                        {
                            $new[$col] = isset(MIX_HAIR_STATE[$row[$col]])?MIX_HAIR_STATE[$row[$col]]['name']:'';
                            continue;
                        }
                        if ($col == 'infringement_state')
                        {
                            $new[$col] = isset(INFRINGEMENT_STATE[$row[$col]])?INFRINGEMENT_STATE[$row[$col]]['name']:'';
                            continue;
                        }
                        if ($col == 'contraband_state')
                        {
                            $new[$col] = isset(CONTRABAND_STATE[$row[$col]])?CONTRABAND_STATE[$row[$col]]['name']:'';
                            continue;
                        }
                        if ($col == 'listing_state')
                        {
                            $new[$col] = isset(LISTING_STATE[$row[$col]])?LISTING_STATE[$row[$col]]['name']:'';
                            continue;
                        }
                        if(in_array($col, $special)){
                            $row[$col] = str_replace(array("\r\n", "\r", "\n"), '',$row[$col]??'');//将换行
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
            $this->after();
            return $file_path;
        }
    }

    public function __destruct()
    {

    }

}