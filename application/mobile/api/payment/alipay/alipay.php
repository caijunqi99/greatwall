<?php
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'service/AlipayTradeService.php';
require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'buildermodel/AlipayTradeWapPayContentBuilder.php';

class alipay
{
    //配置
    private $alipay_config = array();
    //商户订单号
    private $out_trade_no = "";
    //订单名称
    private $subject = "";
    //付款金额
    private $total_amount = "";
    //商品描述
    private $body = "";
    //超时事件
    private $timeout_express = "";

    public function __construct($param=array())
    {
        if(!empty($param)) {
            $this->alipay_config = array(
                //应用ID,您的APPID。
                'app_id' => $param['alipay_appid'],

                //商户私钥，您的原始格式RSA私钥
                'merchant_private_key' => $param['private_key'],

                //异步通知地址
                'notify_url' => MOBILE_SITE_URL . '/payment/notify',

                //同步跳转
                'return_url' => MOBILE_SITE_URL . '/payment/alipay_return_url',

                //编码格式
                'charset' => "UTF-8",

                //签名方式
                'sign_type' => "RSA2",

                //支付宝网关
                'gatewayUrl' => "https://openapi.alipay.com/gateway.do",

                //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
                'alipay_public_key' =>$param['public_key'],

            );
            if (isset($param['order_sn'])) {
                $this->out_trade_no = $param['order_sn'] . '-' . $param['order_type'];
                $this->subject = $param['order_sn'];
                $this->total_amount = $param['order_amount'];
                $this->timeout_express = "1m";
                $this->body = "";
            }
        }
    }

    public function submit()
    {
        $payRequestBuilder = new AlipayTradeWapPayContentBuilder();
        $payRequestBuilder->setBody($this->body);
        $payRequestBuilder->setSubject($this->subject);
        $payRequestBuilder->setOutTradeNo($this->out_trade_no);
        $payRequestBuilder->setTotalAmount($this->total_amount);
        $payRequestBuilder->setTimeExpress($this->timeout_express);

        $payResponse = new AlipayTradeService($this->alipay_config);
        $result = $payResponse->wapPay($payRequestBuilder, $this->alipay_config['return_url'], $this->alipay_config['notify_url']);

        return;

    }

    /**
     * 获取return信息
     */
    public function getReturnInfo()
    {
        $arr = $_GET;
        $alipaySevice = new AlipayTradeService($this->alipay_config);
        $result = $alipaySevice->check($arr);
        trace('result' . $result, 'debug');
        if ($result) {
            return true;
        }
        return false;
    }

    public function getNotifyInfo()
    {
        $arr = $_POST;
            $alipaySevice = new AlipayTradeService($this->alipay_config);
            $alipaySevice->writeLog(var_export($_POST, true));
            $result = $alipaySevice->check($arr);
            if ($result) {
                if ($arr['trade_status'] == 'TRADE_FINISHED' || $arr['trade_status'] == 'TRADE_SUCCESS') {
                    return array(
                        //商户订单号
                        'out_trade_no' => $arr['out_trade_no'], //支付宝交易号
                        'trade_no' => $arr['trade_no'],
                    );
                }
            }
        return false;
    }
}

