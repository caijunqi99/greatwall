<?php

namespace app\mobile\controller;

use think\Lang;

class Member extends MobileMember {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\member.lang.php');
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

    public function my_asset() {
        $fields_arr = array('point', 'available', 'predepoit', 'transaction','redpacket','voucher');
        $fields_str = trim(input('fields'));
        if ($fields_str) {
            $fields_arr = explode(',', $fields_str);
        }
        $member_info = array();
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
        output_data($member_info);
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
                output_data($file_object->getError());
            }
        } else {
            output_data(lang('upload_failed_replace_pictures'));
        }

    }

    /*
    * 获取推荐下级信息
    * */
    public function inviter(){
        $member_id = input('param.member_id');
        if (empty($member_id)) {
            $this->error(lang('param_error'));
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
            $inviterdata = array(
                'datainfo' =>$member_list,'countOne'=>$countOne,'countAll'=>$allcount,'inviterlink'=>$member_info['inviter_code']
            );

            output_data($inviterdata);
        }
        p($countTwo);die;
    }

}

?>
