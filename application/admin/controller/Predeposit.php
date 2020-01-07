<?php

namespace app\admin\controller;

use think\Lang;

/**
 * ============================================================================

 * ============================================================================
 * 控制器
 */
class Predeposit extends AdminControl {
    const EXPORT_SIZE = 1000;
    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/predeposit.lang.php');
    }

    /*
     * 充值明细
     */

    public function pdrecharge_list() {
        $condition = array();
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('param.query_start_date'));
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('param.query_end_date'));
        $start_unixtime = $if_start_date ? strtotime(input('param.query_start_date')) : null;
        $end_unixtime = $if_end_date ? strtotime(input('param.query_end_date')) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition['pdr_addtime'] = array('between', array($start_unixtime, $end_unixtime));
        }
        if (input('param.mname') != '') {
            $condition['pdr_member_name'] = array('like', "%" . input('param.mname') . "%");
        }
        if (input('param.paystate_search') != '') {
            $condition['pdr_payment_state'] = input('param.paystate_search');
        }
        $predeposit_model = model('predeposit');
        $recharge_list = $predeposit_model->getPdRechargeList($condition, 20, '*', 'pdr_id desc');
        $this->assign('recharge_list', $recharge_list);
        $this->assign('show_page', $predeposit_model->page_info->render());

        $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件

        $this->setAdminCurItem('pdrecharge_list');
        return $this->fetch();
    }

    /**
     * 充值编辑(更改成收到款)
     */
    public function recharge_edit() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('admin_predeposit_parameter_error'), 'Predeposit/pdrecharge_list');
        }
        //查询充值信息
        $predeposit_model = model('predeposit');
        $condition = array();
        $condition['pdr_id'] = $id;
        $condition['pdr_payment_state'] = 0;
        $info = $predeposit_model->getPdRechargeInfo($condition);
        if (empty($info)) {
            $this->error(lang('admin_predeposit_record_error'), 'Predeposit/pdrecharge_list');
        }
        if (!request()->isPost()) {
            //显示支付接口列表
            $payment_list = model('payment')->getPaymentOpenList();
            //去掉预存款和货到付款
            foreach ($payment_list as $key => $value) {
                if ($value['payment_code'] == 'predeposit' || $value['payment_code'] == 'offline') {
                    unset($payment_list[$key]);
                }
            }
            $this->assign('payment_list', $payment_list);
            $this->assign('info', $info);
            return $this->fetch('recharge_edit');
        }

        //取支付方式信息
        $payment_model = model('payment');
        $condition = array();
        $condition['payment_code'] = input('post.payment_code');
        $payment_info = $payment_model->getPaymentOpenInfo($condition);
        if (!$payment_info || $payment_info['payment_code'] == 'offline' || $payment_info['payment_code'] == 'offline') {
            $this->error(lang('payment_index_sys_not_support'));
        }

        $condition = array();
        $condition['pdr_sn'] = $info['pdr_sn'];
        $condition['pdr_payment_state'] = 0;
        $update = array();
        $update['pdr_payment_state'] = 1;
        $update['pdr_paymenttime'] = strtotime(input('post.payment_time'));
        $update['pdr_payment_code'] = $payment_info['payment_code'];
        $update['pdr_trade_sn'] = input('post.trade_no');
        $update['pdr_admin'] = $this->admin_info['admin_name'];
        $log_msg = lang('admin_predeposit_recharge_edit_state') . ',' . lang('admin_predeposit_sn') . ':' . $info['pdr_sn'];

        try {
            $predeposit_model->startTrans();
            //更改充值状态
            $state = $predeposit_model->editPdRecharge($update, $condition);
            if (!$state) {
                throw Exception(lang('predeposit_payment_pay_fail'));
            }
            //变更会员预存款
            $data = array();
            $data['member_id'] = $info['pdr_member_id'];
            $data['member_name'] = $info['pdr_member_name'];
            $data['amount'] = $info['pdr_amount'];
            $data['pdr_sn'] = $info['pdr_sn'];
            $data['admin_name'] = $this->admin_info['admin_name'];
            $predeposit_model->changePd('recharge', $data);
            $predeposit_model->commit();
            $this->log($log_msg, 1);
            dsLayerOpenSuccess(lang('admin_predeposit_recharge_edit_success'));
        } catch (Exception $e) {
            $predeposit_model->rollback();
            $this->log($log_msg, 0);
            $this->error($e->getMessage(), 'Predeposit/pdrecharge_list');
        }
    }

    /**
     * 充值查看
     */
    public function recharge_info() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('admin_predeposit_parameter_error'), 'Predeposit/pdrecharge_list');
        }
        //查询充值信息
        $predeposit_model = model('predeposit');
        $condition = array();
        $condition['pdr_id'] = $id;
        $info = $predeposit_model->getPdRechargeInfo($condition);
        if (empty($info)) {
            $this->error(lang('admin_predeposit_record_error'), 'Predeposit/pdrecharge_list');
        }
        $this->assign('info', $info);
        return $this->fetch('recharge_info');
    }

    /**
     * 充值删除
     */
    public function recharge_del() {
        $pdr_id = input('param.pdr_id');
        $pdr_id_array = ds_delete_param($pdr_id);
        if($pdr_id_array === FALSE){
            ds_json_encode('10001', lang('param_error'));
        }
        $predeposit_model = model('predeposit');
        $condition = array();
        $condition = array('pdr_id' => array('in', $pdr_id_array));
        $condition['pdr_payment_state'] = 0;
        $result = $predeposit_model->delPdRecharge($condition);
        if ($result) {
            ds_json_encode('10000', lang('ds_common_del_succ'));
        } else {
            ds_json_encode('10001', lang('ds_common_del_fail'));
        }
    }



    /*
     * 预存款明细
     */

    public function pdlog_list() {
        $condition = array();
        $stime = input('param.stime');
        $etime = input('param.etime');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $stime);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $etime);
        $start_unixtime = $if_start_date ? strtotime($stime) : null;
        $end_unixtime = $if_end_date ? strtotime($etime) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition['lg_addtime'] = array('between', array($start_unixtime, $end_unixtime));
        }
        $mname = input('param.mname');
        if (!empty($mname)) {
            $condition['lg_member_name'] = $mname;
        }
        $aname = input('param.aname');
        if (!empty($aname)) {
            $condition['lg_admin_name'] = $aname;
        }
        $predeposit_model = model('predeposit');
        $list_log = $predeposit_model->getPdLogList($condition, 10, '*', 'lg_id desc');
        $this->assign('show_page', $predeposit_model->page_info->render());
        $this->assign('list_log', $list_log);

        $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件

        $this->setAdminCurItem('pdlog_list');
        return $this->fetch();
    }

    /*
     * 调节预存款
     */

    public function pd_add() {
        if (!(request()->isPost())) {
            $member_id = intval(input('param.member_id'));
            if($member_id>0){
                $condition['member_id'] = $member_id;
                $member = model('member')->getMemberInfo($condition);
                if(!empty($member)){
                    $this->assign('member_info',$member);
                }
            }
            return $this->fetch();
        } else {
            $data = array(
                'member_id' => input('post.member_id'),
                'amount' => input('post.amount'),
                'operatetype' => input('post.operatetype'),
                'lg_desc' => input('post.lg_desc'),
            );
            $predeposit_validate = validate('predeposit');
            if (!$predeposit_validate->scene('pd_add')->check($data)) {
                $this->error($predeposit_validate->getError());
            }

            $money = abs(floatval(input('post.amount')));
            $memo = trim(input('post.lg_desc'));
            if ($money <= 0) {
                $this->error(lang('amount_min'));
            }
            //查询会员信息
            $member_mod = model('member');
            $member_id = intval(input('post.member_id'));
            $operatetype = input('post.operatetype');
            $member_info = $member_mod->getMemberInfo(array('member_id' => $member_id));

            if (!is_array($member_info) || count($member_info) <= 0) {
                $this->error(lang('user_not_exist'), 'Predeposit/pd_add');
            }
            $available_predeposit = floatval($member_info['available_predeposit']);
            $freeze_predeposit = floatval($member_info['freeze_predeposit']);
            if ($operatetype == 2 && $money > $available_predeposit) {
                $this->error(lang('avaliable_predeposit_not_enough') . $available_predeposit, 'Predeposit/pd_add');
            }
            if ($operatetype == 3 && $money > $available_predeposit) {
                $this->error(lang('freezen_predeposit_not_enough') . $available_predeposit, 'Predeposit/pd_add');
            }
            if ($operatetype == 4 && $money > $freeze_predeposit) {
                $this->error(lang('recover_freezen_predeposit_not_enough') . $freeze_predeposit, 'Predeposit/pd_add');
            }
            $predeposit_model = model('predeposit');
            #生成对应订单号
            $order_sn = makePaySn($member_id);
            $admininfo = $this->getAdminInfo();
            $log_msg = "管理员【" . $admininfo['admin_name'] . "】操作会员【" . $member_info['member_name'] . "】预存款，金额为" . $money . ",编号为" . $order_sn;
            $admin_act = "sys_add_money";
            switch ($operatetype) {
                case 1:
                    $admin_act = "sys_add_money";
                    $log_msg = "管理员【" . $admininfo['admin_name'] . "】操作会员【" . $member_info['member_name'] . "】预存款【增加】，金额为" . $money . ",编号为" . $order_sn;
                    break;
                case 2:
                    $admin_act = "sys_del_money";
                    $log_msg = "管理员【" . $admininfo['admin_name'] . "】操作会员【" . $member_info['member_name'] . "】预存款【减少】，金额为" . $money . ",编号为" . $order_sn;
                    break;
                case 3:
                    $admin_act = "sys_freeze_money";
                    $log_msg = "管理员【" . $admininfo['admin_name'] . "】操作会员【" . $member_info['member_name'] . "】预存款【冻结】，金额为" . $money . ",编号为" . $order_sn;
                    break;
                case 4:
                    $admin_act = "sys_unfreeze_money";
                    $log_msg = "管理员【" . $admininfo['admin_name'] . "】操作会员【" . $member_info['member_name'] . "】预存款【解冻】，金额为" . $money . ",编号为" . $order_sn;
                    break;
                default:
                    $this->error(lang('ds_common_op_fail'), 'Predeposit/pdlog_list');
                    break;
            }
            try {
                $predeposit_model->startTrans();
                //扣除冻结的预存款
                $data = array();
                $data['member_id'] = $member_info['member_id'];
                $data['member_name'] = $member_info['member_name'];
                $data['amount'] = $money;
                $data['order_sn'] = $order_sn;
                $data['admin_name'] = $admininfo['admin_name'];
                $data['pdr_sn'] = $order_sn;
                $data['lg_desc'] = $memo;
                $predeposit_model->changePd($admin_act, $data);
                $predeposit_model->commit();
                $this->log($log_msg, 1);
                //返利
                $lg_return = input('post.return_state');
                $amount = input('post.amount');
                if($lg_return==1){
                    $this->rollback($member_info,$amount);
                }
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (Exception $e) {
                $predeposit_model->rollback();
                $this->log($log_msg, 0);
                $this->error($e->getMessage(), 'Predeposit/pdlog_list');
            }
        }
    }

    //充值返利 (推荐人，子公司)
    public function rollback($member_info,$amount){
        $member_mod = model('member');
        //一代
        $member_info_one = $member_mod->getMemberInfo(array('member_id' => $member_info['inviter_id']));
        //二代
        if($member_info_one['inviter_id'] != 0){
            $member_info_two = $member_mod->getMemberInfo(array('member_id' => $member_info_one['inviter_id']));
        }
        //根据经验值判断用户级别
        $list_config = rkcache('config', true);
        $membergrade_list = $list_config['member_grade'] ? unserialize($list_config['member_grade']) : array();//规则
        $member_exppoints_one = $member_info_one['member_exppoints'];//一代经验值
        if(isset($member_info_two)){
            $member_exppoints_two = $member_info_two['member_exppoints'];//二代经验值
        }
        $exppointone=0;
        $exppointtwo=0;
        foreach($membergrade_list as $ke=>$ve){
            if($member_exppoints_one >= $ve['exppoints']){
                $exppointone = $ve['exppointone'];
            }
            if(isset($member_exppoints_two)&&$member_exppoints_two>=$ve['exppoints']){
                $exppointtwo=$ve['exppointtwo'];
            }
        }
        //积分变动
        //积分记录表
        if($exppointone!=0) {
            $insert_arr['pl_memberid'] = $member_info_one['member_id'];
            $insert_arr['pl_membername'] = $member_info_one['member_name'];
            if ($list_config['way'] == 1) {
                $insert_arr['pl_points'] = 0;
                $insert_arr['pl_pointsav'] = $amount * $exppointone / 100;
                $insert_arr['pl_desc'] = "来自" . $member_info['member_name'] . "充值返利,充值金额：" . $amount . "元，原可用积分为：" . $member_info_one['member_points_available'];
            } else if ($list_config['way'] == 2) {
                $insert_arr['pl_points'] = $amount * $exppointone / 100;
                $insert_arr['pl_pointsav'] = 0;
                $insert_arr['pl_desc'] = "来自" . $member_info['member_name'] . "充值返利,充值金额：" . $amount . "元，原冻结积分为：" . $member_info_one['member_points'];
            }
            $insert_arr['pl_adminname'] = session('admin_name');
            model('points')->savePointslog('system', $insert_arr);
        }
        if(isset($member_info_two)){
            if($exppointtwo!=0) {
                $insert_arrs['pl_memberid'] = $member_info_two['member_id'];
                $insert_arrs['pl_membername'] = $member_info_two['member_name'];
                if ($list_config['way'] == 1) {
                    $insert_arrs['pl_points'] = 0;
                    $insert_arrs['pl_pointsav'] = $amount * $exppointtwo / 100;
                    $insert_arrs['pl_desc'] = "来自" . $member_info['member_name'] . "充值返利,充值金额：" . $amount . "元，原可用积分为：" . $member_info_two['member_points_available'];
                } elseif ($list_config['way'] == 2) {
                    $insert_arrs['pl_points'] = $amount * $exppointtwo / 100;
                    $insert_arrs['pl_pointsav'] = 0;
                    $insert_arrs['pl_desc'] = "来自" . $member_info['member_name'] . "充值返利,充值金额：" . $amount . "元，原冻结积分为：" . $member_info_two['member_points'];
                }
                $insert_arrs['pl_adminname'] = session('admin_name');
                model('points')->savePointslog('system', $insert_arrs);
            }
        }

        //子公司返利
        $company_model = model('company');
        //村级
        if($member_info['member_villageid']!=0){
            $company_village = $company_model->getCompanyInfo(array("company_level"=>5,"member_villageid"=>$member_info['member_villageid']));
            $village_info = $member_mod->getMemberInfo(array('member_id' => $company_village['member_id']));
            $insert_arr['pl_memberid'] = $village_info['member_id'];
            $insert_arr['pl_membername'] = $village_info['member_name'];
            if($list_config['companyway']==1) {
                $insert_arr['pl_points'] = 0;
                $insert_arr['pl_pointsav'] = $amount * $list_config['village_scale'] / 100;
                $insert_arr['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原可用积分为：".$village_info['member_points_available'];
            }elseif($list_config['companyway']==2){
                $insert_arr['pl_points'] = $amount * $list_config['village_scale'] / 100;
                $insert_arr['pl_pointsav'] = 0;
                $insert_arr['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原冻结积分为：".$village_info['member_points'];
            }
            $insert_arr['pl_adminname'] = session('admin_name');
            model('points')->savePointslog('system', $insert_arr);
        }
        //镇级
        if($member_info['member_townid']!=0){
            $company_town = $company_model->getCompanyInfo(array("company_level"=>4,"member_townid"=>$member_info['member_townid']));
            $town_info = $member_mod->getMemberInfo(array('member_id' => $company_town['member_id']));
            $insert_arrtown['pl_memberid'] = $town_info['member_id'];
            $insert_arrtown['pl_membername'] = $town_info['member_name'];
            if($list_config['companyway']==1) {
                $insert_arrtown['pl_points'] = 0;
                $insert_arrtown['pl_pointsav'] = $amount * $list_config['town_scale'] / 100;
                $insert_arrtown['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原可用积分为：".$town_info['member_points_available'];
            }elseif($list_config['companyway']==2){
                $insert_arrtown['pl_points'] = $amount * $list_config['town_scale'] / 100;
                $insert_arrtown['pl_pointsav'] = 0;
                $insert_arrtown['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原冻结积分为：".$town_info['member_points'];
            }
            $insert_arrtown['pl_adminname'] = session('admin_name');
            model('points')->savePointslog('system', $insert_arrtown);
        }
        //区/县级
        if($member_info['member_areaid']!=0){
            $company_area = $company_model->getCompanyInfo(array("company_level"=>3,"member_areaid"=>$member_info['member_areaid']));
            $area_info = $member_mod->getMemberInfo(array('member_id' => $company_area['member_id']));
            $insert_arrarea['pl_memberid'] = $area_info['member_id'];
            $insert_arrarea['pl_membername'] = $area_info['member_name'];
            if($list_config['companyway']==1) {
            $insert_arrarea['pl_points'] = 0;
            $insert_arrarea['pl_pointsav'] = $amount*$list_config['county_scale']/100;
                $insert_arrarea['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原可用积分为：".$area_info['member_points_available'];
            }elseif($list_config['companyway']==2){
                $insert_arrarea['pl_points'] = $amount*$list_config['county_scale']/100;
                $insert_arrarea['pl_pointsav'] = 0;
                $insert_arrarea['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原冻结积分为：".$area_info['member_points'];
            }
            $insert_arrarea['pl_adminname'] = session('admin_name');
            model('points')->savePointslog('system', $insert_arrarea);
        }
        //市级
        if($member_info['member_cityid']!=0){
            $company_city = $company_model->getCompanyInfo(array("company_level"=>2,"member_cityid"=>$member_info['member_cityid']));
            $city_info = $member_mod->getMemberInfo(array('member_id' => $company_city['member_id']));
            $insert_arrcity['pl_memberid'] = $city_info['member_id'];
            $insert_arrcity['pl_membername'] = $city_info['member_name'];
            if($list_config['companyway']==1) {
            $insert_arrcity['pl_points'] = 0;
            $insert_arrcity['pl_pointsav'] = $amount*$list_config['city_scale']/100;
                $insert_arrcity['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原可用积分为：".$city_info['member_points_available'];
            }elseif($list_config['companyway']==2){
                $insert_arrcity['pl_points'] = $amount*$list_config['city_scale']/100;
                $insert_arrcity['pl_pointsav'] = 0;
                $insert_arrcity['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原冻结积分为：".$city_info['member_points'];
            }
            $insert_arrcity['pl_adminname'] = session('admin_name');
            model('points')->savePointslog('system', $insert_arrcity);
        }
        //省级
        if($member_info['member_provinceid']!=0){
            $company_province = $company_model->getCompanyInfo(array("company_level"=>1,"member_provinceid"=>$member_info['member_provinceid']));
            $province_info = $member_mod->getMemberInfo(array('member_id' => $company_province['member_id']));
            $insert_arrpro['pl_memberid'] = $province_info['member_id'];
            $insert_arrpro['pl_membername'] = $province_info['member_name'];
            if($list_config['companyway']==1) {
            $insert_arrpro['pl_points'] = 0;
            $insert_arrpro['pl_pointsav'] = $amount*$list_config['province_scale']/100;
                $insert_arrpro['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原可用积分为：".$province_info['member_points_available'];
            }elseif($list_config['companyway']==2){
                $insert_arrpro['pl_points'] = $amount*$list_config['province_scale']/100;
                $insert_arrpro['pl_pointsav'] = 0;
                $insert_arrpro['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原冻结积分为：".$province_info['member_points'];
            }
            $insert_arrpro['pl_adminname'] = session('admin_name');
            model('points')->savePointslog('system', $insert_arrpro);
        }

    }

    //取得会员信息
    public function checkmember() {
        $name = input('post.name');
        if (!$name) {
            exit(json_encode(array('id' => 0)));
            die;
        }
        $obj_member = model('member');
        $member_info = $obj_member->getMemberInfo(array('member_name' => $name));
        if (is_array($member_info) && count($member_info) > 0) {
            exit(json_encode(array('id' => $member_info['member_id'], 'name' => $member_info['member_name'], 'available_predeposit' => $member_info['available_predeposit'], 'freeze_predeposit' => $member_info['freeze_predeposit'])));
        } else {
            exit(json_encode(array('id' => 0)));
        }
    }




    /**
     * 导出预存款充值记录
     *
     */
    public function export_step1() {
        $condition = array();
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('param.query_start_date'));
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', input('param.query_end_date'));
        $start_unixtime = $if_start_date ? strtotime(input('param.query_start_date')) : null;
        $end_unixtime = $if_end_date ? strtotime(input('param.query_end_date')) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition['pdr_addtime'] = array('between', array($start_unixtime, $end_unixtime));
        }
        if (input('param.mname') != '') {
            $condition['pdr_member_name'] = array('like', "%" . input('param.mname') . "%");
        }
        if (input('param.paystate_search') != '') {
            $condition['pdr_payment_state'] = input('param.paystate_search');
        }


        $predeposit_model = model('predeposit');
        if (!is_numeric(input('param.curpage'))) {
            $count = $predeposit_model->getPdRechargeCount($condition);
            $array = array();
            if ($count > self::EXPORT_SIZE) { //显示下载链接
                $page = ceil($count / self::EXPORT_SIZE);
                for ($i = 1; $i <= $page; $i++) {
                    $limit1 = ($i - 1) * self::EXPORT_SIZE + 1;
                    $limit2 = $i * self::EXPORT_SIZE > $count ? $count : $i * self::EXPORT_SIZE;
                    $array[$i] = $limit1 . ' ~ ' . $limit2;
                }
                $this->assign('export_list', $array);
                return $this->fetch('/public/excel');
            } else { //如果数量小，直接下载
                $data = $predeposit_model->getPdRechargeList($condition, '', '*', 'pdr_id desc', self::EXPORT_SIZE);
                $rechargepaystate = array(0 => lang('admin_predeposit_rechargewaitpaying'), 1 => lang('admin_predeposit_rechargepaysuccess'));
                foreach ($data as $k => $v) {
                    $data[$k]['pdr_payment_state'] = $rechargepaystate[$v['pdr_payment_state']];
                }
                $this->createExcel($data);
            }
        } else { //下载
            $limit1 = (input('param.curpage') - 1) * self::EXPORT_SIZE;
            $limit2 = self::EXPORT_SIZE;
            $data = $predeposit_model->getPdRechargeList($condition, '', '*', 'pdr_id desc', "{$limit1},{$limit2}");
            $rechargepaystate = array(0 => lang('admin_predeposit_rechargewaitpaying'), 1 => lang('admin_predeposit_rechargepaysuccess'));
            foreach ($data as $k => $v) {
                $data[$k]['pdr_payment_state'] = $rechargepaystate[$v['pdr_payment_state']];
            }
            $this->createExcel($data);
        }
    }

    /**
     * 生成导出预存款充值excel
     *
     * @param array $data
     */
    private function createExcel($data = array()) {
        Lang::load(APP_PATH .'admin/lang/'.config('default_lang').'/export.lang.php');
        $excel_obj = new \excel\Excel();
        $excel_data = array();
        //设置样式
        $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
        //header
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_yc_no'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_yc_member'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_yc_ctime'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_yc_ptime'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_yc_pay'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_yc_money'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_yc_paystate'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_yc_memberid'));
        foreach ((array) $data as $k => $v) {
            $tmp = array();
            $tmp[] = array('data' => $v['pdr_sn']);
            $tmp[] = array('data' => $v['pdr_member_name']);
            $tmp[] = array('data' => date('Y-m-d H:i:s', $v['pdr_addtime']));
            if (intval($v['pdr_paymenttime'])) {
                if (date('His', $v['pdr_paymenttime']) == 0) {
                    $tmp[] = array('data' => date('Y-m-d', $v['pdr_paymenttime']));
                } else {
                    $tmp[] = array('data' => date('Y-m-d H:i:s', $v['pdr_paymenttime']));
                }
            } else {
                $tmp[] = array('data' => '');
            }
            $tmp[] = array('data' => $v['pdr_payment_code']);
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['pdr_amount']));
            $tmp[] = array('data' => $v['pdr_payment_state']);
            $tmp[] = array('data' => $v['pdr_member_id']);
            $excel_data[] = $tmp;
        }
        $excel_data = $excel_obj->charset($excel_data, CHARSET);
        $excel_obj->addArray($excel_data);
        $excel_obj->addWorksheet($excel_obj->charset(lang('exp_yc_yckcz'), CHARSET));
        $excel_obj->generateXML($excel_obj->charset(lang('exp_yc_yckcz'), CHARSET) . input('param.curpage') . '-' . date('Y-m-d-H', TIMESTAMP));
    }


    /**
     * 导出预存款提现记录
     *
     */
    public function export_cash_step1() {
        $condition = array();
        $stime = input('param.stime');
        $etime = input('param.etime');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $stime);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $etime);
        $start_unixtime = $if_start_date ? strtotime($stime) : null;
        $end_unixtime = $if_end_date ? strtotime($etime) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition['pdc_addtime'] = array('between', array($start_unixtime, $end_unixtime));
        }
        $mname = input('param.mname');
        if (!empty($mname)) {
            $condition['pdc_member_name'] = array('like', "%" . $mname . "%");
        }
        $pdc_bank_user = input('param.pdc_bank_user');
        if (!empty($pdc_bank_user)) {
            $condition['pdc_bank_user'] = array('like', "%" . $pdc_bank_user . "%");
        }
        $paystate_search = input('param.paystate_search');
        if ($paystate_search != '') {
            $condition['pdc_payment_state'] = $paystate_search;
        }

        $predeposit_model = Model('predeposit');

        if (!is_numeric(input('param.curpage'))) {
            $count = $predeposit_model->getPdCashCount($condition);
            $array = array();
            if ($count > self::EXPORT_SIZE) { //显示下载链接
                $page = ceil($count / self::EXPORT_SIZE);
                for ($i = 1; $i <= $page; $i++) {
                    $limit1 = ($i - 1) * self::EXPORT_SIZE + 1;
                    $limit2 = $i * self::EXPORT_SIZE > $count ? $count : $i * self::EXPORT_SIZE;
                    $array[$i] = $limit1 . ' ~ ' . $limit2;
                }
                $this->assign('export_list', $array);
                return $this->fetch('/public/excel');
            } else { //如果数量小，直接下载
                $data = $predeposit_model->getPdCashList($condition, '', '*', 'pdc_id desc', self::EXPORT_SIZE);
                $cashpaystate = array(0 => lang('admin_predeposit_rechargewaitpaying'), 1 => lang('admin_predeposit_rechargepaysuccess'));
                foreach ($data as $k => $v) {
                    $data[$k]['pdc_payment_state'] = $cashpaystate[$v['pdc_payment_state']];
                }
                $this->createCashExcel($data);
            }
        } else { //下载
            $limit1 = (input('param.curpage') - 1) * self::EXPORT_SIZE;
            $limit2 = self::EXPORT_SIZE;
            $data = $predeposit_model->getPdCashList($condition, '', '*', 'pdc_id desc', "{$limit1},{$limit2}");
            $cashpaystate = array(0 => lang('admin_predeposit_rechargewaitpaying'), 1 => lang('admin_predeposit_rechargepaysuccess'));
            foreach ($data as $k => $v) {
                $data[$k]['pdc_payment_state'] = $cashpaystate[$v['pdc_payment_state']];
            }
            $this->createCashExcel($data);
        }
    }

    /**
     * 生成导出预存款提现excel
     *
     * @param array $data
     */
    private function createCashExcel($data = array()) {
        Lang::load(APP_PATH .'admin/lang/'.config('default_lang').'/export.lang.php');
        $excel_obj = new \excel\Excel();
        $excel_data = array();
        //设置样式
        $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
        //header
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_tx_no'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_tx_member'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_tx_money'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_tx_ctime'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_tx_state'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_tx_memberid'));
        foreach ((array) $data as $k => $v) {
            $tmp = array();
            $tmp[] = array('data' => $v['pdc_sn']);
            $tmp[] = array('data' => $v['pdc_member_name']);
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['pdc_amount']));
            $tmp[] = array('data' => date('Y-m-d H:i:s', $v['pdc_addtime']));
            $tmp[] = array('data' => $v['pdc_payment_state']);
            $tmp[] = array('data' => $v['pdc_member_id']);
            $excel_data[] = $tmp;
        }
        $excel_data = $excel_obj->charset($excel_data, CHARSET);
        $excel_obj->addArray($excel_data);
        $excel_obj->addWorksheet($excel_obj->charset(lang('exp_tx_title'), CHARSET));
        $excel_obj->generateXML($excel_obj->charset(lang('exp_tx_title'), CHARSET) . input('param.curpage') . '-' . date('Y-m-d-H', TIMESTAMP));
    }

    /**
     * 预存款明细信息导出
     */
    public function export_mx_step1() {
        $condition = array();
        $stime = input('param.stime');
        $etime = input('param.etime');
        $if_start_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $stime);
        $if_end_date = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $etime);
        $start_unixtime = $if_start_date ? strtotime($stime) : null;
        $end_unixtime = $if_end_date ? strtotime($etime) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition['lg_addtime'] = array('between', array($start_unixtime, $end_unixtime));
        }
        $mname = input('param.mname');
        if (!empty($mname)) {
            $condition['lg_member_name'] = $mname;
        }
        $aname = input('param.aname');
        if (!empty($aname)) {
            $condition['lg_admin_name'] = $aname;
        }


        $predeposit_model = Model('predeposit');
        if (!is_numeric(input('param.curpage'))) {
            $count = $predeposit_model->getPdLogCount($condition);
            $array = array();
            if ($count > self::EXPORT_SIZE) { //显示下载链接
                $page = ceil($count / self::EXPORT_SIZE);
                for ($i = 1; $i <= $page; $i++) {
                    $limit1 = ($i - 1) * self::EXPORT_SIZE + 1;
                    $limit2 = $i * self::EXPORT_SIZE > $count ? $count : $i * self::EXPORT_SIZE;
                    $array[$i] = $limit1 . ' ~ ' . $limit2;
                }
                $this->assign('export_list', $array);
                return $this->fetch('/public/excel');
            } else { //如果数量小，直接下载
                $data = $predeposit_model->getPdLogList($condition, '', '*', 'lg_id desc', self::EXPORT_SIZE);
                $this->createmxExcel($data);
            }
        } else { //下载
            $limit1 = (input('param.curpage') - 1) * self::EXPORT_SIZE;
            $limit2 = self::EXPORT_SIZE;
            $data = $predeposit_model->getPdLogList($condition, '', '*', 'lg_id desc', "{$limit1},{$limit2}");
            $this->createmxExcel($data);
        }
    }

    /**
     * 导出预存款明细excel
     *
     * @param array $data
     */
    private function createmxExcel($data = array()) {
        Lang::load(APP_PATH .'admin/lang/'.config('default_lang').'/export.lang.php');
        $excel_obj = new \excel\Excel();
        $excel_data = array();
        //设置样式
        $excel_obj->setStyle(array('id' => 's_title', 'Font' => array('FontName' => '宋体', 'Size' => '12', 'Bold' => '1')));
        //header
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_mx_member'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_mx_ctime'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_mx_av_money'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_mx_freeze_money'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_mx_system'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_mx_mshu'));
        foreach ((array) $data as $k => $v) {
            $tmp = array();
            $tmp[] = array('data' => $v['lg_member_name']);
            $tmp[] = array('data' => date('Y-m-d H:i:s', $v['lg_addtime']));
            if (floatval($v['lg_av_amount']) == 0) {
                $tmp[] = array('data' => '');
            } else {
                $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['lg_av_amount']));
            }
            if (floatval($v['lg_freeze_amount']) == 0) {
                $tmp[] = array('data' => '');
            } else {
                $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['lg_freeze_amount']));
            }
            $tmp[] = array('data' => $v['lg_admin_name']);
            $tmp[] = array('data' => $v['lg_desc']);
            $excel_data[] = $tmp;
        }
        $excel_data = $excel_obj->charset($excel_data, CHARSET);
        $excel_obj->addArray($excel_data);
        $excel_obj->addWorksheet($excel_obj->charset(lang('exp_mx_rz'), CHARSET));
        $excel_obj->generateXML($excel_obj->charset(lang('exp_mx_rz'), CHARSET) . input('param.curpage') . '-' . date('Y-m-d-H', TIMESTAMP));
    }
    /**
     * 查看提现信息
     */
    public function pdcash_view() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('admin_predeposit_parameter_error'), 'Predeposit/pdcash_list');
        }
        $predeposit_model = model('predeposit');
        $condition = array();
        $condition['pdc_id'] = $id;
        $info = $predeposit_model->getPdcashInfo($condition);
        if (!is_array($info) || count($info) < 0) {
            $this->error(lang('admin_predeposit_record_error'), 'Predeposit/pdcash_list');
        }
        $this->assign('info', $info);
        return $this->fetch();
    }
    /**
     * 更改提现为支付状态
     */
    public function pdcash_pay() {
        $id = intval(input('param.id'));
        if ($id <= 0) {
            $this->error(lang('admin_predeposit_parameter_error'),'Predeposit/pdcash_list');
        }
        $predeposit_model = model('predeposit');
        $condition = array();
        $condition['pdc_id'] = $id;
        $condition['pdc_payment_state'] = 0;
        $info = $predeposit_model->getPdcashInfo($condition);
        if (!is_array($info) || count($info) < 0) {
            $this->error(lang('admin_predeposit_record_error'), 'Predeposit/pdcash_list');
        }
        //查询用户信息
        $member_model = model('member');
        $member_info = $member_model->getMemberInfo(array('member_id' => $info['pdc_member_id']));

        $update = array();
        $admininfo = $this->getAdminInfo();
        $update['pdc_payment_state'] = 1;
        $update['pdc_payment_admin'] = $admininfo['admin_name'];
        $update['pdc_payment_time'] = TIMESTAMP;
        $log_msg = lang('admin_predeposit_cash_edit_state') . ',' . lang('admin_predeposit_cs_sn') . ':' . $info['pdc_sn'];

        try {
            $predeposit_model->startTrans();
            $result = $predeposit_model->editPdcash($update, $condition);
            if (!$result) {
                $this->error(lang('admin_predeposit_cash_edit_fail'));
            }
            //扣除冻结的预存款
//            $data = array();
//            $data['member_id'] = $member_info['member_id'];
//            $data['member_name'] = $member_info['member_name'];
//            $data['amount'] = $info['pdc_amount'];
//            $data['order_sn'] = $info['pdc_sn'];
//            $data['admin_name'] = $admininfo['admin_name'];
//            $predeposit_model->changePd('cash_pay', $data);
            $predeposit_model->commit();
            $this->log($log_msg, 1);
            dsLayerOpenSuccess(lang('admin_predeposit_cash_edit_success'));
        } catch (Exception $e) {
            $predeposit_model->rollback();
            $this->log($log_msg, 0);
            $this->error($e->getMessage(), 'Predeposit/pdcash_list');
        }
    }


    /**
     * 获取卖家栏目列表,针对控制器下的栏目
     */
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'pdrecharge_list',
                'text' => lang('pdrecharge_list'),
                'url' => url('Predeposit/pdrecharge_list')
            ),
            array(
                'name' => 'pdlog_list',
                'text' => lang('pdlog_list_c'),
                'url' => url('Predeposit/pdlog_list')
            ),
            array(
                'name' => 'pd_add',
                'text' => lang('pd_add_c'),
                'url' => "javascript:dsLayerOpen('".url('Predeposit/pd_add')."','".lang('pd_add')."')"
            ),
        );
        return $menu_array;
    }
}

?>
