<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 * Class Login
 * @desc 用户第三方登录
 * @author zhut
 * @since 20181217
 */
class Login extends MX_Controller {

    protected $data;

    public function __construct()
    {
        parent::__construct();
        $this->data['status'] = 0;
        $this->load->model('Login_model','login');
        $this->load->library(array('asset','cookie','rediss'));
    }

    /**
     * 获取用户权限
     */
    public function getUserInfo(){
        $action_token = $this->input->post_get('access_token');

        $userData = $this->cookie->getData('userdata');

        if(empty($action_token) && isset($userData['uid']) && !empty($userData['uid'])) {
            $authData = $this->rediss->getData($userData['uid']);
            if(isset($authData['session_id']) && !empty($authData['session_id'])) {
                if($authData['session_id'] != $userData['session_id']) {
                    $this->cookie->deleteData('userdata');
                    http_response(array(
                        'status'=>0,
                        'errorCode'=>3047,
                        'path' => SECURITY_PATH,
                        'http_status_code'=>402,
                    ),402);
                }

                $this->data['status'] = 1;
                $this->data['data'] = $authData;
                http_response($this->data);
            }
        }

        if (empty($action_token)){
            http_response(array(
                'status'=>0,
                'errorCode'=>3047,
                'path' => SECURITY_PATH,
                'http_status_code'=>401,
            ),401);
        }

        $client_ip = get_client_ip();
        $authData = $this->rediss->getData($action_token);

        if (IP_VALIDATE && $authData['client_ip'] != $client_ip){
            http_response(array(
                'status'=>0,
                'errorCode'=>3047,
                'path' => SECURITY_PATH,
                'http_status_code'=>402,
            ),402);
        }

        if (empty($authData)){
            http_response(array(
                'status'=>0,
                'errorCode'=>3047,
                'path' => SECURITY_PATH,
                'http_status_code'=>401,
            ),401);
        }

        $user_data = array(
            'uid' => $authData['uid'],
            'user_name' => $authData['user_name'],
            'session_id' => $authData['session_id'],
        );

        $authData['path'] = SECURITY_PATH;

        $this->cookie->setData('userdata',$user_data);
        $this->rediss->setData($authData['uid'],$authData);
        //$this->rediss->deleteData($action_token);

        $authData['mrp_code'] = MRP_BACKUP_SYSTEM_MACHINE_CODE;
        $authData['mrp_url'] = MRP_BACKUP_SYSTEM_URL.'?'.http_build_query(['staff_code' => $authData['staff_code']]);

        //$oa_info = RPC_CALL('YB_J1_004', ['userNumber' => $authData['staff_code']]);
        //$this->data['oa'] = $oa_info;

        $this->data['status'] = 1;
        $this->data['data'] = $authData;

        http_response($this->data);
    }


    /**
     * 验证第三方登录
     */
    public function apiLogin(){
        $access_token = $this->input->post_get('access_token');
        $client_ip = $this->input->post_get('client_ip');

        require_once APPPATH . "third_party/CurlRequest.php";
        if (empty($access_token) || empty($client_ip)){
            $this->data['errorCode'] = 3001;
            http_response($this->data);
        }

        $curlRequest = CurlRequest::getInstance();
        try{
            $params = array(
                'session_id' => $access_token,
                'client_ip' => $client_ip,
            );

            //请求权限中心数据
            $curlRequest->setSessionId($access_token);
            $curlRequest->setServer(SECURITY_API_HOST,SECURITY_API_SECRET,SECURITY_APP_ID);

            $result = $curlRequest->cloud_post('login/login/getUserLoginInfo', $params);

            if (isset($result['status']) && $result['status'] && isset($result['data']) && $result['data']){
                $result['data']['client_ip'] = $client_ip;
                $this->rediss->setData($access_token,$result['data']);
                $this->data['status'] = 1;
            }

        }catch (Exception $e){
            $this->data['errorCode'] =1002;
        }

        http_response($this->data);
    }

    /**
     * MRP认证模块配置的机器码，符合则显示
     */
    public function get_code()
    {
        return http_response(['status' => 1, 'code' => MRP_BACKUP_SYSTEM_MACHINE_CODE, 'url' => MRP_BACKUP_SYSTEM_URL]);
    }

}
/* End of file api.php */
/* Location: ./application/modules/demo/controllers/api.php */