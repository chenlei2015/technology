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

class InlandActivityConfigExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_pr_list_model', 'inland_pr_list', false, 'inland');
        $this->_db = $this->_ci->inland_pr_list->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $template = [
            'SKU'   => [
                'col' => 'erpsku'
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
            ]
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
            $file_name = '国内活动列表_'.date('YmdHi');

            if ($this->_format_type == EXPORT_VIEW_NATIVE)
            {
                $trans = function($row) use ($pick_cols) {
                    $new = [];
                    foreach ($pick_cols as $col)
                    {
                        $new[$col] = $row[$col];
                    }
                    return $new;
                };
            }
            else
            {
                $trans = function($row) use ($pick_cols, $col_map) {
                    $new = [];
                    foreach ($pick_cols as $col)
                    {
                        if (isset($row[$col])) {
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