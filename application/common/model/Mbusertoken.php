<?php

/**
 * 手机端买家令牌模型
 */

namespace app\common\model;

use think\Model;

/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Mbusertoken extends Model {

    /**
     * 查询
     * @access public
     * @author bayi-shop
     * @param array $condition 查询条件
     * @return array
     */
    public function getMbusertokenInfo($condition) {
        return db('mbusertoken')->where($condition)->find();
    }
    
    /**
     * 查询
     * @access public
     * @author bayi-shop
     * @param type $token 令牌
     * @return type
     */
    public function getMbusertokenInfoByToken($token) {
        if (empty($token)) {
            return null;
        }
        return $this->getMbusertokenInfo(array('member_token' => $token));
    }

    /**
     * 获取token
     * @DateTime 2019-12-13
     * @param    [type]     $member_id [description]
     */
    public function GetMbusertokenByMember_id($member_id) {
        if (empty($member_id)) {
            return null;
        }
        return $this->getMbusertokenInfo(array('member_id' => $member_id));
    }
    
    /**
     * 编辑
     * @access public
     * @author bayi-shop
     * @param type $token 令牌
     * @param type $openId ID
     * @return type
     */
    public function editMemberOpenId($token, $openId) {
        return db('mbusertoken')->where(array('member_token' => $token,))->update(array('member_openid' => $openId,));
    }


    /**
     * [editMemberOpenId description]
     * @DateTime 2019-12-13
     * @param    [type]     $token  [description]
     * @param    [type]     $openId [description]
     * @return   [type]             [description]
     */
    public function editMemberToken($member_id, $data) {
        return db('mbusertoken')->where(array('member_id' => $member_id))->update($data);
    }


    /**
     * 新增
     * @access public
     * @author bayi-shop
     * @param array $data 参数内容
     * @return bool 布尔类型的返回结果
     */
    public function addMbusertoken($data) {
        return db('mbusertoken')->insertGetId($data);
    }

    /**
     * 删除
     * @access public
     * @author bayi-shop
     * @param int $condition 条件
     * @return bool 布尔类型的返回结果
     */
    public function delMbusertoken($condition) {
        return db('mbusertoken')->where($condition)->delete();
    }

}

?>
