<?php

namespace app\common\validate;

use think\Validate;
/**
 * ============================================================================
 
 * ============================================================================
 * 验证器
 */
class  VerifyCode extends Validate
{
    protected $rule = [
        'verify_code'=>'require|length:6',
        'verify_code_type'=>'require|in:1,2,3',
    ];

    protected $message  =   [
        'verify_code.require' => '验证码必填',
        'verify_code.length' => '验证码长度为6位',
				'verify_code_type.require' => '验证码类型必填',
        'verify_code_type.in' => '验证码类型错误',
        
    ];
    protected $scene = [
        'verify_code_search' => ['verify_code'],
        'verify_code_send' => ['verify_code_type'],
    ];
}