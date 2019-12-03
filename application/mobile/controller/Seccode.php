<?php

namespace app\mobile\controller;

use think\captcha\Captcha;

class Seccode {


    /**
     * 产生验证码
     * type 验证码传入可标识的信息  
     */
    public function makecode() {
        $config =    [
            // 验证码字体大小
            'fontSize'    => 40,
            // 验证码位数
            'length'      =>  4,
            // 关闭验证码杂点
            'useNoise'    =>    false,
        ];
        $captcha = new Captcha($config);
        return $captcha->entry();
    }

    /**
     * AJAX验证
     *
     */
    public function check() {
        $captch=input('param.captcha');
        if(captcha_check($captch)){
           output_data(['state'=>TRUE]);
        } else {
            output_error('验证码错误');
        }
    }

}