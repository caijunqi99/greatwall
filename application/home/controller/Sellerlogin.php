<?php

namespace app\home\controller;

use think\Lang;

/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Sellerlogin extends BaseSeller {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/sellerlogin.lang.php');
    }


    function login() {
        if (!request()->isPost()) {
            return $this->fetch($this->template_dir.'login');
        } else {

            $seller_model = model('seller');
            $seller_info = $seller_model->getSellerInfo(array('seller_name' => input('post.seller_name'),'seller_password'=>md5(input('post.member_password'))));
                if ($seller_info) {
                    // 更新卖家登陆时间
                    $seller_model->editSeller(array('last_logintime' => TIMESTAMP), array('seller_id' => $seller_info['seller_id']));

                    $sellergroup_model = model('sellergroup');
                    $seller_group_info = $sellergroup_model->getSellergroupInfo(array('sellergroup_id' => $seller_info['sellergroup_id']));

                    $store_model = model('store');
                    $store_info = $store_model->getStoreInfoByID($seller_info['store_id']);

                    $seller_model->createSellerSession($store_info,$seller_info, is_array($seller_group_info)?$seller_group_info:array());

                    $this->recordSellerlog('登录成功');
                    $this->redirect('Home/Seller/index');
                } else {
                    $this->error(lang('password_error'),'Sellerlogin/login');
                }
        }
    }

    function logout() {
        $this->recordSellerlog('注销成功');
        session(null);
        $this->redirect('Home/Sellerlogin/login');
    }

}

?>
