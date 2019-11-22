<?php

/**
 * 积分管理
 */

namespace app\admin\controller;

use think\Lang;

/**
 * ============================================================================
 
 * ============================================================================
 * 控制器
 */
class Points extends AdminControl {
    const EXPORT_SIZE = 5000;
    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/points.lang.php');
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/predeposit.lang.php');
    }

    public function index() {
        if (!request()->isPost()) {
            $condition_arr = array();
            $mname = input('param.mname');
            if (!empty($mname)) {
                $condition_arr['pl_membername'] = array('like', '%' . $mname . '%');
            }
            $aname = input('param.aname');
            if (!empty($aname)) {
                $condition_arr['pl_adminname'] = array('like', '%' . $aname . '%');
            }
            $stage = input('param.stage');
            if ($stage) {
                $condition_arr['pl_stage'] = trim($stage);
            }
            $stime = input('param.stime');
            $etime = input('param.etime');
            $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $stime);
            $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $etime);
            $start_unixtime = $if_start_time ? strtotime($stime) : null;
            $end_unixtime = $if_end_time ? strtotime($etime) : null;
            if ($start_unixtime || $end_unixtime) {
                $condition_arr['pl_addtime'] = array('between', array($start_unixtime, $end_unixtime));
            }
            
            $search_desc = trim(input('param.description'));
            if (!empty($search_desc)) {
                $condition_arr['pl_desc'] = array('like', "%" . $search_desc . "%");
            }


            $points_model = model('points');
            $list_log = $points_model->getPointslogList($condition_arr, 10, '*', '');

            $this->assign('pointslog', $list_log);
            $this->assign('show_page', $points_model->page_info->render());
            $this->setAdminCurItem('index');
            return $this->fetch();
        }
    }

    //积分规则设置
    function setting(){
        $config_model = model('config');
        if (request()->isPost()) {
            $update_array = array();
            $update_array['points_reg'] = intval(input('post.points_reg'));
            $update_array['points_login'] = intval(input('post.points_login'));
            $update_array['points_comments'] = intval(input('post.points_comments'));
            $update_array['points_orderrate'] = intval(input('post.points_orderrate'));
            $update_array['points_ordermax'] = intval(input('post.points_ordermax'));
            $update_array['points_invite'] = intval(input('post.points_invite'));
            $update_array['points_rebate'] = intval(input('post.points_rebate'));

            $result = $config_model->editConfig($update_array);
            if ($result === true) {
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        } else {
            $list_setting = rkcache('config', true);
            $this->assign('list_setting', $list_setting);
            $this->setAdminCurItem('setting');
            return $this->fetch('setting');
        }
    }

    //积分提现
    function draw(){
        $config_model = model('config');
        if (request()->isPost()) {
            $update_array = array();
            $update_array['points_reg'] = intval(input('post.points_reg'));
            $update_array['points_login'] = intval(input('post.points_login'));
            $update_array['points_comments'] = intval(input('post.points_comments'));
            $update_array['points_orderrate'] = intval(input('post.points_orderrate'));
            $update_array['points_ordermax'] = intval(input('post.points_ordermax'));
            $update_array['points_invite'] = intval(input('post.points_invite'));
            $update_array['points_rebate'] = intval(input('post.points_rebate'));

            $result = $config_model->editConfig($update_array);
            if ($result === true) {
                $this->success(lang('ds_common_save_succ'));
            } else {
                $this->error(lang('ds_common_save_fail'));
            }
        } else {
            $list_draw = rkcache('config', true);
            $this->assign('list_draw', $list_draw);
            $this->setAdminCurItem('draw');
            return $this->fetch('draw');
        }
    }
    
    //积分明细查询
    function pointslog() {
        if (!request()->isPost()) {
            $member_id = intval(input('get.member_id'));
            if($member_id>0){
                $condition['member_id'] = $member_id;
                $member = model('member')->getMemberInfo($condition);
                if(!empty($member)){
                    $this->assign('member_info',$member);
                }
            }
            return $this->fetch();
        } else {
            $data = [
                'member_name' => input('post.member_name'),
                'points_type' => input('post.points_type'),
                'points_num' => intval(input('post.points_num')),
                'points_num_av' => intval(input('post.points_num_av')),
                'points_desc' => input('post.points_desc'),
            ];
            $point_validate = validate('point');
            if (!$point_validate->scene('pointslog')->check($data)) {
                $this->error($point_validate->getError());
            }

            $member_name = $data['member_name'];
            $member_info = model('member')->getMemberInfo(array('member_name' => $member_name));
            if (!is_array($member_info) || count($member_info) <= 0) {
                $this->error(lang('admin_points_userrecord_error'));
            }
            if ($data['points_type'] == 2 && $data['points_num'] > $member_info['member_points']) {
                $this->error(lang('admin_points_points_short_error') . $member_info['member_points']);
            }
            //积分数据记录
            $insert_arr['pl_memberid'] = $member_info['member_id'];
            $insert_arr['pl_membername'] = $member_info['member_name'];
            if ($data['points_type'] == 2) {
                $insert_arr['pl_points'] = -$data['points_num'];
                $insert_arr['pl_pointsav'] = -$data['points_num_av'];
            } else {
                $insert_arr['pl_points'] = $data['points_num'];
                $insert_arr['pl_pointsav'] = $data['points_num_av'];
            }
            $insert_arr['pl_desc'] = $data['points_desc'];
            $insert_arr['pl_adminname'] = session('admin_name');

            $result = model('points')->savePointslog('system', $insert_arr);
            if ($result) {
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } else {
                $this->error(lang('error'), 'Points/index');
            }
        }
    }

    public function checkmember() {
        $name = trim(input('param.name'));
        if (!$name) {
            exit(json_encode(array('id' => 0)));
        }
        $member_info = model('member')->getMemberInfo(array('member_name' => $name));
        if (is_array($member_info) && count($member_info) > 0) {
            echo json_encode(array('id' => $member_info['member_id'], 'name' => $member_info['member_name'], 'points' => $member_info['member_points'],'points_available'=>$member_info['member_points_available']));
        } else {
            exit(json_encode(array('id' => 0)));
            die;
        }
    }


	/**
     * 积分日志列表导出
     */
    public function export_step1() {
        $condition_arr = array();
        
        $mname = input('param.mname');
        if (!empty($mname)) {
            $condition_arr['pl_membername'] = array('like', '%' . $mname . '%');
        }
        $aname = input('param.aname');
        if (!empty($aname)) {
            $condition_arr['pl_adminname'] = array('like', '%' . $aname . '%');
        }
        
        $stage = input('param.stage');
        if ($stage) {
            $condition_arr['pl_stage'] = trim($stage);
        }
        $stime = input('param.stime');
        $etime = input('param.etime');
        $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $stime);
        $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $etime);
        $start_unixtime = $if_start_time ? strtotime($stime) : null;
        $end_unixtime = $if_end_time ? strtotime($etime) : null;
        if ($start_unixtime || $end_unixtime) {
            $condition_arr['pl_addtime'] = array('between', array($start_unixtime, $end_unixtime));
        }
        $search_desc = trim(input('param.description'));
        if (!empty($search_desc)) {
            $condition_arr['pl_desc'] = array('like', "%" . $search_desc . "%");
        }
        
        
        $points_model = model('points');
        
        if (!is_numeric(input('param.curpage'))) {
            $count = $points_model->getPointsCount($condition_arr);
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
                $list_log = $points_model->getPointsLogList($condition_arr, '', '*', self::EXPORT_SIZE);
                $this->createExcel($list_log);
            }
        } else { //下载
            $limit1 = (input('param.curpage') - 1) * self::EXPORT_SIZE;
            $limit2 = self::EXPORT_SIZE;
            $list_log = $points_model->getPointsLogList($condition_arr, '', '*', "$limit1,$limit2");
            $this->createExcel($list_log);
        }
    }

    /**
     * 生成excel
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
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_pi_member'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_pi_system'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_pi_point'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_pi_time'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_pi_jd'));
        $excel_data[0][] = array('styleid' => 's_title', 'data' => lang('exp_pi_ms'));
        $state_cn = array(lang('admin_points_stage_regist'), lang('admin_points_stage_login'), lang('admin_points_stage_comments'), lang('admin_points_stage_order'), lang('admin_points_stage_system'), lang('admin_points_stage_pointorder'), lang('admin_points_stage_app'));
        foreach ((array) $data as $k => $v) {
            $tmp = array();
            $tmp[] = array('data' => $v['pl_membername']);
            $tmp[] = array('data' => $v['pl_adminname']);
            $tmp[] = array('format' => 'Number', 'data' => ds_price_format($v['pl_points']));
            $tmp[] = array('data' => date('Y-m-d H:i:s', $v['pl_addtime']));
            $tmp[] = array('data' => str_replace(array('regist', 'login', 'comments', 'order', 'system', 'pointorder', 'app'), $state_cn, $v['pl_stage']));
            $tmp[] = array('data' => $v['pl_desc']);

            $excel_data[] = $tmp;
        }
        $excel_data = $excel_obj->charset($excel_data, CHARSET);
        $excel_obj->addArray($excel_data);
        $excel_obj->addWorksheet($excel_obj->charset(lang('exp_pi_jfmx'), CHARSET));
        $excel_obj->generateXML($excel_obj->charset(lang('exp_pi_jfmx'), CHARSET) . input('param.curpage') . '-' . date('Y-m-d-H', TIMESTAMP));
    }
    /*
        * 提现列表
        */
    public function pdcash_list() {
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
        $predeposit_model = model('predeposit');
        $predeposit_list = $predeposit_model->getPdcashList($condition, 20, '*', 'pdc_payment_state asc,pdc_id asc');
        $this->assign('predeposit_list', $predeposit_list);
        $this->assign('show_page', $predeposit_model->page_info->render());

        $this->assign('filtered', $condition ? 1 : 0); //是否有查询条件

        $this->setAdminCurItem('pdcash_list');
        return $this->fetch('pdcash_list');
    }

    /**
     * 删除提现记录
     */
    public function pdcash_del() {
        $pdc_id = intval(input('param.pdc_id'));
        if ($pdc_id <= 0) {
            ds_json_encode(10001, lang('param_error'));
        }
        $predeposit_model = model('predeposit');
        $condition = array();
        $condition['pdc_id'] = $pdc_id;
        $condition['pdc_payment_state'] = 0;
        $info = $predeposit_model->getPdcashInfo($condition);
        if (!$info) {
            ds_json_encode(10001, lang('admin_predeposit_parameter_error'));
        }
        try {
            $result = $predeposit_model->delPdcash($condition);
            if (!$result) {
                ds_json_encode(10001, lang('admin_predeposit_cash_del_fail'));
            }
            //退还冻结的预存款
            $member_model = model('member');
            $member_info = $member_model->getMemberInfo(array('member_id' => $info['pdc_member_id']));
            //扣除冻结的预存款
            $admininfo = $this->getAdminInfo();
            $data = array();
            $data['member_id'] = $member_info['member_id'];
            $data['member_name'] = $member_info['member_name'];
            $data['amount'] = $info['pdc_amount'];
            $data['order_sn'] = $info['pdc_sn'];
            $data['admin_name'] = $admininfo['admin_name'];
            $predeposit_model->changePd('cash_del', $data);
            $predeposit_model->commit();
            ds_json_encode(10000, lang('admin_predeposit_cash_del_success'));
        } catch (Exception $e) {
            $predeposit_model->commit();
            ds_json_encode(10001, lang($e->getMessage()));
        }
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
            $data = array();
            $data['member_id'] = $member_info['member_id'];
            $data['member_name'] = $member_info['member_name'];
            $data['amount'] = $info['pdc_amount'];
            $data['order_sn'] = $info['pdc_sn'];
            $data['admin_name'] = $admininfo['admin_name'];
            $predeposit_model->changePd('cash_pay', $data);
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
    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('admin_points_log_title'),
                'url' => url('Points/index')
            ),
            array(
                'name' => 'pdcash_list',
                'text' => lang('admin_predeposit_cashmanage'),
                'url' => url('Points/pdcash_list')
            ),
            array(
                'name' => 'pointslog',
                'text' => lang('pointslog'),
                'url' => "javascript:dsLayerOpen('".url('Points/pointslog')."','".lang('pointslog')."')"
            ),
            array(
                'name' => 'setting',
                'text' => lang('points_setting'),
                'url' => url('Points/setting')
            ),
            array(
                'name' => 'draw',
                'text' => lang('points_draw'),
                'url' => url('Points/draw')
            ),
        );
        return $menu_array;
    }

}
