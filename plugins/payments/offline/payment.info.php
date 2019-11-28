<?php

return array(
    'payment_code' => 'offline',
    'payment_name' => '线下汇款',
    'payment_desc' => '线下汇款',
    'payment_is_online' => '1',
    'payment_platform' => 'app', #支付平台 pc h5 app
    'payment_author' => '绿色长城',
    'payment_website' => 'http://www.alipay.com',
    'payment_version' => '1.0',
    'payment_config' => array(
        array('name' => 'account', 'type' => 'text', 'value' => '','desc' => '描述'),
        array('name' => 'bank', 'type' => 'text', 'value' => '', 'desc' => '描述'),
        array('name' => 'accountnum', 'type' => 'text', 'value' => '', 'desc' => '描述'),
    ),
);
?>
