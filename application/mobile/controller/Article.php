<?php

namespace app\mobile\controller;


class Article extends MobileMall
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }
    /**
     * 文章列表
     */
    public function article_list() {
        if(input('param.ac_id') && intval(input('param.ac_id')) > 0) {
            $article_class_model	= Model('articleclass');
            $article_model	= Model('article');
            $condition	= array();

            $child_class_list = $article_class_model->getChildClass(intval(input('param.ac_id')));
            $ac_ids	= array();
            if(!empty($child_class_list) && is_array($child_class_list)){
                foreach ($child_class_list as $v){
                    $ac_ids[]	= $v['ac_id'];
                }
            }
            $ac_ids	= implode(',',$ac_ids);
            $condition['ac_id']	= $ac_ids;
            $condition['article_show']	= '1';

            $article_list = $article_model->getArticleList($condition,'','article_id,ac_id,article_url,article_show,article_sort,article_title,article_time,article_type,article_pic');

            $article_type_name = $this->article_type_name($ac_ids);

            output_data(array('article_list' => $article_list, 'article_type_name'=> $article_type_name));
        }
        else {
            output_error('缺少参数:文章类别编号');
        }
    }

    /**
     * 根据类别编号获取文章类别信息
     */
    private function article_type_name() {
        if(!empty(input('param.ac_id')) && intval(input('param.ac_id')) > 0) {
            $article_class_model = Model('articleclass');
            $article_class = $article_class_model->getOneArticleclass(intval(input('param.ac_id')));
            return ($article_class['ac_name']);
        }
        else {
            return ('缺少参数:文章类别编号');
        }
    }

    /**
     * 单篇文章显示
     */
    public function article_show() {
        $article_model	= Model('article');

        if(!empty(input('param.article_id')) && intval(input('param.article_id')) > 0) {
            $article	= $article_model->getOneArticle(intval(input('param.article_id')));
            p($article);exit;
            if (empty($article)) {
                output_error('文章不存在');
            }
            else {
                output_data($article);
            }
        }
        else {
            output_error('缺少参数:文章编号');
        }
    }
}