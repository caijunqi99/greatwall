<?php

namespace app\mobile\controller;

use think\Lang;
use process\Process;
use think\Log;

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
            output_error('member_id参数有误');
        }
        $member_model = model('member');
        $memberbank_model = model('memberbank');
        $condition = array();
        if ($commit != 1) {
            $condition['member_id'] = $member_id;
            $member_info = $member_model->getMemberInfo($condition);
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
                'member_idcard_image2' => UPLOAD_SITE_URL . "/home/idcard_image/".$member_info['member_idcard_image2'],
                'member_idcard_image3' => UPLOAD_SITE_URL . "/home/idcard_image/".$member_info['member_idcard_image3'],
                'username'             => $bankinfo['memberbank_truename'],
                'member_bankname'      =>$bankinfo['memberbank_name'],
                'member_bankcard'      =>$bankinfo['memberbank_no']
            );
            output_data($logindata);
        }else{
            $member_array = array(
                "member_idcard"     => input('post.idcard'),
                "member_provinceid" => input('post.member_provinceid'),
                "member_cityid"     => input('post.member_cityid'),
                "member_areaid"     => input('post.member_areaid'),
                "member_townid"     => input('post.member_townid'),
                "member_villageid"  => input('post.member_villageid'),
                "member_areainfo"   => input('post.member_areainfo'),
                "member_auth_state" => 1,
            );
            $writeLog = [
                'input' =>input(),
                '_FILES' =>$_FILES
            ];
            Log::write($writeLog);
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
            $result = $member_model->editMember(['member_id'=>$member_id],$member_array);
            if($result){
                $bank_array = array(
                    'memberbank_type' => "bank",
                    "memberbank_truename" => input('post.username'),
                    'memberbank_name' => input('post.member_bankname'),
                    'memberbank_no' => input('post.member_bankcard'),
                    'member_id' => $member_id
                );
                $ret = $memberbank_model->addMemberbank($bank_array);
                if($ret){
                    $logindata = array(
                         'member_id' => $member_id
                    );
                    output_data($logindata);
                }else{
                    output_error('提交认证失败');
                }
            }else{
                output_error('提交认证失败');
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
