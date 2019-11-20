<?php

namespace app\mobile\controller;

use think\Lang;

class Brand extends MobileMall {
    
    public function _initialize() {
        parent::_initialize();
    }
    
    public function recommend_list() {
        $brand_list = Model('brand')->getBrandPassedList(array('brand_recommend' => '1'), 'brand_id,brand_name,brand_pic');
        if (!empty($brand_list)) {
            foreach ($brand_list as $key => $val) {
                $brand_list[$key]['brand_pic'] = get_storeclass_pic($val['brand_pic']);
            }
        }
        output_data(array('brand_list' => $brand_list));
    }

}
