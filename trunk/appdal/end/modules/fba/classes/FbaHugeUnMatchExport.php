<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/8
 * Time: 13:48
 */
require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';

class FbaHugeUnMatchExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_db = $this->_ci->load->database('yibai_product', true);
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->classes('fba/classes/FbaLogisticsTemplate');

        $title_map = [
            'seller_sku' =>
                [
                    'col'   => 'seller_sku',
                    'width' => 18,
                ],
            'account_id' =>
                [
                    'col' => 'account_id',
                ],
        ];

        return $title_map;
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
        $this->col_map = [];

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
            $file_name = '未匹配到的sellersku' . date('YmdHi');

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
                    $new = [];
                    foreach ($pick_cols as $col) {
                        $new[$col] = $row[$col];
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