<?php

namespace app\mobile\controller;

use think\Lang;

class Voucher extends MobileMall {

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 代金券列表
     */
    public function voucher_tpl_list() {
        $param = $_REQUEST;

        $model_voucher = Model('voucher');
        $templatestate_arr = $model_voucher->getTemplateState();
        $voucher_gettype_array = $model_voucher->getVoucherGettypeArray();

        $where = array();
        $where['vouchertemplate_state'] = $templatestate_arr['usable'][0];
        $store_id = intval($param['store_id']);
        if ($store_id > 0) {
            $where['vouchertemplate_store_id'] = $store_id;
        }
        $where['vouchertemplate_gettype'] = array('in', array($voucher_gettype_array['points']['sign'], $voucher_gettype_array['free']['sign']));
        if ($param['gettype'] && in_array($param['gettype'], array('points', 'free'))) {
            $where['vouchertemplate_gettype'] = $voucher_gettype_array[$param['gettype']]['sign'];
        }
        $order = 'vouchertemplate_id asc';

        $voucher_list = $model_voucher->getVoucherTemplateList($where, '*', 20, 0, $order);
        if ($voucher_list) {
            foreach ($voucher_list as $k => $v) {
                $v['vouchertemplate_enddate_text'] = $v['vouchertemplate_enddate'] ? @date('Y年m月d日', $v['vouchertemplate_enddate']) : '';
                $voucher_list[$k] = $v;
            }
        }
        output_data(array('voucher_list' => $voucher_list));
    }

}

?>
