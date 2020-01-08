<?php

/**
 * 国内 需求服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Manson
 * @since 2018-12-20
 * @link
 */
class OperationService
{
    public static $s_system_log_name = 'INLAND';

    /**
     * __construct
     */
    public function __construct()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_sales_operation_cfg_model', 'm_operation_cfg', false, 'inland');
        $this->_ci->load->model('Inland_sales_operation_cfg_sku_model', 'm_operation_cfg_sku', false, 'inland');
        $this->_ci->load->helper('inland_helper');
        return $this;
    }

    /**
     * 添加一条备注, 成功为true，否则抛异常， 不做权限
     *
     * @param unknown $params
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return bool true
     */
    public function update_remark($params)
    {
        $gid = $params['gid'];
        $remark = $params['remark'];
        $record = $this->_ci->m_operation_cfg->find_by_pk($gid);
        if (empty($record)) {
            throw new \InvalidArgumentException(sprintf('无效的主键ID'), 412);
        }
        if ($remark == $record['remark']) {
            throw new \InvalidArgumentException(sprintf('新增备注与最新备注相同，无需更新'), 412);
        }
        $this->_ci->load->classes('basic/classes/Record');
        $this->_ci->Record->recive($record);
        $this->_ci->Record->setModel($this->_ci->m_operation_cfg);
        $this->_ci->Record->set('remark', $remark);
//        $this->_ci->Record->set('updated_at', time());
//        $this->_ci->Record->set('updated_uid', get_active_user()->staff_code);

        $db = $this->_ci->m_operation_cfg->getDatabase();

        $db->trans_start();
        $count = $this->_ci->Record->update();
        if ($count !== 1) {
            throw new \RuntimeException(sprintf('国内列表更新备注失败'), 500);
        }
        if (!$this->add_list_remark($params)) {
            throw new \RuntimeException(sprintf('国内列表插入备注失败'), 500);
        }
        $db->trans_complete();

        if ($db->trans_status() === FALSE) {
            throw new \RuntimeException(sprintf('国内添加备注事务提交完成，但检测状态为false'), 500);
        }

        return true;
    }

    /**
     * 添加一条list备注
     *
     * @param unknown $params
     * @return unknown
     */
    public function add_list_remark($params)
    {
        $this->_ci->load->model('Inland_operation_remark_model', 'm_operation_remark', false, 'inland');
        append_login_info($params);
        $insert_params = $this->_ci->m_operation_remark->fetch_table_cols($params);
        return $this->_ci->m_operation_remark->add($insert_params);
    }

    /**
     * 详情
     *
     * @param unknown $gid
     * @return unknown
     */
    public function detail($gid)
    {
        $record = ($pk_row = $this->_ci->load->m_operation_cfg->findByPk($gid)) === null ? [] : $pk_row->toArray();
        $skus = $this->_ci->load->m_operation_cfg_sku->info($gid);
        $skus = implode(',',array_column($skus,'sku'));
        $record['skus'] = $skus;
        return $record;
    }

    public function get_operation_remark($gid, $offset = 1, $limit = 20)
    {
        $this->_ci->load->model('Inland_operation_remark_model', 'm_inland_operation_remark', false, 'inland');
        return $this->_ci->m_inland_operation_remark->get($gid, $offset, $limit);
    }

    /**
     * 新增
     */
    public function add($params)
    {

        $mess = [];
        /*
                //1.先查询该时间段内的信息
                $where = ['set_start_date >=' => $params['set_start_date'], 'set_end_date <=' => $params['set_end_date'], 'is_del'=>'0'];
                $result = $this->_ci->m_operation_cfg->get_info($where);

                //2.拿设置的和在设个时间段的历史记录进行比对
                $temp_platform_code = [];
                if (!empty($result)) {
                    foreach ($result as $key => $value) {
                        $temp_platform_code[] = explode(',', $value['platform_code']);
                    }

                    $arr_platform = explode(',', $params['platform_code']);

                    foreach ($arr_platform as $value) {
                        foreach ($temp_platform_code as $key => $val) {
                            if (in_array($value, $val)) {
                                $mess['errorMess'] = sprintf('%s平台已经在配置中,请勿重复配置',INLAND_PLATFORM_CODE[$value]['name']??'');
                                $mess['code'] = 100;
                                return $mess;
                            }
                        }
                    }
                }

                //1.先查询该时间段外的信息
                $where = ['set_start_date <=' => $params['set_start_date'], 'set_end_date >=' => $params['set_end_date'], 'is_del'=>'0'];
                $result = $this->_ci->m_operation_cfg->get_info($where);

                //2.拿设置的和在设个时间段的历史记录进行比对
                $temp_platform_code = [];
                if (!empty($result)) {
                    foreach ($result as $key => $value) {
                        $temp_platform_code[$key]['platform_code'] = explode(',', $value['platform_code']);
                        $temp_platform_code[$key]['date'] = [
                            'set_start_date' => $value['set_start_date'],
                            'set_end_date' => $value['set_end_date'],
                        ];
                    }

                    $arr_platform = explode(',', $params['platform_code']);

                    foreach ($arr_platform as $value) {
                        foreach ($temp_platform_code as $key => $val) {
                            if (in_array($value, $val['platform_code'])) {
                                if($this->isTimeCross(strtotime($params['set_start_date'].'00:00:00'),strtotime($params['set_end_date'].'23:59:59'),strtotime($val['date']['set_start_date'].'00:00:00'),strtotime($val['date']['set_end_date'].'23:59:59'))){
                                    $mess['errorMess'] = sprintf('%s平台已经在配置中,请勿重复配置33',INLAND_PLATFORM_CODE[$value]['name']??'');
                                    $mess['code'] = 100;
                                    return $mess;
                                };
                            }
                        }
                    }
                }
        */
//        var_dump(date("Y-m-d H:i:s",'1573027953'));exit;
        //方案二:
        //1.先查询该时间段外的信息
//        $result = $this->_ci->m_operation_cfg->check_info_add();
//        //2.拿设置的和在设个时间段的历史记录进行比对
//        $temp_platform_code = [];
//        if (!empty($result)) {
//            foreach ($result as $key => $value) {
//                $temp_platform_code[$key]['platform_code'] = explode(',', $value['platform_code']);
//                $temp_platform_code[$key]['date'] = [
//                    'set_start_date' => $value['set_start_date'],
//                    'set_end_date' => $value['set_end_date'],
//                ];
//            }
//
//            $arr_platform = explode(',', $params['platform_code']);
//
//            foreach ($arr_platform as $value) {
//                foreach ($temp_platform_code as $key => $val) {
//                    if (in_array($value, $val['platform_code'])) {
//                        var_dump(1);
//                        if($this->isTimeCross(strtotime($params['set_start_date'].'00:00:00'),strtotime($params['set_end_date'].'23:59:59'),strtotime($val['date']['set_start_date'].'00:00:00'),strtotime($val['date']['set_end_date'].'23:59:59'))){
//                            $mess['errorMess'] = sprintf('%s平台已经在配置中,请勿重复配置',INLAND_PLATFORM_CODE[$value]['name']??'');
//                            $mess['code'] = 100;
//                            return $mess;
//                        };
//                    }
//                }
//            }
//        }

        $skus = isset($params['skus'])?$params['skus']:"";
        $platform_code = isset($params['platform_code'])?$params['platform_code']:"";
        if (empty($skus) && empty($platform_code)){
            $mess['errorMess'] = "不参与运算sku与不参与运算平台不能同时为空";
            $mess['code'] = 100;
            return $mess;
        }

        $data['gid'] = $this->_ci->m_operation_cfg->gen_id();
        $data['created_uid'] = get_active_user()->staff_code;
        $data['created_zh_name'] = get_active_user()->user_name;
        $data['created_at'] = time();
        $data['set_start_date'] = $params['set_start_date'];
        $data['set_end_date'] = $params['set_end_date'];
        $data['platform_code'] = $platform_code;
        $data['platform_name'] = $this->tran_platform($data['platform_code']);

        $skus = explode(",",$skus);
        $skus_arr = array();
        foreach ($skus as $k => $v){
            if ($v != ""){
                $skus_arr[$k]['cfg_gid'] = $data['gid'];
                $skus_arr[$k]['sku'] = $v;
            }
        }
        //插入销量运算配置
        $insert_operation_cfg = $this->_ci->m_operation_cfg->add_cfg($data,$skus_arr);
        if (!$insert_operation_cfg){
            $mess['errorMess'] = "生成数据失败";
            $mess['code'] = 100;
            return $mess;
        }
        $mess['code'] = 200;
        return $mess;
    }


    /**
     * PHP计算两个时间段是否有交集（边界重叠不算）
     * @param int $beginTime1 开始时间1
     * @param int $endTime1 结束时间1
     * @param int $beginTime2 开始时间2
     * @param int $endTime2 结束时间2
     * @return bool
     */
    public function isTimeCross($beginTime1=0, $endTime1=0, $beginTime2=0, $endTime2=0) {
//        echo $beginTime1.'----'.$endTime1.'---'.$beginTime2.'----'.$endTime2;exit;
        $status = $beginTime2 - $beginTime1;
        if($status > 0){
            $status2 = $beginTime2 - $endTime1;
            if($status2 >= 0){
                return false; // 无交集
            }else{
                return true; // 有交集
            }
        }else{
            $status2 = $endTime2 - $beginTime1;
            if($status2 > 0){
                return true;
            }else{
                return false;
            }
        }
    }


    /**
     *Notes: 导入
     *User: lewei
     *Date: 2019/11/7
     *Time: 16:17
     */
    public function import($param){
        $processed = 0;//已处理
        $undisposed = 0;//未处理
        $mess = [];
        if (empty($param)){
            $mess['code'] = "100";
            $mess['errorMess'] = "无导入数据";
            return $mess;
        }
        //插入运算配置
        $insert_fun = function ($params){
            $skus = isset($params['sku'])?$params['sku']:"";
            $platform_code = isset($params['platform_code'])?$params['platform_code']:"";
            if (empty($skus) && empty($platform_code)){
                $mess['errorMess'] = "不参与运算sku与不参与运算平台不能同时为空";
                $mess['code'] = 100;
                return $mess;
            }

            $data['gid'] = $this->_ci->m_operation_cfg->gen_id();
            $data['created_uid'] = get_active_user()->staff_code;
            $data['created_zh_name'] = get_active_user()->user_name;
            $data['created_at'] = time();
            $data['set_start_date'] = $params['set_start_date'];
            $data['set_end_date'] = $params['set_end_date'];
            $data['platform_code'] = $this->tran_platform_code($platform_code);
            $data['platform_name'] = $this->tran_platform($data['platform_code']);
            $skus = explode(",",$skus);
            $skus_arr = array();
            foreach ($skus as $k => $v){
                if ($v != ""){
                    $skus_arr[$k]['cfg_gid'] = $data['gid'];
                    $skus_arr[$k]['sku'] = $v;
                }
            }
            //插入销量运算配置
            $insert_operation_cfg = $this->_ci->m_operation_cfg->add_cfg($data,$skus_arr);
            $total_skus = count($skus);
            if ($insert_operation_cfg){
                return [$total_skus,0];
            }else{
                return [0,$total_skus];
            }

        };
        //上传插入数据
        foreach ($param as $v){
            list($v_processed,$v_undisposed) = $insert_fun($v);
            $processed += $v_processed;
            $undisposed += $v_undisposed;
        }
        $mess['code'] = 200;
        $mess['processed'] = $processed;
        $mess['undisposed'] = $undisposed;
        return $mess;

    }


    public function tran_platform_code($platform_arr){
        //获取平台
        $ci = CI::$APP;
        $ci->load->service('basic/DropdownService');
        $ci->dropdownservice->setDroplist(['inland_platform_code']);
        $platform = $ci->dropdownservice->get()['inland_platform_code'];
        $platform = array_flip($platform);
        $platform_arr = explode(',',$platform_arr);
        $platform_code = [];
        foreach ($platform_arr as $value){
            $platform_code[] = $platform[$value]??'-';
        }
        $platform_name = implode(',', $platform_code);
        return $platform_name;
    }


    /**
     * 将platform_code转platform_name
     * @param $platform_code
     * @return array|string
     */
    public function tran_platform($platform_code)
    {
        $platform_code = explode(',', $platform_code);
        $platform_name = [];
        foreach ($platform_code as $value) {
            $platform_name[] = INLAND_PLATFORM_CODE[$value]['name']??'-';
        }
        $platform_name = implode(',', $platform_name);
        return $platform_name;
    }

    /**
     * 修改
     */
    public function update($params)
    {
        $mess = [];
//        //1.先查询该时间段外的信息
//        $result = $this->_ci->m_operation_cfg->check_info_update($params['gid']);
//        //2.拿设置的和在设个时间段的历史记录进行比对
//        $temp_platform_code = [];
//        if (!empty($result)) {
//            foreach ($result as $key => $value) {
//                $temp_platform_code[$key]['platform_code'] = explode(',', $value['platform_code']);
//                $temp_platform_code[$key]['date'] = [
//                    'set_start_date' => $value['set_start_date'],
//                    'set_end_date' => $value['set_end_date'],
//                ];
//            }
//
//            $arr_platform = explode(',', $params['platform_code']);
//
//            foreach ($arr_platform as $value) {
//                foreach ($temp_platform_code as $key => $val) {
//                    if (in_array($value, $val['platform_code'])) {
//                        if($this->isTimeCross(strtotime($params['set_start_date'].'00:00:00'),strtotime($params['set_end_date'].'23:59:59'),strtotime($val['date']['set_start_date'].'00:00:00'),strtotime($val['date']['set_end_date'].'23:59:59'))){
//                            $mess['errorMess'] = sprintf('%s平台已经在配置中,请勿重复配置',INLAND_PLATFORM_CODE[$value]['name']??'');
//                            $mess['code'] = 100;
//                            return $mess;
//                        };
//                    }
//                }
//            }
//        }
        $skus = isset($params['skus'])?$params['skus']:"";
        $platform_code = isset($params['platform_code'])?$params['platform_code']:"";
        if (empty($skus) && empty($platform_code)){
            $mess['errorMess'] = "不参与运算sku与不参与运算平台不能同时为空";
            $mess['code'] = 100;
            return $mess;
        }

        $data['gid'] = $params['gid'];
        $data['updated_uid'] = get_active_user()->staff_code;
        $data['updated_zh_name'] = get_active_user()->user_name;
        $data['updated_at'] = time();
        $data['set_start_date'] = $params['set_start_date'];
        $data['set_end_date'] = $params['set_end_date'];
        $data['platform_code'] = $platform_code;
        $data['platform_name'] = $this->tran_platform($data['platform_code']);

        $skus = explode(",",$skus);
        $skus_arr = array();
        foreach ($skus as $k => $v){
            if ($v != ""){
                $skus_arr[$k]['cfg_gid'] = $data['gid'];
                $skus_arr[$k]['sku'] = $v;
            }
        }
        $this->_ci->m_operation_cfg->update_cfg($data,$skus_arr);
        //写入日志
        $this->_ci->load->service('inland/OperationLogService');
        $context = '修改 不参与运算时间: '.$data['set_start_date'].'——'.$data['set_end_date'].' 不参与运算平台'.str_replace(',','、',$data['platform_name']);

        $this->_ci->operationlogservice->send(['gid'=>$data['gid']],$context);

        $mess['code'] = 200;
        return $mess;
    }

    /**
     * 批量删除
     */
    public function batch_delete($params)
    {
        $data = [];
        $success = 0;
        $fail = 0;
        $gid_arr = explode(',', $params['gid']);
        $total = count($gid_arr);
        foreach ($gid_arr as $key => $gid) {
            $result = $this->_ci->m_operation_cfg->batch_delete($gid);
            if (!empty($result)) {
                //写入日志
                $this->_ci->load->service('inland/OperationLogService');
                $context = '此记录被删除';

                $this->_ci->operationlogservice->send(['gid'=>$gid],$context);
                $success++;
                continue;
            }
            $fail++;
        }
        $data['total'] = $total;//总操作数
        $data['success'] = $success;//删除成功的数量
        $data['fail'] = $fail;//删除失败的数量
        return $data;
    }


}
