<?php

namespace app\mobile\controller;

use think\Lang;

class Sellertaobaoapi extends MobileSeller {

    public function _initialize() {
        parent::_initialize();
    }

    public function get_taobao_app_key() {
        $taobao_app_key = "";
        if (config('taobao_api_isuse')) {
            $taobao_app_key = config('taobao_app_key');
        }
        output_data(array('taobao_app_key' => $taobao_app_key));
    }

    public function get_taobao_sign() {
        $taobao_sign = "";
        $taobao_secret_key = config('taobao_secret_key');
        if (config('taobao_api_isuse')) {
            $taobao_sign = md5($taobao_secret_key . $_POST['sign_string'] . $taobao_secret_key);
        }
        output_data(array('taobao_sign' => $taobao_sign));
    }

    public function get_taobao_session_key() {
        $taobao_session_key = "";

        if (config('taobao_api_isuse')) {
            $param = array();
            $param['client_id'] = config('taobao_app_key');
            $param['client_secret'] = config('taobao_secret_key');
            $param['grant_type'] = 'authorization_code';
            $param['code'] = trim($_POST['auth_code']);
            $param['redirect_uri'] = "urn:ietf:wg:oauth:2.0:oob";

            $result = http_post('https://oauth.taobao.com/token', $param);
            if ($result) {
                $result = json_decode($result);
                if (!empty($result->access_token)) {
                    $taobao_session_key = $result->access_token;
                }
            }
        }

        output_data(array('taobao_session_key' => $taobao_session_key));
    }

}

?>
