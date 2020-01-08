<?php

/**
 * 响应http请求，返回json数据
 * Params:
 *      $data   Array   返回的数据
 *      $response_code int http请求状态码
 */
if (!function_exists('http_response')) {

    function http_response($data = array(), $response_code = 200)
    {

        if (isset($data['errorCode'])) {
            $ci              = &get_instance();
            $error_code_conf = $ci->config->item('error_code');
            if (!empty($error_code_conf) && isset($error_code_conf[$data['errorCode']])) {
                $data['errorMess'] = $error_code_conf[$data['errorCode']];
            }
        }

        if (extension_loaded('zlib')) {
            if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== FALSE) {
                ob_get_length() > 0 && ob_clean();
                ob_start('ob_gzhandler');
            }
        }

        header('Content-Type: application/json;charset=utf-8');

        if (function_exists('set_status_header')) {
            set_status_header($response_code);
        }

        echo json_encode($data);
        exit;

    }

}


//调试打印
if (!function_exists('pr')) {
    function pr($arr, $escape_html = true, $bg_color = '#EEEEE0', $txt_color = '#000000')
    {
        echo sprintf('<pre style="background-color: %s; color: %s;">', $bg_color, $txt_color);
        $pr_location = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];
        echo sprintf('print from %s 第%d行 <br/>', $pr_location['file'], $pr_location['line']);
        if ($arr) {
            if ($escape_html) {
                echo htmlspecialchars(print_r($arr, true));
            } else {
                print_r($arr);
            }

        } else {
            var_dump($arr);
        }
        echo '</pre>';
    }
}
//写入日志
if (!function_exists('logger')) {

    function logger($logLever = 'debug', $logTitle = '', $msg = '')
    {

        //日志内容不为空
        if (empty($msg)) return;
        $logMsg = sprintf("#%d %s: %s\r\n", getmypid(), "-----$logTitle-----", $msg);
        log_message($logLever, $logMsg);

    }

}

//将数组按键值升序排序
if (!function_exists('ascSort')) {

    function ascSort($para = '')
    {
        if (is_array($para)) {
            ksort($para);
            reset($para);
        }
        return $para;
    }

}

if (!function_exists('get')) {

    function get($key = '', $urldecode = 0)
    {

        if (!empty($key)) {
            $result = isset($_GET[$key]) ? $_GET[$key] : '';
            $urldecode && $result = urldecode($result);
            return $result;
        } else {
            return $_GET;
        }

    }

}
if (!function_exists('post')) {

    function post($key = '', $urldecode = 0)
    {
//        if(isset($_REQUEST['debug'])) return get($key,$urldecode);
        if (!empty($key)) {
            $result = isset($_POST[$key]) ? $_POST[$key] : '';
            $urldecode && $result = urldecode($result);
            return $result;
        } else {
            return $_POST;
        }

    }

}

if (!function_exists('get_client_ip')) {
    function get_client_ip()
    {

        $ip = false;

        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $ip = $_SERVER["HTTP_CLIENT_IP"];
        }

        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
            if ($ip) {
                array_unshift($ips, $ip);
                $ip = FALSE;
            }
            for ($i = 0; $i < count($ips); $i++) {
                if (!preg_match("^(10|172\.16|192\.168)\.^", $ips[$i])) {
                    $ip = $ips[$i];
                    break;
                }
            }
        }
        if (is_cli())
        {
            return '127.0.0.1';
        }

        return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);

    }
}

//构造接口请求参数，将数据转为字符串a=1&b=c&d=j
if (!function_exists('createLinkstring')) {

    function createLinkstring($para)
    {

        $arg = "";

        foreach ($para as $key => $val) {
            //if(empty($val)) continue;
            if ($val === '' || $val === null) continue;
            if (is_array($val)) $val = json_encode($val);
            $arg .= $key . "=" . urlencode($val) . "&";
        }

        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }

        return $arg;
    }

}

if (!function_exists('is_serialized')) {
    function is_serialized($data)
    {
        $data = trim($data);
        if ('N;' == $data)
            return true;
        if (!preg_match('/^([adObis]):/', $data, $badions))
            return false;
        switch ($badions[1]) {
            case 'a' :
            case 'O' :
            case 's' :
                if (preg_match("/^{$badions[1]}:[0-9]+:.*[;}]\$/s", $data))
                    return true;
                break;
            case 'b' :
            case 'i' :
            case 'd' :
                if (preg_match("/^{$badions[1]}:[0-9.E-]+;\$/", $data))
                    return true;
                break;
        }
        return false;
    }
}
/**
 * 遍历文件夹下所有文件
 * @param $dir
 * @return array|bool
 */
if (!function_exists('read_all')) {
    function read_all($dir)
    {
        if (!is_dir($dir)) return false;
        $handle    = opendir($dir);
        $temp_list = [];
        if ($handle) {
            while (($fl = readdir($handle)) !== false) {
                $temp = $dir . DIRECTORY_SEPARATOR . $fl;
                if (is_dir($temp) && $fl != '.' && $fl != '..') {
                    read_all($temp);
                } else {
                    if ($fl != '.' && $fl != '..') {
                        $temp_list[] = $fl;
                    }
                }
            }
        }
        return $temp_list;
    }
}

/**
 * 生成缩略图函数（支持图片格式：gif、jpeg、png和bmp）
 * @author roy
 * @param  string $src 源图片路径
 * @param  int $width 缩略图宽度（只指定高度时进行等比缩放）
 * @param  int $width 缩略图高度（只指定宽度时进行等比缩放）
 * @param  string $filename 保存路径（不指定时直接输出到浏览器）
 * @return bool
 */

if (!function_exists('mkThumbnail')) {
    function mkThumbnail($src, $width = null, $height = null, $filename = null)
    {
        if (!isset($width) && !isset($height))
            return false;
        if (isset($width) && $width <= 0)
            return false;
        if (isset($height) && $height <= 0)
            return false;

        $size = getimagesize($src);
        if (!$size)
            return false;

        list($src_w, $src_h, $src_type) = $size;
        $src_mime = $size['mime'];
        switch ($src_type) {
            case 1 :
                $img_type = 'gif';
                break;
            case 2 :
                $img_type = 'jpeg';
                break;
            case 3 :
                $img_type = 'png';
                break;
            case 15 :
                $img_type = 'wbmp';
                break;
            default :
                return false;
        }

        if (!isset($width))
            $width = $src_w * ($height / $src_h);
        if (!isset($height))
            $height = $src_h * ($width / $src_w);

        $imagecreatefunc = 'imagecreatefrom' . $img_type;
        $src_img         = $imagecreatefunc($src);
        $dest_img        = imagecreatetruecolor($width, $height);
        imagecopyresampled($dest_img, $src_img, 0, 0, 0, 0, $width, $height, $src_w, $src_h);
        $imagefunc = 'image' . $img_type;
        if ($filename) {
            $imagefunc($dest_img, $filename);
        } else {
            header('Content-Type: ' . $src_mime);
            $imagefunc($dest_img);
        }
//        imagedestroy($src_img);//原图销毁
//        imagedestroy($dest_img);//缩略图销毁
        return true;
    }
}

/**
 * 导出csv函数
 * @param array $data 二维数组（导出数据）
 * @param array $headlist 表格头
 * @param string $file_name 导出文件名
 */
if(!function_exists('csv_export')) {
    function csv_export(array $data = array(), array $head_list = array(), string $file_name='') {
        if(empty($file_name)){
            $file_name= date("YmdHis");
        }
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="'.$file_name.'.csv"');
        header('Cache-Control: max-age=0');

        //打开PHP文件句柄,php://output 表示直接输出到浏览器
        $fp = fopen('php://output', 'a');

        if(!empty($head_list)){
            //输出Excel列名信息
            foreach ($head_list as $key => $value) {
                //CSV的Excel支持GBK编码，一定要转换，否则乱码
                $head_list[$key] = iconv('utf-8', 'gbk', $value);
            }

            //将数据通过fputcsv写到文件句柄
            fputcsv($fp, $head_list);
        }

        //计数器
        $num = 0;

        //每隔$limit行，刷新一下输出buffer，不要太大，也不要太小
        $limit = 100000;

        //逐行取出数据，不浪费内存
        $count = count($data);
        for ($i = 0; $i < $count; $i++) {

            $num++;

            //刷新一下输出buffer，防止由于数据过多造成问题
            if ($limit == $num) {
                ob_flush();
                flush();
                $num = 0;
            }

            $row = $data[$i];
            foreach ($row as $key => $value) {
                $row[$key] = iconv('utf-8', 'gbk', $value);
            }
            fputcsv($fp, $row);
        }
    }
}

if(!function_exists('get_upload_url'))
{
    /**
     * 返回一个url路径地址
     *
     * @param unknown $file_path
     * @return string
     */
    function get_upload_url($file_path)
    {
        //return UPLOAD_URL.$file_path;
        $ci = CI::$APP;
        $ci->load->helper('url_helper');
        return trim(site_url(), '/').'/upload/'.$file_path;
    }
}

if (!function_exists('get_sgs_upload_path')) {

    /**
     * 获取sgs的上传路径
     */
    function get_sgs_upload_path()
    {
        return APPPATH. 'upload'.DIRECTORY_SEPARATOR.'sgs'.DIRECTORY_SEPARATOR;
    }
}


/**
 * 重置树形数据键名
 * @param $array
 */
if(!function_exists('reset_array_keys')) {
    function reset_array_keys(&$array)
    {
        if (!is_array($array)) return;
        foreach ($array as $k => &$v) {
            if (is_array($v)) reset_array_keys($v);
            if ($k == 'children') $v = array_values($v);
        }
    }
}

/**
 * 导出头处理
 * @param $filename
 * @param $title
 * @return bool|resource
 */
if(!function_exists('export_head')) {
    function export_head($filename, $title)
    {
        ob_clean();
        $filename = iconv("UTF-8", "GB2312", $filename);
        header("Accept-Ranges:bytes");
        header("Content-type:application/vnd.ms-excel");
        header("Content-Disposition:attachment;filename=" . $filename . ".csv");
        header("Pragma: no-cache");
        header("Expires: 0");
        $fp = fopen('php://output', 'a');
        if (!empty($title)) {
            foreach ($title as $k => $v) {
                $title[$k] = iconv("UTF-8", "GB2312//IGNORE", $v);
            }
        }
        //将标题写到标准输出中
        fputcsv($fp, $title);
        return $fp;
    }
}

/**
 *
 * UTF-8转GB2312
 */
if(!function_exists('export_head')) {
    function character($result)
    {
        $item = 1;

        foreach ($result['data_list']['value'] as $key => $value) {

            $data[$key][] = $item;
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['rule_type_cn']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sku']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['check_state_cn']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['station_code_cn']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sale_state']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['as_up']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_ship']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_train']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_air']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_blue']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['ls_red']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_ship']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_train']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_air']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_blue']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['pt_red']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['bs']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['lt']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sp']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sc']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['sz']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['created_at']);  //创建信息
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['updated_zh_name']) . ' ' . iconv("UTF-8", "GB2312//IGNORE", $value['updated_at']); //修改信息
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['approved_zh_name']) . ' ' . iconv("UTF-8", "GB2312//IGNORE", $value['approved_at']);      //审核信息
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['remark']);
            $data[$key][] = iconv("UTF-8", "GB2312//IGNORE", $value['gid']);
            $item++;
        }
    }
}


/**
 * 导出内容处理
 * @param $fp
 * @param $data
 */
if(!function_exists('export_content')) {
    function export_content($fp, $data)
    {
        if (!empty($data)) {
            foreach ($data as $key => $val) {
                fputcsv($fp, $val);
            }
        }
    }
}

/**
 * 导入csv数据处理
 * @param $handle
 * @return array
 */
if(!function_exists('input_csv')) {
    function input_csv($handle)
    {
        $out = array();
        $n = 0;
        while ($data = fgetcsv($handle, 10000)) {
            $num = count($data);
            for ($i = 0; $i < $num; $i++) {
                $out[$n][$i] = $data[$i];
            }
            $n++;
        }
        return $out;
    }
}

/**
 * 导出csv文件
 * @param $file_path  临时文件路径
 * @param $name       导出文件名
 */
if(!function_exists('export_csv')) {
    function export_csv($file_path,$name)
    {
        if(ob_get_length() !== false) @ob_end_clean();
        $fp = fopen($file_path,"r");
        $file_size = filesize($file_path);
        $file_name = $name.'-'. date('YmdHis').'.csv';
        //下载文件需要用到的头
        Header("Content-type: application/octet-stream");
        Header("Accept-Ranges: bytes");
        Header("Accept-Length:".$file_size);
        Header("Content-Disposition: attachment; filename=".$file_name);
        $buffer = 1024;
        $file_count=0;
        while(!feof($fp) && $file_count<$file_size)
        {
            $file_con=fread($fp,$buffer);
            $file_count+=$buffer;
            echo $file_con;
        }
        fclose($fp);
        exit();
    }
}

if (!function_exists('RPC_CALL')) {
    /**
     * java远程调用
     *
     * @param unknown $api_code
     * @return string
     */
    function RPC_CALL($api_code, $input_params = [], callable $after_cb = null,  $ctrl_params = [])
    {
        $ci = CI::$APP;
        $ci->load->library('JavaApiExchangeService', null, 'rpc');
        return $ci->rpc->options($ctrl_params)->set($api_code)->after($after_cb)->run($input_params);
    }
}

if (!function_exists('time_empty')) {
    /**
     * 判断时间是否为空
     *
     * @param string $time
     * @return bool
     */
    function time_empty($time='')
    {
        if (is_numeric($time)){
            $time = intval($time);
        }else{
            $time = str_replace(['-',':',' '],'',$time);
            $time = intval($time);
        }
        return (empty($time) || $time=='0')?true:false;
    }
}

if (!function_exists('data_format_filter')) {
    /**
     * 过滤掉不符合规则的时间，显示空字符串
     *
     * @param array $row
     * @param array $fields
     */
    function data_format_filter(&$row,$fields = ['created_at','updated_at','approved_at','approve_at','check_attr_time'])
    {
        static  $data_format_str = 'Y-m-d H:i:s';

        if (is_array($row)){
            if (!empty($row) && !empty($fields)){
                foreach ($fields as $field){
                    if (isset($row[$field])){
                        if (!time_empty($row[$field])){
                            $row[$field] = is_numeric($row[$field]) ? date($data_format_str, intval($row[$field])) : $row[$field];
                        }else{
                            $row[$field] = '';
                        }
                    }
                }
            }
        }elseif (is_string($row)){
            if (!time_empty($row)){
                $row = is_numeric($row) ? date($data_format_str, intval($row)) : $row;
            }else{
                $row = '';
            }
            return $row;
        }
    }
}


if (!function_exists('tran_time_result')) {

    /**
     * 转换list的数据格式
     */
    function tran_time_result(&$data_list)
    {
        if (empty($data_list))
        {
            return [];
        }
        foreach ($data_list as $key => &$row)
        {
            data_format_filter($row);
        }
    }
}


/**
 * 消除所有特殊情况
 */
if (!function_exists('eliminate')) {
    function eliminate($str)
    {
        $str = str_replace(array("\r\n", "\r", "\n"), "", $str);

        return $str;

    }
}


/**
 * EXCEL上传获取内容
 * @param uploadExcel array 上传的文件 $_FILES['key']
 * @param line int 从那一行开始读取默认1
 * @param columnKey array 要读取的列，默认读取全部
 */
function getExcelDetail($uploadExcel,$line = 1,$columnKey = [],$set_title=0){
    require_once APPPATH . "third_party/PHPExcel.php";
    $file_name = $uploadExcel['tmp_name'];
    $tmp_array = explode('.', $uploadExcel['name']);
    $extend = array_pop($tmp_array);
    if ($extend == 'xls') {
        $objReader = new PHPExcel_Reader_Excel5();
    } else {
        $objReader = new PHPExcel_Reader_Excel2007();
    }
    $objPHPExcel = $objReader->load($file_name);
    $sheet = $objPHPExcel->getSheet(0);
    $rows = $sheet->getHighestRow();
    $cols = $sheet->getHighestColumn();
    if(empty($columnKey)){
        $columnKey = getExcelTitleData($cols);
    }
    if(empty($columnKey)){
        return ['status' => 0,'msg' => 'get excel column key fail.'];
    }
    if($rows <= 0){
        return ['status' => 0,'msg' => 'excel rows is '.(int)$rows.'.'];
    }
    $excelData = array();
    if($set_title == 1){
        while($line <= $rows){
            $data = [];
            foreach($columnKey as $key => $value){
                $data[$value] = trim($sheet->getCell($key . $line)->getValue());
            }
            $excelData[$line] = $data;
            $line ++;
        }
    }
    while($line <= $rows){
        $data = [];
        foreach($columnKey as $key){
            $data[$key] = trim($sheet->getCell($key . $line)->getValue());
        }
        $excelData[$line] = $data;
        $line ++;
    }
    return $excelData;
}

function getExcelTitleData($cols){
    $columnKey = [];
    $titleData = ['A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z'];
    for($i=0; $i<=26; $i++){
        foreach($titleData as $title){
            if($i > 0){
                $title = $titleData[$i-1].$title;
            }
            $columnKey[] = $title;
            if($cols == $title){ return $columnKey; }
        }
    }
    return $columnKey;
}


/**
* 判断正整数和正数
* @param $num
* @param int $case 1: 正整数（包含0）;2:大于0的数;3：正整数（不包含0）; 4:正小数 ; 5: 0>x>=1 ;6: 正数和0
*/
function positiveInteger(& $num, $case = 1){
    $res = false;
    $num = trim($num);
    if((($case == 1) && $num == 0 ) || $num ) {
        if(is_numeric($num)){
            switch ($case) {
                case 1:
                    if ($num >= 0 && floor($num) == $num) {
                        $res = true;
                    }
                    break;
                case 2:
                    if ($num > 0) {
                        $res = true;
                    }
                    break;
                case 3:
                    if ($num >= 0 && floor($num) == $num) {
                        $res = true;
                    }
                    break;
                case 4:
                    if($num >0 && floor($num)!=$num){
                        $res = true;
                    }
                    break;
                case 5:
                    if($num >0 && $num<=1){
                        $res = true;
                    }
                    break;
            }
            return $res;
        }else{
            return false;
        }
    }else{
        return false;
    }
}

function yieldBigFile($file)
{
    if (!file_exists($file)) {
        throw new \Exception(sprintf('文件%s不存在', $file), 500);
    }
    $fhandle = fopen($file, 'r');
    if (!$fhandle) {
        throw new \Exception(sprintf('文件%s打开失败', $file), 500);
    }

    try {
        while ($line = fgets($fhandle)) {
            yield $line;
        }
    } finally {
        fclose($fhandle);
    }
}

/**
 * 处理导入的列
 */
function validate_title($title_str='',$modify_item)
{
    $data['status'] = 1;
    $char = detect_encoding($title_str);
    if (empty($char)){
        $data['status'] = 0;
        $data['errorMess'] = '文件编码异常！';
        return $data;
    }
    //判断标题是否有以上修改项
    $title =  iconv($char, 'utf-8//IGNORE', trim(strip_tags($title_str)));//转码
    //因导出的格式为utf-8进行了处理,所有数据都带有双引号, 修改后的数据被软件自动去除
    if(substr($title,0,1)=='"'){
        $data['status'] = 0;
        $data['errorMess'] = '无法导入未修改文件！';
        return $data;
    }
    $title = explode(',',$title);
    $notice_info = [];
    foreach ($modify_item as $key =>  $item){
        if(($index = array_search($item,$title))!==false){
            $final_list[$key] = $index; //$key是字段名, $index是列标题的下标 [rule_type] => 0
        }else{
            $notice_info[] = $item;
        }
    }

    //判断标题是否有以上修改项
    if(!empty($notice_info)){
        $data['errorMess'] = '上传的文件必须包含:';
        foreach ($notice_info as $item){
            $data['status'] = 0;
            $data['errorMess'] .= sprintf('%s标题列%s',str_replace('"','',$item),PHP_EOL);
        }
        return $data;
    }

    if($data['status'] == 1){
        $data['final_list'] = $final_list;
        $data['char'] = $char;
        return $data;
    }


}
function detect_encoding($str) {
    $list = array('GBK', 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1');
    foreach ($list as $item) {
        $tmp = mb_convert_encoding($str, $item, $item);
        if (md5($tmp) == md5($str)) {
            return $item;
        }
    }
    return null;
}

if (!function_exists('tran_country_code')) {

    function tran_country_code($country_name)
    {
        static $country_name_map_code;

        if (!$country_name_map_code)
        {
            foreach (FBA_STATION_CODE as $code => $cfg)
            {
                if (isset($cfg['is_eu']) && $cfg['is_eu'])
                {
                    $country_name_map_code[$cfg['name']] = $code;
                }
            }
        }
        return $country_name_map_code[$country_name] ?? '';
    }
}

/**
 * 获取最近几天所有日期
 */
if (!function_exists('get_last_day')) {
    function get_last_day($time = '', $format='Y-m-d',$num = 7){
        $time = $time != '' ? $time : time();
        //组合数据
        $date = [];
        for ($i=1; $i<=$num; $i++){
            $date[$i] = date($format ,strtotime( '+' . $i-$num .' days', $time));
        }
        return $date;
    }
}

if (!function_exists('get_export_path')) {

    /**
     * 获取上传统一路径，避免因为upload文件夹修改而修改
     */
    function get_export_path()
    {
        return rtrim(EXPORT_FILE_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR ;
    }
}

if (!function_exists('import_error_report')) {
    function import_error_report($err_info,$import_file_name= '批量导入')
    {
        $data = [
            'error_file' =>'',
            'error_msg' =>''
        ];
        if (empty($err_info['errorLines'])) {
            $data['error_msg'] = '错误行数为空';
            return $data;
        }
        $errorLines = array_unique($err_info['errorLines']);
        sort($errorLines);
        $dir_path = get_export_path() . date('Ymd') . DIRECTORY_SEPARATOR;
        is_dir($dir_path) or mkdir($dir_path,0700);
        $file_name = $import_file_name.'_'.date('Hi').'_错误文件.csv';
        $file_path = $dir_path . $file_name;
        $handle = fopen($file_path, 'w+');
        if (!$handle) {
            $data['error_msg'] = '没有文件写入权限';
            return $data;
        }
        fputcsv($handle, ['错误行数']);

        $current = 2;
        foreach ($errorLines as $line) {
            while ($line > $current) {
                fputcsv($handle, [' ']);
                $current++;
            }
            if ($current == $line) {
                fputcsv($handle, [$line]);
                $current++;
            }
        }
        fclose($handle);
        $reletive_path = date('Ymd') . DIRECTORY_SEPARATOR.$file_name;
        $data['error_file'] = EXPORT_DOWNLOAD_URL . '/' . $reletive_path;
        return $data;
    }
}

