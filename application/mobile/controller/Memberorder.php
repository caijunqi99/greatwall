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
        $order_model = model('order');

        //搜索
        $condition = array();
        $condition['buyer_id'] = $this->member_info['member_id'];

        $order_sn = input('param.order_key');
        if ($order_sn != '') {
            if(preg_match('/[\x{4e00}-\x{9fa5}]/u', $order_sn)>0) {//含有中文
                // $condition['order_sn'] = array('like','%'.$order_sn.'%');
            }else{
                $condition['order_sn'] = array('like','%'.$order_sn.'%');
            }
            
        }
        $query_start_date = input('param.query_start_date');
        $query_end_date = input('param.query_end_date');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_start_date);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $query_end_date);
        $start_unixtime = $if_start_date ? strtotime($query_start_date) : null;
        $end_unixtime = $if_end_date ? strtotime($query_end_date) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition['add_time'] = array('between', array($start_unixtime, $end_unixtime));
        }
        $state_type = input('param.state_type');
        if ($state_type != '') {
            $condition['order_state'] = str_replace(
                    array('state_new', 'state_pay', 'state_send', 'state_success', 'state_noeval', 'state_cancel'), array(ORDER_STATE_NEW, ORDER_STATE_PAY, ORDER_STATE_SEND, ORDER_STATE_SUCCESS, ORDER_STATE_SUCCESS, ORDER_STATE_CANCEL),$state_type);
        }
        if ($state_type == 'state_noeval') {
            $condition['evaluation_state'] = 0;
            $condition['order_state'] = ORDER_STATE_SUCCESS;
        }
        
        //回收站
        $recycle = input('param.recycle');
        if ($recycle) {
            $condition['delete_state'] = 1;
        } else {
            $condition['delete_state'] = 0;
        }
        
        
        $order_list = $order_model->getOrderList($condition, 5, '*', 'order_id desc','', array('order_common','order_goods','store'));
        
        $refundreturn_model = model('refundreturn');
        $order_list = $refundreturn_model->getGoodsRefundList($order_list);

        //订单列表以支付单pay_sn分组显示
        $order_group_list = array();
        $order_pay_sn_array = array();
        foreach ($order_list as $order_id => $order) {
            $order_group_list[$order['pay_sn']]['add_time']     = $order['add_time'];
            $order_group_list[$order['pay_sn']]['order_state']  = $order['order_state'];
            $order_group_list[$order['pay_sn']]['order_amount'] = $order['order_amount'];
            $order_group_list[$order['pay_sn']]['state_desc']   = $order['state_desc'];
            $order_group_list[$order['pay_sn']]['order_id']     = $order['order_id'];
            $order_group_list[$order['pay_sn']]['order_sn']     = $order['order_sn'];
            $order_group_list[$order['pay_sn']]['pay_sn']       = $order['pay_sn'];
            $order_group_list[$order['pay_sn']]['buyer_id']     = $order['buyer_id'];
            $order_group_list[$order['pay_sn']]['buyer_name']   = $order['buyer_name'];

            //是否显示取消订单
            $order['if_cancel'] = $order_model->getOrderOperateState('buyer_cancel', $order);
            //是否显示退款取消订单
            $order['if_refund_cancel'] = $order_model->getOrderOperateState('refund_cancel', $order);
            //是否显示投诉
            $order['if_complain'] = $order_model->getOrderOperateState('complain', $order);
            //是否显示收货
            $order['if_receive'] = $order_model->getOrderOperateState('receive', $order);
            //是否显示锁定中
            $order['if_lock'] = $order_model->getOrderOperateState('lock', $order);
            //是否显示物流跟踪
            $order['if_deliver'] = $order_model->getOrderOperateState('deliver', $order);
            //是否显示评价
            $order['if_evaluation'] = $order_model->getOrderOperateState('evaluation', $order);
            //是否显示删除订单(放入回收站)
            $order['if_delete'] = $order_model->getOrderOperateState('delete', $order);
            //是否显示永久删除
            $order['if_drop'] = $order_model->getOrderOperateState('drop', $order);
            //是否显示还原订单
            $order['if_restore'] = $order_model->getOrderOperateState('restore', $order);

            foreach ($order['extend_order_goods'] as $value) {
                $value['goods_type_cn'] = get_order_goodstype($value['goods_type']);
                $value['goods_image'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
                $value['goods_image_url'] = $value['goods_image'];

                // $value['goods_url'] = url('Goods/index', ['goods_id' => $value['goods_id']]);
                if ($value['goods_type'] == 5) {
                    $order['zengpin_list'][] = $value;
                } else {
                    $order['goods_list'][] = $value;
                }
            }
            unset($order['extend_order_goods']);
            if (empty($order['zengpin_list'])) {
                $order['goods_count'] = count($order['goods_list']);
            } else {
                $order['goods_count'] = count($order['goods_list']) + 1;
            }
            $order_group_list[$order['pay_sn']]['order_list'][] = $order;

            //如果有在线支付且未付款的订单则显示合并付款链接
            if ($order['order_state'] == ORDER_STATE_NEW) {
                if (!isset($order_group_list[$order['pay_sn']]['pay_amount'])) {
                    $order_group_list[$order['pay_sn']]['pay_amount'] = 0;
                }
                $order_group_list[$order['pay_sn']]['pay_amount'] += $order['order_amount'] - $order['pd_amount'] - $order['rcb_amount'];
            }


            //记录一下pay_sn，后面需要查询支付单表
            $order_pay_sn_array[] = $order['pay_sn'];
        }

        //取得这些订单下的支付单列表
        // $condition = array('pay_sn' => array('in', array_unique($order_pay_sn_array)));
        // $order_pay_list = $order_model->getOrderpayList($condition,'*','','pay_sn');
        // foreach ($order_group_list as $pay_sn => $pay_info) {
        //     $order_group_list[$pay_sn]['pay_info'] = isset($order_pay_list[$pay_sn])?$order_pay_list[$pay_sn]:'';
        // }

        output_data(array('order_group_list' =>array_values($order_group_list) ), mobile_page($order_model->page_info));
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
        $order_id = intval(input('param.order_id'));
        if ($order_id <= 0) {
            output_error('订单不存在！');
        }
        $order_model = model('order');
        $condition = array();
        $condition['order_id'] = $order_id;
        $condition['buyer_id'] = $this->member_info['member_id'];
        $order_info = $order_model->getOrderInfo($condition, array('order_goods', 'order_common', 'store'));
        if (empty($order_info) || $order_info['delete_state'] == ORDER_DEL_STATE_DROP) {
            output_error('订单已删除！');
        }

        $refundreturn_model = model('refundreturn');
        $order_list = array();
        $order_list[$order_id] = $order_info;
        $order_list = $refundreturn_model->getGoodsRefundList($order_list, 1); //订单商品的退款退货显示
        $order_info = $order_list[$order_id];
        $refund_all = isset($order_info['refund_list'][0]) ? $order_info['refund_list'][0] : '';
        if (!empty($refund_all) && $refund_all['seller_state'] < 3) {//订单全部退款商家审核状态:1为待审核,2为同意,3为不同意
            $order_info['refund_all'] =$refund_all;
        }

        //显示锁定中
        $order_info['if_lock'] = $order_model->getOrderOperateState('lock', $order_info);

        //显示取消订单
        $order_info['if_cancel'] = $order_model->getOrderOperateState('buyer_cancel', $order_info);

        //显示退款取消订单
        $order_info['if_refund_cancel'] = $order_model->getOrderOperateState('refund_cancel', $order_info);

        //显示投诉
        $order_info['if_complain'] = $order_model->getOrderOperateState('complain', $order_info);

        //显示收货
        $order_info['if_receive'] = $order_model->getOrderOperateState('receive', $order_info);

        //显示物流跟踪
        $order_info['if_deliver'] = $order_model->getOrderOperateState('deliver', $order_info);

        //显示评价
        $order_info['if_evaluation'] = $order_model->getOrderOperateState('evaluation', $order_info);

        //显示系统自动取消订单日期
        if ($order_info['order_state'] == ORDER_STATE_NEW) {
            $order_info['order_cancel_day'] = $order_info['add_time'] + config('order_auto_cancel_day') * 24 * 3600;
        }

        //显示快递信息
        if ($order_info['shipping_code'] != '') {
            $express = rkcache('express', true);
            $order_info['express_info']['express_code'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_code'];
            $order_info['express_info']['express_name'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_name'];
            $order_info['express_info']['express_url'] = $express[$order_info['extend_order_common']['shipping_express_id']]['express_url'];
        }

        //显示系统自动收获时间
        if ($order_info['order_state'] == ORDER_STATE_SEND) {
            $order_info['order_confirm_day'] = $order_info['delay_time'] + config('order_auto_receive_day') * 24 * 3600;
        }

        //如果订单已取消，取得取消原因、时间，操作人
        if ($order_info['order_state'] == ORDER_STATE_CANCEL) {
            $order_info['close_info'] = $order_model->getOrderlogInfo(array('order_id' => $order_info['order_id']), 'log_id desc');
        }

        foreach ($order_info['extend_order_goods'] as $value) {
            $value['image_240_url'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
            $value['goods_type_cn'] = get_order_goodstype($value['goods_type']);
            // $value['goods_url'] = url('Goods/index', ['goods_id' => $value['goods_id']]);
            $value['goods_image'] = goods_cthumb($value['goods_image'], 240, $value['store_id']);
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
        $this->assign('order_info', $order_info);
        //卖家发货信息
        if (!empty($order_info['extend_order_common']['daddress_id'])) {
            $daddress_info = model('daddress')->getAddressInfo(array('daddress_id' => $order_info['extend_order_common']['daddress_id']));
            $order_info['daddress_info'] =$daddress_info;
        }
        output_data($order_info);
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
            output_error('订单不存在');
        }
        $content['Traces'] = array_reverse($result['Traces']);
        $output = array();
        if ( isset($content['Traces']) && is_array($content['Traces'])) {
            foreach ($content['Traces'] as $k => $v) {
                if ($v['AcceptTime'] == '')
                    continue;
                $output[] = $v['AcceptTime'] . '&nbsp;&nbsp;' . $v['AcceptStation'];
            }
        }
        if (empty($output))
            output_error('暂无物流信息！');

        // output_data($output);
        output_data($result['Traces']);
    }

}

?>
