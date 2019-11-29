<?php

class wxpay_app {

    function get_payform($param=array()) {

        define("APPID", $param['wxpay_appid']);
        define("MCHID", $param['wxpay_partnerid']);
        define("KEY", $param['wxpay_partnerkey']);


        require_once APP_PATH .ATTACH_MOBILE.'/api/payment/wxpay_app/WxPayApi.php';
        //统一下单  nonce_str、sign、spbill_create_ip  在接口调用统一设置
        $input = new \WxPayUnifiedOrder();
        $input->SetBody($param['orderInfo']);
        $input->SetAttach($param['orderAttach']);
        $input->SetOut_trade_no($param['orderSn']);
        $input->SetTotal_fee($param['orderFee']);
        $input->SetNotify_url(MOBILE_SITE_URL . '/payment/wx_notify_app');
        $input->SetTrade_type('APP');

        $wxpay = new \WxPayApi();
        $order = $wxpay->unifiedOrder($input);

        if ($order['return_code'] == 'SUCCESS') {
            if ($order['result_code'] == 'SUCCESS') {
                $order['timestamp'] = time();
                $order['sign'] = $this->sign_again($order);
                output_data($order);
                //return json(['code'=>200,'result'=>$order]);
            } else {
                output_error($order['err_code_des']);
                //return json(['code'=>100,'result'=>$order]);
            }
        } else {
            output_error($order['return_msg']);
           // return json(['code'=>100,'result'=>$order]);
        }
    }

    function sign_again($order) {
        $values['appid'] = APPID;
        $values['partnerid'] = MCHID;
        $values['prepayid'] = $order['prepay_id'];
        $values['package'] = 'Sign=WXpay';
        $values['noncestr'] = $order['nonce_str'];
        $values['timestamp'] = $order['timestamp'];
        
        ksort($values);
        $buff = "";
        foreach ($values as $key => $value) {
            $buff .= $key . "=" . $value . "&";
        }

        $string = trim($buff, "&");
        $string = $string . "&key=" . KEY;
        $string = md5($string);
        //签名步骤四：所有字符转为大写
        $result = strtoupper($string);
        return $result;
    }


    function verify_notify($param) {
        define("APPID", $param['wxpay_appid']);
        define("MCHID", $param['wxpay_partnerid']);
        define("KEY", $param['wxpay_partnerkey']);

        require_once APP_PATH .ATTACH_MOBILE.'/api/payment/wxpay_app/WxPayApi.php';
        require_once APP_PATH .ATTACH_MOBILE.'/api/payment/wxpay_app/WxPayNotify.php';

        $notify = new \WxPayNotify();
        $notify->Handle(true);
        $xml = $GLOBALS['HTTP_RAW_POST_DATA'];
        $data = $notify->FromXml($xml);
//        $data = unserialize('a:17:{s:5:"appid";s:18:"wx65ea6cb402565257";s:6:"attach";s:10:"1491293185";s:9:"bank_type";s:10:"CMB_CREDIT";s:8:"cash_fee";s:1:"1";s:8:"fee_type";s:3:"CNY";s:12:"is_subscribe";s:1:"N";s:6:"mch_id";s:10:"1451261902";s:9:"nonce_str";s:32:"ritkibas1i8py3qatdt275kzbvj5nft7";s:6:"openid";s:28:"o3kLx0rwsQVbobTG5KoiRXKFpHtQ";s:12:"out_trade_no";s:10:"1491293185";s:11:"result_code";s:7:"SUCCESS";s:11:"return_code";s:7:"SUCCESS";s:4:"sign";s:32:"CA8CD53E20EE5FFE3F58B6372CE1D74D";s:8:"time_end";s:14:"20170404160702";s:9:"total_fee";s:1:"1";s:10:"trade_type";s:3:"APP";s:14:"transaction_id";s:28:"4001522001201704045834278811";}');

        if (!array_key_exists("transaction_id", $data)) {
            $verify_notify = false;
        } else {
            $transaction_id = $data['transaction_id'];
            $input = new \WxPayOrderQuery();
            $input->SetTransaction_id($transaction_id);
            $wxpay = new \WxPayApi();
            $result = $wxpay->orderQuery($input);
            if (array_key_exists("return_code", $result) && array_key_exists("result_code", $result) && $result["return_code"] == "SUCCESS" && $result["result_code"] == "SUCCESS") {
                $verify_notify = TRUE;
            } else {
                $verify_notify = false;
            }
        }
        if ($verify_notify) {
            $notify_result = array(
                'out_trade_no' => $data["out_trade_no"], #商户订单号
                'trade_no' => $data['transaction_id'], #交易凭据单号
                'total_fee' => $data["total_fee"] / 100, #涉及金额
                'trade_status' => '1',
            );
        } else {
            $notify_result = array(
                'trade_status' => '0',
            );
        }
        return $notify_result;
    }
}
