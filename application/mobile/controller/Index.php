<?php

namespace app\mobile\controller;

use think\Lang;

class Index extends MobileMall {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\index.lang.php');
    }

    /**
     * 首页
     */
    
    public function index() {
        $condition['a.ap_id']=array('in',array('14','13'));
        $condition['n.ap_isuse']='1';
        $data=model('adv')-> mbadvlist($condition,'a.adv_sort desc');
        $adname=array('14'=>'adv_list','13'=>'home2');//预留一部分扩充adv_list/home1/home2/home3/home4
       foreach ($data as $key=>$val) {
           $val['adv_code']=adv_image($val['adv_code']);
           $datas[$adname[$val['ap_id']]][] = $val;
       }
       //获取随机推荐的商品
        $goods=model('goods')->getGoodsCommendList(1,8);
       foreach ($goods as $k=>$val){
           $goods[$k]['goods_image']=goods_thumb($val);
       }
       $datas['goods']=$goods;
        output_data($datas);
    }

    /**
     * android客户端版本号
     */
    public function apk_version() {
        $version = config('mobile_apk_version');
        $url = config('mobile_apk');
        if (empty($version)) {
            $version = '';
        }
        if (empty($url)) {
            $url = '';
        }

        output_data(array('version' => $version, 'url' => $url));
    }

    /**
     * 默认搜索词列表
     */
    public function search_key_list() {
        $list = @explode(',', config('hot_search'));
        if (!$list || !is_array($list)) {
            $list = array();
        }
        if (cookie('hisSearch') != '') {
            $his_search_list = explode('~', cookie('hisSearch'));
        }
        if (!$his_search_list || !is_array($his_search_list)) {
            $his_search_list = array();
        }
        output_data(array('list' => $list, 'his_list' => $his_search_list));
    }

    /**
     * 热门搜索列表
     */
    public function search_hot_info() {
        if (config('rec_search') != '') {
            $rec_search_list = @unserialize(config('rec_search'));
        }
        $rec_search_list = is_array($rec_search_list) ? $rec_search_list : array();
        $result = $rec_search_list[array_rand($rec_search_list)];
        output_data(array('hot_info' => $result ? $result : array()));
    }

    /**
     * 高级搜索
     */
    public function search_adv() {
        $gc_id = intval(input('get.gc_id'));
        $_tmp = array();
        $area_list = Model('area')->getAreaList(array('area_deep' => 1), 'area_id,area_name');
        if (config('contract_allow') == 1) {
            $contract_list = Model('contract')->getContractItemByCache();
            $i = 0;
            foreach ($contract_list as $k => $v) {
                $_tmp[$i]['id'] = $v['cti_id'];
                $_tmp[$i]['name'] = $v['cti_name'];
                $i++;
            }
        }
        output_data(array('area_list' => $area_list ? $area_list : array(), 'contract_list' => $_tmp));
    }

}

?>
