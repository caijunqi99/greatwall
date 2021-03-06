<?php


namespace app\mobile\controller;


class Membervoucher extends MobileMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 代金卷列表
     */
    public function voucher_list()
    {
        $model_voucher = Model('voucher');
        $voucher_state = isset($_POST['voucher_state']) ? $_POST['voucher_state'] : '';
        $voucher_list = $model_voucher->getMemberVoucherList($this->member_info['member_id'], $voucher_state, $this->pagesize);
        output_data(array('voucher_list' => $voucher_list), mobile_page($model_voucher->page_info));
    }

    /*代金券领取*/
    public function voucher_point()
    {
        $vid = intval($_POST['tid']);

        if ($vid <= 0){
            output_error('领取失败');
        }

        $model_voucher = Model('voucher');
        //验证是否可以兑换代金券
        $data = $model_voucher->getCanChangeTemplateInfo($vid,$this->member_info['member_id'],$this->member_info['store_id']);
        if ($data['state'] == false){
            output_error($data['msg']);
        }
        //添加代金券信息
        $data = $model_voucher->exchangeVoucher($data['info'],$this->member_info['member_id'],$this->member_info['member_name']);
        if ($data['state'] == true){
            output_data('1');
        } else {
            output_error('领取失败');
        }
    }


}