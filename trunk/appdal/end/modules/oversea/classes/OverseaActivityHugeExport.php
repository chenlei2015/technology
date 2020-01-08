<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractHugeExport.php';
/**
 * 大文件导出csv
 * FBA活动配置表的导出
 * @package -
 * @subpackage -
 * @category -
 * @author zc
 * @since 2018-12-28
 * @link
 * @throw
 */

class OverseaActivityHugeExport extends AbstractHugeExport
{
    private $_db;

    private $_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Oversea_activity_list_model', 'm_oversea_activity', false, 'oversea');
        $this->_db = $this->_ci->m_oversea_activity->getDatabase();
    }

    /**
     *
     * {@inheritDoc}
     * @see AbstractHugeExport::get_default_template_cols()
     */
    protected function get_default_template_cols()
    {
        $this->_ci->load->service('basic/UsercfgProfileService');
        $template['活动ID'] = ['col' => 'id'];
        $join_cols =  ['created_zh_name' => '创建人', 'updated_zh_name' => '修改人', 'approved_zh_name' => '审核人'];
        foreach ($this->_ci->usercfgprofileservice->export_temple('oversea_activity') as $title => $cfg)
        {
            if (count($cols = array_flip(explode(',', $cfg['col']))) > 1) {

                if ($join_col = array_intersect_key($cols, $join_cols))
                {
                    foreach ($cols as $col => $v)
                    {
                        //被拼合的字段
                        if (isset($join_col[$col])) {
                            $template[$join_cols[$col] ?? $col] = ['col' => $col];
                        }
                        else
                        {
                            //主
                            $template[$title] = ['col' => $col];
                        }
                    }
                }
            }
            else
            {
                //去掉模板自带的,以自定义拆分的为准
                if (false !== array_search($title, $join_cols)) {
                    continue;
                }
                $template[$title] = $cfg;
            }
        }
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
            'approve_state'  => ACTIVITY_APPROVAL_STATE,
            'activity_state' => ACTIVITY_STATE,
            'platform_code' => INLAND_PLATFORM_CODE
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
            set_time_limit(0);
            ini_set('memory_limit', '-1');

            $pick_cols = $this->_cols;
            $col_map   = $this->col_map;
            $file_name = '海外活动列表_'.date('YmdHi');

            if ($this->_format_type == EXPORT_VIEW_NATIVE)
            {
                $trans = function($row) use ($pick_cols) {
                    $new = [];
                    $cols = ['created_at', 'updated_at', 'approved_at', 'activity_start_time', 'activity_end_time', 'execute_purcharse_time'];
                    foreach ($pick_cols as $col)
                    {
                        if (in_array($col, $cols))
                        {
                            $new[$col] = empty($row[$col]) || $row[$col] == '0000-00-00 00:00:00' ? '' : $row[$col]."\t";
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
                    $cols = ['created_at', 'updated_at', 'approved_at', 'activity_start_time', 'activity_end_time', 'execute_purcharse_time'];

                    foreach ($pick_cols as $col)
                    {
                        $more_cols = explode(',', $col);

                        $new[$col] = '';

                        //salesman_zh_name,created_at
                        foreach ($more_cols as $one_col)
                        {
                            if (in_array($one_col, $cols))
                            {
                                $new[$col] .= (empty($row[$one_col]) || $row[$one_col] == '0000-00-00 00:00:00' ? '' : $row[$one_col]."\t");
                            }
                            else
                            {
                                //活动状态
                                if ($col == "activity_state"){
                                    switch ($row['activity_state']){
                                        case ACTIVITY_STATE_NOT_START:
                                            if ($row['activity_start_time'] > date("Y-m-d H:i:s",time())){
                                                $new[$col] .= $col_map[$col][ACTIVITY_STATE_NOT_START]['name'] ?? $row[$col];
                                            }else{
                                                $new[$col] .= $col_map[$col][ACTIVITY_STATE_ING]['name'] ?? $row[$col];
                                            }
                                            break;
                                        case ACTIVITY_STATE_DISCARD:
                                            $new[$col] .= $col_map[$col][ACTIVITY_STATE_DISCARD]['name'] ?? $row[$col];
                                            break;
                                        case ACTIVITY_STATE_END:
                                            $new[$col] .= $col_map[$col][ACTIVITY_STATE_END]['name'] ?? $row[$col];
                                            break;
                                    }
                                    continue;
                                }
                                if ($col == "station_code"){
                                    $new[$col] = isset(OVERSEA_STATION_CODE[$row[$col]])?OVERSEA_STATION_CODE[$row[$col]]['name']:'';
                                    continue;
                                }
                                if(isset($row[$one_col])){
                                    $new[$col] .= ($col_map[$one_col][$row[$one_col]]['name'] ?? $row[$one_col]);
                                }
                            }
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