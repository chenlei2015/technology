<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 活动配置
 *
 * @author zc
 * @since 2019-10-22
 */
class New_cfg extends MY_Controller {

    private $_ci;
    public function __construct()
    {
        $this->_ci =& get_instance();
        parent::__construct();
    }

    /**
     * 列表
     */
    public function list()
    {
        try
        {
            $params = $this->compatible('get');

            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $this->load->service('fba/NewCfgListService');

            $this->newcfglistservice->setSearchParams($params);
            //过滤hook
            $this->newcfglistservice->setPreSearchHook(array($this->newcfglistservice, 'hook_filter_params'), ['input' => $this->newcfglistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->newcfglistservice->setPreSearchHook(array($this->newcfglistservice, 'hook_translate_params'), ['input' => &$this->newcfglistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->newcfglistservice->setPreSearchHook(array($this->newcfglistservice, 'hook_format_params'), ['input' => &$this->newcfglistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->newcfglistservice->setAfterSearchHook(array($this->newcfglistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->newcfglistservice->execSearch();

            $cfg = $this->newcfglistservice->get_cfg();

            //取dropdown
            if ($is_get_base == 1)
            {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist($cfg['droplist']);
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }

            //取配置
            $this->load->service('basic/UsercfgProfileService');
            $result = $this->usercfgprofileservice->get_display_cfg($cfg['user_profile']);
            $this->data['selected_data_list'] = $result['config'];
            $this->data['profile'] = $result['field'];
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

    /**
     *
     * @author zc
     * @date 2019-10-22
     * @desc 批量审核
     * @link
     */
    public function batch_approve()
    {
        try
        {
            $params = $this->input->post();
            $this->load->service('fba/NewCfgService');
            $this->data['data'] = $this->newcfgservice->batch_approve($params);
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    /**
     *
     * @author zc
     * @date 2019-10-22
     * @desc 批量审核全部未审核
     * @link
     */
    public function batch_approve_all()
    {
        try
        {
            $params = $this->input->post();
            $this->load->service('fba/NewCfgService');
            $this->data['data'] = $this->newcfgservice->batch_approve_all($params);
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }


    public function import()
    {
        try {
            $user_id = $this->input->post_get('user_id') ?? '';
            $user_name = $this->input->post_get('user_name') ?? '';
            $data_values = json_decode(str_replace(" ",'',$this->input->post_get('data_values')), true);
            $this->load->service('fba/NewCfgService');
            $result = $this->newcfgservice->import_batch($data_values,$user_id,$user_name);
            $this->data['status']    = 1;
            $this->data['data_list'] = $result;
            $code                    = 200;
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
            http_response($this->data);
        }
    }

    public function export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/NewCfgExportService');
            $this->newcfgexportservice->setTemplate($post);
            $this->data['filepath'] = $this->newcfgexportservice->export('csv');
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
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    public function batch_del()
    {
        try
        {
            $params = $this->input->post();
            $this->load->service('fba/NewCfgService');
            $this->data['data'] = $this->newcfgservice->batch_del($params);
            $this->data['status'] = 1;
            $code = 200;
        }
        catch (\Throwable $e)
        {
            $code = 500;
            $errorMsg = $e->getMessage();
        }
        finally
        {
            $code == 200 or logger('error', sprintf('文件： %s 方法：%s 行：%d 错误：%s', __FILE__, __METHOD__, __LINE__, $errorMsg));
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 添加备注
     * http://192.168.71.170:1084/fba/nwe_cfg/remark_add
     */
    public function remark_add()
    {
        $this->load->model('Fba_new_remark_model', "m_new_r");
        $id        = $this->input->post_get('id') ?? '';
        $remark    = $this->input->post_get('remark') ?? '';
        $user_id   = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $params    = [
            'new_id'     => $id,
            'op_uid'     => $user_id,
            'op_zh_name' => $user_name,
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $result    = $this->m_new_r->addRemark($params);
        $this->data['status']    = 0;
        $this->data['errorMess'] = '备注失败';
        if ($result) {
            $this->data['status']    = 1;
            $this->data['errorMess'] = '备注成功';
        }
        http_response($this->data);
    }

    /**
     * 查看-基本信息
     * http://192.168.71.170:1084/fba/nwe_cfg/base_info
     */
    public function base_info()
    {
        $this->lang->load('common');
        $this->_ci->load->model('Fba_new_list_model', 'm_fba_new', false, 'fba');
        $id = $this->input->post_get('id') ?? '';
        $result = $this->m_fba_new->get_base_info($id);
        $this->data['status'] = 1;
        $this->data['data_list'] = [
            'value' => $result??[],
        ];

        http_response($this->data);
    }

    /**
     * 查看-备注
     * http://192.168.71.170:1084/fba/nwe_cfg/remark
     */
    public function remark()
    {
        $this->lang->load('common');
        $this->_ci->load->model('Fba_new_remark_model', 'm_fba_r', false, 'fba');
        $new_id = $this->input->post_get('id') ?? '';
        $column_keys = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $result = $this->m_fba_r->get_remark('',$new_id);
        $this->data['status'] = 1;
        $this->data['data_list'] = [
            'key'   => $column_keys,
            'value' => $result??[],
        ];

        http_response($this->data);
    }

    /**
     * 查看-日志
     * http://192.168.71.170:1084/fba/nwe_cfg/log
     */
    public function log()
    {
        $this->lang->load('common');
        $this->_ci->load->model('Fba_new_log_model', 'm_fba_n_l', false, 'fba');
        $id = $this->input->post_get('id') ?? '';
        $offset = $this->input->post_get('offset') ?? 1;
        $limit = $this->input->post_get('limit') ?? 20;
        $column_keys = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $result = $this->m_fba_n_l->getStockLogList($id, $offset, $limit);
        $this->data['status'] = 1;
        $this->data['data_list']['value'] = $result['data_list']??[];
        $this->data['data_list']['key'] = $column_keys;
        $this->data['page_data'] = $result['page_data'];
        http_response($this->data);
    }

    /**
     * 修改
     * http://192.168.71.170:1084/fba/nwe_cfg/modify
     */
    public function modify()
    {
        $this->load->model('Fba_new_list_model', "m_n_l");
        $id = $this->input->post_get('id') ?? '';
        $demand_num   = $this->input->post_get('demand_num') ?? '';
        $user_id = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $params    = [
            'id'     => $id,
            'demand_num'     => $demand_num
        ];
        $result = $this->m_n_l->modify($params,$user_id,$user_name);
        $this->data['status']    = 0;
        $this->data['errorMess'] = '修改失败';
        if ($result) {
            $this->data['status']    = 1;
            $this->data['errorMess'] = '修改成功';
        }
        http_response($this->data);
    }


    /**
     *  备货详情
     * http://192.168.71.170:1084/fba/nwe_cfg/stock
     */
    public function stock()
    {
        $id = $this->input->post_get('id') ?? '';
        $this->_ci->load->model('Fba_stock_info_model', 'm_fba_s_i', false, 'fba');
        $result = $this->m_fba_s_i->get($id);
        $this->data['status'] = 1;
        $this->data['data_list'] = $result??[];
        http_response($this->data);
    }
}