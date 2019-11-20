<?php

namespace app\home\controller;


/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Qrcode extends BaseMall {

    public function index() {
       import('qrcode.phpqrcode', EXTEND_PATH);
        $value = htmlspecialchars_decode(input('param.url'));
        $errorCorrectionLevel = "L";
        $matrixPointSize = "4";
        \QRcode::png($value, false, $errorCorrectionLevel, $matrixPointSize,2);
        exit;
    }

}
