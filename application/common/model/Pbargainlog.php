<?php

/**
 * 砍价活动模型 
 *
 */

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Pbargainlog extends Model {

    public $page_info;

    /**
     * 获取开团表列表
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @param type $pagesize 分页
     * @param type $order 排序
     * @return type
     */
    public function getPbargainlogList($condition, $pagesize = '',$order='pbargainlog_id desc') {
        $res = db('pbargainlog')->where($condition)->order($order)->paginate($pagesize, false, ['query' => request()->param()]);
        $pbargainlog_list = $res->items();
        $this->page_info = $res;
        return $pbargainlog_list;
    }
    /**
     * 获取单个单团信息
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return type
     */
    public function getOnePbargainlog($condition){
        return db('pbargainlog')->where($condition)->find();
    }
    
    /**
     * 插入砍价开团表
     * @access public
     * @author bayi-shop
     * @param type $data 参数数据
     * @return type
     */
    public function addPbargainlog($data)
    {
        return db('pbargainlog')->insertGetId($data);
    }
 

  
    
}
