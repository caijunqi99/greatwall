<?php

namespace app\api\controller;

class MobileMember extends MobileHome {

    public function _initialize() {
        parent::_initialize();
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (strpos($agent, "MicroMessenger") && request()->controller() == 'Wxauto') {
            $this->wxconfig = db('wxconfig')->find();
            $this->appId = $this->wxconfig['appid'];
            $this->appSecret =$this->wxconfig['appsecret'];
        } else {
            $model_mb_user_token = Model('mbusertoken');
            $key = input('post.key');
            if (empty($key)) {
                $key = input('param.key');
            }
            
            $mb_user_token_info = $model_mb_user_token->getMbusertokenInfoByToken($key);

            if (empty($mb_user_token_info)) {
                output_error('当前登陆信息已失效，请重新登陆！', array('login' => '0'));
            }
            $model_member = Model('member');
            $this->member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);



            if (empty($this->member_info)) {
                output_error('当前登陆信息已失效，请重新登陆！', array('login' => '0'));
            } else {
                if ($this->member_info['member_state'] == 0 || !$this->member_info['member_state']) {
                    output_error('当前手机号账户已被限制登陆，请联系客服！', array('login' => '0'));
                }
                $this->member_info['member_clienttype'] = $mb_user_token_info['member_clienttype'];
                $this->member_info['member_openid'] = $mb_user_token_info['member_openid'];
                $this->member_info['member_token'] = $mb_user_token_info['member_token'];
                $level_name = $model_member->getOneMemberGrade($mb_user_token_info['member_id']);
                $this->member_info['level'] = $level_name['level'];
                $this->member_info['level_name'] = $level_name['level_name'];
                //读取卖家信息
                $seller_info = Model('seller')->getSellerInfo(array('member_id' => $this->member_info['member_id']));
                $this->member_info['store_id'] = $seller_info['store_id'];
            }
        }
    }

    public function getOpenId() {
        return $this->member_info['member_openid'];
    }

    public function setOpenId($openId) {
        $this->member_info['member_openid'] = $openId;
        Model('mbusertoken')->editMemberOpenId($this->member_info['member_token'], $openId);
    }
}

?>
