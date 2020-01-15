<?php

namespace app\common\model;

use think\Db;
use think\Model;

/**
 * ============================================================================

 * ============================================================================
 * 数据层模型
 */
class Tranprice extends Model
{

    public $page_info;

    /**
     * 价格详细信息（查库）
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @param string $field 字段
     * @return array
     */
    public function getTranInfo($condition, $field = '*')
    {
        $res = db('tranprice')->field($field)->where($condition)->find();
        return $res;
    }
    /**
     * 查询最后一条id
    */
    public function getTranLast(){
        $res=db('tranprice')->order('t_id desc')->limit('0,1')->select();
        return $res;
    }
    /**
     * 添加价格
    */
    public function addTran($data){
        return db('tranprice')->insertGetId($data);
    }
    /**
     * 价格列表
     * @access public
     * @author bayi-shop
     * @param array $condition 条件
     * @param string $field    字段
     * @param number $pagesize     分页
     * @param string $order    排序
     * @return array
     */
    public function getTranList($condition = array(), $field = '*', $pagesize = 0, $order = 't_id desc')
    {
        if ($pagesize) {
            $member_list = db('tranprice')->where($condition)->order($order)->paginate($pagesize,false,['query' => request()->param()]
            );
            $this->page_info = $member_list;
            return $member_list->items();
        }
        else {
            return db('tranprice')->where($condition)->order($order)->select();
        }
    }

    /**
     * 会员数量
     * @access public
     * @author bayi-shop
     * @param array $condition 查询条件
     * @return int
     */
    public function getMemberCount($condition)
    {
        return db('company')->where($condition)->count();
    }

    /**
     * 编辑公司
     * @access public
     * @author bayi-shop
     * @param array $condition 检索条件
     * @param array $data 数据
     * @return bool
     */
    public function editCompany($condition, $data)
    {
        $update = db('company')->where($condition)->update($data);
        return $update;
    }


    /**
     * 登录时创建会话SESSION
     * @access public
     * @author bayi-shop
     * @param type $member_info 会员信息
     * @param type $reg 规则
     * @return type
     */
    public function createSession($member_info = array(), $reg = false)
    {
        if (empty($member_info) || !is_array($member_info)) {
            return;
        }
        $member_gradeinfo = model('member')->getOneMemberGrade(intval($member_info['member_exppoints']));
        $member_info = array_merge($member_info, $member_gradeinfo);
        session('is_login', '1');
        session('member_id', $member_info['member_id']);
        session('member_name', $member_info['member_name']);
        session('member_email', $member_info['member_email']);
        session('is_buy', isset($member_info['is_buylimit']) ? $member_info['is_buylimit'] : 1);
        session('avatar', $member_info['member_avatar']);
        session('level', isset($member_info['level']) ? $member_info['level'] : '');
        session('level_name', isset($member_info['level_name']) ? $member_info['level_name'] : '');
        session('member_exppoints', $member_info['member_exppoints']);  //经验值
        session('member_points', $member_info['member_points']);        //积分值
        // 头衔COOKIE
        $this->set_avatar_cookie();

        $seller_info = model('seller')->getSellerInfo(array('member_id' => session('member_id')));
        if ($seller_info) {
            session('store_id', $seller_info['store_id']);
        }
        else {
            session('store_id', NULL);
        }

        if (trim($member_info['member_qqopenid'])) {
            session('openid', $member_info['member_qqopenid']);
        }
        if (trim($member_info['member_sinaopenid'])) {
            session('slast_key.uid', $member_info['member_sinaopenid']);
        }
        if (trim($member_info['member_wxopenid'])) {
            session('wxopenid', $member_info['member_wxopenid']);
        }
        if (trim($member_info['member_wxunionid'])) {
            session('wxunionid', $member_info['member_wxunionid']);
        }

        if (!$reg) {
            //添加会员积分
            $this->addPoint($member_info);
            //添加会员经验值
            $this->addExppoint($member_info);
        }

        if (!empty($member_info['member_logintime'])) {
            $update_info = array(
                'member_loginnum' => ($member_info['member_loginnum'] + 1),
                'member_logintime' => TIMESTAMP,
                'member_old_logintime' => $member_info['member_logintime'],
                'member_login_ip' => request()->ip(),
                'member_old_login_ip' => $member_info['member_login_ip']
            );
            $this->editMember(array('member_id' => $member_info['member_id']), $update_info);
        }
        cookie('cart_goods_num', '', -3600);
        // cookie中的cart存入数据库
        model('cart')->mergeCart($member_info, session('store_id'));
        // cookie中的浏览记录存入数据库
        model('goodsbrowse')->mergeGoodsbrowse(session('member_id'), session('store_id'));

        if (isset($member_info['auto_login']) && ($member_info['auto_login'] == 1)) {
            $this->auto_login();
        }
    }


    /**
     * 7天内自动登录
     * @access public
     * @author bayi-shop
     */
    public function auto_login()
    {
        // 自动登录标记 保存7天
        cookie('auto_login', ds_encrypt(session('member_id'), MD5_KEY), 7 * 24 * 60 * 60);
    }

    /**
     * 设置cookie
     * @access public
     * @author bayi-shop
     */
    public function set_avatar_cookie()
    {
        cookie('member_avatar', session('avatar'), 365 * 24 * 60 * 60);
    }

    /**
     * 获取会员信息
     * @access public
     * @author bayi-shop
     * @param    array $condition 会员条件
     * @param    string $field 显示字段
     * @return    array 数组格式的返回结果
     */
    public function infoMember($condition, $field = '*')
    {
        if (empty($condition))
            return false;
        $member_info = db('member')->where($condition)->field($field)->find();
        return $member_info;
    }

    /**
     * 生成推荐码
     * @DateTime 2019-11-18
     * @return   [type]     [description]
     */
    public function _get_inviter_code(){
        $codeLen = config('inviter_code_len');
        $code = '';
        for ($i=0; $i < $codeLen; $i++) {
            $code .= chr(rand(65,90));
        }
        $member = $this->getMemberCount(['inviter_code'=>$code]);
        if ($member) {
            return $this->_get_inviter_code();
        }
        return $code;
    }

    /**
     * 注册
     * @access public
     * @author bayi-shop
     * @param type $register_info
     * @return type
     */
    public function register($register_info)
    {
        // 验证用户名是否重复
        $check_member_name = $this->getMemberInfo(array('member_name' => $register_info['member_name']));
        if (is_array($check_member_name) and count($check_member_name) > 0) {
            return array('error' => '用户名已存在');
        }
        $check_member_name = $this->getMemberInfo(array('member_mobile' => $register_info['member_mobile']));
        if (is_array($check_member_name) and count($check_member_name) > 0) {
            return array('error' => '用户名已存在');
        }
        $insert_id = $this->addMember($register_info);
        if ($insert_id) {
            $this->addMemberAfter($insert_id,$register_info);
            $member_info = db('member')->where('member_id', $insert_id)->find();
            //是否有注册红包
            $bonus_model = model('bonus');
            $bonus = $bonus_model->getOneBonus(array('bonus_type' => 2, 'bonus_state' => 1, 'bonus_begintime' => array('<', TIMESTAMP), 'bonus_endtime' => array('>', TIMESTAMP)));
            if ($bonus) {
                //获取未领取单个红包
                $condition = array();
                $condition['bonus_id'] = $bonus['bonus_id'];
                $condition['member_id'] = 0;
                $bonusreceive = $bonus_model->getOneBonusreceive($condition);
                if (!empty($bonusreceive)) {
                    $res = $bonus_model->receiveBonus($member_info, $bonus, $bonusreceive, '领取注册红包');
                    if (!$res['code']) {
                        return array('error' =>$res['msg']);
                    }
                }
            }
            return $member_info;
        }
        else {
            return array('error' => '注册失败');
        }
    }
    /**
     * 新增用户后,赠送积分,添加相册等其他操作,主要是针对于 新增用户注册获得积分，等奖励信息的处理
     * @access public
     * @author bayi-shop
     * @param type $member_id 会员ID
     * @param type $member_info 会员信息
     * @return type
     */
    public function addMemberAfter($member_id,$member_info){
        //添加会员积分
        if (config('points_isuse')) {
            model('points')->savePointslog('regist', array('pl_memberid' => $member_id, 'pl_membername' => $member_info['member_name']), false);
            if (isset($member_info['inviter_id'])) {
                //向上查询3级更新分销成员数
                db('inviter')->where('inviter_id='.$member_info['inviter_id'])->setInc('inviter_1_quantity');
                $inviter_2=db('member')->where('member_id='.$member_info['inviter_id'])->value('inviter_id');
                if($inviter_2){
                    db('inviter')->where('inviter_id='.$inviter_2)->setInc('inviter_2_quantity');
                    $inviter_3=db('member')->where('member_id='.$inviter_2)->value('inviter_id');
                    if($inviter_3){
                        db('inviter')->where('inviter_id='.$inviter_3)->setInc('inviter_3_quantity');
                    }
                }
                //添加邀请人(推荐人)会员积分
                $inviter_name = ds_getvalue_byname('member', 'member_id', $member_info['inviter_id'], 'member_name');
                model('points')->savePointslog('inviter', array(
                    'pl_memberid' => $member_info['inviter_id'], 'pl_membername' => $inviter_name,
                    'invited' => $member_info['member_name']
                ));
            }
        }
    }

    /**
     * 会员申请为分公司
     * @access public
     * @author bayi-shop
     * @param  array $data 会员信息
     * @return array 数组格式的返回结果
     */
    public function addCompany($data)
    {
        if (empty($data)) {
            return false;
        }
        try {
            $this->startTrans();
            $company_info = array();
            $company_info['company_addtime'] = TIMESTAMP;
            if (isset($data['member_areainfo'])) {
                $company_info['member_areainfo'] = $data['member_areainfo'];
            }
            if (isset($data['member_villageid'])) {
                $company_info['member_villageid'] = $data['member_villageid'];
            }
            if (isset($data['member_townid'])) {
                $company_info['member_townid'] = $data['member_townid'];
            }
            if (isset($data['member_areaid'])) {
                $company_info['member_areaid'] = $data['member_areaid'];
            }
            if (isset($data['member_cityid'])) {
                $company_info['member_cityid'] = $data['member_cityid'];
            }
            if (isset($data['member_provinceid'])) {
                $company_info['member_provinceid'] = $data['member_provinceid'];
            }
            if (isset($data['company_level'])) {
                $company_info['company_level'] = $data['company_level'];
            }
            if (isset($data['member_mobile'])) {
                $company_info['member_mobile'] = $data['member_mobile'];
            }
            if (isset($data['member_id'])) {
                $company_info['member_id'] = $data['member_id'];
            }
            $insert_id = db('company')->insertGetId($company_info);
            $this->commit();
            return $insert_id;
        } catch (Exception $e) {
            $this->rollback();
            return false;
        }
    }

    /**
     * 会员登录检查
     * @access public
     * @author bayi-shop
     * @return bool
     */
    public function checkloginMember()
    {
        if (session('is_login') == '1') {
            @header("Location: " . url('Home/Member/index'));
            exit();
        }
    }

    /**
     * 检查会员是否允许举报商品
     * @access public
     * @author bayi-shop
     * @param type $member_id 会员id
     * @return boolean
     */
    public function isMemberAllowInform($member_id)
    {
        $condition = array();
        $condition['member_id'] = $member_id;
        $member_info = $this->getMemberInfo($condition, 'inform_allow');
        if (intval($member_info['inform_allow']) === 1) {
            return true;
        }
        else {
            return false;
        }
    }

    /**
     * 添加会员积分
     * @access public
     * @author bayi-shop
     * @param type $member_info 会员信息
     * @return type
     */
    public function addPoint($member_info)
    {
        if (!config('points_isuse') || empty($member_info))
            return;

        //一天内只有第一次登录赠送积分
        if (trim(@date('Y-m-d', $member_info['member_logintime'])) == trim(date('Y-m-d')))
            return;

        //加入队列
        $queue_content = array();
        $queue_content['member_id'] = $member_info['member_id'];
        $queue_content['member_name'] = $member_info['member_name'];
        \mall\queue\QueueClient::push('addPoint', $queue_content);
    }

    /**
     * 添加会员经验值
     * @access public
     * @author bayi-shop
     * @param unknown $member_info 会员信息
     */
    public function addExppoint($member_info)
    {
        if (empty($member_info))
            return;

        //一天内只有第一次登录赠送经验值
        if (trim(@date('Y-m-d', $member_info['member_logintime'])) == trim(date('Y-m-d')))
            return;

        //加入队列
        $queue_content = array();
        $queue_content['member_id'] = $member_info['member_id'];
        $queue_content['member_name'] = $member_info['member_name'];
        \mall\queue\QueueClient::push('addExppoint', $queue_content);
    }

    /**
     * 取得会员安全级别
     * @access public
     * @author bayi-shop
     * @param array $member_info 会员信息
     */
    public function getMemberSecurityLevel($member_info = array())
    {
        $tmp_level = 0;
        if ($member_info['member_emailbind'] == '1') {
            $tmp_level += 1;
        }
        if ($member_info['member_mobilebind'] == '1') {
            $tmp_level += 1;
        }
        if ($member_info['member_paypwd'] != '') {
            $tmp_level += 1;
        }
        return $tmp_level;
    }

    /**
     * 获得会员等级
     * @access public
     * @author bayi-shop
     * @param bool $show_progress 是否计算其当前等级进度
     * @param int $exppoints 会员经验值
     * @param array $cur_level 会员当前等级
     * @return type
     */
    public function getMemberGradeArr($show_progress = false, $exppoints = 0, $cur_level = '')
    {
        $member_grade = config('member_grade') ? unserialize(config('member_grade')) : array();
        //处理会员等级进度
        if ($member_grade && $show_progress) {
            $is_max = false;
            if ($cur_level === '') {
                $cur_gradearr = $this->getOneMemberGrade($exppoints, false, $member_grade);
                $cur_level = $cur_gradearr['level'];
            }
            foreach ($member_grade as $k => $v) {
                if ($cur_level == $v['level']) {
                    $v['is_cur'] = true;
                }
                $member_grade[$k] = $v;
            }
        }
        return $member_grade;
    }



    /**
     * 获得某一会员等级
     * @access public
     * @author bayi-shop
     * @param int $exppoints 会员经验值
     * @param bool $show_progress 是否计算其当前等级进度
     * @param array $member_grade 会员等级
     * @return type
     */
    public function getOneMemberGrade($exppoints, $show_progress = false, $member_grade = array())
    {
        if (!$member_grade) {
            $member_grade = config('member_grade') ? unserialize(config('member_grade')) : array();
        }
        if (empty($member_grade)) {//如果会员等级设置为空
            $grade_arr['level'] = -1;
            $grade_arr['level_name'] = '暂无等级';
            return $grade_arr;
        }

        $exppoints = intval($exppoints);

        $grade_arr = array();
        if ($member_grade) {
            foreach ($member_grade as $k => $v) {
                if ($exppoints >= $v['exppoints']) {
                    $grade_arr = $v;
                }
            }
        }
        //计算提升进度
        if ($show_progress == true) {
            if (intval($grade_arr['level']) >= (count($member_grade) - 1)) {//如果已达到顶级会员
                $grade_arr['downgrade'] = $grade_arr['level'] - 1; //下一级会员等级
                $grade_arr['downgrade_name'] = $member_grade[$grade_arr['downgrade']]['level_name'];
                $grade_arr['downgrade_exppoints'] = $member_grade[$grade_arr['downgrade']]['exppoints'];
                $grade_arr['upgrade'] = $grade_arr['level']; //上一级会员等级
                $grade_arr['upgrade_name'] = $member_grade[$grade_arr['upgrade']]['level_name'];
                $grade_arr['upgrade_exppoints'] = $member_grade[$grade_arr['upgrade']]['exppoints'];
                $grade_arr['less_exppoints'] = 0;
                $grade_arr['exppoints_rate'] = 100;
            }
            else {
                $grade_arr['downgrade'] = $grade_arr['level']; //下一级会员等级
                $grade_arr['downgrade_name'] = $member_grade[$grade_arr['downgrade']]['level_name'];
                $grade_arr['downgrade_exppoints'] = $member_grade[$grade_arr['downgrade']]['exppoints'];
                $grade_arr['upgrade'] = $member_grade[$grade_arr['level'] + 1]['level']; //上一级会员等级
                $grade_arr['upgrade_name'] = $member_grade[$grade_arr['upgrade']]['level_name'];
                $grade_arr['upgrade_exppoints'] = $member_grade[$grade_arr['upgrade']]['exppoints'];
                $grade_arr['less_exppoints'] = $grade_arr['upgrade_exppoints'] - $exppoints;
                $grade_arr['exppoints_rate'] = round(($exppoints - $member_grade[$grade_arr['level']]['exppoints']) / ($grade_arr['upgrade_exppoints'] - $member_grade[$grade_arr['level']]['exppoints']) * 100, 2);
            }
        }
        return $grade_arr;
    }


    /**
     * 登录生成token
     * @access public
     * @author bayi-shop
     * @param type $member_id 会员id
     * @param type $member_name 会员名字
     * @param type $client 客户端
     * @return type
     */
    public function getBuyerToken($member_id, $member_name, $client,$openid='') {
        $mbusertoken_model = model('mbusertoken');
        //重新登录后以前的令牌失效
        //暂时停用
        //$condition = array();
        //$condition['member_id'] = $member_id;
        //$condition['member_clienttype'] = $client;
        //$mbusertoken_model->delMbusertoken($condition);
        //生成新的token
        $mb_user_token_info = array();
        $token = md5($member_name . strval(TIMESTAMP) . strval(rand(0, 999999)));
        $mb_user_token_info['member_id'] = $member_id;
        $mb_user_token_info['member_name'] = $member_name;
        $mb_user_token_info['member_token'] = $token;
        $mb_user_token_info['member_logintime'] = TIMESTAMP;
        $mb_user_token_info['member_clienttype'] = $client;
        if(!empty($openid)){
            $mb_user_token_info['member_openid'] = $openid;
        }

        $result = $mbusertoken_model->addMbusertoken($mb_user_token_info);
        if ($result) {
            return $token;
        } else {
            return null;
        }
    }

}