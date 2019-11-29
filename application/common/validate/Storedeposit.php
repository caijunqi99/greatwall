<?php

namespace app\common\validate;

use think\Validate;
/**
 * ============================================================================
 
 * ============================================================================
 * 验证器
 */
class  Storedeposit extends Validate
{
    protected $rule = [
        ['store_id', 'require|number', '请输入店主用户名|店主信息错误'],
        ['amount', 'require', '请添加金额'],
        ['operatetype', 'require', '请输入增减类型'],
    ];

    protected $scene = [
        'adjust' => ['store_id', 'amount','operatetype'],
    ];
}