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
	private $_cardcategory_model;	//卡类型模型

	/**
	 * 模块初始化，获取注册配置信息、用户模型对象、注册模型对象、邀请注册与站点头部信息设置
	 * @return void
	 */
	protected function _initialize()
	{
		$this->_config = D('Xdata')->get('admin_Config:register');
		$this->_register_model = D('Register');
		$this->_user_model = D('User');
		//$this->_cardcategory_model = model('CardCategory');
	}

	public function code(){
		if (md5(strtoupper($_POST['verify'])) == $_SESSION['verify']) {
			echo 1;
		}else{
			echo 0;
		}
	}

	/**
	 * 注册流程 - 执行第一步骤
	 * @return void
	 */
	public function doStep1(){

		$_POST['corp_name'] ='联想';
		$_POST['email'] ='lenovo3@cophea.com';
		$_POST['uname'] ='haha3';
		$_POST['password'] ='lj6912010';

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

			/*
			//注册来源-第三方帐号绑定
			if(isset($_POST['other_type'])){
				$other['type'] = t($_POST['other_type']);
				$other['type_uid'] = t($_POST['other_uid']);	
				$other['oauth_token'] = t($_POST['oauth_token']);
				$other['oauth_token_secret'] = t($_POST['oauth_token_secret']);
				$other['uid'] = $uid;
				D('login')->add($other);
			}
			*/

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

	/**
	 * 发送激活邮件
	 * @return void
	 */
	public function resendActivationEmail() {
		$res = $this->_register_model->sendActivationEmail($this->uid);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $res);
	}

	/**
	 * 修改激活邮箱
	 */
	public function changeActivationEmail() {
		$email = t($_POST['email']);
		// 验证邮箱是否为空
		if (!$email) {
			$this->ajaxReturn(null, '邮箱不能为空！', 0);
		}
		// 验证邮箱格式
		$checkEmail = $this->_register_model->isValidEmail($email);
		if (!$checkEmail) {
			$this->ajaxReturn(null, $this->_register_model->getLastError(), 0);
		}
		$res = $this->_register_model->changeRegisterEmail($this->uid, $email);
		$res && $this->_register_model->sendActivationEmail($this->uid);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $res);
	}

	/**
	 * 通过链接激活帐号
	 * @return void
	 */
	public function activate() {
		$user_info = $this->_user_model->getUserInfo($this->uid);

		$this->assign('user',$user_info);
		
		if (!$user_info || $user_info['is_active']) {
			$this->redirect('public/Passport/login');
		}

		$active = $this->_register_model->activate($this->uid, t($_GET['code']));

		if ($active) {
			// 登陆
			model('Passport')->loginLocalWithoutPassword($user_info['email']);
			$this->setTitle('成功激活帐号');
			$this->setKeywords('成功激活帐号');
			// 跳转下一步
			$this->assign('jumpUrl', U('public/Register/step2'));
			$this->success($this->_register_model->getLastError());
		} else {
			$this->redirect('public/Passport/login');
			$this->error($this->_register_model->getLastError());
		}
	}

	public function doStep2(){
		//基本信息
		$create['name'] = t($_POST['name']);
		$create['description'] = $_POST['description'];
		$create['image']=APP_URL.'/_static/image/'.$_POST['image'];
		//功能设置-安全码
		$rule['isExistSecurityCode'] = intval($_POST['isExistSecurityCode']);
		//功能设置-开启的功能
		$rule['isRechargeFeature'] = $_POST['isRechargeFeature']=='on'?1:0;
		$rule['isCreditFeature'] = $_POST['isCreditFeature']=='on'?1:0;
		$rule['isDiscountFeature'] = $_POST['isDiscountFeature']=='on'?1:0;
		$rule['isTimes'] = $_POST['isTimes']=='on'?1:0;
		//功能设置-充值规则
		$rule['rechargerule'] = $this->_getRechargeRule();
		//功能设置-积分规则
		$rule['active_award_credits'] = $_POST['active_award_credits'];
		$rule['consume_award_credits'] = $_POST['consume_award_credits'];
		//功能设置-折扣规则
		$rule['consume_discount'] = $this->_filterDiscount($_POST['consume_discount']);
		$create['rule']=$rule;
		if($this->_cardcategory_model->addCardCategory($create)){
			//开启积分应用
			if($rule['isCreditFeature']){
				if(!isset($GLOBALS['ts']['site_nav_apps']['credit'])){
					$this->setCorpApp($this->cid,35);
				}
			}
			$this->doAuditUser();
			//$this->assign('jumpUrl',U('public/Register/step3'));
			//$this->success();
			echo 1;
		}else{
			$this->error($this->_cardcategory_model->getError());
		}
	}

	/**
	 * 注册流程 - 第三步骤
	 * 设置个人标签
	 */
	public function step3() {
		/*
		// 未登录
		empty($_SESSION['mid']) && $this->redirect('public/Passport/login');
		$this->appCssList[] = 'login.css';
		//$this->_config['tag_num'] = $this->_config['tag_num']?$this->_config['tag_num']:10;
		$this->assign('tag_num',$this->_config['tag_num']);
		$this->assign('interester_open',$this->_config['interester_open']);
		$this->setTitle('设置个人标签');
		$this->setKeywords('设置个人标签');
		*/
		$this->display();
	}

	/**
	 * 注册流程 - 执行第三步骤
	 * 添加标签
	 */
	public function doStep3() {
		$tagIds = t($_REQUEST['user_tags']);
		!empty($tagIds) && $tagIds = explode(',', $tagIds);
		$rowId = intval($this->mid);
		if(!empty($rowId)) {
			if(count($tagIds) > 10) {
				return $this->ajaxReturn(null, '最多只能设置10个标签', false);
			}
		    model('Tag')->setAppName('public')->setAppTable('user')->updateTagData($rowId, $tagIds);
		}
		echo 1;
	}

	/**
	 * 注册流程 - 第四步骤
	 */
	public function step4() {
		/*
		// 未登录
		empty($_SESSION['mid']) && $this->redirect('public/Passport/login');
		$this->appCssList[] = 'login.css';

		//dump($this->_config);exit;
		//按推荐用户
		$related_recommend_user = model('RelatedUser')->getRelatedUserByType(5,8);
		$this->assign('related_recommend_user',$related_recommend_user);
		//按标签
		if(in_array('tag', $this->_config['interester_rule'])){
			$related_tag_user = model('RelatedUser')->getRelatedUserByType(4,8);
			$this->assign('related_tag_user',$related_tag_user);
		}
		//按地区
		if(in_array('area', $this->_config['interester_rule'])){
			$related_city_user = model('RelatedUser')->getRelatedUserByType(3,8);
			$this->assign('related_city_user',$related_city_user);
		}
		$userInfo = model('User')->getUserInfo($this->mid);
		$location = explode(' ', $userInfo['location']);
		$this->assign('location',$location[0]);
		$this->setTitle('关注感兴趣的人');
		$this->setKeywords('关注感兴趣的人');
		*/
		$this->display();
	}

	/**
	 * 获取推荐用户
	 * @return void
	 */
	public function getRelatedUser() {
		$type = intval($_POST['type']);
		$related_user = model('RelatedUser')->getRelatedUserByType($type,8);
		$html = '';
		foreach($related_user as $k=>$v){
			$html .= '<li><div style="position:relative;width:80px;height:80px"><div class="selected"><i class="ico-ok-mark"></i></div>
					  <a event-node="bulkDoFollowData" value="'.$v['userInfo']['uid'].'" class="face_part" href="javascript:void(0);">
					  <img src="'.$v['userInfo']['avatar_big'].'" /></a></div><span class="name">'.$v['userInfo']['uname'].'</span></li>';
		}
		echo $html;
	}

	/**
	 * 注册流程 - 执行第四步骤
	 */
	public function doStep4() {
		set_time_limit(0);
		// 初始化完成
		$this->_register_model->overUserInit($this->mid);
		// 添加双向关注用户
		$eachFollow = $this->_config['each_follow'];
		if(!empty($eachFollow)) {
			model('Follow')->eachDoFollow($this->mid, $eachFollow);
		}
		// 添加默认关注用户
		$defaultFollow = $this->_config['default_follow'];
		$defaultFollow = array_diff(explode(',', $defaultFollow), explode(',', $eachFollow));
		if(!empty($defaultFollow)) {
			model('Follow')->bulkDoFollow($this->mid, $defaultFollow);
		}
		//redirect($GLOBALS['ts']['site']['home_url']);
		//$this->redirect($GLOBALS['ts']['site']['home_url_str']);
	}

	/**
	 * 验证邮箱是否已被使用
	 */
	public function isEmailAvailable() {
		$email = t($_POST['email']);
		$result = $this->_register_model->isValidEmail($email);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
	}

	/**
	 * 验证邀请邮件
	 */
    public function isEmailAvailable_invite() {
		$email = t($_POST['email']);
		if(empty($email)) {
			exit($this->ajaxReturn(null, '', 1));
		}
		$result = $this->_register_model->isValidEmail_invite($email);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
	}

	/**
	 * 验证昵称是否已被使用
	 */
	public function isUnameAvailable() {
		$uname = t($_POST['uname']);
		$oldName = t($_POST['old_name']);
		$result = $this->_register_model->isValidName($uname, $oldName);
		$this->ajaxReturn(null, $this->_register_model->getLastError(), $result);
	}

	/**
	 * 添加用户关注信息
	 */
	public function bulkDoFollow() {
		$res = model('Follow')->bulkDoFollow($this->mid, t($_POST['fids']));
    	$this->ajaxReturn($res, model('Follow')->getError(), false !== $res);
	}

	/**
	 *  设置用户为已初始化
	 */
	public function doAuditUser(){
		$this->_register_model->overUserInit($this->mid);
		echo 1;
	}

	/**
	 * 判断验证码是否正确
	 * @return boolean 若正确返回true，否则返回false
	 */
	public function isValidVerify () {
		$verify = t($_POST['verify']);
		$res['status'] = 0;
		$res['info'] = '验证码输入错误';
		if (md5(strtoupper($verify)) == $_SESSION['verify']) {
			$res['status'] = 1;
			$res['info'] = '验证通过';
		}
		exit(json_encode($res));
	}

		/**
	 * 获得充值规则数组
	 * 
	 * @return array
	 * 			返回充值规则数组
	 */
	public function _getRechargeRule(){		
		$rechargeRule = array();
		for ($i=1;$i<10;$i++){
			if(!empty($_POST['recharge_recharge'.$i])){
				$rechargeRule[$_POST['recharge_recharge'.$i]]=$_POST['recharge_award'.$i];
			}
		}
		return $rechargeRule;
	}

	/**
	 * 过滤折扣 - 内部函数
	 * 
	 * @return float 
	 * 			返回过滤之后的折扣
	 */

	public function _filterDiscount($discount){
		if(empty($discount)) return 0;
		
		if(strstr($discount, '.')) {
			return $discount;
		}
		if(strlen($discount)==1){
			return $discount/10;
		}else{
			return $discount/100;
		}
	}

	/**
	 * 设置机构插件状态
	 */
	public function setCorpAddon($corp_id,$addonId){

			$addnoInfo = model('Addon')->getAddon($addonId);
			$add['corp_id'] = $corp_id;
			$add['addonId'] = $addonId;
			$add['name'] = $addnoInfo['name'];
			$add['pluginName'] = $addnoInfo['pluginName'];
			$add['ctime'] = time();
			M('corp_addons')->add($add);
	}

	public function setCorpApp($corp_id,$addonId){

		$appInfo = model('App')->getAppById($addonId);
		$add['corp_id'] = $corp_id;
		$add['app_id'] = $addonId;
		$add['app_name'] = $appInfo['app_name'];
		$add['app_alias'] = $appInfo['app_alias'];
		$add['ctime'] = time();
		M('corp_app')->add($add);
	}
}