<?php
/**
 * 积分互转接口
 */
namespace app\mobile\controller;


class Membertransform extends MobileMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub

        if(config('member_auth')){
            if ($this->member_info['member_auth_state']==0) {
                output_error('您需要先到我的钱包申请实名认证！');
            }elseif ($this->member_info['member_auth_state']==2) {
                output_error('您的实名认证信息未通过审核，请重新提交！');
            }
        }
    }


    /**
     * 转换可用积分到储值卡余额或数字币
     * @DateTime 2019-12-06
     */
    public function PointTransform(){
        $transType = intval(input('param.transtype'));
        $transPoint = abs(floatval(input('param.point')));
        //判断用户剩余可用积分是否足够
        if (empty($this->member_info['member_points_available']) || intval($this->member_info['member_points_available'])<=0 || intval($this->member_info['member_points_available']) - $transPoint <0) {
            output_error('剩余可用积分不足！');
        }
        $act = "pointransform";
        $msg = '用户申请互转，';
        switch ($transType) {
            case 1:     //转到储值卡余额
                $balance = $transPoint* config('point_to_store');
                $msg .= $rmsg = '储值卡【增加】 '.$balance.' ,积分【减少】'.$transPoint.'，'.'本次交易转换比率为 【1：'.config('point_to_store').'】';
                $result = $this->TransToPdcash($balance,$msg,$act);
                break;
            
            case 2:     //转到交易码
                $bitcoin = $transPoint*config('inter');
                $msg .= $rmsg = '交易码【增加】 '.$bitcoin.' ,积分【减少】'.$transPoint.'，'.'本次交易转换比率为 【1：'.config('inter').'】';
                $result = $this->TransToBitcoin($bitcoin,$msg,$act);
                break;
        }
        //改变用户积分并增加记录
        if ($result) {
            //写入积分变动日志
           $insertArr = [
                'pl_memberid'   =>$this->member_info['member_id'],
                'pl_membername' =>$this->member_info['member_name'],
                'pl_points'     =>0,
                'pl_pointsav'   =>-$transPoint,
                'pl_desc'       =>$msg
            ];
            $transfer = model('Points')->savePointslog($act,$insertArr);
            if ($transfer) {
                output_data([
                    'state' =>true,
                    'msg' =>'本次申请互转成功！'.$rmsg
                ]);
            }else{
                output_error('互转失败，请稍后尝试！');
            }
        }else{
            output_error('互转失败，请稍后尝试！');
        }
        
    }

    /**
     * 积分转移到储值卡
     * @DateTime 2019-12-06
     * @param    [type]     $balance [转移数量]
     * @param    [type]     $memo    [备注]
     * @param    [type]     $act     [操作]
     */
    private function TransToPdcash($balance,$memo,$act){
        $member_info = $this->member_info;
        $predeposit_model = model('predeposit');
        #生成对应订单号
        $order_sn = makePaySn($this->member_info['member_id']);
        try {
            $predeposit_model->startTrans();
            //扣除冻结的预存款
            $data = array();
            $data['member_id']   = $member_info['member_id'];
            $data['member_name'] = $member_info['member_name'];
            $data['amount']      = $balance;
            $data['order_sn']    = $order_sn;
            $data['pdr_sn']      = $order_sn;
            $data['lg_desc']     = $memo;
            $predeposit_model->changePd($act.'_add', $data);
            $predeposit_model->commit();
            return true;
        } catch (Exception $e) {
            $predeposit_model->rollback();
            output_error($e->getMessage());
        }
    }

    /**
     * 积分转移到交易码
     * @DateTime 2019-12-06
     * @param    [type]     $bitcoin [转移数量]
     * @param    [type]     $memo    [备注]
     * @param    [type]     $act     [操作]
     */
    private function TransToBitcoin($bitcoin,$memo,$act){
        $member_info = $this->member_info;
        $predeposit_model = model('transaction');
        #生成对应订单号
        $order_sn = makePaySn($this->member_info['member_id']);
        try {
            $predeposit_model->startTrans();
            //扣除冻结的预存款
            $data = array();
            $data['member_id']   = $member_info['member_id'];
            $data['member_name'] = $member_info['member_name'];
            $data['amount']      = $bitcoin;
            $data['order_sn']    = $order_sn;
            $data['pdr_sn']      = $order_sn;
            $data['tl_desc']     = $memo;
            $data['tl_stage']    = $act;
            $predeposit_model->changePd($act.'_add', $data);
            $predeposit_model->commit();
            return true;
        } catch (Exception $e) {
            $predeposit_model->rollback();
            output_error($e->getMessage());
        }
    }

    

}