<?php

namespace app\mobile\controller;

use think\Lang;

class Membercart extends MobileMember {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\membercart.lang.php');
    }

    /**
     * 购物车列表
     */
    public function cart_list() {
        $model_cart = Model('cart');

        $condition = array('buyer_id' => $this->member_info['member_id']);
        $cart_list = $model_cart->listCart('db', $condition);

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
            $cart_list[$key]['goods_sum'] = dsPriceFormat($val['goods_price'] * $val['goods_num']);
            $sum += $cart_list[$key]['goods_sum'];
            $k++;
        }
        foreach ($cart_a as $key=>$value){
           $value['goods']=array_values($value['goods']);
           $cart_l[]=$value;
            }

        $cart_b=array_values($cart_l);

        output_data(array('cart_list' => $cart_a, 'sum' => dsPriceFormat($sum), 'cart_count' => count($cart_list),'cart_val'=>$cart_b));
    }

    /**
     * 购物车添加
     */
    public function cart_add() {
        $goods_id = intval($_POST['goods_id']);
        $quantity = intval($_POST['quantity']);
        if ($goods_id <= 0 || $quantity <= 0) {
            output_error('参数错误');
        }

        $model_goods = Model('goods');
        $model_cart = Model('cart');
        $logic_buy_1 = model('buy_1','logic');

        $goods_info = $model_goods->getGoodsOnlineInfoAndPromotionById($goods_id);

        //验证是否可以购买
        if (empty($goods_info)) {
            output_error('商品已下架或不存在');
        }

        //抢购
        $logic_buy_1->getGroupbuyInfo($goods_info);

        //限时折扣
        $logic_buy_1->getXianshiInfo($goods_info, $quantity);

        if ($goods_info['store_id'] == $this->member_info['store_id']) {
            output_error('不能购买自己发布的商品');
        }
        if (intval($goods_info['goods_storage']) < 1 || intval($goods_info['goods_storage']) < $quantity) {
            output_error('库存不足');
        }

        $param = array();
        $param['buyer_id'] = $this->member_info['member_id'];
        $param['store_id'] = $goods_info['store_id'];
        $param['goods_id'] = $goods_info['goods_id'];
        $param['goods_name'] = $goods_info['goods_name'];
        $param['goods_price'] = $goods_info['goods_price'];
        $param['goods_image'] = $goods_info['goods_image'];
        $param['store_name'] = $goods_info['store_name'];

        $result = $model_cart->addCart($param, 'db', $quantity);
        if ($result) {
            output_data('1');
        } else {
            output_error('加入购物车失败');
        }
    }

    /**
     * 购物车删除
     */
    public function cart_del() {
        $cart_id = intval($_POST['cart_id']);

        $model_cart = Model('cart');

        if ($cart_id > 0) {
            $condition = array();
            $condition['buyer_id'] = $this->member_info['member_id'];
            $condition['cart_id'] = $cart_id;

            $model_cart->delCart('db', $condition);
        }

        output_data('1');
    }

    /**
     * 更新购物车购买数量
     */
    public function cart_edit_quantity() {
        $cart_id = intval(abs($_POST['cart_id']));
        $quantity = intval(abs($_POST['quantity']));
        if (empty($cart_id) || empty($quantity)) {
            output_error('参数错误');
        }

        $model_cart = Model('cart');

        $cart_info = $model_cart->getCartInfo(array('cart_id' => $cart_id, 'buyer_id' => $this->member_info['member_id']));

        //检查是否为本人购物车
        if ($cart_info['buyer_id'] != $this->member_info['member_id']) {
            output_error('参数错误');
        }

        //检查库存是否充足
        if (!$this->_check_goods_storage($cart_info, $quantity, $this->member_info['member_id'])) {
            output_error('超出限购数或库存不足');
        }

        $data = array();
        $data['goods_num'] = $quantity;

        $where['cart_id']= $cart_id;
        $where['buyer_id']=$cart_info['buyer_id'];

        $update = $model_cart->editCart($data, $where);
        if ($update) {
            $return = array();
            $return['quantity'] = $quantity;
            $return['goods_price'] = dsPriceFormat($cart_info['goods_price']);
            $return['total_price'] = dsPriceFormat($cart_info['goods_price'] * $quantity);
            output_data($return);
        } else {
            output_error('修改失败');
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
        // $count = $model_cart->countCartByMemberId($this->member_info['member_id']);
        $data['cart_count'] = 1;
        output_data($data);
    }

}

?>
