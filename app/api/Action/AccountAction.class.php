<?php
/**
 * 账号设置控制器
 * @author jliu <jliu@cophea.com>
 * @version cophea 2.0
 */
namespace api\Action;
use Think\Action;
class AccountAction extends Action 
{

	private $_user_model;

	/**
	 * 控制器初始化，实例化用户档案模型对象
	 * @return void
	 */
	protected function _initialize() {
		$this->_user_model = D('User');
	}

	public function getmid(){
		$data['data'] = intval($_SESSION['mid']);
		$data['status'] = empty($_SESSION['mid'])?0:1;
		$this->ajaxReturn($data,'JSONP');
	}

	public function getsite(){
		$res = D('Xdata')->get('admin_Config:site');
		$this->ajaxReturn($res,'JSONP');
	}

	public function getuser(){
		$res = $this->_user_model->getUserInfo(intval($_SESSION['mid']));
		$this->ajaxReturn($res,'JSONP');
	}

	/**
	 * 获取登录用户的档案信息
	 * @return 登录用户的档案信息
	 */
	private function _getUserProfile() {
		$data['user_profile'] = $this->_profile_model->getUserProfile($this->mid);
		$data['user_profile_setting'] = $this->_profile_model->getUserProfileSettingTree();

		return $data;
	}

	/**
	 * 保存基本信息操作
	 * @return json 返回操作后的JSON信息数据
	 */
	public function doSaveProfile() {
		$_POST = getRequest();
	
 		// 修改用户昵称
		$uname = I('post.uname');
		$save['uname'] = filter_keyword($uname);
		$save['phone'] = I('post.phone');
		
		//如果包含中文将中文翻译成拼音
		if ( preg_match('/[\x7f-\xff]+/', $save['uname'] ) ){
			//昵称和呢称拼音保存到搜索字段
			$save['search_key'] = $save['uname'].' '.D('PinYin')->Pinyin( $save['uname'] );
		} else {
			$save['search_key'] = $save['uname'];
		}
		
		try {
			$map['uid'] = intval($_SESSION['mid']);
			$res = $this->_user_model->where($map)->save($save);
			if(! $res){
				$this->ajaxReturn(array('data'=>null,'info'=>'修改错误','status'=>0),'JSONP');
			}
			//$res && $this->_user_model->cleanCache($this->mid);
			$this->ajaxReturn(array('data'=>$res,'info'=>'','status'=>1),'JSONP');
		}
		catch (Exception $e){
			//$this->error($e->getMessage());	
			$this->ajaxReturn(array('data'=>null,'info'=>'修改错误','status'=>0),'JSONP');
		}
	}

	/**
	 * 修改登录用户账号密码操作
	 * @return json 返回操作后的JSON信息数据
	 */
    public function doModifyPassword() {
    	/*
    	$_POST['oldpassword'] = t($_POST['oldpassword']);
    	$_POST['password'] = t($_POST['password']);
    	$_POST['repassword'] = t($_POST['repassword']);
    	// 验证信息
    	if ($_POST['oldpassword'] === '') {
    		$this->ajaxReturn(array('data'=>null,'info'=>'请填写原始密码','status'=>0),'JSONP');
    	}
    	if ($_POST['password'] === '') {
    		$this->ajaxReturn(array('data'=>null,'info'=>'请填写新密码','status'=>0),'JSONP');
    	}
    	if ($_POST['repassword'] === '') {
    		$this->ajaxReturn(array('data'=>null,'info'=>'请填写确认密码','status'=>0),'JSONP');
    	}
    	if($_POST['password'] != $_POST['repassword']) {
    		$this->ajaxReturn(array('data'=>null,'info'=>'新密码与确认密码不一致','status'=>0),'JSONP');
    	}
    	if(strlen($_POST['password']) < 6) {		
			$this->ajaxReturn(array('data'=>null,'info'=>'密码太短了，最少6位','status'=>0),'JSONP');
		}
		if(strlen($_POST['password']) > 15) {
			$this->ajaxReturn(array('data'=>null,'info'=>'密码太长了，最多15位','status'=>0),'JSONP');		
		}
		if($_POST['password'] == $_POST['oldpassword']) {
			$this->ajaxReturn(array('data'=>null,'info'=>'新密码与旧密码相同','status'=>0),'JSONP');
		}

    	$user_model = D('User');
    	$map['uid'] = $this->mid;
    	$user_info = $user_model->where($map)->find();

    	if($user_info['password'] == $user_model->encryptPassword($_POST['oldpassword'], $user_info['login_salt'])) {
			$data['login_salt'] = rand(11111, 99999);
			$data['password'] = $user_model->encryptPassword($_POST['password'], $data['login_salt']);
			$res = $user_model->where("`uid`={$this->mid}")->save($data);
    		$info = $res ? L('PUBLIC_PASSWORD_MODIFY_SUCCESS') : L('PUBLIC_PASSWORD_MODIFY_FAIL');			// 密码修改成功，密码修改失败
    	} else {
    		$info = L('PUBLIC_ORIGINAL_PASSWORD_ERROR');			// 原始密码错误
    	}
    	return $this->ajaxReturn(null, $info, $res);
    	*/
    	$this->ajaxReturn(array('data'=>null,'info'=>'','status'=>1));
    }

    /**
	 * 开通子账号
	 * @return json 返回操作后的JSON信息数据
	 */
    public function enableSubAccount(){
    	
    }

    /**
	 * 关闭子账号
	 * @return json 返回操作后的JSON信息数据
	 */
    public function disableSubAccont(){
    	
    }

}