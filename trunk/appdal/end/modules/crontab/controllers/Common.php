<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 公共定时任务
 *
 * @author Jason 13292
 * @since 2019-04-11
 */
class Common extends MX_Controller {


    public function __construct()
    {
        parent::__construct();
    }

    private function delete_dir($dir, $delete_my = false, $skip_dir = [])
    {
        $result = false;
        if ($handler = opendir($dir))
        {
            $result = true;
            $has_skip_dir = !empty($skip_dir);

            while ((($file=readdir($handler))!==false) && ($result))
            {
                if ($file != '.' && $file != '..')
                {
                    if (is_dir("$dir/$file"))
                    {
                        if ($has_skip_dir && in_array($file, $skip_dir))
                        {
                            continue;
                        }
                        else
                        {
                            $result = $this->delete_dir("$dir/$file", true, $skip_dir);
                        }
                    }
                    else
                    {
                        $result = @unlink("$dir/$file");
                    }
                }
            }
            closedir($handler);
            $result && $delete_my && rmdir($dir);
        }
        return $result;
    }

    /**
     * 删除导出csv
     */
    public function delete_export_csv()
    {
        if (!is_dir(EXPORT_FILE_PATH))
        {
            $data = ['status' => 0, 'errorMess' => '下载目录不存在'];
            http_response($data);
            return;
        }
        $data['data'] = $this->delete_dir(EXPORT_FILE_PATH, false, [date('Ymd')]);
        $data['status'] = $data['data'] ? 1 : 0;
        http_response($data);
    }

    /**
     * 同步staff的状态
     */
    public function sync_staff_state()
    {
        $this->load->service('UserService');
        $data['data'] = $this->userservice->sync_staff_state();
        $data['status'] = $this->data['data'] ? 1 : 0;
        http_response($data);
    }

    public function expired_cache()
    {
        $this->load->library('QueryStatistics', null, 'QueryStatistics');
        $data = $this->QueryStatistics->flushall();
        http_response($data);
    }

    public function rebuild_process()
    {
        $errorMess = '';
        $version = $this->input->get()['version'] ?? -1;
        $buss_line = $this->input->get()['business_line'] ?? BUSSINESS_FBA;

        $key = 'rebuild_process_'.$buss_line.'_'.$version;
        switch ($buss_line) {
            case BUSSINESS_FBA:
                $this->load->model('Fba_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'fba');
                break;
            case BUSSINESS_IN:
                $this->load->model('Inland_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'inland');
                break;
            case BUSSINESS_OVERSEA:
                $this->load->model('Oversea_rebuild_mvcc_model', 'm_rebuild_mvcc', false, 'oversea');
                break;
            default:
                $data['data'] = -1;
                $data['status'] = 0;
                $data['errorMess'] = '无效的业务线';
                return http_response($data);
                break;
        }

        $data['data'] = $this->m_rebuild_mvcc->get_process($buss_line, $version);

        $this->load->library('Rediss');
        $context = $this->rediss->getData($key);

        if ($context)
        {
            if ($context['percent'] == $data['data'])
            {
                if ($context['counter'] >= 40) {
                    $data['data'] = -1;
                    $data['status'] = 0;
                    $data['errorMess'] = '进度条停止更新，执行超时，请重试继续完成';
                    $this->rediss->deleteData($key);
                    return http_response($data);
                }
                else
                {
                    $context['counter'] += 1;
                    $this->rediss->setData($key, $context, 120);
                }
            }
            elseif ($context['percent'] != $data['data'])
            {
                $this->rediss->setData($key, ['percent' => $data['data'], 'counter' => 1], 120);
            }
        }
        else
        {
            $this->rediss->setData($key, ['percent' => $data['data'], 'counter' => 1], 120);
        }
        $data['status'] = 1;
        http_response($data);
    }

    public function sync_backup_log()
    {
        $this->load->model('Backup_remote_table_model', 'm_remote_table', false, 'basic');
        $data['date'] = $this->m_remote_table->sync();
        $data['status'] = 1;
        http_response($data);
    }

    public function sync_fba_inventory()
    {
        $data['status'] = 0;
        $params = $this->input->get();
        if (!isset($params['date']) || !is_valid_date($params['date'])) {
            $data['errorMess'] = '无效的日期';
            return http_response($data);
        }
        if (date('Y-m-d', strtotime($params['date'])) != date('Y-m-d') ) {
            $data['errorMess'] = '日期必须是今天';
            return http_response($data);
        }
        if (!isset($params['step']) || !in_array($params['step'], [1, 2])) {
            $data['errorMess'] = '无效的步骤，步骤支持1：库存数据完成 2：库存日志完成';
            return http_response($data);
        }
        $this->load->model('Backup_local_table_model', 'm_local_table', false, 'basic');
        $data['date'] = $this->m_local_table->sync($params);
        $data['status'] = 1;
        http_response($data);
    }

    /**
     * 删除重建需求列表中无效的配置。这里先增加FBA线，后续增加国内、海外仓
     */
    public function delete_rebuild_invalid_cfg()
    {
        $data['status'] = 0;
        try {
            $this->load->model('Fba_rebuild_mvcc_model', 'm_fba_mvcc', false, 'fba');
            $fba_delete = $this->m_fba_mvcc->clean();
            $data['data']['fba'] = $fba_delete;
            $data['status'] = 1;
        } catch (\RuntimeException $e) {
            $data['data']['errorMess'] = $e->getMessage();
        }
        http_response($data);
    }

}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */