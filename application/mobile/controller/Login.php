<?php

namespace app\mobile\controller;

use think\Lang;
use process\Process;

class Login extends MobileMall
{

    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\login.lang.php');
    }

    /**
     * 登录
     */
    public function index()
    {
        $username = input('param.username');
        $password = input('param.password');
        $client = input('param.client');
            
        if (empty($username) || empty($password) || !in_array($client, $this->client_type_array)) {
            output_error('登录失败');
        }

        $model_member = Model('member');

        $array = array();
        $array['member_name'] = $username;
        $array['member_password'] = md5($password);
        $member_info = $model_member->getMemberInfo($array);
        if (empty($member_info) && preg_match('/^0?(13|14|15|16|17|18|19)[0-9]{9}$/i', $username)) {//根据会员名没找到时查手机号
            $array = array();
            $array['member_mobile'] = $username;
            $array['member_password'] = md5($password);
            $member_info = $model_member->getMemberInfo($array);
        }

        if (empty($member_info) && (strpos($username, '@') > 0)) {//按邮箱和密码查询会员
            $array = array();
            $array['member_email'] = $username;
            $array['member_password'] = md5($password);
            $member_info = $model_member->getMemberInfo($array);
        }

        if (is_array($member_info) && !empty($member_info)) {
            if ($member_info['member_state'] == 0 || !$member_info['member_state']) {
                output_error('当前手机号已被限制登陆！');
            }
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $client);
            if ($token) {
                $logindata = array(
                    'username' => $member_info['member_name'], 'userid' => $member_info['member_id'], 'key' => $token
                );
                // session('wap_member_info', $logindata);
                output_data($logindata);
            }else {
                output_error('登录失败');
            }
        }else {
            output_error('用户名密码错误');
        }
    }
    public function get_inviter(){
        $inviter_id=intval(input('param.inviter_id'));
        $member=db('member')->where('member_id',$inviter_id)->field('member_id,member_name,inviter_code')->find();
        
        output_data(array('member' => $member));
    }
    /**
     * 登录生成token
     */
    private function _get_token($member_id, $member_name, $client)
    {
        $model_mb_user_token = Model('mbusertoken');
        $mb_user_token_info = array();
        $member = $model_mb_user_token->GetMbusertokenByMember_id($member_id);
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0, 999999)));
        $mb_user_token_info = array();
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['member_token'] = $token;
        $mb_user_token_info['member_logintime'] = TIMESTAMP;
        $mb_user_token_info['member_clienttype'] = $client;
        if ($member) {
            //修改token
            $result = $model_mb_user_token->editMemberToken($member_id,$mb_user_token_info);
        }else{
            //生成新的token
            $mb_user_token_info['member_id'] = $member_id;
            $result = $model_mb_user_token->addMbusertoken($mb_user_token_info);
        }
        if ($result) {
            return $token;
        }
        else {
            return null;
        }
    }


    /**
     * 验证码校验
     * @DateTime 2019-11-18
     * @return   [type]     [description]
     */
    public function check_sms_captcha($username,$sms_captcha,$log_type){
        if (strlen($username) == 11){
            $state = 'true';
            $condition = array();
            $condition['smslog_phone'] = $username;
            $condition['smslog_captcha'] = $sms_captcha;
            $condition['smslog_type'] = $log_type;
            $model_sms_log = Model('smslog');
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['smslog_smstime'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $state = '动态码错误或已过期，重新输入';
                output_error($state);
            }
        }
    }


    /**
     * 注册 重复注册验证
     */
    public function register()
    {
        
        
        $username = trim(input('param.username'));
        $yanzheng = preg_match('/^1[3456789]\d{9}$/',$username);
        if(!$yanzheng) output_error('无效的手机号码！');
        $password         = input('param.password');
        $password_confirm = input('param.password_confirm');
        if ($password != $password_confirm) output_error('两次密码输入不一致！');
        $email            = input('param.email');
        $client           = input('param.client');
        $sms_captcha      = input('param.sms_captcha');
        $inviter_code     = input('param.inviter_code');
        $log_type         = input('param.log_type');//短信类型:1为注册,2为登录,3为找回密码
        $model_member     = Model('member');
        $register_info    = array();
        $register_info['member_name']       = create_guid();
        $register_info['member_mobile']     = $username;
        $register_info['member_mobilebind'] = 1;
        $register_info['member_password']   = $password;
        $register_info['password_confirm']  = $password_confirm;
        $register_info['email']             = $email;
        $inviter_id = $model_member->infoMember(['inviter_code'=>$inviter_code],'member_id');
        //推荐人 
        if($inviter_id){
            $register_info['inviter_id'] = $inviter_id['member_id'];
        }else{
            output_error('无效的注册码！');
        }
        $this->check_sms_captcha($username,$sms_captcha,$log_type);
        //生成推荐码
        $register_info['inviter_code'] = $model_member->_get_inviter_code();
        
        $member_info = $model_member->register($register_info);
        if (!isset($member_info['error'])) {
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $client);
            if ($token) {
                output_data(array(
                                'username' => $member_info['member_name'], 'userid' => $member_info['member_id'],
                                'key' => $token
                            ));
            }
            else {
                output_error('注册失败');
            }
        }
        else {
            output_error($member_info['error']);
        }
    }
}

?>
