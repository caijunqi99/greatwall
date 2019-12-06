<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/16
 * Time: 20:35
 */

namespace app\mobile\controller;


class R extends MobileMall
{
    public function M(){
        $inviter_code = input('get.q');
        $member_model = Model("member");
        $member_info = $member_model->getMemberInfo(array("inviter_code"=>$inviter_code));
        $path = WAP_SITE_URL.'/tmpl/member/register.html?inviter_id=';
        header("Location: ".$path.$member_info['member_id']);
    }
}