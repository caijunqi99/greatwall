<?php

namespace app\common\model;


use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Informsubjecttype extends Model
{
    public $page_info;
    
    /**
     * 增加
     * @access public
     * @author bayi-shop
     * @param array $data 参数内容
     * @return bool
     */
    public function addInformsubjecttype($data)
    {
        return db('informsubjecttype')->insertGetId($data);

    }

    /**
     * 更新
     * @access public
     * @author bayi-shop
     * @param array $update_array 数据
     * @param array $condition 条件
     * @return bool
     */
    public function editInformsubjecttype($update_array, $condition)
    {
        return db('informsubjecttype')->where($condition)->update($update_array);
    }

    /**
     * 删除
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @return bool
     */
    public function delInformsubjecttype($condition)
    {
        return db('informsubjecttype')->where($condition)->delete();
    }

    /**
     * 获得举报类型列表
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @param int $pagesize 分页
     * @param str $order 排序
     * @return array
     */
    public function getInformsubjecttypeList($condition = '', $pagesize = '', $order = 'informtype_id asc') {
        if ($pagesize) {
            $res = db('informsubjecttype')->where($condition)->order($order)->paginate($pagesize, false, ['query' => request()->param()]);
            $this->page_info = $res;
            return $res->items();
        } else {
            return db('informsubjecttype')->where($condition)->order($order)->select();
        }
    }

    /**
     * 获得有效举报类型列表
     * @access public
     * @author bayi-shop
     * @param int $pagesize 分页
     * @return array
     */
    public function getActiveInformsubjecttypeList($pagesize = '')
    {
        $condition = array();
        $condition['informtype_state'] = 1;
        return $this->getInformsubjecttypeList($condition, $pagesize);

    }
}