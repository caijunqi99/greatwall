<?php

namespace app\mobile\controller;

use think\Lang;
use think\Log;

class Version extends MobileMall {

    public function _initialize() {
        parent::_initialize();
    }

    /**
     * 获取版本号
     * @DateTime 2019-12-12
     */
    public function GetVersion(){
        $nowVersion = input('param.version_num');
        $type = intval(input('param.type',1));
        $condition = [];
        $condition['type'] = $type;
        $channel = db('version_update')->where($condition)->order('id DESC')->find();
        $isupdate = 0;
        if (!empty($channel)) {
            $isupdate =  versionCompare($nowVersion,$channel['version_num']);
        }
        unset($channel['id']);
        $channel['is_update'] =$isupdate;
        output_data($channel);
    }


}

?>
