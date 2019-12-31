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

        $payment_code = input('param.payment_code');
        
        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getPaymentInfo($payment_code);
        if (!$result['code']) {
            output_error($result['msg']);
        }
    }

    /**
     * 实物订单支付
     */
    public function pay_new()
    {
        @header("Content-type: text/html; charset=UTF-8");
        $pay_sn = input('param.pay_sn');
        if (!preg_match('/^\d{20}$/', $pay_sn)) {
            output_error('支付单号错误');
        }
        
        $pay_info = $this->_get_real_order_info($pay_sn, input('param.'));
        if (isset($pay_info['error'])) {
            output_error($pay_info['error']);
        }else{
            //站内支付了全款
            if($pay_info['data']['pay_end']==1) {
                //返回抽奖是否开启，
                $pay_info['data']['draw'] = config('draw');
            }
            output_data($pay_info['data']);
        }

        // if($pay_info['data']['pay_end']==1) {
        //     //站内支付了全款
        //     $this->redirect(WAP_SITE_URL . '/tmpl/member/order_list.html');
        // }
        // //第三方API支付
        // $this->_api_pay($pay_info['data']);
    }


    /**
     * 站内余额支付(充值卡、预存款支付) 实物订单
     *
     */
    private function _pd_pay($order_list, $post)
    {
        if (empty($post['password'])) {
            output_error('支付密码不能为空！');
        }
        $model_member = Model('member');
        $buyer_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
        if ($buyer_info['member_paypwd'] == '' || $buyer_info['member_paypwd'] != md5($post['password'])) {
            output_error('支付密码错误！');
        }
        $y = abs($buyer_info['available_predeposit']) - abs( $order_list[0]['order_amount']);
        if ($y < 0 ) {
            output_error('余额不足，请充值！');
        }
        //没有充值卡支付类型
        $post['rcb_pay'] = null;
        if ($buyer_info['available_predeposit'] == 0) {
            output_error('可用余额为0，请充值！');
        }
        if (floatval($order_list[0]['rcb_amount']) > 0 || floatval($order_list[0]['pd_amount']) > 0) {
            output_error('支付失败!');
        }

        try {
            $model_member->startTrans();
            $logic_buy_1 = model('buy_1', 'logic');


            //使用预存款支付
            if (!empty($post['pd_pay'])) {
                $order_list = $logic_buy_1->pdPay($order_list, $post, $buyer_info);
            }
            
            

            //特殊订单站内支付处理
            // $logic_buy_1->extendInPay($order_list);

            $model_member->commit();
        } catch (Exception $e) {
            $model_member->rollback();
            exit($e->getMessage());
        }

        return $order_list;
    }

    private function AddGain($order_list){
        p($order_list);exit;
        //添加会员积分
        if (config('points_isuse') == 1) {
            model('points')->savePointslog('order', array(
                'pl_memberid' => $order_info['buyer_id'], 'pl_membername' => $order_info['buyer_name'],
                'orderprice' => $order_info['order_amount'], 'order_sn' => $order_info['order_sn'],
                'order_id' => $order_info['order_id']
            ), true);
        }
        //添加会员经验值
        model('exppoints')->saveExppointslog('order', array(
            'explog_memberid' => $order_info['buyer_id'], 'explog_membername' => $order_info['buyer_name'],
            'orderprice' => $order_info['order_amount'], 'order_sn' => $order_info['order_sn'],
            'order_id' => $order_info['order_id']
        ), true);
        //邀请人获得返利积分
        $inviter_id = ds_getvalue_byname('member', 'member_id', $member_id, 'inviter_id');
        if(!empty($inviter_id)) {
            $inviter_name = ds_getvalue_byname('member', 'member_id', $inviter_id['inviter_id'], 'member_name');
            $rebate_amount = ceil(0.01 * $order_info['order_amount'] * config('points_rebate'));
            model('points')->savePointslog('rebate', array(
                'pl_memberid' => $inviter_id['inviter_id'], 'pl_membername' => $inviter_name, 'pl_points' => $rebate_amount
            ), true);
        }
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
        $result['data']['api_pay_amount'] = ds_price_format($pay_amount);
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
        $pay_info = $this->_get_real_order_info($pay_sn,input('param.'));
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


    public function PdaddPay(){
        $payment_code = input('post.payment_code');
        $pdr_amount = abs(floatval(input('post.pdr_amount')));
        if ($pdr_amount <= 0) {
            output_error(lang('predeposit_recharge_add_pricemin_error'));
        }
        //获取支付配置
        $logic_payment = model('payment', 'logic');
        $result = $logic_payment->getPaymentInfo($payment_code);
        if (!$result['code']) {
            output_error($result['msg']);
        }
        $payment_info = $result['data'];
        
        $predeposit_model = model('predeposit');
        $data = array();
        $data['pdr_sn'] = $pay_sn = makePaySn($this->member_info['member_id']);
        $data['pdr_member_id'] = $this->member_info['member_id'];
        $data['pdr_member_name'] = $this->member_info['member_name'];
        $data['pdr_amount'] = $pdr_amount;
        $data['pdr_addtime'] = TIMESTAMP;
        $insert = $predeposit_model->addPdRecharge($data);
        $pay_url = '';
        if ($insert) {
            $result = $logic_payment->getPdOrderInfo($pay_sn, $this->member_info['member_id']);
            $payment_api = new $payment_info['payment_code']($payment_info);
            $pay_url=$payment_api->get_payform($result['data']);
        }
        if ($pay_url) {
            output_data($pay_url);
        }
    }
}

?>
