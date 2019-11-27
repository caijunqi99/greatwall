<?php

/**
 * 限时折扣套餐模型
 */

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Pbargainquota extends Model {

    public $page_info;

    /**
     * 获取砍价套餐列表
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @param type $field 字段
     * @return type
     */
    public function getBargainquotaList($condition, $pagesize = null, $order = '', $field = '*') {
        $res = db('pbargainquota')->field($field)->where($condition)->order($order)->paginate($pagesize, false, ['query' => request()->param()]);
        $this->page_info = $res;
        $result = $res->items();
        return $result;
    }

    /**
     * 读取单条记录
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return type
     */
    public function getBargainquotaInfo($condition) {
        $result = db('pbargainquota')->where($condition)->find();
        return $result;
    }

    /**
     * 获取当前可用套餐
     * @access public
     * @author bayi-shop
     * @param type $store_id 店铺ID
     * @return type
     */
    public function getBargainquotaCurrent($store_id) {
        $condition = array();
        $condition['store_id'] = $store_id;
        $condition['bargainquota_endtime'] = array('gt', TIMESTAMP);
        return $this->getBargainquotaInfo($condition);
    }

    /**
     * 增加
     * @access public
     * @author bayi-shop
     * @param type $data 数据
     * @return type
     */
    public function addBargainquota($data) {
        return db('pbargainquota')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author bayi-shop
     * @param type $update 更新数据
     * @param type $condition 条件
     * @return type
     */
    public function editBargainquota($update, $condition) {
        return db('pbargainquota')->where($condition)->update($update);
    }

    /**
     * 删除
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return type
     */
    public function delBargainquota($condition) {
        return db('pbargainquota')->where($condition)->delete();
    }

}
