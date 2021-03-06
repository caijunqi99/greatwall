<?php
namespace app\admin\controller;

use think\Lang;
/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Membergrade extends AdminControl
{
    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/membergrade.lang.php');
    }
    public function index() {
        if (request()->isPost()) {
            $update_arr = array();
            if (!empty(input('post.mg/a'))) {
                $mg_arr = array();
                $i = 1;
                $max_exppoints = '-1';#用户判断 下级会员等级积分应大于上级等级积分
                foreach (input('post.mg/a') as $k => $v) {
                    $mg_arr[$i]['level'] = $i;
                    $level_name = $v['level_name'];
                    $exppoints  = intval($v['exppoints']);
                    $exppointone=$v['exppointone'];
                    $exppointtwo=$v['exppointtwo'];
                    if(empty($level_name)){
                        $this->error(lang('param_error'));
                    }
                    $mg_arr[$i]['level_name'] = $level_name;
                    $mg_arr[$i]['exppointone']=$exppointone;
                    $mg_arr[$i]['exppointtwo']=$exppointtwo;
                    //所需经验值
                    if($max_exppoints>=$exppoints){
                        $this->error($level_name.lang('exppoints_greater_than').$max_exppoints);
                    }else{
                        $mg_arr[$i]['exppoints'] = $exppoints;
                    }
                    $max_exppoints = $exppoints;
                    $i++;
                }
                $update_arr['member_grade'] = serialize($mg_arr);
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
            $result = true;
            if ($update_arr) {
                $config_model = model('config');
                $result = $config_model->editConfig($update_arr);
            }
            if ($result) {
                $this->log(lang('ds_edit') . lang('ds_member_grade'), 1);
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->log(lang('ds_edit') . lang('ds_member_grade'), 0);
                $this->error(lang('ds_common_save_fail'));
            }
        } else {
            $list_config = rkcache('config', true);
            $membergrade_list = $list_config['member_grade'] ? unserialize($list_config['member_grade']) : array();
            //print_r($membergrade_list);exit;
            $this->assign('membergrade_list', $membergrade_list);
            $this->setAdminCurItem('index');
            return $this->fetch();
        }
    }
    
    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_manage'),
                'url' => url('Membergrade/index')
            )
        );
        return $menu_array;
    }
}