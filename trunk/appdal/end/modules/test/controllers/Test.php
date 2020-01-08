<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Test, 测试用例, 本ctrl用来执行测试用例， 正式环境中请去除此文件或者不加入版本库
 *
 * @author Jason
 * @since 2018-12-20
 */
class Test extends MY_Controller {

    /**
     * depend test
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function Test()
    {

        $this->load->model('Export_log_model', 'm_export_log', false, 'basic');
        $this->m_export_log->add(['amount' => 23434, 'created_start' => date('Y-m-d H:i:s'), 'created_end' => date('Y-m-d H:i:s')]);
        exit;


        $this->load->service('basic/DropdownService');
        //$dropdown = $this->dropdownservice->dropdown_manager_list();

        $this->dropdownservice->setDroplist(['manager_list']);
        $dropdown = $this->dropdownservice->get();;

        $this->load->model('Fba_manager_account_model', 'fba_manager_account', false, 'fba');
        pr($accounts = $this->fba_manager_account->get_all_accounts());
        pr(array_column($accounts, 'account_name'));

        exit;

        //测试修改pr
        $this->load->classes('fba/classes/PrState');
        $result = $this->PrState->from(APPROVAL_STATE_FIRST)->go(APPROVAL_STATE_SECOND)->can_jump();

        $result = $this->PrState->from(APPROVAL_STATE_FIRST)->can_action('edit_pr_listing', ['approve_state' => APPROVAL_STATE_FIRST]);

        var_dump($result);
        exit;

        /*$this->load->service('basic/SystemLogService');
        $result = $this->systemlogservice->send([], 'test', '这是一条测试');*/

        /*$this->load->service('basic/DropdownService');
        $this->dropdownservice->setDroplist(['fba_approval_state']);
        $dropdown = $this->dropdownservice->get();*/

        $params = $this->compatible('get');
        $this->load->service('fba/PrListService');

        $params = [
                'sale_group' => '1',
                'salesman' => '2',
                'account_name' => 'zhangwfd',
                'station_code' => 'US',
                'approve_state' => '1',
                'is_trigger_pr' => '1',
                'is_plan_approve' => '1',
                'expired' => '1',
                'sku' => 'KS125409, KS125432',
                'fnsku' => 'X000SRLZ89 X000SRLZ83',
                'asin' => 'nB01HQDQ8P, B01HQDQ82,,,,',
                'start_date' => '2019-03-01',
                'end_date' => '2019-03-03',
                'sc_day' => 32,
                'offset' => 1,
                'limit' => 5,
                'export_save' => true,
        ];
        $this->prlistservice->setSearchParams($params);
        //过滤hook
        $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_filter_params'), ['input' => $this->prlistservice->search_params, 'update' => 'search_params']);
        //参数处理hook
        $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_translate_params'), ['input' => &$this->prlistservice->search_params, 'update' => 'search_params']);
        //参数转换
        $this->prlistservice->setPreSearchHook(array($this->prlistservice, 'hook_format_params'), ['input' => &$this->prlistservice->search_params, 'update' => 'search_params']);

        //返回数据处理
        $this->prlistservice->setAfterSearchHook(array($this->prlistservice, 'translate'), ['input' => 'return', 'update' => 'none']);

        $return = $this->prlistservice->execSearch();

        pr($return);

    }

    /**
     * @author Jason
     */
    public function Jason()
    {
        $this->load->library('unit_test');

        //测试单个服务是否能够加载
        $this->load->service('UserService');
        $this->unit->run(get_class($this->userservice), 'UserService', sprintf('测试用例：%s', 'server加载'), '基础模块');

        $this->load->service(['UserService', 'basic/DropdownService']);
        $this->unit->run(get_class($this->userservice).'_'.get_class($this->dropdownservice), 'UserService_DropdownService', sprintf('测试用例：%s', '多server类加载'), '基础模块');

        //测试is_login
        $this->unit->run(false, is_login(), sprintf('测试用例：%s', 'is_login 接入用户为true， 否则为false', '用户模块'));
        $this->unit->run(true, is_system_call(),sprintf('测试用例：%s', 'is_system_call 接入用户为false， 否则为true', '用户模块'));
        //$this->unit->run(false, api_login(),sprintf('测试用例：%s', 'api_login api未接入目前未false', '用户模块'));
        $this->unit->run(false, is_api_user(),sprintf('测试用例：%s', 'is_api_user api未接入目前未false', '用户模块'));

        //获取用户信息
        $active_user = get_active_user();
        $this->unit->run('SystemUser', get_class($active_user), sprintf('测试用例：%s', 'get_active_user() 获取用户对象， 没有接入用户为SystemUser'), '用户模块' );

        $servier_user = $this->userservice->getActiveUser();
        $this->unit->run($active_user, $servier_user, sprintf('测试用例：%s', '$this->user_service->getActiveUser() 必须和$active_user()函数为同一个对象'), '用户模块');

        //-------------OrderSnPoolService-----------------------//
        $this->load->service('basic/OrderSnPoolService', null, 'pool');

        try {
            $this->pool->setScene('suggest');
        }
        catch (Exception $e)
        {
            $this->unit->run('InvalidArgumentException', get_class($e), sprintf('测试用例：%s', 'setScene错误场景名称'), 'OrderSnPoolService');
        }

        /**
         * Record测试用例
         */
        $this->load->classes('basic/classes/Record');
        $this->unit->run(true, $this->Record, sprintf('测试用例：%s', 'Record类对象创建成功'), 'Record类');

        $this->load->model('FBA_global_rule_cfg_model', 'fba_model', false, 'fba');
        $record = $this->fba_model->findByPk('UK')->toArray();
        $this->Record->recive($record);
        $this->unit->run($record, $this->Record->get(), sprintf('测试用例：%s', 'Record->recive()'), 'Record类');

        //不开启外部模式
        try
        {
            $this->Record->set('new_property', 'foo');
        }
        catch (Exception $e)
        {
            $this->unit->run('RuntimeException', get_class($e), sprintf('测试用例：%s', 'Record->_enable_extra_property'), 'Record类');
        }

        $this->Record->set('as_up', 12);
        $this->unit->run(12, $this->Record->get('as_up'), sprintf('测试用例：%s', '字符串 Record->set, Record->get'), 'Record类');

        //pr($this->Record->get());exit;

        $this->Record->set('ls_train', ['abc']);
        $this->Record->set('ls_train', 0, 'bcd');
        $this->unit->run('bcd', $this->Record->get('ls_train', 0), sprintf('测试用例：%s', '数组 Record->set, Record->get'), 'Record类');

        $this->unit->run(true, $this->Record->has('ls_red'), sprintf('测试用例：%s', 'Record->has'), 'Record类');
        $this->unit->run(false, $this->Record->has('ls_black'), sprintf('测试用例：%s', 'Record->has'), 'Record类');

        //silent_get
        $this->unit->run(null, $this->Record->silent_get('ls_black'), sprintf('测试用例：%s', 'Record->silent_get'), 'Record类');
        $this->unit->run('bcd', $this->Record->silent_get('ls_train', 0), sprintf('测试用例：%s', 'Record->silent_get'), 'Record类');

        //report
        $this->unit->run(2, $this->Record->report($this->Record::REPORT_ONLY_NUMS), sprintf('测试用例：%s', 'Record->report::REPORT_ONLY_NUMS'), 'Record类');
        $this->unit->run(['as_up', 'ls_train'], $this->Record->report($this->Record::REPORT_ONLY_COLS), sprintf('测试用例：%s', 'Record->report::REPORT_ONLY_COLS'), 'Record类');

        $expect = array ( 'as_up' => array ( 'before' => '10', 'after' => 12, ), 'ls_train' => array ( 'before' => '10', 'after' => array ( 0 => 'bcd', ), ), );
        $this->unit->run($expect, $this->Record->report($this->Record::REPORT_FULL_ARR), sprintf('测试用例：%s', 'Record->report::REPORT_FULL_ARR'), 'Record类');

        $this->Record->set('ls_train', 11);
        $this->Record->setModel($this->fba_model);
        $count = $this->Record->update();
        $this->unit->run(1, $count, sprintf('测试用例：%s', 'Record->update'), 'Record类');

        $this->unit->run(1, $this->Record->reback(), sprintf('测试用例：%s', 'Record->reback'), 'Record类');

        $this->Record->mset($this->Record->origin());
        $this->unit->run(false, $this->Record->has_change(), sprintf('测试用例：%s', 'Record->origin， has_change'), 'Record类');

        /**
         * dropdown 测试用例
         */
        $this->load->service('basic/DropdownService');

        //dropdown_fba_approval_state
        $approval_state = $this->dropdownservice->dropdown_fba_approval_state();
        $this->unit->run(true, isset($approval_state[APPROVAL_STATE_FIRST]), sprintf('测试用例：%s', 'dropdown_fba_approval_state'), 'dropdown - fba下拉列表 - 审核状态');
        $this->unit->run('待三级审核', $approval_state[APPROVAL_STATE_THREE], sprintf('测试用例：%s', 'dropdown_fba_approval_state'), 'dropdown - fba下拉列表 - 审核状态');

        $this->dropdownservice->setDroplist(['fba_approval_state']);
        $dropdown = $this->dropdownservice->get();
        $this->unit->run($approval_state, $dropdown['fba_approval_state'], sprintf('测试用例：%s', 'setDroplist'), 'dropdown - fba下拉列表 - 审核状态');

        //dropdown_fba_trigger_pr
        $trigger_pr = $this->dropdownservice->dropdown_fba_trigger_pr();
        $this->unit->run(true, isset($trigger_pr[TRIGGER_PR_YES]), sprintf('测试用例：%s', 'dropdown_fba_trigger_pr'), 'dropdown - fba下拉列表 - 是否触发pr状态');
        $this->unit->run('N', $trigger_pr[TRIGGER_PR_NO], sprintf('测试用例：%s', 'dropdown_fba_trigger_pr'), 'dropdown - fba下拉列表 - 是否触发pr');

        $this->dropdownservice->setDroplist(['fba_trigger_pr']);
        $dropdown = $this->dropdownservice->get();
        $this->unit->run($trigger_pr, $dropdown['fba_trigger_pr'], sprintf('测试用例：%s', 'fba_trigger_pr'), 'dropdown - fba下拉列表 - 审核状态');

        //dropdown_fba_plan_approval
        $plan_approval = $this->dropdownservice->dropdown_fba_plan_approval();
        $this->unit->run(true, isset($plan_approval[NEED_PLAN_APPROVAL_YES]), sprintf('测试用例：%s', 'dropdown_fba_plan_approval'), 'dropdown - fba下拉列表 - 是否计划审核');
        $this->unit->run('N', $plan_approval[TRIGGER_PR_NO], sprintf('测试用例：%s', 'dropdown_fba_plan_approval'), 'dropdown - fba下拉列表 - 是否计划审核');

        $this->dropdownservice->setDroplist(['fba_plan_approval']);
        $dropdown = $this->dropdownservice->get();
        $this->unit->run($plan_approval, $dropdown['fba_plan_approval'], sprintf('测试用例：%s', 'fba_trigger_pr'), 'dropdown - fba下拉列表 - 是否计划审核');


        /**
         * fba_helper
         */
        $this->load->helper('fba_helper');
        $this->unit->run(true, is_valid_approval_state(APPROVAL_STATE_SECOND), sprintf('测试用例：%s', 'is_valid_approval_state'), 'fba_helper');
        $this->unit->run(false, is_valid_approval_state(-1), sprintf('测试用例：%s', 'is_valid_approval_state'), 'fba_helper');

        $this->unit->run(true, is_valid_trigger_pr(TRIGGER_PR_NO), sprintf('测试用例：%s', 'is_valid_trigger_pr'), 'fba_helper');
        $this->unit->run(false, is_valid_trigger_pr(-1), sprintf('测试用例：%s', 'is_valid_trigger_pr'), 'fba_helper');

        $this->unit->run(true, is_valid_plan_approval(NEED_PLAN_APPROVAL_YES), sprintf('测试用例：%s', 'dropdown_fba_plan_approval'), 'fba_helper');
        $this->unit->run(false, is_valid_plan_approval(-1), sprintf('测试用例：%s', 'dropdown_fba_plan_approval'), 'fba_helper');


        //remark更新接口

        //先获取一条记录
        $this->load->model('Fba_pr_list_model', 'fba_pr_list', false, 'fba');
        $db = $this->fba_pr_list->getDatabase();
        $row = $db->from($this->fba_pr_list->getTable())->order_by('gid', 'desc')->limit(1)->get()->result_array();
        $params = [
                'gid' => $row[0]['gid'],
                'remark' => '这是一条新的备注, 由测试用例产生。'
        ];
        $this->load->service('fba/PrService');
        $count = $this->prservice->update_remark($params);
        $this->unit->run(1, $count, sprintf('测试用例：%s', 'update_remark'), 'prservice模块');

        /**
         * 测试系统流水操作日志
         */
        $this->load->service('basic/SystemLogService');
        $result = $this->systemlogservice->send([], 'test', '这是一条测试, 由测试用例产生。');
        $this->unit->run(true, $result, sprintf('测试用例：%s', 'send发送一条日志'), 'SystemLogService');

        $this->load->classes('fba/classes/PrState');
        $result = $this->PrState->from(APPROVAL_STATE_FIRST)->go(APPROVAL_STATE_SECOND)->can_jump();
        $this->unit->run(true, $result, sprintf('测试用例：%s', 'can_jump'), 'PrState类定义');

        $result = $this->PrState->from(APPROVAL_STATE_FIRST)->go(APPROVAL_STATE_SUCCESS)->can_jump();
        $this->unit->run(false, $result, sprintf('测试用例：%s', 'can_jump'), 'PrState类定义');

        $result = $this->PrState->from(APPROVAL_STATE_FIRST)->go(APPROVAL_STATE_FAIL)->can_jump();
        $this->unit->run(true, $result, sprintf('测试用例：%s', 'can_jump'), 'PrState类定义');

        /**
         * 测试审核
         */
        $db = $this->fba_pr_list->getDatabase();
        $row = $db->from($this->fba_pr_list->getTable())
        ->where('approve_state', 102)
        ->order_by('gid', 'desc')->limit(1)->get()->result_array();

        if (!empty($row))
        {
            $this->unit->run(1, count($this->prservice->detail($gid)), sprintf('测试用例：%s', 'detail'), 'prservice模块');

            $reback_params = [
                    'gid' => $row[0]['gid'],
                    'bd' => $row[0]['bd']
            ];

            $sub_params = [
                    'gid' => $row[0]['gid'],
                    'bd'  => $row[0]['bd'] < 0 ? $row[0]['bd'] - 5 : -5,
            ];
            $add_params = [
                    'gid' => $row[0]['gid'],
                    'bd'  => $row[0]['bd'] > 0 ? $row[0]['bd'] + 6 : 7,
            ];

            $this->load->service('fba/PrService');
            $result = $this->prservice->edit_pr_listing($sub_params);
            $this->unit->run(true, $result, sprintf('测试用例：%s', 'edit_pr_listing'), 'prservice模块');

            $result = $this->prservice->edit_pr_listing($add_params);
            $this->unit->run(true, $result, sprintf('测试用例：%s', 'edit_pr_listing'), 'prservice模块');

            $record = $this->fba_pr_list->findByPk($sub_params['gid'])->toArray();
            $this->unit->run(NEED_PLAN_APPROVAL_YES, $record['is_plan_approve'], sprintf('测试用例：%s', 'edit_pr_listing'), 'prservice模块');

            $sub_overflow_params = [
                    'gid' => $row[0]['gid'],
                    'bd'  =>  -1 * $row[0]['purchase_qty'] - 3
            ];

            try {
                $result = $this->prservice->edit_pr_listing($sub_overflow_params);
            }
            catch (Exception $e)
            {
                $this->unit->run('InvalidArgumentException', get_class($e), sprintf('测试用例：%s', 'edit_pr_listing 减少数量超限'), 'prservice模块');
            }

            //将继续修改回来
            $result = $this->prservice->edit_pr_listing($reback_params);
            $record = $this->fba_pr_list->findByPk($sub_params['gid'])->toArray();
            $this->unit->run($row[0]['bd'], $record['bd'], sprintf('测试用例：%s', 'edit_pr_listing'), 'prservice模块');
            $this->unit->run($row[0]['is_plan_approve'], $record['is_plan_approve'], sprintf('测试用例：%s', 'edit_pr_listing'), 'prservice模块');



        }


        echo $this->unit->report();

    }

    /**
     *
     */
    public function api()
    {
        $this->api_jason_example();
    }


    /**
     * api 使用范例
     */
    private function api_jason_example()
    {
        $this->load->service('UserService');

        $info = array (
                'uid' => '1',
                'login_name' => 'admin',
                'user_name' => '系统管理员',
                'session_id' => 'c6cc883a7d7d301d12b616811be6ef70',
        );
        $this->userservice::login($info);

        //测试正常登录用户
        $login_user = get_active_user();

        //获取用户信息数组
        $info = $login_user->get_user_info();

        pr($info);

        //获取属性 - 直接属性访问
        $uid = $login_user->uid;
        $login_name = $login_user->login_name;
        $user_name = $login_user->user_name;

        //获取属性 - get获取
        $uid = $login_user->get('uid');
        $session_id = $login_user->get('session_id');
        pr($session_id);

        //获取多个属性
        $attrs = $login_user->get(['uid', 'session_id']);

        //获取全部属性
        $all = $login_user->get();
        pr($all);

        //设置属性
        $login_user->uid = 3;
        $login_user->user_name = 'test';
        pr($login_user->user_name);

        //设置属性
        $login_user->set('login_name', 'new_login_name');
        pr($login_user->login_name);

        //获取变更情况
        pr($login_user->report());

        //推送用户信息, 暂无需求，未实现
        $login_user->push();

        //清空登录信息
        $login_user->logout();

        //api用户信息
        //根据用户id获取用户信息
        $info = array (
                'uid' => '100',
                'login_name' => 'admin',
                'user_name' => '系统管理员',
                'session_id' => 'c6cc883a7d7d301d12b616811be6ef70',
        );
        $this->userservice::login($info);
        global $g_api_login;
        $g_api_login = true;

        $api_user = get_active_user($refresh = true);
        pr(get_class($api_user));

        $api_user->login_name = '修改用户名';
        //获取变更情况
        pr($api_user->report());

        //清空登录信息
        $api_user->logout();


        $this->userservice::login();
        global $g_system_login;
        $g_system_login = true;

        $system_user = get_active_user($refresh = true);

        //获取system_user自定义属性
        pr($system_user->get_user_info());
        pr(get_class($system_user));
        pr($system_user->get_plan_managers());
    }

    public function db_dict()
    {
        $this->load->model('Fba_amazon_account_model', 'sku_cfg', false, 'fba');
        $db = $this->sku_cfg->getDatabase();
        $all_tables = $db->list_tables();

        $title = ['', '字段', '类型', '空', '默认','注释', ''];
        $splite = ['', ':----', ':-------', ':---', '-- -', '------', ''];

        foreach ($all_tables as $table)
        {
            $result = $db->query('show create table '.$table)->result_array();
            $ddl = $result[0]['Create Table'];
            //pr($ddl);

            $left_start = strpos($ddl, '(');
            $right_end = strpos($ddl, 'PRIMARY KEY');

            $ddl_column = substr($ddl, $left_start, $right_end - $left_start);

            //pr($ddl_column);
            preg_match_all('/`([^\`]+)`/', $ddl_column, $matches);
            preg_match_all('/COMMENT \'([^\']+)\'/', $ddl_column, $comment_matches);
            preg_match_all('/COMMENT=\'([^\']+)\'/', substr($ddl, $right_end), $matches_table);

            $columns = $matches[1];
            if (count($columns) != count($comment_matches[1]))
            {
                $comment = array_merge(['主键'], $comment_matches[1]);
            }
            else
            {
                $comment = $comment_matches[1];
            }
            $col_comment_map = array_combine($columns, $comment);
            $table_comment = $matches_table[1][0];

            echo $table.' -- '.$table_comment.'<br/>';
            echo '<br/>';

            echo implode('|', $title).'<br/>';
            echo implode('|', $splite).'<br/>';
            $fields = $db->field_data($table);
            foreach ($fields as $fi)
            {
                $msg = '';
                if ($fi->primary_key == 1) {
                    $msg = '主键，自增';
                }
                $tmp[] = '';
                $tmp[] = $fi->name;
                $tmp[] = $fi->type;
                $tmp[] = '否';
                $tmp[] = $fi->default;
                $tmp[] = '注释：'.$msg.$col_comment_map[$fi->name];
                $tmp[] = '';

                echo implode('|', $tmp).'<br/>';
                $tmp = [];
            }

            echo '<br/>--------CREATE DDL--------------<br/>';

            pr($ddl);
        }
    }
}
/* End of file Dict.php */
/* Location: ./application/modules/basic/controllers/Dict.php */