<?php

namespace app\mobile\controller;

//use think\Lang;

class Shopnearby extends MobileMall {

    public function _initialize() {
        parent::_initialize();
        //Lang::load(APP_PATH . 'mobile\lang\zh-cn\shop.lang.php');
    }

    /*
     * 首页显示
     */

    public function index() {
        $this->_get_Own_Store_List();
    }

    private function _get_Own_Store_List() {

        //查询条件
        $condition = array();
        if (!empty(input('param.keyword'))) {
            $condition['store_name'] = array('like', '%' . input('param.keyword') . '%');
        }
        $lat = input('param.latitude');
        $lng = input('param.longitude');
        $page = intval(input('param.page'));
        $store_list = db('store')->where($condition)->where('(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*(' . $lat . '-latitude)/360),2)+COS(PI()*' . $lat . '/180)* COS(latitude * PI()/180)*POW(SIN(PI()*(' . $lng . '-longitude)/360),2)))) < 100000')->field('store_id,is_platform_store,store_name,area_info,store_address,store_logo,(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*(' . $lat . '-latitude)/360),2)+COS(PI()*' . $lat . '/180)* COS(latitude * PI()/180)*POW(SIN(PI()*(' . $lng . '-longitude)/360),2)))) as distance')->order('distance asc')->page($page, 30)->select();

        $goods_conditions = array(
            'goods_verify' => array('eq', 1),
            'goods_state' => array('eq', 1),
            'goods_state' => array('eq', 1),
        );

        foreach ($store_list as $key => $value) {
            $store_list[$key]['distance'] = round($value['distance'], 2);
            $store_list[$key]['store_logo'] = get_store_logo($value['store_logo'], 'store_logo');
            $goods_conditions['store_id'] = array('EQ', $value['store_id']);
            $store_list[$key]['goods_list'] = db('goods')->where($goods_conditions)->field('goods_name,goods_id,goods_image,goods_price')->order('goods_addtime desc')->page($page, 4)->select();
            foreach ($store_list[$key]['goods_list'] as $key2 => $goods) {
                $store_list[$key]['goods_list'][$key2]['goods_image'] = goods_cthumb($goods['goods_image']);
            }
        }

        output_data(array('store_list' => $store_list));
    }

}
