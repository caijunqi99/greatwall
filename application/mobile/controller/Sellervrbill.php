<?php

namespace app\mobile\controller;

use think\Lang;

class Sellervrbill extends MobileSeller {
    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 结算列表
     *
     */
    public function vrbill_list() {
        $model_bill = Model('vr_bill');
        $condition = array();
        $condition['ob_store_id'] = $this->store_info['store_id'];
        if (preg_match('/^\d+$/',$_POST['ob_id'])) {
            $condition['ob_id'] = intval($_POST['ob_id']);
        }
        if (is_numeric($_POST['bill_state'])) {
            $condition['ob_state'] = intval($_POST['bill_state']);
        }
        $bill_list = $model_bill->getOrderBillList($condition, '*', $this->pagesize, 'ob_state asc,ob_id asc');
        output_data(array('bill_list' => $bill_list), mobile_page($model_bill->page_info));
    }
}
?>
