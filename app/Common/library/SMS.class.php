<?php
class SMS{
	const VERIFYUSERID 	  = "cd22445f-7338-410b-a975-0f206307dfb6";
	const VERIFYPASSWD 	  = "fe5f47d026";
	const MARKETINGUSERID = "f36b54bc-d14e-4348-bcc5-fc3de58653e2";
	const MARKETINGPASSWD = "373895b7c9";
	const SENDURL	      = "http://api.goinsms.com/sms/http/Submit";
	const BALANCEURL	  = "http://api.goinsms.com/sms/http/balance";
	
	public $dataArray = null;
	
	public function __construct(){
		date_default_timezone_set("PRC");
	}
	
	//行业短信发送
	public function verifySMSSend($mobile,$content,$sign){
		return $this->SMSSend(SMS::VERIFYUSERID,SMS::VERIFYPASSWD,$mobile,$content,$sign);
	}
	
	//营销短信发送
	public function marketingSMSSend($mobile,$content,$sign){
		return $this->SMSSend(SMS::MARKETINGUSERID,SMS::MARKETINGPASSWD,$mobile,$content,$sign);
	}
	
	public function SMSSend($userId,$passwd,$mobile,$content,$sign){
		$parameters=array("userId"=>$userId,"passwd"=>$passwd,"mobiles"=>$mobile,"content"=>$content.$sign,'sign'=>'','messages'=>'');
		$parameters=json_encode($parameters);
		$post_string = 'message='.$parameters;
		return $this->request_by_other(SMS::SENDURL,$post_string);
	}
	
	//行业短信查询余额
	public function verifySMSBalance(){
		return $this->Balance(SMS::VERIFYUSERID,SMS::VERIFYPASSWD);
	}
	
	//营销短信查询余额
	public function marketingSMSBalance(){
		return $this->Balance(SMS::MARKETINGUSERID,SMS::MARKETINGPASSWD);
	}
	
	public function Balance($userId,$passwd){
		$parameters=array("userId"=>$userId,"passwd"=>$passwd);
		$parameters=json_encode($parameters);
		$post_string = 'message='.$parameters;
		return $this->request_by_other(SMS::BALANCEURL,$post_string);
	}
	
	public function request_by_other($remote_server,$post_string){
		$context = array(
				'http'=>array(
						'method'=>'POST',
						'header'=>'Content-type: application/x-www-form-urlencoded'."\r\n".
						'Content-length: '.strlen($post_string)+8,
						'content'=>$post_string)
		);
		$stream_context = stream_context_create($context);
		$data = file_get_contents($remote_server,FALSE,$stream_context);
	
		return json_decode($data,true) ;
	}
}