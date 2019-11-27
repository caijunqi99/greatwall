<?php

namespace app\common\validate;


use think\Validate;
/**
 * ============================================================================
 
 * ============================================================================
 * 验证器
 */
class  Promotionbooth extends Validate
{
    protected $rule = [
        ['promotion_booth_price', 'require|number|egt:0', '不能为空，且不小于0的整数'],
        ['promotion_booth_goods_sum', 'require|number', '不能为空，且不小于1的整数']
    ];

    protected $scene = [
        'booth_setting' => ['promotion_booth_price', 'promotion_booth_goods_sum'],
    ];
}