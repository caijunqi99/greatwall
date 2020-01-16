<?php

namespace app\mobile\controller;

use think\Lang;

class Payoffline extends MobileMember {
    /**
     *线下汇款信息
     */
    public function remittance(){
        $payment_model = model('payment');
        $payment_code = 'offline';
        $install_payment = $payment_model->getPaymentInfo(array('payment_code' => $payment_code));
        $file_payment = include_once(PLUGINS_PATH . '/payments/' . $install_payment['payment_code'] . '/payment.info.php');
        if(is_array($file_payment['payment_config'])){
            $install_payment_config = unserialize($install_payment['payment_config']);
            unset($install_payment['payment_config']);
            foreach ($file_payment['payment_config'] as $key => $value){
                $offlines[] = isset($install_payment_config[$value['name']])?$install_payment_config[$value['name']]:$value['value'];
            }
        }
        foreach($offlines as $k=>$v){
            if($k==0){
                $offline['name']=$v;
            }elseif($k==1){
                $offline['bank']=$v;
            }elseif($k==2){
                $offline['account']=$v;
            }
        }
        $offline['code']=$this->member_info['inviter_code'];
        output_data($offline);
    }


}

?>
