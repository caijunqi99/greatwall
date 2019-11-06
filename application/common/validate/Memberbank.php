<?php
namespace app\common\validate;
use think\Validate;
/**
 * ============================================================================
 
 * ============================================================================
 * 验证器
 */
class  Memberbank extends Validate
{
    protected $rule = [
        ['memberbank_type','require','账户类型不能为空'],
        ['memberbank_truename','require','开户名不能为空'],
        ['memberbank_no','require','账号不能为空'],

    ];
    protected $scene = [
        'add' => ['memberbank_type', 'memberbank_truename', 'memberbank_no'],
        'edit' => ['memberbank_type', 'memberbank_truename', 'memberbank_no'],
    ];


}