<?php

/**
 * 积分功能公用
 */
$lang['admin_points_userrecord_error'] = '会员信息错误';
$lang['admin_points_membername'] = '会员名称';
$lang['admin_points_operatetype'] = '增减类型';
$lang['admin_points_operatetype_add'] = '增加';
$lang['admin_points_operatetype_reduce'] = '减少';
$lang['admin_points_pointsnum'] = '冻结积分';
$lang['admin_points_pointsnum_av'] = '可用积分';
$lang['admin_points_pointsdesc'] = '描述';
$lang['admin_points_pointsdesc_notice'] = '描述信息将显示在积分明细相关页，会员和管理员都可见';

/**
 * 积分添加
 */
$lang['admin_points_member_error_again'] = '会员信息错误，请重新填写会员名';
$lang['admin_points_points_null_error'] = '请添加积分值';
$lang['admin_points_points_min_error'] = '积分值必须大于等于0';
$lang['admin_points_points_short_error'] = '积分不足，会员当前积分数为';
$lang['admin_points_addmembername_error'] = '请输入会员名';
$lang['admin_points_member_tip'] = '会员';
$lang['admin_points_member_tip_2'] = ', 冻结积分数为';
$lang['admin_points_member_tip_2c'] = ', 当前可用积分数为';

/**
 * 积分日志
 */
$lang['admin_points_log_title'] = '积分明细';
$lang['admin_points_adminname'] = '管理员名称';
$lang['admin_points_stage'] = '操作阶段';
$lang['admin_points_stage_regist'] = '注册';
$lang['admin_points_stage_login'] = '登录';
$lang['admin_points_stage_comments'] = '商品评论';
$lang['admin_points_stage_order'] = '订单消费';
$lang['admin_points_stage_system'] = '系统调整';
$lang['admin_points_stage_rebate'] = '推荐返利';
$lang['admin_points_stage_marketmanage'] = '购物抽奖';
$lang['admin_points_stage_pointransform'] = '积分互转';
$lang['admin_points_stage_withdraw'] = '用户提现';

$lang['admin_points_stage_pointorder'] = '礼品兑换';
$lang['admin_points_stage_app'] = '积分兑换';
$lang['admin_points_stage_signin'] = '签到';
$lang['admin_points_stage_inviter'] = '推荐注册';
$lang['admin_points_addtime'] = '添加时间';
$lang['admin_points_log_help1'] = '积分管理，展示了会员、管理员、操作积分数（积分值，无符号表示增加，“-”表示减少，）、添加时间等信息';
$lang['admin_points_stage_release']='系统释放';




$lang['points_ruletip'] = '积分规则如下';
$lang['points_item'] = '项目';
$lang['points_number'] = '赠送积分';
$lang['points_number_reg'] = '会员注册';
$lang['points_number_login'] = '会员每天登录';
$lang['points_number_comments'] = '订单商品评论';
$lang['points_invite'] = '邀请注册';
$lang['points_invite_tips'] = '邀请非会员注册时给邀请人的积分数';
$lang['points_rebate'] = '返利比例';
$lang['points_rebate_tips'] = '被邀请会员购买商品时给邀请人返的积分数(例如设为5%，被邀请人购买100元商品，返给邀请人5积分)';
$lang['points_number_order'] = '购物并付款';
$lang['points_number_orderrate'] = '消费额与赠送积分比例';
$lang['points_number_orderrate_tip'] = '例:设置为10，表明消费10单位货币赠送1积分';
$lang['points_number_ordermax'] = '每订单最多赠送积分';
$lang['points_number_ordermax_tip'] = '例:设置为100，表明每订单赠送积分最多为100积分';
$lang['pointslog']='积分调整';
$lang['points_setting']='积分规则';
$lang['points_draw']='积分提现';

/**
 * 提现功能公用
 */
$lang['admin_predeposit_cashmanage'] = '提现管理';
$lang['admin_predeposit_cash_price'] = '提现金额';
$lang['admin_predeposit_cash_shoukuanname'] = '收款人姓名';
$lang['admin_predeposit_cash_shoukuanbank'] = '收款银行';
$lang['admin_predeposit_cash_shoukuanaccount'] = '收款账号';
$lang['admin_predeposit_cash_help1'] = '未支付的提现单可以更改提现单的支付状态';
$lang['admin_predeposit_cash_help2'] = '删除按钮可以删除未支付的提现单';
$lang['admin_predeposit_cash_confirm'] = '您确认已将提现金额支付到买家提现账户吗？';
/**
 * 提现信息删除
 */
$lang['admin_predeposit_cash_del_success'] = '提现信息删除成功';
$lang['admin_predeposit_cash_del_fail'] = '提现信息删除失败';
/**
 * 提现信息编辑
 */
$lang['admin_predeposit_cash_edit_success'] = '提现信息修改成功';
$lang['admin_predeposit_cash_edit_fail'] = '提现信息修改失败';
$lang['admin_predeposit_cash_edit_state'] = '修改提现单状态';