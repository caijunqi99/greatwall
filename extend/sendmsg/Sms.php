<?php
/**
 * 手机短信类
 */

namespace sendmsg;

ini_set("display_errors", "on");

require_once __DIR__ . '/api_sdk/vendor/autoload.php';

use Aliyun\Core\Config;
use Aliyun\Core\Profile\DefaultProfile;
use Aliyun\Core\DefaultAcsClient;
use Aliyun\Api\Sms\Request\V20170525\SendSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\SendBatchSmsRequest;
use Aliyun\Api\Sms\Request\V20170525\QuerySendDetailsRequest;

// 加载区域结点配置
Config::load();

class Sms
{


    static $acsClient = null;

    /**
     * 取得AcsClient
     *
     * @return DefaultAcsClient
     */
    public static function getAcsClient() {
        $user_id = urlencode(config('smscf_wj_username')); // 这里填写用户名
        $key = urlencode(config('smscf_wj_key')); // 这里填接口安全密钥
        //产品名称:云通信短信服务API产品,开发者无需替换
        $product = "Dysmsapi";

        //产品域名,开发者无需替换
        $domain = "dysmsapi.aliyuncs.com";

        // TODO 此处需要替换成开发者自己的AK (https://ak-console.aliyun.com/)
        $accessKeyId = $user_id; // AccessKeyId

        $accessKeySecret = $key; // AccessKeySecret

        // 暂时不支持多Region
        $region = "cn-hangzhou";

        // 服务结点
        $endPointName = "cn-hangzhou";


        if(static::$acsClient == null) {

            //初始化acsClient,暂不支持region化
            $profile = DefaultProfile::getProfile($region, $accessKeyId, $accessKeySecret);

            // 增加服务结点
            DefaultProfile::addEndpoint($endPointName, $region, $product, $domain);

            // 初始化AcsClient用于发起请求
            static::$acsClient = new DefaultAcsClient($profile);
        }
        return static::$acsClient;
    }

    /**
     * 发送手机短信
     * @DateTime 2019-11-14
     * @param    [type]     $mobile         [手机号]
     * @param    [type]     $content        [内容--暂时无用]
     * @param    [type]     $smslog_captcha [验证码]
     * @param    [type]     $temp           [模版code]
     * @return   [type]                     [bool]
     */
    public function send($mobile, $content,$smslog_captcha,$temp)
    {
        //判断是否存在模板CODE
        if(empty($temp))return false;

        // 初始化SendSmsRequest实例用于设置发送短信的参数
        $request = new SendSmsRequest();

        //可选-启用https协议
        //$request->setProtocol("https");

        // 必填，设置短信接收号码
        $request->setPhoneNumbers($mobile);

        // 必填，设置签名名称，应严格按"签名名称"填写，请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/sign
        // $request->setSignName($temp['SignName']);
        $request->setSignName(config('site_name'));

        // 必填，设置模板CODE，应严格按"模板CODE"填写, 请参考: https://dysms.console.aliyun.com/dysms.htm#/develop/template
        $request->setTemplateCode($temp['TemplateCode']);

        // 可选，设置模板参数, 假如模板中存在变量需要替换则为必填项
        // $code = rand(10001,99999);
        $request->setTemplateParam(json_encode(array(  // 短信模板中字段的值
            "code"=>$smslog_captcha,
        ), JSON_UNESCAPED_UNICODE));

        // 可选，设置流水号
        // $m = md5($code);
        // $request->setOutId($m);

        // 选填，上行短信扩展码（扩展码字段控制在7位或以下，无特殊需求用户请忽略此字段）
        // $request->setSmsUpExtendCode("1234567");
        // p($request);exit;
        // 发起访问请求
        $acsResponse = static::getAcsClient()->getAcsResponse($request);
        // p($request->queryParameters);exit;
        $writeLog = [
            'request' =>$request,
            'response' =>$acsResponse,
            'content' =>$content
        ];
        write_payment(var_export($writeLog,true),$mobile,'mobile_Sms');

        if ($acsResponse->Code =='OK') {
            return true;
        }

        return false;
        // return $acsResponse;
        // return $this->mysend_sms($mobile, $content);
    }

    /*
    您于{$send_time}绑定手机号，验证码是：{$verify_code}。【{$site_name}】
    -1	没有该用户账户
    -2	接口密钥不正确 [查看密钥]不是账户登陆密码
    -21	MD5接口密钥加密不正确
    -3	短信数量不足
    -11	该用户被禁用
    -14	短信内容出现非法字符
    -4	手机号格式不正确
    -41	手机号码为空
    -42	短信内容为空
    -51	短信签名格式不正确接口签名格式为：【签名内容】
    -6	IP限制
   大于0 短信发送数量
    http://utf8.api.smschinese.cn/?Uid=本站用户名&Key=接口安全秘钥&smsMob=手机号码&smsText=验证码:8888
    */
    private function mysend_sms($mobile, $content)
    {
        $user_id = urlencode(config('smscf_wj_username')); // 这里填写用户名
        $key = urlencode(config('smscf_wj_key')); // 这里填接口安全密钥
        if (!$mobile || !$content || !$user_id || !$key)
            return false;
        if (is_array($mobile)) {
            $mobile = implode(",", $mobile);
        }
        $mobile=urlencode($mobile);
        $content=urlencode($content);
        $url = "http://utf8.api.smschinese.cn/?Uid=" . $user_id . "&Key=" . $key . "&smsMob=" . $mobile . "&smsText=" . $content;
        if (function_exists('file_get_contents')) {
            $res = file_get_contents($url);
        }
        else {
            $ch = curl_init();
            $timeout = 5;
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            $res = curl_exec($ch);
            curl_close($ch);
        }
        if ($res >0) {
            return true;
        }
        return false;

    }


}
