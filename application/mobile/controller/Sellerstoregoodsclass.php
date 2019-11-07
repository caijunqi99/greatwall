<?php

namespace app\mobile\controller;

use think\Lang;

class Sellerstoregoodsclass extends MobileSeller {

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $this->class_list();
    }

    /**
     * 返回商家店铺商品分类列表
     */
    public function class_list() {
        $store_goods_class = Model('store_goods_class')->getStoreGoodsClassPlainList($this->store_info['store_id']);
        output_data(array('class_list' => $store_goods_class));
    }

}

?>
