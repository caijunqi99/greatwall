<?php

namespace app\admin\controller;

use think\Lang;

/**
 * ============================================================================

 * ============================================================================
 * 控制器
 */
class Scale extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/scale.lang.php');
    }

    //比例设置
    public function scale() {
        $config_model = model('config');
        if (!request()->isPost()) {
            $list_config = rkcache('config', true);
            $this->assign('list_config', $list_config);
            /* 设置卖家当前栏目 */
            $this->setAdminCurItem('base');
            return $this->fetch();
        } else {
            $update_array['province_scale'] = input('post.province_scale');
            $update_array['city_scale'] = input('post.city_scale');
            $update_array['county_scale'] = input('post.county_scale');
            $update_array['town_scale'] = input('post.town_scale');
            $update_array['village_scale'] = input('post.village_scale');
            $update_array['release'] = intval(input('post.release'));
            $update_array['release_scale'] = input('post.release_scale');
            $update_array['withdraw'] = input('post.withdraw');
            $update_array['commission'] = input('post.commission');
            $update_array['way']=input('post.way');
            $update_array['companyway']=input('post.companyway');
            $result = $config_model->editConfig($update_array);
            if ($result) {
                $this->log(lang('ds_edit').lang('scale_set'),1);
                $this->success(lang('ds_common_save_succ'), 'Scale/scale');
            }else{
                $this->log(lang('ds_edit').lang('scale_set'),0);
            }
        }
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'base',
                'text' => lang('scale_set'),
                'url' => url('Scale/scale')
            ),
        );
        return $menu_array;
    }

}
