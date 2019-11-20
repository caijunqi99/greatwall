<?php

namespace app\mobile\controller;

class MobileMall extends MobileHome {

    public function _initialize() {
        parent::_initialize();
    }

    protected function getMemberIdIfExists() {
        $key = input('post.key');
        if (empty($key)) {
            $key = input('param.key');
        }

        $model_mb_user_token = Model('mbusertoken');
        $mb_user_token_info = $model_mb_user_token->getMbusertokenInfoByToken($key);
        if (empty($mb_user_token_info)) {
            return 0;
        }

        return $mb_user_token_info['member_id'];
    }

}

?>
