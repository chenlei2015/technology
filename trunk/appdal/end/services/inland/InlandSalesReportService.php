<?php

/**
 * 国内销量报表
 * @author W02278
 * @name InlandSalesReportService Class
 */
class InlandSalesReportService
{
    /**
     * client
     * @var array
     */
    public static $s_encode = ['UTF-8'] ;

    /**
     * @author W02278
     * @var MY_Controller object
     */
    private $_ci;

    public $validataRes = [
        'status' => 0,
        'message' => '',
    ];

    /**
     * InlandGlobalRuleCfgService constructor.
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('Inland_sales_report_model', 'reportModel', false, 'inland');
    }

    /**
     * @param null $gid
     * @return array
     * @throws Exception
     * @author W02278
     * CreateTime: 2019/4/26 14:12
     */
    public function getReportList($params , $offset , $limit)
    {
        $where = "";
        if (isset($params['created_at']) && $params['created_at']) {
            $created_at = date("Y-m-d", strtotime($params['created_at']));
        } else {
            $created_at = date("Y-m-d");
        }

        $dates = $this->getKeys($created_at);
        $where .= " where Date(`created_at`) = '{$created_at}' ";

        if (isset($params['sku']) && $params['sku']) {
            $skuStr = $this->filterWhere($params['sku']);
            $where .= " AND `sku` IN ($skuStr) ";
        }

        if (isset($params['sku_state']) && is_numeric($params['sku_state'])) {
            $where .= " AND `sku_state` = '{$params['sku_state']}' ";
        }
        //配置数据
        $res = $this->_ci->reportModel->getReportList($dates , $where , $offset, $limit );
        $return['status'] = 1;

        //键名
        $this->_ci->lang->load('common');
        $common_keys = $this->_ci->lang->myline('inland_sales_report');
        $all_keys = array_merge(array_values($common_keys) , array_values($dates)) ;
        //数据填充
        $return['data_list'] = [
            'key' => $all_keys,
            'value' => $res['data_list'],
        ];
        $return['data_page'] = $res['data_page'];

        return $return;
    }

    public function filterWhere($skuStr)
    {
        $skuArr = explode(',', $skuStr);

        $str = '';
        foreach ($skuArr as $v) {
            $str .= " ,'$v' ";
        }
        return substr($str , stripos($str,",") + 1);
    }

    /**
     * 获取28天key
     * @param $date
     * @return array
     * @author W02278
     * CreateTime: 2019/4/26 17:01
     */
    public function getKeys($date)
    {
        $date = strtotime($date);
        $dates = [];
        $j = 1;
        for ($i = 28 ;$i >= 1 ; $i --) {
            $dates[$j] = date('Y/m/d' , strtotime("-$i day" , $date));
            $j ++;
        }
        return $dates;
    }
    
    public function __destruct()
    {
        //todo:do something
    }
}