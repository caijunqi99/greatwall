<?php

namespace app\mobile\controller;

use think\Lang;

class Snsalbum extends MobileMember {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH.'');
    }

    /**
     * 上传图片
     *
     * @param
     * @return
     */
    public function file_upload() {
        $member_id  = $this->member_info['member_id'];
        // 验证图片数量
        $count = db('snsalbumpic')->where(array('member_id'=>$member_id))->count();
        if(config('malbum_max_sum') != 0 && $count >= config('malbum_max_sum')){
            output_error('已经超出允许上传图片数量，不能在上传图片！');
        }
    
        /**
         * 上传图片
         */
        $upload_dir = BASE_UPLOAD_PATH.'/'.ATTACH_MALBUM.'/'.$member_id.'/';
        $file_name = $member_id . '_' . date('YmdHis') . rand(10000, 99999);
        $file = request()->file('file');
        $result=$file->validate(['size'=>config('image_max_filesize')])->move($upload_dir,$file_name);

        if (!$result){
            output_error($file->getError());
        }

        $img_path = $result->getFilename();
        list($width, $height, $type, $attr) = getimagesize($upload_dir.$img_path);
    
        $image = explode('.', $_FILES["file"]["name"]);

        $model_sns_alumb = Model('snsalbum');
        $ac_id = $model_sns_alumb->getSnsAlbumClassDefault($member_id);
        $insert = array();
        $insert['ap_name']      = $image['0'];
        $insert['ac_id']        = $ac_id;
        $insert['ap_cover']     = $img_path;
        $insert['ap_size']      = intval($_FILES['file']['size']);
        $insert['ap_spec']      = $width.'x'.$height;
        $insert['upload_time']  = time();
        $insert['member_id']    = $member_id;
        $result = db('snsalbumpic')->insertGetId($insert);
    
        $data = array();
        $data['file_id'] = $result;
        $data['file_name'] = $img_path;
        $data['origin_file_name'] = $_FILES["file"]["name"];
        $data['file_url'] = snsThumb($img_path, 240);
        output_data($data);
    }
}
?>
