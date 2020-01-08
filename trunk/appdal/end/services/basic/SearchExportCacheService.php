<?php

/**
 * 暂存列表搜索的条件或者直接结果集，用于其他场景中直接使用结果集的操作
 * 比如列表搜索-导出。
 * 按照session_id
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2018-12-20
 * @link
 */
class SearchExportCacheService
{
    /**
     * 一个session对应一个list， list下面是各个场景，session失效之后直接delete这个key完成
     * 所有清理操作
     *
     * @var string
     */
    const COLLECTION_SEARCH = 'search_cache_';

    const FBA_PR_LIST_SEARCH_EXPORT = 1;

    const FBA_SUMMARY_LIST_SEARCH_EXPORT = 2;

    const OVERSEA_PR_LIST_SEARCH_EXPORT = 3;

    const OVERSEA_TRACK_LIST_SEARCH_EXPORT = 4;

    const FBA_PR_TRACK_SEARCH_EXPORT = 8;

    const PLAN_PUR_LIST_SEARCH_EXPORT = 16;

    const PLAN_ALLOT_SN_SEARCH_EXPORT = 32;

    const PLAN_SUMMARY_SEARCH_EXPORT = 64;

    const PLAN_TRACK_SEACH_EXPORT = 128;

    const OVERSEA_SUMMARY_LIST_SEARCH_EXPORT = 256;

    const INLAND_PR_LIST_SEARCH_EXPORT = 257;

    const INLAND_PR_TRACK_SEARCH_EXPORT = 258;

    const INLAND_PR_SUMMARY_SEARCH_EXPORT = 259;

    const INLAND_SPECIAL_PR_LIST_SEARCH_EXPORT = 260;

    const SHIPMENT_PR_LIST_SEARCH_EXPORT = 301;

    const SHIPMENT_FBA_LIST_SEARCH_EXPORT = 302;

    const SHIPMENT_OVERSEA_LIST_SEARCH_EXPORT = 303;

    const FBA_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT = 600;

    const FBA_LOGISTICS_LIST_SEARCH_EXPORT = 601;

    const FBA_STOCK_CONDITION_EXPORT = 602;

    const FBA_PROMOTION_SKU_EXPORT = 603;

    const FBA_ACTIVITY_EXPORT = 604;

    const FBA_NEW_EXPORT = 605;

    const OVERSEA_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT = 610;

    const OVERSEA_LOGISTICS_LIST_SEARCH_EXPORT = 611;

    const OVERSEA_STOCK_CONDITION_EXPORT = 612;

    const OVERSEA_PLATFORM_LIST_EXPORT = 613;

    const OVERSEA_ACTIVITY_SEARCH_EXPORT = 614;

    const OVERSEA_NEW_EXPORT = 615;

    const INLAND_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT = 711;

    const INLAND_OPERATION_CFG_SEARCH_EXPORT = 721;

    const INLAND_INVENTORY_REPORT_SEARCH_EXPORT = 731;

    const INLAND_SALES_REPORT_SEARCH_EXPORT = 732;

    const INLAND_ACTIVITY_SEARCH_EXPORT = 741;


    private $_ci;

    private $_redis;

    private $_scene;

    private $_scene_key;

    private $_collection;

    private $_blank_char = '_b*a*l_';

    /**
     * 构造
     */
    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->library('rediss');
        $this->_redis = $this->_ci->rediss;
    }

    /**
     *
     * @return string[][]
     */
    public static final function scene_config()
    {
        return [
            SearchExportCacheService::FBA_PR_LIST_SEARCH_EXPORT => [
                's_key'  => 'fba_pr_list_',
                'es_key' => 'es_fba_pr_list_'
            ],
            SearchExportCacheService::FBA_SUMMARY_LIST_SEARCH_EXPORT => [
                's_key'   => 'fba_sku_summary_',
            ],
            SearchExportCacheService::OVERSEA_PR_LIST_SEARCH_EXPORT => [
                's_key'   => 'oversea_pr_list_',
            ],
            SearchExportCacheService::OVERSEA_TRACK_LIST_SEARCH_EXPORT => [
                's_key'   => 'oversea_track_list_',
            ],
            SearchExportCacheService::FBA_PR_TRACK_SEARCH_EXPORT => [
                's_key'   => 'fba_pr_track_list_',
            ],
            SearchExportCacheService::PLAN_PUR_LIST_SEARCH_EXPORT => [
                's_key'   => 'plan_pur_track_list_',
            ],
            SearchExportCacheService::PLAN_ALLOT_SN_SEARCH_EXPORT => [
                's_key'   => 'plan_allot_sn_list_',
            ],
            SearchExportCacheService::PLAN_SUMMARY_SEARCH_EXPORT => [
                's_key'   => 'plan_summary_list_',
            ],
            SearchExportCacheService::PLAN_TRACK_SEACH_EXPORT => [
                's_key'   => 'plan_track_list_',
            ],
            SearchExportCacheService::OVERSEA_SUMMARY_LIST_SEARCH_EXPORT => [
                's_key'   => 'oversea_summary_list_',
            ],
            SearchExportCacheService::INLAND_PR_LIST_SEARCH_EXPORT => [
                's_key'   => 'inland_pr_list',
            ],
            SearchExportCacheService::INLAND_PR_TRACK_SEARCH_EXPORT => [
                's_key'   => 'inland_track_list',
            ],
            SearchExportCacheService::INLAND_PR_SUMMARY_SEARCH_EXPORT => [
                's_key'   => 'inland_summary_list',
            ],
            SearchExportCacheService::INLAND_SPECIAL_PR_LIST_SEARCH_EXPORT => [
                's_key'   => 'inland_special_list',
            ],
            SearchExportCacheService::INLAND_ACTIVITY_SEARCH_EXPORT => [
                's_key'   => 'inland_activity_list',
            ],
            SearchExportCacheService::FBA_STOCK_CONDITION_EXPORT => [
                's_key'   => 'fba_condition_list_',
            ],
            SearchExportCacheService::FBA_PROMOTION_SKU_EXPORT => [
                's_key'   => 'fba_promotion_list_',
            ],
            SearchExportCacheService::FBA_ACTIVITY_EXPORT => [
                's_key'   => 'fba_activity_',
            ],
            SearchExportCacheService::FBA_NEW_EXPORT => [
                's_key'   => 'fba_new_',
            ],
            SearchExportCacheService::OVERSEA_STOCK_CONDITION_EXPORT => [
                's_key'   => 'oversea_condition_list_',
            ],
            SearchExportCacheService::OVERSEA_PLATFORM_LIST_EXPORT => [
                's_key'   => 'oversea_platform_list_',
            ],
            SearchExportCacheService::FBA_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT => [
                's_key'   => 'fba_stock_relationship_cfg_list_',
            ],
            SearchExportCacheService::OVERSEA_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT => [
                's_key'   => 'oversea_stock_relationship_cfg_list_',
            ],
            SearchExportCacheService::FBA_LOGISTICS_LIST_SEARCH_EXPORT => [
                's_key'   => 'fba_logistics_list_',
            ],
            SearchExportCacheService::OVERSEA_LOGISTICS_LIST_SEARCH_EXPORT => [
                's_key'   => 'oversea_logistics_list_',
            ],
            SearchExportCacheService::INLAND_OPERATION_CFG_SEARCH_EXPORT => [
                's_key'   => 'inland_operation_list_',
            ],
            SearchExportCacheService::INLAND_INVENTORY_REPORT_SEARCH_EXPORT => [
                's_key'   => 'inland_inventory_report_list_',
            ],
            SearchExportCacheService::INLAND_STOCK_RELATIONSHIP_CFG_SEARCH_EXPORT => [
                's_key'   => 'inland_stock_relationship_cfg_list_',
            ],
            SearchExportCacheService::INLAND_SALES_REPORT_SEARCH_EXPORT => [
                's_key'   => 'inland_sales_report_list_',
            ],
            SearchExportCacheService::SHIPMENT_PR_LIST_SEARCH_EXPORT => [
                's_key'   => 'shipment_pr_list_',
            ],
            SearchExportCacheService::SHIPMENT_FBA_LIST_SEARCH_EXPORT => [
                's_key'   => 'shipment_fba_list_',
            ],
            SearchExportCacheService::SHIPMENT_OVERSEA_LIST_SEARCH_EXPORT => [
                's_key'   => 'shipment_oversea_list_',
            ],
            SearchExportCacheService::OVERSEA_ACTIVITY_SEARCH_EXPORT => [
                's_key'   => 'oversea_activity_list',
            ],
            SearchExportCacheService::OVERSEA_NEW_EXPORT => [
                's_key'   => 'oversea_new_',
            ],
        ];
    }

    /**
     * 设置订单场景
     *
     * @param unknown $name
     * @throws \InvalidArgumentException
     */
    public function setScene($name)
    {
        $config = static::scene_config()[$name] ?? [];
        if (!$config)
        {
            throw new \InvalidArgumentException(sprintf('未定义的搜索导出：%s', $name), 3001);
        }
        $this->_scene = $name;
        $session_id = get_active_user()->silent_get('session_id');
        if ($session_id)
        {
            $this->_collection = self::COLLECTION_SEARCH.$session_id;
            $this->_scene_key = $config['s_key'].$session_id;
        }
        return $this;
    }

    /**
     * 设置订单场景
     *
     * @param unknown $name
     *
     * @throws \InvalidArgumentException
     */
    public function setESScene($name)
    {
        $config = static::scene_config()[$name] ?? [];
        if (!$config) {
            throw new \InvalidArgumentException(sprintf('未定义的搜索导出：%s', $name), 3001);
        }
        $this->_scene = $name;
        $session_id   = get_active_user()->silent_get('session_id');
        if ($session_id) {
            $this->_collection = self::COLLECTION_SEARCH . $session_id;
            $this->_scene_key  = $config['es_key'] . $session_id;
        }

        return $this;
    }

    /**
     * 设置val
     */
    public function set($val)
    {
        if (!$this->_scene)
        {
            throw new \RuntimeException(sprintf('必须先设置搜索导出类型'), 500);
        }
        $session_id = get_active_user()->silent_get('session_id');
        if ($session_id)
        {
            $ttl = $this->_redis->getTTL($session_id) + 10;
            $val = str_replace(["\n", "\r\n"], ' ', addslashes($val));
            $val = str_replace(' ', $this->_blank_char, $val);

            $this->_redis->command(sprintf('hset %s %s %s', $this->_collection, $this->_scene_key, $val));
            $this->_redis->command('expire '.$this->_collection.' '.($ttl));
            return true;
        }

        return false;
    }

    /**
     *
     * @throws \RuntimeException
     * @return mixed
     */
    public function get()
    {
        if (!$this->_scene)
        {
            throw new \RuntimeException(sprintf('必须先设置搜索导出类型'), 500);
        }
        $val =  stripslashes($this->_redis->command(sprintf('hget %s %s', $this->_collection, $this->_scene_key)));
        $val = str_replace($this->_blank_char, ' ', $val);
        return $val;
    }

}