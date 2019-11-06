<?php

namespace app\common\model;


use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Complainsubject extends Model
{
    public $page_info;

    /**
     * 增加投诉主题
     * @access public
     * @author bayi-shop 
     * @param array $data 参数内容
     * @return bool
     */
    public function addComplainsubject($data)
    {
        return db('complainsubject')->insertGetId($data);
    }

    /**
     * 更新
     * @access public
     * @author bayi-shop 
     * @param array $update_array 更新数据
     * @param array $condition 更新条件
     * @return bool
     */
    public function editComplainsubject($update_array, $condition)
    {
        return db('complainsubject')->where($condition)->update($update_array);
    }

    /**
     * 删除投诉主题
     * @access public
     * @author bayi-shop 
     * @param array $condition 检索条件
     * @return bool
     */
    public function delComplainsubject($condition)
    {
        return db('complainsubject')->where($condition)->delete();
    }

    /**
     * 获得投诉主题列表
     * @access public
     * @author bayi-shop  
     * @param array $condition 检索条件
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @return array
     */
    public function getComplainsubject($condition = '', $pagesize = '',$order = 'complainsubject_id desc')
    {
        $res= db('complainsubject')->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]);
        $this->page_info=$res;
        return $res->items();
    }

    /**
     * 获得有效投诉主题列表
     * @access public
     * @author bayi-shop  
     * @param array $condition 检索条件
     * @param int $pagesize 分页信息
     * @param str $order 排序
     * @return array
     */
    public function getActiveComplainsubject($condition = '', $pagesize = '',$order='complainsubject_id desc ')
    {
        //搜索条件
        $condition['complainsubject_state'] = 1;
        $res=db('complainsubject')->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]);
        $this->page_info=$res;
        return $res->items();
    }

}