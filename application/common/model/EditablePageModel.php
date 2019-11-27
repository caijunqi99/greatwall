<?php

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class EditablePageModel extends Model {

    public $page_info;

    /**
     * 新增可编辑页面模块
     * @author bayi-shop
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addEditablePageModel($data) {
        return db('editable_page_model')->insertGetId($data);
    }

    /**
     * 删除一个可编辑页面模块
     * @author bayi-shop
     * @param array $editable_page_model_id 可编辑页面模块id
     * @return bool 布尔类型的返回结果
     */
    public function delEditablePageModel($editable_page_model_id) {
        return db('editable_page_model')->where('editable_page_model_id', $editable_page_model_id)->delete();
    }

    /**
     * 获取可编辑页面模块列表
     * @author bayi-shop
     * @param array $condition 查询条件
     * @param obj $pagesize 分页页数
     * @param str $orderby 排序
     * @return array 二维数组
     */
    public function getEditablePageModelList($condition = array(), $pagesize = '', $orderby = 'editable_page_model_id desc') {
        if ($pagesize) {
            $result = db('editable_page_model')->where($condition)->order($orderby)->paginate($pagesize, false, ['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        } else {
            return db('editable_page_model')->where($condition)->order($orderby)->select();
        }
    }
    public function getOneEditablePageModel($condition = array()) {
        return db('editable_page_model')->where($condition)->find();
    }
    /**
     * 更新可编辑页面模块记录
     * @author bayi-shop
     * @param array $data 更新内容
     * @return bool
     */
    public function editEditablePageModel($condition, $data) {
        return db('editable_page_model')->where($condition)->update($data);
    }



}

?>
