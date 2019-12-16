<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/16
 * Time: 20:12
 */

namespace app\mobile\controller;


class Logout extends MobileMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }
    /**
     * 注销
     */
    public function index(){
        $uname = input('post.username');
        $client = input('post.client');
        if (empty($uname)) {
            $uname = input('param.username');
            $client = input('param.client');
        }
        if(empty($uname) || !in_array($uname, $this->client_type_array)) {
            output_error('参数错误1');
        }
 
        $model_mb_user_token = Model('mbusertoken');

        if($this->member_info['member_mobile'] == trim($uname)) {
            $condition = array();
            $condition['member_id'] = $this->member_info['member_id'];
            $condition['member_clienttype'] = $client;
            $model_mb_user_token->delMbusertoken($condition);
            output_data(['state'=>TRUE]);
        } else {
            output_error('参数错误2');
        }
    }
}