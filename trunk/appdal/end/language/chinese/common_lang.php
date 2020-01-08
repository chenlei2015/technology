<?php
defined('BASEPATH') OR exit('No direct script access allowed');

$lang['item']             = '序号';
$lang['item_id']          = '数据ID';
$lang['rule']             = '规则';
$lang['sku']              = 'SKU';
$lang['fn_sku']           = 'FNSKU';
$lang['asin']             = 'ASIN';
$lang['seller']           = 'SellerSKU';
$lang['product_name']     = '产品名称';
$lang['sales_group']      = '销售小组';
$lang['salesman']         = '销售人员';
$lang['account_name']     = '账号名称';
$lang['state']            = '审核状态';
$lang['listing_state']    = 'listing状态';
$lang['listing_state_text']    = 'listing状态明细';
$lang['create_at']        = '创建时间';
$lang['modify']           = '修改信息';
$lang['remark']           = '备注';
$lang['fba_station']      = 'FBA站点';
$lang['logistics_attr']   = '物流属性';
$lang['as_up']            = '上架时效(AS)';
$lang['bs']               = '缓冲库存(BS)';
$lang['ls_shipping_full'] = '物流时效LS_海运整柜';
$lang['ls_shipping_bulk'] = '物流时效LS_海运散货';
$lang['ls_trains_full']   = '物流时效LS_铁运整柜';
$lang['ls_trains_bulk']   = '物流时效LS_铁运散货';
$lang['ls_land']          = '物流时效LS_陆运';
$lang['ls_air']           = '物流时效LS_空运';
$lang['ls_red']           = '物流时效LS_红单';
$lang['ls_blue']          = '物流时效LS_蓝单';
$lang['pt_shipping_full'] = '打包时效PT_海运整柜';
$lang['pt_shipping_bulk'] = '打包时效PT_海运散货';
$lang['pt_trains_full']   = '打包时效PT_铁运整柜';
$lang['pt_trains_bulk']   = '打包时效PT_铁运散货';
$lang['pt_land']          = '打包时效PT_陆运';
$lang['pt_air']           = '打包时效PT_空运';
$lang['pt_red']           = '打包时效PT_红单';
$lang['pt_blue']          = '打包时效PT_蓝单';
$lang['first_way_transportation']   = '头程运输方式大类';
$lang['details_of_first_way_transportation']   = '头程运输方式明细';

$lang['ls_shipping_sea']     = '物流时效--海运';
$lang['ls_shipping_land']    = '物流时效--陆运';
$lang['ls_shipping_air']     = '物流时效--空运';
$lang['ls_shipping_rail']    = '物流时效--铁运';
$lang['pt_shipping_sea']     = '打包时效--海运';
$lang['pt_shipping_land']    = '打包时效--陆运';
$lang['pt_shipping_air']     = '打包时效--空运';
$lang['pt_shipping_rail']    = '打包时效--铁运';
$lang['moq_qty']             = 'MOQ数量';
$lang['supplier_code']       = '供应商编码';
$lang['original_min_start_amount']       = '供应商起订金额1';
$lang['min_start_amount']    = '供应商起订金额2';


$lang['sc']                = '一次备货天数(SC)';
$lang['sp']                = '备货处理周期(SP)';
$lang['lt']                = '供货周期(L/T)';
$lang['sz']                = '服务对应"Z"值';
$lang['operation']         = '操作';
$lang['operator']          = '操作人';
$lang['operation_time']    = '操作时间';
$lang['operation_context'] = '操作内容';
$lang['update_at']         = '更新时间';
$lang['update_info']       = '修改信息';
$lang['check_info']        = '审核信息';
$lang['is_refund_tax']     = '是否能退税';
$lang['pur_warehouse']     = '建议采购仓库';
$lang['oversea_station']   = '海外站点';
$lang['sku_state']         = '计划系统sku状态';
$lang['product_status']    = 'erp系统sku状态';
$lang['created_at']        = '创建时间';
$lang['delivery_cycle']    = '发货周期';
$lang['refund_rate']       = '退款率';

$lang['mix_hair']= '是否允许空海混发';
$lang['infringement_state']= '是否侵权';
$lang['contraband_state']  = '是否违禁';
$lang['listing_states']     = '运营状态';

/**
 * 主键名为id 的列表
 */
$lang['id_collection'] = ['fba_stock_relationship_cfg', 'fba_logistics_list', 'oversea_logistics_list'];

/**
 * field为数据表字段
 * index为返回最后返回给前端的字段名
 * label为返回最后返回给前端的中文名
 */

/**
 * 备货关系配置表
 */
$lang['fba_stock_relationship_cfg'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'state', 'key' => 'check_state_cn', 'label' => '审核状态',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'sku',],
    ['field' => 'category_cn_name', 'key' => 'category_cn_name', 'label' => '品类',],
    ['field' => 'purchase_price', 'key' => 'purchase_price', 'label' => '成本价',],
    ['field' => 'provider_status', 'key' => 'provider_status_cn', 'label' => '货源状态',],
    ['field' => 'moq_qty', 'key' => 'moq_qty', 'label' => 'MOQ数量',],
    ['field' => 'supplier_code', 'key' => 'supplier_code', 'label' => '供应商编码',],
    ['field' => 'original_min_start_amount', 'key' => 'original_min_start_amount', 'label' => '供应商最小起订金额1',],
    ['field' => 'min_start_amount', 'key' => 'min_start_amount', 'label' => '供应商最小起订金额2',],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_cn', 'label' => '是否退税',],
    ['field' => 'is_boutique', 'key' => 'is_boutique_cn', 'label' => '是否精品',],
    ['field' => 'sku_state', 'key' => 'sku_state_cn', 'label' => '计划系统sku状态',],
    ['field' => 'product_status', 'key' => 'product_status_text', 'label' => 'erp系统sku状态',],
    ['field' => 'lt', 'key' => 'lt', 'label' => '供货周期(L/T)',],
    ['field' => 'sp', 'key' => 'sp', 'label' => '备货处理周期(SP)',],
    ['field' => 'is_contraband', 'key' => 'is_contraband_cn', 'label' => '是否违禁',],
    ['field' => 'max_sp', 'key' => 'max_sp', 'label' => '最大额外备货天数',],
    ['field' => 'max_lt', 'key' => 'max_lt', 'label' => '最大供货周期',],
    ['field' => 'max_safe_stock', 'key' => 'max_safe_stock', 'label' => '最大安全库存天数',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'update_info', 'label' => '修改信息',],
    ['field' => 'approved_zh_name,approved_at', 'key' => 'check_info', 'label' => '审核信息',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];


$lang['oversea_stock_relationship_cfg'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'rule_type', 'key' => 'rule_type_cn', 'label' => '规则',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'sku',],
    ['field' => 'state', 'key' => 'check_state_cn', 'label' => '审核状态',],
    ['field' => 'station_code', 'key' => 'station_code_cn', 'label' => '海外站点',],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_cn', 'label' => '是否能退税',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_cn', 'label' => '建议采购仓库',],
    ['field' => 'original_min_start_amount', 'key' => 'original_min_start_amount', 'label' => '供应商最小起订金额1',],
    ['field' => 'min_start_amount', 'key' => 'min_start_amount', 'label' => '供应商最小起订金额2',],
    ['field' => 'sku_state', 'key' => 'sku_state_text', 'label' => '计划系统sku状态',],
    ['field' => 'product_status', 'key' => 'product_status_text', 'label' => 'erp系统sku状态',],
    ['field' => 'is_import', 'key' => 'is_import_text', 'label' => '是否为导入SKU',],
    ['field' => 'as_up', 'key' => 'as_up', 'label' => '上架时效(AS)',],
    ['field' => 'ls_air', 'key' => 'ls_air', 'label' => '物流时效LS_空运',],
    ['field' => 'ls_shipping_bulk', 'key' => 'ls_shipping_bulk', 'label' => '物流时效LS_海运散货',],
    ['field' => 'ls_shipping_full', 'key' => 'ls_shipping_full', 'label' => '物流时效LS_海运整柜',],
    ['field' => 'ls_trains_bulk', 'key' => 'ls_trains_bulk', 'label' => '物流时效LS_铁运散货',],
    ['field' => 'ls_trains_full', 'key' => 'ls_trains_full', 'label' => '物流时效LS_铁运整柜',],
    ['field' => 'ls_land', 'key' => 'ls_land', 'label' => '物流时效LS_陆运',],
    ['field' => 'ls_blue', 'key' => 'ls_blue', 'label' => '物流时效LS_蓝单',],
    ['field' => 'ls_red', 'key' => 'ls_red', 'label' => '物流时效LS_红单',],
    ['field' => 'pt_air', 'key' => 'pt_air', 'label' => '打包时效PT_空运',],
    ['field' => 'pt_shipping_bulk', 'key' => 'pt_shipping_bulk', 'label' => '打包时效PT_海运散货',],
    ['field' => 'pt_shipping_full', 'key' => 'pt_shipping_full', 'label' => '打包时效PT_海运整柜',],
    ['field' => 'pt_trains_bulk', 'key' => 'pt_trains_bulk', 'label' => '打包时效PT_铁运散货',],
    ['field' => 'pt_trains_full', 'key' => 'pt_trains_full', 'label' => '打包时效PT_铁运整柜',],
    ['field' => 'pt_land', 'key' => 'pt_land', 'label' => '打包时效PT_陆运',],
    ['field' => 'pt_blue', 'key' => 'pt_blue', 'label' => '打包时效PT_蓝单',],
    ['field' => 'pt_red', 'key' => 'pt_red', 'label' => '打包时效PT_红单',],
    ['field' => 'bs', 'key' => 'bs', 'label' => '缓冲库存(BS)',],
    ['field' => 'lt', 'key' => 'lt', 'label' => '供货周期(L/T)',],
    ['field' => 'sp', 'key' => 'sp', 'label' => '备货处理周期(SP)',],
    ['field' => 'sc', 'key' => 'sc', 'label' => '一次备货天数(SC)',],
    ['field' => 'sz', 'key' => 'sz', 'label' => '服务对应"Z"值',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'update_info', 'label' => '修改信息',],
    ['field' => 'approved_zh_name,approved_at', 'key' => 'check_info', 'label' => '审核信息',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',]
];
/**
 * 物流属性配置表
 */
$lang['fba_logistics_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'rule_type', 'key' => 'rule_type_cn', 'label' => '规则',],
    ['field' => 'approve_state', 'key' => 'approve_state_cn', 'label' => '审核状态',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'station_code', 'key' => 'station_code_cn', 'label' => 'FBA站点',],
    ['field' => 'fnsku', 'key' => 'fnsku', 'label' => 'FNSKU',],
    ['field' => 'seller_sku', 'key' => 'seller_sku', 'label' => 'SellerSKU',],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'ASIN',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'is_first_sale', 'key' => 'is_first_sale_cn', 'label' => '是否首发',],
    ['field' => 'sale_group_zh_name', 'key' => 'sale_group_zh_name', 'label' => '销售小组',],
    ['field' => 'account_name', 'key' => 'account_name', 'label' => '账号名称',],
    ['field' => 'salesman_zh_name', 'key' => 'salesman_zh_name', 'label' => '销售人员',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_cn', 'label' => '采购仓库',],
    ['field' => 'avg_inventory_age', 'key' => 'avg_inventory_age', 'label' => '平均库龄',],
    ['field' => 'expand_factor', 'key' => 'expand_factor', 'label' => '扩销系数',],
    ['field' => 'as_up', 'key' => 'as_up', 'label' => '上架时效(AS)',],
    ['field' => 'ls', 'key' => 'ls', 'label' => '物流时效(LS)',],
    ['field' => 'pt', 'key' => 'pt', 'label' => '打包时效(PT)',],
    ['field' => 'bs', 'key' => 'bs', 'label' => '缓冲库存(BS)',],
    ['field' => 'sc', 'key' => 'sc', 'label' => '一次备货天数(SC)',],
    ['field' => 'sz', 'key' => 'sz', 'label' => '服务对应"Z"值',],
    ['field' => 'sku_state', 'key' => 'sku_state_cn', 'label' => '计划系统sku状态',],
    ['field' => 'product_status', 'key' => 'product_status_cn', 'label' => 'erp系统sku状态',],
    ['field' => 'listing_state', 'key' => 'listing_state_cn', 'label' => 'Listing状态',],
    ['field' => 'listing_state_text', 'key' => 'listing_state_text', 'label' => 'Listing状态明细',],
    ['field' => 'logistics_id', 'key' => 'logistics_id_cn', 'label' => '物流属性',],
    ['field' => 'delivery_cycle', 'key' => 'delivery_cycle', 'label' => '发货周期',],
    ['field' => 'account_num', 'key' => 'account_num', 'label' => '账号编号',],
    //['field' => 'site', 'key' => 'site', 'label' => '站点',],
    ['field' => 'pan_eu', 'key' => 'pan_eu', 'label' => '是否泛欧',],
    ['field' => 'account_id', 'key' => 'account_id', 'label' => '账号id',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'update_info', 'label' => '修改信息',],
    ['field' => 'approve_zh_name,approve_at', 'key' => 'check_info', 'label' => '审核信息',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => 'refund_rate', 'key' => 'refund_rate', 'label' => '退款率',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

$lang['oversea_logistics_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'station_code', 'key' => 'station_code', 'label' => '海外站点',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'sku_state', 'key' => 'sku_state_zh', 'label' => '计划系统sku状态',],
    ['field' => 'product_status', 'key' => 'product_status_zh', 'label' => 'erp系统sku状态',],
    ['field' => 'is_import', 'key' => 'is_import_text', 'label' => '是否为导入SKU',],
    ['field' => 'logistics_id', 'key' => 'logistics_id_zh', 'label' => '物流属性',],
    ['field' => 'approve_state', 'key' => 'approve_state_zh', 'label' => '审核状态',],
    ['field' => 'mix_hair', 'key' => 'mix_hair_zh', 'label' => '是否允许空海混发',],
    ['field' => 'infringement_state', 'key' => 'infringement_state_zh', 'label' => '是否侵权',],
    ['field' => 'contraband_state', 'key' => 'contraband_state_zh', 'label' => '是否违禁',],
    ['field' => 'listing_state', 'key' => 'listing_state_zh', 'label' => '运营状态',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'update_info', 'label' => '修改信息',],
    ['field' => 'approve_zh_name,approve_at', 'key' => 'check_info', 'label' => '审核信息',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => 'refund_rate', 'key' => 'refund_rate', 'label' => '退款率',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

/**
 * 需求列表
 */
$lang['fba_pr_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'pr_sn', 'key' => 'pr_sn', 'label' => '需求单号',],
    ['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'station_code', 'key' => 'station_code_text', 'label' => 'FBA站点',],
    /*['field' => 'country_code', 'key' => 'country_name', 'label' => '目的国',],*/
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否退税',],
    //['field' => 'is_boutique', 'key' => 'is_boutique_text', 'label' => '是否精品',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '采购仓库',],
    ['field' => 'supplier_code', 'key' => 'supplier_code', 'label' => '供应商编码',],
    ['field' => 'fnsku', 'key' => 'fnsku', 'label' => 'FNSKU',],
    ['field' => 'seller_sku', 'key' => 'seller_sku', 'label' => 'SELLER_SKU',],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'ASIN',],
    ['field' => 'product_status', 'key' => 'product_status_text', 'label' => 'erp系统sku状态',],
    ['field' => 'sku_state', 'key' => 'sku_state_text', 'label' => '计划系统sku状态',],
    ['field' => 'category_cn_name', 'key' => 'category_cn_name', 'label' => '品类',],
    ['field' => 'purchase_price', 'key' => 'purchase_price', 'label' => '成本价',],
    ['field' => 'provider_status', 'key' => 'provider_status_text', 'label' => '货源状态',],
    ['field' => 'moq_qty', 'key' => 'moq_qty', 'label' => 'MOQ数量',],
    ['field' => 'is_contraband', 'key' => 'is_contraband_text', 'label' => '是否违禁',],
    ['field' => 'designates', 'key' => 'designates', 'label' => '指定备货',],
    ['field' => 'bd', 'key' => 'bd', 'label' => '活动量',],
    ['field' => 'fixed_amount', 'key' => 'fixed_amount', 'label' => '一次修正量',],
    ['field' => 'ext_trigger_info,sales_15_day', 'key' => 'sales_15_day', 'label' => '15天销量',],
    ['field' => 'ext_trigger_info,sales_30_day', 'key' => 'sales_30_day', 'label' => '30天销量',],
//    ['field' => 'sales_15_day', 'key' => 'sales_15_day', 'label' => '30天销量',],
//    ['field' => 'sales_30_day', 'key' => 'sales_30_day', 'label' => '30天销量',],
    ['field' => 'sales_factor', 'key' => 'sales_factor', 'label' => '销量系数',],
    ['field' => 'exhaust_factor', 'key' => 'exhaust_factor', 'label' => '断货系数',],
    ['field' => 'warehouse_age_factor', 'key' => 'warehouse_age_factor', 'label' => '超90天库龄系数',],
    ['field' => 'supply_factor', 'key' => 'supply_factor', 'label' => '可售卖天数系数',],
    ['field' => 'actual_safe_stock', 'key' => 'actual_safe_stock', 'label' => '实际最大安全库存天数',],
    ['field' => 'actual_bs', 'key' => 'actual_bs', 'label' => '实际缓冲库存天数',],
    ['field' => 'require_qty', 'key' => 'require_qty', 'label' => '需求数量(pcs)',],
    ['field' => 'stocked_qty', 'key' => 'stocked_qty', 'label' => '国内已准备数量',],
    ['field' => 'is_trigger_pr', 'key' => 'is_trigger_pr_text', 'label' => '是否触发需求',],
    ['field' => 'trigger_mode', 'key' => 'trigger_mode', 'label' => '主动/被动触发需求',],
    ['field' => 'is_plan_approve', 'key' => 'is_plan_approve_text', 'label' => '是否计划审核',],
    ['field' => 'is_accelerate_sale', 'key' => 'is_accelerate_sale_text', 'label' => '是否停止加快动销',],
    ['field' => 'accelerate_sale_end_time', 'key' => 'accelerate_sale_end_time', 'label' => '加快动销结束时间',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'is_first_sale', 'key' => 'is_first_sale_text', 'label' => '是否首发',],
    ['field' => 'sale_group', 'key' => 'sale_group', 'label' => '销售小组',],
    ['field' => 'account_name', 'key' => 'account_name', 'label' => '账号名称',],
    ['field' => 'salesman', 'key' => 'salesman', 'label' => '销售人员',],
    ['field' => 'listing_state', 'key' => 'listing_state_text', 'label' => 'listing状态',],
    ['field' => 'avg_inventory_age', 'key' => 'avg_inventory_age', 'label' => '平均库龄',],
    ['field' => 'inventory_turns_days', 'key' => 'inventory_turns_days', 'label' => '存货周转天数',],
    ['field' => 'logistics_id', 'key' => 'logistics_id_text', 'label' => '物流属性',],
    ['field' => 'weight_sale_pcs', 'key' => 'weight_sale_pcs', 'label' => '加权日均销量(pcs)',],
    ['field' => 'sale_sd_pcs', 'key' => 'sale_sd_pcs', 'label' => '销量标准偏差(pcs)',],
    ['field' => 'avg_deliver_day', 'key' => 'avg_deliver_day', 'label' => '交付标准偏差(day)',],
    ['field' => 'pre_day', 'key' => 'pre_day', 'label' => '备货提前期(day)',],
    ['field' => 'safe_stock_pcs', 'key' => 'safe_stock_pcs', 'label' => '安全库存(pcs)',],
    ['field' => 'point_pcs', 'key' => 'point_pcs', 'label' => '订购点(pcs)',],
    ['field' => 'sc_day', 'key' => 'sc_day', 'label' => '一次备货天数SC(day)',],
    ['field' => 'purchase_qty', 'key' => 'purchase_qty', 'label' => '订购数量(pcs)',],
    ['field' => 'available_qty', 'key' => 'available_qty', 'label' => 'FBA可用库存(pcs)',],
    ['field' => 'exchange_up_qty', 'key' => 'exchange_up_qty', 'label' => 'FBA待上架(pcs)',],
    ['field' => 'oversea_ship_qty', 'key' => 'oversea_ship_qty', 'label' => 'FBA国际在途(pcs)',],
    ['field' => 'supply_day', 'key' => 'supply_day', 'label' => '可支撑天数(day)',],
    /*['field' => 'z', 'key' => 'z', 'label' => '服务对应"Z"值',],*/
    ['field' => 'expect_exhaust_date', 'key' => 'expect_exhaust_date', 'label' => '预计缺货时间',],
    ['field' => 'inventory_health', 'key' => 'inventory_health_text', 'label' => '库存健康度诊断',],
    ['field' => 'exhausted_days', 'key' => 'exhausted_days', 'label' => '已断货天数',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_uid,updated_at', 'key' => 'update_info', 'label' => '修改信息',],
    ['field' => 'approved_uid,approved_at', 'key' => 'check_info', 'label' => '审核信息',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => 'expired', 'key' => 'expired_text', 'label' => '是否过期',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

$lang['fba_activity'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'account_id', 'key' => 'account_id', 'label' => 'account_id',],
    ['field' => 'account_num', 'key' => 'account_num', 'label' => 'account_num',],
    ['field' => 'seller_sku', 'key' => 'seller_sku', 'label' => 'SellerSKU',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'ErpSKU',],
    ['field' => 'fnsku', 'key' => 'fnsku', 'label' => 'FNSKU',],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'ASIN',],
    ['field' => 'sale_group', 'key' => 'sale_group', 'label' => '销售小组',],
    ['field' => 'salesman', 'key' => 'salesman', 'label' => '销售人员',],
    ['field' => 'account_name', 'key' => 'account_name', 'label' => '账号名称',],
    ['field' => 'activity_name', 'key' => 'activity_name', 'label' => '活动名称',],
    ['field' => 'amount', 'key' => 'amount', 'label' => '活动量',],
    ['field' => 'execute_purcharse_time', 'key' => 'execute_purcharse_time', 'label' => '开始备货时间',],
    ['field' => 'activity_start_time', 'key' => 'activity_start_time', 'label' => '活动开始时间',],
    ['field' => 'activity_end_time', 'key' => 'activity_end_time', 'label' => '活动结束时间',],
    ['field' => 'staff_zh_name,created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'updated_at', 'label' => '最后一次修改时间',],
    ['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态',],
    ['field' => 'activity_state', 'key' => 'activity_state_text', 'label' => '活动状态',],
    ['field' => 'approved_zh_name,approved_at', 'key' => 'approved_at', 'label' => '审核信息',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

$lang['inland_activity'] = [
        ['field' => '', 'key' => 'index', 'label' => '序号',],
        ['field' => 'erpsku', 'key' => 'erpsku', 'label' => 'erpsku',],
        ['field' => 'platform_code', 'key' => 'platform_code', 'label' => '平台',],
        //['field' => 'activity_name', 'key' => 'activity_name', 'label' => '活动名称',],
        ['field' => 'amount', 'key' => 'amount', 'label' => '活动量',],
        ['field' => 'execute_purcharse_time', 'key' => 'execute_purcharse_time', 'label' => '开始备货时间',],
        ['field' => 'activity_start_time', 'key' => 'activity_start_time', 'label' => '活动开始时间',],
        ['field' => 'activity_end_time', 'key' => 'activity_end_time', 'label' => '活动结束时间',],
        ['field' => 'staff_zh_name,created_at', 'key' => 'created_at', 'label' => '创建时间',],
        ['field' => 'updated_zh_name,updated_at', 'key' => 'updated_at', 'label' => '最后一次修改时间',],
        ['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态',],
        ['field' => 'activity_state', 'key' => 'activity_state_text', 'label' => '活动状态',],
        ['field' => 'approved_zh_name,approved_at', 'key' => 'approved_at', 'label' => '审核信息',],
        ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
        ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

$lang['oversea_activity'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'station_code', 'key' => 'station_code_text', 'label' => '站点',],
    ['field' => 'platform_code', 'key' => 'platform_code', 'label' => '平台',],
    ['field' => 'erpsku', 'key' => 'erpsku', 'label' => 'sku',],
    ['field' => 'activity_title', 'key' => 'activity_title', 'label' => '活动名称',],
    ['field' => 'amount', 'key' => 'amount', 'label' => '活动量',],
    ['field' => 'execute_purcharse_time', 'key' => 'execute_purcharse_time', 'label' => '开始备货时间',],
    ['field' => 'activity_start_time', 'key' => 'activity_start_time', 'label' => '活动开始时间',],
    ['field' => 'activity_end_time', 'key' => 'activity_end_time', 'label' => '活动结束时间',],
    ['field' => 'staff_zh_name,created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'updated_at', 'label' => '最后一次修改时间',],
    ['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态',],
    ['field' => 'activity_state', 'key' => 'activity_state_text', 'label' => '活动状态',],
    ['field' => 'approved_zh_name,approved_at', 'key' => 'approved_at', 'label' => '审核信息',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

$lang['oversea_pr_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'pr_sn', 'key' => 'pr_sn', 'label' => '站点需求单号',],
    //['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'station_code', 'key' => 'station_code', 'label' => '海外站点',],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否能退税',],
    ['field' => 'is_boutique', 'key' => 'is_boutique_text', 'label' => '是否精品',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '建议采购仓库',],
    ['field' => 'platform_require_qty', 'key' => 'platform_require_qty', 'label' => '平台毛需求汇总(pcs)',],
    ['field' => 'require_qty', 'key' => 'require_qty', 'label' => '需求数量(pcs)',],
    ['field' => 'stocked_qty', 'key' => 'stocked_qty', 'label' => '国内已准备数量',],
    ['field' => 'is_trigger_pr', 'key' => 'is_trigger_pr_text', 'label' => '是否触发需求',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'sku_state', 'key' => 'sku_state_text', 'label' => '计划系统SKU状态',],
    ['field' => 'product_status', 'key' => 'product_status_text', 'label' => 'erp系统SKU状态',],
//    ['field' => 'logistics_id', 'key' => 'logistics_id_text', 'label' => '物流属性',],
    ['field' => 'logistics_id', 'key' => 'logistics_id_text', 'label' => '头程运输方式明细',],
    ['field' => 'weight_sale_pcs', 'key' => 'weight_sale_pcs', 'label' => '站点加权日均销量(pcs)',],
    ['field' => 'sale_sd_pcs', 'key' => 'sale_sd_pcs', 'label' => '销量标准偏差(pcs)',],
    ['field' => 'pre_day', 'key' => 'pre_day', 'label' => '备货提前期(day)',],
    ['field' => 'z', 'key' => 'z', 'label' => '服务对应"Z"值',],
    ['field' => 'safe_stock_pcs', 'key' => 'safe_stock_pcs', 'label' => '安全库存(pcs)',],
    ['field' => 'point_pcs', 'key' => 'point_pcs', 'label' => '订购点(pcs)',],
    ['field' => 'purchase_qty', 'key' => 'purchase_qty', 'label' => '站点订购数量(pcs)',],
    ['field' => 'available_qty', 'key' => 'available_qty', 'label' => '海外可用库存(pcs)',],
    ['field' => 'oversea_up_qty', 'key' => 'oversea_up_qty', 'label' => '海外待上架(pcs)',],
    ['field' => 'oversea_ship_qty', 'key' => 'oversea_ship_qty', 'label' => '海外国际在途(pcs)',],
    ['field' => 'supply_day', 'key' => 'supply_day', 'label' => '可支撑天数(day)',],
    ['field' => 'expect_exhaust_date', 'key' => 'expect_exhaust_date', 'label' => '预计缺货时间',],

    //['field' => 'sc_day', 'key' => 'sc_day', 'label' => '一次备货天数SC(day)',],
    //['field' => 'deviate_28_pcs', 'key' => 'deviate_28_pcs', 'label' => '28天销量偏差值(pcs)',],
    //['field' => 'avg_weight_sale_pcs', 'key' => 'avg_weight_sale_pcs', 'label' => '28天销量平均值(pcs)',],
    //['field' => 'avg_deliver_day', 'key' => 'avg_deliver_day', 'label' => '交付标准偏差(day)',],
    //['field' => 'bd', 'key' => 'bd', 'label' => 'BD(pcs)',],
    //['field' => 'is_plan_approve', 'key' => 'is_plan_approve_text', 'label' => '是否计划审核',],

    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_uid,updated_at', 'key' => 'update_info', 'label' => '修改信息',],
    /*['field' => 'approved_uid,approved_at', 'key' => 'check_info', 'label' => '审核信息',],*/
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
//    ['field' => 'expired', 'key' => 'expired_text', 'label' => '是否过期',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

//海外需求列表
$lang['oversea_platform_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'pr_sn', 'key' => 'pr_sn', 'label' => '平台需求单号',],
    ['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态',],
    ['field' => 'station_code', 'key' => 'station_code', 'label' => '海外站点',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'platform_code', 'key' => 'platform_code', 'label' => '平台',],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否能退税',],
    ['field' => 'is_boutique', 'key' => 'is_boutique_text', 'label' => '是否精品',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '建议采购仓库',],
    ['field' => 'bd', 'key' => 'bd', 'label' => '活动量',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'sku_state', 'key' => 'sku_state_text', 'label' => '计划系统sku状态',],
    ['field' => 'product_status', 'key' => 'product_status_text', 'label' => 'erp系统sku状态',],
//    ['field' => 'logistics_id', 'key' => 'logistics_id_text', 'label' => '物流属性',],
    ['field' => 'logistics_id', 'key' => 'logistics_id_text', 'label' => '头程运输方式明细',],
    ['field' => 'weight_sale_pcs', 'key' => 'weight_sale_pcs', 'label' => '平台加权日均销量(pcs)',],
    ['field' => 'pre_day', 'key' => 'pre_day', 'label' => '备货提前期(day)',],
    ['field' => 'require_qty', 'key' => 'require_qty', 'label' => '平台毛需求(pcs)',],
    ['field' => 'sc_day', 'key' => 'sc_day', 'label' => '一次备货天数SC(day)',],
    ['field' => 'purchase_qty', 'key' => 'purchase_qty', 'label' => '平台订购数量(pcs)',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_uid,updated_at', 'key' => 'update_info', 'label' => '修改信息',],
    ['field' => 'approved_uid,approved_at', 'key' => 'check_info', 'label' => '审核信息',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => 'expired', 'key' => 'expired_text', 'label' => '是否过期',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
    ['field' => '', 'key' => 'fixed_amount', 'label' => '一次修订量',],
];




/**
 * 跟踪列表
 */
$lang['fba_track_list']     = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'pr_sn', 'key' => 'pr_sn', 'label' => '需求单号',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'station_code', 'key' => 'station_code_text', 'label' => 'FBA站点',],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否退税',],
    ['field' => 'is_boutique', 'key' => 'is_boutique_text', 'label' => '是否精品',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '采购仓库',],
    ['field' => 'fnsku', 'key' => 'fnsku', 'label' => 'FNSKU',],
    ['field' => 'seller_sku', 'key' => 'seller_sku', 'label' => 'SELLER_SKU',],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'ASIN',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'is_first_sale', 'key' => 'is_first_sale_text', 'label' => '是否首发',],
    ['field' => 'bd', 'key' => 'bd', 'label' => 'BD(pcs)',],
    ['field' => 'require_qty', 'key' => 'require_qty', 'label' => '需求数量(pcs)',],
    ['field' => 'stocked_qty', 'key' => 'stocked_qty', 'label' => '国内已准备数量',],
    ['field' => 'sale_group', 'key' => 'sale_group', 'label' => '销售小组',],
    ['field' => 'salesman', 'key' => 'salesman', 'label' => '销售人员',],
    ['field' => 'account_name', 'key' => 'account_name', 'label' => '账号名称',],
    ['field' => 'expect_exhaust_date', 'key' => 'expect_exhaust_date', 'label' => '预计缺货时间',],
    ['field' => 'sum_sn', 'key' => 'sum_sn', 'label' => '汇总单号',],
    ['field' => 'pur_sn', 'key' => 'pur_sn', 'label' => '备货单号',],
    ['field' => 'pur_state', 'key' => 'pur_state_text', 'label' => '备货状态',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'push_status_logistics', 'key' => 'push_status_logistics_text', 'label' => '推送状态',],
    ['field' => 'push_time_logistics', 'key' => 'push_time_logistics', 'label' => '推送时间',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],

];
$lang['oversea_track_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'pr_sn', 'key' => 'pr_sn', 'label' => '需求单号',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'station_code', 'key' => 'station_code', 'label' => '海外站点',],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否能退税',],
    ['field' => 'is_boutique', 'key' => 'is_boutique_text', 'label' => '是否精品',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '建议采购仓库',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'weight_sale_pcs', 'key' => 'weight_sale_pcs', 'label' => '加权日均销量',],
    ['field' => 'bd', 'key' => 'bd', 'label' => 'BD(pcs)',],
    ['field' => 'require_qty', 'key' => 'require_qty', 'label' => '需求数量(pcs)',],
    ['field' => 'stocked_qty', 'key' => 'stocked_qty', 'label' => '国内已准备数量',],
    ['field' => 'expect_exhaust_date', 'key' => 'expect_exhaust_date', 'label' => '预计缺货时间',],
    ['field' => 'sum_sn', 'key' => 'sum_sn', 'label' => '汇总单号',],
    ['field' => 'pur_sn', 'key' => 'pur_sn', 'label' => '备货单号',],
    ['field' => 'pur_state', 'key' => 'pur_state_text', 'label' => '备货状态',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'push_status_logistics', 'key' => 'push_status_logistics_text', 'label' => '推送状态',],
    ['field' => 'push_time_logistics', 'key' => 'push_time_logistics', 'label' => '推送时间',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

/**
 * 备货列表
 */
$lang['stock_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'pur_sn', 'key' => 'pur_sn', 'label' => '备货单号',],
    ['field' => 'transfer_apply_sn', 'key' => 'transfer_apply_sn', 'label' => '调拨申请单号',],
    ['field' => 'sum_sn', 'key' => 'sum_sn', 'label' => '需求汇总单号',],
    ['field' => 'bussiness_line', 'key' => 'bussiness_line_text', 'label' => '需求业务线',],
    ['field' => 'sku_state', 'key' => 'sku_state_text', 'label' => '计划系统sku状态',],
    ['field' => 'product_status', 'key' => 'product_status_text', 'label' => 'erp系统sku状态',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否退税',],
    ['field' => 'is_boutique', 'key' => 'is_boutique_text', 'label' => '是否精品',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '采购仓库',],
    ['field' => 'supplier_code', 'key' => 'supplier_code', 'label' => '供应商编码',],
    ['field' => 'original_min_start_amount', 'key' => 'original_min_start_amount', 'label' => '供应商最小起订金额1',],
    ['field' => 'min_start_amount', 'key' => 'min_start_amount', 'label' => '供应商最小起订金额2',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'earliest_exhaust_date', 'key' => 'earliest_exhaust_date', 'label' => '最早缺货时间',],
    ['field' => 'total_required_qty', 'key' => 'total_required_qty', 'label' => '总需求数量',],
    ['field' => 'pr_quantity', 'key' => 'pr_quantity', 'label' => 'PR数量',],
    ['field' => 'on_way_qty', 'key' => 'on_way_qty', 'label' => '采购在途',],
    ['field' => 'transfer_way_qty', 'key' => 'transfer_way_qty', 'label' => '调拨在途数量',],
    ['field' => 'avail_qty', 'key' => 'avail_qty', 'label' => '可用库存',],
    ['field' => 'surplus_inventory', 'key' => 'surplus_inventory', 'label' => '富余库存',],
    ['field' => 'pre_transfer_qty', 'key' => 'pre_transfer_qty', 'label' => '预调拨数量',],
    ['field' => 'actual_transfer_qty', 'key' => 'actual_transfer_qty', 'label' => '实际调拨数量',],
    ['field' => 'original_stock_qty', 'key' => 'original_stock_qty', 'label' => '原始采购建议量',],
    ['field' => 'moq_qty', 'key' => 'moq_qty', 'label' => 'MOQ数量',],
    ['field' => 'moq_recommend_qty', 'key' => 'moq_recommend_qty', 'label' => '采购建议量（已经过moq运算）',],
    ['field' => 'recommend_qty', 'key' => 'recommend_qty', 'label' => '最终需采购数量',],
    //['field' => 'actual_purchase_qty', 'key' => 'actual_purchase_qty', 'label' => '最终需采购数量',],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'state', 'key' => 'pur_sn_state_text', 'label' => '备货状态',],
//    ['field' => 'is_pushed', 'key' => 'is_pushed_text', 'label' => '推送状态',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];


/**
 * 备货跟踪列表
 */
$lang['track_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'pur_sn', 'key' => 'pur_sn', 'label' => '备货单号',],
    ['field' => 'bussiness_line', 'key' => 'bussiness_line_text', 'label' => '需求业务线',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否退税',],
    ['field' => 'is_boutique', 'key' => 'is_boutique_text', 'label' => '是否精品',],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '采购仓库',],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称',],
    ['field' => 'product_status', 'key' => 'product_status_text', 'label' => '产品状态',],
    ['field' => 'earliest_generate_time', 'key' => 'earliest_generate_time', 'label' => '最早生成时间',],
    ['field' => 'earliest_exhaust_date', 'key' => 'earliest_exhaust_date', 'label' => '最早缺货时间',],
    ['field' => 'recommend_qty', 'key' => 'recommend_qty', 'label' => '最终采购建议量',],
//    ['field' => 'push_stock_quantity', 'key' => 'push_stock_quantity', 'label' => '推送采购备货数量',],
    ['field' => 'po_sn', 'key' => 'po_sn', 'label' => 'PO单号',],
    ['field' => 'po_state', 'key' => 'po_state_text', 'label' => '采购状态',],
    ['field' => 'po_qty', 'key' => 'po_qty', 'label' => 'PO数量',],
    ['field' => 'shipment_type', 'key' => 'shipment_type_text', 'label' => '发运类型',],
    ['field' => 'expect_arrived_date', 'key' => 'expect_arrived_date', 'label' => '预计到货时间',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];

/**
 * FBA库存状况列表
 */
$lang['fba_stock_condition_month'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'sales_group_id', 'key' => 'sales_group_cn', 'label' => '销售小组',],
    ['field' => 'salesman', 'key' => 'salesman', 'label' => '销售人员',],
    ['field' => 'account_id', 'key' => 'account_name', 'label' => '账号名称',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'seller_sku', 'key' => 'seller_sku', 'label' => 'SELLERSKU',],
    ['field' => 'fn_sku', 'key' => 'fn_sku', 'label' => 'FNSKU',],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'ASIN',],
    ['field' => 'product_name', 'key' => 'product_name', 'label' => '产品名称',],
    ['field' => 'station_code', 'key' => 'station_code_cn', 'label' => 'FBA站点',],
    ['field' => 'shelf_time', 'key' => 'shelf_time', 'label' => '上架时间',],
    ['field' => 'listing_state', 'key' => 'listing_state_cn', 'label' => 'SKU状态',],
    ['field' => 'month_1', 'key' => 'month_1', 'label' => '一月',],
    ['field' => 'month_2', 'key' => 'month_2', 'label' => '二月',],
    ['field' => 'month_3', 'key' => 'month_3', 'label' => '三月',],
    ['field' => 'month_4', 'key' => 'month_4', 'label' => '四月',],
    ['field' => 'month_5', 'key' => 'month_5', 'label' => '五月',],
    ['field' => 'month_6', 'key' => 'month_6', 'label' => '六月',],
    ['field' => 'month_7', 'key' => 'month_7', 'label' => '七月',],
    ['field' => 'month_8', 'key' => 'month_8', 'label' => '八月',],
    ['field' => 'month_9', 'key' => 'month_9', 'label' => '九月',],
    ['field' => 'month_10', 'key' => 'month_10', 'label' => '十月',],
    ['field' => 'month_11', 'key' => 'month_11', 'label' => '十一月',],
    ['field' => 'month_12', 'key' => 'month_12', 'label' => '十二月',],
    ['field' => 'month_sales', 'key' => 'month_sales', 'label' => '本月销量',],
    ['field' => 'accumulative_sales', 'key' => 'accumulative_sales', 'label' => '累计销量',],
    ['field' => 'accumulative_return', 'key' => 'accumulative_return', 'label' => '累计退货',],
    ['field' => 'rma', 'key' => 'rma', 'label' => 'RMA%',],
    ['field' => 'can_sale_stock', 'key' => 'can_sale_stock', 'label' => '可售库存',],
    ['field' => 'cannot_sale_stock', 'key' => 'cannot_sale_stock', 'label' => '不可售库存',],
    ['field' => 'shipping_full_in_transit', 'key' => 'shipping_full_in_transit', 'label' => '海运整柜在途',],
    ['field' => 'shipping_bulk_in_transit', 'key' => 'shipping_bulk_in_transit', 'label' => '海运散货在途',],
    ['field' => 'trains_full_in_transit', 'key' => 'trains_full_in_transit', 'label' => '铁运整柜在途',],
    ['field' => 'trains_bulk_in_transit', 'key' => 'trains_bulk_in_transit', 'label' => '铁运散货在途',],
    ['field' => 'air_in_transit', 'key' => 'air_in_transit', 'label' => '空运在途',],
    ['field' => 'blueorder_in_transit', 'key' => 'blueorder_in_transit', 'label' => '蓝单在途',],
    ['field' => 'redorder_in_transit', 'key' => 'redorder_in_transit', 'label' => '红单在途',],
    ['field' => 'fba_onway_data', 'key' => 'fba_onway_data', 'label' => 'FBA在途数据',],
];

$lang['fba_stock_condition_week'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'sales_group_id', 'key' => 'sales_group_cn', 'label' => '销售小组',],
    ['field' => 'salesman', 'key' => 'salesman', 'label' => '销售人员',],
    ['field' => 'account_id', 'key' => 'account_name', 'label' => '账号名称',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'seller_sku', 'key' => 'seller_sku', 'label' => 'SELLERSKU',],
    ['field' => 'fn_sku', 'key' => 'fn_sku', 'label' => 'FNSKU',],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'ASIN',],
    ['field' => 'product_name', 'key' => 'product_name', 'label' => '产品名称',],
    ['field' => 'station_code', 'key' => 'station_code_cn', 'label' => 'FBA站点',],
    ['field' => 'shelf_time', 'key' => 'shelf_time', 'label' => '上架时间',],
    ['field' => 'listing_state', 'key' => 'listing_state_cn', 'label' => 'SKU状态',],
    ['field' => 'week_no_1', 'key' => 'week_no_1', 'label' => '第1周',],
    ['field' => 'week_no_2', 'key' => 'week_no_2', 'label' => '第2周',],
    ['field' => 'week_no_3', 'key' => 'week_no_3', 'label' => '第3周',],
    ['field' => 'week_no_4', 'key' => 'week_no_4', 'label' => '第4周',],
    ['field' => 'week_no_5', 'key' => 'week_no_5', 'label' => '第5周',],
    ['field' => 'month_sales', 'key' => 'month_sales', 'label' => '本月销量',],
    ['field' => 'accumulative_sales', 'key' => 'accumulative_sales', 'label' => '累计销量',],
    ['field' => 'accumulative_return', 'key' => 'accumulative_return', 'label' => '累计退货',],
    ['field' => 'rma', 'key' => 'rma', 'label' => 'RMA%',],
    ['field' => 'can_sale_stock', 'key' => 'can_sale_stock', 'label' => '可售库存',],
    ['field' => 'cannot_sale_stock', 'key' => 'cannot_sale_stock', 'label' => '不可售库存',],
    ['field' => 'shipping_full_in_transit', 'key' => 'shipping_full_in_transit', 'label' => '海运整柜在途',],
    ['field' => 'shipping_bulk_in_transit', 'key' => 'shipping_bulk_in_transit', 'label' => '海运散货在途',],
    ['field' => 'trains_full_in_transit', 'key' => 'trains_full_in_transit', 'label' => '铁运整柜在途',],
    ['field' => 'trains_bulk_in_transit', 'key' => 'trains_bulk_in_transit', 'label' => '铁运散货在途',],
    ['field' => 'air_in_transit', 'key' => 'air_in_transit', 'label' => '空运在途',],
    ['field' => 'blueorder_in_transit', 'key' => 'blueorder_in_transit', 'label' => '蓝单在途',],
    ['field' => 'redorder_in_transit', 'key' => 'redorder_in_transit', 'label' => '红单在途',],
];

$lang['fba_stock_condition_day'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'sales_group_id', 'key' => 'sales_group_cn', 'label' => '销售小组',],
    ['field' => 'salesman', 'key' => 'salesman', 'label' => '销售人员',],
    ['field' => 'account_id', 'key' => 'account_name', 'label' => '账号名称',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'seller_sku', 'key' => 'seller_sku', 'label' => 'SELLERSKU',],
    ['field' => 'fn_sku', 'key' => 'fn_sku', 'label' => 'FNSKU',],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'ASIN',],
    ['field' => 'product_name', 'key' => 'product_name', 'label' => '产品名称',],
    ['field' => 'station_code', 'key' => 'station_code_cn', 'label' => 'FBA站点',],
    ['field' => 'shelf_time', 'key' => 'shelf_time', 'label' => '上架时间',],
    ['field' => 'listing_state', 'key' => 'listing_state_cn', 'label' => 'SKU状态',],
    ['field' => 'last_days_1', 'key' => 'last_days_1', 'label' => '过去1天',],
    ['field' => 'last_days_3', 'key' => 'last_days_3', 'label' => '过去3天',],
    ['field' => 'last_days_7', 'key' => 'last_days_7', 'label' => '过去7天',],
    ['field' => 'last_days_14', 'key' => 'last_days_14', 'label' => '过去14天',],
    ['field' => 'last_days_28', 'key' => 'last_days_28', 'label' => '过去28天',],
    ['field' => 'accumulative_sales', 'key' => 'accumulative_sales', 'label' => '累计销量',],
    ['field' => 'accumulative_return', 'key' => 'accumulative_return', 'label' => '累计退货',],
    ['field' => 'rma', 'key' => 'rma', 'label' => 'RMA%',],
    ['field' => 'can_sale_stock', 'key' => 'can_sale_stock', 'label' => '可售库存',],
    ['field' => 'cannot_sale_stock', 'key' => 'cannot_sale_stock', 'label' => '不可售库存',],
    ['field' => 'shipping_full_in_transit', 'key' => 'shipping_full_in_transit', 'label' => '海运整柜在途',],
    ['field' => 'shipping_bulk_in_transit', 'key' => 'shipping_bulk_in_transit', 'label' => '海运散货在途',],
    ['field' => 'trains_full_in_transit', 'key' => 'trains_full_in_transit', 'label' => '铁运整柜在途',],
    ['field' => 'trains_bulk_in_transit', 'key' => 'trains_bulk_in_transit', 'label' => '铁运散货在途',],
    ['field' => 'air_in_transit', 'key' => 'air_in_transit', 'label' => '空运在途',],
    ['field' => 'blueorder_in_transit', 'key' => 'blueorder_in_transit', 'label' => '蓝单在途',],
    ['field' => 'redorder_in_transit', 'key' => 'redorder_in_transit', 'label' => '红单在途',],
];

/**
 * 海外库存状态列表
 */
$lang['oversea_stock_condition_month'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'product_name', 'key' => 'product_name', 'label' => '产品名称',],
    ['field' => 'station_code', 'key' => 'station_code_cn', 'label' => '海外站点',],
    ['field' => 'shelf_time', 'key' => 'shelf_time', 'label' => '上架时间',],
    ['field' => 'listing_state', 'key' => 'sku_state_cn', 'label' => 'SKU状态',],
    ['field' => 'month_1', 'key' => 'month_1', 'label' => '一月',],
    ['field' => 'month_2', 'key' => 'month_2', 'label' => '二月',],
    ['field' => 'month_3', 'key' => 'month_3', 'label' => '三月',],
    ['field' => 'month_4', 'key' => 'month_4', 'label' => '四月',],
    ['field' => 'month_5', 'key' => 'month_5', 'label' => '五月',],
    ['field' => 'month_6', 'key' => 'month_6', 'label' => '六月',],
    ['field' => 'month_7', 'key' => 'month_7', 'label' => '七月',],
    ['field' => 'month_8', 'key' => 'month_8', 'label' => '八月',],
    ['field' => 'month_9', 'key' => 'month_9', 'label' => '九月',],
    ['field' => 'month_10', 'key' => 'month_10', 'label' => '十月',],
    ['field' => 'month_11', 'key' => 'month_11', 'label' => '十一月',],
    ['field' => 'month_12', 'key' => 'month_12', 'label' => '十二月',],
    ['field' => 'month_sales', 'key' => 'month_sales', 'label' => '本月销量',],
    ['field' => 'accumulative_sales', 'key' => 'accumulative_sales', 'label' => '累计销量',],
    ['field' => 'accumulative_return', 'key' => 'accumulative_return', 'label' => '累计退货',],
    ['field' => 'rma', 'key' => 'rma', 'label' => 'RMA%',],
    ['field' => 'can_sale_stock', 'key' => 'can_sale_stock', 'label' => '可售库存',],
    ['field' => 'cannot_sale_stock', 'key' => 'cannot_sale_stock', 'label' => '不可售库存',],
    ['field' => 'shipping_full_in_transit', 'key' => 'shipping_full_in_transit', 'label' => '海运整柜在途',],
    ['field' => 'shipping_bulk_in_transit', 'key' => 'shipping_bulk_in_transit', 'label' => '海运散货在途',],
    ['field' => 'trains_full_in_transit', 'key' => 'trains_full_in_transit', 'label' => '铁运整柜在途',],
    ['field' => 'trains_bulk_in_transit', 'key' => 'trains_bulk_in_transit', 'label' => '铁运散货在途',],
    ['field' => 'air_in_transit', 'key' => 'air_in_transit', 'label' => '空运在途',],
    ['field' => 'land_in_transit', 'key' => 'land_in_transit', 'label' => '陆运在途',],
    ['field' => 'blueorder_in_transit', 'key' => 'blueorder_in_transit', 'label' => '蓝单在途',],
    ['field' => 'redorder_in_transit', 'key' => 'redorder_in_transit', 'label' => '红单在途',],
];

$lang['oversea_stock_condition_week'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'product_name', 'key' => 'product_name', 'label' => '产品名称',],
    ['field' => 'station_code', 'key' => 'station_code_cn', 'label' => '海外站点',],
    ['field' => 'shelf_time', 'key' => 'shelf_time', 'label' => '上架时间',],
    ['field' => 'listing_state', 'key' => 'sku_state_cn', 'label' => 'SKU状态',],
    ['field' => 'week_no_1', 'key' => 'week_no_1', 'label' => '第1周',],
    ['field' => 'week_no_2', 'key' => 'week_no_2', 'label' => '第2周',],
    ['field' => 'week_no_3', 'key' => 'week_no_3', 'label' => '第3周',],
    ['field' => 'week_no_4', 'key' => 'week_no_4', 'label' => '第4周',],
    ['field' => 'week_no_5', 'key' => 'week_no_5', 'label' => '第5周',],
    ['field' => 'month_sales', 'key' => 'month_sales', 'label' => '本月销量',],
    ['field' => 'accumulative_sales', 'key' => 'accumulative_sales', 'label' => '累计销量',],
    ['field' => 'accumulative_return', 'key' => 'accumulative_return', 'label' => '累计退货',],
    ['field' => 'rma', 'key' => 'rma', 'label' => 'RMA%',],
    ['field' => 'can_sale_stock', 'key' => 'can_sale_stock', 'label' => '可售库存',],
    ['field' => 'cannot_sale_stock', 'key' => 'cannot_sale_stock', 'label' => '不可售库存',],
    ['field' => 'shipping_full_in_transit', 'key' => 'shipping_full_in_transit', 'label' => '海运整柜在途',],
    ['field' => 'shipping_bulk_in_transit', 'key' => 'shipping_bulk_in_transit', 'label' => '海运散货在途',],
    ['field' => 'trains_full_in_transit', 'key' => 'trains_full_in_transit', 'label' => '铁运整柜在途',],
    ['field' => 'trains_bulk_in_transit', 'key' => 'trains_bulk_in_transit', 'label' => '铁运散货在途',],
    ['field' => 'air_in_transit', 'key' => 'air_in_transit', 'label' => '空运在途',],
    ['field' => 'land_in_transit', 'key' => 'land_in_transit', 'label' => '陆运在途',],
    ['field' => 'blueorder_in_transit', 'key' => 'blueorder_in_transit', 'label' => '蓝单在途',],
    ['field' => 'redorder_in_transit', 'key' => 'redorder_in_transit', 'label' => '红单在途',],
];

$lang['oversea_stock_condition_day'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU',],
    ['field' => 'product_name', 'key' => 'product_name', 'label' => '产品名称',],
    ['field' => 'station_code', 'key' => 'station_code_cn', 'label' => '海外站点',],
    ['field' => 'shelf_time', 'key' => 'shelf_time', 'label' => '上架时间',],
    ['field' => 'listing_state', 'key' => 'sku_state_cn', 'label' => 'SKU状态',],
    ['field' => 'last_days_1', 'key' => 'last_days_1', 'label' => '过去1天',],
    ['field' => 'last_days_3', 'key' => 'last_days_3', 'label' => '过去3天',],
    ['field' => 'last_days_7', 'key' => 'last_days_7', 'label' => '过去7天',],
    ['field' => 'last_days_14', 'key' => 'last_days_14', 'label' => '过去14天',],
    ['field' => 'last_days_28', 'key' => 'last_days_28', 'label' => '过去28天',],
    ['field' => 'accumulative_sales', 'key' => 'accumulative_sales', 'label' => '累计销量',],
    ['field' => 'accumulative_return', 'key' => 'accumulative_return', 'label' => '累计退货',],
    ['field' => 'rma', 'key' => 'rma', 'label' => 'RMA',],
    ['field' => 'can_sale_stock', 'key' => 'can_sale_stock', 'label' => '可售库存',],
    ['field' => 'cannot_sale_stock', 'key' => 'cannot_sale_stock', 'label' => '不可售库存',],
    ['field' => 'shipping_full_in_transit', 'key' => 'shipping_full_in_transit', 'label' => '海运整柜在途',],
    ['field' => 'shipping_bulk_in_transit', 'key' => 'shipping_bulk_in_transit', 'label' => '海运散货在途',],
    ['field' => 'trains_full_in_transit', 'key' => 'trains_full_in_transit', 'label' => '铁运整柜在途',],
    ['field' => 'trains_bulk_in_transit', 'key' => 'trains_bulk_in_transit', 'label' => '铁运散货在途',],
    ['field' => 'air_in_transit', 'key' => 'air_in_transit', 'label' => '空运在途',],
    ['field' => 'land_in_transit', 'key' => 'land_in_transit', 'label' => '陆运在途',],
    ['field' => 'blueorder_in_transit', 'key' => 'blueorder_in_transit', 'label' => '蓝单在途',],
    ['field' => 'redorder_in_transit', 'key' => 'redorder_in_transit', 'label' => '红单在途',],
];


//导入导出
$lang['tag'] = '请勿修改此标记';

//库存状况
$lang['seller_sku']           = 'SELLERSKU';
$lang['shelf_time']           = '上架时间';
$lang['month_1']              = '一月';
$lang['month_2']              = '二月';
$lang['month_3']              = '三月';
$lang['month_4']              = '四月';
$lang['month_5']              = '五月';
$lang['month_6']              = '六月';
$lang['month_7']              = '七月';
$lang['month_8']              = '八月';
$lang['month_9']              = '九月';
$lang['month_10']             = '十月';
$lang['month_11']             = '十一月';
$lang['month_12']             = '十二月';
$lang['week_no_1']            = '第1周';
$lang['week_no_2']            = '第2周';
$lang['week_no_3']            = '第3周';
$lang['week_no_4']            = '第4周';
$lang['week_no_5']            = '第5周';
$lang['last_days_1']          = '过去1天';
$lang['last_days_3']          = '过去3天';
$lang['last_days_7']          = '过去7天';
$lang['last_days_14']         = '过去14天';
$lang['last_days_28']         = '过去28天';
$lang['month_sales']          = '本月销量';
$lang['weighted_sales']       = '加权销量';
$lang['accumulative_sales']   = '累计销量';
$lang['accumulative_return']  = '累计退货';
$lang['rma']                  = 'RMA%';
$lang['can_sale_stock']       = '可售库存';
$lang['cannot_sale_stock']    = '不可售库存';
$lang['shipping_in_transit']  = '海运在途';
$lang['iron_in_transit']      = '铁运在途';
$lang['air_in_transit']       = '空运在途';
$lang['blueorder_in_transit'] = '蓝单在途';
$lang['redorder_in_transit']  = '红单在途';
$lang['fba_onway_data']       = 'FBA在途数据';
$lang['shipping_full_in_transit']       = '海运整柜在途';
$lang['shipping_bulk_in_transit']       = '海运散货在途';
$lang['trains_full_in_transit']       = '铁运整柜在途';
$lang['trains_bulk_in_transit']       = '铁运散货在途';
$lang['land_in_transit']       = '陆运在途';

//国内需求列表
$lang['inland_pr_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'pr_sn', 'key' => 'pr_sn', 'label' => '需求单号'],
    ['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态'],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU'],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称'],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否退税'],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '采购仓库'],
    ['field' => 'sku_state', 'key' => 'sku_state_text', 'label' => 'SKU状态'],
    ['field' => 'stock_up_type', 'key' => 'stock_up_type_text', 'label' => '备货方式'],
    ['field' => 'debt_qty', 'key' => 'debt_qty', 'label' => '欠货量'],
    ['field' => 'pr_qty', 'key' => 'pr_qty', 'label' => 'PR数量'],
    ['field' => 'ship_qty', 'key' => 'ship_qty', 'label' => '采购在途数量'],
    ['field' => 'available_qty', 'key' => 'available_qty', 'label' => '可用库存(pcs)'],
    ['field' => 'purchase_price', 'key' => 'purchase_price', 'label' => '采购单价'],
    ['field' => 'accumulated_order_days', 'key' => 'accumulated_order_days', 'label' => '28天内出单天数'],
    ['field' => 'accumulated_sale_qty', 'key' => 'accumulated_sale_qty', 'label' => '28天总销量'],
    //['field' => 'sale_qty_order', 'key' => 'sale_qty_order', 'label' => '排序'],
    ['field' => 'weight_sale_pcs', 'key' => 'weight_sale_pcs', 'label' => '加权日均销量'],
    ['field' => 'sale_sd_pcs', 'key' => 'sale_sd_pcs', 'label' => '销量标准偏差'],
    ['field' => 'deliver_sd_day', 'key' => 'deliver_sd_day', 'label' => '交付标准偏差'],
    ['field' => 'supply_wa_day', 'key' => 'supply_wa_day', 'label' => '权均供货周期'],
    ['field' => 'buffer_pcs', 'key' => 'buffer_pcs', 'label' => '缓冲库存'],
    ['field' => 'purchase_cycle_day', 'key' => 'purchase_cycle_day', 'label' => '备货处理周期'],
    ['field' => 'ship_timeliness_day', 'key' => 'ship_timeliness_day', 'label' => '发运时效'],
    ['field' => 'fixed_amount', 'key' => 'fixed_amount', 'label' => '一次修正量'],
    ['field' => 'pre_day', 'key' => 'pre_day', 'label' => '备货提前期'],
    ['field' => 'sc_day', 'key' => 'sc_day', 'label' => '一次备货天数SC'],
    ['field' => 'z', 'key' => 'z', 'label' => '服务对应"Z"值'],
    ['field' => 'safe_stock_pcs', 'key' => 'safe_stock_pcs', 'label' => '安全库存'],
    ['field' => 'point_pcs', 'key' => 'point_pcs', 'label' => '订购点'],
    ['field' => 'supply_day', 'key' => 'supply_day', 'label' => '可用库存支撑天数'],
    ['field' => 'expect_exhaust_date', 'key' => 'expect_exhaust_date', 'label' => '预计断货时间'],
    ['field' => 'require_qty', 'key' => 'require_qty', 'label' => '需求数量'],
    ['field' => 'stocked_qty', 'key' => 'stocked_qty', 'label' => '已备货数量'],
    ['field' => 'is_trigger_pr', 'key' => 'is_trigger_pr_text', 'label' => '触发需求'],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间'],
    ['field' => 'expired', 'key' => 'expired_text', 'label' => '是否过期'],
    ['field' => 'expand_factor', 'key' => 'expand_factor', 'label' => '扩销系数'],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];

//国内需求跟踪列表
$lang['inland_pr_track_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'pr_sn', 'key' => 'pr_sn', 'label' => '需求单号'],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU'],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否退税'],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '采购仓库'],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称'],
    ['field' => 'require_qty', 'key' => 'require_qty', 'label' => '需求数量'],
    ['field' => 'expect_exhaust_date', 'key' => 'expect_exhaust_date', 'label' => '预计断货时间'],
    ['field' => 'sum_sn', 'key' => 'sum_sn', 'label' => '汇总单号'],
    ['field' => 'pur_sn', 'key' => 'pur_sn', 'label' => '备货单号'],
    ['field' => 'stocked_qty', 'key' => 'stocked_qty', 'label' => '已备货数量'],
    ['field' => 'pur_state', 'key' => 'pur_state', 'label' => '备货状态'],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间'],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];

//国内需求汇总列表
$lang['inland_pr_summary_list'] = [
    ['field' => 'index', 'label' => '序号'],
    ['field' => 'sum_sn', 'label' => '汇总单号'],
    ['field' => 'bussiness_line', 'label' => '需求业务线'],
    ['field' => 'sku', 'label' => 'SKU'],
    ['field' => 'is_refund_tax', 'label' => '是否退税'],
    ['field' => 'purchase_warehouse_id', 'label' => '采购仓库'],
    ['field' => 'sku_name', 'label' => '产品名称'],
    ['field' => 'total_required_qty', 'label' => '总需求数量'],
    ['field' => 'earliest_exhaust_date', 'label' => '最早缺货时间'],
    ['field' => 'created_at', 'label' => '创建时间'],
    ['field' => 'remark', 'label' => '备注'],
    ['field' => 'operation', 'label' => '操作'],
];


//国内销量运算配置
$lang['inland_operation_cfg_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'set_start_date,set_end_date', 'key' => 'no_operation_date', 'label' => '不参与运算时间'],
    ['field' => 'platform_code', 'key' => 'platform_code', 'label' => '不参与预算平台'],
    ['field' => 'skus', 'key' => 'skus', 'label' => '不参与预算sku'],
    ['field' => 'created_zh_name,created_at', 'key' => 'created_info', 'label' => '创建信息'],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'updated_info', 'label' => '修改信息'],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];

//国内手动备货列表
$lang['inland_special_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'pr_sn', 'key' => 'pr_sn', 'label' => '需求单号'],
    ['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态'],
    ['field' => 'requisition_date', 'key' => 'requisition_date', 'label' => '申请日期'],
//    ['field' => 'calc_date', 'key' => 'calc_date', 'label' => '参与运算时间'],v1.1.1去掉参与运算时间
    ['field' => 'requisition_uid', 'key' => 'requisition_uid_cn', 'label' => '申请人'],
    ['field' => 'requisition_platform_code', 'key' => 'requisition_platform_name', 'label' => '申请平台'],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU'],
    ['field' => 'require_qty', 'key' => 'require_qty', 'label' => '需求数量'],
    ['field' => 'requisition_reason', 'key' => 'requisition_reason', 'label' => '申请原因'],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称'],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_text', 'label' => '是否退税'],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_text', 'label' => '采购仓库'],
    ['field' => 'is_sku_match', 'key' => 'is_sku_match_text', 'label' => 'SKU是否匹配'],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间'],
    ['field' => 'updated_uid,updated_at', 'key' => 'modify_info', 'label' => '修改信息'],
    ['field' => 'approved_uid,approved_at', 'key' => 'approve_info', 'label' => '审核信息'],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];

$lang['inland_inventory_report_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间'],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU'],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称'],
    ['field' => 'sku_state', 'key' => 'sku_state_cn', 'label' => 'SKU状态'],
    ['field' => 'out_order_day', 'key' => 'out_order_day', 'label' => '28天内出单天数'],
    ['field' => 'owe_qty', 'key' => 'owe_qty', 'label' => '欠货量'],
    ['field' => 'pr_qty', 'key' => 'pr_qty', 'label' => 'PR数量'],
    ['field' => 'purchase_way_qty', 'key' => 'purchase_way_qty', 'label' => '采购在途数量'],
    ['field' => 'available_stock', 'key' => 'available_stock', 'label' => '可用库存'],
    ['field' => 'purchase_price', 'key' => 'purchase_price', 'label' => '采购单价'],
    ['field' => 'safe_stock_pcs', 'key' => 'safe_stock_pcs', 'label' => '安全库存'],
    ['field' => 'order_point', 'key' => 'order_point', 'label' => '订购点'],
];

//国内备货关系配置表
$lang['inland_stock_cfg_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'rule_type', 'key' => 'rule_type_cn', 'label' => '规则'],
    ['field' => 'state', 'key' => 'state_cn', 'label' => '审核状态'],
    ['field' => 'sku', 'key' => 'sku', 'label' => 'SKU'],
    ['field' => 'sku_name', 'key' => 'sku_name', 'label' => '产品名称'],
    ['field' => 'path_name_first', 'key' => 'path_name_first', 'label' => '一级产品线'],
    ['field' => 'is_refund_tax', 'key' => 'is_refund_tax_cn', 'label' => '是否退税'],
    ['field' => 'purchase_warehouse_id', 'key' => 'purchase_warehouse_id_cn', 'label' => '采购仓库'],
    ['field' => 'provider_status', 'key' => 'provider_status_cn', 'label' => '货源状态'],
    ['field' => 'sku_state', 'key' => 'sku_state_cn', 'label' => '计划系统sku状态'],
    ['field' => 'product_status', 'key' => 'product_status_cn', 'label' => 'erp系统sku状态'],
    ['field' => 'quality_goods', 'key' => 'quality_goods_cn', 'label' => '是否精品'],
    ['field' => 'stock_way', 'key' => 'stock_way_cn', 'label' => '备货方式'],
    ['field' => 'bs', 'key' => 'bs', 'label' => '缓冲库存天数'],
    ['field' => 'max_safe_stock_day', 'key' => 'max_safe_stock_day', 'label' => '最大安全库存天数'],
    ['field' => 'sp', 'key' => 'sp', 'label' => '备货处理周期'],
    ['field' => 'shipment_time', 'key' => 'shipment_time', 'label' => '发运时效'],
    ['field' => 'first_lt', 'key' => 'first_lt', 'label' => '首次供货周期'],
    ['field' => 'sc', 'key' => 'sc', 'label' => '一次备货天数'],
    ['field' => 'reduce_factor', 'key' => 'reduce_factor', 'label' => '季节性系数'],
    ['field' => 'sz', 'key' => 'sz', 'label' => '服务对应"Z"值'],
    ['field' => 'deved_time', 'key' => 'deved_time', 'label' => '开发完成时间'],
    ['field' => 'published_time', 'key' => 'published_time', 'label' => '首次刊登时间'],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间'],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'updated_info', 'label' => '修改信息'],
    ['field' => 'approved_zh_name,approved_at', 'key' => 'approved_info', 'label' => '审核信息'],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注'],
    ['field' => 'refund_rate', 'key' => 'refund_rate', 'label' => '退款率'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];

$lang['fba_shipment_plan_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'shipment_sn', 'key' => 'pr_sn', 'label' => '发运计划编号'],
    ['field' => 'pr_date', 'key' => 'pr_date', 'label' => '关联需求日期'],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间'],
    ['field' => 'created_uid', 'key' => 'created_uid', 'label' => '创建人'],
    ['field' => 'push_status', 'key' => 'push_status', 'label' => '当前状态'],
    ['field' => 'push_time', 'key' => 'push_time', 'label' => '推送时间'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];

$lang['oversea_shipment_plan_list'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'shipment_sn', 'key' => 'pr_sn', 'label' => '发运计划编号'],
    ['field' => 'pr_date', 'key' => 'pr_date', 'label' => '关联需求日期'],
    ['field' => 'created_at', 'key' => 'created_at', 'label' => '创建时间'],
    ['field' => 'created_uid', 'key' => 'created_uid', 'label' => '创建人'],
    ['field' => 'push_status', 'key' => 'push_status', 'label' => '当前状态'],
    ['field' => 'push_time', 'key' => 'push_time', 'label' => '推送时间'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];



//国内全局规则配置列表
$lang['inland_global_cfg']        = [
    'i'               => '序号',
    'id'              => 'id',
    'bs'              => '缓冲库存天数',
    'sp'              => '备货处理周期',
    'shipment_time'   => '发运时效',
    'sc'              => '一次备货天数',
    'first_lt'        => '首次供货周期',
    'sz'              => '服务对应"z"值',
    'created_at'      => '创建时间',
    'updated_at'      => '修改时间',
    'updated_zh_name' => '修改人中文名',
    'remark'          => '备注',
];
$lang['inland_global_cfg_log']    = [
    'created_at' => '操作时间',
    'user_name'  => '操作人',
    'context'    => '操作内容',
];
$lang['inland_global_cfg_remark'] = [
    'created_at' => '操作时间',
    'user_name'  => '操作人',
    'remark'     => '操作内容',
];

$lang['inland_sales_report'] = [
    'i'                    => '序号',
    'created_at'           => '创建时间',
    'sku'                  => 'SKU',
    'sku_name'             => '产品名称',
    'sku_state'            => 'SKU状态',
    'out_order_day'        => '28天内出单天数',
    'accumulated_sale_qty' => '28天总销量',
    'sort'                 => '排序',
    'weight_sale_pcs'      => '加权日均销量',
    'deliver_sd_day'       => '交付标准偏差',
    'supply_wa_day'        => '权均供货周期',

];
//发运计划跟踪列表
$lang['fba_shipment_track_list'] = [
    ['field' => '',                       'key' => 'index',                           'label' => '序号'],
    ['field' => 'pr_sn',                  'key' => 'pr_sn',                           'label' => '需求单号'],
    ['field' => 'pur_sn',                 'key' => 'pur_sn',                          'label' => '采购单号'],
    ['field' => 'sku',                    'key' => 'sku',                             'label' => 'SKU'],
    ['field' => 'fnsku',                  'key' => 'fnsku',                           'label' => 'FNSKU'],
    ['field' => 'seller_sku',             'key' => 'seller_sku',                      'label' => 'SELLER_SKU'],
    ['field' => 'asin',                   'key' => 'asin',                            'label' => 'ASIN'],
    ['field' => 'account_name',           'key' => 'account_name',                    'label' => 'FBA账号'],
    ['field' => 'shipment_type',          'key' => 'shipment_type_text',                   'label' => '发运类型'],
    ['field' => 'warehouse_id',           'key' => 'warehouse_id_text',                    'label' => '发货仓库'],
    ['field' => 'station_code',           'key' => 'station_code_text',                    'label' => '站点'],
    ['field' => 'pr_qty',                 'key' => 'pr_qty',                          'label' => '需求数量'],
    ['field' => 'warehouse_destination_name','key' => 'warehouse_destination_name',       'label' => '目的仓'],
    ['field' => 'shipment_status',        'key' => 'shipment_status_text',                 'label' => '发运状态'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];

//发运计划详情列表
$lang['fba_shipment_detail_list'] = [
    ['field' => '',                       'key' => 'index',                           'label' => '序号'],
    ['field' => 'pr_sn',                  'key' => 'pr_sn',                           'label' => '需求单号'],
    ['field' => 'pur_sn',                 'key' => 'pur_sn',                          'label' => '采购单号'],
    ['field' => 'sku',                    'key' => 'sku',                             'label' => 'SKU'],
    ['field' => 'fnsku',                  'key' => 'fnsku',                           'label' => 'FNSKU'],
    ['field' => 'seller_sku',             'key' => 'seller_sku',                      'label' => 'SELLER_SKU'],
    ['field' => 'asin',                   'key' => 'asin',                            'label' => 'ASIN'],
    ['field' => 'account_name',           'key' => 'account_name',                    'label' => 'FBA账号'],
    ['field' => 'shipment_type',          'key' => 'shipment_type_text',                   'label' => '发运类型'],
    ['field' => 'business_type',          'key' => 'business_type_text',              'label' => '业务类型'],
    ['field' => 'logistics_id',           'key' => 'logistics_id_text',                    'label' => '物流类型'],
    ['field' => 'station_code',           'key' => 'station_code_text',                    'label' => '站点'],
    ['field' => 'warehouse_id',           'key' => 'warehouse_id_text',                    'label' => '发货仓库'],
    ['field' => 'country_of_destination', 'key' => 'country_of_destination',          'label' => '目的国'],
    ['field' => 'pur_cost',               'key' => 'pur_cost',                        'label' => '采购成本'],
    ['field' => 'pr_qty',                 'key' => 'pr_qty',                          'label' => '需求数量'],
    ['field' => 'order_source',           'key' => 'order_source',                          'label' => '订单来源'],
//    ['field' => 'audited_sku',            'key' => 'audited_sku',                          'label' => '审核后的sku'],
    ['field' => 'is_refund_tax',          'key' => 'is_refund_tax_text',                   'label' => '是否退税'],
//    ['field' => 'inbound_type',           'key' => 'inbound_type_text',                          'label' => '入库类型'],
    ['field' => 'cargo_company_name',     'key' => 'cargo_company_name',                          'label' => '物流公司'],
    ['field' => 'ship_name',                'key' => 'ship_name',                          'label' => '物流方式'],
    ['field' => 'is_inspection',          'key' => 'is_inspection_text',                          'label' => '是否商检'],
    ['field' => 'is_fumigation',     'key' => 'is_fumigation_text',                'label' => '是否熏蒸'],
    ['field' => 'warehouse_destination_name','key' => 'warehouse_destination_name',       'label' => '目的仓'],
    ['field' => 'vat',                    'key' => 'vat',                             'label' => 'VAT'],

];

//海外发运计划跟踪列表
$lang['oversea_shipment_track_list'] = [
    ['field' => '',                       'key' => 'index',                           'label' => '序号'],
    ['field' => 'stock_sn', 'key' => 'stock_sn', 'label' => '备货单号'],
    ['field' => 'pr_sn',                  'key' => 'pr_sn',                           'label' => '需求单号'],
    ['field' => 'pur_sn',                 'key' => 'pur_sn',                          'label' => '采购单号'],
    ['field' => 'sku',                    'key' => 'sku',                             'label' => 'SKU'],
    ['field' => 'shipment_type',          'key' => 'shipment_type_text',                   'label' => '发运类型'],
    ['field' => 'warehouse_id',           'key' => 'warehouse_id_text',               'label' => '发货仓库'],
    ['field' => 'station_code',           'key' => 'station_code_text',                    'label' => '站点'],
    ['field' => 'pr_qty',                 'key' => 'pr_qty',                          'label' => '需求数量'],
    ['field' => 'warehouse_destination_name','key' => 'warehouse_destination_name',       'label' => '目的仓'],
    ['field' => 'shipment_status',        'key' => 'shipment_status_text',                 'label' => '发运状态'],
    ['field' => '', 'key' => 'operation', 'label' => '操作'],
];

//海外发运计划详情列表
$lang['oversea_shipment_detail_list'] = [
    ['field' => '',                       'key' => 'index',                           'label' => '序号'],
    ['field' => 'stock_sn', 'key' => 'stock_sn', 'label' => '备货单号'],
    ['field' => 'pr_sn',                  'key' => 'pr_sn',                           'label' => '需求单号'],
    ['field' => 'pur_sn',                 'key' => 'pur_sn',                          'label' => '采购单号'],
    ['field' => 'sku',                    'key' => 'sku',                             'label' => 'SKU'],
    ['field' => 'shipment_type',          'key' => 'shipment_type_text',                   'label' => '发运类型'],
    ['field' => 'business_type',          'key' => 'business_type_text',              'label' => '业务类型'],
    ['field' => 'logistics_id',           'key' => 'logistics_id_text',                    'label' => '物流类型'],
    ['field' => 'station_code',           'key' => 'station_code_text',                    'label' => '站点'],
    ['field' => 'warehouse_id',           'key' => 'warehouse_id_text',                    'label' => '发货仓库'],
    ['field' => 'country_of_destination', 'key' => 'country_of_destination',          'label' => '目的国'],
    ['field' => 'pur_cost',               'key' => 'pur_cost',                        'label' => '采购成本'],
    ['field' => 'pr_qty',                 'key' => 'pr_qty',                          'label' => '需求数量'],
    ['field' => 'order_source',           'key' => 'order_source',                          'label' => '订单来源'],
//    ['field' => 'audited_sku',            'key' => 'audited_sku',                          'label' => '审核后的sku'],
    ['field' => 'is_refund_tax',          'key' => 'is_refund_tax_text',                   'label' => '是否退税'],
//    ['field' => 'inbound_type',           'key' => 'inbound_type_text',                          'label' => '入库类型'],
    ['field' => 'cargo_company_name',     'key' => 'cargo_company_name',                          'label' => '物流公司'],
    ['field' => 'ship_name',              'key' => 'ship_name',                          'label' => '物流方式'],
    ['field' => 'is_inspection',          'key' => 'is_inspection_text',                          'label' => '是否商检'],
    ['field' => 'is_fumigation',          'key' => 'is_fumigation_text',                'label' => '是否熏蒸'],
    ['field' => 'warehouse_destination_name','key' => 'warehouse_destination_name',       'label' => '目的仓'],
    ['field' => 'vat',                    'key' => 'vat',                             'label' => 'VAT'],
];

//模拟发运单列表
$lang['python_send_yes_num']   =   [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'account_name', 'key' => 'account_name', 'label' => '账号站点'],
    ['field' => 'batch_num', 'key' => 'batch_num', 'label' => '批次号'],
    ['field' => 'need_batch_weight','key' => 'need_batch_weight', 'label' => 'need账号单重量'],
    ['field' => 'order_batch_weight', 'key' => 'order_batch_weight', 'label' => 'order账号单重量'],
    ['field' => 'if_product_attr','key' => 'if_product_attr', 'label' => '产品属性'],
    ['field' => 'only_flag','key' => 'only_flag', 'label' => '标识'],
    ['field' => 'site','key' => 'site', 'label' => '站点'],
    ['field' => 'account_id','key' => 'account_id', 'label' => 'account_id'],
    ['field' => 'seller_sku','key' => 'seller_sku', 'label' => 'sellersku'],
    ['field' => 'erp_sku','key' => 'erp_sku', 'label' => 'erpsku'],
    ['field' => 'sale_30','key' => 'sale_30', 'label' => '30天销量'],
    ['field' => 'quantity','key' => 'quantity', 'label' => 'seller_sku需求数量'],
    ['field' => 'per_weight','key' => 'per_weight', 'label' => '计费重'],
    ['field' => 'total_quantity', 'key' => 'total_quantity', 'label' => 'erp_sku总需求'],
    ['field' => 'fba_available_inv','key' => 'fba_available_inv', 'label' => '可用库存'],
    ['field' => 'country_inv','key' => 'country_inv', 'label' => '国内在库'],
    ['field' => 'country_per_sale', 'key' => 'country_per_sale', 'label' => '国内加权日均销量'],
    ['field' => 'country_can_allot', 'key' => 'country_can_allot', 'label' => '国内仓库可调拨库存量'],
    ['field' => 'sz_hm_can_allot', 'key' => 'sz_hm_can_allot', 'label' => '塘厦虎门可调拨库存'],
    ['field' => 'need_allot','key' => 'need_allot', 'label' => '需要调拨'],
    ['field' => 'real_allot', 'key' => 'real_allot', 'label' => '最终调拨'],
    ['field' => 'sz_real_allot','key' => 'sz_real_allot', 'label' => '最终塘厦调拨'],
    ['field' => 'hm_real_allot', 'key' => 'hm_real_allot', 'label' => '最终虎门调拨'],
    ['field' => 'real_send', 'key' => 'real_send', 'label' => '实际发货量'],
    ['field' => 'transferApplyNum','key' => 'transferApplyNum', 'label' => '调拨申请号'],
    ['field' => 'by_need', 'key' => 'by_need', 'label' => '按需求分摊'],
    ['field' => 'by_order', 'key' => 'by_order', 'label' => '按销量分摊'],
    ['field' => 'product_cost','key' => 'product_cost', 'label' => '采购成本'],
    ['field' => 'update_time','key' => 'update_time', 'label' => '更新时间'],
];

//实际发运单列表
$lang['python_send_real_allot']   =   [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'account_name', 'key' => 'account_name', 'label' => '账号站点'],
    ['field' => 'batch_num', 'key' => 'batch_num', 'label' => '批次号'],
    ['field' => 'need_batch_weight','key' => 'need_batch_weight', 'label' => 'need账号单重量'],
    ['field' => 'order_batch_weight', 'key' => 'order_batch_weight', 'label' => 'order账号单重量'],
    ['field' => 'if_product_attr','key' => 'if_product_attr', 'label' => '产品属性'],
    ['field' => 'only_flag','key' => 'only_flag', 'label' => '标识'],
    ['field' => 'site','key' => 'site', 'label' => '站点'],
    ['field' => 'account_id','key' => 'account_id', 'label' => 'account_id'],
    ['field' => 'seller_sku','key' => 'seller_sku', 'label' => 'sellersku'],
    ['field' => 'erp_sku','key' => 'erp_sku', 'label' => 'erpsku'],
    ['field' => 'sale_30','key' => 'sale_30', 'label' => '30天销量'],
    ['field' => 'quantity','key' => 'quantity', 'label' => 'seller_sku需求数量'],
    ['field' => 'per_weight','key' => 'per_weight', 'label' => '计费重'],
    ['field' => 'total_quantity', 'key' => 'total_quantity', 'label' => 'erp_sku总需求'],
    ['field' => 'fba_available_inv','key' => 'fba_available_inv', 'label' => '可用库存'],
    ['field' => 'real_send', 'key' => 'real_send', 'label' => '实际发货量'],
    ['field' => 'by_need', 'key' => 'by_need', 'label' => '按需求分摊'],
    ['field' => 'by_order', 'key' => 'by_order', 'label' => '按销量分摊'],
    ['field' => 'update_time','key' => 'update_time', 'label' => '更新时间'],
];

//新品
$lang['fba_new'] = [
    ['field' => '', 'key' => 'index', 'label' => '序号',],
    ['field' => 'approve_state', 'key' => 'approve_state_text', 'label' => '审核状态',],
    ['field' => 'sale_group_name', 'key' => 'sale_group_name', 'label' => '销售分组',],
    ['field' => 'account_name', 'key' => 'account_name', 'label' => '销售账号',],
    ['field' => 'staff_zh_name', 'key' => 'staff_zh_name', 'label' => '销售名称',],
    ['field' => 'site', 'key' => 'site', 'label' => '站点',],
    ['field' => 'seller_sku', 'key' => 'seller_sku', 'label' => 'seller_sku',],
    ['field' => 'erp_sku', 'key' => 'erp_sku', 'label' => 'erpsku',],
    ['field' => 'fnsku', 'key' => 'fnsku', 'label' => 'fnsku',],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'asin',],
    ['field' => 'demand_num', 'key' => 'demand_num', 'label' => '需求数量',],
    ['field' => 'shipment_qty_create', 'key' => 'shipment_qty_create', 'label' => '已发运数量(建单时)',],
    ['field' => 'shipment_qty_approved', 'key' => 'shipment_qty_approved', 'label' => '已发运数量(审批后)',],
    ['field' => 'stock_num', 'key' => 'stock_num', 'label' => '备货数量',],
    ['field' => 'created_zh_name,created_at', 'key' => 'created_at', 'label' => '创建时间',],
    ['field' => 'updated_zh_name,updated_at', 'key' => 'updated_at', 'label' => '更新时间',],
    ['field' => 'approved_zh_name,approved_at', 'key' => 'approved_at', 'label' => '审核时间',],
    ['field' => 'remark', 'key' => 'remark', 'label' => '备注',],
    ['field' => '', 'key' => 'operation', 'label' => '操作',],
];
//FBA发运毛需求
$lang['python_send_require']   =   [
    ['field' => '', 'key' => 'index', 'label' => '序号'],
    ['field' => 'seller_sku','key' => 'seller_sku', 'label' => 'sellersku'],
    ['field' => 'fnsku','key' => 'fnsku', 'label' => 'fnsku'],
    ['field' => 'asin', 'key' => 'asin', 'label' => 'asin'],
    ['field' => 'erp_sku','key' => 'erp_sku', 'label' => 'erpsku'],
    ['field' => 'site','key' => 'site', 'label' => '发运目的站点'],
    ['field' => 'sale_amount_15_days', 'key' => 'sale_amount_15_days', 'label' => 'sale_15'],
    ['field' => 'sale_amount_30_days', 'key' => 'sale_amount_30_days', 'label' => 'sale_30'],
    ['field' => 'available_qty', 'key' => 'available_qty', 'label' => 'FBA可用库存'],
    ['field' => 'oversea_ship_qty','key' => 'oversea_ship_qty', 'label' => '国际在途'],
    ['field' => 'weight_sale_pcs', 'key' => 'weight_sale_pcs', 'label' => '加权销量'],
    ['field' => 'avg_deliver_day','key' => 'avg_deliver_day', 'label' => '日销标准差'],
    ['field' => 'active_amount','key' => 'active_amount', 'label' => '活动量'],
    ['field' => 'Inventory_age90','key' => 'Inventory_age90', 'label' => '超90天库龄'],
    ['field' => 'short_days','key' => 'short_days', 'label' => '缺货天数'],
    ['field' => 'days_for_sale','key' => 'days_for_sale', 'label' => '可支撑天数'],
    ['field' => 'fix_amount','key' => 'fix_amount', 'label' => '备货修正量'],
    ['field' => 'new_goods','key' => 'new_goods', 'label' => '新品'],
    ['field' => 'ls', 'key' => 'ls', 'label' => '物流时效'],
    ['field' => 'pt','key' => 'pt', 'label' => '打包时效'],
    ['field' => 'as_up', 'key' => 'as_up', 'label' => '上架时效'],
    ['field' => 'sp', 'key' => 'sp', 'label' => '备货处理周期'],
    ['field' => 'if_can_sale', 'key' => 'if_can_sale', 'label' => '是否运营'],
    ['field' => 'delivery_cycle','key' => 'delivery_cycle', 'label' => '发货周期'],
    ['field' => 'z_value','key' => 'z_value', 'label' => 'z值'],
    ['field' => 'shiiping_days','key' => 'shiiping_days', 'label' => '一次发运天数'],
    ['field' => 'bs','key' => 'bs', 'label' => '缓冲天数'],
    ['field' => 'ss_max','key' => 'ss_max', 'label' => '最大安全天数'],
    ['field' => 'soq','key' => 'soq', 'label' => '单票起步发运量'],
    ['field' => 'seller_sku_soq','key' => 'seller_sku_soq', 'label' => '单seller_sku最小发运量'],
    ['field' => 'max_extra_day','key' => 'max_extra_day', 'label' => '最大额外发运天数'],
    ['field' => 'Coefficient_of_expansion','key' => 'Coefficient_of_expansion', 'label' => '扩销系数'],
    ['field' => 'Delivery_lead_time','key' => 'Delivery_lead_time', 'label' => '发货提前期'],
    ['field' => 'ss','key' => 'ss', 'label' => '安全库存'],
    ['field' => 'shipping_point','key' => 'shipping_point', 'label' => '发运点'],
    ['field' => 'if_to_ship','key' => 'if_to_ship', 'label' => '是否触发需求'],
    ['field' => 'gross_amount','key' => 'gross_amount', 'label' => '发运毛需求量'],
    ['field' => 'gross_amount_soq','key' => 'gross_amount_soq', 'label' => '考虑最小发运量的需求量'],
    ['field' => 'Shipping_amount','key' => 'Shipping_amount', 'label' => '发货金额'],
    ['field' => 'update_time','key' => 'update_time', 'label' => '更新时间'],
];

//查看推送结果列表
$lang['python_push_erp_result']   =   [
    ['field' => 'creat_time', 'key' => 'creat_time', 'label' => '推送发起时间'],
    ['field' => 'total_items', 'key' => 'total_items', 'label' => '推送总条数'],
    ['field' => 'success_items','key' => 'success_items', 'label' => '推送成功条数'],
    ['field' => 'fail_items','key' => 'fail_items', 'label' => '推送失败条数'],
    ['field' => 'push_status_cn','key' => 'push_status_cn', 'label' => '总推送状态'],
    ['field' => 'update_time','key' => 'update_time', 'label' => '推送完成时间'],
];

//查看调拨结果列表
$lang['python_real_allot_result']   =   [
    ['field' => 'creat_time', 'key' => 'creat_time', 'label' => '调拨发起时间'],
    ['field' => 'total_items', 'key' => 'total_items', 'label' => '调拨总条数'],
    ['field' => 'success_items','key' => 'success_items', 'label' => '调拨成功条数'],
    ['field' => 'fail_items','key' => 'fail_items', 'label' => '调拨失败条数'],
    ['field' => 'allot_status_cn','key' => 'allot_status_cn', 'label' => '总调拨状态'],
    ['field' => 'update_time','key' => 'update_time', 'label' => '调拨完成时间'],
];
