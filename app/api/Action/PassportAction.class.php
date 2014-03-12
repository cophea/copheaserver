<?php
/**
 * PassportAction 通行证模块
 * @author  jliu <jliu@cophea.com>
 * @version Cophea 2.0
 */
namespace api\Action;
use Think\Action;
class PassportAction extends Action{
	
	var $passport;
	
	/**
	 * 模块初始化
	 * @return void
	 */
	protected function _initialize() {
		$this->passport = D('Passport');
	}
	
	/**
	 * 用户登录
	 * @return void
	 */
	public function doLogin() {
		$login 		= I('post.login_email');
		$password 	= I('post.login_password');
		$remember	= intval(I('post.login_remember'));

		$result 	= $this->passport->loginLocal($login,$password,$remember);

		if(!$result){
			$data['status'] = 0;
			$data['info']=$this->passport->getError();
			$data['data']=null;
			//记录
			LogRecord('public','login',array( 'uname'=>$GLOBALS['ts']['user']['uname']));
		}else{
			$data['status'] = 1;
			$data['info']='';
			$data['data']=null;
		}
		$this->ajaxReturn($data,'JSONP');
	}
	
	/**
	 * 注销登录
	 * @return void
	 */
	public function logout() {
		$this->passport->logoutLocal();
		//记录
		LogRecord('public','logout',array( 'uname'=>$GLOBALS['ts']['user']['uname']));
		$this->ajaxReturn('1','JSONP');
	}
	
}