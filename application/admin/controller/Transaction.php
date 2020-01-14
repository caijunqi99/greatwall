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
class Transaction extends AdminControl {
    const EXPORT_SIZE = 5000;
    public function _initialize() {
        parent::_initialize();
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/predeposit.lang.php');
        Lang::load(APP_PATH . 'admin/lang/'.config('default_lang').'/transaction.lang.php');
    }

    public function index() {
        if (!request()->isPost()) {
            $condition_arr = array();
            $mname = input('param.mname');
            if (!empty($mname)) {
                $condition_arr['tl_membername'] = array('like', '%' . $mname . '%');
            }
            $aname = input('param.aname');
            if (!empty($aname)) {
                $condition_arr['tl_adminname'] = array('like', '%' . $aname . '%');
            }
            $stage = input('param.stage');
            if ($stage) {
                $condition_arr['tl_stage'] = trim($stage);
            }
            $stime = input('param.stime');
            $etime = input('param.etime');
            $if_start_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $stime);
            $if_end_time = preg_match('/^20\d{2}-\d{2}-\d{2}$/', $etime);
            $start_unixtime = $if_start_time ? strtotime($stime) : null;
            $end_unixtime = $if_end_time ? strtotime($etime) : null;
            if ($start_unixtime || $end_unixtime) {
                $condition_arr['tl_addtime'] = array('between', array($start_unixtime, $end_unixtime));
            }
            
            $search_desc = trim(input('param.description'));
            if (!empty($search_desc)) {
                $condition_arr['tl_desc'] = array('like', "%" . $search_desc . "%");
            }


            $transaction_model = model('transaction');
            $list_log = $transaction_model->getTransactionlogList($condition_arr, 10, '*', '');

            $this->assign('transactionlog', $list_log);
            $this->assign('show_page', $transaction_model->page_info->render());
            $this->setAdminCurItem('index');
            return $this->fetch();
        }
    }
    public function price() {
            $tranprice_model=model('tranprice');
            $condition_arr=array();
            $list = $tranprice_model->getTranList($condition_arr,'*',10, 't_id desc');
            foreach($list as $k=>$v){
                $list[$k]['t_addtime']=date('Y-m-d H:i:s',$v['t_addtime']);
            }
            $this->assign('tranlist', $list);
            $this->assign('show_page', $tranprice_model->page_info->render());
            $this->setAdminCurItem('price');
            return $this->fetch();

    }
    /*
    * 调节认筹股
    */

    public function ts_add() {
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
            $member_transaction = floatval($member_info['member_transaction']);
            if ($operatetype == 2 && $money > $member_transaction) {
                $this->error(lang('avaliable_predeposit_not_enough') . $member_transaction, 'Transaction/ts_add');
            }
            $transaction_model = model('transaction');
            #生成对应订单号
            $order_sn = makePaySn($member_id);
            $admininfo = $this->getAdminInfo();
            $log_msg = "管理员【" . $admininfo['admin_name'] . "】操作会员【" . $member_info['member_name'] . "】认筹股，金额为" . $money . ",编号为" . $order_sn;
            $admin_act = "sys_add_money";
            switch ($operatetype) {
                case 1:
                    $admin_act = "sys_add_money";
                    $log_msg = "管理员【" . $admininfo['admin_name'] . "】操作会员【" . $member_info['member_name'] . "】认筹股【增加】，金额为" . $money . ",编号为" . $order_sn;
                    break;
                case 2:
                    $admin_act = "sys_del_money";
                    $log_msg = "管理员【" . $admininfo['admin_name'] . "】操作会员【" . $member_info['member_name'] . "】认筹股【减少】，金额为" . $money . ",编号为" . $order_sn;
                    break;
                default:
                    $this->error(lang('ds_common_op_fail'), 'Predeposit/pdlog_list');
                    break;
            }
            try {
                $transaction_model->startTrans();
                //扣除冻结的预存款
                $data = array();
                $data['member_id'] = $member_info['member_id'];
                $data['member_name'] = $member_info['member_name'];
                $data['amount'] = $money;
                $data['order_sn'] = $order_sn;
                $data['admin_name'] = $admininfo['admin_name'];
                $data['admin_id'] = $admininfo['admin_id'];
                $data['pdr_sn'] = $order_sn;
                $data['tl_desc'] = $memo;
                $transaction_model->changePd($admin_act, $data);
                $transaction_model->commit();
                $this->log($log_msg, 1);
                dsLayerOpenSuccess(lang('ds_common_op_succ'));
            } catch (Exception $e) {
                $transaction_model->rollback();
                $this->log($log_msg, 0);
                $this->error($e->getMessage(), 'Predeposit/pdlog_list');
            }
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
            return $this->fetch();
        } else {
            $data = [
                'member_name' => input('post.member_name'),
                'points_type' => input('post.points_type'),
                'points_num' => intval(input('post.points_num')),
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
            } else {
                $insert_arr['pl_points'] = $data['points_num'];
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
            echo json_encode(array('id' => $member_info['member_id'], 'name' => $member_info['member_name'], 'points' => $member_info['member_points']));
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

    protected function getAdminItemList() {
        $menu_array = array(
            array(
                'name' => 'index',
                'text' => lang('ds_transaction_code'),
                'url' => url('Transaction/index')
            ),
            array(
                'name' => 'price',
                'text' => lang('ds_transaction_price'),
                'url' => url('Transaction/price')
            ),
        );
        return $menu_array;
    }

}
