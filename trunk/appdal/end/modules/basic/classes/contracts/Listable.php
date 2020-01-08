<?php 

/**
 * 服务接口
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason 13292
 * @since 2019-03-02
 * @link
 * @throw
 */

interface Listable
{
    
    public function get_cfg() : array;
    
    public function get_search_params() : array;
    
    public function setTableHeader(array $header_cols);
    
    public function setSelect(array $cols);
    
    public function setSearchRule(array $cols);
    
    public function setPreSearchHook($hook, $params = []);
    
    public function execSearch();
    
    public function setAfterSearchHook($hook, $params = []);
    
    public function registerService(\Serviceable $service);
    
    public function importDependent();
    
}