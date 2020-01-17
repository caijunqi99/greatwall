<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/8/16
 * Time: 20:22
 */

namespace app\mobile\controller;

class Memberfund extends MobileMember {

    public function _initialize() {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 储值卡日志列表
     */
    public function predepositlog() {
        $model_predeposit = Model('predeposit');
        $where = array();
        $where['lg_member_id'] = $this->member_info['member_id'];
        $where['lg_av_amount'] = array('neq', 0);
        $list = $model_predeposit->getPdLogList($where, $this->pagesize, '*', 'lg_id desc');
        if ($list) {
            foreach ($list as $k => $v) {
                $v['lg_add_time_text'] = @date('Y-m-d', $v['lg_addtime']);
                $list[$k] = $v;
                if($v['lg_av_amount']>0){
                    $list[$k]['lg_desc']="储值卡增加".$v['lg_av_amount']."元";
                }else{
                    $list[$k]['lg_desc']="储值卡减少".$v['lg_av_amount']."元";
                }
            }
        }
        output_data(array('list' => $list), mobile_page($model_predeposit->page_info));
    }
    /**
     * 认筹股日志列表
     */
    public function transactionlog() {
        $model_transaction = Model('transaction');
        $where = array();
        $where['tl_memberid'] = $this->member_info['member_id'];
        $where['tl_transaction'] = array('neq', 0);
        $list = $model_transaction->getTransactionlogList($where, $this->pagesize, '*', 'tl_id desc');
        if ($list) {
            foreach ($list as $k => $v) {
                $v['tl_add_time_text'] = @date('Y-m-d', $v['tl_addtime']);
                $list[$k] = $v;
                if($v['tl_transaction']>0){
                    $list[$k]['tl_desc']="认筹股增加".$v['tl_transaction'];
                }else{
                    $list[$k]['tl_desc']="认筹股减少".$v['tl_transaction'];
                }
            }
        }
        output_data(array('list' => $list), mobile_page($model_transaction->page_info));
    }

    /**
     * 充值卡余额变更日志
     */
    public function rcblog() {
        $model_rcb_log = model('rcblog');
        $where = array();
        $where['member_id'] = $this->member_info['member_id'];
        $where['available_amount'] = array('neq', 0);
        $log_list = $model_rcb_log->getRechargeCardBalanceLogList($where, $this->pagesize, '', 'id desc');
        if ($log_list) {
            foreach ($log_list as $k => $v) {
                $v['add_time_text'] = @date('Y-m-d H:i:s', $v['add_time']);
                $log_list[$k] = $v;
            }
        }
        output_data(array('log_list' => $log_list), mobile_page($model_rcb_log->page_info));
    }

    /**
     * 充值明细
     */
    public function pdrechargelist() {
        $where = array();
        $where['pdr_member_id'] = $this->member_info['member_id'];
        $model_pd = Model('predeposit');
        $list = $model_pd->getPdRechargeList($where, $this->pagesize, '*', 'pdr_id desc');

        if ($list) {
            foreach ($list as $k => $v) {
                $v['pdr_add_time_text'] = @date('Y-m-d H:i:s', $v['pdr_add_time']);
                $v['pdr_payment_state_text'] = $v['pdr_payment_state'] == 1 ? '已支付' : '未支付';
                $list[$k] = $v;
            }
        }
        output_data(array('list' => $list), mobile_page($model_pd->page_info));
    }

    /**
     * 提现记录
     */
    public function pdcashlist() {
        $where = array();
        $where['pdc_member_id'] = $this->member_info['member_id'];
        $model_pd = Model('predeposit');
        $list = $model_pd->getPdCashList($where, $this->pagesize, '*', 'pdc_id desc');
        if ($list) {
            foreach ($list as $k => $v) {
                $v['pdc_add_time_text'] = @date('Y-m-d H:i:s', $v['pdc_addtime']);
                $v['pdc_payment_time_text'] = @date('Y-m-d H:i:s', $v['pdc_payment_time']);
                $v['pdc_payment_state_text'] = $v['pdc_payment_state'] == 1 ? '已支付' : '未支付';
                $list[$k] = $v;
            }
        }
        output_data(array('list' => $list), mobile_page($model_pd->page_info));
    }

    /**
     * 充值卡充值
     */
    public function rechargecard_add() {
        $param = $_POST;
        $rc_sn = trim($param["rc_sn"]);

        if (!$rc_sn) {
            output_error('请输入平台充值卡号');
        }
           $res= Model('predeposit')->addRechargeCard($rc_sn, array('member_id' => $this->member_info['member_id'], 'member_name' => $this->member_info['member_name']));
           if($res['message'])
               output_error($res['message']);
            output_data('1');
    }

    /**
     * 预存款提现记录详细
     */
    public function pdcashinfo() {
        $param = $_GET;
        $pdc_id = intval($param["pdc_id"]);
        if ($pdc_id <= 0) {
            output_error('参数错误');
        }
        $where = array();
        $where['pdc_member_id'] = $this->member_info['member_id'];
        $where['pdc_id'] = $pdc_id;
        $info = Model('predeposit')->getPdCashInfo($where);
        if (!$info) {
            output_error('参数错误');
        }
        $info['pdc_add_time_text'] = $info['pdc_addtime'] ? @date('Y-m-d H:i:s', $info['pdc_addtime']) : '';
        $info['pdc_payment_time_text'] = $info['pdc_payment_time'] ? @date('Y-m-d H:i:s', $info['pdc_payment_time']) : '';
        $info['pdc_payment_state_text'] = $info['pdc_payment_state'] == 1 ? '已支付' : '未支付';
        output_data(array('info' => $info));
    }

    /**
     * 充值列表
     */
    public function index() {
        $condition = array();
        $condition['pdr_member_id'] = $this->member_info['member_id'];
        if (!empty($_GET['pdr_sn'])) {
            $condition['pdr_sn'] = $_GET['pdr_sn'];
        }

        $model_pd = Model('predeposit');
        $list = $model_pd->getPdRechargeList($condition, 20, '*', 'pdr_id desc');
        foreach ($list as $key => $value) {
            $list[$key]['pdr_add_time_text'] = date('Y-m-d H:i:s', $value['pdr_add_time']);
        }
        output_data(array('list' => $list), mobile_page($model_pd->page_info));
    }

    /**
     * 我的积分 我的余额
     */
    public function my_asset() {
        $point = $this->member_info['member_points'];
        output_data(array('point' => $point));
    }

    protected function getMemberAndGradeInfo($is_return = false) {
        $member_info = array();
        //会员详情及会员级别处理
        if ($this->member_info['member_id']) {
            $model_member = Model('member');
            $member_info = $model_member->getMemberInfoByID($this->member_info['member_id']);
            if ($member_info) {
                $member_gradeinfo = $model_member->getOneMemberGrade(intval($member_info['member_exppoints']));
                $member_info = array_merge($member_info, $member_gradeinfo);
                $member_info['security_level'] = $model_member->getMemberSecurityLevel($member_info);
            }
        }
        if ($is_return == true) {//返回会员信息
            return $member_info;
        } else {//输出会员信息
            $this->assign('member_info', $member_info);
        }
    }
    /**
     * 认筹股折线图
     */
    public function tranprice() {
        $tranprice_model=model('tranprice');
        $condition_arr=array();
        $list = $tranprice_model->getTranList($condition_arr,'*',7, 't_id desc');
        $end = end($list);
        foreach($list as $k=>$v){
            $list[$k]['t_addtime']=date('m-d',$v['t_addtime']);
            if ($k == 0) {
                // $list[$k]['t_addtime']='当前';
            }
        }
        $list = array_reverse($list);
        output_data(array('list' => $list));
    }

    /**
     * AJAX验证
     *
     */
    protected function check() {
        if (checkSeccode($_POST['nchash'], $_POST['captcha'])) {
            return true;
        } else {
            return false;
        }
    }

}