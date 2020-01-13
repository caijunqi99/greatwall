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
        $data_log['tl_stage'] = isset($data['tl_stage'])?$data['tl_stage']:'system';
        $data_msg['time'] = date('Y-m-d H:i:s');
        switch ($change_type) {
            case 'sys_add_money':
                $data_log['tl_transaction'] = $data['amount'];
                $data_log['tl_desc'] = '管理员调节认筹股【增加】，充值单号: ' . $data['pdr_sn'].',备注：'.$data['tl_desc'];
                $data_log['tl_adminname'] = $data['admin_name'];
                $data_log['tl_adminid'] = $data['admin_id'];
                $data_pd['member_transaction'] = Db::raw('member_transaction+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['tl_desc'];
                break;
            case 'sys_del_money':
                $data_log['tl_transaction'] = -$data['amount'];
                $data_log['tl_desc'] = '管理员调节认筹股【减少】，充值单号: ' . $data['pdr_sn'].',备注：'.$data['tl_desc'];
                $data_log['tl_adminname'] = $data['admin_name'];
                $data_log['tl_adminid'] = $data['admin_id'];
                $data_pd['member_transaction'] = Db::raw('member_transaction-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['tl_desc'];
                break;
            case 'pointransform_add':
                $data_log['tl_transaction'] = $data['amount'];
                $data_log['tl_desc'] = $data['tl_desc'];
                $data_pd['member_transaction'] = Db::raw('member_transaction+'.$data['amount']);

                $data_msg['av_amount'] = $data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['tl_desc'];
                break;
            case 'pointransform_del':
                $data_log['tl_transaction'] = -$data['amount'];
                $data_log['tl_desc'] = $data['tl_desc'];
                $data_pd['member_transaction'] = Db::raw('member_transaction-'.$data['amount']);

                $data_msg['av_amount'] = -$data['amount'];
                $data_msg['freeze_amount'] = 0;
                $data_msg['desc'] = $data_log['tl_desc'];
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
        $message['code'] = 'transaction_change';
        $message['member_id'] = $data['member_id'];
        $data_msg['av_amount'] = ds_price_format($data_msg['av_amount']);
        $data_msg['freeze_amount'] = ds_price_format($data_msg['freeze_amount']);
        $message['param'] = $data_msg;
        \mall\queue\QueueClient::push('sendMemberMsg', $message);
        return $insert;
    }
    /**
     * 认筹股日志列表
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