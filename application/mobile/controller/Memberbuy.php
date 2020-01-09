<?php

namespace app\mobile\controller;


class Memberbuy extends MobileMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub

        //验证该会员是否禁止购买
        if(!$this->member_info['is_buylimit']){
            output_error('您没有商品购买的权限,如有疑问请联系客服人员!');
        }
        if(config('member_auth')){
            if ($this->member_info['member_auth_state']==0) {
                output_error('您需要先到我的钱包申请实名认证！');
            }elseif ($this->member_info['member_auth_state']==1) {
                output_error('您的实名认证信息正在审核中！');
            }elseif ($this->member_info['member_auth_state']==2) {
                output_error('您的实名认证信息未通过审核，请重新提交！');
            }
        }
        if (!$this->member_info['member_paypwd']) {
            output_error('您需要先到个人中心设置支付密码!');
        }
    }


    /**
     * 购物车、直接购买第一步:选择收获地址和配置方式
     */
    public function buy_step1()
    {
        $_POST['cart_id']  = input('param.cart_id','');
        $_POST['ifcart']  = input('param.ifcart','');
        $cart_id = explode(',', $_POST['cart_id']);

        $logic_buy = model('buy','logic');

        //得到会员等级
        $model_member = Model('member');
        $member_info = $model_member->getMemberInfoByID($this->member_info['member_id']);

        if ($member_info) {
            $member_gradeinfo = $model_member->getOneMemberGrade(intval($member_info['member_exppoints']));
            //$member_discount = $member_gradeinfo['orderdiscount'];
            $member_level = $member_gradeinfo['level'];
        }
        else {
            $member_level = 0;
        }

        //得到购买数据
        $ifcart=!empty($_POST['ifcart'])?true:false;
        $extra=array();
        $result = $logic_buy->buyStep1($cart_id, $ifcart, $this->member_info['member_id'], $this->member_info['store_id'],$extra,$member_level);
        if (!$result['code']) {
            output_error($result['msg']);
        }
        else {
            $result = $result['data'];
        }
        $data_area = [];
        if (isset($_POST['address_id'])&&intval($_POST['address_id']) > 0){
            $result['address_info'] = Model('address')->getDefaultAddressInfo(array('address_id' => intval($_POST['address_id']), 'member_id' => $this->member_info['member_id']));
            if ($result['address_info']) {
                $data_area = $logic_buy->changeAddr($result['freight_list'], $result['address_info']['city_id'], $result['address_info']['area_id'], $this->member_info['member_id']);
                if (!empty($data_area) && $data_area['state'] == 'success') {
                    if (is_array($data_area['content'])) {
                        foreach ($data_area['content'] as $store_id => $value) {
                            $data_area['content'][$store_id] = ds_price_format($value);
                        }
                    }
                }
                else {
                    output_error('地区请求失败');
                }
            }
        }


        //整理数据
        $store_cart_list = array();
        $store_total_list = $result['store_goods_total'];


        foreach ($result['store_cart_list'] as $key => $value) {
            $store_cart_list[$key]['goods'] = $value;
            $store_cart_list[$key]['store_goods_total'] = ds_price_format($result['store_goods_total'][$key]);

            $store_cart_list[$key]['store_mansong_rule_list'] = isset($result['store_mansong_rule_list'][$key])?$result['store_mansong_rule_list'][$key]:'';
            // 不需要代金券
            if (isset($result['store_voucher_list'][$key]) && is_array($result['store_voucher_list'][$key]) && count($result['store_voucher_list'][$key]) > 0) {
                current($result['store_voucher_list'][$key]);

                $store_cart_list[$key]['store_voucher_info'] = reset($result['store_voucher_list'][$key]);
                $store_cart_list[$key]['store_voucher_info']['voucher_price'] = ds_price_format($store_cart_list[$key]['store_voucher_info']['voucher_price']);
                $store_cart_list[$key]['store_voucher_info']['voucher_end_date_text']=date('Y年m月d日',$store_cart_list[$key]['store_voucher_info']['voucher_end_date']);
                $store_total_list[$key] = $store_cart_list[$key]['store_voucher_info']['voucher_price'];

            }
            else {
                $store_cart_list[$key]['store_voucher_info'] = array();
            }

            $store_cart_list[$key]['store_voucher_list'] = isset($result['store_voucher_list'][$key])?$result['store_voucher_list'][$key]:[];
            if (!empty($result['cancel_calc_sid_list'][$key])) {
                $store_cart_list[$key]['freight'] = '0';
                $store_cart_list[$key]['freight_message'] = $result['cancel_calc_sid_list'][$key]['desc'];
            }
            $store_cart_list[$key]['store_name'] = $value[0]['store_name'];
            $store_cart_list[$key]['store_id'] = $value[0]['store_id'];
        }

        $buy_list = array();
        // $buy_list['store_cart_list'] = $store_cart_list;
        $buy_list['store_cart_list_api']=array_values($store_cart_list);
        $buy_list['freight_hash'] = $result['freight_list'];
        $buy_list['address_info'] = !empty($result['address_info'])?$result['address_info']:[];
        $buy_list['ifshow_offpay'] = $result['ifshow_offpay'];
        $buy_list['vat_hash'] = $result['vat_hash'];
        $buy_list['inv_info'] = $result['inv_info'];
        $buy_list['available_predeposit'] = isset($result['available_predeposit'])?$result['available_predeposit']:0;
        $buy_list['available_rc_balance'] = isset($result['available_rc_balance'])?$result['available_rc_balance']:0;
        if (isset($result['rpt_list']) && !empty($result['rpt_list'])) {
            foreach ($result['rpt_list'] as $k => $v) {
                unset($result['rpt_list'][$k]['rpacket_id']);
                unset($result['rpt_list'][$k]['rpacket_end_date']);
                unset($result['rpt_list'][$k]['rpacket_owner_id']);
                unset($result['rpt_list'][$k]['rpacket_code']);
            }
        }
        $buy_list['rpt_list'] = isset($result['rpt_list']) ? $result['rpt_list'] : array();
        $buy_list['zk_list'] = isset($result['zk_list'])?$result['zk_list']:array();


        if (isset($data_area['content']) ){

            $store_total_list = model('buy_1','logic')->reCalcGoodsTotal($store_total_list, $data_area['content'], 'freight');

            //返回可用平台红包
            // $result['rpt_list'] = model('buy_1','logic')->getStoreAvailableRptList($this->member_info['member_id'], array_sum($store_total_list), 'rpacket_limit desc');
            // reset($result['rpt_list']);
            // if (is_array($result['rpt_list']) && count($result['rpt_list']) > 0) {
            //     $result['rpt_info'] = current($result['rpt_list']);
            //     unset($result['rpt_info']['rpacket_id']);
            //     unset($result['rpt_info']['rpacket_end_date']);
            //     unset($result['rpt_info']['rpacket_owner_id']);
            //     unset($result['rpt_info']['rpacket_code']);
            // }
        }
        $rpacket_price=isset($result['rpt_info']['rpacket_price']) ? ds_price_format($result['rpt_info']['rpacket_price']):'';
        $rpacket_price = is_numeric($rpacket_price)?ds_price_format($rpacket_price):0;
        $buy_list['order_amount'] = ds_price_format(array_sum($store_total_list) - $rpacket_price);
        $buy_list['rpt_info'] = isset($result['rpt_info']) ? $result['rpt_info'] : array();
        $buy_list['address_api'] = $data_area ? $data_area : '';

        foreach ($store_total_list as $store_id => $value) {
            $store_total_list[$store_id] = ds_price_format($value);
        }
        $buy_list['store_final_total_list'] = $store_total_list;

        output_data($buy_list);
    }

    /**
     * 购物车、直接购买第二步:保存订单入库，产生订单号，开始选择支付方式
     *
     */
    public function buy_step2()
    {
        $param = array();
        $param['ifcart']            = input('param.ifcart');
        $param['cart_id']           = explode(',', input('param.cart_id'));
        $param['address_id']        = input('param.address_id');
        $param['vat_hash']          = input('param.vat_hash');
        $param['offpay_hash']       = input('param.offpay_hash');
        $param['offpay_hash_batch'] = input('param.offpay_hash_batch');
        $param['pay_name']          = input('param.pay_name');
        $param['invoice_id']        = input('param.invoice_id');
        $param['rpt']               = input('param.rpt');

        //处理代金券
        $voucher = array();
        $post_voucher = explode(',', input('param.voucher'));
        if (!empty($post_voucher)) {
            foreach ($post_voucher as $value) {
                list($vouchertemplate_id, $store_id, $voucher_price) = explode('|', $value);
                $voucher[$store_id] = $value;
            }
        }

        $param['voucher'] = $voucher;

        $_POST['pay_message'] = trim(input('param.pay_message'), ',');
        $_POST['pay_message'] = explode(',', input('param.pay_message'));
        $param['pay_message'] = array();

        if (is_array(input('param.pay_message')) && input('param.pay_message')) {
            foreach (input('param.pay_message') as $v) {
                if (strpos($v, '|') !== false) {
                    $v = explode('|', $v);
                    $param['pay_message'][$v[0]] = $v[1];
                }
            }
        }
        $param['pd_pay'] = input('param.pd_pay');
        $param['rcb_pay'] = input('param.rcb_pay');
        $param['password'] = input('param.password');
        $param['fcode'] = input('param.fcode');
        $param['order_from'] = 2;
        $logic_buy = model('buy','logic');

        //得到会员等级
       $model_member = Model('member');
        $member_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
        if ($member_info) {
            $member_gradeinfo = $model_member->getOneMemberGrade(intval($member_info['member_exppoints']));
            //$member_discount = $member_gradeinfo['orderdiscount'];
            $member_level = $member_gradeinfo['level'];
        }
        else {
             $member_level = 0;
        }

        $result = $logic_buy->buyStep2($param, $this->member_info['member_id'], $this->member_info['member_name'], $this->member_info['member_email'],$member_level);
        if (!$result['code']) {
            output_error($result['msg']);
        }
        $order_info = current($result['data']['order_list']);
        output_data(array('pay_sn' => $result['data']['pay_sn'], 'payment_code' => $order_info['payment_code']));
    }

    /**
     * 验证密码
     */
    public function check_password()
    {
        if (empty($_POST['password'])) {
            output_error('参数错误');
        }

        $model_member = Model('member');

        $member_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
        if ($member_info['member_paypwd'] == md5($_POST['password'])) {
            output_data('1');
        }
        else {
            output_error('密码错误');
        }
    }

    /**
     * 更换收货地址
     */
    public function change_address()
    {
        $logic_buy = model('buy','logic');
        if (empty($_POST['city_id'])) {
            $_POST['city_id'] = $_POST['area_id'];
        }

        $data = $logic_buy->changeAddr($_POST['freight_hash'], $_POST['city_id'], $_POST['area_id'], $this->member_info['member_id']);
        if (!empty($data) && $data['state'] == 'success') {
            output_data($data);
        }
        else {
            output_error('地址修改失败');
        }
    }

    /**
     * 实物订单支付(新接口)
     */
    public function pay()
    {
        // $_POST['pay_sn'] = input('param.pay_sn');
        $pay_sn = input('param.pay_sn');
        if (!preg_match('/^\d{20}$/', $pay_sn)) {
            output_error('该订单不存在');
        }

        //查询支付单信息
        $model_order = Model('order');
        $pay_info = $model_order->getOrderPayInfo(array(
                                                      'pay_sn' => $pay_sn, 'buyer_id' => $this->member_info['member_id']
                                                  ), true);
        if (empty($pay_info)) {
            output_error('该订单不存在');
        }

        //取子订单列表
        $condition = array();
        $condition['pay_sn'] = $pay_sn;
        $condition['order_state'] = array('in', array(ORDER_STATE_NEW, ORDER_STATE_PAY));
        $order_list = $model_order->getOrderList($condition, '', '*', '', '', array(), true);
        if (empty($order_list)) {
            output_error('未找到需要支付的订单');
        }

        //定义输出数组
        $pay = array();
        //支付提示主信息
        //订单总支付金额(不包含货到付款)
        $pay['pay_amount'] = 0;
        //充值卡支付金额(之前支付中止，余额被锁定)
        $pay['payed_rcb_amount'] = 0;
        //预存款支付金额(之前支付中止，余额被锁定)
        $pay['payed_pd_amount'] = 0;
        //还需在线支付金额(之前支付中止，余额被锁定)
        $pay['pay_diff_amount'] = 0;
        //账户可用金额
        $pay['member_available_pd'] = 0;
        $pay['member_available_rcb'] = 0;

        $logic_order = model('order','logic');

        //计算相关支付金额
        foreach ($order_list as $key => $order_info) {
            if (!in_array($order_info['payment_code'], array('offline', 'chain'))) {
                if ($order_info['order_state'] == ORDER_STATE_NEW) {
                    $pay['payed_rcb_amount'] += $order_info['rcb_amount'];
                    $pay['payed_pd_amount'] += $order_info['pd_amount'];
                    $pay['pay_diff_amount'] += $order_info['order_amount'] - $order_info['rcb_amount'] - $order_info['pd_amount'];
                }
            }
        }
        if (isset($order_info['chain_id']) && $order_info['payment_code'] == 'chain') {
            $order_list[0]['order_remind'] = '下单成功，请在' . CHAIN_ORDER_PAYPUT_DAY . '日内前往门店提货，逾期订单将自动取消。';
            $flag_chain = 1;
        }

        //如果线上线下支付金额都为0，转到支付成功页
        if (empty($pay['pay_diff_amount'])) {
            output_error('订单重复支付');
        }
        $condition = [];
        $condition['payment_code'] = 'predeposit';
        $payment_list = model('payment')->getPaymentOpenList();
        foreach ($payment_list as $k => $value) {
            if ($value['payment_code']!='predeposit') {
                unset($payment_list[$k]);
            }
        }

        // if (!empty($payment_list)) {
        //     foreach ($payment_list as $k => $value) {
        //         unset($payment_list[$k]['payment_id']);
        //         unset($payment_list[$k]['payment_config']);
        //         unset($payment_list[$k]['payment_state']);
        //         unset($payment_list[$k]['payment_state_text']);
        //     }
        // }


        // if(in_array($this->member_info['member_clienttype'],array('ios','android'))){
        //     foreach ($payment_list as $k => $value) {
        //        if(!strpos($payment_list[$k]['payment_code'],'app')){
        //            unset($payment_list[$k]);
        //        }
        //     }
        // }
        sort($payment_list);
        // p($payment_list);exit;
        //显示预存款、支付密码、充值卡
        $pay['member_available_pd'] = $this->member_info['available_predeposit'];
        // $pay['member_available_rcb'] = $this->member_info['available_rc_balance'];
        $pay['member_paypwd'] = $this->member_info['member_paypwd'] ? true : false;
        $pay['pay_sn'] = $pay_sn;
        $pay['payed_amount'] = ds_price_format($pay['payed_rcb_amount'] + $pay['payed_pd_amount']);
        unset($pay['payed_pd_amount']);
        unset($pay['payed_rcb_amount']);
        $pay['pay_amount'] = ds_price_format($pay['pay_diff_amount']);
        unset($pay['pay_diff_amount']);
        $pay['member_available_pd'] = ds_price_format($pay['member_available_pd']);
        $pay['member_available_rcb'] = ds_price_format($pay['member_available_rcb']);
        $pay['payment_list'] = $payment_list ? array_values($payment_list) : array();
        output_data(array('pay_info' => $pay));
    }

    /**
     * AJAX验证支付密码
     */
    public function check_pd_pwd()
    {
        if (empty($_POST['password'])) {
            output_error('支付密码格式不正确');
        }
        $buyer_info = Model('member')->getMemberInfoByID($this->member_info['member_id'], 'member_paypwd');
        if ($buyer_info['member_paypwd'] != '') {
            if ($buyer_info['member_paypwd'] === md5($_POST['password'])) {
                output_data(['state'=>true]);
            }
        }
        output_error('支付密码验证失败');
    }

    /**
     * F码验证
     */
    public function check_fcode()
    {
        $goods_id = intval($_POST['goods_id']);
        if ($goods_id <= 0) {
            output_error('商品ID格式不正确');
        }
        if ($_POST['fcode'] == '') {
            output_error('F码格式不正确');
        }
        $result = model('buy','logic')->checkFcode($goods_id, trim($_POST['fcode']));
        if ($result['code']) {
            output_data('1');
        }
        else {
            output_error('F码验证失败');
        }
    }
}