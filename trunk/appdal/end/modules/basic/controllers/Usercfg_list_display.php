<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/2
 * Time: 16:56
 */
class Usercfg_list_display extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    //编辑显示列表页
    public function getList(){
        try
        {
            $params = $this->compatible('get');

            $active_user = get_active_user();
            $this->load->service('basic/UsercfgProfileService');
            $params['staff_code'] = $active_user->staff_code;

            $data = $this->usercfgprofileservice->cfg_info($params);

            $this->lang->load('common');
            $list = $this->lang->myline($params['collection']);
//            $list = json_encode($list);
//            $list = json_decode($list,true);
//            pr($list);
            // 重新赋值从0开始的排序标记
            $i=0;
            foreach ($list as $key => &$item){
                $item['value'] = (string)$i;
                $i++;
//                $item = (object)$item;
//                $item = json_encode($item);
            }

            $this->data['list'] = $list;//列表页
            $this->data['selected_list'] = $data;//已勾选
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }

    public function objarray_to_array($obj)
    {
        $ret = array();
        foreach ($obj as $key => $value) {
            if (gettype($value) == "array" || gettype($value) == "object") {
                $ret[$key] = $this->objarray_to_array($value);
            } else {
                $ret[$key] = $value;
            }
        }
        return $ret;
    }
    public function cfg()
    {
        try
        {
            $this->lang->load('common');
            $active_user = get_active_user();
            $params = $this->compatible('');

            //主键名为id的列表
            $id_collection      = $this->lang->myline('id_collection')??[];
            $data = [];
            $data['collection'] = $params['collection']??'';
            $data['staff_code'] = $active_user->staff_code;
            if(isset($params['config'])){
                $config = json_decode($params['config'],true)??[];
                $data['config_index'] = serialize($config);    //字段序号
            }else{
                $this->data['status'] = 0;
                $this->data['errorMess'] = '参数错误';
                $code = 412;
                return;
                //http_response($this->data);
            }

            sort($config);//升序排序
            $arr = $this->lang->myline($data['collection']);
//            pr($arr);

//            $arr = $this->objarray_to_array($arr);

//            pr($config);
            $field_str = '';
            if(isset($config) && is_array($config)){
                foreach ($config as $value){
                    if (!isset($arr[$value])) {
                        throw new InvalidArgumentException('参数异常,请重新勾选后重试');
                    }
                    $data['config'][$arr[$value]['key']] = $arr[$value]['label'];
                    $field_str .= $arr[$value]['field'].',';
                }
            }
            $data['field'] = array_filter(explode(',',$field_str));
            $data['field'][] = 'gid';//字段默认带上gid 默认导出gid
            if (in_array($data['collection'], $id_collection)) {
                $data['field'][] = 'id';
            }

            $this->load->service('basic/UsercfgProfileService');
            $this->usercfgprofileservice->cfg($data);
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\InvalidArgumentException $e)
        {
            $code = $e->getCode();
            $errorMsg = $e->getMessage();
        }
        catch (\RuntimeException $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
        
    }
}