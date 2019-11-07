<?php

namespace app\mobile\controller;


class Sellerlogout extends MobileSeller
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 注销
     */
    public function index()
    {
        if (empty($_POST['seller_name']) || !in_array($_POST['client'], $this->client_type_array)) {
            output_error('参数错误');
        }

        $model_mb_seller_token = Model('mb_seller_token');

        if ($this->seller_info['seller_name'] == $_POST['seller_name']) {
            $condition = array();
            $condition['seller_id'] = $this->seller_info['seller_id'];
            $model_mb_seller_token->delSellerToken($condition);
            output_data('1');
        }
        else {
            output_error('参数错误');
        }
    }
}