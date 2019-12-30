<?php

namespace app\mobile\controller;

use think\Lang;
use process\Process;

class College extends MobileMall
{

    public function _initialize()
    {
        parent::_initialize();
        Lang::load(APP_PATH . 'mobile\lang\zh-cn\login.lang.php');
    }

    /*
     *商学院列表
     */
    public function college() {
        $condition = array();
        $condition['ac_id'] = 8;
        $condition['article_show'] = 1;
        $search_type = input('param.article_type');
        $condition['article_type'] = isset($search_type) ? $search_type:0;
        $article_model = model('article');
        $article = $article_model->getArticleList($condition, 10);
        if($article){
            foreach($article as $k=>$v){
                $article[$k]['article_time'] = date("Y-m-d H:i:s",$v['article_time']);
                $article[$k]['article_pic']=UPLOAD_SITE_URL . '/' . ATTACH_ARTICLE . '/'.$v['article_pic'];
            }
            $logindata = $article;
        }else{
            $logindata = array();
        }
        output_data($logindata);

    }

    /*
     * 商学院详情
     */
    public function detail(){
        $article_id = input('param.article_id');
        if (empty($article_id)) {
            output_error('参数有误');
        }
        $article_model = model('article');
        $condition['article_id'] = $article_id;
        $article = $article_model->getOneArticle($condition);
        if($article){
            $article['article_time'] = date("Y-m-d H:i:s",$article['article_time']);
            $logindata = $article;
            output_data($logindata);
        }else{
            output_error('获取的文章或视频不存在');
        }
    }

}