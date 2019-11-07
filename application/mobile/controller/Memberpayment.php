<?php

namespace app\mobile\controller;

use think\Lang;

class Memberpayment extends MobileMember
{

    private $payment_code;
    private $payment_config;

    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\memberpayment.lang.php');

        if (request()->action() != 'payment_list' && !input('param.payment_code')) {
            $payment_code = 'alipay';
        }
        else {
            $payment_code = input('param.payment_code');
        }
        $model_mb_payment = Model('mbpayment');
        $condition = array();
        $condition['payment_code'] = $payment_code;
        $mb_payment_info = $model_mb_payment->getMbPaymentOpenInfo($condition);
        if (!$mb_payment_info) {
            output_error('支付方式未开启');
        }
        else {
            $this->payment_code = $payment_code;
            $this->payment_config = $mb_payment_info['payment_config'];

            $inc_file = APP_PATH . DIR_MOBILE . DS . 'api' . DS . 'payment' . DS . $this->payment_code . DS . $this->payment_code . '.php';
            if (!is_file($inc_file)) {
                output_error('支付接口出错，请联系管理员！');
            }
            require_once($inc_file);
        }
    }

    /**
     * 实物订单支付
     */
    public function pay_new()
    {
        @header("Content-type: text/html; charset=UTF-8");
        $pay_sn = input('param.pay_sn');
        if (!preg_match('/^\d{18}$/', $pay_sn)) {
            output_error('支付单号错误');
        }
        $pay_info = $this->_get_real_order_info($pay_sn, input('get.'));
        if (isset($pay_info['error'])) {
            exit($pay_info['error']);
        }
        if($pay_info['data']['pay_end']==1) {
            //站内支付了全款
            $this->redirect(WAP_SITE_URL . '/tmpl/member/order_list.html');
        }
        //第三方API支付
        $this->_api_pay($pay_info['data']);
    }

    /**
     * 虚拟订单支付
     */
    public function vr_pay_new()
    {
        @header("Content-type: text/html; charset=UTF-8");
        $order_sn = input('param.pay_sn');
        if (!preg_match('/^\d{18}$/', $order_sn)) {
            exit('订单号错误');
        }
        $pay_info = $this->_get_vr_order_info($order_sn, input('param.'));

        if (isset($pay_info['error'])) {
            exit($pay_info['error']);
        }

         if($pay_info['data']['pay_end'] == 1) {
             $this->redirect(WAP_SITE_URL . '/tmpl/member/vr_order_list.html');
         }
        //第三方API支付
        $this->_api_pay($pay_info['data']);
    }

    /**
     * 站内余额支付(充值卡、预存款支付) 实物订单
     *
     */
    private function _pd_pay($order_list, $post)
    {
        if (empty($post['password'])) {
            return $order_list;
        }
        $model_member = Model('member');
        $buyer_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
        if ($buyer_info['member_paypwd'] == '' || $buyer_info['member_paypwd'] != md5($post['password'])) {
            return $order_list;
        }

        if ($buyer_info['available_rc_balance'] == 0) {
            $post['rcb_pay'] = null;
        }
        if ($buyer_info['available_predeposit'] == 0) {
            $post['pd_pay'] = null;
        }
        if (floatval($order_list[0]['rcb_amount']) > 0 || floatval($order_list[0]['pd_amount']) > 0) {
            return $order_list;
        }

        try {
            $model_member->startTrans();
            $logic_buy_1 = model('buy_1', 'logic');
            //使用充值卡支付
            if (!empty($post['rcb_pay'])) {
                $order_list = $logic_buy_1->rcbPay($order_list, $post, $buyer_info);
            }

            //使用预存款支付
            if (!empty($post['pd_pay'])) {
                $order_list = $logic_buy_1->pdPay($order_list, $post, $buyer_info);
            }

            //特殊订单站内支付处理
            $logic_buy_1->extendInPay($order_list);

            $model_member->commit();
        } catch (Exception $e) {
            $model_member->rollback();
            exit($e->getMessage());
        }

        return $order_list;
    }

    /**
     * 站内余额支付(充值卡、预存款支付) 虚拟订单
     *
     */
    private function _pd_vr_pay($order_info, $post)
    {
        if (empty($post['password'])) {
            return $order_info;
        }
        $model_member = Model('member');
        $buyer_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
        if ($buyer_info['member_paypwd'] == '' || $buyer_info['member_paypwd'] != md5($post['password'])) {
            return $order_info;
        }
        if ($buyer_info['available_rc_balance'] == 0) {
            $post['rcb_pay'] = null;
        }
        if ($buyer_info['available_predeposit'] == 0) {
            $post['pd_pay'] = null;
        }
        if (floatval($order_info['rcb_amount']) > 0 || floatval($order_info['pd_amount']) > 0) {
            return $order_info;
        }

        try {
            $model_member->startTrans();
            $logic_buy = model('buyvirtual', 'logic');
            //使用充值卡支付
            if (!empty($post['rcb_pay'])) {
                $order_info = $logic_buy->rcbPay($order_info, $post, $buyer_info);
            }

            //使用预存款支付
            if (!empty($post['pd_pay'])) {
                $order_info = $logic_buy->pdPay($order_info, $post, $buyer_info);
            }

            $model_member->commit();
        } catch (Exception $e) {
            $model_member->rollback();
            exit($e->getMessage());
        }

        return $order_info;
    }

    /**
     * 第三方在线支付接口
     *
     */
    private function _api_pay($order_pay_info)
    {

        /*处理h5支付和公众号支付的切换*/
        if ($this->payment_code == 'wxpay_jsapi' && strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') == false) {
            $this->payment_code = 'wxpay_h5';
        }
        $param = $this->payment_config;

        // wxpay_jsapi
        if ($this->payment_code == 'wxpay_jsapi') {
            $param['orderSn'] = $order_pay_info['pay_sn'];
            $param['orderFee'] = (int)(100 * $order_pay_info['api_pay_amount']);
            $param['orderInfo'] = config('site_name') . '商品订单' . $order_pay_info['pay_sn'];
            $param['orderAttach'] = ($order_pay_info['order_type'] == 'real_order' ? 'r' : 'v');
            $api = new \wxpay_jsapi();
            $api->setConfigs($param);
            try {
                echo $api->paymentHtml($this);
            } catch (Exception $ex) {
                if (config('debug')) {
                    header('Content-type: text/plain; charset=utf-8');
                    echo $ex, PHP_EOL;
                }
                else {
                    $this->assign('msg', $ex->getMessage());
                    return $this->fetch('payment_result');
                }
            }
            exit;
        }

        // wxpay_h5
        if ($this->payment_code == 'wxpay_h5') {
            $param['orderSn'] = $order_pay_info['pay_sn'];
            $param['orderFee'] = (int)(100 * $order_pay_info['api_pay_amount']);
            $param['orderInfo'] = config('site_name') . '商品订单' . $order_pay_info['pay_sn'];
            $param['orderAttach'] = ($order_pay_info['order_type'] == 'real_order' ? 'r' : 'v');
            $api = new \wxpay_h5();
            $api->setConfigs($param);
            $mweburl = $api->get_mweb_url($this);
            Header("Location: $mweburl");
            exit;
        }

        //alipay and so on
        $param['order_sn'] = $order_pay_info['pay_sn'];
        $param['order_amount'] = $order_pay_info['api_pay_amount'];
        $param['order_type'] = ($order_pay_info['order_type'] == 'real_order' ? 'r' : 'v');
        $payment_api = new $this->payment_code($param);
        $return = $payment_api->submit();
        echo $return;
        exit;
    }

    /**
     * 获取订单支付信息
     */
    private function _get_real_order_info($pay_sn, $rcb_pd_pay = array())
    {
        $logic_payment = model('payment', 'logic');

        //取订单信息
        $result = $logic_payment->getRealOrderInfo($pay_sn, $this->member_info['member_id']);
        if (!$result['code']) {
            return array('error' => $result['msg']);
        }

        //站内余额支付
        if ($rcb_pd_pay) {
            $result['data']['order_list'] = $this->_pd_pay($result['data']['order_list'], $rcb_pd_pay);
        }

        //计算本次需要在线支付的订单总金额
        $pay_amount = 0;
        $pay_order_id_list = array();
        if (!empty($result['data']['order_list'])) {
            foreach ($result['data']['order_list'] as $order_info) {
                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    $pay_amount += $order_info['order_amount'] - $order_info['pd_amount'] - $order_info['rcb_amount'];
                    $pay_order_id_list[] = $order_info['order_id'];
                }
            }
        }

        if ($pay_amount == 0) {
            $result['data']['pay_end']=1;
        }else {
            $result['data']['pay_end']=0;
        }
        $result['data']['api_pay_amount'] = dsPriceFormat($pay_amount);
        //临时注释
        //$update = Model('order')->editOrder(array('api_pay_time'=>TIMESTAMP),array('order_id'=>array('in',$pay_order_id_list)));
        //if(!$update) {
        //       return array('error' => '更新订单信息发生错误，请重新支付');
        //    }
        //如果是开始支付尾款，则把支付单表重置了未支付状态，因为支付接口通知时需要判断这个状态
        if (isset($result['data']['if_buyer_repay'])) {
            $update = Model('order')->editOrderPay(array('api_pay_state' => 0), array('pay_id' => $result['data']['pay_id']));
            if (!$update) {
                return array('error' => '订单支付失败');
            }
            $result['data']['api_pay_state'] = 0;
        }

        return $result;
    }

    /**
     * 获取虚拟订单支付信息
     */
    private function _get_vr_order_info($pay_sn, $rcb_pd_pay = array())
    {
        $logic_payment = model('payment', 'logic');

        //取得订单信息
        $result = $logic_payment->getVrOrderInfo($pay_sn, $this->member_info['member_id']);
        if (!$result['code']) {
            output_error($result['msg']);
        }

        //站内余额支付
        if ($rcb_pd_pay) {
            $result['data'] = $this->_pd_vr_pay($result['data'], $rcb_pd_pay);
        }
        //计算本次需要在线支付的订单总金额
        $pay_amount = 0;
        if ($result['data']['order_state'] == ORDER_STATE_NEW) {
            $pay_amount += $result['data']['order_amount'] - $result['data']['pd_amount'] - $result['data']['rcb_amount'];
        }

        if ($pay_amount == 0) {
            $result['data']['pay_end']=1;
        }else{
            $result['data']['pay_end']=0;
        }

        $result['data']['api_pay_amount'] = dsPriceFormat($pay_amount);
        //临时注释
        //$update = Model('order')->editOrder(array('api_pay_time'=>TIMESTAMP),array('order_id'=>$result['data']['order_id']));
        //if(!$update) {
        //    return array('error' => '更新订单信息发生错误，请重新支付');
        //}       
        //计算本次需要在线支付的订单总金额
        $pay_amount = $result['data']['order_amount'] - $result['data']['pd_amount'] - $result['data']['rcb_amount'];
        $result['data']['api_pay_amount'] = dsPriceFormat($pay_amount);

        return $result;
    }

    /**
     * 可用支付参数列表
     */
    public function payment_list()
    {
        $model_mb_payment = model('mbpayment');

        $payment_list = $model_mb_payment->getMbPaymentOpenList();

        $payment_array = array();
        if (!empty($payment_list)) {
            foreach ($payment_list as $value) {
                $payment_array[] = $value['payment_code'];
            }
        }
        output_data(array('payment_list' => $payment_array));
    }

    /**
     * APP实物订单支付
     */
    public function orderpay_app()
    {
        $pay_sn = input('param.pay_sn');
        $pay_info = $this->_get_real_order_info($pay_sn,input('get.'));
        if (isset($pay_info['error'])) {
            output_error($pay_info['error']);
        }
        if($pay_info['data']['pay_end'] ==1){
            output_data(array('pay_end'=>1));
        }
        $param = $this->payment_config;
        //微信app支付
        if ($this->payment_code == 'wxpay_app') {
            $param['orderSn'] = $pay_sn;
            $param['orderFee'] = (int)($pay_info['data']['api_pay_amount'] * 100);
            $param['orderInfo'] = config('site_name') . '商品订单' . $pay_sn;
            $param['orderAttach'] = ($pay_info['data']['order_type'] == 'real_order' ? 'r' : 'v');
            $api = new \wxpay_app();
            $api->get_payform($param);
            exit;
        }
        //支付宝
        if ($this->payment_code == 'alipay_app') {
            $param['orderSn'] = $pay_sn;
            $param['orderFee'] = $pay_info['data']['api_pay_amount'];
            $param['orderInfo'] = config('site_name') . '商品订单' . $pay_sn;
            $param['order_type'] = ($pay_info['data']['order_type'] == 'real_order' ? 'r' : 'v');
            $api = new \alipay_app();
            $api->get_payform($param);
            exit;
        }
    }

    /**
     * APP虚拟订单支付
     */
    public function orderpay_app_vr()
    {
        $pay_sn = input('param.pay_sn');

        $pay_info = $this->_get_vr_order_info($pay_sn,input('param.'));
        if (isset($pay_info['error'])) {
            output_error($pay_info['error']);
        }
        if($pay_info['data']['pay_end'] ==1){
            output_data(array('pay_end'=>1));
        }
        $param = $this->payment_config;
        //微信app支付
        if ($this->payment_code == 'wxpay_app') {
            $param['orderSn'] = $pay_sn;
            $param['orderFee'] = (int)($pay_info['data']['api_pay_amount'] * 100);
            $param['orderInfo'] = config('site_name') . '虚拟商品订单' . $pay_sn;
            $param['orderAttach'] = ($pay_info['data']['order_type'] == 'real_order' ? 'r' : 'v');
            $api = new \wxpay_app();
            $api->get_payform($param);
            exit;
        }
        if ($this->payment_code == 'alipay_app') {
            $param['orderSn'] = $pay_sn;
            $param['orderFee'] = $pay_info['data']['api_pay_amount'];
            $param['orderInfo'] = config('site_name') . '虚拟商品订单' . $pay_sn;
            $param['order_type'] = ($pay_info['data']['order_type'] == 'real_order' ? 'r' : 'v');
            $api = new \alipay_app();
            $api->get_payform($param);
            exit;
        }
    }
}

?>
