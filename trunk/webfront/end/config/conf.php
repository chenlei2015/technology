<?php
//常量定义文件
define('USER_CONSTANTS_FILE',APPPATH. 'modules/basic/classes/contracts/constants.php');

if (file_exists(USER_CONSTANTS_FILE) && !defined('GLOBAL_YES')) {
    require_once USER_CONSTANTS_FILE;
}

//权限路径
define('SECURITY_PATH','http://192.168.71.128:83');

//权限API路径
define('SECURITY_API_HOST','http://192.168.71.128');

//权限加密串
define('SECURITY_API_SECRET','123456');

//权限APPID
define('SECURITY_APP_ID',13);

//计划系统数据层地址
define('PLAN_HOST','http://192.168.71.170:84');

//计划系统后端端口
define('PLAN_HOST_PORT', 84);

//计划系统前后端鉴权秘钥
define('PLAN_SECRET','123456');

//是否IP拦截
define('IP_VALIDATE',false);

//RSA认证
define('RSA_VALIDATE',false);

//是否开启登录拦截
define('LOGIN_VALIDATE', true);

//定义java的配置文件
define('JAVA_API_CFG_FILE', 'java_api');

////////////////////////////////////与appdal同步区//////////////////////////

//是否开启token认证
define('ENABLE_JAVA_API_TOKEN', true);

//OA接口地址
define('OA_JAVA_API_URL','http://rest.dev.java.yibainetworklocal.com/oa');

//token地址
define('JAVA_SECRET_PATH', 'http://oauth.dev.java.yibainetworklocal.com/oauth/token?grant_type=client_credentials');

//token用户
define('JAVA_SECRET_USER', 'service');

//token秘钥
define('JAVA_SECRET_PASS', 'service');

//java planapi地址
define('PLAN_JAVA_API_URL','http://rest.dev.java.yibainetworklocal.com');

//亚马逊销售部门id
define('FBA_SALEMAN_DEP_ID', 30046131);

//定义java接口(cloud-service-erp)请求地址
define('CLOUD_SERVICE_ERP_API_URL','http://rest.dev.java.yibainetworklocal.com/erp');

//FBA采购仓库转中转仓
define('FBA_WAREHOUSE_JAVA_API_URL','http://rest.dev.java.yibainetworklocal.com');

//MRP源数据下载地址
define('MRP_SOURCE_DOWNLOAD_URL', 'http://192.168.71.170:82');

//导出文件地址
define('EXPORT_FILE_PATH', '/mnt/yibai_cloud/plan/project_upload');

//python对接mrp
define('PYTHON_MRP_API','http://mrp.yibainetwork.com:8000');

//////////////////////////////////与appdal同步区//////////////////////////


////////////////////////////////webfront特有//////////////////////////

//分布式系统新版erp的请求API url
define('SERVICE_ERP_GET_ALL_WAREHOUSE', 'yibaiWarehouse/getAllWarehouse');

//csv下载地址
define('EXPORT_DOWNLOAD_URL', 'http://192.168.71.170:82');

//定时任务鉴权key
define('CRON_SECRET_KEY', 'a#2=JnixfT');

define('MRP_BACKUP_SYSTEM_MACHINE_CODE',
    [
        '04BF-D4EB-C4FB-01FF-7C00-2109-06E9',
        'B8BF-CAEB-3AFB-AEFF-3F00-B903-06A9',
        '40BF-B0EB-76FB-7FFF-3000-7C09-06EA',
        '18BF-31EB-BFFB-CBFF-3C00-8609-06E9',
        '02BF-00EB-4CFB-4FFF-4F00-5009-06E9',
    ]
);

define('MRP_BACKUP_SYSTEM_URL', 'http://192.168.24.65:8000/login/login');
