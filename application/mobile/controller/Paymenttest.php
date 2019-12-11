<?php
/**
 * 二维码
 * */

namespace app\mobile\controller;


class Paymenttest extends MobileMember
{
    public function pay(){
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
        $data['pdr_sn'] = $pay_sn = makePaySn(session('member_id'));
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