<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 13:48
 */
require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';

class FbaHugeLogisticsExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_logistics_list_model', 'm_logistics', false, 'fba');
        $this->_db = $this->_ci->m_logistics->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('fba/classes/FbaLogisticsTemplate');
        $template = $this->_ci->FbaLogisticsTemplate->get_default_template_cols();
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
            'approve_state'         => CHECK_STATE,
            'station_code'          => FBA_STATION_CODE,
            'site'          => FBA_STATION_CODE,
            'logistics_id'          => LOGISTICS_ATTR,
            'listing_state'         => LISTING_STATE,
            'pan_eu'         => IS_PAN_EU,
            'rule_type'             => RULE_TYPE,
            'purchase_warehouse_id' => PURCHASE_WAREHOUSE,
            'sku_state'             => SKU_STATE,
            'product_status'        => INLAND_SKU_ALL_STATE,
            'is_first_sale'         => FBA_FIRST_SALE_STATE
        ];
        $this->_ci->load->model('Global_rule_cfg_model', 'm_global_cfg', false, 'fba');

        $this->global_cfg = $this->_ci->m_logistics->global_cfg();
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
            $pick_cols  = $this->_cols;
            $col_map    = $this->col_map;
            $global_cfg = $this->global_cfg;
            $file_name  = 'SELLERSKU属性配置表_'.date('YmdHi');

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
                $trans = function ($row) use ($pick_cols, $col_map, $global_cfg) {
                    $new     = [];
                    $special = ['seller_sku', 'sku', 'sku_name', 'remark'];
                    $cols    = ['created_at', 'updated_at', 'approve_at'];
                    foreach ($pick_cols as $col)
                    {
                        if ($col == 'rule_type' && $row['rule_type'] == RULE_TYPE_GLOBAL && isset($global_cfg[$row['station_code']])) {
                            if ($row['logistics_id'] == LOGISTICS_ATTR_SHIPPING_BULK) {//海运散货
                                $row['ls'] = $global_cfg[$row['station_code']]['ls_shipping_bulk'];
                                $row['pt'] = $global_cfg[$row['station_code']]['pt_shipping_bulk'];

                            } elseif ($row['logistics_id'] == LOGISTICS_ATTR_AIR) {//空运
                                $row['ls'] = $global_cfg[$row['station_code']]['ls_air'];
                                $row['pt'] = $global_cfg[$row['station_code']]['pt_air'];
                            }
                            $row['as_up'] = $global_cfg[$row['station_code']]['as_up'];
                            $row['bs']    = $global_cfg[$row['station_code']]['bs'];
                            $row['sc']    = $global_cfg[$row['station_code']]['sc'];
                            $row['sz']    = $global_cfg[$row['station_code']]['sz'];
                        }
                        //退款率
                        if ($col == 'refund_rate'){
                            $new[$col] = $row[$col] . '%';
                            continue;
                        }
                        //erp系统sku状态
                        if ($col == 'product_status'){
                            $new[$col] = isset(PRODUCT_STATUS_ALL[$row[$col]]['name'])?PRODUCT_STATUS_ALL[$row[$col]]['name']:"-";
                            continue;
                        }
                        //sku科学计数法的问题
                        if ($col == 'sku'){
                            $new[$col] = $row[$col]."\t";
                            continue;
                        }
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

    /**
     * 拦截写入的文件，判断是否有泛欧，如果有，则重新去查询
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::_file()
     */
    protected function _file($file_name, $genertor, $file_type = 'csv')
    {
        if(empty($this->_export_type))
        {
            return parent::_file($file_name, $genertor, $file_type = 'csv');
        }
        else
        {
            $file_path = $this->get_download_file_path($file_name) . '.' . $file_type;
            $abs_file = get_export_path() . $file_path;
            $fp = fopen($abs_file, 'w+');
            if (!$fp)
            {
                throw new \RuntimeException('导出csv写入文件失败，请检测权限', 500);
            }
            fwrite($fp, $this->encode($this->get_listing_state_header()));
            property_exists($this->_ci, 'm_pan_eu') OR $this->_ci->load->model('Fba_pan_eu_model', 'm_pan_eu', false, 'fba');

            $col_to_index = array_flip($this->_cols);
            $sku_index = $col_to_index['sku'];
            $account_name_index = $col_to_index['account_name'];
            $account_num_index = $col_to_index['account_num'];
            $seller_sku_index = $col_to_index['seller_sku'];
            $fnsku_index = $col_to_index['fnsku'];
            $asin_index = $col_to_index['asin'];
            $site_index = $col_to_index['station_code'];
            $pan_eu_index = $col_to_index['pan_eu'];
            $id_index = $col_to_index['id'];
            $listing_state_text_index = $col_to_index['listing_state_text'];
            $salesman_zh_name_index = $col_to_index['salesman_zh_name'];
            $tags = [];
            $pan_eu = [];
            $col_len = count($this->_cols);
            //是否范欧
            foreach (IS_PAN_EU as $key =>$value){
                $pan_eu[$value['name']] = $key;
            }
            $num = 1;
            $rownums = 1;
            $filenums = 1;
            $files = [$abs_file];
            foreach ($genertor as $rows)
            {
                    $tags = empty($this->_eu_tag) ? [] : $this->_ci->m_pan_eu->get_by_tags($this->_eu_tag);
                    $origin_station_rows = [];
                    if (!empty($tags))
                    {
                        //解析处理
                        foreach(explode("\n", $rows) as $row) {
                            //泛欧
                            $items = explode(',', $row);
                            $sku_index_value = trim($items[$sku_index], '"');
                            if(!empty($row)) {
                                $pan_eu_value = trim($items[$pan_eu_index], '"');
                                $account_name_index_value = trim($items[$account_name_index], '"');
                                $account_num_index_value = trim($items[$account_num_index], '"');
                                $seller_sku_index_value = trim($items[$seller_sku_index], '"');
                                $fnsku_index_value = trim($items[$fnsku_index], '"');
                                $asin_index_value = trim($items[$asin_index], '"');
                                $id_index_value = trim($items[$id_index], '"');
                                $salesman_zh_name = trim($items[$salesman_zh_name_index], '"');
                                $listing_state_text_index_value = trim($items[$listing_state_text_index], '"');
                                //这里重新在转换, 泛欧解析出来的tag必须设置为泛欧这条记录。
                                if (!empty($pan_eu[$pan_eu_value]) && $pan_eu[$pan_eu_value] == IS_PAN_EU_YES) {
                                    //md5($row['sku'].$row['seller_sku'].$row['fnsku'].$row['asin'].$row['account_num'])
                                    $tag = md5($sku_index_value.$seller_sku_index_value.$fnsku_index_value.$asin_index_value.$account_num_index_value);
                                    if (isset($tags[$tag])) {
                                        $listing_data = [];
                                        if(!empty($listing_state_text_index_value))
                                        {
                                            $listing_state_text_site = explode(';',$listing_state_text_index_value);
                                            array_map(function ($item) use (&$listing_data){
                                                if(!empty($item) && strpos($item,':'))
                                                {
                                                    $site_listing_state = explode(':',$item);
                                                    $listing_data[$site_listing_state[0]] = $site_listing_state[1];
                                                }
                                            }, $listing_state_text_site);
                                        }
                                        foreach ($tags[$tag] as $tag_value) {
                                            $items[$sku_index] = '"'.$sku_index_value."\t".'"';
                                            $items[$account_name_index] = '"'.$account_name_index_value.'"';
                                            $items[$seller_sku_index] = '"'.$seller_sku_index_value.'"';
                                            $items[$fnsku_index] = '"'.$fnsku_index_value.'"';
                                            $items[$asin_index] = '"'.$asin_index_value.'"';
                                            $site = $tag_value['site'] == 'sp' ? 'es' : $tag_value['site'];
                                            $items[$site_index]  = '"'.FBA_STATION_CODE[$site]['name'].'"';
                                            $items[$id_index] = '"'.$id_index_value.'"';
                                            $items[$salesman_zh_name_index] = '"'.$salesman_zh_name.'"';
                                            //英国:运营;德国:不运营;法国:运营;
                                            $items[$listing_state_text_index] = '运营';
                                            if(!empty($listing_state_text_index_value) && !empty($listing_data[FBA_STATION_CODE[$site]['name']]))
                                            {
                                                $items[$listing_state_text_index] = empty($listing_data) ? '"'.$listing_state_text_index_value.'"' : '"'.$listing_data[FBA_STATION_CODE[$site]['name']].'"';
                                            }
                                            $item_num = 1;
                                            foreach ($items as $item_key => $item_value) {
                                                if($item_num > $col_len)
                                                {
                                                    unset($items[$item_key]);
                                                }
                                                $item_num++;
                                            }
                                            $origin_station_rows[] = implode(',', $items);
                                            $rownums++;
                                        }
                                    }
                                    else
                                    {
                                        $item_num = 1;
                                        $items[$sku_index] = '"'.$sku_index_value."\t".'"';
                                        foreach ($items as $item_key => $item_value) {
                                            if($item_num > $col_len)
                                            {
                                                unset($items[$item_key]);
                                            }
                                            $item_num++;
                                        }
                                        $origin_station_rows[] = implode(',', $items);
                                        $rownums++;
                                    }
                                }
                                else
                                {
                                    $items[$sku_index] = '"'.$sku_index_value."\t".'"';
                                    $origin_station_rows[] = implode(',', $items);
                                    $rownums++;
                                }
                            }
                        }
                    }
                    else
                    {
                        foreach(explode("\n", $rows) as $row) {
                            $items = explode(',', $row);
                            $sku_index_value = trim($items[$sku_index], '"');
                            $items[$sku_index] = '"'.$sku_index_value."\t".'"';
                            if(!empty($row))
                            {
                                $item_num = 1;
                                foreach ($items as $item_key => $item_value) {
                                    if($item_num > $col_len)
                                    {
                                        unset($items[$item_key]);
                                    }
                                    $item_num++;
                                }
                                $origin_station_rows[] = implode(',', $items);
                                $rownums++;
                            }
                            $num++;
                        }
                    }
                $rows = implode("\n", $origin_station_rows);
                fwrite($fp, $this->encode($rows."\n"));
                if ($rownums > 499999) {
                    fflush($fp);
                    fclose($fp);
                    $fp = null;

                    //开始换文件
                    $file_path = $this->get_download_file_path($file_name) . '_part'.$filenums. '.' . $file_type;
                    $abs_file = get_export_path() . $file_path;
                    array_push($files,$abs_file);

                    $fp = fopen($abs_file, 'w+');
                    fwrite($fp, $this->encode($this->get_listing_state_header()));
                    $filenums++;
                    $rownums = 1;
                }
                $this->_eu_tag = [];
            }
            fflush($fp);
            fclose($fp);
            $genertor = $fp = null;
            if (count($files) > 1) {
                //将文件进行打包
                $file_path = $this->get_download_file_path($file_name) . '.zip';
                $zip_name = get_export_path() . $file_path;
                $zip = new ZipArchive();
                if ($zip->open($zip_name, ZIPARCHIVE::CREATE) !== true) {
                    throw new \RuntimeException('生成zip文件失败', 500);
                }
                foreach ($files as $key => $csvfile) {
                    $zip->addFile($csvfile, basename($csvfile, '.csv').'.csv');
                }
                $zip->close();
                return $file_path;
            }
            return $file_path;
        }
    }

    public function get_listing_state_header()
    {
        $out = '';
        $title_map = ['sku','seller_sku','fnsku','asin','account_num','account_id','销售组中文名','销售人员','账号名称','站点','listing状态明细','是否泛欧','请勿改动此标记'];
        foreach ($title_map as $col => $name)
        {
            $out .= $this->enclosure.str_replace($this->enclosure, $this->enclosure.$this->enclosure, $name).$this->enclosure.$this->delim;
        }
        $out = substr($out, 0, -strlen($this->delim)).$this->newline;
        return $this->_output_bom ? chr(0xEF).chr(0xBB).chr(0xBF).$out : $out;
    }

    public function run_listing_state()
    {
        try
        {
            $this->before();
            set_time_limit(0);
            $pick_cols  = $this->_cols;
            $col_map    = $this->col_map;
            $file_name  = 'SELLERSKU属性配置表_设置listing状态'.date('YmdHi');

            $pan_eu_tag = &$this->_eu_tag;

            if ($this->_format_type == EXPORT_VIEW_NATIVE)
            {
                $trans = function($row) use ($pick_cols,&$pan_eu_tag) {
                    $new = [];
                    foreach ($pick_cols as $col)
                    {
                        $new[$col] = $row[$col];
                    }
                    if ($row['pan_eu'] == IS_PAN_EU_YES) {
                        $pan_eu_tag[] = md5($row['sku'].$row['seller_sku'].$row['fnsku'].$row['asin'].$row['account_num']);
                        //$new['tag'] = md5($row['sku'].$row['seller_sku'].$row['fnsku'].$row['asin'].$row['account_num']);
                    }
                    return $new;
                };
            }
            else
            {
                $trans = function ($row) use ($pick_cols, &$pan_eu_tag) {
                    $new     = [];
                    $special = ['seller_sku', 'sku'];
                    $cols    = ['created_at', 'updated_at', 'approve_at'];
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
                        if(in_array($col, $special)){
                            $row[$col] = str_replace(array("\r\n", "\r", "\n"), '', $row[$col]);//将换行
                            $row[$col] = str_replace(',',"，",$row[$col]);//将英文逗号转成中文逗号
                        }
                        if(isset($row[$col])){
                            $col_map    = $this->col_map;
                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];
                        }
                    }

                    if(empty($new['listing_state_text']))//$row['pan_eu'] == IS_PAN_EU_NO &&
                    {
                        $new['listing_state_text'] = LISTING_STATE[$row['listing_state']]['name'];
                    }
                    if ($row['pan_eu'] == IS_PAN_EU_YES) {
                        //md5(sku,seller_sku,fnsku,asin,account_num)
                        $pan_eu_tag[] = md5($row['sku'].$row['seller_sku'].$row['fnsku'].$row['asin'].$row['account_num']);
                        $new['tag'] = md5($row['sku'].$row['seller_sku'].$row['fnsku'].$row['asin'].$row['account_num']);
                        $new['is_pan'] = 1;
                    }
                    //echo 'new:'.json_encode($new).PHP_EOL;
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