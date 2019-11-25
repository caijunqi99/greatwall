<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/16
 * Time: 20:44
 */

namespace app\mobile\controller;


class Sellergoodsclass extends MobileSeller
{
    public function index()
    {
        $this->class_list();
    }

    /**
     * 返回商家商品分类列表
     */
    public function class_list()
    {
        $gc_id = intval($_POST['gc_id']);
        $deep = intval($_POST['deep']);
        if ($gc_id < 0) {
            $gc_id = 0;
        }
        if ($deep < 1) {
            $deep = 1;
        }

        $model_goods_class = Model('goods_class');
        $seller_goods_class = $model_goods_class->getGoodsClass($this->seller_info['store_id'], $gc_id, $deep, $this->seller_info['seller_group_id'], $this->seller_group_info['gc_limits'], $this->store_info['is_platform_store'] && $this->store_info['bind_all_gc']);
        output_data(array('class_list' => $seller_goods_class));
    }

    /**
     * 返回分类规格属性
     */
    public function type_info()
    {
        $gc_id = intval($_POST['gc_id']);

        $model_goods_class = Model('goods_class');
        $goods_class = $model_goods_class->getGoodsClassLineForTag($gc_id);
        $type_id = intval($goods_class['type_id']);

        $spec_list = array();
        $attr_list = array();

        if ($type_id > 0) {
            list($spec_json, $spec_list, $attr_list, $brand_list) = Model('type')->getAttr($goods_class['type_id'], $this->store_info['store_id'], $gc_id);

            $temp = array();
            foreach ($spec_list as $key => $value) {
                $value['sp_id'] = $key;
                $temp[] = $value;
            }
            $spec_list = $temp;

            $temp = array();
            foreach ($attr_list as $key => $value) {
                $value['attr_id'] = $key;
                $temp[] = $value;
            }
            $attr_list = $temp;
        }

        output_data(array('type_id' => $type_id, 'spec_list' => $spec_list, 'attr_list' => $attr_list));
    }
}