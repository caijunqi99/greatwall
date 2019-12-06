<?php

namespace app\api\controller;

use think\Controller;

/*
 * 基类
 */

class MobileHome extends Controller
{

    //客户端类型
    protected $client_type_array = array('android', 'wap', 'wechat', 'ios', 'windows', 'jswechat');
    //列表默认分页数
    protected $page = 5;

    public function _initialize()
    {
        parent::_initialize();

        //分页数处理
        $pagesize = intval(input('param.pagesize'));
        if ($pagesize > 0) {
            $this->pagesize = $pagesize;
        }
        else {
            $this->pagesize = 10;
        }
        /*加入配置信息*/
        $config_list = rkcache('config', true);
        config($config_list);
    }

    public function httpGet($url)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 50);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);
        return $res;
    }
}

?>
