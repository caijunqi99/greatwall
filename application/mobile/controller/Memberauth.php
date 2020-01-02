<?php

namespace app\mobile\controller;

use think\Lang;
use process\Process;
// use think\Log;

class Memberauth extends MobileMall
{

    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\login.lang.php');
    }

    /**
     * 实名认证
     */
    public function auth()
    {
        $member_id = input('param.member_id');
        $commit = input('param.commit');
        if (empty($member_id)) {
            output_error('参数有误');
        }
        $member_model = model('member');
        $memberbank_model = model('memberbank');
        $condition = array();
        $condition['member_id'] = $member_id;
        $member_info = $member_model->getMemberInfo($condition);
        if (!$member_info) output_error('没有此用户信息！');
        if ($commit != 1) {
            
            $bankinfo = $memberbank_model -> getMemberbankInfo($condition);
            $logindata = array(
                'member_id'            => $member_info['member_id'],
                'member_auth_state'    => $member_info['member_auth_state'],
                'idcard'               =>$member_info['member_idcard'],
                'member_provinceid'    =>$member_info['member_provinceid'],
                'member_cityid'        =>$member_info['member_cityid'],
                'member_areaid'        =>$member_info['member_areaid'],
                'member_townid'        =>$member_info['member_townid'],
                'member_villageid'     =>$member_info['member_villageid'],
                'member_areainfo'      =>$member_info['member_areainfo'],
                'member_idcard_image2' => UPLOAD_SITE_URL . "/home/idcard_image/".$member_info['member_idcard_image2'].'?t='.time(),
                'member_idcard_image3' => UPLOAD_SITE_URL . "/home/idcard_image/".$member_info['member_idcard_image3'].'?t='.time(),
                'username'             => $bankinfo['memberbank_truename'],
                'member_bankname'      =>$bankinfo['memberbank_name'],
                'member_bankcard'      =>$bankinfo['memberbank_no']
            );
            output_data($logindata);
        }else{
            if ($member_info['member_auth_state']==0 || $member_info['member_auth_state']==2) {
                $idcard = input('post.idcard');
                if (!is_idcard($idcard)) {
                    output_error('请输入正确的身份证号码！');
                }
                //修改个人信息
                $member_array = array(
                    "member_truename"   => $member_info['member_truename']?$member_info['member_truename']:input('post.username'),
                    "member_idcard"     => $idcard,
                    "member_provinceid" => input('post.member_provinceid'),
                    "member_cityid"     => input('post.member_cityid'),
                    "member_areaid"     => input('post.member_areaid'),
                    "member_townid"     => input('post.member_townid'),
                    "member_villageid"  => input('post.member_villageid'),
                    "member_areainfo"   => input('post.member_areainfo'),
                    "member_auth_state" => 1,
                );
                //上传身份证图
                if ($_FILES) {
                    $files['member_idcard_image2'] = isset($_FILES['member_idcard_image2']['name'])?$_FILES['member_idcard_image2']['name']:'';
                    $files['member_idcard_image3'] = isset($_FILES['member_idcard_image3']['name'])?$_FILES['member_idcard_image3']['name']:'';
                    $upload = $this->img($files,$member_id);
                    if (!empty($upload['member_idcard_image2'])) {
                        $member_array['member_idcard_image2'] = $upload['member_idcard_image2'];
                    }
                    if (!empty($upload['member_idcard_image3'])) {
                        $member_array['member_idcard_image3'] = $upload['member_idcard_image3'];
                    }
                }
                //编辑个人信息
                $result = $member_model->editMember(['member_id'=>$member_id],$member_array);
                if($result){
                    $bank_array = array(
                        'memberbank_type' => "bank",
                        "memberbank_truename" => input('post.username'),
                        'memberbank_name' => input('post.member_bankname'),
                        'memberbank_no' => input('post.member_bankcard'),
                        'member_id' => $member_id
                    );
                    if ($memberbank_model->getMemberbankCount( ['member_id'=>$member_id] ) ) {
                        $memberbank_model->editMemberbank($bank_array,['member_id'=>$member_id]);
                    }else{
                        $memberbank_model->addMemberbank($bank_array);    
                    }
                    
                    $logindata = array(
                         'member_id' => $member_id
                    );
                    output_data($logindata);
                }else{
                    output_error('提交认证失败');
                }
            }elseif ($member_info['member_auth_state']==1) {
                output_error('信息审核中！！！');
            }else{
                output_error('已通过实名信息认证，不能重复提交！');
            }
        }

    }

    //上传图片
    public function img($files,$member_id){
        $upload_file = BASE_UPLOAD_PATH . DS ."home/idcard_image";
        $upload = [];
        if (!empty($files['member_idcard_image2'])) {
            $file = request()->file('member_idcard_image2');
            $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, $member_id.'_idcard_z');
            if ($info) {
                $upload['member_idcard_image2'] = $info->getFilename();
            } else {
                // 上传失败获取错误信息
                output_error($file->getError());
            }
        }
        if (!empty($files['member_idcard_image3'])) {
            $file = request()->file('member_idcard_image3');
            $info = $file->validate(['ext'=>ALLOW_IMG_EXT])->move($upload_file, $member_id.'_idcard_f');
            if ($info) {
                $upload['member_idcard_image3'] = $info->getFilename();
            } else {
                // 上传失败获取错误信息
                output_error($file->getError());
            }
        }
        return $upload;
    }

}

?>
