<?php

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Activity extends Model
{
    public $page_info;

    /**
     * 活动列表
     * @author bayi-shop
     * @param type $condition   查询条件
     * @param type $pagesize        分页页数
     * @param type $order       排序
     * @return type
     */
    public function getActivityList($condition, $pagesize = '', $order = 'activity_sort asc') {
        if ($pagesize) {
            $res = db('activity')->where($condition)->order($order)->paginate($pagesize, false, ['query' => request()->param()]);
            $this->page_info = $res;
            return $res->items();
        } else {
            return db('activity')->where($condition)->order($order)->select();
        }
    }

    /**
     * 添加活动
     * @author bayi-shop
     * @param type $data 查询数据
     * @return array 一维数组
     */
    public function addActivity($data)
    {
        return db('activity')->insertGetId($data);
    }
    /**
     * 更新活动
     * @author bayi-shop
     * @param type $data 活动数据
     * @param type $id   活动id
     * @return type
     */
    public function editActivity($data, $id)
    {
        return db('activity')->where("activity_id='$id' ")->update($data);
    }

    /**
     * 删除活动
     * @author bayi-shop
     * @param type $condition 删除条件
     * @return type
     */
    public function delActivity($condition)
    {
        return db('activity')->where($condition)->delete();
    }

    /**
     * 根据id查询一条活动
     * @author bayi-shop
     * @param int $id 活动id
     * @return array 一维数组
     */
    public function getOneActivityById($id)
    {
        return db('activity')->where('activity_id',$id)->find();
    }
}