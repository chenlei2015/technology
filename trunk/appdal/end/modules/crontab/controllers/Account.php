<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/18
 * Time: 10:39
 */

class Account extends MX_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->stock   = $this->load->database('stock', TRUE);//计划系统数据库
        $this->_debug = $this->input->post_get('debug') ?? '';
    }

    /**
     * 获取 Amazon 平台账号 每隔2小时更新一次
     *  crontab/account/fetch_amazon_account
     */
    public  function fetch_amazon_account(){
        $flag = true;
        $today = date('Y-m-d 00:00:00');
        $this->load->model('Platform_account_model', 'm_platform_account', false, 'fba');
        $this->load->model('Fba_amazon_account_model', 'm_amazon_account', false, 'fba');
        do{
            $sync_time = time();
            $accounts = $this->stock->select(['id','account_name','short_name','status'])
            ->where('sync_time <',strtotime($today))
            ->limit(50)
            ->get($this->m_amazon_account->getTable())->result_array();
            if(empty($accounts)){
                $flag = false;
            }else{
                foreach ($accounts as $key => $account){
                    $data = [];
                    $row = $this->stock->select('*')
                    ->where(['account_name'=>$account['account_name'],'platform_code'=>'amazon'])
                    ->get($this->m_platform_account->getTable())->row_array();
                    if(empty($row)){
                        //新增
                        $data['platform_code'] = 'AMAZON';
                        $data['account_name'] = $account['account_name'];
                        $data['short_name'] =  $account['short_name'];
                        $data['status'] =  $account['status'];
                        $data['created_at'] = time();
                        $data['updated_at'] = time();
                        $this->stock->insert($this->m_platform_account->getTable(),$data);
                    }else{
                        //修改
                        $data['short_name'] =  $account['short_name'];
                        $data['status'] =  $account['status'];
                        $data['updated_at'] = time();
                        $this->stock->where(['platform_code'=>'amazon','account_name'=>$account['account_name']])->update($this->m_platform_account->getTable(),$data);
                    }
                }
                //更新同步时间
                $ids = array_column($accounts,'id');
                $this->stock->where_in('id',$ids)->update($this->m_amazon_account->getTable(),['sync_time'=>$sync_time]);
            }
        }while($flag);
    }
     /**
      * 获取除了Amazon平台以外其他平台账号 每隔2小时更新一次
      * crontab/account/fetch_other_account
      */
    public  function fetch_other_account(){
        $this->load->model('Platform_account_model', 'm_platform_account', false, 'fba');
        $params = [];
        //$params =['access_token'=>'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJyZWFkIl0sImV4cCI6MTU3NzM0NDcyMywiYXV0aG9yaXRpZXMiOlsiMTAiXSwianRpIjoiODQxZDE0ZTUtZTE4Ni00NjdhLTkwZTMtYjY3ODM5M2FlYWMwIiwiY2xpZW50X2lkIjoidGVzdCJ9.hlG_rqXWDNLip-nyxUXYjk1GUNGyjmVLMmz0blVteMc'];
        $platform_codes = $this->m_platform_account->java_getPlatformCode($params);
        foreach ($platform_codes as $platform_code){
            $code = $platform_code['platformCode'];
            if($code == 'AMAZON') continue;
            $accounts = $this->m_platform_account->java_getAccountNameByPlatformCode(['platformCode'=>$code]);
            //$accounts = $this->m_platform_account->java_getAccountNameByPlatformCode(['platformCode'=>$code,'access_token'=>'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJzY29wZSI6WyJyZWFkIl0sImV4cCI6MTU3NzM0NDcyMywiYXV0aG9yaXRpZXMiOlsiMTAiXSwianRpIjoiODQxZDE0ZTUtZTE4Ni00NjdhLTkwZTMtYjY3ODM5M2FlYWMwIiwiY2xpZW50X2lkIjoidGVzdCJ9.hlG_rqXWDNLip-nyxUXYjk1GUNGyjmVLMmz0blVteMc']);
            foreach ($accounts as $key => $account){
                if(!isset($account['accountName'])) continue;
                $data = [];
                $row = $this->stock->select('*')
                    ->where(['account_name'=>$account['accountName'],'platform_code'=>$code])
                    ->get($this->m_platform_account->getTable())->row_array();
                if(empty($row)){
                    //新增
                    $data['platform_code'] = $code;
                    $data['account_name'] = $account['accountName'];
                    $data['short_name'] =  $account['shortName']??'';
                    $data['status'] =  $account['status']??0;
                    $data['created_at'] = time();
                    $data['updated_at'] = time();
                    $this->stock->insert($this->m_platform_account->getTable(),$data);
                }else{
                    $update_date = date('Y-m-d',$row['updated_at']);
                    if($update_date === date('Y-m-d')) continue;
                    //修改
                    $data['short_name'] =  $account['shortName'];
                    $data['status'] =  $account['status'];
                    $data['updated_at'] = time();
                    $this->stock->where(['account_name'=>$account['accountName'],'platform_code'=>$code])->update($this->m_platform_account->getTable(),$data);
                }
            }
        }
    }



}
