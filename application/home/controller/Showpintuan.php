<?php
namespace app\home\controller;
use think\Lang;
/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Showpintuan extends BaseMall
{
    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'home/lang/'.config('default_lang').'/pintuan.lang.php');
    }
    
    /**
     * 拼团列表页
     */
    public function index()
    {
        $ppintuan_model = model('ppintuan');
        $condition = array(
            'pintuan_state'=>1,
            'pintuan_starttime' => array('lt', TIMESTAMP),
            'pintuan_end_time' => array('gt', TIMESTAMP),
        );
        $cache_key = 'pintuan' . md5(serialize($condition)) . '-' . intval(input('param.page'));
        $result = rcache($cache_key);
        if (empty($result)) {
            $pintuan_list = $ppintuan_model->getPintuanList($condition, 10, 'pintuan_state desc, pintuan_end_time desc');
            foreach ($pintuan_list as $key => $pintuan) {
                $pintuan_list[$key]['pintuan_image'] = goods_cthumb($pintuan['pintuan_image'], 240);
                $pintuan_list[$key]['pintuan_zhe_price'] = round($pintuan['pintuan_goods_price']*$pintuan['pintuan_zhe']/10,2);
                $pintuan_list[$key]['pintuan_url'] = urlencode(H5_SITE_URL."/home/goodsdetail?goods_id=".$pintuan['pintuan_goods_id']."&pintuan_id=".$pintuan['pintuan_id']);
            }
            $result['pintuan_list'] = $pintuan_list;
            $result['show_page'] = $ppintuan_model->page_info->render();
            wcache($cache_key, $result);
        }
//        halt($result['pintuan_list']);
        $this->assign('pintuan_list', $result['pintuan_list']);
        $this->assign('show_page', $result['show_page']);
        // 当前位置导航
        $this->assign('nav_link_list', array(array('title' => lang('homepage'), 'link' => url('Home/Index/index')),array('title'=>lang('pintuan_list'))));
        //SEO 设置
        $seo = array(
            'html_title'=>config('site_name').'-'.lang('pintuan_list'),
            'seo_keywords'=>lang('pintuan_list'),
            'seo_description'=>lang('pintuan_list'),
        );
        $this->_assign_seo($seo);
        
        return $this->fetch($this->template_dir.'index');
    }
    
    
    
}