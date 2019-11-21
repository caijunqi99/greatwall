<?php
namespace app\common\model;

use think\Model;
use think\Db;
/**
 * ============================================================================

 * ============================================================================
 * 数据层模型
 */
class Transaction extends Model{

    public $page_info;

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

        $data_log['tl_memberid'] = $data['member_id'];
        $data_log['tl_membername'] = $data['member_name'];
        $data_log['tl_addtime'] = TIMESTAMP;
        $data_log['tl_stage'] = 'system';
        $data_msg['time'] = date('Y-m-d H:i:s');
        switch ($change_type) {
            case 'order_pay':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '下单，支付预存款，订单号: ' . $data['order_sn'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_freeze':
                $data_log['lg_av_amount'] = -$data['amount'];
                $data_log['lg_freeze_amount'] = $data['amount'];
                $data_log['lg_desc'] = '下单，冻结预存款，订单号: ' . $data['order_sn'];
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit+'.$data['amount']);
                $data_pd['available_predeposit'] = Db::raw('available_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = $data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_cancel':
                $data_log['lg_av_amount'] = $data['amount'];
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '取消订单，解冻预存款，订单号: ' . $data['order_sn'];
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit-'.$data['amount']);
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'order_comb_pay':
                $data_log['lg_freeze_amount'] = -$data['amount'];
                $data_log['lg_desc'] = '下单，支付被冻结的预存款，订单号: ' . $data['order_sn'];
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
                $data_log['lg_desc'] = '申请提现，冻结预存款，提现单号: ' . $data['order_sn'];
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
                $data_log['lg_desc'] = '取消提现申请，解冻预存款，提现单号: ' . $data['order_sn'];
                $data_log['lg_admin_name'] = $data['admin_name'];
                $data_pd['available_predeposit'] = Db::raw('available_predeposit+'.$data['amount']);
                $data_pd['freeze_predeposit'] = Db::raw('freeze_predeposit-'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = -$data['amount'];
                $data_msg['desc'] = $data_log['lg_desc'];
                break;
            case 'sys_add_money':
                $data_log['tl_transaction'] = $data['amount'];
                $data_log['tl_desc'] = '管理员调节交易码【增加】，充值单号: ' . $data['pdr_sn'].',备注：'.$data['tl_desc'];
                $data_log['tl_adminname'] = $data['admin_name'];
                $data_pd['member_transaction'] = Db::raw('member_transaction+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['tl_desc'];
                break;
            case 'sys_del_money':
                $data_log['tl_transaction'] = -$data['amount'];
                $data_log['tl_desc'] = '管理员调节交易码【减少】，充值单号: ' . $data['pdr_sn'].',备注：'.$data['tl_desc'];
                $data_log['tl_adminname'] = $data['admin_name'];
                $data_pd['member_transaction'] = Db::raw('member_transaction-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['tl_desc'];
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
            //end

            default:
                exception('参数错误');
                break;
        }

        $update = model('member')->editMember(array('member_id' => $data['member_id']), $data_pd);

        if (!$update) {
            exception('操作失败');
        }
        $insert = db('transactionlog')->insertGetId($data_log);
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
     * 交易码日志列表
     * @access public
     * @author bayi-shop
     * @param type $condition
     * @param type $pagesize
     * @param type $field
     * @return type
     */
    public function getTransactionlogList($condition, $pagesize = '', $field = '*',$limit='') {
        $order = isset($condition['order']) ? $condition['order'] : 'tl_id desc';
        if ($pagesize) {
            $result = db('transactionlog')->where($condition)->field($field)->order($order)->paginate($pagesize,false,['query' => request()->param()]);
            $this->page_info = $result;
            return $result->items();
        } else {
            return db('transactionlog')->where($condition)->field($field)->order($order)->limit($limit)->select();
        }
    }
}