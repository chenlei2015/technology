<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/**
 * 发运毛需求配置
 *
 * @author Jason 13292
 *
 */
class Cfg extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        get_active_user();
    }

    public function list()
    {
        try {
            //接收get参数
            //$get    = $this->compatible('get');
            $result = [];
            if (!file_exists(APPPATH . 'upload/shipment_cfg.php')) { //文件不存在返回空
                $this->data['status']             = 1;
                $this->data['data_list']['value'] = [];
            } else {
                require_once APPPATH . 'upload/shipment_cfg.php';//引入文件
                $this->data['data_list']['value'] = compact(['sales_amount_cfg', 'exhaust_cfg', 'in_warehouse_age_cfg', 'supply_day_cfg',  'sale_category_cfg', 'global_cfg','bs_cfg','max_safe_stock_day_cfg']);
                $this->data['status']             = 1;
            }
            $code = 200;
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
     *Notes:编辑FBA发运毛需求生成策略
     *User: lewei
     *Date: 2019/12/4
     *Time: 18:59
     */
    public function edit(){
        try{
            $post    = $this->compatible('post');
            $file_name = "shipment_cfg.php";
            $file_txt = "<?php\r\n\r\n\r\n";//需要写入的数据

            //销售（订单量）系数表
            $sales_amount_cfg = explode(',',$post['sales_amount_cfg']);
            $file_txt .="
//销售（订单量）系数表
\$sales_amount_cfg = [
    [
        //销量 15 <= 2 并且 30 <=3 ，系数配置0.6
        'cfg' => [
            15 => ['<=', 2],
            30 => ['<', 3],
        ],
        'factor' => {$sales_amount_cfg[0]}
    ],
    [
        //销量 15 > 2 并且 30 <=3 ，系数配置0.8
        'cfg' => [
            15 => ['>', 2],
            30 => ['<=', 3],
        ],
        'factor' => {$sales_amount_cfg[1]}
    ],
    [
        //销量 15 <= 2 并且 30>3 ，系数配置0.9
        'cfg' => [
            15 => ['<=', 2],
            30 => ['>', 3],
        ],
        'factor' => {$sales_amount_cfg[2]}
    ],
    [
        //销量 15 > 2 并且 30>3 ，系数配置1
        'cfg' => [
            15 => ['>', 2],
            30 => ['>', 3],
        ],
        'factor' => {$sales_amount_cfg[3]}
    ],
];";

            //断货系数
            $exhaust_cfg = explode(',',$post['exhaust_cfg']);

            $file_txt .="
/**
 *  断货天数系数配置说明, 配置检测包含两侧端点， 时间依次按照从小到大区间设置。
 *  字段说明： 起始天数， 截止天数， 系数
 *            min  表示无限小， max 表示无限大， 一般可能用在第一个区间和最大一个区间
 *
 *   示例： [0,  14,  1],     0<=断货天数<=14, 系数设置为1
 *       [15, 21,  0.9],   15<=断货天数<=21, 系数设置为0.9
 *		 [43, 'max', 0.5], 断货天数>=43, 系数配置0.5
 */
\$exhaust_cfg = [
    //0<=断货天数<=14, 系数设置为1
    [0,   14,   $exhaust_cfg[0]],
    //15<=断货天数<=21, 系数设置为0.9
    [15,  21,   $exhaust_cfg[1]],
    //22<=断货天数<=28, 系数设置为0.8
    [22,  28,   $exhaust_cfg[2]],
    [29,  35,   $exhaust_cfg[3]],
    [36,  42,   $exhaust_cfg[4]],
    [43, 'max', $exhaust_cfg[5]],
];
            ";

            //超90天库龄系数
            $in_warehouse_age_cfg = explode(',',$post['in_warehouse_age_cfg']);
            $file_txt .= "
/**
 *  库龄系数配置说明。
 *  字段说明： 是否有超90天库龄， 超90天库龄系数 1 为超过 2 为不超过
 *
 *   示例： [1,   0],   有超90天库龄，则超90天库龄系数为0
 */
\$in_warehouse_age_cfg = [
    [1,   $in_warehouse_age_cfg[0]],
    [2,   $in_warehouse_age_cfg[1]],
];
            
            ";

            //可售卖天数系数
            $supply_day_cfg = explode(',',$post['supply_day_cfg']);
            $file_txt .="
 /**
 *  可售卖天数系数配置说明, 配置检测包含两侧端点， 时间依次按照从小到大区间设置。
 *  字段说明： 起始天数， 截止天数， 系数
 *            min  表示无限小， max 表示无限大， 一般可能用在第一个区间和最大一个区间
 *
 *   示例： [0,  29,   1],      0<=可售卖天数<=29, 系数设置为1
 *		 [360, 'max', 0],    可售卖天数>=360, 系数配置0
 */
\$supply_day_cfg = [
    [0,   29,    $supply_day_cfg[0]   ],
    [30,  59,    $supply_day_cfg[1]   ],
    [60,  89,    $supply_day_cfg[2] ],
    [90,  179,   $supply_day_cfg[3] ],
    [180, 269,   $supply_day_cfg[4] ],
    [270, 359,   $supply_day_cfg[5] ],
    [360, 'max', $supply_day_cfg[6]   ],
];
            ";


            //Z值/一次备货天数/扩销系数
            $sale_category_cfg = json_decode($post['sale_category_cfg'],true);
            $file_txt .= "
/**
 *  销量分类
 *
 *  可售卖天数系数配置说明, 配置检测包含左端点，不含右端点， 时间依次按照从小到大区间设置。
 *  字段说明： 起始天数， 截止天数， Z值， 一次发运天数， 扩销系数
 *            min  表示无限小的正数， max 表示无限大的正数， 一般可能用在第一个区间和最大一个区间
 *  注意
 *   示例：
 *        ['min',      0.1,   	  	0.65, 5,   1],    'min' <= 加权日均销量 < 0.1（即0 < 日均销量 < 0.1）的情况下，Z值为0.65、一次备货天数为5、扩销系数为1。
 *		  [0.1,        0,3,         0.85, 5,   1],    0.1 <= 加权日均销量 < 0.3的情况下，Z值为0.85、一次备货天数为5、扩销系数为1。
 *		  [20,         'max',       2.25, 5,   1.3],  20 <= 加权日均销量的情况下，Z值为2.25、一次备货天数为5、扩销系数为1.3。
 */
\$sale_category_cfg = [
        [0,          0.0000001,   {$sale_category_cfg[0][0]},    {$sale_category_cfg[0][1]},   {$sale_category_cfg[0][2]}],
        [0.0000001,  0.1,   	  {$sale_category_cfg[1][0]},    {$sale_category_cfg[1][1]},   {$sale_category_cfg[1][2]}],
        [0.1,        0.3,         {$sale_category_cfg[2][0]},    {$sale_category_cfg[2][1]},   {$sale_category_cfg[2][2]}],
        [0.3,        0.6,         {$sale_category_cfg[3][0]},    {$sale_category_cfg[3][1]},   {$sale_category_cfg[3][2]}],
        [0.6,        1,           {$sale_category_cfg[4][0]},    {$sale_category_cfg[4][1]},   {$sale_category_cfg[4][2]}],
        [1,          3,           {$sale_category_cfg[5][0]},    {$sale_category_cfg[5][1]},   {$sale_category_cfg[5][2]}],
        [3,          5,           {$sale_category_cfg[6][0]},    {$sale_category_cfg[6][1]},   {$sale_category_cfg[6][2]}],
        [5,          10,          {$sale_category_cfg[7][0]},    {$sale_category_cfg[7][1]},   {$sale_category_cfg[7][2]}],
        [10,         20,          {$sale_category_cfg[8][0]},    {$sale_category_cfg[8][1]},   {$sale_category_cfg[8][2]}],
        [20,         'max',       {$sale_category_cfg[9][0]},    {$sale_category_cfg[9][1]},   {$sale_category_cfg[9][2]}],
];
            ";

            //最大额外发运天数
            $global_cfg = explode(',',$post['global_cfg']);
            $file_txt .= "
/**
 * 全局参数
 *
 * 单票起步发运量_kg，单seller_sku最小发运量_个，最大额外发运天数
 *
 */
\$global_cfg = [
    'config_1' => [$global_cfg[0], $global_cfg[1], $global_cfg[2]]
];
            ";

            //缓冲天数
            $bs_cfg = $post['bs_cfg'];
            $file_txt .= "
/**
 * 缓冲天数
 */
\$bs_cfg = [
       ['bs'    =>  $bs_cfg]
];
            ";

            //最大安全库存天数
            $max_safe_stock_day_cfg = $post['max_safe_stock_day_cfg'];
            $file_txt .= "
/**
 * 最大安全天数
 */
\$max_safe_stock_day_cfg = [
    ['max_safe_stock_day'    =>  $max_safe_stock_day_cfg]
];
            ";

            file_put_contents(APPPATH . 'upload/'.$file_name,$file_txt);

            $code = 200;
            $this->data['status']             = 1;

        }catch (\InvalidArgumentException $e) {
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
     * 转为json前处理为str
     * @author Manson
     * @param $data
     * @return mixed
     */
    private function json_encode_pre($data)
    {
        foreach ($data as $key => &$item) {
            foreach ($item as $k => &$v) {
                $v = strval($v);
            }
        }

        return $data;
    }


    public function templateDownload()
    {
        try {
            $file_path               = APPPATH . 'upload/shipment_cfg.php';
            $this->data['file_path'] = $file_path;
            $this->data['status']    = 1;
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
     * 查询原始配置数据
     */
    public function get_cfg_data()
    {
        try {
            //接收get参数
            $get    = $this->compatible('get');
            $result = [];
            if (!file_exists(APPPATH . 'upload/shipment_cfg.php')) { //文件不存在返回空
                $this->data['status']    = 0;
                $this->data['errorMess'] = 'shipment_cfg.php文件不存在';
            } else {
                require_once APPPATH . 'upload/shipment_cfg.php';//引入文件
                $this->data['data_list'] = compact(['sales_amount_cfg', 'exhaust_cfg', 'in_warehouse_age_cfg', 'supply_day_cfg',  'sale_category_cfg', 'global_cfg']);;
                $this->data['status']    = 1;
            }
            $code = 200;
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
}
