<?php

/**
 * Push_log_model
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @link
 */
class Push_log_model extends MY_Model
{
    /**
     * 汇总推送到备货
     *
     * @var string
     */
    private $cons_type_summary = 'summary';

    /**
     * 背会推送的采购
     *
     * @var string
     */
    private $cons_type_purchase = 'purchase';

    /**
     * 成功
     *
     * @var integer
     */
    private $cons_state_succ = 1;

    /**
     * 失败
     *
     * @var integer
     */
    private $cons_state_fail = 2;

    public function __construct()
    {
        $this->database = 'stock';
        $this->table_name = 'yibai_push_log';
        $this->primaryKey = 'id';
        parent::__construct();
    }

    /**
     *
     * @param array $params
     * @return bool
     */
    public function add($params)
    {
        return $this->_db->insert($this->table_name, $params);
    }

    /**
     * 获取当天业务线推送成功的记录
     *
     * @param int $busniess_line
     * @return array
     */
    public function get_today_summary_pushed($busniess_line) : array
    {
        $today_start = date('Y-m-d').' 00:00:00';
        $today_end = date('Y-m-d').' 23:59:59';

        $result = $this->_db->from($this->table_name)
        ->where("created_at between '{$today_start}' and '{$today_end}' ")
        ->where('type', $this->cons_type_summary)
        ->where('state', $this->cons_state_succ)
        ->where('business_line', strval($busniess_line))
        ->get()
        ->result_array();

        return $result;
    }

    /**
     * 获取推送采购系统的推送成功记录
     *
     * @return array
     */
    public function get_today_purchase_pushed_rows() : array
    {
        $today_start = date('Y-m-d').' 00:00:00';
        $today_end = date('Y-m-d').' 23:59:59';

        $result = $this->_db->from($this->table_name)
        ->where("created_at between '{$today_start}' and '{$today_end}' ")
        ->where('type', $this->cons_type_purchase)
        ->where('state', $this->cons_state_succ)
        ->get()
        ->result_array();

        return $result;
    }

    /**
     * 检测指定汇总列表业务线是否已经推送
     *
     * @param int $busniess_line
     * @return boolean
     */
    public function is_summary_pushed($busniess_line)
    {
        return empty($this->get_today_summary_pushed($busniess_line)) ? false : true;
    }

    /**
     * 获取已推送到采购的信息
     *
     * @param array|string $lines 1,2,3 | [1, 3]
     * @return array ['pushed' => [2,3], 'unpush' => [1]]
     */
    public function get_today_purchase_pushed() : array
    {
        $all_lines = array_keys(BUSINESS_LINE);
        $pushed_info = $this->get_today_purchase_pushed_rows();
        if (empty($pushed_info)) {
            return ['pushed' => [], 'unpush' => $all_lines];
        }
        $pushed_lines = [];
        foreach (array_column($pushed_info, 'business_line') as $pl) {
            $pushed_lines = array_merge($pushed_lines, explode(',', $pl));
        }

        return ['pushed' => array_intersect($pushed_lines, $all_lines), 'unpush' => array_diff($pushed_lines, $all_lines)];
    }

    /**
     * 检测业务线是否已经上传
     *
     * @param string|array $lines
     * @return array[]
     */
    public function check_purchase_pushed($lines)
    {
        $lines = is_array($lines) ? $lines : explode(',', $lines);
        $info = $this->get_today_purchase_pushed();
        return ['pushed' => array_intersect($lines, $info['pushed']), 'unpush' => array_intersect($lines, $info['unpush'])];
    }

    /**
     * 添加一个推送日志
     *
     * @param int $code
     * @param string $business_line
     * @param string $errorMsg
     * @return unknown
     */
    public function add_push_summary($code, $business_line, $errorMsg = '')
    {
        $active_user = get_active_user();
        $params = [
            'uid' => $active_user->staff_code,
            'user_name' => $active_user->user_name,
            'type' => $this->cons_type_summary,
            'business_line' => $business_line,
            'state' => $code == 200 ? $this->cons_state_succ : $this->cons_state_fail,
            'errormsg' => $code == 200 ? '' : $errorMsg
        ];
        return $this->_db->insert($this->table_name, $params);
    }

    public function add_push_purchase($code, $business_line, $errorMsg = '')
    {
        $active_user = get_active_user();
        $params = [
                'uid' => $active_user->staff_code,
                'user_name' => $active_user->user_name,
                'type' => $this->cons_type_purchase,
                'business_line' => $business_line,
                'state' => $code == 200 ? $this->cons_state_succ : $this->cons_state_fail,
                'errormsg' => $code == 200 ? '' : $errorMsg
        ];
        return $this->_db->insert($this->table_name, $params);
    }

}