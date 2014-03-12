<?php
/**
 * RegisterAction 注册模块
 * @author  jliu <jliu@cophea.com>
 * @version Cophea 2.0
 */
namespace api\Action;
use Think\Action;
class RegisterAction extends Action
{
	private $_config;					// 注册配置信息字段
	private $_register_model;			// 注册模型字段
	private $_user_model;				// 用户模型字段
	private $_invite;					// 是否是邀请注册
	private $_invite_code;				// 邀请码

	/**
	 * 模块初始化，获取注册配置信息、用户模型对象、注册模型对象、邀请注册与站点头部信息设置
	 * @return void
	 */
	protected function _initialize()
	{
		$this->_config = D('Xdata')->get('admin_Config:register');
		$this->_register_model = D('Register');
		$this->_user_model = D('User');
	}

	/**
	 * 注册流程 - 执行第一步骤
	 * @return void
	 */
	public function doStep1(){
		/*
		$_POST['corp_name'] ='联想';
		$_POST['email'] ='lenovo3@cophea.com';
		$_POST['uname'] ='haha3';
		$_POST['password'] ='lj6912010';
		*/
		$corp_name	= I('post.corp_name');
		$email = I('post.email');
		$uname = I('post.uname');
		$password = I('post.password');

		
		if(!$this->_register_model->isValidName($uname)) {
			$this->ajaxReturn(array('status'=>0,'info'=>$this->_register_model->getLastError()),'JSONP');
		}

		if(!$this->_register_model->isValidEmail($email)) {
			$this->ajaxReturn(array('status'=>0,'info'=>$this->_register_model->getLastError()),'JSONP');
		}

		if(!$this->_register_model->isValidPassword($password, $password)){
			$this->ajaxReturn(array('status'=>0,'info'=>$this->_register_model->getLastError()),'JSONP');
		}
		
		$login_salt = rand(11111, 99999);
		$map['uname'] = $uname;
		$map['login_salt'] = $login_salt;
		$map['password'] = md5(md5($password).$login_salt);
		$map['login'] = $map['email'] = $email;
		$map['reg_ip'] = get_client_ip();
		$map['ctime'] = time();

		// 添加地区信息
		//$map['location'] = t($_POST['city_names']);
		//$cityIds = t($_POST['city_ids']);
		//$cityIds = explode(',', $cityIds);
		//isset($cityIds[0]) && $map['province'] = intval($cityIds[0]);
		//isset($cityIds[1]) && $map['city'] = intval($cityIds[1]);
		//isset($cityIds[2]) && $map['area'] = intval($cityIds[2]);
		// 审核状态： 0-需要审核；1-通过审核
		$map['is_audit'] = $this->_config['register_audit'] ? 0 : 1;
		// 需求添加 - 若后台没有填写邮件配置，将直接过滤掉激活操作
		$isActive = $this->_config['need_active'] ? 0 : 1;
		if ($isActive == 0) {
			$emailConf = D('Xdata')->get('admin_Config:email');
			if (empty($emailConf['email_host']) || empty($emailConf['email_account']) || empty($emailConf['email_password'])) {
				$isActive = 1;
			}
		}
		$map['is_active'] = $isActive;
		$map['first_letter'] = getFirstLetter($uname);
		//如果包含中文将中文翻译成拼音
		if ( preg_match('/[\x7f-\xff]+/', $map['uname'] ) ){
			//昵称和呢称拼音保存到搜索字段
			$map['search_key'] = $map['uname'].' '.D('PinYin')->Pinyin( $map['uname'] );
		} else {
			$map['search_key'] = $map['uname'];
		}
		$uid = $this->_user_model->add($map);

		if($uid) {
			// 如果是邀请注册，则邀请码失效
			if($invite) {
				$receiverInfo = D('User')->getUserInfo($uid);
				// 验证码使用
				D('Invite')->setInviteCodeUsed($inviteCode, $receiverInfo);
				// 添加用户邀请码字段
				D('User')->where('uid='.$uid)->setField('invite_code', $inviteCode);
				//给邀请人奖励
			}

			// 添加至默认的用户组
			$userGroup = D('Xdata')->get('admin_Config:register');
			$userGroup = empty($userGroup['default_user_group']) ? C('DEFAULT_GROUP_ID') : $userGroup['default_user_group'];
			D('UserGroupLink')->domoveUsergroup($uid, implode(',', $userGroup));

			// 添加机构信息
			$corp['uid'] = $uid;
			$corp['name'] = $corp_name;
			D('user_corp')->add($corp);

			//判断是否需要审核
			if($this->_config['register_audit']) {
				//$this->redirect('public/Register/waitForAudit', array('uid' => $uid));
			} else {
				if(!$isActive){
					//$this->_register_model->sendActivationEmail($uid);
					//$this->redirect('public/Register/waitForActivation', array('uid' => $uid));
				}else{
					D('Passport')->loginLocal($email,$password);
					$this->ajaxReturn(array('status'=>1),'JSONP');
				}
			}
			
		} else {
			$this->ajaxReturn(array('status'=>0,'info'=>'注册失败'),'JSONP');
		}

	}
}