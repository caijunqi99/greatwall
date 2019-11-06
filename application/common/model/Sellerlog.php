<?php

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Sellerlog extends Model {

    public $page_info;
 
    /**
     * 读取列表
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param string $order 排序
     * @param string $field 字段
     * @return array
     */
    public function getSellerlogList($condition, $pagesize = '', $order = '', $field = '*') {
        if($pagesize){
            $result = db('sellerlog')->field($field)->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        }else{
            $result = db('sellerlog')->field($field)->where($condition)->order($order)->select();
            return $result;
        }
    }

    /**
     * 读取单条记录
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return type
     */
    public function getSellerlogInfo($condition) {
        $result = db('sellerlog')->where($condition)->find();
        return $result;
    }

    /**
     * 增加 
     * @access public
     * @author bayi-shop
     * @param array $data 数据
     * @return bool
     */
    public function addSellerlog($data) {
        return db('sellerlog')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @return bool
     */
    public function delSellerlog($condition) {
        return db('sellerlog')->where($condition)->delete();
    }

}
