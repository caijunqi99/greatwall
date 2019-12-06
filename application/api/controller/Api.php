<?php
/**
 *第三方api处理
 */
namespace app\api\controller;


class Api extends MobileMall
{
    /**
     * QQ登录
     */
    public function oa_qq()
    {
        if (input('param.step') == 'callback'){
            include (APP_PATH.'mobile/api/qq/oauth/qq_callback.php');
        }else{
            include (APP_PATH.'mobile/api/qq/oauth/qq_login.php');
        }
    }
    /**
     *新浪微博登录
     */
    public function oa_sina(){
        if (input('param.step') == 'callback'){
            include APP_PATH.'mobile/api/sina/callback.php';
        }else{
            include APP_PATH.'mobile/api/sina/index.php';
        }
    }
}