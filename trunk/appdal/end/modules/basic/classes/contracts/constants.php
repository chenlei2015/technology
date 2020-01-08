<?php

/**
 * 导入导出限制
 */
define('MAX_EXCEL_LIMIT', 1000000);
define('MAX_BROWSE_LIMIT', 300000);

/**
 * 审核通用状态显示
 *
 * @var unknown
 */
define('APPROVAL_RESULT_PASS', 1);
define('APPROVAL_RESULT_FAILED', 2);

/**
 * 全局 1 是 2 否
 *
 * @var unknown
 */
define('GLOBAL_YES', 1);
define('GLOBAL_NO', 2);

/**
 * mrp业务线
 */
define('BUSINESS_LINE_FBA', 1);
define('BUSINESS_LINE_OVERSEA', 2);
define('BUSINESS_LINE_INLAND', 3);
define('BUSINESS_LINE', [
    BUSINESS_LINE_FBA => ['name' => 'FBA'],
    BUSINESS_LINE_OVERSEA => ['name' => '海外仓'],
    BUSINESS_LINE_INLAND => ['name' => '国内仓'],
]);

/**
 * fba - pr 需求单 - 审核状态 常量定量
 * @var unknown
 */
define('APPROVAL_STATE_NONE', 101);
define('APPROVAL_STATE_FIRST', 102);
define('APPROVAL_STATE_SECOND', 103);
define('APPROVAL_STATE_THREE', 104);
define('APPROVAL_STATE_UNDO', 126);
define('APPROVAL_STATE_SUCCESS', 128);
define('APPROVAL_STATE_FAIL', 127);
define('APPROVAL_STATE', [
    APPROVAL_STATE_NONE    => ['name' => '无需审核'],
    APPROVAL_STATE_UNDO    => ['name' => '不能审核'],
    APPROVAL_STATE_FIRST   => ['name' => '待一级审核'],
    APPROVAL_STATE_SECOND  => ['name' => '待二级审核'],
    APPROVAL_STATE_THREE   => ['name' => '待三级审核'],
    APPROVAL_STATE_SUCCESS => ['name' => '审核成功'],
    APPROVAL_STATE_FAIL    => ['name' => '审核失败'],
]);

/**
 * 是否触发需求
 * @var unknown
 */
define('TRIGGER_PR_YES', 1);
define('TRIGGER_PR_NO', 2);
define('TRIGGER_PR', [
    TRIGGER_PR_YES => ['name' => 'Y'],
    TRIGGER_PR_NO  => ['name' => 'N'],
]);

/**
 * 是否计划审核
 *
 * @var unknown
 */
define('NEED_PLAN_APPROVAL_YES', 1);
define('NEED_PLAN_APPROVAL_NO', 2);
define('NEED_PLAN_APPROVAL', [
    NEED_PLAN_APPROVAL_YES => ['name' => 'Y'],
    TRIGGER_PR_NO          => ['name' => 'N'],
]);

/**
 * 是否过期
 *
 * @var unknown
 */
define('FBA_PR_EXPIRED_YES', 1);
define('FBA_PR_EXPIRED_NO', 2);
define('FBA_PR_EXPIRED', [
    FBA_PR_EXPIRED_YES => ['name' => '过期'],
    FBA_PR_EXPIRED_NO  => ['name' => '正常'],
]);

/**
 * pr需求数量是否统计
 *
 * @var unknown
 */
define('PR_IS_ADDUP_YES', 1);
define('PR_IS_ADDUP_NO', 2);

/**
 * FBA站点
 *
 * @var unknown
 */

define('FBA_STATION_CODE', [
    'au' => ['name' => '澳大利亚'],
    'ca' => ['name' => '加拿大'],
    'de' => ['name' => '德国', 'is_eu' => true],
    'fr' => ['name' => '法国', 'is_eu' => true],
    'it' => ['name' => '意大利', 'is_eu' => true],
    'jp' => ['name' => '日本'],
    'mx' => ['name' => '墨西哥'],
    'es' => ['name' => '西班牙', 'is_eu' => true],//erp上的是sp,sp又被转成了eu欧洲站点,但是计划系统一是es
    'uk' => ['name' => '英国', 'is_eu' => true],
    'us' => ['name' => '美国'],
    'eu' => ['name' => '欧洲'],
    'ae' => ['name' => '中东'],
    'in' => ['name' => '印度'],

]);


/**
 * 审核状态
 *
 * @var unknown
 */
define('CHECK_STATE_INIT', 1);
define('CHECK_STATE_SUCCESS', 2);
define('CHECK_STATE_FAIL', 3);
define('CHECK_STATE', [
    CHECK_STATE_INIT    => ['name' => '待审核'],
    CHECK_STATE_SUCCESS => ['name' => '审核成功'],
    CHECK_STATE_FAIL    => ['name' => '审核失败']
]);

/**
 * 规则类型
 *
 * @var unknown
 */
define('RULE_TYPE_DIY', 1);
define('RULE_TYPE_GLOBAL', 2);
define('RULE_TYPE', [
    RULE_TYPE_DIY    => ['name' => '自定义'],
    RULE_TYPE_GLOBAL => ['name' => '全局']
]);

/**
 * v1.1.1物流属性
 * v1.4.0 将"海外物流属性配置表"中"物流属性"改为"头程运输方式明细"
 */
define('LOGISTICS_UNKNOWN',0);//待设置
define('LOGISTICS_ATTR_SHIPPING_FULL', 1);//海运整柜
define('LOGISTICS_ATTR_SHIPPING_BULK', 2);//海运散货
define('LOGISTICS_ATTR_TRAINS_FULL', 3);//铁运整柜
define('LOGISTICS_ATTR_TRAINS_BULK', 4);//铁运散货
define('LOGISTICS_ATTR_LAND', 5);//陆运
define('LOGISTICS_ATTR_AIR', 6);//空运
define('LOGISTICS_ATTR_RED', 7);//红单
define('LOGISTICS_ATTR_BLUE', 8);//蓝单
define('LOGISTICS_ATTR', [
    LOGISTICS_ATTR_SHIPPING_FULL => ['name' => '海运整柜', 'code' => 'HYZG'],
    LOGISTICS_ATTR_SHIPPING_BULK => ['name' => '海运散货', 'code' => 'HYSH', 'business_line' => BUSINESS_LINE_FBA],
    LOGISTICS_ATTR_TRAINS_FULL   => ['name' => '铁运整柜', 'code' => 'TJP2'],
    LOGISTICS_ATTR_TRAINS_BULK   => ['name' => '铁运散货', 'code' => 'TJP1'],
    LOGISTICS_ATTR_LAND          => ['name' => '陆运', 'code' => 'LYSH'],
    LOGISTICS_ATTR_AIR           => ['name' => '空运', 'code' => 'KJP1', 'business_line' => BUSINESS_LINE_FBA],
    LOGISTICS_ATTR_RED           => ['name' => '红单', 'code' => 'KD'],
    LOGISTICS_ATTR_BLUE          => ['name' => '蓝单', 'code' => 'KD'],
    LOGISTICS_UNKNOWN            => ['name'=>'待设置','code'=>'','has_unknown'=>true],

]);


/**
 * v1.4.0 添加 头程运输方式大类 1:海运;2:陆运;3:空运;4:铁运
 */
define('DETAILS_OF_FIRST_WAY_TRANSPORTATION_SEA', 1);//海运
define('DETAILS_OF_FIRST_WAY_TRANSPORTATION_LAND', 2);//陆运
define('DETAILS_OF_FIRST_WAY_TRANSPORTATION_AIR', 3);//空运
define('DETAILS_OF_FIRST_WAY_TRANSPORTATION_RAIL', 4);//铁运
define('DETAILS_OF_FIRST_WAY_TRANSPORTATION', [
    DETAILS_OF_FIRST_WAY_TRANSPORTATION_SEA           => ['name' => '海运',],
    DETAILS_OF_FIRST_WAY_TRANSPORTATION_LAND          => ['name' => '陆运',],
    DETAILS_OF_FIRST_WAY_TRANSPORTATION_AIR           => ['name' => '空运',],
    DETAILS_OF_FIRST_WAY_TRANSPORTATION_RAIL          => ['name' => '铁运',],
    LOGISTICS_UNKNOWN            => ['name'=>'待设置','code'=>'','has_unknown'=>true],

]);

/**
 * 头程运输方式明细 与 头程运输方式大类的映射关系
 */
define('LOGISTICS_ATTR_TO_DETAILS_OF_FIRST_WAY_TRANSPORTATION', [
    LOGISTICS_ATTR_SHIPPING_FULL => DETAILS_OF_FIRST_WAY_TRANSPORTATION_SEA,
    LOGISTICS_ATTR_SHIPPING_BULK => DETAILS_OF_FIRST_WAY_TRANSPORTATION_SEA,
    LOGISTICS_ATTR_TRAINS_FULL   => DETAILS_OF_FIRST_WAY_TRANSPORTATION_RAIL,
    LOGISTICS_ATTR_TRAINS_BULK   => DETAILS_OF_FIRST_WAY_TRANSPORTATION_RAIL,
    LOGISTICS_ATTR_LAND          => DETAILS_OF_FIRST_WAY_TRANSPORTATION_LAND,
    LOGISTICS_ATTR_AIR           => DETAILS_OF_FIRST_WAY_TRANSPORTATION_AIR,
    LOGISTICS_ATTR_RED           => DETAILS_OF_FIRST_WAY_TRANSPORTATION_AIR,
    LOGISTICS_ATTR_BLUE          => DETAILS_OF_FIRST_WAY_TRANSPORTATION_AIR,
    LOGISTICS_UNKNOWN            => LOGISTICS_UNKNOWN,
]);



define('FBA_LOGISTICS_ATTR', [
    LOGISTICS_ATTR_SHIPPING_BULK => ['name' => '海运散货', 'code' => 'HYSG'],
    LOGISTICS_ATTR_AIR           => ['name' => '空运', 'code' => 'KJP1'],
]);

//物流属性id映射物流系统的id
define('LOGISTICS_ATTR_MAP', [
    1 => 29,  //海运整柜
    2 => 28,  //海运散货
    3 => 30,  //铁运整柜
    4 => 12,  //铁运散货
    5 => 26,  //陆运
    6 => 8,   //空运
    7 => 27,  //红单 => 快递
    8 => 27,  //蓝单 => 快递
]);

/**
 * sku产品状态 在售品、新产品、清仓品、下架品
 *
 * @var unknown
 */
define('SKU_STATE_UP', 1);
define('SKU_STATE_DOWN', 2);
define('SKU_STATE_CLEAN', 3);
define('SKU_STATE_NEW', 4);
define('SKU_STATE_UNKNOWN', 5);
define('SKU_STATE', [
    SKU_STATE_UP      => ['name' => '在售品'],
    SKU_STATE_DOWN    => ['name' => '下架品'],
    SKU_STATE_CLEAN   => ['name' => '清仓品'],
    SKU_STATE_NEW     => ['name' => '新产品'],
    SKU_STATE_UNKNOWN => ['name' => '未知'],
]);

/**
 * lisitng状态 1运营 2停止运营
 */
define('LISTING_STATE_OPERATING', 1);
define('LISTING_STATE_STOP_OPERATE', 2);
define('LISTING_STATE', [
    LISTING_STATE_OPERATING    => ['name' => '运营'],
    LISTING_STATE_STOP_OPERATE => ['name' => '停止运营'],
]);

/**
 * 允许空海混发状态
 */
define('MIX_HAIR_YES',1);
define('MIX_HAIR_NO',2);
define('MIX_HAIR_STATE',[
    MIX_HAIR_YES    => ['name' => '是'],
    MIX_HAIR_NO => ['name' => '否'],
]);


/**
 * 账号启用状态
 * @var unknown
 */
define('FBA_ACCOUNT_MGR_ENABLE_YES', 1);
define('FBA_ACCOUNT_MGR_ENABLE_NO', 2);

/**
 * 用户角色
 *
 * @var unknown
 */
define('USER_ROLE_MANAGER', 1);
define('USER_ROLE_SECOND', 2);
define('USER_ROLE_THREE', 3);
define('USER_ROLE', [
    USER_ROLE_MANAGER => ['name' => '管理员'],
    USER_ROLE_SECOND  => ['name' => '二级审核'],
    USER_ROLE_THREE   => ['name' => '三级审核'],
]);

/**
 * 需求业务线
 *
 * @var unknown
 */
define('BUSSINESS_FBA', 1);
define('BUSSINESS_OVERSEA', 2);
define('BUSSINESS_IN', 3);
define('BUSSINESS_LINE', [
    BUSSINESS_FBA     => ['name' => 'FBA'],
    BUSSINESS_OVERSEA => ['name' => '海外'],
    BUSSINESS_IN      => ['name' => '国内'],
]);

/**
 * 数据权限
 * @var unknown
 */
define('DATA_PRIVILEGE_ALL', 1);
define('DATA_PRIVILEGE_PRIVATE', 2);
define('DATA_PRIVILEGE', [
    DATA_PRIVILEGE_ALL     => ['name' => '全部数据'],
    DATA_PRIVILEGE_PRIVATE => ['name' => '个人数据'],
]);

/**
 * 调拨虚拟仓
 *
 * @var unknown
 */
define('ALLOT_VM_WAREHOUSE_FBA', 1);
define('ALLOT_VM_WAREHOUSE_OVERSEA', 2);
define('ALLOT_VM_WAREHOUSE_IN', 3);
define('ALLOT_VM_WAREHOUSE', [
    ALLOT_VM_WAREHOUSE_FBA => ['name' => 'FBA'],
    BUSSINESS_OVERSEA      => ['name' => '海外'],
    ALLOT_VM_WAREHOUSE_IN  => ['name' => '国内'],
]);

/**
 * 备货状态
 *
 * @var unknown
 */
define('PUR_STATE_ING', 1);
define('PUR_STATE_FINISHED', 2);
define('PUR_STATE_PUSH_PUR', 3);
define('PUR_STATE_PUSHED_PUR', 4);
define('PUR_STATE_END', 5);
define('PUR_STATE', [
    PUR_STATE_ING        => ['name' => '待推送至调拨'],
    PUR_STATE_FINISHED   => ['name' => '已完结'],
    PUR_STATE_PUSH_PUR   => ['name' => '推送至调拨,等待回传'],
    PUR_STATE_PUSHED_PUR => ['name' => '调拨已回传,待推送采购'],
    PUR_STATE_END        => ['name' => '已推送至采购,已完结'],
]);

/**
 * 备货单数据推送状态
 *
 * @var unknown
 */
define('PUR_DATA_UNPUSH', 1);
define('PUR_DATA_PUSHED', 2);
define('PUR_DATA_STATE', [
    PUR_DATA_UNPUSH => ['name' => '未推送'],
    PUR_DATA_PUSHED => ['name' => '已推送'],
]);

/**
 * 海外站点列表
 */
define('OVERSEA_STATION_CODE', [
    'east'  => ['name' => '美东', 'erp_attribute_name' => '美国仓'],
    'west'  => ['name' => '美西', 'erp_attribute_name' => '美国仓'],
    'south' => ['name' => '美南', 'erp_attribute_name' => '美国仓'],
    'gb'    => ['name' => '英国', 'erp_attribute_name' => '英国仓'],
    'de'    => ['name' => '德国', 'erp_attribute_name' => '德国仓'],
    'es'    => ['name' => '西班牙', 'erp_attribute_name' => '西班牙仓'],
    //'au'    => ['name' => '澳洲', 'erp_attribute_name' => '澳洲仓'],
    'sy'    => ['name' => '悉尼',   'erp_attribute_name' => '澳洲仓'],
    'mb'    => ['name' => '墨尔本', 'erp_attribute_name' => '澳洲仓'],
    'fr'    => ['name' => '法国',   'erp_attribute_name' => '法国仓'],
    'ca'    => ['name' => '加拿大', 'erp_attribute_name' => '加拿大仓'],
    'it'    => ['name' => '意大利', 'erp_attribute_name' => '意大利仓'],
    'ru'    => ['name' => '俄罗斯', 'erp_attribute_name' => '俄罗斯仓'],
    'jp'    => ['name' => '日本', 'erp_attribute_name' => '日本仓'],
    'my'    => ['name' => '马来西亚', 'erp_attribute_name' => '马来西亚仓']
]);

/**
 * 亚马逊账号启用状态
 * @var unknown
 */
define('FBA_ACCOUNT_STATUS_ENABLE', 1);
define('FBA_ACCOUNT_STATUS_DISABLE', 2);
define('FBA_ACCOUNT_STATUS', [
    FBA_ACCOUNT_STATUS_ENABLE  => ['name' => '启用'],
    FBA_ACCOUNT_STATUS_DISABLE => ['name' => '禁用'],
]);

/**
 * 采购单状态
 */
define('PURCHASE_ORDER_STATUS_WAITING_QUOTE', 1); // 等待采购询价
//define('PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT', 2); // 信息变更等待审核
define('PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT', 3); // 待采购审核
//define('PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT', 5); // 待销售审核
define('PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER', 6); // 等待生成进货单
define('PURCHASE_ORDER_STATUS_WAITING_ARRIVAL', 7); // 等待到货
define('PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION', 8); // 已到货待检测
define('PURCHASE_ORDER_STATUS_ALL_ARRIVED', 9); // 全部到货
define('PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE', 10); // 部分到货等待剩余到货
define('PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE', 11); // 部分到货不等待剩余到货
define('PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT', 12); // 作废订单待审核
define('PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND', 13); // 作废订单待退款
define('PURCHASE_ORDER_STATUS_CANCELED', 14); // 已作废订单
define('PURCHASE_ORDER_STATUS', [
    PURCHASE_ORDER_STATUS_WAITING_QUOTE                  => ['name' => '等待采购询价'],
    //PURCHASE_ORDER_STATUS_INFO_CHANGE_WAITING_AUDIT    => ['name' => '信息变更等待审核'],
    PURCHASE_ORDER_STATUS_WAITING_PURCHASE_AUDIT         => ['name' => '待采购审核'],
    //PURCHASE_ORDER_STATUS_WAITING_SALE_AUDIT           => ['name' => '待销售审核'],
    PURCHASE_ORDER_STATUS_WAITING_CREATE_PURCHASE_ORDER  => ['name' => '等待生成进货单'],
    PURCHASE_ORDER_STATUS_WAITING_ARRIVAL                => ['name' => '等待到货'],
    PURCHASE_ORDER_STATUS_ARRIVED_WAITING_INSPECTION     => ['name' => '已到货待检测'],
    PURCHASE_ORDER_STATUS_ALL_ARRIVED                    => ['name' => '全部到货'],
    PURCHASE_ORDER_STATUS_PART_ARRIVED_WAITING_LEAVE     => ['name' => '部分到货等待剩余到货'],
    PURCHASE_ORDER_STATUS_PART_ARRIVED_NOT_WAITING_LEAVE => ['name' => '部分到货不等待剩余到货'],
    PURCHASE_ORDER_STATUS_CANCEL_WAITING_AUDIT           => ['name' => '作废订单待审核'],
    PURCHASE_ORDER_STATUS_CANCEL_WAITING_REFUND          => ['name' => '作废订单待退款'],
    PURCHASE_ORDER_STATUS_CANCELED                       => ['name' => '已作废订单']
]);

/**
 * 是否退税
 * @var unknown
 */
define('REFUND_TAX_YES', 1);
define('REFUND_TAX_NO', 2);
define('REFUND_TAX_UNKNOWN',3);
define('REFUND_TAX', [
    REFUND_TAX_YES     => ['name' => '退税'],
    REFUND_TAX_NO      => ['name' => '不退税'],
    REFUND_TAX_UNKNOWN => ['name' => '未知'],
]);

/**
 * 采购仓库（虚拟仓库）
 * w_age_code 为平均库龄code
 * @var unknown
 */
define('PURCHASE_WAREHOUSE_FBA_TAX_NO', 1);
define('PURCHASE_WAREHOUSE_FBA_TAX_YES', 2);
define('PURCHASE_WAREHOUSE_EXCHANGE', 3);
define('PURCHASE_WAREHOUSE_INLAND_DG', 4);
define('PURCHASE_WAREHOUSE_SHZZ', 5);
define('PURCHASE_WAREHOUSE_CX', 6);
define('PURCHASE_WAREHOUSE_AM', 7);
define('PURCHASE_WAREHOUSE_YB_TXAMTS', 8);
define('PURCHASE_WAREHOUSE_TCXNC', 9);
define('PURCHASE_WAREHOUSE_HM_AA', 10);
define('PURCHASE_WAREHOUSE', [
    PURCHASE_WAREHOUSE_FBA_TAX_NO  => ['w_age_code' => 'SZ_AA', 'code' => 'FBA_SZ_AA', 'name' => 'FBA虚拟仓_塘厦', 'business_line' => BUSSINESS_FBA],
    PURCHASE_WAREHOUSE_FBA_TAX_YES => ['w_age_code' => 'SZ_AA', 'code' => 'TS', 'name' => 'FBA退税仓_塘厦', 'business_line' => BUSSINESS_FBA],
    PURCHASE_WAREHOUSE_EXCHANGE    => ['w_age_code' => '', 'code' => 'AFN', 'name' => '中转仓_虎门', 'business_line' => BUSSINESS_OVERSEA],
    PURCHASE_WAREHOUSE_INLAND_DG   => ['w_age_code' => 'SZ_AA', 'code' => 'SZ_AA', 'name' => '小包仓_塘厦', 'business_line' => BUSSINESS_IN],
    PURCHASE_WAREHOUSE_SHZZ        => ['w_age_code' => '', 'code' => 'shzz', 'name' => '中转仓_慈溪', 'business_line' => BUSSINESS_OVERSEA],
    PURCHASE_WAREHOUSE_CX          => ['w_age_code' => 'CX', 'code' => 'CX', 'name' => '小包仓-慈溪', 'business_line' => BUSSINESS_IN],
    PURCHASE_WAREHOUSE_AM          => ['w_age_code' => 'AM', 'code' => 'AM', 'name' => 'FBA精品仓_塘厦', 'business_line' => BUSSINESS_FBA],
    PURCHASE_WAREHOUSE_YB_TXAMTS   => ['w_age_code' => '', 'code' => 'YB_TXAMTS', 'name' => '塘厦AM精品退税仓', 'business_line' => BUSSINESS_FBA],
    PURCHASE_WAREHOUSE_TCXNC       => ['w_age_code' => 'SZ_AA', 'code' => 'TCXNC', 'name' => '平台头程虚拟仓_塘厦', 'business_line' => BUSSINESS_IN],
    PURCHASE_WAREHOUSE_HM_AA       => ['w_age_code' => 'HM_AA', 'code' => 'HM_AA', 'name' => '小包仓_虎门', 'business_line' => BUSSINESS_IN],
]);

/**
 * warehouse_code和warehouse_id映射关系
 */
define('FBA_SZ_AA', 1);//FBA虚拟仓_塘厦
define('TS', 2);//FBA退税仓_塘厦
define('AFN', 3);//中转仓_虎门
define('SZ_AA', 4);//小包仓_塘厦
define('shzz', 5);//中转仓_慈溪
define('CX', 6);//小包仓-慈溪
define('AM', 7);//FBA精品仓_塘厦
define('YB_TXAMTS', 8);//塘厦AM精品退税仓
define('TCXNC', 9);//平台头程虚拟仓_塘厦
define('WAREHOUSE_CODE', [
    'FBA_SZ_AA' => 1,
    'TS'        => 2,
    'AFN'       => 3,
    'SZ_AA'     => 4,
    'shzz'      => 5,
    'CX'        => 6,
    'AM'        => 7,
    'YB_TXAMTS' => 8,
    'TCXNC'     => 9,
]);

/**
 * csv导出格式数据可读格式
 *
 * @var unknown
 */
define('EXPORT_VIEW_PRETTY', 1);
define('EXPORT_VIEW_NATIVE', 2);

/**
 * csv输出方式
 * @var unknown
 */
define('VIEW_BROWSER', 1);
define('VIEW_FILE', 2);
define('VIEW_AUTO', 3);

/**
 * 数据类型, 数据类型 1 全部 2 销量 3 库存 4 交付周期
 *
 * @var unknown
 */
define('MRP_DOWNLOAD_TYPE_ALL', 1);
define('MRP_DOWNLOAD_TYPE_SALE', 2);
define('MRP_DOWNLOAD_TYPE_STORAGE', 3);
define('MRP_DOWNLOAD_TYPE_DELIVER', 4);
define('MRP_DOWNLOAD_DATA_TYPE', [
    MRP_DOWNLOAD_TYPE_ALL     => ['name' => '全部'],
    MRP_DOWNLOAD_TYPE_SALE    => ['name' => '销量'],
    MRP_DOWNLOAD_TYPE_STORAGE => ['name' => '库存'],
    MRP_DOWNLOAD_TYPE_DELIVER => ['name' => '交付周期'],
]);

/**
 * 状况表时间维度类型  1月 2周 3日
 */
define('TIME_TYPE_MONTH', 1);
define('TIME_TYPE_WEEK', 2);
define('TIME_TYPE_DAY', 3);
define('TIME_TYPE', [
    TIME_TYPE_MONTH => 'month',
    TIME_TYPE_WEEK  => 'week',
    TIME_TYPE_DAY   => 'day',
]);


/**
 * 国内仓货源状态 provider_status  0未知、1正常、2停产 、3断货
 */
define('PROVIDER_STATUS_UNKNOWN', 0);
define('PROVIDER_STATUS_NORMAL', 1);
define('PROVIDER_STATUS_STOP', 2);
define('PROVIDER_STATUS_BROKEN', 3);
define('PROVIDER_STATUS', [
    PROVIDER_STATUS_UNKNOWN => ['name' => '未知'],
    PROVIDER_STATUS_NORMAL  => ['name' => '正常'],
    PROVIDER_STATUS_STOP    => ['name' => '停产'],
    PROVIDER_STATUS_BROKEN  => ['name' => '断货'],

]);

/**
 * 国内仓sku状态
 */
define('INLAND_SKU_STATE_SALE', 4);
define('INLAND_SKU_STATE_TRY_SALE', 18);
define('INLAND_SKU_STATE', [
    INLAND_SKU_STATE_SALE     => ['name' => '在售中'],
    INLAND_SKU_STATE_TRY_SALE => ['name' => '试卖在售中'],
]);

/**
 * 国内仓特殊备货需求_审核状态  1 无需审核 2 待审核 3 审核失败 4 成功',
 */
define('SPECIAL_CHECK_STATE_UNAUDITED', 1);
define('SPECIAL_CHECK_STATE_INIT', 2);
define('SPECIAL_CHECK_STATE_FAIL', 3);
define('SPECIAL_CHECK_STATE_SUCCESS', 4);
define('SPECIAL_CHECK_STATE', [
    SPECIAL_CHECK_STATE_UNAUDITED => ['name' => '无需审核'],
    SPECIAL_CHECK_STATE_INIT      => ['name' => '待审核'],
    SPECIAL_CHECK_STATE_FAIL      => ['name' => '审核失败'],
    SPECIAL_CHECK_STATE_SUCCESS   => ['name' => '审核成功']
]);


/**
 * 国内仓备货方式
 * @var unknown
 */
define('STOCK_UP_NORMAL', 1);
define('STOCK_UP_APPEND', 2);
define('STOCK_UP_NONE', 3);
define('STOCK_UP_TYPE', [
    STOCK_UP_NORMAL => ['name' => '正常备货'],
    STOCK_UP_APPEND => ['name' => '出单补货'],
    STOCK_UP_NONE   => ['name' => '不备货'],
]);

/**
 * SKU是否匹配
 */
define('SKU_MATCH_STATE_TRUE', 1);
define('SKU_MATCH_STATE_FALSE', 2);
define('SKU_MATCH_STATE', [
    SKU_MATCH_STATE_TRUE  => ['name' => '已匹配'],
    SKU_MATCH_STATE_FALSE => ['name' => '未匹配'],
]);

/**
 * 国内特殊备货记录状态
 */
define('SPECIAL_PR_STATE_NORMAL', 1);
define('SPECIAL_PR_STATE_DELETE', 2);
define('SPECIAL_PR_STATE', [
    SPECIAL_PR_STATE_NORMAL => ['name' => '正常'],
    SPECIAL_PR_STATE_DELETE => ['name' => '删除'],
]);

/**
 * 国内仓平台名称对应code, 海外仓也对应此表
 * 'EB' 平台code
 * 'Ebay' 平台名称
 */
define('INLAND_PLATFORM_CODE', [
    'EB'        => ['name' => 'Ebay', 'oversea' => true],
    'ALI'       => ['name' => 'Aliexpress', 'oversea' => true],
    'WISH'      => ['name' => 'Wish', 'oversea' => true],
    'AMAZON'    => ['name' => 'Amazon', 'oversea' => true],
    'LAZADA'    => ['name' => 'LAZADA'],
    'SHOPEE'    => ['name' => 'SHOPEE'],
    'CDISCOUNT' => ['name' => 'CDISCOUNT', 'oversea' => true],
    'WALMART'   => ['name' => 'WALMART', 'oversea' => true],
    'MALL'      => ['name' => 'MALL'],
    'BB'        => ['name' => 'Alibaba'],
    'JOOM'      => ['name' => 'JOOM'],
    'STR'       => ['name' => '11street'],
    'JUM'       => ['name' => 'jumia'],
    'GRO'       => ['name' => 'Groupon'],
    'INW'       => ['name' => '独立网站'],
    'JOL'       => ['name' => 'jollychic'],
    'SOU'       => ['name' => 'Souq'],
    'PM'        => ['name' => 'priceminister'],
    'WADI'      => ['name' => 'Wadi'],
    'OBERLO'    => ['name' => 'oberlo'],
    'NEWEGG'    => ['name' => 'newegg'],
    'VOVA'      => ['name' => 'vova'],
    'TOP'       => ['name' => 'tophatter'],
    'KiKUU'     => ['name' => 'KiKUU'],
    'JD-TH'     => ['name' => 'JDTH'],
    'NOON'      => ['name' => 'NOON'],
    'FNAC'      => ['name' => 'FNAC'],
    'MERC'      => ['name' => 'MercadoLibre'],
    'DAR'       => ['name' => 'daraz'],
]);


/**
 * 是否精品
 */
define('INLAND_QUALITY_GOODS_TRUE', 1);
define('INLAND_QUALITY_GOODS_FALSE', 2);
define('INLAND_QUALITY_GOODS', [
    INLAND_QUALITY_GOODS_TRUE  => ['name' => '是'],
    INLAND_QUALITY_GOODS_FALSE => ['name' => '否'],
]);

/**
 *
 * @var unknown
 */
define('INLAND_SKU_ALL_STATE', [
    0  => ['name' => '审核不通过', 'listing_state' => SKU_STATE_CLEAN],
    1  => ['name' => '刚开发', 'listing_state' => SKU_STATE_NEW,],
    2  => ['name' => '编辑中', 'listing_state' => SKU_STATE_NEW],
    3  => ['name' => '预上线', 'listing_state' => SKU_STATE_NEW],
    4  => ['name' => '在售中', 'listing_state' => SKU_STATE_UP],
    5  => ['name' => '已滞销', 'listing_state' => SKU_STATE_CLEAN],
    6  => ['name' => '待清仓', 'listing_state' => SKU_STATE_CLEAN],
    7  => ['name' => '已停售', 'listing_state' => SKU_STATE_CLEAN],
    8  => ['name' => '待买样', 'listing_state' => SKU_STATE_NEW],
    9  => ['name' => '待品检', 'listing_state' => SKU_STATE_NEW],
    10 => ['name' => '拍摄中', 'listing_state' => SKU_STATE_NEW],
    11 => ['name' => '产品信息确认', 'listing_state' => SKU_STATE_NEW],
    12 => ['name' => '修图中', 'listing_state' => SKU_STATE_NEW],
//    13 => ['name' => ''],
    14 => ['name' => '设计审核中', 'listing_state' => SKU_STATE_NEW],
    15 => ['name' => '文案审核中', 'listing_state' => SKU_STATE_NEW],
    16 => ['name' => '文案主管终审中', 'listing_state' => SKU_STATE_NEW],
    17 => ['name' => '试卖编辑中', 'listing_state' => SKU_STATE_NEW],
    18 => ['name' => '试卖在售中', 'listing_state' => SKU_STATE_UP],
    19 => ['name' => '试卖文案终审中', 'listing_state' => SKU_STATE_NEW],
    20 => ['name' => '预上线拍摄中', 'listing_state' => SKU_STATE_NEW],
    21 => ['name' => '物流审核中', 'listing_state' => SKU_STATE_NEW],
//   22 => ['name' => ''],
//   23 => ['name' => ''],
//   24 => ['name' => ''],
//    25 => ['name' => ''],
//    26 => ['name' => ''],
    27 => ['name' => '作图审核中', 'listing_state' => SKU_STATE_NEW],
    28 => ['name' => '关务审核中', 'listing_state' => SKU_STATE_NEW],
    29 => ['name' => '开发检查中', 'listing_state' => SKU_STATE_NEW],
    30 => ['name' => '已编辑，拍摄中', 'listing_state' => SKU_STATE_NEW],
    31 => ['name' => '编辑中，拍摄中', 'listing_state' => SKU_STATE_NEW],
    32 => ['name' => '已编辑，拍摄中', 'listing_state' => SKU_STATE_NEW],
    33 => ['name' => '编辑中，已拍摄', 'listing_state' => SKU_STATE_NEW],
    35 => ['name' => '新系统开发中', 'listing_state' => SKU_STATE_NEW],
]);

/**
 * 是否可以推送的数量
 */
define('PURCHASE_CAN_PUSH_YES', 1);
define('PURCHASE_CAN_PUSH_NO', 2);
define('PURCHASE_CAN_PUSH', [
    PURCHASE_CAN_PUSH_YES => ['name' => 'Y'],
    PURCHASE_CAN_PUSH_NO  => ['name' => 'N'],
]);

/**
 * 促销sku的状态
 * @var unknown
 */
define('PROMOTION_SKU_RUNNING', 1);
define('PROMOTION_SKU_FINISHED', 2);
define('PROMOTION_SKU_DELETED', 3);
define('PROMOTION_SKU_STATE', [
    PROMOTION_SKU_RUNNING  => ['name' => '运行中'],
    PROMOTION_SKU_FINISHED => ['name' => '已结束'],
    PROMOTION_SKU_DELETED  => ['name' => '已删除'],
]);

/**
 * 国内平台审核状态
 */
define('OVERSEA_PLATFORM_APPROVAL_STATE_FIRST', 102);
define('OVERSEA_PLATFORM_APPROVAL_STATE_FAIL', 127);
define('OVERSEA_PLATFORM_APPROVAL_STATE_SUCCESS', 128);
define('OVERSEA_PLATFROM_APPROVAL_STATE', [
    OVERSEA_PLATFORM_APPROVAL_STATE_FIRST   => ['name' => '待销售审核'],
    OVERSEA_PLATFORM_APPROVAL_STATE_FAIL    => ['name' => '审核失败'],
    OVERSEA_PLATFORM_APPROVAL_STATE_SUCCESS => ['name' => '审核成功'],
]);


/**
 * 海外平台审核状态
 */
define('OVERSEA_SUMMARY_APPROVAL_STATE_FIRST', 104);
define('OVERSEA_SUMMARY_APPROVAL_STATE_FAIL', 127);
define('OVERSEA_SUMMARY_APPROVAL_STATE_SUCCESS', 128);
define('OVERSEA_SUMMARY_APPROVAL_STATE', [
    OVERSEA_SUMMARY_APPROVAL_STATE_FIRST   => ['name' => '待审核'],
    OVERSEA_SUMMARY_APPROVAL_STATE_FAIL    => ['name' => '审核失败'],
    OVERSEA_SUMMARY_APPROVAL_STATE_SUCCESS => ['name' => '审核成功'],
]);

/**
 * 海外需求列表是否显示
 *
 * @var unknown
 */
define('OVERSEA_STATION_DISPLAY_NO', 1);
define('OVERSEA_STATION_DISPLAY_YES', 2);

/**
 * 主/被动模式
 *
 * @var unknown
 */
define('TRIGGER_MODE_NONE', 1);
define('TRIGGER_MODE_ACTIVE', 2);
define('TRIGGER_MODE_INACTIVE', 3);
//临时状态，重建需求列表时被动未确定的定位5
define('TRIGGER_MODE_REBUILD_TEMP', 5);

//临时状态，不对外显示
define('TRIGGER_MODE_UNKOWN', 99);
define('TRIGGER_MODE_STATE', [
    TRIGGER_MODE_NONE     => ['name' => '-'],
    TRIGGER_MODE_ACTIVE   => ['name' => '主动'],
    TRIGGER_MODE_INACTIVE => ['name' => '被动'],
]);

/**
 * 是否因为失去主动记录而被降级
 * @var unknown
 */
define('TRIGGER_LOST_ACTIVE_YES', 1);
define('TRIGGER_LOST_ACTIVE_NORMAL', 2);


/**
 * 备货列表产品状态
 */
define('PLAN_PRODUCT_STATUS_ON_SALE', 1);
define('PLAN_PRODUCT_STATUS_NEW', 2);
define('PLAN_PRODUCT_STATUS', [
    PLAN_PRODUCT_STATUS_ON_SALE => ['name' => '在售品'],
    PLAN_PRODUCT_STATUS_NEW     => ['name' => '新品'],
]);

/**
 * 发运计划业务线
 */
define('SHIPMENT_BUSINESS_LINE',[
    BUSINESS_LINE_FBA => ['name' => 'FBA仓'],
    BUSINESS_LINE_OVERSEA => ['name' => '海外仓'],
    BUSINESS_LINE_INLAND => ['name' => '国内仓'],
]);


/**
 * 发运计划推送状态
 *
 * @var unknown
 */
define('SHIPMENT_WAITING_SEND', 1);//待发送至物流系统
define('SHIPMENT_WAITING_BACK', 2);//待物流系统回传
define('SHIPMENT_WAITING_PUSH', 3);//待推送
define('SHIPMENT_PUSHED', 4);//已推送
define('SHIPMENT_SEND_FAIL', 5);//发送至物流系统失败
define('SHIPMENT_STATE', [
    SHIPMENT_WAITING_SEND => ['name' => '待发送至物流系统'],
    SHIPMENT_SEND_FAIL => ['name' => '发送至物流系统失败'],
    SHIPMENT_WAITING_BACK => ['name' => '待物流系统回传'],
    SHIPMENT_WAITING_PUSH => ['name' => '待推送'],
    SHIPMENT_PUSHED => ['name' => '已推送'],
]);

/**
 * 站点对应国家
 */
define('STATION_COUNTRY_MAP', [
    'au' => ['name' => '澳洲', 'code'=>'AU'],
    'ca' => ['name' => '加拿大', 'code'=>'CA'],
    'de' => ['name' => '德国', 'code'=>'DE'],
    'east' => ['name' => '美国', 'code'=>'US'],
    'es' => ['name' => '西班牙', 'code'=>'ES'],
    'fr' => ['name' => '法国', 'code'=>'FR'],
    'gb' => ['name' => '英国', 'code'=>'GB'],
    'it' => ['name' => '意大利', 'code'=>'IT'],
    'ru' => ['name' => '俄罗斯', 'code'=>'RU'],
    'south' => ['name' => '美国', 'code'=>'US'],
    'west' => ['name' => '美国', 'code'=>'US'],
    'jp'    => ['name' => '日本', 'code'=>'JP'],
    'my' => ['name' => '马来西亚', 'code'=>'MY'],
    'mx' => ['name' => '墨西哥', 'code'=>'MX'],
    'uk' => ['name' => '英国', 'code'=>'GB'],
    'us' => ['name' => '美国', 'code'=>'US'],
    'eu' => ['name' => '欧洲', 'code'=>''],
]);

/**
 * 发运计划业务类型
 */
define('BUSINESS_TYPE_PLATFORM', 1);//平台仓
define('BUSINESS_TYPE_OVERSEA', 2);//海外仓
define('BUSINESS_TYPE',[
    BUSINESS_TYPE_PLATFORM => ['name' => '平台仓'],
    BUSINESS_TYPE_OVERSEA => ['name' => '海外仓'],
]);

//发运类型
define('SHIPMENT_TYPE_FACTORY', 1);
define('SHIPMENT_TYPE_EXCHANGE', 2);
define('SHIPMENT_TYPE_LIST', [
    SHIPMENT_TYPE_FACTORY => ['name' => '厂家直发'],
    SHIPMENT_TYPE_EXCHANGE => ['name' => '中转仓发货'],
]);


define('SHIPMENT_STATUS_NOT_SHIPMENT', 1);
define('SHIPMENT_STATUS_PARTIAL_SHIPMENT', 2);
define('SHIPMENT_STATUS_ALL_SHIPMENT', 3);
define('SHIPMENT_STATUS', [
    SHIPMENT_STATUS_NOT_SHIPMENT => ['name' => '未发运'],
    SHIPMENT_STATUS_PARTIAL_SHIPMENT => ['name' => '部分发运'],
    SHIPMENT_STATUS_ALL_SHIPMENT => ['name' => '全部发运'],
]);

/**
 * 是否精品
 *
 * @var unknown
 */
define('BOUTIQUE_YES', 1);
define('BOUTIQUE_NO', 2);
define('BOUTIQUE_STATE', [
    BOUTIQUE_YES => ['name' => '是'],
    BOUTIQUE_NO  => ['name' => '否'],
]);

/**
 * 是否商检
 *
 * @var unknown
 */
define('INSPECTION_NO', 1);
define('INSPECTION_YES', 2);
define('INSPECTION_STATE', [
    INSPECTION_NO  => ['name' => '否'],
    INSPECTION_YES => ['name' => '是'],
]);

/**
 * 是否熏蒸
 */
define('FUMIGATION_NO', 1);
define('FUMIGATION_YES', 2);
define('FUMIGATION_STATE', [
    FUMIGATION_NO  => ['name' => '否'],
    FUMIGATION_YES => ['name' => '是'],
]);


/**
 * 推送至物流系统  推送状态
 */
define('LOGISTICS_UNPUSH', 1);
define('LOGISTICS_PUSHED', 2);
define('LOGISTICS_BACKED', 3);
define('PUSH_STATUS_LOGISTICS', [
    LOGISTICS_UNPUSH => ['name' => '未推送'],
    LOGISTICS_PUSHED  => ['name' => '已推送'],
    LOGISTICS_BACKED  => ['name' => '已回传'],
]);

define('SHIPMENT_PLAN_TRACKING_DATE', 1);//类型:记录需求跟踪列表日期是否生成发运计划


/**
 * 上传,非上传
 */
define('UPLOAD',1);
define('UN_UPLOAD',2);


/**
 * 仓库系统回传的  物流状态
 */
define('LOGISTICS_STATUS_IN_TRANSIT', 2);
define('LOGISTICS_STATUS_SIGNED', 4);
define('LOGISTICS_STATUS', [
    LOGISTICS_STATUS_IN_TRANSIT => ['name' => '物流在途'],
    LOGISTICS_STATUS_SIGNED => ['name' => '物流已签收'],
]);

/**
 * 活动审核状态, 支持多条业务线
 * @var array
 */
define('ACTIVITY_APPROVAL_INIT', 1);
define('ACTIVITY_APPROVAL_SUCCESS', 8);
define('ACTIVITY_APPROVAL_FAIL', 9);
define('ACTIVITY_APPROVAL_STATE', [
    ACTIVITY_APPROVAL_INIT    => ['name' => '待审核', 'is_fba' => true],
    ACTIVITY_APPROVAL_SUCCESS  => ['name' => '审核成功', 'is_fba' => true],
    ACTIVITY_APPROVAL_FAIL   => ['name' => '审核失败', 'is_fba' => true],
]);

/**
 * 活动状态, 支持多条业务线
 * @var array
 */
define('ACTIVITY_STATE_NOT_START', 1);
define('ACTIVITY_STATE_DISCARD', 2);
define('ACTIVITY_STATE_ING', 3);
define('ACTIVITY_STATE_END', 4);
define('ACTIVITY_STATE', [
    ACTIVITY_STATE_NOT_START  => ['name' => '未开始', 'is_fba' => true],
    ACTIVITY_STATE_ING  => ['name' => '进行中', 'is_fba' => true],
    ACTIVITY_STATE_DISCARD  => ['name' => '废弃', 'is_fba' => true],
    ACTIVITY_STATE_END  => ['name' => '已结束', 'is_fba' => true],
]);

/**
 * FBA配置表执行顺序
 */
define('FBA_LOGISTICS_CFG_LIST', 1);//任务: SELLERSKU属性配置表 inventory_month_end表新增和删除
define('FBA_LOGISTICS_CFG_LIST_UP', 2);//任务:处理sku_map更新和删除
define('FBA_CHECK_PAN_EU', 3);//任务:处理是否泛欧记录
define('FBA_STOCK_CFG_LIST', 4);//任务: ERPSKU属性配置表
define('FBA_ACCELERATE_SALE_STATE', 5);//任务:是否停止加快动销
define('FBA_PUR_PRODUCT_INFO', 6);//任务:成本价,货源状态,MOQ数量,供应商编码
define('FBA_AVG_INVENTORY_AGE', 7);//任务:平均库龄
define('FBA_IS_BOUTIQUE', 8);//任务：FBA是否精品
define('FBA_DELIVERY_CYCLE', 9);//任务: fba更新发货周期
define('FBA_IS_REFUND_TAX', 10);//任务:fba更新是否退税字段
define('FBA_SKU_STATE_A', 11);//任务:ERPSKU属性配置表同步erp上的产品状态
define('FBA_SKU_STATE_B', 12);//任务:SELLERSKU属性配置表同步erp上的产品状态
define('FBA_UPDATE_LEAD_TIME', 13);//任务:供货周期

define('FBA_PLAN_SCRIPT', [
    FBA_STOCK_CFG_LIST        => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_sku_cfg', 'title' => 'FBA增量拉取ERPSKU属性配置表数据'],
    FBA_IS_BOUTIQUE           => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_sku_cfg', 'title' => 'FBA更新是否精品字段'],
    FBA_IS_REFUND_TAX         => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_sku_cfg', 'title' => 'FBA更新是否退税字段'],
    FBA_PUR_PRODUCT_INFO      => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_sku_cfg', 'title' => '同步采购系统的sku信息'],
    FBA_AVG_INVENTORY_AGE     => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_sku_cfg', 'title' => '平均库龄'],
    FBA_ACCELERATE_SALE_STATE => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_sku_cfg', 'title' => '是否停止加快动销'],
    FBA_SKU_STATE_A           => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_sku_cfg', 'title' => 'FBA更新ERPSKU属性配置表SKU状态'],
    FBA_UPDATE_LEAD_TIME      => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_sku_cfg', 'title' => '同步供货周期'],


    FBA_LOGISTICS_CFG_LIST    => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_logistics_list', 'title' => 'FBA增量拉取SELLERSKU属性配置表数据'],
    FBA_LOGISTICS_CFG_LIST_UP => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_logistics_list', 'title' => '处理sku_map更新和删除'],
    FBA_CHECK_PAN_EU          => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_logistics_list', 'title' => '处理是否泛欧记录'],
    FBA_DELIVERY_CYCLE        => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_logistics_list', 'title' => 'FBA更新发货周期字段'],
    FBA_SKU_STATE_B           => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_logistics_list', 'title' => 'FBA更新SELLERSKU属性配置表SKU状态'],
//    FBA_CHECK_SCRIPT_STATUS   => ['business_line' => BUSINESS_LINE_FBA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_fba_logistics_list', 'title' => '处理是否泛欧记录'],
]);
/**
 * 海外配置表执行顺序
 */
define('OVERSEA_INCREMENTAL_DATA_SOURCE', 1);//任务:海外备货关系配置表 和 海外物流属性配置表
define('OVERSEA_IS_BOUTIQUE', 2);//任务：海外是否精品
define('OVERSEA_IS_REFUND_TAX', 3);//任务:海外更新是否退税字段
define('OVERSEA_UPDATE_WAREHOUSE', 4);//任务:海外仓配置关系表定期匹配修改采购仓库
define('OVERSEA_SKU_STATE_A', 5);//任务:海外备货关系配置表同步erp上的产品状态
define('OVERSEA_SKU_STATE_B', 6);//任务:海外物流属性配置表同步erp上的产品状态
define('OVERSEA_PLAN_SCRIPT', [
    OVERSEA_INCREMENTAL_DATA_SOURCE => ['business_line' => BUSINESS_LINE_OVERSEA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_oversea_sku_cfg_main,yibai_oversea_sku_cfg_part,yibai_oversea_logistics_list', 'title' => '海外增量拉取备货关系配置表数据和物流属性配置表数据'],
    OVERSEA_IS_BOUTIQUE             => ['business_line' => BUSINESS_LINE_OVERSEA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_oversea_sku_cfg_main', 'title' => '海外更新是否精品字段'],
    OVERSEA_IS_REFUND_TAX           => ['business_line' => BUSINESS_LINE_OVERSEA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_oversea_sku_cfg_main', 'title' => '海外更新是否退税字段'],
    OVERSEA_UPDATE_WAREHOUSE        => ['business_line' => BUSINESS_LINE_OVERSEA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_oversea_sku_cfg_main', 'title' => '海外更新采购仓库字段'],
    OVERSEA_SKU_STATE_A             => ['business_line' => BUSINESS_LINE_OVERSEA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_oversea_sku_cfg_main', 'title' => '海外更新备货关系配置表SKU状态'],
    OVERSEA_SKU_STATE_B             => ['business_line' => BUSINESS_LINE_OVERSEA, 'database' => 'yibai_plan_stock', 'table' => 'yibai_oversea_logistics_list', 'title' => '海外更新物流属性配置表SKU状态'],
]);

/**
 * 是否停止加快动销 is_accelerate_sale
 * 1 不动销 2 动销中（清仓中） 3 未知
 */
define('ACCELERATE_SALE_NO', 1);
define('ACCELERATE_SALE_YES', 2);
define('ACCELERATE_SALE_UNKNOWN', 3);

define('ACCELERATE_SALE_STATE', [
    ACCELERATE_SALE_NO      => ['name' => '否'],
    ACCELERATE_SALE_YES     => ['name' => '是'],
    ACCELERATE_SALE_UNKNOWN => ['name' => '-'],
]);

/**
 * 是否违禁 is_contraband
 */
define('CONTRABAND_STATE_YES', 1);
define('CONTRABAND_STATE_NO', 2);
define('CONTRABAND_STATE', [
    CONTRABAND_STATE_YES => ['name' => '是'],
    CONTRABAND_STATE_NO  => ['name' => '否'],
]);


/**
 * 是否侵权 is_infringement
 */
define('INFRINGEMENT_STATE_YES', 1);
define('INFRINGEMENT_STATE_NO', 2);
define('INFRINGEMENT_STATE', [
    INFRINGEMENT_STATE_YES => ['name' => '是'],
    INFRINGEMENT_STATE_NO  => ['name' => '否'],
]);


/**
 * 重算需求列表的运行状态描述
 * @var unknown
 */
define('REBUILD_INIT', 1);
define('REBUILD_CFG_BACKUP_OVER', 2);
define('REBUILD_CFG_BUILDING', 3);
define('REBUILD_CFG_FINISHED', 4);

/**
 * 库存健康度诊断 1.健康 2.采购过量 3.有积压风险 4.严重积压
 * @var unknown
 */
define('INVENTORY_HEALTH_WELL', 1);
define('INVENTORY_HEALTH_ENOUGH', 2);
define('INVENTORY_HEALTH_WARNNING', 3);
define('INVENTORY_HEALTH_EMERGENT', 4);
define('INVENTORY_HEALTH_DESC', [
    INVENTORY_HEALTH_WELL => ['name' => '库存健康'],
    INVENTORY_HEALTH_ENOUGH  => ['name' => '采购过量'],
    INVENTORY_HEALTH_WARNNING  => ['name' => '积压风险'],
    INVENTORY_HEALTH_EMERGENT  => ['name' => '严重积压'],
]);


//1 无 2 “违禁”sku不能审核 3 停产/断货sku不能审核 4 Sku所属类目不能审核 5 严重积压/有积压风险的sku不能审核 6 停售sku不能审核
define('DENY_APPROVE_NONE', 1);
define('DENY_APPROVE_CONTRABAND', 2);
define('DENY_APPROVE_SUPPLIER_SUSPENDED_LACK', 3);
define('DENY_APPROVE_RESTRICT_CATEGORY', 4);
define('DENY_APPROVE_INVENTORY_HEALTH_EMERGENT_WARNNING', 5);
define('DENY_APPROVE_HALT_SKU', 6);

define('DENY_APPROVE_REASON', [
    DENY_APPROVE_NONE => ['name' => '无'],
    DENY_APPROVE_CONTRABAND  => ['name' => '“违禁”sku不能审核'],
    DENY_APPROVE_SUPPLIER_SUSPENDED_LACK  => ['name' => '停产/断货sku不能审核'],
    DENY_APPROVE_RESTRICT_CATEGORY  => ['name' => 'Sku所属类目不能审核'],
    DENY_APPROVE_INVENTORY_HEALTH_EMERGENT_WARNNING  => ['name' => '严重积压/有积压风险的sku不能审核'],
    DENY_APPROVE_HALT_SKU  => ['name' => '停售sku不能审核'],
]);

define('FBA_FIRST_SALE_YES', 1);
define('FBA_FIRST_SALE_NO', 2);
define('FBA_FIRST_SALE_UNKNOWN', 3);
define('FBA_FIRST_SALE_STATE', [
    FBA_FIRST_SALE_YES => ['name' => '是'],
    FBA_FIRST_SALE_NO  => ['name' => '否'],
    FBA_FIRST_SALE_UNKNOWN  => ['name' => '-'],
]);

//是否超过90天库龄
define('WAREHOURE_DAYS_OVER_90_YES', 1);
define('WAREHOURE_DAYS_OVER_90_NO', 2);
define('WAREHOURE_DAYS_OVER_90_STATE', [
    WAREHOURE_DAYS_OVER_90_YES => ['name' => '是'],
    WAREHOURE_DAYS_OVER_90_NO  => ['name' => '否'],
]);

//是否为导入sku is_import
define('IS_IMPORT_YES', 1);
define('IS_IMPORT_NO', 2);
define('IS_IMPORT', [
    IS_IMPORT_YES => ['name' => '是'],
    IS_IMPORT_NO  => ['name' => '否'],
]);

define('SYS_LOG_LEVEL', ['ERROR','INFO','DEBUG']);

/**
 * 新品审核状态
 * @var array
 */
define('NEW_APPROVAL_INIT', 0);
define('NEW_APPROVAL_SUCCESS', 1);
define('NEW_APPROVAL_FAIL', 2);
define('NEW_APPROVAL_STATE', [
    NEW_APPROVAL_INIT    => ['name' => '待审核', 'is_fba' => true],
    NEW_APPROVAL_SUCCESS  => ['name' => '审核成功', 'is_fba' => true],
    NEW_APPROVAL_FAIL   => ['name' => '审核失败', 'is_fba' => true],
]);

//是否FBA首发
define('IS_FIRST_SALE_YES', 1);
define('IS_FIRST_SALE_NO', 2);
define('IS_FIRST_SALE', [
    IS_FIRST_SALE_YES => ['name' => '是'],
    IS_FIRST_SALE_NO  => ['name' => '否'],
]);

//是否范欧
define('IS_PAN_EU_YES', 1);
define('IS_PAN_EU_NO', 2);
define('IS_PAN_EU', [
    IS_PAN_EU_YES => ['name' => '是'],
    IS_PAN_EU_NO  => ['name' => '否'],
]);

/**
 * MVCC状态 1 点击初始 2 备份完成 3 处理中 4 处理完成
 * @var int
 */
define('REBUILD_MVCC_START', 1);
define('REBUILD_MVCC_BACKUP_FINISH', 2);
define('REBUILD_MVCC_RUNNING', 3);
define('REBUILD_MVCC_FINISH', 4);


//erp系统sku状态
define('PRODUCT_STATUS_SHBTG',0);
define('PRODUCT_STATUS_KFZ',1);
define('PRODUCT_STATUS_BJZ',2);
define('PRODUCT_STATUS_YSX',3);
define('PRODUCT_STATUS_ZSZ',4);
define('PRODUCT_STATUS_YXZ',5);
define('PRODUCT_STATUS_DQC',6);
define('PRODUCT_STATUS_YTS',7);
define('PRODUCT_STATUS_DMY',8);
define('PRODUCT_STATUS_DPJ',9);
define('PRODUCT_STATUS_PSZ',10);
define('PRODUCT_STATUS_CPXXQR',11);
define('PRODUCT_STATUS_XTZ',12);
define('PRODUCT_STATUS_SJSHZ',14);
define('PRODUCT_STATUS_WASHZ',15);
define('PRODUCT_STATUS_WAZGZSZ',16);
define('PRODUCT_STATUS_SMBJZ',17);
define('PRODUCT_STATUS_SMZSZ',18);
define('PRODUCT_STATUS_SMWAZSZ',19);
define('PRODUCT_STATUS_YSXPSZ',20);
define('PRODUCT_STATUS_WSSHZ',21);
define('PRODUCT_STATUS_ZTSHZ',27);
define('PRODUCT_STATUS_GWSHZ',28);
define('PRODUCT_STATUS_KFJCZ',29);
define('PRODUCT_STATUS_YBJ_F',30);
define('PRODUCT_STATUS_BJZPSZ',31);
define('PRODUCT_STATUS_YBJ_S',32);
define('PRODUCT_STATUS_BJZYPS',33);
//
define('PRODUCT_STATUS_ALL',[
    PRODUCT_STATUS_SHBTG    =>  ['name'=>'审核不通过'],
    PRODUCT_STATUS_KFZ  =>  ['name'=>'刚开发'],
    PRODUCT_STATUS_BJZ  =>  ['name'=>'编辑中'],
    PRODUCT_STATUS_YSX  =>  ['name'=>'预上线'],
    PRODUCT_STATUS_ZSZ  =>  ['name'=>'在售中'],
    PRODUCT_STATUS_YXZ  =>  ['name'=>'已滞销'],
    PRODUCT_STATUS_DQC  =>  ['name'=>'待清仓'],
    PRODUCT_STATUS_YTS  =>  ['name'=>'已停售'],
    PRODUCT_STATUS_DMY  =>  ['name'=>'待买样'],
    PRODUCT_STATUS_DPJ  =>  ['name'=>'待品检'],
    PRODUCT_STATUS_PSZ  =>  ['name'=>'拍摄中'],
    PRODUCT_STATUS_CPXXQR   =>  ['name'=>'产品信息确认'],
    PRODUCT_STATUS_XTZ  =>  ['name'=>'修图中'],
    PRODUCT_STATUS_SJSHZ    =>  ['name'=>'设计审核中'],
    PRODUCT_STATUS_WASHZ    =>  ['name'=>'文案审核中'],
    PRODUCT_STATUS_WAZGZSZ  =>  ['name'=>'文案主管终审中'],
    PRODUCT_STATUS_SMBJZ    =>  ['name'=>'试卖编辑中'],
    PRODUCT_STATUS_SMZSZ    =>  ['name'=>'试卖在售中'],
    PRODUCT_STATUS_SMWAZSZ  =>  ['name'=>'试卖文案终审中'],
    PRODUCT_STATUS_YSXPSZ   =>  ['name'=>'预上线拍摄中'],
    PRODUCT_STATUS_WSSHZ    =>  ['name'=>'物流审核中'],
    PRODUCT_STATUS_ZTSHZ    =>  ['name'=>'作图审核中'],
    PRODUCT_STATUS_GWSHZ    =>  ['name'=>'关务审核中'],
    PRODUCT_STATUS_KFJCZ    =>  ['name'=>'开发检查中'],
    PRODUCT_STATUS_YBJ_F    =>  ['name'=>'已编辑，拍摄中'],
    PRODUCT_STATUS_BJZPSZ   =>  ['name'=>'编辑中，拍摄中'],
    PRODUCT_STATUS_YBJ_S    =>  ['name'=>'已编辑，拍摄中'],
    PRODUCT_STATUS_BJZYPS   =>  ['name'=>'编辑中，已拍摄'],
]);

/**
 * 停止清仓
 * 停止清仓 0 清仓中 1 停止清仓 跟源保持一致
 */
define('STOP_CLEAN_WAREHOUSE_NO', 0);
define('STOP_CLEAN_WAREHOUSE_YES', 1);

define('STOP_CLEAN_WAREHOUSE_STATE', [
        STOP_CLEAN_WAREHOUSE_NO      => ['name' => '清仓中'],
        STOP_CLEAN_WAREHOUSE_YES     => ['name' => '正常'],
]);

/**
* 国内sku是否停售
*/
define('INLAND_HALT_SALE_YES', 1);
define('INLAND_HALT_SALE_NO', 0);

/**
 * 国内 - pr 需求单 - 审核状态 常量定量  2 待审核 3 审核失败 4 成功'
 * @var unknown
 */
define('INLAND_APPROVAL_STATE_INIT', 2);
define('INLAND_APPROVAL_STATE_SUCCESS', 4);
define('INLAND_APPROVAL_STATE_FAIL', 3);
define('INLAND_APPROVAL_STATE', [
    INLAND_APPROVAL_STATE_INIT   => ['name' => '待审核'],
    INLAND_APPROVAL_STATE_SUCCESS => ['name' => '审核成功'],
    INLAND_APPROVAL_STATE_FAIL    => ['name' => '审核失败']
]);

/**
 * 海外 - pr 需求单 - 审核状态 常量定量  2 待审核 3 审核失败 4 成功'
 */
define('OVERSEA_APPROVAL_STATE_INIT', 2);
define('OVERSEA_APPROVAL_STATE_SUCCESS', 4);
define('OVERSEA_APPROVAL_STATE_FAIL', 3);
define('OVERSEA_APPROVAL_STATE', [
    OVERSEA_APPROVAL_STATE_INIT   => ['name' => '待审核'],
    OVERSEA_APPROVAL_STATE_SUCCESS => ['name' => '审核成功'],
    OVERSEA_APPROVAL_STATE_FAIL    => ['name' => '审核失败']
]);


/**
 * 国内指定备货
 * @var unknown
 */
define('INLAND_DESIGNATES_NO', 0);
define('INLAND_DESIGNATES_YES', 1);


