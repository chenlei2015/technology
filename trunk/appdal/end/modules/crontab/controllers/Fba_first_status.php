<?php

/**
 * Created by PhpStorm.
 * User: lewei
 * Date: 2019/10/30
 * Time: 13:41
 */
class Fba_first_status extends MY_Controller
{
    protected $stock;  //计划系统数据库
    protected $process; //进程数

    public function __construct()
    {
        parent::__construct();
        $this->stock   = $this->load->database('stock', TRUE);//计划系统数据库
    }

    /**
     * 检测所有数据是否是首发状态
     */
    public function first_status(){
        echo "---------start-------".PHP_EOL;
        ini_set('memory_limit', '3072M');
        ini_set('max_execution_time', '0');
        set_time_limit(0);
        try{
            $total = $this->stock->select('*')->from('yibai_fba_logistics_list')->count_all_results();
            if ($total == 0){
                log_message('error','获取数据失败');
                throw new Exception("获取数据失败");
            }

            $first_sql = "SELECT
	                        list.id 
                FROM
	                yibai_fba_logistics_list AS list
	            LEFT JOIN yibai_fba_new_cfg AS cfg ON (
		            md5(
			            CONCAT(cfg.account_name,LOWER(cfg.site),cfg.seller_sku,cfg.erp_sku,cfg.fnsku,cfg.asin) 
		                ) 
			        = 
		            md5(
		                CONCAT(list.account_name,list.site,list.seller_sku,list.sku,list.fnsku,list.asin) 
		                ) 
	                ) 
	            where cfg.is_delete = 0";

            $first_not_sql = "SELECT
	                            list.id 
                        FROM
	                            yibai_fba_logistics_list AS list
	                    LEFT JOIN yibai_fba_new_cfg AS cfg ON (
		                        md5(
			                        CONCAT(cfg.account_name,LOWER(cfg.site),cfg.seller_sku,cfg.erp_sku,cfg.fnsku,cfg.asin) 
		                            ) 
			                    = 
		                        md5(
		                            CONCAT(list.account_name,list.site,list.seller_sku,list.sku,list.fnsku,list.asin) 
		                            ) 
	                            ) 
	                    where (cfg.is_delete is NULL or cfg.is_delete = 1)";

            //全部转首发
            $first_result_data = $this->stock->query($first_sql)->result_array();
            $first_not_result_data = $this->stock->query($first_not_sql)->result_array();
            $ids_first = array_column($first_result_data,'id');
            $ids_first = implode("','",$ids_first);
            $ids_not_first = array_column($first_not_result_data,'id');
            $ids_not_first = implode("','",$ids_not_first);

            $this->stock->trans_start();
            //更新首发
            $update_first_sql = "update yibai_fba_logistics_list set is_first_sale = 1 where id in ('".$ids_first."')";
            $updae_first = $this->stock->query($update_first_sql);
            //更新非首发
            $update_first_not_sql = "update yibai_fba_logistics_list set is_first_sale = 2 where id in ('".$ids_not_first."')";
            $updae_first_not = $this->stock->query($update_first_not_sql);
            if (!$updae_first || !$updae_first_not){
                $this->stock->trans_rollback();
                log_message('error','更新检测数据失败');
                die("更新检测失败");
                echo "------end------";
                exit;
            }
            $this->stock->trans_complete();
            echo "------end------";
        }catch (\Throwable $e){
            die($e->getMessage());
        }

    }
}