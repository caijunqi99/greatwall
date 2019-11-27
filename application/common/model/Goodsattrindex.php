<?php

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Goodsattrindex extends Model {
    /**
     * 对应列表
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getGoodsAttrIndexList($condition, $field = '*') {
        return db('goodsattrindex')->where($condition)->field($field)->select();
    }
    
}

