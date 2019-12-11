<?php

/**
 * 公共用户可以访问的类(不需要登录)
 */

namespace app\home\controller;
use think\Lang;

/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class BaseMall extends BaseHome {

    public function _initialize() {
        parent::_initialize();
        if (!strstr(strtolower(request()->controller()) , 'seller') || strstr(strtolower(request()->controller()), 'member')) {
            //只开启商户端
            // $this->redirect('Home/Seller/index');

        }
        
        $this->template_dir = 'default/mall/'.  strtolower(request()->controller()).'/';
    }
}

?>
