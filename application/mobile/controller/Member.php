<?php

namespace app\mobile\controller;

use think\Lang;

class Member extends MobileMember {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\member.lang.php');
    }
    
    
    public function index() {
        $member_info = array();

        $member_info['user_name'] = $this->member_info['member_name'];
        $member_info['avator'] = get_member_avatar_for_id($this->member_info['member_id']);
        //$member_info['point'] = $this->member_info['member_points'];
        $member_gradeinfo = Model('member')->getOneMemberGrade(intval($this->member_info['member_exppoints']));

        $member_info['level_name'] = $member_gradeinfo['level_name'];

        $member_info['favorites_store'] = Model('favorites')->getStoreFavoritesCountByMemberId($this->member_info['member_id']);
        $member_info['favorites_goods'] = Model('favorites')->getGoodsFavoritesCountByMemberId($this->member_info['member_id']);
        $member_info['mobile'] = encrypt_show($this->member_info['member_mobile'], 4, 4);
        // 交易提醒
        $model_order = Model('order');
        $member_info['order_nopay_count'] = $model_order->getOrderCountByID('buyer', $this->member_info['member_id'], 'NewCount');
        $member_info['order_noreceipt_count'] = $model_order->getOrderCountByID('buyer', $this->member_info['member_id'], 'PayCount');
        $member_info['order_notakes_count'] = $model_order->getOrderCountByID('buyer', $this->member_info['member_id'], 'SendCount');
        $member_info['order_noeval_count'] = $model_order->getOrderCountByID('buyer', $this->member_info['member_id'], 'EvalCount');
        output_data(array('member_info' => $member_info));
        // 售前退款
//        $condition = array();
//        $condition['buyer_id'] = $this->member_info['member_id'];
//        $condition['refund_state'] = array('lt', 3);
//        $member_info['return'] = Model('refundreturn')->getRefundReturnCount($condition);
    }

    public function my_asset() {
        $fields_arr = array('point', 'predepoit', 'available_rc_balance', 'redpacket', 'voucher');
        $fields_str = trim(input('fields'));
        if ($fields_str) {
            $fields_arr = explode(',', $fields_str);
        }
        $member_info = array();
        if (in_array('point', $fields_arr)) {
            $member_info['point'] = $this->member_info['member_points'];
        }
        if (in_array('predepoit', $fields_arr)) {
            $member_info['predepoit'] = $this->member_info['available_predeposit'];
        }
        if (in_array('available_rc_balance', $fields_arr)) {
            $member_info['available_rc_balance'] = $this->member_info['available_rc_balance'];
        }
       /* if (in_array('redpacket', $fields_arr)) {
            $member_info['redpacket'] = Model('redpacket')->getCurrentAvailableRedpacketCount($this->member_info['member_id']);
        }*/
        if (in_array('voucher', $fields_arr)) {
            $member_info['voucher'] = Model('voucher')->getCurrentAvailableVoucherCount($this->member_info['member_id']);
        }
        output_data($member_info);
    }

}

?>
