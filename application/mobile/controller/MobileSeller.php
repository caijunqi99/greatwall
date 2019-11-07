<?php

namespace app\mobile\controller;

class MobileSeller extends MobileHome {

    protected $seller_info = array();
    protected $seller_group_info = array();
    protected $member_info = array();
    protected $store_info = array();
    protected $store_grade = array();

    public function _initialize() {
        parent::_initialize();

        $model_mb_seller_token = Model('mb_seller_token');

        $key = input('post.key');
        if (empty($key)) {
            $key = input('get.key');
        }
        if (empty($key)) {
            output_error('请登录', array('login' => '0'));
        }

        $mb_seller_token_info = $model_mb_seller_token->getSellerTokenInfoByToken($key);
        if (empty($mb_seller_token_info)) {
            output_error('请登录', array('login' => '0'));
        }

        $model_seller = Model('seller');
        $model_member = Model('member');
        $model_store = Model('store');
        $model_seller_group = Model('seller_group');

        $this->seller_info = $model_seller->getSellerInfo(array('seller_id' => $mb_seller_token_info['seller_id']));
        $this->member_info = $model_member->getMemberInfoByID($this->seller_info['member_id']);
        $this->store_info = $model_store->getStoreInfoByID($this->seller_info['store_id']);
        $this->seller_group_info = $model_seller_group->getSellerGroupInfo(array('group_id' => $this->seller_info['seller_group_id']));

        // 店铺等级
        if (intval($this->store_info['is_own_shop']) === 1) {
            $this->store_grade = array(
                'sg_id' => '0',
                'sg_name' => '自营店铺专属等级',
                'sg_goods_limit' => '0',
                'sg_album_limit' => '0',
                'sg_space_limit' => '999999999',
                'sg_template_number' => '6',
                'sg_price' => '0.00',
                'sg_description' => '',
                'sg_function' => 'editor_multimedia',
                'sg_sort' => '0',
            );
        } else {
            $store_grade = rkcache('store_grade', true);
            $this->store_grade = $store_grade[$this->store_info['grade_id']];
        }

        if (empty($this->member_info)) {
            output_error('请登录', array('login' => '0'));
        } else {
            $this->seller_info['client_type'] = $mb_seller_token_info['client_type'];
        }
    }

}

?>
