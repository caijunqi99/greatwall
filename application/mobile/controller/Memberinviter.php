<?php

namespace app\mobile\controller;

//use think\Lang;

class Memberinviter extends MobileMember {

    public function _initialize() {
        parent::_initialize();
        //Lang::load(APP_PATH . 'mobile\lang\zh-cn\shop.lang.php');
    }

    /*
     * 首页显示
     */

    public function index() {
        $member_info = $this->member_info;
        $wx_error_msg='';
  
        if(!file_exists(BASE_UPLOAD_PATH . '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_weixin.png')){
            $config = model('wechat')->getOneWxconfig();
            $wechat=new WechatApi($config);
            $expire_time = $config['expires_in'];
            if($expire_time > time()){
                //有效期内
                $wechat->access_token_= $config['access_token'];
            }else{
                $access_token=$wechat->checkAuth();
                $web_expires = time() + 7000; // 提前200秒过期
                db('wxconfig')->where(array('id'=>$config['id']))->update(array('access_token'=>$access_token,'expires_in'=>$web_expires));
            }
            $return=$wechat->getQRCode($member_info['member_id'], 1);
            if($return){
                $refer_qrcode_weixin=$wechat->getQRUrl($return['ticket']);
                copy($refer_qrcode_weixin,BASE_UPLOAD_PATH . '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_weixin.png');
            }else{
                $wx_error_msg=$wechat->errMsg;
            }
        }else{
            $refer_qrcode_weixin=UPLOAD_SITE_URL. '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_weixin.png';
        }

        //二维码
        $qrcode_path = BASE_UPLOAD_PATH . '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '.png';
        $refer_qrcode_logo = BASE_UPLOAD_PATH . '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_poster.png';
        if (!file_exists($qrcode_path)) {
            import('qrcode.phpqrcode', EXTEND_PATH);
            \QRcode::png(WAP_SITE_URL . '/tmpl/member/register.html?inviter_id=' . $member_info['member_id'], $qrcode_path);
        }
        $qrcode = imagecreatefromstring(file_get_contents($qrcode_path));
        //背景图片
        $inviter_back = db('config')->where('code', 'inviter_back')->value('value');
        $inviter_back = imagecreatefromstring(file_get_contents(UPLOAD_SITE_URL . DS . ATTACH_COMMON . DS . $inviter_back));


        $QR_width = imagesx($qrcode);
        $QR_height = imagesy($qrcode);
        imagecopyresampled($inviter_back, $qrcode, 55, 270, 0, 0, 250, 250, $QR_width, $QR_height);
        $portrait = imagecreatefromstring(file_get_contents(get_member_avatar_for_id($member_info['member_id'])));

        $QR_width2 = imagesx($portrait);
        $QR_height2 = imagesy($portrait);
        imagecopyresampled($inviter_back, $portrait, 45, 120, 0, 0, 50, 50, $QR_width2, $QR_height2);

        //此处是给图片载入文字
        $text = '我是'.$member_info['member_name'];
        $textcolor = imagecolorallocate($inviter_back, 255, 255, 255);
        imagefttext($inviter_back, 16, 0, 120, 150, $textcolor, ROOT_PATH . '/public/font/msyh.ttf', mb_convert_encoding($text, "html-entities", "utf-8"));
        $code = $member_info['inviter_code'];
        $textcolors = imagecolorallocate($inviter_back, 0, 0, 0);
        imagefttext($inviter_back, 16, 0, 140, 265, $textcolors, ROOT_PATH . '/public/font/msyh.ttf', mb_convert_encoding($code, "html-entities", "utf-8"));
        imagepng($inviter_back, $refer_qrcode_logo);
        output_data(array('refer_qrcode_logo' => UPLOAD_SITE_URL. '/' . ATTACH_INVITER . '/' . $member_info['member_id'] . '_poster.png','inviter_url'=>MOBILE_SITE_URL.'/r/m?q=' . $member_info['inviter_code'],'refer_qrcode_weixin'=>$refer_qrcode_weixin,'wx_error_msg'=>$wx_error_msg));
    }
public function user(){
        $model_member = Model('member');
        $conditions=array('inviter_id'=>$this->member_info['member_id']);
        if(input('param.member_name')){
            $conditions['member_name']=array('like','%'.input('param.member_name').'%');
        }
        $list=$model_member->getMemberList($conditions, 'member_id,member_name,member_avatar,member_add_time,member_login_time', 10, 'member_id desc');
        if(is_array($list)){
            foreach($list as $key => $val){
                $list[$key]['member_avatar'] = getMemberAvatar($val['member_avatar']).'?'.microtime();
                $list[$key]['member_add_time'] = $val['member_add_time'] ? date('Y-m-d H:i:s', $val['member_add_time']) : '';
                $list[$key]['member_login_time'] = $val['member_login_time'] ? date('Y-m-d H:i:s', $val['member_login_time']) : '';
                //该会员的2级内推荐会员
                $list[$key]['inviter']=array();
                $inviter_1=db('member')->where('inviter_id',$val['member_id'])->field('member_id,member_name')->find();
                if($inviter_1){
                    $list[$key]['inviters'][]=$inviter_1['member_name'];
                    $inviter_2=db('member')->where('inviter_id',$inviter_1['member_id'])->field('member_id,member_name')->find();
                    if($inviter_2){
                        $list[$key]['inviters'][]=$inviter_2['member_name'];
                    }
                }
                
            }
        }
        output_data(array('list' => $list), mobile_page($model_member->page_info));
    }
    
        public function order(){

        $conditions=array('orderinviter_member_id'=> $this->member_info['member_id']);
        if(input('param.orderinviter_order_sn')){
            $conditions['orderinviter_order_sn']=array('like','%'.input('param.orderinviter_order_sn').'%');
        }
        $list = db('orderinviter')->where($conditions)->order('orderinviter_id desc')->paginate(10,false,['query' => request()->param()]);
        output_data(array('list' => $list->items()), mobile_page($list));
    }
}
