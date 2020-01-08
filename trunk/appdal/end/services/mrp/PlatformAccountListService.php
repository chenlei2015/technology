<?php
/**
 * Created by PhpStorm.
 * User: Yibai
 * Date: 2019/12/18
 * Time: 18:57
 */
require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

class PlatformAccountListService  extends AbstractList
{
    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'created_at desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Platform_account_model', 'm_platform_account', false, 'fba');
        return $this;
    }

    /**
     * 列表都需要按照key值配置服务， 这里可以放到配置文件
     *
     * {@inheritDoc}
     * @see Listable::get_cfg()
     */
    public function get_cfg() : array
    {
        $search = [
            'account_name'        => [
                'desc'     => '账号',
                'name'     => 'account_name',
                'type'     => 'strval',
            ],
            'platform_code'        => [
                'desc'     => '平台',
                'dropname' => 'inland_platform_code',
                'name'     => 'platform_code',
                'type'     => 'strval',
            ],
            'status'        => [
                'desc'     => '状态',
                'dropname' => 'account_status',
                'name'     => 'status',
                'type'     => 'intval',
                'callback' => 'is_valid_account_status'
            ],
            'offset'        => [
                'name'     => 'page',
                'type'     => 'intval',
                'callback' => 'is_valid_page'
            ],
            'limit'        => [
                'name'     => 'per_page',
                'type'     => 'intval',
                'callback' => 'is_valid_pagesize'
            ],
        ];
        $this->_cfg = [
            'title' => ['no','platform_code','account_name','short_name','status','updated_at'],
            'select_cols' => [
                ($this->_ci->m_platform_account->getTable()) => [
                    '*'
                ],
            ],
            'search_rules' => &$search,
            'droplist' => ['inland_platform_code', 'account_status'],
        ];
        return $this->_cfg;
    }


    /**
     *
     * {@inheritDoc}
     * @see AbstractList::search()
     */
    protected function search($params)
    {
        //pr($params);exit;
        $db = $this->_ci->m_platform_account->getDatabase();

        $o_t = $this->_ci->m_platform_account->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($o_t);


        /**
         * 审核状态
         */
        if (isset($params['status']))
        {
            $query->where("{$o_t}.status", $params['status']);
        }

        if (isset($params['platform_code']))
        {
            $query->where("{$o_t}.platform_code", $params['platform_code']);
        }


        if (isset($params['account_name']))
        {
            if (count($sns = explode(',', $params['account_name'])) > 1)
            {
                $query->where_in("{$o_t}.account_name", $sns);
            }
            else
            {
                $query->where("{$o_t}.account_name", $sns[0]);
            }
        }

        //从cfg里面获取配置
        $select_cols = '';
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols .= implode(',', $append_table_prefix($cols, $tbl.'.'));
        }

        $query_counter = clone $query;
        $query_counter->select("{$o_t}.id");
        $count = $query_counter->count_all_results();
        $page = ceil($count / $params['per_page']);

        /**
         * 导出暂存
         */
        if (isset($params['export_save']))
        {
            $query_export = clone $query;
            $query_export->select($select_cols)->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')));
            $this->_ci->load->library('rediss');
            $this->_ci->load->service('basic/SearchExportCacheService');
            $total = str_pad((string)$count, 10, '0', STR_PAD_LEFT);
            $this->_ci->searchexportcacheservice->setScene($this->_ci->searchexportcacheservice::OVERSEA_SUMMARY_LIST_SEARCH_EXPORT)->set($total.($query_export->get_compiled_select('', false)));
        }

        //执行搜索
        $query->select($select_cols)
            ->order_by(implode(',', $append_table_prefix($params['sort'], $o_t.'.')))
            ->limit($params['per_page'], ($params['page'] - 1) * $params['per_page']);

        //pr($query->get_compiled_select());exit;
        //pr($query->get());exit;
        $result = $query->get()->result_array();

        //设定统一返回格式
        return [
            'page_data' => [
                'total' => $count,
                'offset' => $params['page'],
                'limit' => $params['per_page'],
                'pages' => $page
            ],
            'data_list'  => [
                'value' => &$result
            ]
        ];
    }

    /**
     * 转换
     *
     * {@inheritDoc}
     * @see AbstractList::translate()
     */
    public function translate($search_result)
    {
        return $search_result;
    }

}
