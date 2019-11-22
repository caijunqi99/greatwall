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
       
        $adv = model('Appadv')->getAllAppAdv();
        $ids = '';
        foreach ($adv as $key => $v) {
            if($v['adv_type']=='goods')$ids .=$v['adv_typedate'].',';
        }
        $ids= trim($ids,',');
        $condition = [];
        $condition['goods_id'] = ['in',$ids];
        $goods = model('Goods')->getGoodsCommendListByAppadv($condition);
        $goods_ids = array_column($goods, null,'goods_id');
        foreach ($adv as $k => $a) {
            if ($a['adv_type']=='goods') {
                if (!empty($goods_ids[ $adv[$k]['adv_typedate'] ])) {
                    //替换为完整缩略图地址
                    $goods_ids[$adv[$k]['adv_typedate']]['goods_image'] = goods_cthumb($goods_ids[$adv[$k]['adv_typedate']]['goods_image'],240);

                    $adv[$k]['goodsInfo']=$goods_ids[ $adv[$k]['adv_typedate'] ];
                }
            }
        }
        $advtitle = [
            0=>'超值好货-数码精品',
            1=>'热品限时-超值秒杀',
            2=>'天然山货-大自然的馈赠',
            3=>'海鲜大咖-海的味道我知道',
        ];
        $adv = array_group_by($adv,'ap_id');
        $datas = [];
        //轮播图
        $datas['chart']      = $adv[1];
        //促销
        $datas['promotion']  = $adv[2];
        //首页横图广告
        $datas['transverse'] = $adv[4];
        //导航栏目
        $datas['menu']       = $adv[5];
        //折扣栏
        $datas['discount'] = [
            'title '=> $advtitle,
            'date '=> $adv[6],
        ];
        output_data($datas);
    }

    /**
     * 获取首页推荐商品
     * @DateTime 2019-11-12
     * @return   [type]     [description]
     */
    public function getCommendGoods(){
        $limit = input('param.limit')?input('param.limit'):6;
        //获取随机推荐的商品
        $goods=model('goods')->getGoodsCommendListBymall($limit);
        foreach ($goods as $k=>$val){
           $goods[$k]['goods_image']=goods_thumb($val);
        }
        output_data($goods);
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
        $gc_id = intval(input('param.gc_id'));
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
