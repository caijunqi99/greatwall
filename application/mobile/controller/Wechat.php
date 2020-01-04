<?php
/**
 * 公众号行为处理
 */

namespace app\mobile\controller;
use app\mobile\controller\WechatApi;
use think\Log;

class Wechat
{
    public $type;
    public $wxid;
    public $data;
    public $weixin;

    public function index()
    {
        //获取配置信息
        $wxConfig = model('wechat')->getOneWxconfig();
        $this->weixin = new WechatApi($wxConfig);
        $this->weixin->valid();
        $this->type = $this->weixin->getRev()->getRevType();  //获取消息类型MsgType
        $this->wxid = $this->weixin->getRev()->getRevFrom();  //获取消息类型MsgId
        $this->data = $this->weixin->getRevData();            //把获取的消息进行转码
        $reMsg = '';

        switch ($this->type) {
            //接收普通消息-文本消息
            case 'text':
                $content = $this->weixin->getRev()->getRevContent();
                break;
            //接收事件推送 事件类型，subscribe(订阅)、unsubscribe(取消订阅)
            case 'event':
                $event = $this->weixin->getRev()->getRevEvent();
                $content = json_encode($event);
                break;
            //接收普通消息-图片消息
            case 'image':
                $content = json_encode($this->weixin->getRev()->getRevPic());
                $reMsg = "图片很美！";
                break;
            default:
                $reMsg = '未识别信息';
        }
        /**
         *处理事件
         */
        if (!empty($reMsg)) {
            echo $this->weixin->text($reMsg)->reply();
            exit;
        }
        //一.接收事件推送
        if ($this->type == 'event') {
            //1.订阅(关注)事件
            if (isset($event['event']) && $event['event'] == 'subscribe') {
                $welcome = '欢迎关注';
                if($event['key']){
                    $qrscene=explode("qrscene_", $event['key']);
                    $inviter_id=intval($qrscene[1]);
                    $config = model('wechat')->WxConfig();
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
                    $userinfo=$wechat->getwxUserInfo($this->wxid);
                    $res=array(
                        'openid' =>$this->wxid,
                        'unionid' =>$userinfo['unionid'],
                        'nickName' =>$userinfo['nickname'],
                        'avatarUrl' =>$userinfo['headimgurl'],
                        'inviter_id' =>$inviter_id
                    );
                    $this->register($res);
                }
                echo $this->weixin->text($welcome)->reply();
                exit;
            }

            //2.扫码已关注
            if (isset($event['event']) && $event['event'] == 'SCAN') {
                $welcome = '已关注';
                echo $this->weixin->text($welcome)->reply(); 
                exit;
            }

            //4.点击菜单拉取消息时的事件推送
            if($event['event'] == 'CLICK'){
                $click=$event['key'];
                switch ($click) {
                    case "commend": //店铺推荐商品
                    case "hot":  //点击率商品
                    case "sale": //销售量
                    case "colleect": //收藏量
                      $reMsg = $this->getGoods($click);
                    if(!empty($reMsg)) {
                        $this->MsgTypeNews($reMsg);
                    }else {
                        echo $this->weixin->text("success")->reply();
                        exit;
                    }
                    break;
                    //{后续可待添加}
                    default :
                        echo $this->weixin->text("未定义此菜单事件{$click}")->reply();
                        exit;
                }
            }
        }

        //二.文本消息(关键字回复/商品显示)
        if ($this->type == 'text') {
            //处理关键字
            $this->MsgTypeText($content);

            //处理商品的情况
            $reMsg = $this->getGoodsByKey($content);
            if(!empty($reMsg)) {
                $this->MsgTypeNews($reMsg);
            }
            /*处理其他输入文字*/
            echo $this->weixin->text("抱歉，暂时无法对您的输入作出处理。")->reply();
            exit;
        }



    }
    private function register($user_info) {
        $member = array();
        $unionid = $user_info['unionid'];
        $nickname = $user_info['nickName'];
        if (!empty($unionid) && !db('member')->where('member_wxopenid',$user_info['openid'])->value('member_id')) {
            $rand = rand(100, 899);
            if (empty($nickname))
                $nickname = 'name_' . $rand;
            if (strlen($nickname) < 3)
                $nickname = $nickname . $rand;
            $member_name = $nickname;
            $model_member = Model('member');
            $member_info = $model_member->getMemberInfo(array('member_name' => $member_name));
            $password = rand(100000, 999999);

            $member['member_password'] = $password;
            $member['member_email'] = '';
            $member['weixin_unionid'] = $unionid;
            $member['member_wxopenid'] = $user_info['openid'];

            $weixin_info = array();
            $weixin_info['unionid'] = $user_info['unionid'];
            $weixin_info['nickname'] = $user_info['nickName'];
            $weixin_info['openid'] = $user_info['openid'];
            $member['weixin_info'] = serialize($weixin_info);
            $member['inviter_id']=$user_info['inviter_id'];
            if (empty($member_info)) {
                $member['member_name'] = $member_name;
                $result = $model_member->addMember($member);
            } else {
                for ($i = 1; $i < 999; $i++) {
                    $rand += $i;
                    $member_name = $nickname . $rand;
                    $member_info = $model_member->getMemberInfo(array('member_name' => $member_name));
                    if (empty($member_info)) {//查询为空表示当前会员名可用
                        $member['member_name'] = $member_name;
                        $result = $model_member->addMember($member);
                        break;
                    }
                }
            }
            $headimgurl = $user_info['avatarUrl']; //用户头像，最后一个数值代表正方形头像大小（有0、46、64、96、132数值可选，0代表640*640正方形头像）
            $headimgurl = substr($headimgurl, 0, -1) . '132';
            $avatar = @copy($headimgurl, BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . "/avatar_$result.jpg");
            if ($avatar) {
                $model_member->editMember(array('member_id' => $result), array('member_avatar' => "avatar_$result.jpg"));
            }
            $member = $model_member->getMemberInfo(array('member_name' => $member_name));
        }
        return $member;
    }

    /**
    *文本格式消息回复
     */
    private function MsgTypeText($content)
    {
        //先处理是关键字的情况
        $value = $this->keywordsReply($content);
        if (!empty($value)) {
            echo $this->weixin->text($value['text'])->reply();
            exit;
        }
    }


    /**商品图文回复*/
    private function MsgTypeNews($reMsg){
            $k = 0;
            foreach ($reMsg as $v) {
                $newsData[$k]['Title'] = $v['goods_name'];
                $newsData[$k]['Description'] = strip_tags($v['goods_name']);
                $newsData[$k]['PicUrl'] = goods_cthumb($v['goods_image']);
                $newsData[$k]['Url'] = WAP_SITE_URL . '/tmpl/product_detail.html?goods_id='.$v['goods_id'];
                $k++;
            }
            echo $this->weixin->news($newsData)->reply();
            exit;
    }

    /**
     *关键字回复信息
     */
    public function keywordsReply($content)
    {
        //关键字查询
        $where = "k.keyword = '{$content}'";
        $value = model('wechat')->keyText($field = 't.text', $where);
        return $value;
    }

    /**关键字商品信息*/
    public function getGoodsByKey($key)
    {
        $condi = "(goods_name like '%{$key}%' or goods_advword like '%{$key}%' or store_name like '%{$key}%')";
        $condi .= " and goods_state = 1 and goods_verify = 1";
        $res=db('goods')->where($condi)->limit(4)->field('goods_id,goods_name,goods_image')->select();
        $res=ds_changeArraykey($res,'goods_id');
        return $res;
    }

    /**菜单事件商品信息*/
    public function getGoods($type){
        //条件
        //后续可待添加
        $types=array('hot'=>'goods_click','sale'=>'goods_salenum','collect'=>'goods_collect','commend'=>'goods_commend');
        $condition = $types[$type].' DESC';
        $where = "goods_state = 1 and goods_verify = 1";
        $res = db('goods')->field('goods_id,goods_name,goods_image')->where($where)->limit(4)->order($condition)->select();
        $res=ds_changeArraykey($res,'goods_id');
        return $res;
    }
}