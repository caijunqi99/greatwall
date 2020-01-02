<?php

namespace app\admin\controller;

use think\Lang;
use PHPExcel;
/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class MemberAuth extends AdminControl {

    const EXPORT_SIZE = 1000;
    
    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/member.lang.php');
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/member_auth.lang.php');
    }

    public function index() {
        $member_model = model('member');
        
        $search_field_value = input('search_field_value');
        $search_field_name = input('search_field_name');
        $condition = '1=1';
        $filtered=0;
        $default_condition = array();
        if ($search_field_value != '') {
            switch ($search_field_name) {
                case 'member_name':
                    $condition.=' AND member_name LIKE "%' . trim($search_field_value) . '%"';
                    $filtered=1;
                    break;
                case 'member_email':
                    $condition.=' AND member_email LIKE "%' . trim($search_field_value) . '%"';
                    $filtered=1;
                    break;
                case 'member_mobile':
                    $condition.=' AND member_mobile LIKE "%' . trim($search_field_value) . '%"';
                    $filtered=1;
                    break;
                case 'member_truename':
                    $condition.=' AND member_truename LIKE "%' . trim($search_field_value) . '%"';
                    $filtered=1;
                    break;
            }
        }
        $search_state = input('search_state');
        switch ($search_state) {
            case 'check':
                $condition.=' AND member_auth_state=1';
                $filtered=1;
                break;
            case 'pass':
                $condition.=' AND member_auth_state=3';
                $filtered=1;
                break;
            case 'fail':
                $condition.=' AND member_auth_state=2';
                $filtered=1;
                break;
            default:
                $condition.=' AND member_auth_state IN (1,2,3)';
        }
        $member_list = $member_model->getMemberListAuth($condition, '*', 10, 'memberbank_id desc');
        //整理会员信息
        if (is_array($member_list) && !empty($member_list)) {
            foreach ($member_list as $k => $v) {
                $member_list[$k]['member_addtime'] = $v['member_addtime'] ? date('Y-m-d H:i:s', $v['member_addtime']) : '';
            }
        }
        $this->assign('search_field_name', trim($search_field_name));
        $this->assign('search_field_value', trim($search_field_value));
        $this->assign('member_list', $member_list);
        $this->assign('show_page', $member_model->page_info->render());

        $this->assign('filtered', $filtered); //是否有查询条件

        $this->setAdminCurItem('index');
        return $this->fetch();
    }
    
    public function verify(){
        $member_id = input('param.member_id');
        $state = input('param.state');
        $message = input('param.message');
        $member_id_array = ds_delete_param($member_id);
        if ($member_id_array == FALSE || !in_array($state, array(1,2))) {
            ds_json_encode(10001, lang('param_error'));
        }
        
        if($state==1){
            $update=array('member_auth_state'=>3);
        }else{
            $update=array('member_auth_state'=>2);
        }
        if(!model('member')->editMember(array('member_auth_state'=>1,'member_id'=>array('in',$member_id_array)),$update)){
            ds_json_encode(10001, lang('ds_common_op_fail'));
        }
        if($message){
            //添加短消息
                $message_model = model('message');
                $insert_arr = array();
                $insert_arr['from_member_id'] = 0;
                $insert_arr['member_id'] = "," . implode(',', $member_id_array) . ",";
                $insert_arr['msg_content'] = lang('member_auth_fail').'：'.$message;
                $insert_arr['message_type'] = 1;
                $insert_arr['message_ismore'] = 1;
                $message_model->addMessage($insert_arr);
        }
        ds_json_encode(10000, lang('ds_common_op_succ'));
    }

    /**
     * 编辑会员认证信息
     */
    public function edit(){
        $member_id = input('param.member_id');
        if (empty($member_id)) {
            $this->error(lang('param_error'));
        }
        $member_model = model('member');
        if (!request()->isPost()) {
            $condition['member_id'] = $member_id;
            $member_array = $member_model->getMemberInfo($condition);
            $memberbank_model = model('memberbank');
            $memberbank_array = $memberbank_model->getMemberbankInfo($condition);
            $member_array = array_merge($member_array,$memberbank_array);
            $this->assign('member_array', $member_array);
            return $this->fetch();
        } else {
            $memberbank_model = model('memberbank');
            $bank_array = array(
                'memberbank_name' => input('post.member_bankname'),
                'memberbank_no' => input('post.member_bankcard'),
            );
            $memberbank_model->editMemberbank($bank_array,array("member_id"=>$member_id));
            $data = array(
                'member_villageid' => input('post.village_id'),
                'member_townid' => input('post.town_id'),
                'member_cityid' => input('post.city_id'),
                'member_provinceid' => input('post.province_id'),
                'member_areaid' => input('post.area_id'),
                'member_areainfo' => input('post.region'),
            );
            $result = $member_model->editMember(array('member_id'=>intval($member_id)),$data);
            if ($result>=0) {
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->error(lang('ds_common_op_fail'));
            }
        }
    }

    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_list'),
                'url' => url('MemberAuth/index')
            ),
        );

        return $menu_array;
    }

}

?>
