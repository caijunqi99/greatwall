<?php

namespace app\mobile\controller;

use think\Lang;

class Sellertransport extends MobileSeller {

    public function _initialize() {
        parent::_initialize();
    }

    public function index() {
        $this->transport_list();
    }

    /**
     * 返回商家店铺商品分类列表
     */
    public function transport_list() {
        $model_transport = Model('transport');
        $transport_list = $model_transport->getTransportList(array('store_id' => $this->store_info['store_id']));
        output_data(array('transport_list' => $transport_list));
    }

}

?>
