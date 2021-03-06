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
            $key = input('post.key');
            if (empty($key)) {
                $key = input('param.key');
            }
            
            $mb_user_token_info = $model_mb_user_token->getMbusertokenInfoByToken($key);
            $model_member = Model('member');
            $mobile = '';
            if (empty($mb_user_token_info)) {
                //如果传入手机号，以手机号查询
                $mobile = input('param.mobile_key');
                if ($mobile) {
                    $member = $model_member->getMemberInfo(['member_mobile'=>$mobile],'member_id');
                    if ($member) {
                        $mb_user_token_info['member_id'] = $member['member_id'];
                    }else{
                        output_error('请登录', array('login' => '0'));
                    }
                }else{
                    output_error('请登录', array('login' => '0'));    
                }
                
            }
            $this->member_info = $model_member->getMemberInfoByID($mb_user_token_info['member_id']);


            if (empty($this->member_info)) {

                output_error('请登录', array('login' => '0'));    
            } else {
                if (!$mobile) {
                    $this->member_info['member_clienttype'] = $mb_user_token_info['member_clienttype'];
                    $this->member_info['member_openid'] = $mb_user_token_info['member_openid'];
                    $this->member_info['member_token'] = $mb_user_token_info['member_token'];
                    $level_name = $model_member->getOneMemberGrade($this->member_info['member_exppoints']);
                    $this->member_info['level'] = $level_name['level'];
                    $this->member_info['level_name'] = $level_name['level_name'];
                    //读取卖家信息
                    $seller_info = Model('seller')->getSellerInfo(array('member_id' => $this->member_info['member_id']));
                    $this->member_info['store_id'] = $seller_info['store_id'];
                }
                
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
