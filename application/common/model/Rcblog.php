<?php

namespace app\common\model;


use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Rcblog extends Model
{
    public $page_info;
    
    /**
     * 获取列表
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @param string $pagesize 分页
     * @param string $order 排序
     * @return array
     */
    public function getRechargecardBalanceLogList($condition, $pagesize, $order)
    {
        $res =db('rcblog')->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]);
        $this->page_info=$res;
        return $res->items();
    }
}