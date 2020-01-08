<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 日志
 *
 * @package
 * @subpackage
 * @category
 * @author Jason
 * @link
 */
class Log extends MY_Controller {

    private static $s_key = '7c9d3e4dc0f75f634224d4c765e3cadc';

    private $cmd = 'none';

    private $params = [];

    public function __construct()
    {
        parent::__construct();
        $this->params = $this->input->get();
        $this->cmd = $this->params['cmd'] ?? 'none';
        if (($this->params['key'] ?? '') != self::$s_key)
        {
            pr('认证错误, 没有权限访问');
            exit;
        }
    }

    public function Log()
    {
        $params = $this->params;
        $cmd = $this->cmd;

        header('Content-Type: text/html;charset=utf-8');

        switch ($cmd)
        {
            case 'date':
                if (!isset($params['date']))
                {
                    echo '需要传参date=Y-m-d';
                    exit;
                }

                $file = APPPATH . 'logs/log-'.(isset($params['date']) ? $params['date'] : date('Y-m-d', time())).'.php';
                if (isset($params['truncate']))
                {
                    echo $this->truncate_end($params);
                    exit;
                }
                if (!file_exists($file))
                {
                    echo sprintf('file %s 不存在', $file);
                    exit;
                }
                pr(file_get_contents($file));
                pr('--------------------------done-----------------------');
                break;
            case 'ls':
                $filenames = [];
                $iterator = new DirectoryIterator(APPPATH . 'logs');
                foreach ($iterator as $fileinfo) {
                    if ($fileinfo->isFile()) {
                        $filenames[date('Y-m-d_H_i_s', $fileinfo->getMTime())] = $fileinfo->getFilename();
                    }
                }
                ksort($filenames);
                if (empty($filenames))
                {
                    echo 'empty';
                }
                else {
                    pr($filenames);
                }
                break;
            case 'start_record':
                echo $this->enable_end($params);
                break;
            case 'end_record':
                echo $this->disable_end($params);
                break;
            case 'staff_info':
                echo $this->staff_info($params);
                break;
            case 'end_log':
                echo $this->end_log($params);
                break;
            case 'total_line':
                echo $this->total_line($params);
                break;
            case 'salesman':
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist(['fba_salesman']);
                $salesmans = $this->dropdownservice->get()['fba_salesman'];
                pr($salesmans);
                break;
            case 'privileges':
                if (isset($params['staff_code']))
                {
                    echo '当前账号：'.$params['staff_code'].'<br/>';

                    $this->load->service('basic/UsercfgService');
                    echo '产品线权限状态：<br/>';
                    pr($this->usercfgservice->get_my_privileges($params['staff_code']));

                    $this->load->service('fba/FbaManagerAccountService');
                    echo 'FBA管理账号：<br/>';
                    pr($this->fbamanageraccountservice->get_my_accounts($params['staff_code']));

                    echo '是否是销售人员：<br/>';
                    pr(get_active_user()->isSalesman());

                    echo '海外仓平台管理账号：<br/>';
                    $this->load->service('oversea/OverseaManagerAccountService');
                    $account_config =  $this->overseamanageraccountservice->get_station_platforms([$params['staff_code']]);
                    pr($account_config);

                    echo '海外仓站点管理账号：<br/>';
                    $this->load->service('shipment/OverseaManagerAccountService');
                    $station_config =  $this->overseamanageraccountservice->get_my_stations($params['staff_code']);
                    pr($station_config);
                    exit;
                }
                echo '需要传参staff_code=';
                exit;
                break;
            default:
                pr('Cmd: '.$cmd.' 不存在!');
                exit;
                break;
        }
        pr('------------------debug over--------------------');
        exit;
    }

    private function staff_info($params = [])
    {
        pr(get_active_user()->get_user_info());
        pr(get_active_user()->get_user_privileges());
        exit;
    }

    private function end_log($params)
    {
        $max_nums = 200;

        $file = APPPATH . 'logs/log-'.(isset($params['date']) ? $params['date'] : date('Y-m-d', time())).'.php';
        if (!file_exists($file))
        {
            echo sprintf('file %s 不存在', $file);
            exit;
        }

        $start_num = $params['start_num'] ?? 0;
        $end_num = $params['end_num'] ?? 0;

        $tmp_start_num = $start_num;
        $start_num = min($start_num, $end_num);
        $end_num = max($end_num, $tmp_start_num);

        if ($start_num == 0)
        {
            $total_line = $this->total_line($params);
            if (isset($params['nums']))
            {
                $end_num = $total_line;
                $start_num = $total_line - intval($params['nums']);
            }
            else
            {
                $start_num = $total_line - $end_num;
                $end_num = $total_line;
            }
        }
        //pr($start_num . ' - ' . $end_num);exit;
        $fp = new SplFileObject($file);
        $line_count = 0;
        foreach ($fp as $key => $line)
        {
            if ($key > $end_num)
            {
                break;
            }
            if ($key >= $start_num && $line_count <= $max_nums)
            {
                echo 'Line:'.($key+1).'   : '.$line."\n\n<br/><br/>";
                $line_count ++;
            }
        }
        $fp = null;
        exit;

        /*
        $eof = '';
        $postion = -2;
        $fetch_logs = [];
        while ($end_num > 0)
        {
            while ($eof != "\n")
            {
                $postion --;
                $fp->fseek($postion, SEEK_END);
                $eof = $fp->fgetc();
            }
            $fetch_logs[] = $fp->fgets();
            $fp->fseek($postion-1, SEEK_END);
            $eof = '';
            $end_num --;
        }
        $counter = count($fetch_logs) - 1;
        for(; $counter>0; $counter--)
        {
            echo $fetch_logs[$counter] . "<br/>";
        }
        unset($fetch_logs);
        $fp = null;
        exit;
        */
    }

    private function total_line($params)
    {
        $file = APPPATH . 'logs/log-'.(isset($params['date']) ? $params['date'] : date('Y-m-d', time())).'.php';
        if (!file_exists($file))
        {
            echo sprintf('file %s 不存在', $file);
            exit;
        }
        $fp = new SplFileObject($file);

        $line = 0;
        foreach ($fp as $v)
        {
            $line ++;
        }
        $fp = null;
        return $line;
    }

    private function enable_end($params)
    {
        $lock_file = APPPATH . 'logs/log.lock';
        if (file_exists($lock_file))
        {
            return 'start';
        }
        touch($lock_file) OR file_put_contents($lock_file, '');
        return  file_exists($lock_file) ? 'start' : 'fail';
    }

    private function disable_end($params)
    {
        $lock_file = APPPATH . 'logs/log.lock';
        if (file_exists($lock_file))
        {
            unlink($lock_file);
        }
        return file_exists($lock_file) ? 'unlink fail' : 'unlink success';
    }

    private function truncate_end($params)
    {
        $date = $params['date'] ?? date('Y-m-d');
        $file = APPPATH . 'logs/log-'.($date).'.php';
        file_put_contents($file, '');
        return 'truncate log ' . $date . 'over';
    }

    public function cache_console()
    {
        $get = $this->input->get();
        $cmd = $get['cmd'] ?? '';

        $this->load->library('QueryStatistics', null, 'QueryStatistics');

        switch ($cmd)
        {
            case 'expired_hash':
                $data = $this->QueryStatistics->expired_one_sql($this->input->get());
                break;
            case 'expired_methods':
                $methods = explode(',', $this->input->get('methods'));
                $data = empty($methods) ? 'empty method, example fba.pr.list' : $this->QueryStatistics->expired_methods($methods);
                break;
            case 'expired_ctrls':
                $ctrls = explode(',', $this->input->get('ctrls'));
                $data = empty($ctrls) ? 'empty ctrls, example fba.pr' : $this->QueryStatistics->expired_ctrls($ctrls);
                break;
            case 'expired_modules':
                $modules = explode(',', $this->input->get('modules'));
                $data = empty($modules) ? 'empty modules, example fba' : $this->QueryStatistics->expired_modules($modules);
                break;
            case 'expired_tables':
                $tables = explode(',', $this->input->get('tables'));
                $data = empty($tables) ? 'empty tabls, example yibai_user_config' : $this->QueryStatistics->expired_tables($tables);
                break;
            case 'expired_staffs':
                $staffs = explode(',', $this->input->get('staffs'));
                $data = empty($staffs) ? 'empty staffs, example W01206' : $this->QueryStatistics->expired_staffs($staffs);
                break;
            case 'expired_staff_all':
                $data = $this->QueryStatistics->expired_staff_all();
                break;
            case 'reset':
                $data = $this->QueryStatistics->reset();
                break;
            case 'flushall':
                $data = $this->QueryStatistics->flushall();
                break;
            case 'expired_limit':
                $data = $this->QueryStatistics->expired_limit();
                break;
            case 'report':
            default:
                $data = $this->QueryStatistics->report();
                break;
        }
        http_response($data);
    }
}
/* End of file api.php */
/* Location: ./application/modules/demo/controllers/api.php */