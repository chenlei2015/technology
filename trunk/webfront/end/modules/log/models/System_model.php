<?php
/**
 *
 * Class System_model
 * @desc 系统日志
 * @author zc
 * @since 20191021
 *
 */

class System_model extends MY_Model{
    private $_sys_log_type = '前端';
    private $_sys_log_start_num = 1;

    public function __construct(){
        parent::__construct();
    }

    public function getLogInfo($log_date,$level='',$offset = 1,$limit = 20)
    {
        $log_date = $log_date??date('Y-m-d');
        $file = APPPATH."logs".DIRECTORY_SEPARATOR."log-{$log_date}.php";
        $data = [];
        if(file_exists($file))
        {
            $log = new SplFileObject($file);
            $no = 1;
            $start = ($offset - 1) * $limit > 0 ? ($offset - 1) * $limit + 2 : 2;
            $log->seek($start);
            $num = $limit;
            while($num)
            {
                $line = $log->current();
                $array_line = explode(' ',$line);
                if(in_array($array_line[0],SYS_LOG_LEVEL) && $no <= $limit)
                {
                    if((!empty($level) && $level == $array_line[0]) || empty($level))
                    {
                        $data['list'][] = [
                            'no' => $no,
                            'desc' => str_replace(array("\r\n", "\r", "\n"), "", $line),
                            'type' => $this->_sys_log_type,
                            'level' => $array_line[0],
                            'log_time' =>$array_line[2].' '.$array_line[3]
                        ];
                        $no++;
                        $num--;
                    }
                }
                $log->next();
                if($log->eof()) {
                    break;
                }
            }
            $data['total'] = $this->getFileRowCount($file,$level);
        }
        return $data;
    }

    public function getLogInfoDesc($log_date,$level='',$offset = 1,$limit = 20)
    {
        $log_date = $log_date??date('Y-m-d');
        $file = APPPATH."logs".DIRECTORY_SEPARATOR."log-{$log_date}.php";
        $data = [];
        if(file_exists($file))
        {
            $data['total'] = $this->getFileRowCount($file);
            $log = new SplFileObject($file);
            $no = 1;
            $start = $data['total'] > 0 ? $data['total'] - ($offset-1) * $limit + 1 : 0 ;
            //$end = $data['total'] > 0 ? $data['total'] - $offset * $limit : 0 ;
            $data['list'] = [];
            if($start > 0)
            {
                $num = $limit;
                while($num)
                {
                    $log->seek($start);
                    if($log->key() < $this->_sys_log_start_num) {
                        break;
                    }
                    $line = $log->current();
                    $data['list'][] = [
                        'no' => $no,
                        'desc' => str_replace(array("\r\n", "\r", "\n"), "", $line),
                        'type' => $this->_sys_log_type,
                        'level' => '',
                        'log_time' =>'',
                        'num' => $log->key(),
                        'start' => $start
                    ];
                    $no++;
                    $num--;
                    $start--;
                    if($no > $data['total']) {
                        break;
                    }
                }
            }
        }
        return $data;
    }

    public function getFileRowCount($file,$level='')
    {
        $log = new SplFileObject($file);
        $total = 0;
        foreach( $log as $line ) {
            if(!empty($line))
            {
                $total++;
            }
        }
        if(empty($level))
        {
            $total = $total > 2 ? $total - 2 : 0;
        }
        return $total;
    }
}