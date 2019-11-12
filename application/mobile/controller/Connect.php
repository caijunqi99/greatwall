<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/16
 * Time: 20:05
 */

namespace app\mobile\controller;


class Connect extends MobileMall
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }
    /**
     * 新浪微博登陆
     */
    public function get_sina_oauth2() {
        $this->redirect('api/oa_sina');
    }

    /**
     * QQ登陆
     */
    public function get_qq_oauth2() {
        $this->redirect('api/oa_qq');
    }


    /**
     * 短信动态码
     */
    public function get_sms_captcha(){
        $state = '发送失败';
        $phone = $_GET['phone'];
        if (strlen($phone) == 11){
            $log_type = $_GET['type'];//短信类型:1为注册,2为登录,3为找回密码
            $model_sms_log = Model('smslog');
            $condition = array();
            $condition['log_ip'] = request()->ip();
            $condition['log_type'] = $log_type;
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(!empty($sms_log) && ($sms_log['add_time'] > TIMESTAMP-10)) {//同一IP十分钟内只能发一条短信
                $state = '同一IP地址十分钟内，请勿多次获取动态码！';
            } else {
                $state = 'true';
                $log_array = array();
                $model_member = Model('member');
                $member = $model_member->getMemberInfo(array('member_mobile'=> $phone));
                $captcha = rand(100000, 999999);
                $log_msg = '【'.config('site_name').'】您于'.date("Y-m-d");
                switch ($log_type) {
                    case '1':
                        if(config('sms_register') != 1) {
                            $state = '系统没有开启手机注册功能';
                        }
                        if(!empty($member)) {//检查手机号是否已被注册
                            $state = '当前手机号已被注册，请更换其他号码。';
                        }
                        $log_msg .= '申请注册会员，动态码：'.$captcha.'。';
                        break;
                    case '2':
                        if(config('sms_login') != 1) {
                            $state = '系统没有开启手机登录功能';
                        }
                        if(empty($member)) {//检查手机号是否已绑定会员
                            $state = '当前手机号未注册，请检查号码是否正确。';
                        }
                        $log_msg .= '申请登录，动态码：'.$captcha.'。';
                        $log_array['member_id'] = $member['member_id'];
                        $log_array['member_name'] = $member['member_name'];
                        break;
                    case '3':
                        if(config('sms_password') != 1) {
                            $state = '系统没有开启手机找回密码功能';
                        }
                        if(empty($member)) {//检查手机号是否已绑定会员
                            $state = '当前手机号未注册，请检查号码是否正确。';
                        }
                        $log_msg .= '申请重置登录密码，动态码：'.$captcha.'。';
                        $log_array['member_id'] = $member['member_id'];
                        $log_array['member_name'] = $member['member_name'];
                        break;
                    default:
                        $state = '参数错误';
                        break;
                }
                if($state == 'true'){
                    $sms = new \sendmsg\Sms();
                    $result = $sms->send($phone,$log_msg);
                    if($result){
                        $log_array['log_phone'] = $phone;
                        $log_array['log_captcha'] = $captcha;
                        $log_array['log_ip'] = request()->ip();
                        $log_array['log_msg'] = $log_msg;
                        $log_array['log_type'] = $log_type;
                        $log_array['add_time'] = time();
                        $model_sms_log->addSms($log_array);
                        output_data(array('sms_time'=>10));exit;
                    } else {
                        $state = '手机短信发送失败';
                    }
                }
            }
        }
        output_error($state);
    }
    /**
     * 验证注册动态码
     */
    public function check_sms_captcha(){
        $state = '验证失败';
        $phone = $_GET['phone'];
        $captcha = $_GET['captcha'];
        $log_type=$_GET['type'];
        if (strlen($phone) == 11){
            $state = 'true';
            $condition = array();
            $condition['log_phone'] = $phone;
            $condition['log_captcha'] = $captcha;
            $condition['log_type'] = $log_type;
            $model_sms_log = Model('smslog');
            $sms_log = $model_sms_log->getSmsInfo($condition);
            if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
                $state = '动态码错误或已过期，重新输入';
                output_error($state);
            }
            output_data($state);
        }
        output_error($state);
    }


    /**
     * 找回密码
     */
    public function find_password_ww(){
        if(config('sms_password') != 1) {
            output_error('系统没有开启手机找回密码功能','','error');
        }
        $phone = $_POST['phone'];
        $captcha = $_POST['captcha'];
        $condition = array();
        $condition['log_phone'] = $phone;
        $condition['log_captcha'] = $captcha;
        $condition['log_type'] = 3;
        $model_sms_log = Model('sms_log');
        $sms_log = $model_sms_log->getSmsInfo($condition);
        if(empty($sms_log) || ($sms_log['add_time'] < TIMESTAMP-1800)) {//半小时内进行验证为有效
            output_error('动态码错误或已过期，重新输入','','error');
        }
        $model_member = Model('member');
        $member = $model_member->getMemberInfo(array('member_mobile'=> $phone));//检查手机号是否已被注册
        if(!empty($member)) {
            $new_password = md5($_POST['password']);
            $model_member->editMember(array('member_id'=> $member['member_id']),array('member_password'=> $new_password));

            $token = $this->_get_token($member['member_id'], $member['member_name'], $_POST['client']);
            if($token) {
                output_data(array('username' => $member_info['member_name'], 'key' => $token));
            }
        }

        output_error($state);
    }

    /**
     * 手机注册
     */
    public function sms_register(){
        $phone = $_POST['phone'];
        $captcha = $_POST['captcha'];
        $password = $_POST['password'];
        $client = $_POST['client'];
        $logic_connect_api = model('connectapi','logic');
        $state_data = $logic_connect_api->smsRegister($phone, $captcha, $password, $client);

        if($state_data['state']=='1'){
            output_data($state_data);
        } else {
            output_error($state_data['msg']);
        }
    }


    /**
     * 手机找回密码
     */
    public function find_password(){
        $phone = $_POST['phone'];
        $captcha = $_POST['captcha'];
        $password = $_POST['password'];
        $client = $_POST['client'];
        $logic_connect_api = model('connectapi','logic');
        $state_data = $logic_connect_api->smsPassword($phone, $captcha, $password, $client);
        $this->connect_output_data($state_data);
    }

    /**
     * 格式化输出数据
     */
    private function connect_output_data($state_data, $type = 0){
        if($state_data['state']){
            unset($state_data['state']);
            unset($state_data['msg']);
            if ($type == 1){
                $state_data = 1;
            }
            output_data($state_data);
        } else {
            output_error($state_data['msg']);
        }
    }

    /**
     * 登录开关状态
     */
    public function get_state() {
        $logic_connect_api = model('connectapi','logic');
        $state_array = $logic_connect_api->getStateInfo();

        $key = $_GET['t'];
        if(trim($key) != '' && array_key_exists($key,$state_array)){
            output_data($state_array[$key]);
        } else {
            output_data($state_array);
        }
    }

    /**
     * 登录生成token
     */
    private function _get_token($member_id, $member_name, $client) {
        $model_mb_user_token = Model('mb_user_token');

        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0,999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['token'] = $token;
        $mb_user_token_info['login_time'] = TIMESTAMP;
        $mb_user_token_info['client_type'] = $client;

        $result = $model_mb_user_token->addMbUserToken($mb_user_token_info);

        if($result) {
            return $token;
        } else {
            return null;
        }

    }

}