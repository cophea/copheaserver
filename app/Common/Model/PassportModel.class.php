<?php
/**
 * 通行证模型 - 业务逻辑模型
 * @author jliu <jliu@cophea.com>
 * @version cophea 2.0
 */
namespace Common\Model;
use Think\Model;
class PassportModel {

	protected $error = null;		// 错误信息
	protected $rel = array();		// 判断是否是第一次登录

	/**
	 * 返回最后的错误信息
	 * @return string 最后的错误信息
	 */
	public function getError() {
		return $this->error;
	}

	/**
	 * 使用本地帐号登陆（密码为null时不参与验证）
	 * @param string $login 登录名称，邮箱或用户名
	 * @param string $password 密码
	 * @param boolean $is_remember_me 是否记录登录状态，默认为false
	 * @return boolean 是否登录成功
	 */
	public function loginLocal($login, $password = null, $is_remember_me = false) {		
		$user = $this->getLocalUser($login, $password);
		return $user['uid']>0 ? $this->_recordLogin($user['uid'], $is_remember_me) : false;
	}

	/**
	 * 根据标示符（email或uid）和未加密的密码获取本地用户（密码为null时不参与验证）
	 * @param string $login 标示符内容（为数字时：标示符类型为uid，其他：标示符类型为email）
	 * @param string|boolean $password 未加密的密码
	 * @return array|boolean 成功获取用户数据时返回用户信息数组，否则返回false
	 */
	public function getLocalUser($login, $password) {
		
		if(empty($login) || empty($password)) {
			$this->error = '账号或密码不能为空';			// 帐号或密码不能为空
			return false;
		}
		
		if($this->isValidEmail($login)){
			$map = "(login = '{$login}' or email='{$login}') AND is_del=0";
		}else{
			$map = "(login = '{$login}' or uname='{$login}') AND is_del=0";
		}
		
		if(!$user = D('User')->where($map)->find()) {
			$this->error = '账号不存在';			// 帐号不存在
			return false;
		}

		$uid  = $user['uid'];

		// 记录登陆日志，首次登陆判断
		$this->rel = M('LoginRecord')->where("uid = ".$uid)->field('locktime')->find();
		$login_error_time = cookie('login_error_time');

		if($this->rel['locktime'] > time()) {
			$this->error = '您的账号已经被锁定,请稍后再登录';			// 您的帐号已经被锁定，请稍后再登录
			return false;
		}
		
		if($password && md5(md5($password).$user['login_salt']) != $user['password']) {
			$login_error_time = intval($login_error_time) + 1;
			cookie('login_error_time', $login_error_time);

			$this->error = '密码输入错误，您还可以输入'.(6 - $login_error_time).'次';			// 密码错误
			if($login_error_time >=6) {
				// 记录锁定账号时间
				$save['locktime'] = time() + 60 * 60;
				$save['ip'] = get_client_ip();
				$save['ctime'] = time();
				$m['uid'] = $save['uid'] = $uid;

				$this->error = '您输入的密码错误次数过多，帐号将被锁定1小时';		// 您输入的密码错误次数过多，帐号将被锁定1小时
				// 发送锁定通知
				//D('Notify')->sendNotify($uid, 'user_lock');

				cookie('login_error_time', null);

				if($this->rel) {
					M('login_record')->add($save);
				} else {
					M('login_record')->where($m)->save($save);
				}
			}
			return false;
		} else {
			$logData['uid'] = $uid;
			$logData['ip'] = get_client_ip();
			$logData['ctime'] = time();
			M('login_logs')->add($logData);
			return $user;
		}
	}

	/**
	 * 设置登录状态、记录登录日志
	 * @param integer $uid 用户ID
	 * @param boolean $is_remember_me 是否记录登录状态，默认为false
	 * @return boolean 操作是否成功
	 */
	private function _recordLogin($uid, $is_remember_me = false) {

		// 注册cookie
		//if(!$this->getCookieUid() && $is_remember_me ) {
			$expire = 3600 * 24 * 30;
			cookie('TSV3_LOGGED_USER', $this->jiami(C('SECURE_CODE').".{$uid}"), $expire);
		//}

		// 记住活跃时间
		//cookie('TSV3_ACTIVE_TIME',time() + 60 * 30);
		//cookie('login_error_time', null);

		// 更新登陆时间
		D('User')->setField('last_login_time', $_SERVER['REQUEST_TIME'], 'uid='.$uid );

		// 记录登陆日志，首次登陆判断
		empty($this->rel) && $this->rel	= M('login_record')->where("uid = ".$uid)->getField('login_record_id');

		// 注册session
		$_SESSION['mid'] = intval($uid);
		$_SESSION['SITE_KEY']=getSiteKey();
		// 机构id
		$_SESSION['cid'] = D('User')->getCorpId($uid); 
		
		$map['ip'] = get_client_ip();
		$map['ctime'] = time();
		$map['locktime'] = 0;

		if($this->rel) {
			M('login_record')->where("uid = ".$uid)->save($map);
		} else {
			$map['uid'] = $uid;
			M('login_record')->add($map);
		}
		
		return true;
	}

	/**
	 * 判断email地址是否合法
	 * @param string $email 邮件地址
	 * @return boolean 邮件地址是否合法
	 */
	public function isValidEmail($email) {
		//return preg_match("/[_a-zA-Z\d\-\.]+@[_a-zA-Z\d\-]+(\.[_a-zA-Z\d\-]+)+$/i", $email) !== 0;
		return true;
	}

	/**
	 * 加密函数
	 * @param string $txt 需加密的字符串
	 * @param string $key 加密密钥，默认读取SECURE_CODE配置
	 * @return string 加密后的字符串
	 */
	private function jiami($txt, $key = null) {
		empty($key) && $key = C('SECURE_CODE');
		//有mcrypt扩展时
		//if(function_exists('mcrypt_module_open')){
		//	return desencrypt($txt, $key);
		//}
		//无mcrypt扩展时
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789-=_";
		$nh = rand(0, 64);
		$ch = $chars[$nh];
		$mdKey = md5($key.$ch);
		$mdKey = substr($mdKey, $nh % 8, $nh % 8 + 7);
		$txt = base64_encode($txt);
		$tmp = '';
		$i = 0;
		$j = 0;
		$k = 0;
		for($i = 0; $i < strlen($txt); $i++) {
			$k = $k == strlen($mdKey) ? 0 : $k;
			$j = ($nh + strpos($chars, $txt [$i]) + ord($mdKey[$k++])) % 64;
			$tmp .= $chars[$j];
		}
		return $ch.$tmp;
	}

	/**
	 * 注销本地登录
	 * @return void
	 */
	public function logoutLocal() {
		unset($_SESSION['mid'],$_SESSION['SITE_KEY']); // 注销session
		cookie('TSV3_LOGGED_USER', NULL);	// 注销cookie
	}
}