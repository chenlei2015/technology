<?php
/**
 * Created by PhpStorm.
 * User: Manson
 * Date: 2019/4/2
 * Time: 16:59
 */
class UsercfgProfileService
{
    private $_ci;

    public function __construct()
    {
        $this->_ci =& get_instance();
        $this->_ci->load->model('User_profile_model', 'm_profile', false, 'basic');
    }

    public function cfg_info($params)
    {
        $data =  $this->_ci->m_profile->get($params);
        if(isset($data['config'])&&!empty($data['config'])){
            $data['config'] = unserialize($data['config']);
        }else{
            $data['config'] = [];
        }
        if(isset($data['config_index'])&&!empty($data['config_index'])){
            $data['config_index'] = unserialize($data['config_index']);
        }else{
            $data['config_index'] = [];
        }
        if(isset($data['field'])&&!empty($data['field'])){
            $data['field'] = unserialize($data['field']);
        }else{
            $data['field'] = [];
        }
        return $data;
    }

    public function cfg($params)
    {
        if (!isset($params['config']))
        {
            throw new \InvalidArgumentException(sprintf('未定义参数config'));
        }
        $params['field'] = serialize($params['field']);
        $params['config'] = serialize($params['config']);
        $params['updated_at'] = date('Y-m-d H:i:s');
        $this->_ci->m_profile->modify($params);
    }

    public function get_display_cfg($collection){
        $this->_ci->lang->load('common');
        $active_user = get_active_user();
        $data = ['collection'=>$collection,'staff_code'=>$active_user->staff_code];
        $result = $this->_ci->m_profile->getConfig($data);

        if(isset($result['config'])&&!empty($result['config'])){
            $result['config'] = unserialize($result['config']);
        }else{//直接给编辑显示的默认配置(选择的内容也就是key=>中文)
            $default = [];
            $cfg_info = $this->_ci->lang->myline($collection);

            if (empty($cfg_info)){//没有编辑显示的情况, 与前端约定好返回null, 走key
                $result['config'] = NULL;
            }else{
                foreach ($cfg_info as $key => $item){
                    $default[$item['key']] = $item['label'];
                }
                $result['config'] = $default;
            }
        }

        if(isset($result['field'])&&!empty($result['field'])){
            $result['field'] = unserialize($result['field']);
        }else{
            $result['field'] = NULL;
        }
        return $result;
    }


    /**
     *  导出功能获取模板
     *
     * @param string $collection 列表页
     * @param array  $no_allow   需要过滤的字段(field)
     * @param array  $special_field 处理带有修改信息的字段
     * @param array  $no_allow_label 需要过滤的字段(label)
     * @param array  $sign_field    导入的id标记 放在最后一列
     */
    public function export_temple($collection = '', $no_allow_field = [], $special_field = [], $no_allow_label = [], $sign_field = [])
    {
        $this->_ci->lang->load('common');
        $cfg_info = $this->_ci->lang->myline($collection);
        //不需要导出的字段
        if (empty($no_allow_field)){
            $no_allow_field = ['','修改信息','审核信息','创建信息'];
        }
        if (empty($no_allow_label)){
            $no_allow_label = ['修改信息','审核信息','创建信息'];
        }
        if (empty($special_field)) {
            $special_field = [
                '修改人'  =>
                    [
                        'col'   => 'updated_uid',
                        'width' => 18,
                    ],
                '修改时间' =>
                    [
                        'col'   => 'updated_at',
                        'width' => 18,
                    ],
                '审核人'  =>
                    [
                        'col'   => 'approved_uid',
                        'width' => 18,
                    ],
                '审核时间' =>
                    [
                        'col'   => 'approved_at',
                        'width' => 18,
                    ],
            ];
        }
        /*        if(empty($sign_field) && $need_sign===true){
                    $sign_field = [
                        '请勿改动此标记' =>
                            [
                                'col' => 'gid',
                            ]
                    ];
                }*/

        $export_field = [];
        $i=0;
        if (!empty($cfg_info)){
            foreach ($cfg_info as $key => $item){
                if (in_array($item['field'], $no_allow_field)) {//跳过 例如:修改信息审核信息,这种字段
                    continue;
                }
                if (in_array($item['label'], $no_allow_label)) {//合并一次 自定义的例如:修改人,修改时间
                    if($i == 0){
                        $export_field = array_merge($export_field,$special_field);
                        $i = 1;
                    }
                    continue;
                }

                $export_field[$item['label']] =  [
                    'col' => $item['field']??'',
                    'width'=> $item['width']??18,
                ];
            }
            if(!empty($sign_field)){
                $export_field = array_merge($export_field,$sign_field);
            }
            return $export_field;
        }
    }
}