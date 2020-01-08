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

    public function __construct()
    {
        parent::__construct();
    }

    public function Log()
    {
        $params = $this->input->get();
        if (!isset($params['key']) || $params['key'] != self::$s_key) return false;

        $cmd = $params['cmd'] ?? 'none';
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
                    file_put_contents($file, '');
                    echo 'truncate log over';
                    exit;
                }
                if (!file_exists($file))
                {
                    echo sprintf('file %s 不存在', $file);
                    exit;
                }
                header('Content-Type: text/html;charset=utf-8');
                pr(file_get_contents($file));
                pr('--------------------------done-----------------------');
                exit;
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
                exit;
                break;
            case 'start_record':
                $lock_file = APPPATH . 'logs/log.lock';
                touch($lock_file) OR file_put_contents($lock_file, '');
                echo file_exists($lock_file) ? 'start' : 'fail';
                exit;
                break;
            case 'end_record':
                $lock_file = APPPATH . 'logs/log.lock';
                if (file_exists($lock_file))
                {
                    unlink($lock_file);
                }
                echo file_exists($lock_file) ? 'unlink fail' : 'unlink success';
                exit;
                break;
            case 'info':
                pr($this->_user_data);
                exit;
                break;
            default:
                $this->redirect_appdal($params);
                break;
        }

        exit;
    }

    private function redirect_appdal($params)
    {
        $api = 'log/log/log';
        $result = $this->_curl_request->cloud_get($api, $params, $is_json = false);
        pr($result);
    }

    public function cache_console()
    {
        $params = $this->input->get();
        if (!isset($params['key']) || $params['key'] != self::$s_key) return false;
        $api_name = 'log/log/cache_console';
        $result = $this->_curl_request->cloud_get($api_name, $params);
        http_response($result);
    }
}
/* End of file api.php */
/* Location: ./application/modules/demo/controllers/api.php */