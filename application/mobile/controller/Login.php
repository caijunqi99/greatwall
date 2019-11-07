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
        if (empty($member_info) && preg_match('/^0?(13|15|17|18|14)[0-9]{9}$/i', $username)) {//根据会员名没找到时查手机号
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
            $token = $this->_get_token($member_info['member_id'], $member_info['member_name'], $client);
            if ($token) {
                $logindata = array(
                    'username' => $member_info['member_name'], 'userid' => $member_info['member_id'], 'key' => $token
                );
                session('wap_member_info', $logindata);
                output_data($logindata);
            }
            else {
                output_error('登录失败');
            }
        }
        else {
            output_error('用户名密码错误');
        }
    }
    public function get_inviter(){
        $inviter_id=intval(input('get.inviter_id'));
        $member=db('member')->where('member_id',$inviter_id)->field('member_id,member_name')->find();
        
        output_data(array('member' => $member));
    }
    /**
     * 登录生成token
     */
    private function _get_token($member_id, $member_name, $client)
    {
        $model_mb_user_token = Model('mbusertoken');

        //重新登录后以前的令牌失效
        //暂时停用
        //$condition = array();
        //$condition['member_id'] = $member_id;
        //$condition['client_type'] = $client;
        //$model_mb_user_token->delMbUserToken($condition);
        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0, 999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = $client;

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);

        if ($result) {
            return $token;
        }
        else {
            return null;
        }
    }

    /**
     * 注册 重复注册验证
     */
    public function register()
    {
        if (Process::islock('reg')) {
            output_error('您的操作过于频繁，请稍后再试');
        }
        $username = input('param.username');
        $password = input('param.password');
        $password_confirm = input('param.password_confirm');
        $email = input('param.email');
        $client = input('param.client');
        $inviter_id = intval(input('param.inviter_id'));

        $model_member = Model('member');
        $register_info = array();
        $register_info['member_name'] = $username;
        $register_info['member_password'] = $password;
        $register_info['password_confirm'] = $password_confirm;
        $register_info['email'] = $email;
        //添加奖励积分 v3-b12
        if($inviter_id){
            $register_info['inviter_id'] = $inviter_id;
        }else{
            $register_info['inviter_id'] = intval(base64_decode(cookie('uid'))) / 1;
        }
        
        $member_info = $model_member->register($register_info);
        if (!isset($member_info['error'])) {
            process::addprocess('reg');
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
