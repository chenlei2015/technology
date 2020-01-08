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

class ShipmentOverseaHugeExport extends AbstractHugeExport
{

    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_list_model', 'm_oversea_list', false, 'shipment');
        $this->_db = $this->_ci->m_oversea_list->getDatabase();
    }


    public function set_template($template='')
    {
        $this->template = $template;
    }

    public function set_filename($filename='')
    {
        if (empty($filename)){
            $filename =  '发运计划详情列表_'.date('YmdHis');
        }else{
            $filename = $filename.date('His').rand(0,9);
        }
        $this->filename = $filename;
    }


    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        if (!empty($this->template)){
            $this->_ci->load->service('basic/UsercfgProfileService');
            //选择模板
            if($this->template == 4){
                $this->template = 'oversea_shipment_track_list';
                //需要加上发运详情字段
                $add_field = $special_field = [
                    '物流单号'  =>
                        [
                            'col'   => 'logistics_sn',
                            'width' => 18,
                        ],
                    '实际发运数量' =>
                        [
                            'col'   => 'shipment_qty',
                            'width' => 18,
                        ],
                    '物流状态'  =>
                        [
                            'col'   => 'logistics_status',
                            'width' => 18,
                        ],
                    '签收数量' =>
                        [
                            'col'   => 'receipt_qty',
                            'width' => 18,
                        ],
                ];
            }else{
                $this->template = 'oversea_shipment_detail_list';

            }

            return $this->_ci->usercfgprofileservice->export_temple($this->template,[],[],[],false,$add_field);
        }
    }


    /**
     * 字段转换
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
        $this->col_map = [
            'updated_uid'      => $staff_map,
            'approved_uid'     => $staff_map,
            'station_code'     => OVERSEA_STATION_CODE,
            'sku_state'        => SKU_STATE,
            'logistics_id'     => LOGISTICS_ATTR,
            'warehouse_id'     => PURCHASE_WAREHOUSE,
            'is_refund_tax'    => REFUND_TAX,
            'shipment_type'    =>SHIPMENT_TYPE_LIST,
            'shipment_status'  =>SHIPMENT_STATUS,
            'logistics_status' => LOGISTICS_STATUS,
        ];
        return $this;
    }

    /**
     * 入口
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
            $file_name = $this->filename;

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
//                    pr($row);exit;
                    $new = [];
                    $cols = ['created_at', 'updated_at', 'approved_at'];
                    $user_cols = ['salesman', 'updated_uid', 'approved_uid'];
                    //$zero_number = ['deviate_28_pcs', 'avg_weight_sale_pcs', 'avg_deliver_day', 'z', 'weight_sale_pcs'];
                    foreach ($pick_cols as $col)
                    {
                        if (in_array($col, $cols))
                        {
                            $new[$col] = empty($row[$col]) ? '' : date('Y-m-d H:i:s', $row[$col])."\t";
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
            $db_query && $db_query->free_result();
            $this->after();
            return $file_path;
        }
    }

    public function __destruct()
    {

    }

}