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

class FbaActivityConfigExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    private $_eu_tag = [];

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_pr_list_model', 'fba_pr_list', false, 'fba');
        $this->_db = $this->_ci->fba_pr_list->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $template = [
            '销售小组'        => [
                'col' => 'sale_group'
            ],
            '账号名称'        => [
                'col' => 'account_name'
            ],
            '销售人员'        => [
                'col' => 'salesman'
            ],
            'account_id'  => [
                'col' => 'account_id'
            ],
            'account_num' => [
                'col' => 'account_num'
            ],
            'ERPSKU' => [
                'col' => 'sku'
            ],
            'SellerSKU'   => [
                'col' => 'seller_sku'
            ],
            'FNSKU'       => [
                'col' => 'fnsku'
            ],
            'ASIN'        => [
                'col' => 'asin'
            ],
            '泛欧标记' => [
                'col' => 'tag'
            ],
            '是否泛欧' => [
                'col' => 'is_pan'
            ],
            '站点' => [
                'col' => 'station_code'
            ],
            '活动名称'        => [
                'col' => 'activity_name'
            ],
            '活动量'         => [
                'col' => 'amount'
            ],
            '开始备货时间'      => [
                'col' => 'execute_purcharse_time'
            ],
            '活动开始时间'      => [
                'col' => 'activity_start_time'
            ],
            '活动结束时间'      => [
                'col' => 'activity_end_time'
            ],
        ];
        return $template;
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::set_translator()
     */
    public function set_translator() : AbstractHugeExport
    {
        if ($this->_format_type == EXPORT_VIEW_NATIVE) {
            return $this;
        }
        $staff_map     = $this->get_all_saleman();
        $fba_group     = $this->get_all_fba_group();
        $this->col_map = [
            'salesman'   => $staff_map,
            'sale_group' => $fba_group,
            'station_code' => FBA_STATION_CODE,
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
            $file_name = 'FBA活动列表_'.date('YmdHi');

            $pan_eu_tag = &$this->_eu_tag;

            if ($this->_format_type == EXPORT_VIEW_NATIVE)
            {
                $trans = function($row) use ($pick_cols, $pan_eu_tag) {
                    $new = [];
                    foreach ($pick_cols as $col)
                    {
                        $new[$col] = $row[$col];
                    }

                    if ($row['station_code'] == 'eu') {
                        $pan_eu_tag[$row['tag']] = 1;
                        $new['tag'] = $row['tag'];
                        $new['is_pan'] = 1;

                    } else {
                        $new['tag'] = $row['tag'];
                        $new['is_pan'] = 2;
                    }

                    return $new;
                };
            }
            else
            {
                $trans = function($row) use ($pick_cols, $col_map, &$pan_eu_tag) {
                    $new = [];
                    foreach ($pick_cols as $col)
                    {
                        if ($col == 'sale_group') {
                            $new[$col] = $col_map[$col][$row[$col]] ?? $row[$col];
                            continue;
                        }
                        if ($col == 'salesman') {
                            $new[$col] = empty($row[$col]) ? '' : $col_map[$col][$row[$col]] ?? $row[$col];
                            continue;
                        }
                        if (isset($row[$col])) {
                            $new[$col] = $col_map[$col][$row[$col]]['name'] ?? $row[$col];
                        }
                    }

                    if ($row['station_code'] == 'eu') {
                        $pan_eu_tag[] = $row['tag'];
                        $new['tag'] = $row['tag'];
                        $new['is_pan'] = 'Y';
                    } else {
                        $new['tag'] = $row['tag'];
                        $new['is_pan'] = 'N';
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
        $file_path = $this->get_download_file_path($file_name) . '.' . $file_type;
        $abs_file = get_export_path() . $file_path;
        $fp = fopen($abs_file, 'w+');
        if (!$fp)
        {
            throw new \RuntimeException('导出csv写入文件失败，请检测权限', 500);
        }

        fwrite($fp, $this->encode($this->get_header()));
        $rownums = 1;
        $filenums = 1;
        $files = [$abs_file];

        property_exists($this->_ci, 'm_pan_eu') OR $this->_ci->load->model('Fba_pan_eu_model', 'm_pan_eu', false, 'fba');
        property_exists($this->_ci, 'm_amzon_account') OR $this->_ci->load->model('Fba_amazon_account_model', 'm_amzon_account', false, 'fba');

        $col_to_index = array_flip($this->_cols);

        $tag_index          = $col_to_index['tag'];
        $station_index      = $col_to_index['station_code'];
        $account_id_index   = $col_to_index['account_id'];
        $account_num_index  = $col_to_index['account_num'];
        $account_name_index = $col_to_index['account_name'];
        $seller_sku_index   = $col_to_index['seller_sku'];
        $fnsku_index        = $col_to_index['fnsku'];
        $asin_index         = $col_to_index['asin'];
        $sku_index          = $col_to_index['sku'];

        $account_id_info = $this->_ci->m_amzon_account->get_account_id_map();

        //一次是100行 c1,c2\nc11,c22
        foreach ($genertor as $rows)
        {
            if (!empty($this->_eu_tag)) {

                $origin_station_rows = [];
                $tags = $this->_ci->m_pan_eu->get_by_tags($this->_eu_tag);

                if (!empty($tags)) {

                    //截断末尾分割符号
                    $rows = trim($rows, "\n");

                    //解析处理
                    foreach(explode("\n", $rows) as $row) {

                        //泛欧
                        $items = array_filter(explode(',', $row));
                        if (empty($items)) {
                            //空行
                            continue;
                        }
                        if (!array_key_exists($tag_index, $items)) {
                            log_message('ERROR', sprintf('FBA需求列表导出活动列表，tag_index:%d不存在记录：%s中', $tag_index, json_encode($items)));
                        }
                        $tag = trim($items[$tag_index] ?? '', '"');

                        //unset($items[$tag_index]);
                        //这里重新在转换, 泛欧解析出来的tag必须设置为泛欧这条记录。
                        if (substr($row, -4) == ',"Y"') {
                            $parent_eu_aggr_id = md5(implode('', [trim($items[$sku_index], '"'), trim($items[$seller_sku_index], '"'), trim($items[$fnsku_index], '"'), trim($items[$asin_index], '"'), trim($items[$account_num_index], '"')  ]));
                            if (isset($tags[$tag])) {
                                foreach ($tags[$tag] as $row) {
                                    $items[$account_id_index] = $row['account_id'];
                                    if (isset($account_id_info[$row['account_id']])) {
                                        $account_name               = $account_id_info[$row['account_id']]['account_name'];
                                        $items[$account_name_index] = '"'.$account_name.'"';
                                        $items[$account_num_index]  = '"'.$account_id_info[$row['account_id']]['account_num'].'"';
                                        $site                       = $account_id_info[$row['account_id']]['site'];
                                        $site                       = $site == 'sp' ? 'es' : $site;
                                        $items[$station_index]      = '"'.FBA_STATION_CODE[$site]['name'].'"';
                                    }
                                    $items[$tag_index] = $parent_eu_aggr_id;
                                    $origin_station_rows[] = implode(',', $items);
                                }
                                //增加的行数
                                $rownums += (count($tags[$tag]) - 1);
                            }
                        } else {
                            $origin_station_rows[] = $row;
                        }
                    }
                }//end tags

                if (!empty($origin_station_rows)) {
                    $rows = implode("\n", $origin_station_rows);
                }
            } //end eu_tag

            if (empty($rows)) {
                continue;
            }
            fwrite($fp, $this->encode($rows."\n"));
            $this->_eu_tag = [];

            $rownums += 100;
            if ($rownums >= 500000) {

                fflush($fp);
                fclose($fp);
                $fp = null;

                //开始换文件
                $file_path = $this->get_download_file_path($file_name) . '_part'.$filenums. '.' . $file_type;
                $abs_file = get_export_path() . $file_path;
                $files[] = $abs_file;

                $fp = fopen($abs_file, 'w+');
                fwrite($fp, $this->encode($this->get_header()));
                $filenums ++;
                $rownums = 0;
            }
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
                $zip->addFile($csvfile, basename($file_name, '.csv').'_part'.($key+1).'.csv');
            }
            $zip->close();
            return $file_path;
        }

        return $file_path;
    }

    public function __destruct()
    {

    }

}