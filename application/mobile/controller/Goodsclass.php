<?php

namespace app\mobile\controller;

use think\Lang;

class Goodsclass extends MobileMall {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\category.lang.php');
    }

    public function index() {
        $gc_id = intval(input('param.gc_id'));
        if ($gc_id > 0) {
            $date = $this->_get_class_list($gc_id);
            output_data($date);
        } else {
            $this->_get_root_class();
        }
    }

    /**
     * 返回一级分类列表
     */
    private function _get_root_class() {
        $model_goods_class = Model('goodsclass');

        $goods_class_array = Model('goodsclass')->getGoodsClassForCacheModel();
        $class_list = $model_goods_class->getGoodsclassListByParentId(0);
        // p($goods_class_array);exit;
        // 



        
        // $mb_categroy = array_under_reset($mb_categroy, 'gc_id');
        foreach ($class_list as $key => $value) {
            $class_list[$key]['image'] = goodsclass_image($value['gc_id']);

            $class_list[$key]['text'] = '';
            if (isset($goods_class_array[$value['gc_id']]['child'])) {
                $child_class_string = $goods_class_array[$value['gc_id']]['child'];
                $child_class_array = explode(',', $child_class_string);
            }
            
            foreach ($child_class_array as $child_class) {
                $class_list[$key]['text'] .= $goods_class_array[$child_class]['gc_name'] . '/';
            }
            $class_list[$key]['text'] = rtrim($class_list[$key]['text'], '/');
            if ( !empty($navmenu[$value['gc_id']] ) ) {
                $class_list[$key]['gc_adv'] = $navmenu[$value['gc_id']];
            }
            // $class_list[$key]['gc_adv'] = $navmenu[$value['gc_id']];
        }

        output_data(array('class_list' => $class_list));
    }

    /**
     * 根据分类编号返回下级分类列表
     */
    private function _get_class_list($gc_id) {
        $goods_class_array = Model('goodsclass')->getGoodsclassForCacheModel();

        $goods_class = $goods_class_array[$gc_id];

        if (empty($goods_class['child'])) {
            //无下级分类返回0
            return array('class_list' => array());
        } else {
            //返回下级分类列表
            $class_list = array();
            $child_class_string = $goods_class_array[$gc_id]['child'];
            // p($goods_class_array);
            $child_class_array = explode(',', $child_class_string);
            foreach ($child_class_array as $child_class) {
                $class_item = array();
                $class_item['gc_id'] = '';
                $class_item['gc_name'] = '';
                $class_item['image'] = '';
                $class_item['gc_id'] .= $goods_class_array[$child_class]['gc_id'];
                $class_item['gc_name'] .= $goods_class_array[$child_class]['gc_name'];
                $class_item['image'] .= goodsclass_image($goods_class_array[$child_class]['gc_id']);
                $class_list[] = $class_item;
            }
            return array('class_list' => $class_list);
        }
    }

    /**
     * 获取全部子集分类
     */
    public function get_child_all() {
        $gc_id = intval(input('param.gc_id'));
        $model_class = Model('Goodsclass');
        $classnav = $model_class->getHotClass($gc_id);
        $data = array();
        if ($gc_id > 0) {
            $data = $this->_get_class_list($gc_id);
            if (!empty($data['class_list'])) {
                if (!empty($classnav)) {
                    //把一维数组 刷成二维数组 给APP端
                    $adv = [];
                    $adv[0]=[
                            'goodscn_adv'      =>isset($classnav['goodscn_adv1'])?$classnav['goodscn_adv1']:'',
                            'goodscn_adv_link' =>isset($classnav['goodscn_adv1_link'])?$classnav['goodscn_adv1_link']:''
                        ];
                    $adv[1]=[
                            'goodscn_adv'      =>isset($classnav['goodscn_adv2'])?$classnav['goodscn_adv2']:'',
                            'goodscn_adv_link' =>isset($classnav['goodscn_adv2_link'])?$classnav['goodscn_adv2_link']:''
                        ];
                    
        
                    //热门推荐
                    $data['hot_list'] = $classnav['cn_classs'];
                    //分类轮播图
                    $data['adv_list'] = $adv;
                    foreach ($data['hot_list'] as $k => $v) {
                        $data['hot_list'][$k]['image'] = goodsclass_image($v['gc_id']);
                    }
                }else{
                    $data['hot_list'] = [];
                    $data['adv_list'] = [];
                }
                foreach ($data['class_list'] as $key => $val) {
                    $data['class_list'][$key]['image'] = goodsclass_image($val['gc_id']);
                    $d = $this->_get_class_list($val['gc_id']);
                    $data['class_list'][$key]['child'] = $d['class_list'];
                }
            }
        }
        output_data($data);
    }

}

?>
