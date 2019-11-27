<?php

namespace app\common\model;
use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Mbfeedback extends Model {
public $page_info;
    /**
     * 列表
     * @access public
     * @author bayi-shop
     * @param array $condition 查询条件
     * @param int $pagesize 分页数
     * @param string $order 排序
     * @return array
     */
    public function getMbfeedbackList($condition, $pagesize = null, $order = 'mbfb_id desc') {
        $list = db('mbfeedback')->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]);
        $this->page_info=$list;
        return $list;
    }

    /**
     * 新增
     * @access public
     * @author bayi-shop
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMbfeedback($data) {
        return db('mbfeedback')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author bayi-shop
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delMbfeedback($condition) {
        return db('mbfeedback')->where($condition)->delete();
    }

}

?>
