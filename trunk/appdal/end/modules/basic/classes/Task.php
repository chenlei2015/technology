<?php

/**
 * 投递一个异步任务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-28
 * @link
 * @throw RuntimeException|InvalidArgumentException
 */
class Task
{
    /**
     * 外部必须异常捕获
     */
    public function __construct()
    {
        $this->check_env();
    }

    private function check_env()
    {
        if (!function_exists('shell_exec')) {
            throw new RuntimeException('请在php.ini或者php-fpm中开启appdal的shell_exec的函数', 500);
        }
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            throw new RuntimeException('该操作不能再Window操作系统下执行', 500);
        }
    }

    /**
     * @param array $paths ['fba', 'PR', 'estimate']
     * @param array $params [1933,]
     * @throws \InvalidArgumentException
     * @return string NULL|process output
     */
    public function delivery(array $paths, array $params)
    {
        if (count($paths) != 3) {
            throw new \InvalidArgumentException('任务路径必须是3参数 module,ctrl,method', 500);
        }
        $task_path = implode(' ', $paths);

        //固定第一个参数传当前登陆用户的uid
        $session_uid = get_active_user()->uid;
        $shell_params[] = $session_uid;
        $process_output = '无返回';

        if (!empty($params)) {
            $shell_params = array_merge($shell_params, $params);
        }

        $path_entry = FCPATH.'index.php';
        $cmd = sprintf('/usr/bin/php %s %s %s > /dev/null 2>&1 &', $path_entry, $task_path, implode(' ', $shell_params));
        $process_output = shell_exec($cmd);
        log_message('INFO', sprintf('投递cli任务:%s, 进程输出：%s',$cmd, strval($process_output)));

        return $process_output;
    }

}