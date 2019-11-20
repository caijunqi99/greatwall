<?php

namespace app\mobile\controller;

use think\Lang;

class Shop extends MobileMall {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\shop.lang.php');
    }

    /*
     * 首页显示
     */

    public function index() {
        $this->_get_Own_Store_List();
    }

    private function _get_Own_Store_List() {

        $model_store = Model('store');
        //查询条件
        $condition = array();
        $sc_id = intval(input('param.sc_id'));
        if ($sc_id > 0) {
            $condition['sc_id'] = $sc_id;
        } elseif (!empty(input('param.keyword'))) {
            //$condition['store_name'] = array('like', '%' . input('param.keyword') . '%');
        }
        //所需字段
        $fields = "*";
        //排序方式
        $order = $this->_store_list_order(input('param.key'), input('param.order'));
        $page = intval(input('param.page'));
        $store_list = db('store')->where($condition)->order($order)->page($page,10)->select();
        $page_count = db('store')->where($condition)->count();
        $own_store_list = $store_list;
        $simply_store_list = array();

        foreach ($own_store_list as $key => $value) {
            $simply_store_list[$key]['store_id'] = $own_store_list[$key]['store_id'];
            $simply_store_list[$key]['store_name'] = $own_store_list[$key]['store_name'];
            $simply_store_list[$key]['store_address'] = $own_store_list[$key]['store_address'];
            $simply_store_list[$key]['store_area_info'] = $own_store_list[$key]['area_info'];
        }

        output_data(array('store_list' => $simply_store_list), mobile_page($page_count));
    }

    /**
     * 商品列表排序方式
     */
    private function _store_list_order($key, $order) {
        $result = 'store_id desc';
        if (!empty($key)) {

            $sequence = 'desc';
            if ($order == 1) {
                $sequence = 'asc';
            }

            switch ($key) {
                //销量
                case '1' :
                    $result = 'store_id' . ' ' . $sequence;
                    break;
                //浏览量
                case '2' :
                    $result = 'store_name' . ' ' . $sequence;
                    break;
                //价格
                case '3' :
                    $result = 'store_name' . ' ' . $sequence;
                    break;
            }
        }
        return $result;
    }

}
