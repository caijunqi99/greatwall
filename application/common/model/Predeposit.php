<?php

namespace app\common\model;

use think\Model;
use think\Db;
/**
 * ============================================================================
 
 * ============================================================================
 * 数据层模型
 */
class Predeposit extends Model {

    public $page_info;


    /**
     * 增加充值卡
     * @access public
     * @author bayi-shop
     * @param type $sn
     * @param type $member_info
     * @return type
     * @throws \app\common\model\Exception
     */        
    public function addRechargecard($sn, $member_info) {
        $member_id = $member_info['member_id'];
        $member_name = $member_info['member_name'];

        if ($member_id < 1 || !$member_name) {
            return array('message' => '当前登录状态为未登录，不能使用充值卡');
        }

        $rechargecard_model = model('rechargecard');

        $card = $rechargecard_model->getRechargecardBySN($sn);

        if (empty($card) || $card['rc_state'] != 0 || $card['member_id'] != 0) {
            return array('message' => '充值卡不存在或已被使用');
        }

        $card['member_id'] = $member_id;
        $card['member_name'] = $member_name;

        try {
            $this->startTrans();

            $rechargecard_model->setRechargecardUsedById($card['rc_id'], $member_id, $member_name);

            $card['amount'] = $card['rc_denomination'];
            $this->changeRcb('recharge', $card);

            $this->commit();
        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    /**
     * 取得充值列表
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @param type $pagesize 页面大小
     * @param type $fields 字段
     * @param type $order 排序
     * @return type
     */
    public function getPdRechargeList($condition = array(), $pagesize = '', $fields = '*', $order = '') {
        if ($pagesize) {
            $result = db('pdrecharge')->where($condition)->field($fields)->order($order)->paginate($pagesize, false, ['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        } else {
            return db('pdrecharge')->where($condition)->field($fields)->order($order)->select();
        }
    }

    /**
     * 添加充值记录
     * @access public
     * @author bayi-shop
     * @param type $data 参数内容
     * @return bool
     */
    public function addPdRecharge($data) {
        return db('pdrecharge')->insertGetId($data);
    }

    /**
     * 编辑
     * @access public
     * @author bayi-shop
     * @param type $data 数据
     * @param type $condition 条件
     * @return bool
     */
    public function editPdRecharge($data, $condition = array()) {
        return db('pdrecharge')->where($condition)->update($data);
    }

    /**
     * 取得单条充值信息
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @param type $fields 字段
     * @return type
     */
    public function getPdRechargeInfo($condition = array(), $fields = '*') {
        return db('pdrecharge')->where($condition)->field($fields)->find();
    }

    /**
     * 取充值信息总数
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @return int
     */
    public function getPdRechargeCount($condition = array()) {
        return db('pdrecharge')->where($condition)->count();
    }

    /**
     * 取提现单信息总数
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return int
     */
    public function getPdcashCount($condition = array()) {
        return db('pdcash')->where($condition)->count();
    }

    /**
     * 取日志总数
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return int
     */
    public function getPdLogCount($condition = array()) {
        return db('pdlog')->where($condition)->count();
    }

    /**
     * 取得预存款变更日志列表
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @param type $pagesize 页面信息
     * @param type $fields 字段
     * @param type $order 排序
     * @param type $limit 限制
     * @return array
     */
    public function getPdLogList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = '') {
        if ($pagesize) {
            $pdlog_list_paginate = db('pdlog')->where($condition)->field($fields)->order($order)->paginate($pagesize, false, ['query' => request()->param()]);
            $this->page_info = $pdlog_list_paginate;
            return $pdlog_list_paginate->items();
        } else {
            $pdlog_list_paginate = db('pdlog')->where($condition)->field($fields)->order($order)->limit($limit)->select();
            return $pdlog_list_paginate;
        }
    }

    /**
     * 变更充值卡余额
     * @access public
     * @author bayi-shop
     * @param type $type 类型
     * @param type $data 数据
     * @return type
     */
    public function changeRcb($type, $data = array()) {
        $amount = (float) $data['amount'];
        if ($amount < .01) {
            exception('参数错误');
        }

        $available = $freeze = 0;
        $desc = null;

        switch ($type) {
            case 'order_pay':
                $available = -$amount;
                $desc = '下单，使用充值卡余额，订单号: ' . $data['order_sn'];
                break;

            case 'order_freeze':
                $available = -$amount;
                $freeze = $amount;
                $desc = '下单，冻结充值卡余额，订单号: ' . $data['order_sn'];
                break;

            case 'order_cancel':
                $available = $amount;
                $freeze = -$amount;
                $desc = '取消订单，解冻充值卡余额，订单号: ' . $data['order_sn'];
                break;

            case 'order_comb_pay':
                $freeze = -$amount;
                $desc = '下单，扣除被冻结的充值卡余额，订单号: ' . $data['order_sn'];
                break;

            case 'recharge':
                $available = $amount;
                $desc = '平台充值卡充值，充值卡号: ' . $data['rc_sn'];
                break;

            case 'refund':
                $available = $amount;
                $desc = '确认退款，订单号: ' . $data['order_sn'];
                break;

            case 'vr_refund':
                $available = $amount;
                $desc = '虚拟兑码退款成功，订单号: ' . $data['order_sn'];
                break;

            default:
                exception('参数错误');
        }

        $update = array();
        if ($available) {
            $update['available_rc_balance'] = Db::raw('available_rc_balance+'.$available);
        }
        if ($freeze) {
            $update['freeze_rc_balance'] = Db::raw('freeze_rc_balance+'.$freeze);
        }

        if (!$update) {
            exception('参数错误');
        }

        // 更新会员
        $updateSuccess = model('member')->editMember(array(
            'member_id' => $data['member_id'],
                ), $update);

        if (!$updateSuccess) {
            exception('操作失败');
        }

        // 添加日志
        $rcblog = array(
            'member_id' => $data['member_id'],
            'member_name' => $data['member_name'],
            'rcblog_type' => $type,
            'rcblog_addtime' => TIMESTAMP,
            'available_amount' => $available,
            'freeze_amount' => $freeze,
            'rcblog_description' => $desc,
        );

        $insertSuccess = db('rcblog')->insertGetId($rcblog);
        if (!$insertSuccess) {
            exception('操作失败');
        }

        $msg = array(
            'code' => 'recharge_card_balance_change',
            'member_id' => $data['member_id'],
            'param' => array(
                'time' => date('Y-m-d H:i:s', TIMESTAMP),
                'url' => url('Home/Predeposit/rcb_log_list'),
                'available_amount' => ds_price_format($available),
                'freeze_amount' => ds_price_format($freeze),
                'description' => $desc,
            ),
        );

        // 发送买家消息
        \mall\queue\QueueClient::push('sendMemberMsg', $msg);
        return $insertSuccess;
    }

    /**
     * 变更预存款
     * @access public
     * @author bayi-shop
     * @param type $change_type
     * @param type $data
     * @return type
     */
    public function changePd($change_type, $data = array()) {
        $data_log = array();
        $data_pd = array();
        $data_msg = array();

        $data_log['lg_member_id'] = $data['member_id'];
        $data_log['lg_member_name'] = $data['member_name'];
        $data_log['lg_addtime'] = TIMESTAMP;
        $data_log['lg_type'] = $change_type;

        $data_msg['time'] = date('Y-m-d H:i:s');
        $data_msg['pd_url'] = url('home/Predeposit/pd_log_list');
        switch ($change_type) {
            case 'order_pay':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '下单，支付储值卡，订单号: ' . $data['order_sn'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_freeze':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '下单，冻结储值卡，订单号: ' . $data['order_sn'];
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit+'.$data['amount']);
                $data_pd['available_predeposit'] = Db::raw('available_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_cancel':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '取消订单，解冻储值卡，订单号: ' . $data['order_sn'];
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit-'.$data['amount']);
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_comb_pay':
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '下单，支付被冻结的储值卡，订单号: ' . $data['order_sn'];
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = 0;
                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'recharge':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '充值，充值单号: ' . $data['pdr_sn'];
                $data_log['lg_admin_name'] = isset($data['admin_name']) ? $data['admin_name'] : '会员' . $data['member_name'] . '在线充值';
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;

            case 'refund':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '确认退款，订单号: ' . $data['order_sn'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'vr_refund':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '虚拟兑码退款成功，订单号: ' . $data['order_sn'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'cash_apply':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '申请提现，冻结储值卡，提现单号: ' . $data['order_sn'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit-'.$data['amount']);
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'cash_pay':
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '提现成功，提现单号: ' . $data['order_sn'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = 0;
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'cash_del':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '取消提现申请，解冻储值卡，提现单号: ' . $data['order_sn'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'sys_add_money':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = '管理员调节储值卡【增加】，充值单号: ' . $data['pdr_sn'].',备注：'.$data['lg_desc'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'sys_del_money':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '管理员调节储值卡【减少】，充值单号: ' . $data['pdr_sn'].',备注：'.$data['lg_desc'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'sys_freeze_money':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '管理员调节储值卡【冻结】，充值单号: ' . $data['pdr_sn'].',备注：'.$data['lg_desc'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit-'.$data['amount']);
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'sys_unfreeze_money':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '管理员调节储值卡【解冻】，充值单号: ' . $data['pdr_sn'].',备注：'.$data['lg_desc'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_inviter':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = $data['lg_desc'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'bonus':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = $data['lg_desc'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'pointransform_add':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_desc'] = $data['lg_desc'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            //end

            default:
                exception('参数错误');
                break;
        }

        $update = model('member')->editMember(array('member_id' => $data['member_id']), $data_pd);

        if (!$update) {
            exception('操作失败');
        }
        $insert = db('pdlog')->insertGetId($data_log);
        if (!$insert) {
            exception('操作失败');
        }

        // 支付成功发送买家消息
        $message = array();
        $message['code'] = 'predeposit_change';
        $message['member_id'] = $data['member_id'];
        $data_msg['av_amount'] = ds_price_format($data_msg['av_amount']);
        $data_msg['freeze_amount'] = ds_price_format($data_msg['freeze_amount']);
        $message['param'] = $data_msg;
        \mall\queue\QueueClient::push('sendMemberMsg', $message);
        return $insert;
    }



    /**
     * 充值返利
     * @DateTime 2019-12-11
     * @param    [type]     $member_info [description]
     * @param    [type]     $amount      [description]
     * @return   [type]                  [description]
     */
    public function PdRebate($member_info,$amount){
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
        if ($exppointone!=0) {
            $insert_arr['pl_memberid'] = $member_info_one['member_id'];
            $insert_arr['pl_membername'] = $member_info_one['member_name'];
            if($list_config['way']==1) {
                $insert_arr['pl_points'] = 0;
                $insert_arr['pl_pointsav'] = $amount * $exppointone / 100;
                $insert_arr['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原可用积分为：".$member_info_one['member_points_available'];
            }else if($list_config['way']==2){
                $insert_arr['pl_points'] = $amount * $exppointone / 100;
                $insert_arr['pl_pointsav'] = 0;
                $insert_arr['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原冻结积分为：".$member_info_one['member_points'];
            }
            $insert_arr['pl_adminname'] = !empty(session('admin_name'))?session('admin_name'):'';
            model('points')->savePointslog('system', $insert_arr);
        }
        
        if(isset($member_info_two)){
            if ($exppointtwo!=0) {

                $insert_arrs['pl_memberid'] = $member_info_two['member_id'];
                $insert_arrs['pl_membername'] = $member_info_two['member_name'];
                if($list_config['way']==1) {
                   $insert_arrs['pl_points'] = 0;
                   $insert_arrs['pl_pointsav'] = $amount*$exppointtwo/100;
                    $insert_arrs['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原可用积分为：".$member_info_two['member_points_available'];
                }elseif($list_config['way']==2){
                    $insert_arrs['pl_points'] = $amount * $exppointtwo / 100;
                    $insert_arrs['pl_pointsav'] = 0;
                    $insert_arrs['pl_desc'] ="来自".$member_info['member_name']."充值返利,充值金额：".$amount."元，原冻结积分为：".$member_info_two['member_points'];
                }
                $insert_arrs['pl_adminname'] = !empty(session('admin_name'))?session('admin_name'):'';
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
            $insert_arr['pl_adminname'] = !empty(session('admin_name'))?session('admin_name'):'';
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
            $insert_arrtown['pl_adminname'] = !empty(session('admin_name'))?session('admin_name'):'';
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
            $insert_arrarea['pl_adminname'] = !empty(session('admin_name'))?session('admin_name'):'';
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
            $insert_arrcity['pl_adminname'] = !empty(session('admin_name'))?session('admin_name'):'';
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
            $insert_arrpro['pl_adminname'] = !empty(session('admin_name'))?session('admin_name'):'';
            model('points')->savePointslog('system', $insert_arrpro);
        }

    }

    /**
     * 删除充值记录
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return type
     */
    public function delPdRecharge($condition) {
        return db('pdrecharge')->where($condition)->delete();
    }

    /**
     * 取得提现列表
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @param type $pagesize 页面
     * @param type $fields 字段
     * @param type $order 排序
     * @param type $limit 限制
     * @return type
     */
    public function getPdcashList($condition = array(), $pagesize = '', $fields = '*', $order = '', $limit = '') {
        if ($pagesize) {
            $pdcash_list_paginate = db('pdcash')->where($condition)->field($fields)->order($order)->paginate($pagesize, false, ['query' => request()->param()]);
            $this->page_info = $pdcash_list_paginate;
            return $pdcash_list_paginate->items();
        } else {
            return db('pdcash')->where($condition)->field($fields)->order($order)->limit($limit)->select();
        }
    }

    /**
     * 添加提现记录
     * @access public
     * @author bayi-shop
     * @param type $data 数据
     * @return bool
     */
    public function addPdcash($data) {
        return db('pdcash')->insertGetId($data);
    }

    /**
     * 编辑提现记录
     * @access public
     * @author bayi-shop
     * @param type $data 数据
     * @param type $condition 条件
     * @return bool
     */
    public function editPdcash($data, $condition = array()) {
        return db('pdcash')->where($condition)->update($data);
    }

    /**
     * 取得单条提现信息
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @param type $fields 字段
     * @return type
     */
    public function getPdcashInfo($condition = array(), $fields = '*') {
        return db('pdcash')->where($condition)->field($fields)->find();
    }

    /**
     * 删除提现记录
     * @access public
     * @author bayi-shop
     * @param type $condition 条件
     * @return type
     */
    public function delPdcash($condition) {
        return db('pdcash')->where($condition)->delete();
    }

}
