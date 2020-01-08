<?php

/**
 * 专注于生成可用不重复的order_sn
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class OrderSnPoolService
{
    private $_ci;

    private $_redis;

    public $today_unuse_key;

    public $today_seq_key;

    private $_scene;

    private $_reload_used = [];

    /**
     * v1.1.0 新增redis意外丢失已使用的订单重启情况
     *
     * @var unknown
     */
    private $_interupt_cb;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->library('rediss');
        $this->_redis = $this->_ci->rediss;
    }

    public static final function scene_config()
    {
        return [
            'fba_summary' => [
                    's_prefix'        => 'FHZ',
                    's_random_length' => 5,
                    's_date_string'   => 'ymd',
                    's_length'        => 16,
                    's_key_unuse'   => 'fba_sum_unpool_',
                    's_key_seq'     => 'fba_char_',
                    's_seq_char'    => 2,
                    's_init_seq'    => 'AA',
                    'interrupt_reload_used' => function($sn_prefix, $cut_length) {
                        $ci = CI::$APP;
                        $ci->load->model('Fba_pr_summary_model', 'm_fba_summary', false, 'fba');
                        return $ci->m_fba_summary->get_today_used_sum_sns($sn_prefix, $cut_length);
                    }
            ],
            'oversea_summary' => [
                    's_prefix'        => 'HHZ',
                    's_random_length' => 5,
                    's_date_string'   => 'ymd',
                    's_length'        => 16,
                    's_key_unuse'   => 'oversea_sum_unpool_',
                    's_key_seq'     => 'oversea_char_',
                    's_seq_char'    => 2,
                    's_init_seq'    => 'AA',
                    'interrupt_reload_used' => function($sn_prefix, $cut_length) {
                        $ci = CI::$APP;
                        $ci->load->model('Oversea_pr_summary_model', 'm_oversea_summary', false, 'oversea');
                        return $ci->m_oversea_summary->get_today_used_sum_sns($sn_prefix, $cut_length);
                    }
            ],
            'inland_summary' => [
                    's_prefix'        => 'GHZ',
                    's_random_length' => 5,
                    's_date_string'   => 'ymd',
                    's_length'        => 16,
                    's_key_unuse'   => 'inland_sum_unpool_',
                    's_key_seq'     => 'inland_char_',
                    's_seq_char'    => 2,
                    's_init_seq'    => 'AA',
                    'interrupt_reload_used' => function($sn_prefix, $cut_length) {
                        $ci = CI::$APP;
                        $ci->load->model('Inland_pr_summary_model', 'm_inland_summary', false, 'inland');
                        return $ci->m_inland_summary->get_today_used_sum_sns($sn_prefix, $cut_length);
                    }
            ],
            'inland_special' => [
                    's_prefix'        => 'GTS',
                    's_random_length' => 5,
                    's_date_string'   => 'ymd',
                    's_length'        => 16,
                    's_key_unuse'   => 'special_inland_unpool_',
                    's_key_seq'     => 'special_char_',
                    's_seq_char'    => 2,
                    's_init_seq'    => 'AA',
                    'interrupt_reload_used' => function($sn_prefix, $cut_length) {
                        $ci = CI::$APP;
                        $ci->load->model('Inland_special_pr_list_model', 'm_special_pr_list', false, 'inland');
                        return $ci->m_special_pr_list->get_today_used_pr_sns($sn_prefix, $cut_length);
                    }
            ],
            'fba_shipment' => [
                's_prefix'        => 'FYFBA',
                's_random_length' => 5,
                's_date_string'   => 'ymd',
                's_length'        => 16,
                's_key_unuse'   => 'fba_shipment_unpool_',
                's_key_seq'     => 'fba_shipment_char_',
                's_seq_char'    => 2,
                's_init_seq'    => 'AA',
                'interrupt_reload_used' => function($sn_prefix, $cut_length) {
                    $ci = CI::$APP;
                    $ci->load->model('Fba_shipment_list_model', 'm_fba_shipment', false, 'shipment');
                    return $ci->m_fba_shipment->get_today_used_shipment_sns($sn_prefix, $cut_length);
                }
            ],
            'oversea_shipment' => [
                's_prefix'        => 'FYHW',
                's_random_length' => 5,
                's_date_string'   => 'ymd',
                's_length'        => 16,
                's_key_unuse'   => 'oversea_shipment_unpool_',
                's_key_seq'     => 'oversea_shipment_char_',
                's_seq_char'    => 2,
                's_init_seq'    => 'AA',
                'interrupt_reload_used' => function($sn_prefix, $cut_length) {
                    $ci = CI::$APP;
                    $ci->load->model('Oversea_shipment_list_model', 'm_oversea_shipment', false, 'shipment');
                    return $ci->m_oversea_shipment->get_today_used_shipment_sns($sn_prefix, $cut_length);
                }
            ],
            'fba_pr'           => [
                's_prefix'        => 'FSS',
                's_random_length' => 5,
                's_date_string'   => 'ymd',
                's_length'        => 16,
                's_key_unuse'   => 'fba_pr_unpool_',
                's_key_seq'     => 'fba_pr_char_',
                's_seq_char'    => 2,
                's_init_seq'    => 'AA',
                'interrupt_reload_used' => function($sn_prefix, $cut_length) {
                    $ci = CI::$APP;
                    $ci->load->model('Fba_pr_list_model', 'fba_pr', false, 'fba');
                    return $ci->fba_pr->get_today_used_pr_sns($sn_prefix, $cut_length);
                }
            ],
            'oversea_pr'       => [
                's_prefix'        => 'GSS',
                's_random_length' => 5,
                's_date_string'   => 'ymd',
                's_length'        => 16,
                's_key_unuse'   => 'oversea_pr_unpool_',
                's_key_seq'     => 'oversea_pr_char_',
                's_seq_char'    => 2,
                's_init_seq'    => 'AA',
                'interrupt_reload_used' => function($sn_prefix, $cut_length) {
                    $ci = CI::$APP;
                    $ci->load->model('Oversea_pr_list_model', 'oversea_pr', false, 'oversea');
                    return $ci->oversea_pr->get_today_used_pr_sns($sn_prefix, $cut_length);
                }
            ],
            //海外备货单号
            'oversea_pur'      => [
                's_prefix'              => 'HBH',
                's_random_length'       => 5,
                's_date_string'         => 'ymd',
                's_length'              => 16,
                's_key_unuse'           => 'oversea_pur_unpool_',
                's_key_seq'             => 'oversea_pur_char_',
                's_seq_char'            => 2,
                's_init_seq'            => 'AA',
                'interrupt_reload_used' => function ($sn_prefix, $cut_length) {
                    $ci = CI::$APP;
                    $ci->load->model('Plan_purchase_list_model', 'oversea_plan_pur', false, 'plan');

                    return $ci->oversea_plan_pur->get_today_used_pur_sns($sn_prefix, $cut_length);
                }
            ],
        ];
    }

    /**
     * 设置订单场景
     *
     * @param unknown $name
     * @throws \InvalidArgumentException
     */
    public function setScene($name)
    {
        $config = static::scene_config()[$name] ?? [];
        if (!$config)
        {
            throw new \InvalidArgumentException(sprintf('获取订单号出现无效的类型名：%s', $name), 3001);
        }
        foreach ($config as $property => $value)
        {
            $this->$property = $value;
        }
        $date = date('m_d');
        $this->today_seq_key = $this->s_key_seq.$date;
        $this->today_unuse_key = $this->s_key_unuse.$date;
        $this->_scene = $name;
        return $this;
    }

    /**
     * 生成全量
     *
     * @return unknown
     */
    private function _general()
    {
        $general_sns = [];
        $max = 10 ** $this->s_random_length - 1 - count($this->_reload_used);
        $seq_char = $this->_get_seq();
        for ($i =1; $i < $max; $i ++)
        {
            $key = str_pad($i, $this->s_random_length, '0', STR_PAD_LEFT);
            if (!isset($this->_reload_used[$key]))
            {
                $general_sns[] = $seq_char.$key;
            }
        }
        shuffle($general_sns);
        return $general_sns;
    }

    /**
     * redis save
     *
     * @param unknown $arr
     * @throws \RuntimeException
     * @return boolean
     */
    private function _save($arr)
    {
        $one_day_second = 24 * 60 * 60;
        $page_size = 50;
        //每次lpush 50个，防止命令过长
        $split_array = array_chunk($arr, 50);
        foreach ($split_array as $k => $part)
        {
            $command = sprintf('lpush %s %s', $this->today_unuse_key, implode(' ', $part));
            if (!$this->_redis->command($command))
            {
                throw new \RuntimeException(sprintf('lpush %s 执行错误', $this->today_unuse_key), 500);
            }
        }
        $this->_redis->command('expire '.$this->today_unuse_key.' '.$one_day_second );
        unset($arr, $split_array);
        $this->_save_clean();
        return true;
    }

    private function _save_clean()
    {
        $this->_reload_used = [];
    }

    /**
     *
     * @return string
     */
    private function _factory()
    {
        $seq_char = $this->_get_seq();
        return implode('', [
                $this->s_prefix,
                date($this->s_date_string, time()),
                $seq_char,
                str_pad((string)mt_rand(0, 10 ** ($this->s_random_length) - 1), $this->s_random_length, '0', STR_PAD_LEFT)
        ]);
    }

    /**
     * 一个大写顺序序列递增
     * @param unknown $seq
     * @return string|unknown
     */
    private function seq_increment($seq)
    {
        if (strlen($seq) != 2)
        {
            throw new \InvalidArgumentException('单号顺序递增字符序列错误', 500);
        }
        $arr = array_reverse(str_split($seq));
        $inc_over = true;
        foreach ($arr as $key => &$value) {
            if ($value >= 'Z')
            {
                $value = 'A';
                $inc_over = false;
            }
            else
            {
                $value = chr(ord($value) + 1);
                $inc_over = true;
                break;
            }
        }
        if (!$inc_over)
        {
            return '';
        }
        return implode('', array_reverse($arr));
    }

    private function _rand_char($length = 2)
    {
        //65-90
        for($i = 1; $i <= $length; $i++)
        {
            $char[] = chr(mt_rand(65, 90));
        }
        return implode('', $char);
    }

    public function get_sn_prefix()
    {
        return $this->s_prefix.date($this->s_date_string);
    }

    /**
     * 重新加载已经消耗完， 获取当前字母序列和已经使用的列表
     *
     * @return boolean
     */
    private function _reload()
    {
        if (!property_exists($this, 'interrupt_reload_used') || !is_callable($this->interrupt_reload_used))
        {
            throw new \BadMethodCallException('请为单号池设置redis异常时重新加载回调', 500);
        }
        $sn_prefix = $this->get_sn_prefix();
        $sn_prefix_length = strlen($sn_prefix) + $this->s_seq_char;
        $today_used = ($this->interrupt_reload_used)($sn_prefix, $this->s_seq_char);
        if (empty($today_used))
        {
            $seq_char = $this->s_init_seq;
        }
        else
        {
            $seq_char =  $today_used[0];
            foreach ($today_used[1] as $key => &$val)
            {
                $val = str_pad(substr($val, $sn_prefix_length), $this->s_random_length, '0', STR_PAD_LEFT);
            }
            $this->_reload_used = array_flip($today_used);
        }
        //获取并设置当前序列
        $this->_redis->command(sprintf('SET %s %s EX %d', $this->today_seq_key, $seq_char, 24 * 3600));
        return true;
    }


    private function _get_seq()
    {
        $exists = $this->_redis->command('exists '.$this->today_seq_key);
        if ($exists == 0)
        {
            $this->_redis->command(sprintf('SET %s %s EX %d', $this->today_seq_key, 'AA', 24 * 3600));
            $this->_redis->command('expire '.$this->today_seq_key.' '.(24 * 3600) );
        }
        return $this->_redis->command('get '.$this->today_seq_key);
    }

    /**
     * 自增长一个序列
     *
     * @throws \OverflowException
     * @return string|unknown
     */
    private function _incr_seq()
    {
        $seq = $this->_get_seq();
        $seq = $this->seq_increment($seq);
        if ($seq === '')
        {
            throw new \OverflowException(sprintf('单号编码今日生成已达上限，需要设计重新扩容'), 500);
        }
        $this->_redis->command(sprintf('SET %s %s EX %d', $this->today_seq_key, $seq, 24 * 3600));
        $this->_redis->command('expire '.$this->today_seq_key.' '.(24 * 3600) );
        return $seq;
    }

    /**
     * 获取今天剩余的单号
     */
    public function today()
    {
        return $this->_redis->command('lrange '.$this->today_unuse_key.' 0 -1');
    }


    public function remain() : int
    {
        return $this->_pool_length();
    }

    /**
     * 验证一个单号
     *
     * @param unknown $sn
     * @return boolean
     */
    public static function is($sn, $scene)
    {
        $config = static::scene_config()[$scene] ?? [];
        if (!$config)
        {
            throw new \InvalidArgumentException(sprintf('验证订单号出现无效的类型名：%s', $scene), 3001);
        }

        return strlen($sn) == $config['s_length'] &&
        substr($sn, 0, strlen($config['s_prefix'])) == $config['s_prefix'] &&
        strtotime(substr($sn, 2, 8));
    }

    private function _pool_length() : int
    {
        return intval($this->_redis->command('llen '.$this->today_unuse_key));
    }

    private function _pop() : string
    {
        $order_sn = $this->_redis->command('rpop '.$this->today_unuse_key);
        if (!$order_sn) throw new \RuntimeException(sprintf('rpop %s 执行错误', $order_sn), 500);
        return $this->get_sn_prefix().$order_sn;
    }

    private function _is_exhaust()
    {
        return $this->_pool_length() == 0 && intval($this->_redis->command('exists '.$this->today_seq_key)) == 1;
    }

    /**
     * 获取一个可用的订单号
     *
     * v1.0.1 之后用空间换时间，先生成全量列表, pop完之后升序字符串序列
     */
    public function pop()
    {
        if (!$this->_scene)
        {
            throw new \RuntimeException(sprintf('必须先设置生成哪种类型的订单号'), 500);
        }
        try
        {
            if (($length = $this->_pool_length()) >= 1)
            {
                $order_sn = $this->_pop();
                //预生成
                if ($length == 1)
                {
                    $this->_incr_seq();
                    $this->_save($this->_general());
                }
            }
            else
            {
                if ($this->_is_exhaust())
                {
                    //增序
                    $this->_incr_seq();
                }
                else
                {
                    //重新加载
                    $this->_reload();
                }
                //重新生成
                $this->_save($this->_general());
                return $this->_pop();
            }
        }
        catch (\OverflowException $e)
        {
            throw new \RuntimeException('今天的单号已经用完', 500);
        }
        catch (\Throwable $e)
        {
            //如果redis挂掉，降级成随机生成，可能有重复，但实际中概率很小
            logger('error', 'redis throw exception', $e->getMessage());
            return $this->_factory();
            //throw new \RuntimeException(sprintf('redis pop order sn error'), 500);
        }

        return $order_sn;
    }

}