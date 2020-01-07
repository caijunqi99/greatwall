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
            $datas = array(
                'company_level' => input('post.member_level'),
                'member_provinceid' => input('post.province_id'),
                'member_cityid' => input('post.city_id'),
                'member_areaid' => input('post.area_id'),
                'member_townid' => input('post.member_townid'),
                'member_villageid' => input('post.member_villageid'),
                'is_del' =>0
            );
            $res=$company_model->getCompanyInfo($datas);
            if(empty($res)) {
                $result = $company_model->addCompany($data);
                if ($result) {
                    dsLayerOpenSuccess(lang('ds_common_op_succ'));
                } else {
                    $this->error(lang('member_add_fail'));
                }
            }else{
                $this->error(lang('ds_company_error'));
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
                    $company_model=model('company');
                    $condition['is_del']=0;
                    $lists=$company_model->getCompanyInfo($condition);
                    if(empty($lists)) {
                        echo 'true';
                        exit;
                    }else{
                        echo 'false';
                        exit;
                    }
                }
                break;
        }
    }
    /**
     * 子公司添加股东
     */
    public function shareholderadd(){
        if (!request()->isPost()) {
            $company_id = input('param.company_id');
            $this->assign('company_id', $company_id);
            return $this->fetch();
        } else {
            $condition['member_mobile'] = input('post.member_phone');
            $member_model = model('member');
            $member_array = $member_model->getMemberInfo($condition);
            $shareholder_model = model('shareholder');
            $data = array(
                'c_id' => input('post.c_id'),
                'm_id' => $member_array['member_id'],
                's_addtime' => TIMESTAMP,
                's_del'=>0
            );
            $datas = array(
                'm_id' => $member_array['member_id'],
                's_del' =>0
            );
            $res=$shareholder_model->getShareholder($datas);
            if(empty($res)) {
                $result = $shareholder_model->addShareholder($data);
                if ($result) {
                    dsLayerOpenSuccess(lang('ds_common_op_succ'));
                } else {
                    $this->error(lang('member_add_fail'));
                }
            }else{
                $this->error(lang('ds_share_error'));
            }
        }
    }
    /**
     * ajax操作
     */
    public function ajaxs() {
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
                    $shareholder_model=model('shareholder');
                    $conditions['s_del']=0;
                    $conditions['m_id']=$list['member_id'];
                    $lists=$shareholder_model->getShareholder($conditions);
                    if(empty($lists)) {
                        echo 'true';
                        exit;
                    }else{
                        echo 'false';
                        exit;
                    }
                }
                break;
        }
    }
    /**
     * 股东列表
    */
    public function sharelist(){
        $company_id = input('param.company_id');
        if (empty($company_id)) {
            $this->error(lang('param_error'));
        }
        $share_model = model('shareholder');
        //股东信息
        $share_list = $share_model->getShareList(array('c_id'=>$company_id,'s_del'=>0), '*');
        $member_model = model('member');
        foreach($share_list as $k=>$v){
            $member_list = $member_model->getMemberInfoByID($v['m_id']);
            $share_list[$k]['mobile']=$member_list['member_mobile'];
        }
        $this->assign('share_list', $share_list);
        $this->setAdminCurItem('sharelist');
        return $this->fetch();
    }
    /**
     * 删除股东
    */
    public function sharedel() {
        $s_id = input('param.s_id');
        if (empty($s_id)) {
            $this->error(lang('param_error'));
        }
        $shareholder_model = model('shareholder');
        $result = $shareholder_model->editShare(array('s_id'=>intval($s_id)),array("s_del"=>1));
        if ($result>=0) {
            dsLayerOpenSuccess(lang('ds_common_op_succ'));
        } else {
            $this->error(lang('ds_common_op_fail'));
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
