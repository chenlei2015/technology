<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 *
 *
 * @author Manson
 * @since 2019-07-18 16:35
 */
class Shipment extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function update_sku_name()
    {
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        $this->local   = $this->load->database('local', TRUE);//ERP系统数据库
        $this->product = $this->load->database('yibai_product', TRUE);//ERP系统数据库

        $sql     = "SELECT DISTINCT sku FROM yibai_fba_logistics_list";
        $all_sku = $this->local->query($sql)->result_array();


        $sql       = "SELECT p.sku,descrip.title FROM yibai_product p
left join yibai_product_description as descrip on (descrip.sku=p.sku and descrip.language_code='Chinese')";
        $title_map = $this->product->query($sql)->result_array();
        $title_map = array_column($title_map, 'title', 'sku');
        foreach ($all_sku as $key => &$item) {
            $item['sku_name'] = $title_map[$item['sku']] ? addslashes($title_map[$item['sku']]) : '';
        }

        $this->local->update_batch('yibai_fba_logistics_list', $all_sku, 'sku');
        echo 'ok';
        exit;
    }

    /**
     * 提前定时任务获取数据
     * stock库 需求跟踪列表 需求列表
     * common库 备货跟踪列表
     */
    public function fba_pre_data()
    {
        try {
            $params = $this->compatible('get');

            $this->load->service('shipment/FbaPlanService');
            $this->data['date'] = $this->fbaplanservice->pre_data($params);

            $this->data['status'] = 1;
            $code                 = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }
        exit;
    }

    /**
     * 提前定时任务获取数据
     * stock库 需求跟踪列表 需求列表
     * common库 备货跟踪列表
     */
    public function oversea_pre_data()
    {
        try {
            $params = $this->compatible('get');

            $this->load->service('shipment/OverseaPlanService');
            $this->data['date'] = $this->overseaplanservice->pre_data($params);

            $this->data['status'] = 1;
            $code                 = 200;
        } catch (\InvalidArgumentException $e) {
            $code     = $e->getCode();
            $errorMsg = $e->getMessage();
        } catch (\RuntimeException $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } catch (\Throwable $e) {
            $code     = 500;
            $errorMsg = $e->getMessage();
        } finally {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }
        exit;
    }

    //redis练习
    public function redis_study()
    {

    }

    //发送邮件
    public function send_email($subject, $message)
    {
        $this->load->library('email');
        $config['protocol']     = 'smtp';
        $config['smtp_host']    = 'smtp.163.com';
        $config['smtp_user']    = 'zmbear888@163.com';
        $config['smtp_pass']    = 'asd123';//去QQ邮箱设置开启smtp
        $config['smtp_port']    = 25;
        $config['smtp_timeout'] = 30;
        $config['mailtype']     = 'text';
        $config['charset']      = 'utf-8';
        $config['wordwrap']     = TRUE;
        $this->email->initialize($config);
        $this->email->set_newline("\r\n");
        $config['crlf'] = "\r\n";
        $this->email->from('zmbear888@163.com', 'Plan');
        $this->email->to('ofh3990@dingtalk.com');
        $this->email->subject($subject);
        $this->email->message($message);
        $this->email->send();
    }


}
