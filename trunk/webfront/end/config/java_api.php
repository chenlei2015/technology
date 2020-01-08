<?php

//表映射配置
$config['tbl'] = [
        //海外仓映射表
        'J2_OVERSEA' => [
                'approveState'      => 'approve_state',
                'approvedAt'        => 'approved_at',
                'approveUid'        => 'approved_uid',
                'availableQty'      => 'available_qty',
                'avgDeliverDay'     => 'avg_deliver_day',
                'avgWeightSalePcs'  => 'avg_weight_sale_pcs',
                'bd'                => 'bd',
                'checkAttrState'    => 'check_attr_state',
                'checkAttrTime'     => 'check_attr_time',
                'checkAttrUid'      => 'check_attr_uid',
                'createdAt'         => 'created_at',
                'deviatePcs'        => 'deviate_28_pcs',
                'expectExhaustDate' => 'expect_exhaust_date',
                'expired'           => 'expired',
                'gid'               => 'gid',
                'isAddup'           => 'is_addup',
                'isPlanApprove'     => 'is_plan_approve',
                'isTriggerPr'       => 'is_trigger_pr',
                'jid'               => 'jid',
                'logisticsId'       => 'logistics_id',
                'overseaShipQty'    => 'oversea_ship_qty',
                'overseaUpQty'      => 'oversea_up_qty',
                'pointPcs'          => 'point_pcs',
                'prSn'              => 'pr_sn',
                'preDay'            => 'pre_day',
                'purchaseQty'       => 'purchase_qty',
                'remark'            => 'remark',
                'requireQty'        => 'require_qty',
                'safeStockPcs'      => 'safe_stock_pcs',
                'scDay'             => 'sc_day',
                'sku'               => 'sku',
                'skuName'           => 'sku_name',
                'skuState'          => 'sku_state',
                'stationCode'       => 'station_code',
                'supplyDay'         => 'supply_day',
                'updatedAt'         => 'updated_at',
                'updatedUid'        => 'updated_uid',
                'weightSalePcs'     => 'weight_sale_pcs',
                'z'                 => 'z',
        ],
        'J3_FBA' => [
                'accountName'       => 'account_name',
                'approveState'      => 'approve_state',
                'approvedUid'       => 'approved_uid',
                'asin'              => 'asin',
                'availableQty'      => 'available_qty',
                'avgDeliverDay'     => 'avg_deliver_day',
                'avgWeightSalePcs'  => 'avg_weight_sale_pcs',
                'bd'                => 'bd',
                'checkAttrState'    => 'check_attr_state',
                'checkAttrTime'     => 'check_attr_time',
                'checkAttrUid'      => 'check_attr_uid',
                'createdAt'         => 'created_at',
                'deviate28Pcs'      => 'deviate_28_pcs',
                'exchangeUpQty'     => 'exchange_up_qty',
                'expectExhaustDate' => 'expect_exhaust_date',
                'expired'           => 'expired',
                'fnsku'             => 'fnsku',
                'gid'               => 'gid',
                'isPlanApprove'     => 'is_plan_approve',
                'isTriggerPr'       => 'is_trigger_pr',
                'jid'               => 'jid',
                'listingState'      => 'listing_state',
                'logisticsId'       => 'logistics_id',
                'overseaShipQty'    => 'oversea_ship_qty',
                'pointPcs'          => 'point_pcs',
                'prSn'              => 'pr_sn',
                'preDay'            => 'pre_day',
                'purchaseQty'       => 'purchase_qty',
                'remark'            => 'remark',
                'requireQty'        => 'require_qty',
                'safeStockPcs'      => 'safe_stock_pcs',
                'saleGroup'         => 'sale_group',
                'salesman'          => 'salesman',
                'scDay'             => '',
                'sellerSku'         => 'sc_day',
                'sku'               => 'sku',
                'skuName'           => 'sku_name',
                'stationCode'       => 'station_code',
                'supplyDay'         => 'supply_day',
                'totalSupplyDay'    => 'total_supply_day',
                'updatedAt'         => 'updated_at',
                'updatedUid'        => 'updated_uid',
                'weightSalePcs'     => 'weight_sale_pcs',
                'yuStockQty'        => '',
                'z'                 => 'z',
        ]
];

//开启rpc的服务和model, key值： dir/class
$config['rpc_list'] = [
        //'oversea/PrListService',
        //'oversea/Oversea_pr_list_model',
        //'fba/PrListService',
        //'fba/FbaSummary'

];

$config['api'] = [
        'YB_J1_001' => [
                'api'    => 'getUserListByUserId',
                'method' => 'post',
                'server' => OA_JAVA_API_URL,
                'type'   => 'json',

        ],
        'YB_J1_002' => [
                'api' => 'oaUser/selectAllUserByDeptId',
                'method' => 'post',
                'server' => OA_JAVA_API_URL,
                'type'   => 'json',
        ],
        'YB_J1_003' => [
                'api' => 'oaDepartment/getDirectlyDept',
                'method' => 'post',
                'server' => OA_JAVA_API_URL,
                'type'   => 'json',
        ],
        'YB_J1_004' => [
                'api' => 'oaUser/selectOaUser',
                'method' => 'post',
                'server' => OA_JAVA_API_URL,
                'type'   => 'json',
        ],
        'YB_J1_005' => [
                'api' => 'getUserListByUserNo',
                'method' => 'post',
                'server' => OA_JAVA_API_URL,
                'type'   => 'json',
        ],
        'YB_J1_PLAN_001' => [
                'api' => 'mrp/yibaiPurchaseList/pushPurchase',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
        ],
        'YB_J2_OVERSEA_001' => [
                'api' => 'oversea/PR/list',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'map' => [
                        'input' => [
                                'approve_state'   => 'approveState',
                                'approved_at'     => ['approvedStart', 'approvedEnd'],
                                'created_at'      => ['startDate', 'endDate'],
                                'updated_at'      => ['updatedStart', 'updatedEnd'],
                                'expired'         => 'expired',
                                'is_plan_approve' => 'isPlanApprove',
                                'is_trigger_pr'   => 'isTriggerPr',
                                'page'            => 'pageNumber',
                                'per_page'        => 'pageSize',
                                'sku'             => 'sku',
                                'pr_sn'           => 'prSn',
                                'sku_state'       => 'skuState',
                                'station_code'    => 'stationCode',
                        ],
                        //需要转换
                        'output' => 'J2_OVERSEA'
                ]

        ],
        'YB_J2_OVERSEA_002' => [
                'api' => 'oversea/PR/detail',
                'method' => 'get',
                'server' => PLAN_JAVA_API_URL,
                //'type'   => 'json',
                'map' => [
                        'output' => 'J2_OVERSEA'
                ]
        ],
        'YB_J2_OVERSEA_003' => [
                'api' => 'oversea/PR/updateBD',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'map' => [
                        'input' => 'J2_OVERSEA',
                        'output' => 'J2_OVERSEA'
                ]
        ],
        'YB_J2_OVERSEA_004' => [
                'api' => 'oversea/PR/approval_first',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'map' => [
                        'output' => 'J2_OVERSEA'
                ]
        ],
        'YB_J2_OVERSEA_005' => [
                'api' => 'oversea/PR/approval_second',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'map' => [
                        'output' => 'J2_OVERSEA'
                ]
        ],
        'YB_J3_FBA_001' => [
                'api' => 'fbaPr/findDemand',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'map' => [
                        'input' => [
                                'account_name'    => 'accountId',
                                'approve_state'   => '',
                                'approved_at'     => ['approvedTimeStart', 'approvedTimeEnd'],
                                'asin'            => 'asin',
                                'created_at'      => ['createTimeStart', 'createTimeEnd'],
                                'expired'         => 'expired',
                                'export_save'     => '',
                                'fnsku'           => 'fnsku',
                                'gids'            => 'gids',
                                'is_plan_approve' => 'planApprove',
                                'is_trigger_pr'   => 'triggerPr',
                                'per_page'        => 'size',
                                'listing_state'   => 'listingState',
                                'page'            => 'page',
                                'pr_sn'           => 'prSn',
                                'sale_group'      => 'saleGroup',
                                'salesman'        => 'salesman',
                                'sku'             => 'sku',
                                'station_code'    => 'site',
                                'updated_at'      => '',
                        ],
                        //需要转换
                        //'output' => 'J2_OVERSEA'
                ]
        ],
        'YB_J3_FBA_002' => [
                'api' => 'fbaPr/update',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'pre_hook' => '',
                'map' => [
                        'input' => 'J2_OVERSEA',
                        //'output' => 'J2_OVERSEA'
                ]
        ],
        'YB_J3_FBA_003' => [
                'api' => 'fbaPr/XXXX',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'map' => [
                ]
        ],
        'YB_J3_FBA_004' => [
                'api' => 'fbaPr/XXXX',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'map' => [
                ]
        ],
        'YB_J3_FBA_005' => [
                'api' => 'fbaPr/XXXX',
                'method' => 'post',
                'server' => PLAN_JAVA_API_URL,
                'type'   => 'json',
                'map' => [
                ]
        ],
        'YB_ERP_01' => [
            'api' => 'erp/yibaiWarehouse/getAllWarehouse',
            'method' => 'post',
            'server' => PLAN_JAVA_API_URL,
            'type'   => 'json',
            'map' => [
            ]
        ],
        'YB_LOGISTICS_01' => [
            'api' => 'logistics/yibaiLogisticsTransitRule/batchGetWarehouseInfo',
            'method' => 'post',
            'server' => PLAN_JAVA_API_URL,
            'type'   => 'json',
            'map' => [
            ]
        ],
        'YB_PLAN_PUSH_SUMMARY' => [
            'api' => 'mrp/yibaiPurchaseList/savePurchaseList',
            'method' => 'post',
            'server' => PLAN_JAVA_API_URL,
            'type'   => 'json',
            'map' => [
            ]
        ],

];


