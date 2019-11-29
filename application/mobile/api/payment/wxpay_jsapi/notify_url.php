<?php
/**
 * 微信支付通知地址
 */

$_GET['act'] = 'payment';
$_GET['op'] = 'notify';
$_GET['payment_code'] = 'wxpay_jsapi';

require __DIR__ . '/../../../index.php';
