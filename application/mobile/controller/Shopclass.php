<?php

namespace app\mobile\controller;


class Shopclass extends MobileMall
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }
    public function index(){
        //获取自营店列表
        $model_store_class = Model("storeclass");
        //如果只想显示自营店铺，把下面的//去掉即可
        //$condition = array(
        //   'is_own_shop' => 1,
        //);

        $lst = $model_store_class->getStoreClassList();
        $new_lst = array();
        foreach ($lst as $key => $value) {

            $new_lst[$key]['sc_id'] = $lst[$key]['sc_id'];
            $new_lst[$key]['sc_name'] = $lst[$key]['sc_name'];
            $new_lst[$key]['sc_bail'] = $lst[$key]['sc_bail'];
            $new_lst[$key]['sc_sort'] = $lst[$key]['sc_sort'];

        }

        output_data(array('class_list' => $new_lst));
    }
}