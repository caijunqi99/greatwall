<?php
namespace libr;


class AliMethod{
	private $AppSecret;
	private $AppCode;
	private $AppKey;
	private $NidCardhost   = "https://bankali.market.alicloudapi.com/";
	private $OcrCardhost   = "https://dm-51.data.aliyun.com/rest/160601/ocr/ocr_idcard.json";
	private $BankCheckhost = "https://bankali.market.alicloudapi.com/";

	function __construct($config=[]){
		$this->AppSecret = isset($config['AppSecret'])?$config['AppSecret']:'fhvbpmiqxhbcvkn4p1h67eeljxft3fmv';
		$this->AppCode   = isset($config['AppCode'])?$config['AppCode']:'2a829c2e12504bff846a7c92ca7460ab';
		$this->AppKey    = isset($config['AppKey'])?$config['AppKey']:'203766257';
	}



	/**
	 * 身份证识别
	 * @DateTime 2020-01-05
	 * @param    [type]     $cardInfo [身份证信息]
	 * @param    string     $side     [正反面  face/back]
	 */
	public function OcrIdcard($cardInfo,$side='face'){
		$url = $this->OcrCardhost;
		$o = 'member_'.$side;
	    $file = str_replace('/', '\\', $cardInfo[$o]); 
	    //如果输入带有 inputs, 设置为True，否则设为False
	    $is_old_format = false;
	    //如果没有configure字段，config设为空  side: face , side:back
	    $config = ["side" => $side];
	    if($fp = fopen($file, "rb", 0)) { 
	        $binary = fread($fp, filesize($file)); // 文件读取
	        fclose($fp); 
	        $base64 = base64_encode($binary); // 转码
	    }
	    $headers = array('Authorization:APPCODE '.$this->AppCode);
	    //根据API的要求，定义相对应的Content-Type
	    array_push($headers, "Content-Type".":"."application/json; charset=UTF-8");
	    $request = array(
            "image" => "$base64",
            'configure' =>json_encode($config),
        );
        $body = json_encode($request);
	    $method = "POST";
	    $res  = http_request($this->OcrCardhost, $method, $body, $headers);
	    $res  = json_decode($res , TRUE);
	    $return =['code'=>100];
	    if ( is_array( $res ) && isset( $res['success'] ) && $res['success'] ) {
	    	$return['code'] = 200;
	    	switch ($side) {
	    		case 'face':
	    			$return['info'] = [
						'name'        => $res['name'], //姓名
						'idcard'      => $res['num'], //身份证号码
						'address'     => $res['address'], //住址
						'birth'       => $res['birth'], // 生日
						'nationality' => $res['nationality'], //民族
						'sex'         => $res['sex'] //性别
			    	];
	    			break;
	    		case 'back':
	    			$return['info'] = [
						'issue'      => $res['issue'], //签发机关
						'start_date' => $res['start_date'], //有效期起始时间
						'end_date'   => $res['end_date'] //有效期结束时间
			    	];
	    			break;
	    	}
	    }else{
	    	switch ($side) {
	    		case 'face':
	    			$return['msg'] = '请上传正确的身份证图片 ：正面';
	    			break;
	    		case 'back':
	    			$return['msg'] = '请上传正确的身份证图片 ：背面';
	    			break;
	    	}
	    }
	    return $return;
	}

	/**
	 * 全国身份证实名认证
	 * @DateTime 2020-01-05
	 * @param    [type]     $member [个人信息]
	 */
	public function NidCard($member){
	    $this->NidCardhost .= "nidCard";
	    $method = "GET";
	    $headers = array('Authorization:APPCODE '.$this->AppCode);
	    $querys = "idCard=".$member['member_idcard']."&name=".$member['member_truename'];
	    $this->NidCardhost .="?" . $querys;
	    $res  = http_request($this->NidCardhost, $method, [], $headers);
	    $res  = json_decode($res , TRUE);
	    return $this->GetCodeCardcheck($res);
	}

	/**
	 * 银行卡三要素验证
	 * @DateTime 2020-01-05
	 * @param    [type]     $bank [银行卡信息]
	 * @return   [type]           [description]
	 */
	public function bankCheckNew($bank){
	    $this->BankCheckhost .= "bank3CheckNew";
	    $method = "GET";
	    $headers = array('Authorization:APPCODE '.$this->AppCode);
	    $querys = "accountNo=".$bank['memberbank_no']."&idCard=".$bank['member_idcard']."&name=".$bank['memberbank_truename'];
	    $this->BankCheckhost .="?" . $querys;
	    $res  = http_request($this->BankCheckhost, $method, [], $headers);
	    $res  = json_decode($res , TRUE);
	    return $this->GetCodeBankcheck($res);
	}

	/**
	 * 实名认证状态码
	 * @DateTime 2020-01-05
	 * @param    [type]     $res [description]
	 */
    private function GetCodeCardcheck($res){
    	$return = array(
    		'code' => 100
    	);
    	switch (strval($res['status'])) {
    		case '01':
	    		$return['msg'] = '实名认证通过！';
	    		$return['info'] = $res;
	    		$return['code'] = 200;
    			break;
    		case '02':
	    		$return['msg'] = '实名认证不通过！';
    			break;
    		case '202':
	    		$return['msg'] = '无法验证！【系统无此身份证记录，军人转业，户口迁移等】';
    			break;
    		case '203':
	    		$return['msg'] = '系统异常情况！';
    			break;
    		case '204':
	    		$return['msg'] = '姓名格式不正确！';
    			break;
    		case '205':
	    		$return['msg'] = '身份证格式不正确！';
    			break;
    		default:
	    		$return['msg'] = '实名认证不通过！';
    			break;
    	}
    	return $return;
    }

    /**
     * 银联三要素详情版本状态码
     * @DateTime 2020-01-05
     * @param    [type]     $re [description]
     */
    private function GetCodeBankcheck($res){
    	$return = array(
    		'code' => 100
    	);
    	switch (strval($res['status'])) {
    		case '01':
				$return['msg']  = '验证通过!';
				$return['info'] = $res;
				$return['code'] = 200;
				break;
			case '02':
				$return['msg']	= '身份信息或手机号输入不正确';
				break;
			case '03':
				$return['msg']	= '银行卡未开通认证支付';
				break;
			case '04':
				$return['msg']	= '此卡被没收';
				break;
			case '05':
				$return['msg']	= '银行卡无效';
				break;
			case '06':
				$return['msg']	= '此卡无对应发卡行';
				break;
			case '07':
				$return['msg']	= '该卡未初始化或睡眠卡';
				break;
			case '08':
				$return['msg']	= '此卡为作弊卡、吞卡';
				break;
			case '09':
				$return['msg']	= '此卡已挂失';
				break;
			case '10':
				$return['msg']	= '此卡已过期';
				break;
			case '11':
				$return['msg']	= '此卡为受限制的卡';
				break;
			case '12':
				$return['msg']	= '密码错误次数超限';
				break;
			case '13':
				$return['msg']	= '发卡行不支持此交易';
				break;
			case '14':
				$return['msg']	= '卡状态不正常';
				break;
			case '16':
				$return['msg']	= '输入的密码、有效期或 CVN2 有误';
				break;
			case '202':
				$return['msg']	= '无法验证 银行卡暂不支持该业务';
				break;
			case '203':
				$return['msg']	= '无法验证 此样本姓名身份证号银行卡号认证次数超限';
				break;
			case '204':
				$return['msg']	= '姓名输入错误 姓名不标准';
				break;
			case '205':
				$return['msg']	= '身份证号输入错误 身份证号不标准';
				break;
			case '206':
				$return['msg']	= '银行卡号输入错误 银行卡不标准';
				break;
			case '208':
				$return['msg']	= '无法验证 交易失败或银行拒绝交易，请联系发卡行';
				break;
			case '209':
				$return['msg']	= '无法验证 验证要素格式有误';
				break;
			case '211':
				$return['msg']	= '无法验证 输入参数不正确';
				break;
			case '3008':
				$return['msg']	= '无法验证 请重新签约或更换其它银行卡签约';
				break;
			case '3999':
				$return['msg']	= '无法验证 其他无法验证';
				break;
			case '9999':
				$return['msg']	= '服务异常';
				break;
			default:
				$return['msg']	= '服务异常';
				break;
    	}
    	$return['msg'] = '银行卡验证：'.$return['msg'];
    	return $return;
    }

}

?>