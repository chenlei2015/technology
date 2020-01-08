<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * ERPSKU属性配置表 (原:FBA备货关系配置表)
 *
 * @version 1.2.2
 * @since 2019-09-04
 */
class Stock_relationship_cfg extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }

    public function getStockList()
    {
        try {
            $params      = $this->compatible('get');
            $is_get_base = intval($params['is_get_base'] ?? 0);
            unset($params['is_get_base']);

            $this->load->service('fba/SkuCfgListService', null, 'sku_cfg_list');

            $this->sku_cfg_list->setSearchParams($params);
            //过滤hook
            $this->sku_cfg_list->setPreSearchHook([$this->sku_cfg_list, 'hook_filter_params'], ['input' => $this->sku_cfg_list->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->sku_cfg_list->setPreSearchHook([$this->sku_cfg_list, 'hook_translate_params'], ['input' => &$this->sku_cfg_list->search_params, 'update' => 'search_params']);
            //参数转换
            $this->sku_cfg_list->setPreSearchHook([$this->sku_cfg_list, 'hook_format_params'], ['input' => &$this->sku_cfg_list->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->sku_cfg_list->setAfterSearchHook([$this->sku_cfg_list, 'translate'], ['input' => 'return', 'update' => 'none']);
            $this->data = $this->sku_cfg_list->execSearch();

            $cfg = $this->sku_cfg_list->get_cfg();
            //取key值
            $this->data['data_list']['key'] = $cfg['title'];

            //取dropdown
            if ($is_get_base == 1) {
                $this->load->service('basic/DropdownService');
                $this->dropdownservice->setDroplist($cfg['droplist']);
                $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            }
            //取配置
            $this->load->service('basic/UsercfgProfileService');

            $result                           = $this->usercfgprofileservice->get_display_cfg($cfg['profile']);
            $this->data['selected_data_list'] = $result['config'];
            $this->data['profile']            = $result['field'];

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
            http_response($this->data);
        }
    }

    /**
     * @author Manson
     * @date 2019-09-04
     * @desc 查看详情
     * @link
     */

    public function getStockDetails()
    {
        try {
            $params = $this->compatible('get');
            $this->load->service('fba/SkuCfgService');
            $this->load->model('Fba_remark_model', "m_remark");
            $this->data['data_list']['value']  = $this->skucfgservice->detail($params['id']);
            $this->data['data_list']['remark'] = $this->m_remark->getRemarkList('', $params['id'], '');
            $this->load->service('basic/DropdownService');
            $this->dropdownservice->setDroplist(['is_contraband']);
            $this->data['data_list']['drop_down_box'] = $this->dropdownservice->get();
            $this->data['status']                     = 1;
            $code                                     = 200;

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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            //$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     * 获取日志列表
     * http://192.168.71.170:1084/fba/stock_relationship_cfg/getStockLogList
     */
    public function getStockLogList()
    {
        $this->lang->load('common');
        $this->load->model('Stock_log_model', "m_log");
        $id                                 = $this->input->post_get('id') ?? '';
        $offset                             = $this->input->post_get('offset') ?? '';
        $limit                              = $this->input->post_get('limit') ?? '';
        $offset                             = $offset ? $offset : 1;
        $limit                              = $limit ? $limit : 20;
        $column_keys                        = [
            $this->lang->myline('operation_time'),
            $this->lang->myline('operator'),
            $this->lang->myline('operation_context')
        ];
        $result                             = $this->m_log->getStockLogList($id, $offset, $limit);
        if (isset($result) && count($result['data_list']) > 0) {
            $this->data['status']           = 1;
            $this->data['data_list']['log'] = [
                'key'   => $column_keys,
                'value' => $result['data_list'],
            ];
            $this->data['page_data']['log'] = [
                'offset' => (int)$result['data_page']['offset'],
                'limit'  => (int)$result['data_page']['limit'],
                'total'  => $result['data_page']['total'],
            ];
        } else {
            $this->data['status']           = 1;
            $this->data['data_list']['log'] = [
                'key'   => $column_keys,
                'value' => [],
            ];
            $this->data['page_data']['log'] = [
                'offset' => (int)$offset,
                'limit'  => (int)$limit,
                'total'  => $result['data_page']['total']
            ];
        }

        http_response($this->data);
    }


    /**
     * 添加备注
     * http://192.168.71.170:1084/fba/stock_relationship_cfg/addRemark
     */
    public function addRemark()
    {
        $this->load->model('Fba_remark_model', "m_remark");
        $id        = $this->input->post_get('id') ?? '';
        $remark    = $this->input->post_get('remark') ?? '';
        $user_id   = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        $params    = [
            'op_uid'     => $user_id,
            'op_zh_name' => $user_name,
            'sku_id'     => $id,
            'remark'     => $remark,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $result    = $this->m_remark->addRemark($params);
        if ($result) {
            $this->data['status']    = 1;
            $this->data['errorMess'] = '备注成功';
            http_response($this->data);

            return;
        } else {
            $this->data['status']    = 0;
            $this->data['errorMess'] = '备注失败';
            http_response($this->data);

            return;
        }
    }

    /**
     * 批量审核成功
     * http://192.168.71.170:1084/fba/stock_relationship_cfg/batchCheckSuccess
     */
    public function batchCheckSuccess()
    {
        $this->load->model('Fba_sku_cfg_model', 'm_sku_cfg');
        $id        = json_decode($this->input->post_get('id'));
        $uid       = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        if (!is_array($id)) {
            $this->data['status']    = 0;
            $this->data['errorMess'] = '参数错误';
            http_response($this->data);

            return;
        }
        $result = $this->m_sku_cfg->batchCheckSuccess($id, $uid, $user_name);
        if ($result) {
            $this->data['status']    = 1;
            $this->data['data_list'] = $result;
            http_response($this->data);

            return;
        }
    }

    /**
     * 批量审核失败
     * http://192.168.71.170:1084/fba/stock_relationship_cfg/batchCheckFail
     */
    public function batchCheckFail()
    {
        $this->load->model('Fba_sku_cfg_model', 'm_sku_cfg');
        $id        = json_decode($this->input->post_get('id'));
        $uid       = $this->input->post_get('user_id') ?? '';
        $user_name = $this->input->post_get('user_name') ?? '';
        if (!is_array($id)) {
            $this->data['status']    = 0;
            $this->data['errorMess'] = '参数错误';
            http_response($this->data);

            return;
        }
        $result = $this->m_sku_cfg->batchCheckFail($id, $uid, $user_name);
        if ($result) {
            $this->data['status']    = 1;
            $this->data['data_list'] = $result;
            http_response($this->data);

            return;
        }
    }


    /**
     * 导出功能
     */
    public function export()
    {
        try {
            $post = $this->compatible('post');
            $this->load->service('fba/FbaStockCfgExportService');
            $this->fbastockcfgexportservice->setTemplate($post);
            $this->data['filepath'] = $this->fbastockcfgexportservice->export('csv');
            $this->data['status']   = 1;
            $code                   = 200;
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
            //$this->data['errorCode'] = $code
            isset($errorMsg) && $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }

    }

    /**
     * 修改配置
     */
    public function modifyStock()
    {
        try {
            $params = $this->compatible('post');
            $this->load->service('fba/SkuCfgService');
            $result = $this->skucfgservice->modifyOne($params);
            if (!$result) {
                throw new \RuntimeException(sprintf('修改失败'), 500);
            }
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     * 批量修改导入功能
     */
    public function uploadExcel()
    {
        try {
            $data_values = json_decode($this->input->post_get('data_values'), true);
            $all         = count($data_values);
            $this->data  = [];
            $processed   = 0;//已处理
            $undisposed  = 0;//未处理
            $this->load->service('fba/SkuCfgService');
            $processed               = $this->skucfgservice->modifyByExcel($data_values);
            $undisposed              = $all - $processed;
            $this->data['status']    = 1;
            $this->data['data_list'] = ['processed' => $processed, 'undisposed' => $undisposed];
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
            isset($errorMsg) and $this->data['errorMess'] = $errorMsg;
            http_response($this->data);
        }
    }

    /**
     *
     * @author zc
     * @date 2019-10-22
     * @desc 相关列修改
     * @link
     */
    public function update_column()
    {
        try
        {
            $params = $this->input->post();
            $column = $this->input->post_get('column') ?? '';
            $column_value = $this->input->post_get('column_value') ?? '';
            $active_user = get_active_user();
            $user_id = $active_user->staff_code;
            $user_name = $active_user->user_name;
            $post_params = [
                'column' => $column,
                'column_value' => $column_value,
            ];
            $this->load->service('fba/SkuCfgService');
            $this->data['data'] = $this->skucfgservice->update_column($post_params,$user_id,$user_name);
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
}
