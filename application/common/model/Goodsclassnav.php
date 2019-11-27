<?php

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Goodsclassnav extends Model {


    /**
     * 获取全部设置
     * @DateTime 2019-11-25
     * @param    [type]     $condition [description]
     * @return   [type]                [description]
     */
    public function getGoodsclassnav($condition=array(),$field ='*') {
        return db('goodsclassnav')->field($field)->where($condition)->select();
    }

    /**
     * 根据商品分类id取得数据
     * @access public
     * @author bayi-shop
     * @param num $gc_id 分类ID
     * @return array
     */
    public function getGoodsclassnavInfoByGcId($gc_id,$field ='*') {
        return db('goodsclassnav')->field($field)->where(array('gc_id' => $gc_id))->find();
    }

    /**
     * 保存分类导航设置
     * @access public
     * @author bayi-shop
     * @param type $data 更新数据
     * @return type
     */
    public function addGoodsclassnav($data) {
        return db('goodsclassnav')->insert($data);
    }
    /**
     * 编辑存分类导航设置
     * @access public
     * @author bayi-shop
     * @param array $update 更新数据
     * @param int $gc_id 分类id
     * @return boolean
     */
    public function editGoodsclassnav($update, $gc_id) {
        return db('goodsclassnav')->where(array('gc_id' => $gc_id))->update($update);
    }
}
?>
