<?php

namespace app\mobile\controller;

use think\Lang;

class Memberorder extends MobileMember {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\memberorder.lang.php');
    }

    /**
     * 订单列表
     */
    public function order_list() {
        $model_order = Model('order');
        $condition = array();
        $condition = $this->order_type_no(input('post.state_type'));
        $condition['buyer_id'] = $this->member_info['member_id'];
        //$order_list_array = $model_order->getNormalOrderList($condition, $this->pagesize, '*', 'order_id desc','', array('order_goods'));
        $order_list_array = $model_order->getOrderList($condition, 5, '*', 'order_id desc', '', array('order_common', 'order_goods', 'store'));

        $order_group_list = $order_pay_sn_array = array();
        foreach ($order_list_array as $value) {

            //$value['zengpin_list'] = false;
            //显示取消订单
            $value['if_cancel'] = $model_order->getOrderOperateState('buyer_cancel', $value);
            //显示收货
            $value['if_receive'] = $model_order->getOrderOperateState('receive', $value);
            //显示锁定中
            $value['if_lock'] = $model_order->getOrderOperateState('lock', $value);
            //显示物流跟踪
            $value['if_deliver'] = $model_order->getOrderOperateState('deliver', $value);

            $value['if_evaluation'] = false;
            $value['if_evaluation_again'] = false;
            $value['if_delete'] = false;
            $value['ownshop'] = true;

            $value['zengpin_list'] = false;
            if (isset($value['extend_order_goods'])) {
                foreach ($value['extend_order_goods'] as $val) {
                    if ($val['goods_type'] == 5) {
                        $value['zengpin_list'][] = $val;
                    }
                }
            }

            //商品图
            if (isset($value['extend_order_goods'])) {
                foreach ($value['extend_order_goods'] as $k => $goods_info) {

                    if ($goods_info['goods_type'] == 5) {
                        unset($value['extend_order_goods'][$k]);
                    }
                    else {
                        $value['extend_order_goods'][$k] = $goods_info;
                        $value['extend_order_goods'][$k]['goods_image_url'] = goods_cthumb($goods_info['goods_image'], 240, $value['store_id']);
                    }
                }
            }
            $order_group_list[$value['pay_sn']]['order_list'][] = $value;
            //如果有在线支付且未付款的订单则显示合并付款链接
            if ($value['order_state'] == ORDER_STATE_NEW) {
                if(!isset($order_group_list[$value['pay_sn']]['pay_amount'])){
                    $order_group_list[$value['pay_sn']]['pay_amount'] = 0;
                }
                $order_group_list[$value['pay_sn']]['pay_amount'] += $value['order_amount'] - $value['rcb_amount'] - $value['pd_amount'];
            }
            $order_group_list[$value['pay_sn']]['add_time'] = $value['add_time'];

            //记录一下pay_sn，后面需要查询支付单表
            $order_pay_sn_array[] = $value['pay_sn'];
        }

        $new_order_group_list = array();
        foreach ($order_group_list as $key => $value) {
            $value['pay_sn'] = strval($key);
            $new_order_group_list[] = $value;
        }

        output_data(array('order_group_list' => $new_order_group_list), mobile_page($model_order->page_info));
    }

    private function order_type_no($stage) {
        $condition = array();
        switch ($stage) {
            case 'state_new':
                $condition['order_state'] = '10';
                break;
            case 'state_pay':
                $condition['order_state'] = '20';
                break;
            case 'state_send':
                $condition['order_state'] = '30';
                break;
            case 'state_notakes':
                $condition['order_type'] = '3';
                $condition['order_state'] = '30';
                break;
            case 'state_noeval':
                $condition['order_state'] = '40';
                break;
            case 'state_success':
                $condition['order_state'] = '40';
                $condition['delete_state'] = '0';
                break;
            case 'state_cancel':
                $condition['order_state'] = '0';
                $condition['delete_state'] = '0';
                break;
        }
        return $condition;
    }

    /**
     * 买家订单状态操作
     *
     */
    public function change_state() {
        $state_type = input('param.state_type');
        $order_id = intval(input('param.order_id'));

        $order_model = model('order');

        $condition = array();
        $condition['order_id'] = $order_id;
        $condition['buyer_id'] = $this->member_info['member_id'];
        $order_info = $order_model->getOrderInfo($condition);

        if ($state_type == 'order_cancel') {
            $result = $this->_order_cancel($order_info, input('post.'));
        } else if ($state_type == 'order_receive') {
            $result = $this->_order_receive($order_info, input('post.'));
        } else if (in_array($state_type, array('order_delete', 'order_drop', 'order_restore'))) {
            $result = $this->_order_recycle($order_info, input('param.'));
        } else {
            output_error('缺少必要参数！请确认您要操作的任务！');
        }
        if (!$result['code']) {
            output_error($result['msg']);
        } else {
            output_data(['state'=>'true','msg'=>$result['msg']]);
        }
        
    }

    /**
     * 取消订单
     */
    private function _order_cancel($order_info, $post) {
        $order_model = model('order');
        $logic_order = model('order','logic');
        $if_allow = $order_model->getOrderOperateState('buyer_cancel', $order_info);
        if (!$if_allow) {
            return ds_callback(false,  '无权操作');
        }
        $msg = isset($post['state_info1'])? $post['state_info1'] : (isset($post['state_info'])?$post['state_info']:'其他原因');
        return $logic_order->changeOrderStateCancel($order_info, 'buyer', $this->member_info['member_name'], $msg);
    }

    /**
     * 收货
     */
    private function _order_receive($order_info, $post) {
        $order_model = model('order');
        $logic_order = model('order','logic');
        $if_allow = $order_model->getOrderOperateState('receive', $order_info);
        if (!$if_allow) {
            return ds_callback(false,  '无权操作');
        }
        
        return $logic_order->changeOrderStateReceive($order_info, 'buyer', $this->member_info['member_name']);
    }

    /**
     * 回收站
     */
    private function _order_recycle($order_info, $get) {
        $order_model = model('order');
        $logic_order = model('order','logic');
        $state_type = str_replace(array('order_delete', 'order_drop', 'order_restore'), array('delete', 'drop', 'restore'), input('param.state_type'));
        $if_allow = $order_model->getOrderOperateState($state_type, $order_info);
        if (!$if_allow) {
            return ds_callback(false, '无权操作');
        }
        return $logic_order->changeOrderStateRecycle($order_info, 'buyer', $state_type);
    }

    /**
     * 物流跟踪
     */
    public function search_deliver() {
        $order_id = intval(input('post.order_id'));
        if ($order_id <= 0) {
            output_error('订单不存在');
        }

        $model_order = Model('order');
        $condition['order_id'] = $order_id;
        $condition['buyer_id'] = $this->member_info['member_id'];
        $order_info = $model_order->getOrderInfo($condition, array('order_common', 'order_goods'));
        if (empty($order_info) || !in_array($order_info['order_state'], array(ORDER_STATE_SEND, ORDER_STATE_SUCCESS))) {
            output_error('订单不存在');
        }

        $express = rkcache('express', true);
        $express_code = $express[$order_info['extend_order_common']['shipping_express_id']]['express_code'];
        $e_name = $express[$order_info['extend_order_common']['shipping_express_id']]['express_name'];

        $deliver_info = $this->_get_express($express_code, $order_info['shipping_code']);
        output_data(array('express_name' => $e_name, 'shipping_code' => $order_info['shipping_code'], 'deliver_info' => $deliver_info));
    }

    /**
     * 订单详情
     */
    public function order_info() {
        $order_id = intval(input('order_id'));
        if ($order_id <= 0) {
            output_error('订单不存在');
        }
        $model_order = Model('order');
        $condition = array();
        $condition['order_id'] = $order_id;
        $condition['buyer_id'] = $this->member_info['member_id'];
        $order_info = $model_order->getOrderInfo($condition, array('order_goods', 'order_common', 'store'));

        if (empty($order_info) || $order_info['delete_state'] == ORDER_DEL_STATE_DROP) {
            output_error('订单不存在');
        }

        $model_refund_return = Model('refundreturn');
        $order_list = array();
        $order_list[$order_id] = $order_info;
        $order_list = $model_refund_return->getGoodsRefundList($order_list, 1); //订单商品的退款退货显示
        $order_info = $order_list[$order_id];
        $refund_all = isset($order_info['refund_list'][0])?$order_info['refund_list'][0]:'';
        if (!empty($refund_all) && $refund_all['seller_state'] < 3) {//订单全部退款商家审核状态:1为待审核,2为同意,3为不同意
            output_error($refund_all);
        }


        $order_info['store_member_id'] = $order_info['extend_store']['member_id'];
        $order_info['store_phone'] = $order_info['extend_store']['store_phone'];


        if ($order_info['payment_time']) {
            $order_info['payment_time'] = date('Y-m-d H:i:s', $order_info['payment_time']);
        } else {
            $order_info['payment_time'] = '';
        }
        if ($order_info['finnshed_time']) {
            $order_info['finnshed_time'] = date('Y-m-d H:i:s', $order_info['finnshed_time']);
        } else {
            $order_info['finnshed_time'] = '';
        }
        if ($order_info['add_time']) {
            $order_info['add_time'] = date('Y-m-d H:i:s', $order_info['add_time']);
        } else {
            $order_info['add_time'] = '';
        }

        if ($order_info['extend_order_common']['order_message']) {
            $order_info['order_message'] = $order_info['extend_order_common']['order_message'];
        }
        if(!empty($order_info['extend_order_common']['invoice_info'])) {
            $order_info['invoice'] = $order_info['extend_order_common']['invoice_info']['类型'] . $order_info['extend_order_common']['invoice_info']['抬头'] . $order_info['extend_order_common']['invoice_info']['内容'];
        }

        $order_info['reciver_phone'] = $order_info['extend_order_common']['reciver_info']['phone'];
        $order_info['reciver_name'] = $order_info['extend_order_common']['reciver_name'];
        $order_info['reciver_addr'] = $order_info['extend_order_common']['reciver_info']['address'];

        $order_info['promotion'] = array();
        //显示锁定中
        $order_info['if_lock'] = $model_order->getOrderOperateState('lock', $order_info);

        //显示取消订单
        $order_info['if_buyer_cancel'] = $model_order->getOrderOperateState('buyer_cancel', $order_info);

        //显示退款取消订单
        $order_info['if_refund_cancel'] = $model_order->getOrderOperateState('refund_cancel', $order_info);

        //显示投诉
        // $order_info['if_complain'] = $model_order->getOrderOperateState('complain', $order_info);

        //显示收货
        $order_info['if_receive'] = $model_order->getOrderOperateState('receive', $order_info);

        //显示物流跟踪
        $order_info['if_deliver'] = $model_order->getOrderOperateState('deliver', $order_info);




        //显示评价
        // $order_info['if_evaluation'] = $model_order->getOrderOperateState('evaluation', $order_info);
        //显示分享
        // $order_info['if_share'] = $model_order->getOrderOperateState('share', $order_info);

        // $order_info['ownshop'] = $model_order->getOrderOperateState('share', $order_info);
        
        //显示系统自动取消订单日期
        if ($order_info['order_state'] == ORDER_STATE_NEW) {
            $order_info['order_cancel_day'] = $order_info['add_time'] + ORDER_AUTO_CANCEL_DAY * 24* 3600;
        }
        $order_info['if_deliver'] = false;
        //显示快递信息
        if ($order_info['shipping_code'] != '') {
            $order_info['if_deliver'] = true;
            $express = rkcache('express', true);
            $order_info['express_info']['express_code'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_code'];
            $order_info['express_info']['express_name'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_name'];
            $order_info['express_info']['express_url'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_url'];
            // $result = model('express')->queryExpress($express[$order_info['extend_order_common']['shipping_express_id']]['express_code'],$order_info['shipping_code'],$order_info['extend_order_common']['reciver_info']['phone']);
            // $content['Traces'] = array_reverse($result['Traces']);
            // $output = array();
            // if (is_array($content['Traces'])) {
            //     foreach ($content['Traces'] as $k => $v) {
            //         if ($v['AcceptTime'] == '')
            //             continue;
            //         $output[] = $v['AcceptTime'] . '&nbsp;&nbsp;' . $v['AcceptStation'];
            //     }
            // }
            // $order_info['express_info'] = $output;
        }


        //显示系统自动收获时间
        if ($order_info['order_state'] == ORDER_STATE_SEND) {
            $order_info['order_confirm_day'] = $order_info['delay_time'] + config('order_auto_receive_day') * 24 * 3600;
        }

        //如果订单已取消，取得取消原因、时间，操作人
        if ($order_info['order_state'] == ORDER_STATE_CANCEL) {
            $close_info = $model_order->getOrderLogInfo(array('order_id' => $order_info['order_id']), 'log_id desc');
            $order_info['close_info'] = $close_info;
            $order_info['state_desc'] = $close_info['log_orderstate'];
            $order_info['order_tips'] = $close_info['log_msg'];
        }
        //查询消费者保障服务
        if (config('contract_allow') == 1) {
            $contract_item = Model('contract')->getContractItemByCache();
        }
        foreach ($order_info['extend_order_goods'] as $value) {
            $value['image_60_url'] = goods_cthumb($value['goods_image'], 60, $value['store_id']);
            $value['image_url'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
            $value['goods_type_cn'] = get_order_goodstype($value['goods_type']);
            $value['goods_url'] = url('goods/index', array('goods_id' => $value['goods_id']));
            //处理消费者保障服务
            if (isset($value['goods_contractid']) && $contract_item) {
                $goods_contractid_arr = explode(',', $value['goods_contractid']);
                foreach ((array) $goods_contractid_arr as $gcti_v) {
                    $value['contractlist'][] = $contract_item[$gcti_v];
                }
            }
            if ($value['goods_type'] == 5) {
                $order_info['zengpin_list'][] = $value;
            } else {
                $order_info['goods_list'][] = $value;
            }
        }

        if (empty($order_info['zengpin_list'])) {
            $order_info['goods_count'] = count($order_info['goods_list']);
        } else {
            $order_info['goods_count'] = count($order_info['goods_list']) + 1;
        }

        $order_info['real_pay_amount'] = $order_info['order_amount'] + $order_info['shipping_fee'];
        //取得其它订单类型的信息000--------------------------------
        //$model_order->getOrderExtendInfo($order_info);


        $order_info['zengpin_list'] = array();
        if (is_array($order_info['extend_order_goods'])) {
            foreach ($order_info['extend_order_goods'] as $val) {
                if ($val['goods_type'] == 5) {
                    $order_info['zengpin_list'][] = $val;
                }
            }
        }
        output_data(array('order_info' => $order_info));


        //卖家发货信息
        if (!empty($order_info['extend_order_common']['daddress_id'])) {
            // $daddress_info = Model('daddress')->getAddressInfo(array('address_id' => $order_info['extend_order_common']['daddress_id']));
            // $this->assign('daddress_info', $daddress_info);
        }
    }

    /**
     * 订单详情
     */
    public function get_current_deliver() {
        $order_id = intval(input('post.order_id'));
        if ($order_id <= 0) {
            output_error('订单不存在');
        }

        $model_order = Model('order');
        $condition['order_id'] = $order_id;
        $condition['buyer_id'] = $this->member_info['member_id'];
        $order_info = $model_order->getOrderInfo($condition, array('order_common', 'order_goods'));
        if (empty($order_info) || !in_array($order_info['order_state'], array(ORDER_STATE_SEND, ORDER_STATE_SUCCESS))) {
            output_error('订单不存在');
        }

        $express = rkcache('express', true);
        $express_code = $express[$order_info['extend_order_common']['shipping_express_id']]['express_code'];
        $e_name = $express[$order_info['extend_order_common']['shipping_express_id']]['express_name'];

        $deliver_info = $this->_get_express($express_code, $order_info['shipping_code']);


        $data = array();
        $data['deliver_info']['context'] = $e_name;
        $data['deliver_info']['time'] = $deliver_info['0'];
        output_data($data);
    }


    /**
     * 从第三方取快递信息
     *
     */
    public function get_express(){

        $result = model('express')->queryExpress(input('param.express_code'),input('param.shipping_code'),input('param.phone'));
        if ($result['Success'] != true) {
            output_error('订单不存在1');
        }
        $content['Traces'] = array_reverse($result['Traces']);
        $output = array();
        if (is_array($content['Traces'])) {
            foreach ($content['Traces'] as $k => $v) {
                if ($v['AcceptTime'] == '')
                    continue;
                $output[] = $v['AcceptTime'] . '&nbsp;&nbsp;' . $v['AcceptStation'];
            }
        }
        if (empty($output))
            output_error('订单不存在2');

        // output_data($output);
        output_data($result['Traces']);
    }

}

?>
