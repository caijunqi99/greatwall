<?php
/**
 * 营销抽奖接口
 */
namespace app\mobile\controller;


class Membermarket extends MobileMember
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $this->marketmanage_type=input('param.marketmanage_id',2);
    }


    /**
     * 获取转盘信息
     * @DateTime 2019-12-04
     */
    public function GetMarketWheel(){
        $paySn=trim(input('param.pay_sn'));
        $orderModel = model('order');
        //查询订单信息，获取抽奖次数
        $orderInfo = $orderModel->getOrderInfo([
            'buyer_id'=>$this->member_info['member_id'],
            'order_state'=>ORDER_STATE_PAY,
            'pay_sn'=>$paySn,
            'draw_num'=> 1
        ]);
        if (empty($orderInfo) ){
            output_error('您的抽奖次数已经用完了！');
        }
        $marketmanage_model = model('marketmanage');
        $condition['marketmanage_type'] = $this->marketmanage_type;
        $marketmanage_list = $marketmanage_model->getMarketmanageList($condition, 10);
        $marketmanage_list = array_column($marketmanage_list, null ,'marketmanage_grade');
        //获取当前等级的抽奖转盘
        $marketinfo = $marketmanage_list[$this->member_info['level']];
        $marketinfo['botton'] = UPLOAD_SITE_URL . '/' . ATTACH_WARD . '/choujiang.png?t='.time();
        //获取转盘奖品
        $MarketAward = $marketmanage_model->getMarketmanageAwardList(['marketmanage_id'=>$marketinfo['marketmanage_id']]);
        foreach($MarketAward as $k=>$v){
            $MarketAward[$k]['marketmanageaward_picture']=UPLOAD_SITE_URL . '/' . ATTACH_WARD . '/'.$v['marketmanageaward_picture'];
        }
        //修改抽奖次数
        $orderModel->editOrder(['draw_num'=>0],[
            'buyer_id'=>$this->member_info['member_id'],
            'order_state'=>ORDER_STATE_PAY,
            'pay_sn'=>$paySn,
            'draw_num'=> 1
        ]);
        output_data([
            'marketmanage_info' =>$marketinfo,
            'marketmanageaward_list' =>$MarketAward
        ]);
    }

    /**
     * 抽奖开始
     * @DateTime 2019-12-04
     */
    public function ToSurprise(){
        $marketmanage_model = model('marketmanage');
        $condition['marketmanage_type'] = $this->marketmanage_type;
        $marketmanage_list = $marketmanage_model->getMarketmanageList($condition, 10);
        $marketmanage_list = array_column($marketmanage_list, null ,'marketmanage_grade');
        //获取当前等级的抽奖转盘
        $marketinfo = $marketmanage_list[$this->member_info['level']];
        //获取所有转盘奖品
        $MarketAward = $marketmanage_model->getMarketmanageAwardList(['marketmanage_id'=>$marketinfo['marketmanage_id']]);
        $leftAward = array_column($MarketAward, null ,'marketmanageaward_id');
        //加入空奖  --并删除已发放完的奖品
        $probability = config('marketmanageaward_probability');
        foreach ($MarketAward as $key => $val) {  
            //删除已发放完的奖品
            if ($val['marketmanageaward_count']<=0 && $val['marketmanageaward_type']!=3) {
                unset($MarketAward[$key]);
            }else{
                //$probability -= $val['marketmanageaward_probability'];//概率数组  
            }
        } 
        //空奖
        // $nothingAward = [
        //     'marketmanageaward_id' => 99999,
        //     'marketmanageaward_probability' => $probability
        // ];
        //如果奖品已发放完毕， 100%空奖
        // if (empty($MarketAward)) {
        //     $MarketAward[]=$nothingAward;
        // }else{
        //     array_push($MarketAward, $nothingAward);
        // }
        //开始抽奖
        sort($MarketAward);
        $result = $this->GetSurprise($MarketAward);
        foreach ($leftAward as $key => &$v) {
            $v['marketmanageaward_picture'] = UPLOAD_SITE_URL . '/' . ATTACH_WARD . '/'.$v['marketmanageaward_picture'];
        }
        unset($v);
        //获取最终奖品
        $LastAward = isset($leftAward[$result['yes']])?$leftAward[$result['yes']]:[];
        $return = [];
        $return['count_left'] = 0;//当前剩余抽奖次数
        $return['draw_result'] = $LastAward?true:false;//是否中奖
        $return['draw_info'] =$LastAward;//中奖信息
        if ($return['draw_result'] && $LastAward['marketmanageaward_type']!=3) {
            //处理奖品
            $return['awardMsg'] = $this->HandleAward($LastAward);
        }else{
            $return['draw_result'] = false;
            $return['awardMsg'] = $marketinfo['marketmanage_failed'];
        }
        output_data($return);
    }

    /**
     * 处理奖品
     * @DateTime 2019-12-05
     * @param    [type]     $LastAward [description]
     */
    public function HandleAward($LastAward){
        $msg = '恭喜手机号码为【'.$this->member_info['member_mobile'].'】的用户【'.$this->member_info['member_name'].'】,抽中'.number2chinese($LastAward['marketmanageaward_level']).'等奖，';
        //积分奖励
        if ($LastAward['marketmanageaward_type']==1) {
            $msg .= '奖励 '.intval($LastAward['marketmanageaward_point']).' 积分!';
            $insertArr = [
                'pl_memberid'   =>$this->member_info['member_id'],
                'pl_membername' =>$this->member_info['member_name'],
                'pl_points'     =>intval($LastAward['marketmanageaward_point']),
                'pl_desc'       =>$msg
            ];

            //改变用户积分并增加记录
            model('Points')->savePointslog('marketmanage',$insertArr);
        }
        //实物奖励
        if ($LastAward['marketmanageaward_type']==2) {
            $msg .= '奖励 '.$LastAward['bonus_id'].'!';
        }
        //改变奖品数量
        $marketmanage_model = model('marketmanage');
        $awardEdit = [
            'marketmanageaward_count' =>$LastAward['marketmanageaward_count']-1 <= 0 ? 0 : $LastAward['marketmanageaward_count']-1, //奖品库存减一
            'marketmanageaward_send'  =>$LastAward['marketmanageaward_send']+1 //中奖数量加1
        ];  
        $marketmanage_model->editMarketmanageAward(['marketmanageaward_id'=> $LastAward['marketmanageaward_id']],$awardEdit);
        //写入领取记录
        $log = [
            'member_id'              => $this->member_info['member_id'], //用户ID
            'member_name'            => $this->member_info['member_name'], //会员名
            'marketmanage_id'        => $LastAward['marketmanage_id'], //营销活动活动ID
            'marketmanageaward_id'   => $LastAward['marketmanageaward_id'], //中奖的奖品ID
            'marketmanagelog_win'    => 1, //是否中奖 0未中奖 1中奖
            'marketmanagelog_time'   => TIMESTAMP, //参与时间
            'marketmanagelog_remark' => $msg, //备注领取信息
        ];
        $marketmanage_model->addMarketmanageLog($log);

        return $msg;
    }


    /**
     * 获取奖品
     * @DateTime 2019-12-05
     * @param    [type]     $MarketAward [奖品]
     */
    private function GetSurprise($MarketAward){ 
        //拼装奖项数组 
        $leftRid = array_column($MarketAward,null, 'marketmanageaward_id');
        foreach ($leftRid as $key => $val) {  
            $arr[$val['marketmanageaward_id']] = $val['marketmanageaward_probability'];//概率数组  
        }  
        $rid = $this->RandSurprise($arr); //根据概率获取奖项id  
        $res['yes'] = $leftRid[$rid]['marketmanageaward_id']; //中奖项  
        unset($leftRid[$rid]); //将中奖项从数组中剔除，剩下未中奖项  
        shuffle($leftRid); //打乱数组顺序 
        sort($leftRid) ;
        for($i=0;$i<count($leftRid);$i++){  
            $pr[] = $leftRid[$i]['marketmanageaward_id']; //未中奖项数组 
        }  
        $res['no'] = isset($pr)?$pr:[]; 
        return $res;
    } 
    
    /**
     * 计算中奖概率
     * @DateTime 2019-12-05
     * @param    [type]     $proArr [description]
     * @return   [type]             [description]
     */
    private function RandSurprise($proArr) {  
        $result = '';  
        //概率数组的总概率精度  
        $proSum = array_sum($proArr);
        //概率数组循环  
        foreach ($proArr as $key => $proCur) {  
            $randNum = mt_rand(1, $proSum); //返回随机整数 
            if ($randNum <= $proCur) {  
                $result = $key;  
                break;  
            } else {  
                $proSum -= $proCur;  
            }  
        }  
        unset ($proArr);  
        return $result;  
    }

}