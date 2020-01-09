<?php

namespace app\mobile\controller;


class Memberpoints extends MobileMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
    }

    /**
     * 签到列表
     */
    public function pointslog()
    {
        $condition_arr = $list_log = array();
        $condition_arr['pl_memberid'] = $this->member_info['member_id'];
        //分页
        $points_model = Model('points');
        $list_log = $points_model->getPointsLogList($condition_arr, $this->pagesize, '*', '');
        if (!empty($list_log)) {
            foreach ($list_log as $key => $value) {
                //$list_log[$key]['stagetext'] = $this->insertarr($value['pl_stage']);
                $list_log[$key]['addtimetext'] = date('Y-m-d', $value['pl_addtime']);
                if($value['pl_points']!=0){
                    if($value['pl_points']>0){
                        $desc="冻结积分+".$value['pl_points'];
                    }else{
                        $desc="冻结积分".$value['pl_points'];
                    }
                    if($value['pl_pointsav']!=0) {
                        if ($value['pl_pointsav'] > 0) {
                            $desc = $desc . "，可用积分+" . $value['pl_pointsav'];
                        } else {
                            $desc = $desc . "，可用积分" . $value['pl_pointsav'];
                        }
                    }
                }else{
                    if($value['pl_pointsav']>0){
                        $desc="可用积分+".$value['pl_pointsav'];
                    }else{
                        $desc="可用积分".$value['pl_pointsav'];
                    }
                }
                $list_log[$key]['pl_desc']=$desc;
            }
        }
        output_data(array('log_list' => $list_log), mobile_page($points_model->page_info));
    }

    private function insertarr($stage)
    {
        $insertarr  = '';
        switch ($stage) {
            case 'regist':
                $insertarr = '注册会员';
                break;
            case 'login':
                $insertarr = '会员登录';
                break;
            case 'comments':
                $insertarr = '评论商品';
                break;
            case 'order':
                $insertarr = '购物消费';
                break;
            case 'pointorder':
                $insertarr = '兑换礼品';
                break;
            case 'signin':
                $insertarr = '会员签到';
                break;
        }
        return $insertarr;
    }

    /**
     * 检验是否能签到
     */
    public function checksignin()
    {
        $condition = array();
        $condition['pl_memberid'] = $this->member_info['member_id'];
        $condition['pl_stage'] = 'signin';
        $todate = date('Ymd000000');
        $totime = strtotime($todate);
        $condition['pl_addtime'][] = array('egt', $totime);
        $condition['pl_addtime'][] = array('elt', $totime + 86400);
        $points_model = Model('points');
        $log_array = $points_model->getPointsInfo($condition);
        if (!empty($log_array)) {
            output_error('已签到');
        }
        else {
            $points_signin = intval(config('points_signin'));
            output_data(array('points_signin' => $points_signin));
        }
    }

    /**
     * 签到 array('pl_memberid'=>'会员编号','pl_membername'=>'会员名称','pl_adminid'=>'管理员编号','pl_adminname'=>'管理员名称','pl_points'=>'积分','pl_desc'=>'描述','orderprice'=>'订单金额','order_sn'=>'订单编号','order_id'=>'订单序号','point_ordersn'=>'积分兑换订单编号');
     */
    public function signin_add()
    {
        $points_signin = intval(config('points_signin'));//签到对得积分数
        $points_model = Model('points');
        $insertarr['pl_memberid'] = $this->member_info['member_id'];
        $insertarr['pl_membername'] = $this->member_info['member_name'];
        $insertarr['pl_points'] = $points_signin;
        $insertarr['pl_points'] = $points_signin;
        $return = $points_model->savePointsLog('signin', $insertarr, false);

        output_data(array('point' => $return));
    }
}