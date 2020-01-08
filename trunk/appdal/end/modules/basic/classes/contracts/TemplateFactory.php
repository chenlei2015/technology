<?php 

/**
 * 模板工厂，
 *
 * @package -
 * @subpackage -
 * @category -
 * @author Jason
 * @since 2019-01-03 17:05
 * @link
 * @throw 
 */
class TemplateFactory
{
    private static $s_bind_template;
    
    protected $_ci;
    
    public function __construct($params  = array())
    {
        $this->_ci =& get_instance();
    }
    
    public static final function build($template_id)
    {
        if (!TemplateFactory::$s_bind_template[$template_id] instanceof ExportTemplate)
        {
            (new self())->setTemplate($template_id);
        }
        return TemplateFactory::$s_bind_template[$template_id];
    }
    
    
    public function setTemplate($template_id, $appoint_handler = '')
    {
        $class = $appoint_handler ? : '';
        if ($class == '')
        {
            switch ($template_id)
            {
                case 'FbaSummaryExportService';
                    $class = 'fba/classes/SummaryTemplate';
                    break;
                case 'OverseaSummaryExportService';
                    $class = 'oversea/classes/SummaryTemplate';
                    break;
                case 'FbaTrackExportService';
                    $class = 'fba/classes/TrackTemplate';
                    break;
                case 'OverseaTrackExportService';
                    $class = 'oversea/classes/TrackTemplate';
                    break;
                case 'FbaExportService':
                    $class = 'fba/classes/ListTemplate';
                    break;
                case 'ExportService':
                    $class = 'oversea/classes/ListTemplate';
                    break;
                case 'PlanListExportService':
                    $class = 'plan/classes/StockTemplate';
                    break;
                case 'PlanTrackExportService':
                    $class = 'plan/classes/TrackTemplate';
                    break;
                case 'PlanSummaryExportService':
                    $class = 'plan/classes/SummaryTemplate';
                    break;
                case 'FbaConditionExportService_1':
                    $class = 'stock/classes/FbaMonthTemplate';
                    break;
                case 'FbaConditionExportService_2':
                    $class = 'stock/classes/FbaWeekTemplate';
                    break;
                case 'FbaConditionExportService_3':
                    $class = 'stock/classes/FbaDayTemplate';
                    break;
                case 'OverseaConditionExportService_1':
                    $class = 'stock/classes/OverseaMonthTemplate';
                    break;
                case 'OverseaConditionExportService_2':
                    $class = 'stock/classes/OverseaWeekTemplate';
                    break;
                case 'OverseaConditionExportService_3':
                    $class = 'stock/classes/OverseaDayTemplate';
                    break;
                case 'FbaStockCfgExportService':
                    $class = 'fba/classes/FbaStockListTemplate';
                    break;
                case 'OverseaStockCfgExportService':
                    $class = 'oversea/classes/OverseaStockListTemplate';
                    break;
                case 'FbaLogisticsExportService':
                    $class = 'fba/classes/FbaLogisticsTemplate';
                    break;
                case 'OverseaLogisticsExportService':
                    $class = 'oversea/classes/OverseaLogisticsTemplate';
                    break;
                case 'InlandExportService':
                    $class = 'inland/classes/ListTemplate';
                    break;
                case 'InlandTrackExportService':
                    $class = 'inland/classes/TrackTemplate';
                    break;
                case 'InlandSummaryExportService':
                    $class = 'inland/classes/SummaryTemplate';
                    break;
                case 'InlandSpecialExportService':
                    $class = 'inland/classes/SpecialListTemplate';
                    break;
                case 'InlandOperationExportService':
                    $class = 'inland/classes/OperationTemplate';
                    break;
                case 'InlandInventoryExportService':
                    $class = 'inland/classes/InventoryTemplate';
                    break;
                case 'InlandStockCfgExportService':
                    $class = 'inland/classes/StockCfgTemplate';
                    break;
                case 'InlandSalesReportExportService':
                    $class = 'inland/classes/InlandSalesReportTemplate';
                    break;
            }
        }
        if ($class == '')
        {
            throw new \RuntimeException(sprintf('该模板类型：%s暂无法解析', $template_id), 3001);
        }

        $class_instance = pathinfo($class)['filename'];
        $this->_ci->load->classes($class);
        TemplateFactory::$s_bind_template[$template_id] = $this->_ci->{$class_instance};
        return TemplateFactory::$s_bind_template[$template_id];
    }
    
}