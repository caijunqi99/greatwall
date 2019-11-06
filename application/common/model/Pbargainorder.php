<?php

/**
 * 砍价订单辅助,用于判断砍价订单是归属于哪一个团长的 
 *
 */

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Pbargainorder extends Model {

    public $page_info;
    const PINTUANORDER_STATE_CLOSE = 0;
    const PINTUANORDER_STATE_NORMAL = 1;
    const PINTUANORDER_STATE_SUCCESS = 2;
    const PINTUANORDER_STATE_FAIL = 3;

    private $bargainorder_state_array = array(
        self::PINTUANORDER_STATE_CLOSE => '砍价取消',
        self::PINTUANORDER_STATE_NORMAL => '砍价中',
        self::PINTUANORDER_STATE_SUCCESS => '砍价成功',
        self::PINTUANORDER_STATE_FAIL => '砍价失败'
    );

    /**
     * 获取砍价订单表列表
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return type
     */
    public function getPbargainorderList($condition,$pagesize) {
        $res = db('pbargainorder')->where($condition)->order('bargainorder_id desc')->paginate($pagesize, false, ['query' => request()->param()]);
        $pbargainorder_list = $res->items();
        $this->page_info = $res;
        return $pbargainorder_list;
    }

    /**
     * 获取砍价订单表列表
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return type
     */
    public function getOnePbargainorder($condition,$lock=false) {
        return db('pbargainorder')->where($condition)->lock($lock)->find();
    }

    /**
     * 增加砍价订单
     * @access public
     * @author bayi-shop
     * @param type $data 参数内容
     * @return type
     */
    public function addPbargainorder($data) {
        return db('pbargainorder')->insertGetId($data);
    }

    /**
     * 编辑砍价订单
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @param type $data 数据
     * @return type
     */
    public function editPbargainorder($condition, $data) {
        return db('pbargainorder')->where($condition)->update($data);
    }
    
    /**
     * 砍价状态数组
     * @access public
     * @author bayi-shop
     * @return type
     */
    public function getBargainorderStateArray() {
        return $this->bargainorder_state_array;
    }
    

}
