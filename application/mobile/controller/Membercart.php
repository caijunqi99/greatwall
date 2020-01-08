<?php

namespace app\mobile\controller;

use think\Lang;

class Membercart extends MobileMember {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/cart.lang.php');
    }

    /**
     * 购物车列表
     */
    public function cart_list() {
        $model_cart = Model('cart');

        $condition = array('buyer_id' => $this->member_info['member_id']);
        $cart_list = $model_cart->getCartList('db', $condition);

        // 购物车列表 [得到最新商品属性及促销信息]
        $cart_list = model('buy_1','logic')->getGoodsCartList($cart_list);

        $model_goods = Model('goods');

        $sum = 0;
        $cart_a = array();
        $k=0;
        foreach ($cart_list as $key => $val) {
            $cart_a[$val['store_id']]['store_id'] = $val['store_id'];
            $cart_a[$val['store_id']]['store_name'] = $val['store_name'];
            $goods_data = $model_goods->getGoodsOnlineInfoForShare($val['goods_id']);
            $cart_a[$val['store_id']]['goods'][$key] = $goods_data;

            $cart_a[$val['store_id']]['goods'][$key]['cart_id'] = $val['cart_id'];
            $cart_a[$val['store_id']]['goods'][$key]['goods_num'] = $val['goods_num'];
            $cart_a[$val['store_id']]['goods'][$key]['goods_image_url'] = goods_cthumb($val['goods_image'], $val['store_id']);
            if (isset($goods_data['goods_spec'])&&$goods_data['goods_spec'] == 'N;') {
                $cart_a[$val['store_id']]['goods'][$key]['goods_spec'] = '';
            }
            if (isset($goods_data['goods_promotion_type'])) {
                $cart_a[$val['store_id']]['goods'][$key]['goods_price'] = $goods_data['goods_promotion_price'];
            }
            $cart_a[$val['store_id']]['goods'][$key]['gift_list'] = isset($val['gift_list'])?$val['gift_list']:'';
            $cart_list[$key]['goods_sum'] = ds_price_format($val['goods_price'] * $val['goods_num']);
            $sum += $cart_list[$key]['goods_sum'];
            $k++;
        }
        $cart_l = [];
        foreach ($cart_a as $key=>$value){
           $value['goods']=array_values($value['goods']);
           $cart_l[]=$value;
        }

        $cart_b=array_values($cart_l);

        // output_data(array('cart_list' => $cart_a, 'sum' => ds_price_format($sum), 'cart_count' => count($cart_list),'cart_val'=>$cart_b));
        output_data(array('sum' => ds_price_format($sum), 'cart_count' => count($cart_list),'cart_list'=>$cart_b));
    }

    /**
     * 购物车添加
     */
    public function cart_add() {
        $goods_model = model('goods');
        $logic_buy_1 =  model('buy_1','logic');
        $goods_id = intval(input('param.goods_id'));
        $quantity = intval(input('param.quantity'));
        $bl_id = intval(input('param.bl_id'));
        if (is_numeric($goods_id) && $goods_id>0) {
            //商品加入购物车(默认)
            if ($goods_id <= 0)
                return;
            $goods_info = $goods_model->getGoodsOnlineInfoAndPromotionById($goods_id);
            //抢购
            $logic_buy_1->getGroupbuyInfo($goods_info, $quantity);

            //限时折扣
            $logic_buy_1->getXianshiInfo($goods_info, $quantity);

            //得到会员等级
            $model_member = Model('member');
            $member_info = $model_member->getMemberInfoByID(session('member_id'));

            if ($member_info) {
                $member_gradeinfo = $model_member->getOneMemberGrade(intval($member_info['member_exppoints']));
                //$member_discount = $member_gradeinfo['orderdiscount'];
                $member_level = $member_gradeinfo['level'];
            }
            else {
                $member_level = 0;
            }
            //会员等级折扣
            $logic_buy_1->getMgdiscountInfo($goods_info);

            $this->_check_goods($goods_info, $quantity);
        } elseif (is_numeric($bl_id)&& $bl_id>0 ) {
            //优惠套装加入购物车(单套)
            if (!$this->member_info['member_id']) {
                exit(json_encode(array('msg' => lang('please_login_first'), 'UTF-8')));
            }
            if ($bl_id <= 0)
                return;
            $pbundling_model = model('pbundling');
            $bl_info = $pbundling_model->getBundlingInfo(array('bl_id' => $bl_id));
            if (empty($bl_info) || $bl_info['bl_state'] == '0') {
                output_error(lang('recommendations_buy_separately'));
            }

            //检查每个商品是否符合条件,并重新计算套装总价
            $bl_goods_list = $pbundling_model->getBundlingGoodsList(array('bl_id' => $bl_id));
            $goods_id_array = array();
            $bl_amount = 0;
            foreach ($bl_goods_list as $goods) {
                $goods_id_array[] = $goods['goods_id'];
                $bl_amount += $goods['blgoods_price'];
            }
            $goods_model = model('goods');
            $goods_list = $goods_model->getGoodsOnlineListAndPromotionByIdArray($goods_id_array);
            foreach ($goods_list as $goods) {
                $this->_check_goods($goods, 1);
            }

            //优惠套装作为一条记录插入购物车，图片取套装内的第一个商品图
            $goods_info = array();
            
            $goods_info['store_id'] = $bl_info['store_id'];
            $goods_info['goods_id'] = $goods_list[0]['goods_id'];
            $goods_info['goods_name'] = $bl_info['bl_name'];
            $goods_info['goods_price'] = $bl_amount;
            $goods_info['goods_num'] = 1;
            $goods_info['goods_image'] = $goods_list[0]['goods_image'];
            $goods_info['store_name'] = $bl_info['store_name'];
            $goods_info['bl_id'] = $bl_id;
            $quantity = 1;
        }
        $save_type = 'db';
        $goods_info['buyer_id'] = $this->member_info['member_id'];
        $cart_model = model('cart');
        $insert = $cart_model->addCart($goods_info, $save_type, $quantity);
        if ($insert) {
            $data = array('state' => 'true', 'num' => $cart_model->cart_goods_num, 'amount' => ds_price_format($cart_model->cart_all_price));
        } else {
            $data = array('state' => 'false','message'=>$cart_model->error_message);
        }
        output_data($data);

    }

    /**
     * 购物车删除
     */
    public function cart_del() {
        $cart_id = intval(abs(input('param.cart_id')));

        $model_cart = Model('cart');

        if ($cart_id > 0) {
            $condition = array();
            $condition['buyer_id'] = $this->member_info['member_id'];
            $condition['cart_id'] = $cart_id;
            $result = $model_cart->delCart('db', $condition);
            output_data(['state'=>true]);
        }else{
            output_error('未获取到该条购物车信息！');
        }

    }

    /**
     * 更新购物车购买数量
     */
    public function cart_edit_quantity() {
        $cart_id = intval(abs(input('param.cart_id')));
        $quantity = intval(abs(input('param.quantity')));

        if (empty($cart_id) || empty($quantity)) {
            output_error(lang('cart_update_buy_fail'));
        }
        $cart_model = model('cart');
        $goods_model = model('goods');
        $logic_buy_1 =  model('buy_1','logic');

        //存放返回信息
        $return = array();

        $cart_info = $cart_model->getCartInfo(array('cart_id' => $cart_id, 'buyer_id' => $this->member_info['member_id']));
        if ($cart_info['bl_id'] == '0') {

            //普通商品
            $goods_id = intval($cart_info['goods_id']);
            $goods_info = $logic_buy_1->getGoodsOnlineInfo($goods_id, $quantity);
            if (empty($goods_info)) {
                $return['state'] = 'invalid';
                $return['msg'] = lang('merchandise_off_shelves');
                $return['subtotal'] = 0;
                \mall\queue\QueueClient::push('delCart', array('buyer_id' => $this->member_info['member_id'], 'cart_ids' => array($cart_id)));
                output_error(lang('merchandise_off_shelves'));
            }
            
//            //抢购
//            $logic_buy_1->getGroupbuyInfo($goods_info, $quantity);
//            //限时折扣
//            $logic_buy_1->getXianshiInfo($goods_info, $quantity);
            
            $quantity = $goods_info['goods_num'];

            if (intval($goods_info['goods_storage']) < $quantity) {
                $return['state'] = 'shortage';
                $return['msg'] = lang('cart_add_too_much');
                $return['goods_num'] = $goods_info['goods_num'];
                $return['goods_price'] = $goods_info['goods_price'];
                $return['subtotal'] = $goods_info['goods_price'] * $quantity;
                $cart_model->editCart(array('goods_num' => $goods_info['goods_storage']), array('cart_id' => $cart_id, 'buyer_id' => $this->member_info['member_id']));
                output_data($return);
                // exit(json_encode($return));
            }
        } else {

            //优惠套装商品
            $pbundling_model = model('pbundling');
            $bl_goods_list = $pbundling_model->getBundlingGoodsList(array('bl_id' => $cart_info['bl_id']));
            $goods_id_array = array();
            foreach ($bl_goods_list as $goods) {
                $goods_id_array[] = $goods['goods_id'];
            }
            $goods_list = $goods_model->getGoodsOnlineListAndPromotionByIdArray($goods_id_array);

            //如果其中有商品下架，删除
            if (count($goods_list) != count($goods_id_array)) {
                $return['state'] = 'invalid';
                $return['msg'] = lang('wheatsuit_no_longer_valid');
                $return['subtotal'] = 0;
                \mall\queue\QueueClient::push('delCart', array('buyer_id' => $this->member_info['member_id'], 'cart_ids' => array($cart_id)));
                output_error(lang('wheatsuit_no_longer_valid'));
            }

            //如果有商品库存不足，更新购买数量到目前最大库存
            foreach ($goods_list as $goods_info) {
                if ($quantity > $goods_info['goods_storage']) {
                    $return['state'] = 'shortage';
                    $return['msg'] = lang('preferential_suit_understock');
                    $return['goods_num'] = $goods_info['goods_storage'];
                    $return['goods_price'] = $cart_info['goods_price'];
                    $return['total_price'] = $cart_info['goods_price'] * $quantity;
                    $cart_model->editCart(array('goods_num' => $goods_info['goods_storage']), array('cart_id' => $cart_id, 'buyer_id' => $this->member_info['member_id']));
                    output_data($return);
                    break;
                }
            }
            $goods_info['goods_price'] = $cart_info['goods_price'];
        }

        $data = array();
        $data['goods_num'] = $quantity;
        $data['goods_price'] = $goods_info['goods_price'];
        $update = $cart_model->editCart($data, array('cart_id' => $cart_id, 'buyer_id' => $this->member_info['member_id']));
        $return = array();
        $return['quantity'] = $quantity;
        $return['update'] = $update;
        output_data($return);
        if ($update) {
            $return = array();
            $return['quantity'] = $quantity;
            $return['goods_price'] = ds_price_format($goods_info['goods_price']);
            $return['total_price'] = ds_price_format($goods_info['goods_price'] * $quantity);
            output_data($return);
        } else {
            output_error(lang('cart_update_buy_fail'));
        }
    }

    /**
     * 检查库存是否充足
     */
    private function _check_goods_storage(& $cart_info, $quantity, $member_id) {
        $model_goods = Model('goods');
        $model_bl = Model('pbundling');
        $logic_buy_1 = Model('buy_1','logic');

        if ($cart_info['bl_id'] == '0') {
            //普通商品
            $goods_info = $model_goods->getGoodsOnlineInfoAndPromotionById($cart_info['goods_id']);

            //团购
            $logic_buy_1->getGroupbuyInfo($goods_info);
            if (isset($goods_info['ifgroupbuy'])) {
                if ($goods_info['upper_limit'] && $quantity > $goods_info['upper_limit']) {
                    return false;
                }
            }

            //限时折扣
            $logic_buy_1->getXianshiInfo($goods_info, $quantity);
            if (intval($goods_info['goods_storage']) < $quantity) {
                return false;
            }
            $goods_info['cart_id'] = $cart_info['cart_id'];
            $goods_info['buyer_id'] = $cart_info['buyer_id'];
            $cart_info = $goods_info;
        } else {
            //优惠套装商品
            $bl_goods_list = $model_bl->getBundlingGoodsList(array('bl_id' => $cart_info['bl_id']));
            $goods_id_array = array();
            foreach ($bl_goods_list as $goods) {
                $goods_id_array[] = $goods['goods_id'];
            }
            $bl_goods_list = $model_goods->getGoodsOnlineListAndPromotionByIdArray($goods_id_array);

            //如果有商品库存不足，更新购买数量到目前最大库存
            foreach ($bl_goods_list as $goods_info) {
                if (intval($goods_info['goods_storage']) < $quantity) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * 检查购物车数量
     */
    public function cart_count() {
        $model_cart = Model('cart');
        $count = $model_cart->getCartCountByMemberId($this->member_info['member_id']);
        $data['cart_count'] = $count;
        output_data($data);
    }


    /**
     * 检查商品是否符合加入购物车条件
     * @param unknown $goods
     * @param number $quantity
     */
    private function _check_goods($goods_info, $quantity) {
        if (empty($quantity)) {
            output_error(lang('param_error'));
        }
        if (empty($goods_info)) {
            output_error(lang('cart_add_goods_not_exists'));
        }
        if ($goods_info['store_id'] == $this->member_info['store_id']) {
            output_error(lang('cart_add_cannot_buy'));
        }
        if (intval($goods_info['goods_storage']) < 1) {
            output_error(lang('cart_add_stock_shortage'));
        }
        if (intval($goods_info['goods_storage']) < $quantity) {
            output_error(lang('cart_add_too_much'));
        }

        if ($goods_info['is_virtual'] || $goods_info['is_goodsfcode'] || $goods_info['is_presell']) {
            output_error(lang('please_purchase_directly'));
        }

    }

}

?>
