<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Config for the CodeIgniter Redis library
 *
 * @see ../libraries/Rediss.php
 */

// Default connection group
$config['redis_default']['host'] = '192.168.71.170';		// IP address or host
$config['redis_default']['port'] = '7001';			// Default Redis port is 6379
$config['redis_default']['password'] = 'yis@2019._';			// Can be left empty when the server does not require AUTH


$config['redis_slave']['host'] = '192.168.71.170';
$config['redis_slave']['port'] = '7001';
$config['redis_slave']['password'] = 'yis@2019._';
