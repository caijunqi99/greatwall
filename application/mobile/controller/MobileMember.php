<?php

namespace app\mobile\controller;

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
            //$key = input('post.key');
            $key="bd271df2d303ad1cefc8f21e99a70431";
            if (empty($key)) {
                $key = input('param.key');
            }
            
            $mb_user_token_info = $model_mb_user_token->getMbusertokenInfoByToken($key);

            if (empty($mb_user_token_info)) {
                output_error('请登录', array('login' => '0'));
            }
            $model_member = Model('member');
            $this->member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);



            if (empty($this->member_info)) {
                output_error('请登录', array('login' => '0'));
            } else {
                $this->member_info['member_clienttype'] = $mb_user_token_info['member_clienttype'];
                $this->member_info['member_openid'] = $mb_user_token_info['member_openid'];
                $this->member_info['member_token'] = $mb_user_token_info['member_token'];
                $level_name = $model_member->getOneMemberGrade($mb_user_token_info['member_id']);
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
