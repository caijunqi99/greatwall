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
                //修改个人信息
                $member_array = array(
                    "member_truename"   => input('post.username')?input('post.username'):$member_info['member_truename'],
                    "member_idcard"     => $idcard,
                    "member_provinceid" => input('post.member_provinceid'),
                    "member_cityid"     => input('post.member_cityid'),
                    "member_areaid"     => input('post.member_areaid'),
                    "member_townid"     => input('post.member_townid'),
                    "member_villageid"  => input('post.member_villageid'),
                    "member_areainfo"   => input('post.member_areainfo'),
                    "member_auth_state" => 1,
                );
                
                $IdCardValidate = [];
                //上传身份证图
                if ($_FILES) {
                    $files['member_idcard_image2'] = isset($_FILES['member_idcard_image2']['name'])?$_FILES['member_idcard_image2']['name']:'';
                    $files['member_idcard_image3'] = isset($_FILES['member_idcard_image3']['name'])?$_FILES['member_idcard_image3']['name']:'';
                    $upload = $this->img($files,$member_id);
                    if (!empty($upload['member_idcard_image2'])) {
                        $member_array['member_idcard_image2'] = $upload['member_idcard_image2'];
                        $IdCardValidate['member_face'] = BASE_UPLOAD_PATH . "/home/idcard_image/".$upload['member_idcard_image2'];
                    }
                    if (!empty($upload['member_idcard_image3'])) {
                        $member_array['member_idcard_image3'] = $upload['member_idcard_image3'];
                        $IdCardValidate['member_back'] = BASE_UPLOAD_PATH . "/home/idcard_image/".$upload['member_idcard_image3'];
                    }
                }

                $AliMethod = new \libr\AliMethod([]);
                $writeLog = [
                    'logName' =>'实名认证',
                    'AliMethod' =>$AliMethod,
                    'IdCardValidate' =>$IdCardValidate
                ];
                //判断身份证正面
                $member_face_validate = $AliMethod->OcrIdcard($IdCardValidate,'face');
                $writeLog['member_face_validate'] = $member_face_validate;
                if ($member_face_validate['code']==100) {
                    output_error($member_face_validate['msg']);
                }
                
                //判断身份证反面
                $member_back_validate = $AliMethod->OcrIdcard($IdCardValidate,'back');
                $writeLog['member_back_validate'] = $member_back_validate;
                if ($member_back_validate['code']==100) {
                    output_error($member_back_validate['msg']);
                }
                

                //使用输入的姓名和身份证号 与身份证上面的姓名和身份证号做对比
                if ($member_array['member_truename'] == $member_face_validate['info']['name'] && strval($idcard) == strval($member_face_validate['info']['idcard'])) {
                    $member_array['member_sex']        = $member_face_validate['info']['sex'] == '男'?0:1;
                    $member_array['member_birthday']   = strtotime($member_face_validate['info']['birth']);
                    $member_array['member_auth_state'] = 3;
                }else{
                    output_error('输入的姓名或身份证号码与正面身份证照片上的信息不符合！');
                }
                //银行卡信息
                $bank_array = array(
                    'memberbank_type'     => "bank",
                    "memberbank_truename" => input('post.username'),
                    'memberbank_name'     => input('post.member_bankname'),
                    'memberbank_no'       => input('post.member_bankcard'),
                    'member_id'           => $member_id,
                    "member_idcard"       => $idcard,
                );
                //银联三要素详情版本验证
                $bankValidate = $AliMethod->bankCheckNew($bank_array);
                $writeLog['bank_array'] = $bank_array;
                $writeLog['bankValidate'] = $bankValidate;
                unset($bank_array['member_idcard']);
                if ($bankValidate['code']==100) {
                    output_error($bankValidate['msg']);
                }
                // Log::write($writeLog);
                //判断是否为真实姓名 -- 如果银联三要素通过，实名验证可跳过
                // $IdValidate = $AliMethod->NidCard($member_array);
                // if ($IdValidate['code']==100) {
                //     output_error($IdValidate['msg']);
                // }
                
                //编辑个人信息
                $result = $member_model->editMember(['member_id'=>$member_id],$member_array);
                if($result){
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
