<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 活动配置
 *
 * @author Jason 13292
 * @since 2019-09-05
 */
class Activity extends MY_Controller {

    public function __construct()
    {
        parent::__construct();
        get_active_user();
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

            $active_user = get_active_user();
            if (!$active_user->has_all_data_privileges(BUSSINESS_FBA))
            {
                //加上自己作为销售员的条件
                $params['set_data_scope'] = 1;

                //这个账号是否是子账号的管理员
                //$account_name = $active_user->get_my_manager_accounts();
                $account_nums = $active_user->get_my_manager_account_nums();
                if (!empty($account_nums))
                {
                    $params['prev_account_nums'] = implode(',', $account_nums);
                    $params['prev_salesman'] = $active_user->staff_code;
                }
                else
                {
                    //只能查自己的 但选择了其他人的记录，一定没有数据返回
                    $params['prev_salesman'] = $active_user->staff_code;
                }
            }

            $this->load->service('fba/ActivityListService');

            $this->activitylistservice->setSearchParams($params);
            //过滤hook
            $this->activitylistservice->setPreSearchHook(array($this->activitylistservice, 'hook_filter_params'), ['input' => $this->activitylistservice->search_params, 'update' => 'search_params']);
            //参数处理hook
            $this->activitylistservice->setPreSearchHook(array($this->activitylistservice, 'hook_translate_params'), ['input' => &$this->activitylistservice->search_params, 'update' => 'search_params']);
            //参数转换
            $this->activitylistservice->setPreSearchHook(array($this->activitylistservice, 'hook_format_params'), ['input' => &$this->activitylistservice->search_params, 'update' => 'search_params']);
            //返回数据处理
            $this->activitylistservice->setAfterSearchHook(array($this->activitylistservice, 'translate'), ['input' => 'return', 'update' => 'none']);
            $this->data = $this->activitylistservice->execSearch();

            $cfg = $this->activitylistservice->get_cfg();

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
            ////$this->data['errorCode'] = $code
            http_response($this->data);
        }

    }

    /**
     *
     * @author Jason 13292
     * @date 2019-03-04
     * @desc fba prlist添加备注
     * @link
     */
    public function remark()
    {
        try
        {
            $params = $this->compatible('post');
            $this->load->service('fba/ActivityService');
            $this->data = $this->activityservice->update_remark($params);

            $this->data['status'] = $this->data['data'] ? 1 : 0;
            $code = $this->data['data'] ? 200 : 500;
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

    public function batch_discard()
    {
        try
        {
            $params = $this->input->post();
            $this->load->service('fba/ActivityService');
            $this->data['data'] = $this->activityservice->batch_discard($params);
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

    public function batch_approve()
    {
        try
        {
            $params = $this->input->post();
            $this->load->service('fba/ActivityService');
            $this->data['data'] = $this->activityservice->batch_approve($params);
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
     * 导入修改或插入
     */
    public function import()
    {
        try
        {
            $params = $this->compatible('post');
            $requir_cols = array_flip(['primary_key', 'map', 'selected']);
            if (count(array_diff_key($requir_cols, $params)) > 0 )
            {
                throw new \InvalidArgumentException('无效的参数', 412);
            }
            $this->load->service('fba/ActivityService');
            $this->data['data'] = $this->activityservice->import($params);
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

    public function export()
    {
        try
        {
            $post = $this->compatible('post');
            $this->load->service('fba/ActivityExportService');
            $this->activityexportservice->setTemplate($post);
            $this->data['filepath'] = $this->activityexportservice->export('csv');
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

}
/* End of file Pr.php */
/* Location: ./application/modules/fba/controllers/Pr.php */