<?php

class wxpay_h5
{
    const DEBUG = 0;

    protected $config;

    public function __construct()
    {
        $this->config = (object) array(
            'appId' => '',
            'partnerId' => '',
            'apiKey' => '',

            'notifyUrl' => MOBILE_SITE_URL . '/payment/wx_notify',

            'orderSn' => date('YmdHis'),
            'orderInfo' => '',
            'orderFee' => 1,
            'orderAttach' => '_',
            'sceneInfo' =>json_encode(array('h5_info'=>array('type'=>'WAP','wap_url'=>WAP_SITE_URL,'wap_name'=>config('site_name')))),
        );
    }



    public function setConfigs(array $params)
    {
        foreach ($params as $name => $value) {
            $this->config->$name = trim($value);
        }
    }

    /*mweb_url*/
    public function get_mweb_url(){
        $data = array();
        $data['appid'] = $this->config->appId;
        $data['mch_id'] = $this->config->partnerId;
        $data['nonce_str'] = md5(uniqid(mt_rand(), true));
        $data['body'] = $this->config->orderInfo;
        $data['attach'] = $this->config->orderAttach;
        $data['out_trade_no'] = $this->config->orderSn;
        $data['total_fee'] = $this->config->orderFee;
        $data['spbill_create_ip'] = $_SERVER['REMOTE_ADDR'];
        $data['notify_url'] = $this->config->notifyUrl;
        $data['trade_type'] = 'MWEB';
        $data['scene_info']= $this->config->sceneInfo;
        $sign = $this->sign($data);
        $data['sign'] = $sign;

        $result = $this->postXml('https://api.mch.weixin.qq.com/pay/unifiedorder', $data);

        if ($result['return_code'] != 'SUCCESS') {
            exception($result['return_msg']);
        }

        if ($result['result_code'] != 'SUCCESS') {
            exception("[{$result['err_code']}]{$result['err_code_des']}");
        }

        return $result['mweb_url'];
    }

    public function notify()
    {
        try {
            $data = $this->onNotify();
            $resultXml = $this->arrayToXml(array(
                                               'return_code' => 'SUCCESS',
                                           ));
trace('notify'.json_encode($data),'debug');

        } catch (Exception $ex) {

            $data = null;
            $resultXml = $this->arrayToXml(array(
                                               'return_code' => 'FAIL',
                                               'return_msg' => $ex->getMessage(),
                                           ));

        }
        return array(
            $data,
            $resultXml,
        );
    }

    public function onNotify()
    {
        $d = $this->xmlToArray(file_get_contents('php://input'));

        if (empty($d)) {
            exception(__METHOD__);
        }

        if ($d['return_code'] != 'SUCCESS') {
            exception($d['return_msg']);
        }

        if ($d['result_code'] != 'SUCCESS') {
            exception("[{$d['err_code']}]{$d['err_code_des']}");
        }

        if (!$this->verify($d)) {
            exception("Invalid signature");
        }

        return $d;
    }

    public function verify(array $d)
    {
        if (empty($d['sign'])) {
            return false;
        }

        $sign = $d['sign'];
        unset($d['sign']);

        return $sign == $this->sign($d);
    }

    public function sign(array $data)
    {
        ksort($data);
        $a = array();
        foreach ($data as $k => $v) {
            if ((string) $v === '') {
                continue;
            }
            $a[] = "{$k}={$v}";
        }

        $a = implode('&', $a);
        $a .= '&key=' . $this->config->apiKey;

        return strtoupper(md5($a));
    }

    public function postXml($url, array $data)
    {
        // pack xml
        $xml = $this->arrayToXml($data);

        // curl post
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        $response = curl_exec($ch);
        if (!$response) {
            exception('CURL Error: ' . curl_errno($ch));
        }
        curl_close($ch);

        // unpack xml
        return $this->xmlToArray($response);
    }

    public function arrayToXml(array $data)
    {
        $xml = "<xml>";
        foreach ($data as $k => $v) {
            if (is_numeric($v)) {
                $xml .= "<{$k}>{$v}</{$k}>";
            } else {
                $xml .= "<{$k}><![CDATA[{$v}]]></{$k}>";
            }
        }
        $xml .= "</xml>";
        return $xml;
    }

    public function xmlToArray($xml)
    {
        return json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
    }
}