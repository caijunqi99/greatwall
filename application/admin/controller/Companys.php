<?php

namespace app\admin\controller;

use think\Lang;

/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Companys extends AdminControl {

    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/member.lang.php');
    }

    public function company() {
        $company_model = model('company');
        $condition = array();
        $condition['is_del'] = 0;
        $search_field_value = input('search_field_value');
        $search_field_name = input('search_field_name');
        if ($search_field_value != '') {
            switch ($search_field_name) {
                case 'member_mobile':
                    $condition['member_mobile'] = array('like', '%' . trim($search_field_value) . '%');
                    break;
                case 'member_areainfo':
                    $condition['member_areainfo'] = array('like', '%' . trim($search_field_value) . '%');
                    break;
            }
        }
        $company_level = input("company_level");
        if($company_level){
            $condition['company_level'] = $company_level;
        }
        //排序
        $order = trim(input('param.search_sort'));
        if (!in_array($order,array('member_logintime desc','member_loginnum desc'))) {
            $order = 'member_id desc';
        }
        $member_list = $company_model->getCompanyList($condition, '*', 10, $order);

        $this->assign('search_sort', $order);
        $this->assign('member_list', $member_list);
        $this->assign('show_page', $company_model->page_info->render());
        $this->assign('search_field_name', trim($search_field_name));
        $this->assign('search_field_value', trim($search_field_value));
        $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件

        $this->setAdminCurItem('company');
        return $this->fetch();
    }

    public function add() {
        if (!request()->isPost()) {
            return $this->fetch();
        } else {
            $condition['member_mobile'] = input('post.member_phone');
            $member_model = model('member');
            $member_array = $member_model->getMemberInfo($condition);
            $company_model = model('company');
            $data = array(
                'member_id' => $member_array['member_id'],
                'member_mobile' => input('post.member_phone'),
                'company_level' => input('post.member_level'),
                'member_provinceid' => input('post.province_id'),
                'member_cityid' => input('post.city_id'),
                'member_areaid' => input('post.area_id'),
                'member_townid' => input('post.member_townid'),
                'member_villageid' => input('post.member_villageid'),
                'member_areainfo' => input('post.member_areaino'),
                'company_addtime' => TIMESTAMP,
            );
            $result = $company_model->addCompany($data);
            if ($result) {
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->error(lang('member_add_fail'));
            }
        }
    }

    public function del() {
        $company_id = input('param.company_id');
        if (empty($company_id)) {
            $this->error(lang('param_error'));
        }
        $company_model = model('company');
        $result = $company_model->editCompany(array('company_id'=>intval($company_id)),array("is_del"=>1));
        if ($result>=0) {
            dsLayerOpenSuccess(lang('ds_common_op_succ'));
        } else {
            $this->error(lang('ds_common_op_fail'));
        }
    }

    /**
     * ajax操作
     */
    public function ajax() {
        $branch = input('param.branch');

        switch ($branch) {
            /**
             * 验证手机号
             */
            case 'member_phone':
                $member_model = model('member');
                $condition['member_mobile'] = input('param.member_phone');
                $list = $member_model->getMemberInfo($condition);
                if (empty($list)) {
                    echo 'false';
                    exit;
                } else {
                    echo 'true';
                    exit;
                }
                break;
        }
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'company',
                'text' => lang('ds_manage'),
                'url' => url('Companys/company')
            ),
        );
        if (request()->action() == 'add' || request()->action() == 'company') {
            $menu_array[] = array(
                'name' => 'add',
                'text' => lang('ds_add'),
                'url' => "javascript:dsLayerOpen('".url('Companys/add')."','".lang('ds_add')."')"
            );
        }
        return $menu_array;
    }

}

?>
