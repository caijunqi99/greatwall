<?php
namespace app\home\controller;
use think\Lang;
/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Showbargain extends BaseMall
{
    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/bargain.lang.php');
    }
    
    /**
     * 砍价列表页
     */
    public function index()
    {
        $pbargain_model = model('pbargain');
        $condition = array();
        $cache_key = 'bargain' . md5(serialize($condition)) . '-' . intval(input('param.page'));
        $result = rcache($cache_key);
        if (empty($result)) {
        $bargain_list = $pbargain_model->getOnlineBargainList($condition, 10);
            foreach ($bargain_list as $key => $bargain) {
                $bargain_list[$key]['bargain_goods_image_url'] = goods_cthumb($bargain['bargain_goods_image'], 480, $bargain['store_id']);
                $bargain_list[$key]['bargain_url'] = urlencode(H5_SITE_URL."/home/goodsdetail?goods_id=".$bargain['bargain_goods_id']."&bargain_id=".$bargain['bargain_id']);
            }
            $result['bargain_list'] = $bargain_list;
            $result['show_page'] = $pbargain_model->page_info->render();
            wcache($cache_key, $result);
        }
//        halt($result['bargain_list']);
        $this->assign('bargain_list', $result['bargain_list']);
        $this->assign('show_page', $result['show_page']);
        // 当前位置导航
        $this->assign('nav_link_list', array(array('title' => lang('homepage'), 'link' => url('Home/Index/index')),array('title'=>lang('bargain_list'))));
        //SEO 设置
        $seo = array(
            'html_title'=>config('site_name').'-'.lang('bargain_list'),
            'seo_keywords'=>lang('bargain_list'),
            'seo_description'=>lang('bargain_list'),
        );
        $this->_assign_seo($seo);
        
        return $this->fetch($this->template_dir.'index');
    }
    
}