<?php

require_once APPPATH . 'modules/basic/classes/contracts/AbstractList.php';

/**
 * FBA 账号配置列表服务
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class FbaManagerAccountListService extends AbstractList
{

    /**
     * 默认排序
     * @var string
     */
    protected static $_default_sort = 'id desc';

    /**
     * {@inheritDoc}
     * @see Listable::importDependent()
     */
    public function importDependent()
    {
        // TODO Auto-generated method stub
        $this->_ci =& get_instance();
        $this->_ci->load->model('Fba_amazon_account_model', 'amazon_account', false, 'fba');
        $this->_ci->load->model('Fba_amazon_group_model', 'amazon_group', false, 'fba');
        $this->_ci->load->model('Fba_manager_account_model', 'manager_account', false, 'fba');
        $this->_ci->load->helper('fba_helper');
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
                /**
                 * fba账号
                 */
                'account_name'        => [
                        'desc'     => '亚马逊账号,精确，支持多个，","分割',
                        'name'     => 'account_name',
                        'type'     => 'strval',
                        'hook'     => 'tran_sku_sn',
                        //'callback' => 'is_valid_skusn'
                ],
                'staff_code'        => [
                        'desc'     => '管理员工号,精确，支持多个，","分割',
                        'name'     => 'staff_code',
                        'type'     => 'strval',
                ],
                'sale_group'        => [
                        'desc'     => '亚马逊分组id',
                        'name'     => 'sale_group',
                        'type'     => 'intval',
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
            'title' => [
                    'no',               'account_name',     'short_name',       'merchant_id',      'market_place_id',
                    'secret_key',       'aws_access_key_id','amzsite_email',    'group_name',       'first_manager',
                    'status',           'update_info',      'op_name',
            ],
            'select_cols' => [
                    ($this->_ci->amazon_account->getTable()) => [
                        'account_name','short_name','merchant_id','market_place_id','secret_key','aws_access_key_id','amzsite_email','status',
                    ],
                    ($this->_ci->amazon_group->getTable()) => [
                            'group_name'
                    ],
                    ($this->_ci->manager_account->getTable()) => [
                            'staff_code', 'user_zh_name','op_zh_name','updated_at'
                    ],
            ],
            'search_rules' => &$search,
            'droplist' => ['']
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
        $db = $this->_ci->amazon_account->getDatabase();

        $a_t = $this->_ci->amazon_account->getTable();
        $g_t = $this->_ci->amazon_group->getTable();
        $m_t = $this->_ci->manager_account->getTable();

        $append_table_prefix = function($arr, $tbl) {
            array_walk($arr, function(&$val) use ($tbl) { $val = $tbl.$val;});
            return $arr;
        };

        $query = $db->from($a_t);

        /*$query = $db->from($a_t)
        ->join($g_t, sprintf('%s.group_id = %s.group_id', $a_t, $g_t))
        ->join($m_t, sprintf('%s.account_name = %s.account_name', $a_t, $m_t), 'left');*/

        /**
         * 账号
         */
        if (isset($params['account_name']))
        {
            if (count($sns = explode(',', $params['account_name'])) > 1)
            {
                $query->where_in("{$a_t}.account_name", $sns);
            }
            else
            {
                $query->where("{$a_t}.account_name", $sns[0]);
            }
        }

        //从cfg里面获取配置
        $select_cols = [];
        foreach ($this->_cfg['select_cols'] as $tbl => $cols)
        {
            $select_cols = array_merge($select_cols, $append_table_prefix($cols, $tbl.'.'));
        }

        //执行搜索
        $query->select(implode(',', $select_cols))
            ->join($g_t, sprintf('%s.group_id = %s.group_id', $a_t, $g_t))
            ->join($m_t, sprintf('%s.account_name = %s.account_name', $a_t, $m_t), 'left');


        if (isset($params['sale_group']))
        {
            $query->where($g_t.'.group_id', $params['sale_group']);
        }

        if (isset($params['staff_code']))
        {
            if (count($sns = explode(',', $params['staff_code'])) > 1)
            {
                $query->where_in("{$m_t}.staff_code", $sns);
            }
            else
            {
                $query->where("{$m_t}.staff_code", $sns[0]);
            }
        }

        $query_counter = clone $query;
        $query_counter->select("{$a_t}.id");
        $count = $query_counter->count_all_results();
        $page = ceil($count / $params['per_page']);

        $query->order_by(implode(',', $append_table_prefix($params['sort'], $a_t.'.')))
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
