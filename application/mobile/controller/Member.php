<?php

namespace app\mobile\controller;

use think\Lang;

class Member extends MobileMember {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'home\lang\zh-cn\member.lang.php');
    }
    
    
    public function index() {
        $member_info = array();

        $member_info['user_name'] = $this->member_info['member_name'];
        $member_info['avator'] = get_member_avatar_for_id($this->member_info['member_id']);
        //$member_info['point'] = $this->member_info['member_points'];
        $member_gradeinfo = Model('member')->getOneMemberGrade(intval($this->member_info['member_exppoints']));

        $member_info['level_name'] = $member_gradeinfo['level_name'];

        $member_info['favorites_store'] = Model('favorites')->getStoreFavoritesCountByMemberId($this->member_info['member_id']);
        $member_info['favorites_goods'] = Model('favorites')->getGoodsFavoritesCountByMemberId($this->member_info['member_id']);
        $member_info['mobile'] = encrypt_show($this->member_info['member_mobile'], 4, 4);
        // 交易提醒
        $model_order = Model('order');
        $member_info['order_nopay_count'] = $model_order->getOrderCountByID('buyer', $this->member_info['member_id'], 'NewCount');
        $member_info['order_noreceipt_count'] = $model_order->getOrderCountByID('buyer', $this->member_info['member_id'], 'PayCount');
        $member_info['order_notakes_count'] = $model_order->getOrderCountByID('buyer', $this->member_info['member_id'], 'SendCount');
        $member_info['order_noeval_count'] = $model_order->getOrderCountByID('buyer', $this->member_info['member_id'], 'EvalCount');
        output_data(array('member_info' => $member_info));
        // 售前退款
//        $condition = array();
//        $condition['buyer_id'] = $this->member_info['member_id'];
//        $condition['refund_state'] = array('lt', 3);
//        $member_info['return'] = Model('refundreturn')->getRefundReturnCount($condition);
    }
    public function wallet() {
        $member_info = array();
        $member_info['user_name'] = $this->member_info['member_name'];
        $member_info['avator'] = get_member_avatar_for_id($this->member_info['member_id']);
        $member_info['member_points'] = $this->member_info['member_points'];
        $member_info['member_points_available'] = $this->member_info['member_points_available'];
        $member_info['available_predeposit'] = $this->member_info['available_predeposit'];
        $member_info['member_transaction'] = $this->member_info['member_transaction'];
        $member_gradeinfo = Model('member')->getOneMemberGrade(intval($this->member_info['member_exppoints']));
        $member_info['level_name'] = $member_gradeinfo['level_name'];
        $member_info['mobile'] = encrypt_show($this->member_info['member_mobile'], 4, 4);
        $member_info['inviter_code'] = $this->member_info['inviter_code'];
        $data['member_id']=$this->member_info['member_id'];
        $data['is_del']=0;
        $company=Model('company')->getCompanyInfo($data);
        $company_level=$company['company_level'];
        if($company_level==1){
            $member_info['company_level']='省级子公司';
        }elseif($company_level==2){
            $member_info['company_level']='市级子公司';
        }elseif($company_level==3){
            $member_info['company_level']='县级子公司';
        }elseif($company_level==4){
            $member_info['company_level']='镇级子公司';
        }elseif($company_level==5){
            $member_info['company_level']='村级子公司';
        }else{
            $member_info['company_level']='0';
        }
        output_data(array('member_info' => $member_info));
    }

    /*
     * 获取可提现金额/银行卡
     * */
    public function my_asset() {
        if(empty($this->member_info['member_paypwd'])){
            output_error('您需要先到个人中心设置支付密码！');
        }else{
            if (config('member_auth')) {
                if ($this->member_info['member_auth_state']==0) {
                    output_error('您需要先到我的钱包申请实名认证！');
                }elseif ($this->member_info['member_auth_state']==1) {
                    output_error('您的实名认证信息正在审核中！');
                }elseif ($this->member_info['member_auth_state']==2) {
                    output_error('您的实名认证信息未通过审核，请重新提交！');
                }
            }
            $fields_arr = array('point', 'available', 'predepoit', 'transaction','redpacket','voucher');
            $fields_str = trim(input('fields'));
            if ($fields_str) {
                $fields_arr = explode(',', $fields_str);
            }
            $member_info = array();
            //最低可提现积分
            $list_config = rkcache('config', true);
            $member_info['withdraw'] = $list_config['withdraw'];//规则
            //冻结积分
            if (in_array('point', $fields_arr)) {
                $member_info['point'] = $this->member_info['member_points'];
            }
            //可用积分
            if (in_array('available', $fields_arr)) {
                $available = $this->member_info['member_points_available'];
                $list_setting = rkcache('config', true);
                $availables=$list_setting['withdraw'];
                $member_info['available']=$available;
                if($available>=$availables) {
                    $member_info['awable']=$available;
                }else{
                    $member_info['awable']=0.00;
                }
                $member_info['commission']=$list_setting['commission'];
            }
            //储值卡
            if (in_array('predepoit', $fields_arr)) {
                $member_info['predepoit'] = $this->member_info['available_predeposit'];
            }
            //交易码
            if (in_array('transaction', $fields_arr)) {
                $member_info['transaction'] = $this->member_info['member_transaction'];
            }
            //银行卡信息
            $member_id = $this->member_info['member_id'];
            $bank_model = Model("memberbank");
            $bank = $bank_model->getMemberbankInfo(array("member_id"=>$member_id,"memberbank_type"=>"bank"));
            $member_info['memberbank_name'] = $bank['memberbank_name'];
            $member_info['memberbank_no'] = $bank['memberbank_no'];
            $member_info['memberbank_truename'] = $bank['memberbank_truename'];
            $member_info['member_mobile'] = $bank['member_mobile'];
            output_data($member_info);
        }
        
    }

    /*
     * 申请提现
    * */
    public function my_withdraw(){
        $amount = input('param.amount');
        if (empty($amount)) {
            output_error('积分参数有误');
        }
        //可用积分验证
        if($amount>$this->member_info['member_points_available']){
            output_error('提现金额超过可用积分');
        }
        //最低提现积分验证
        $list_config = rkcache('config', true);
        $withdraw = $list_config['withdraw'];//规则
        if($amount < $withdraw){
            output_error('提现金额未达到最低可提现积分数量');
        }
        //积分扣减
        $member_points_available = $this->member_info['member_points_available'] - $amount;
        $member_model = Model("Member");
        $member_id = $this->member_info['member_id'];
        $member_model->editMember(array("member_id"=>$member_id),array("member_points_available"=>$member_points_available));
        //添加积分日志
        $pointslog_model = Model("points");
        $data = array(
            "pl_memberid" => $member_id,
            "pl_membername" => $this->member_info['member_name'],
            "pl_pointsav" => "-".$amount,
            "pl_addtime" => TIMESTAMP,
            "pl_desc" => "用户申请提现，提现积分为".$amount,
            "pl_stage" => "withdraw",
        );
        $pointslog_model->addPointslog($data);
        //提现管理
        $commission = input('param.commission');//手续费比例
        $predeposit_model = model('predeposit');
        $datacash = array();
        $pdc_sn = makePaySn(session('member_id'));
        $datacash['pdc_sn'] = $pdc_sn;
        $datacash['pdc_member_id'] = 1;
        $datacash['pdc_member_name'] = $this->member_info['member_name'];
        $datacash['pdc_amount'] = $amount-$amount*$commission/100;
        $datacash['pdc_bank_name'] = input('param.memberbank_name');
        $datacash['pdc_bank_no'] = input('param.memberbank_no');
        $datacash['pdc_bank_user'] = input('param.memberbank_truename');
        $datacash['pdc_addtime'] = TIMESTAMP;
        $datacash['pdc_payment_state'] = 0;
        $predeposit_model->addPdcash($datacash);
        $cashinfo = array(
            "pdc_amount" =>$amount-$amount*$commission/100,
            "pdc_bank_name" => input('param.memberbank_name'),"pdc_bank_no"=>input('param.memberbank_no'),"pdc_bank_user"=>input('param.memberbank_truename')
        );
        output_data($cashinfo);
    }

    //用户头像上传
    public function upload()
    {
        $member_id = $this->member_info['member_id'];
        //上传图片
        if (!empty($_FILES['pic']['tmp_name'])) {
            $file_object= request()->file('pic');
            $base_url=BASE_UPLOAD_PATH . '/' . ATTACH_AVATAR . '/';
            //$ext = strtolower(pathinfo($_FILES['pic']['name'], PATHINFO_EXTENSION));
            $file_name='avatar_'.$member_id.".jpg";
            $info = $file_object->rule('uniqid')->validate(['ext' => ALLOW_IMG_EXT])->move($base_url,$file_name);
            //print_r($info->getFilename());exit;
            if ($info) {
                model('member')->editMember(array('member_id' => $member_id), array('member_avatar' => $file_name));
                $img=UPLOAD_SITE_URL . '/' . ATTACH_AVATAR .'/'.$file_name;
                output_data(array('result' => 1,'avatar'=>$img));
            }else{
                output_error($file_object->getError().'a');
            }
        } else {
            output_error(lang('upload_failed_replace_pictures').'A');
        }

    }

    /*
     * 个人资料编辑
     * */
    public function my_edit(){
        $member_model = Model("member");
        $memberinfo = $this->member_info;
        $member_id = $memberinfo['member_id'];
        if(input('param.commit')==1){
            $data = array();
            if(input('param.member_name')){
                $data['member_name'] = input('param.member_name');
            }
            if(input('param.member_sex')){
                $data['member_sex'] = input('param.member_sex');
            }
            if(input('param.member_email')){
                $data['member_email'] = input('param.member_email');
            }
            $member_model->editMember(array("member_id"=>$member_id),$data);
            $memberdata = array(
                "member_msg" => "修改信息"
            );
            output_data($memberdata);
        }
        $memberinfo['member_avatar'] = UPLOAD_SITE_URL . "/home/avatar/".$memberinfo['member_avatar'];
        output_data($memberinfo);

    }

    /*
    * 获取推荐下级信息
    * */
    public function inviter(){
        $member_id = $this->member_info['member_id'];
        if (empty($member_id)) {
            output_error('member_id参数有误');
        }
        $member_model = model('member');
        //一代
        $member_list = $member_model->getMemberList(array("inviter_id"=>$member_id), '*');
        if(!empty($member_list)){
            foreach($member_list as $k=>$v){
                $member_inviterids[] = $v['member_id'];
                $member_list[$k]['level'] = "一代";
                $member_list[$k]['member_addtime'] = date("Y-m-d H:i:s",$v['member_addtime']);
            }
            $member_inviterids = implode(",",$member_inviterids);
            //二代
            $cond = array();
            $cond['inviter_id'] = ['in',$member_inviterids];
            $member_list_two = $member_model->getMemberList($cond);
            foreach($member_list_two as $i=>$t){
                $member_list_two[$i]["level"] = "二代";
                $member_list_two[$i]["member_addtime"] = date("Y-m-d H:i:s",$t['member_addtime']);
            }
            $countOne = count($member_list,COUNT_NORMAL);//直推人数
            $countTwo = count($member_list_two,COUNT_NORMAL);
            $allcount = $countOne+$countTwo;
            $member_info = $member_model->getMemberInfo(array("member_id"=>$member_id));
            $member_list=array_merge($member_list,$member_list_two);
            
        }else{
            $member_list = [];
            $countOne = 0 ;
            $allcount = 0 ;
        }
        $inviterdata = array(
                'datainfo' =>$member_list,
                'countOne'=>$countOne,
                'countAll'=>$allcount,
                'inviterlink'=>$this->member_info['inviter_code']
            );
        output_data($inviterdata);
    }

}

?>
