<?php
/**
 * 用户模型 - 数据对象模型
 * @author jliu <jliu@cophea.com>
 * @version cophea 2.0
 */
namespace Common\Model;
use Think\Model;
class UserModel extends Model {
	protected $tableName = 'user';
	protected $error = '';
	protected $fields = array (
			0 => 'uid',
			1 => 'login',
			2 => 'password',
			3 => 'login_salt',
			4 => 'uname',
			5 => 'email',
			6 => 'sex',
			7 => 'location',
			8 => 'is_root_admin',
			9 => 'is_init',
			10 => 'ctime',
			11 => 'identity',
			12 => 'api_key',
			13 => 'domain',
			14 => 'province',
			15 => 'city',
			16 => 'area',
			17 => 'reg_ip',
			18 => 'lang',
			19 => 'timezone',
			20 => 'is_del',
			21 => 'first_letter',
			22 => 'intro',
			23 => 'last_login_time',
			24 => 'last_feed_id',
			25 => 'last_post_time',
			26 => 'search_key',
			27 => 'invite_code',
			28 => 'invite_code',
			29 => 'feed_email_time',
			30 => 'send_email_time',
			31 => 'corp_id',
			32 => 'phone',
			33 => 'department_id',
			34 => 'is_auth',
			35 => 'pid',
			36 => 'phone',
			'_autoinc' => true,
			'_pk' => 'uid' 
	);
	
	/**
	 * 根据UID批量获取多个用户的相关信息
	 * 
	 * @param array $uids
	 *        	用户UID数组
	 * @return array 指定用户的相关信息
	 */
	public function getUserInfoByUids($uids) {
		! is_array ( $uids ) && $uids = explode ( ',', $uids );
		
		//$cacheList = model ( 'Cache' )->getList ( 'ui_', $uids );
		foreach ( $uids as $v ) {
			! $cacheList [$v] && $cacheList [$v] = $this->getUserInfo ( $v );
		}
		
		return $cacheList;
	}

	/**
	 * 获取指定用户的相关信息
	 *
	 * @param array $map
	 *        	查询条件
	 * @return array 指定用户的相关信息
	 */	
	private function _getUserInfo($map, $field = "*") {
		$user = $this->getUserDataByCache($map, $field);
		unset ( $user ['password'] );
		if (! $user) {
			$this->error = '获取用户信息失败'; // 获取用户信息失败
			return false;
		} else {
// 			$uid = $user ['uid'];
// 			$user = array_merge ( $user, model ( 'Avatar' )->init ( $user ['uid'] )->getUserAvatar () );
// 			$user ['avatar_url'] = U ( 'public/Attach/avatar', array (
// 					'uid' => $user ["uid"] 
// 			) );
// 			$user ['space_url'] = ! empty ( $user ['domain'] ) ? U ( 'public/Profile/index', array (
// 					'uid' => $user ["domain"] 
// 			) ) : U ( 'public/Profile/index', array (
// 					'uid' => $user ["uid"] 
// 			) );
// 			$user ['space_link'] = "<a href='" . $user ['space_url'] . "' target='_blank' uid='{$user['uid']}' event-node='face_card'>" . $user ['uname'] . "</a>";
// 			$user ['space_link_no'] = "<a href='" . $user ['space_url'] . "' title='" . $user ['uname'] . "' target='_blank'>" . $user ['uname'] . "</a>";
			// 用户勋章
// 			$user ['medals'] = model ( 'Medal' )->getMedalByUid ( $user ['uid'] );
			// 用户认证图标
// 			$groupIcon = array ();
// 			$userGroup = model ( 'UserGroupLink' )->getUserGroupData ( $uid );
// 			$user ['api_user_group'] = $userGroup [$uid];
// 			$user ['user_group'] = $userGroup [$uid];
// 			foreach ( $userGroup [$uid] as $value ) {
// 				$groupIcon [] = '<img title="' . $value ['user_group_name'] . '" src="' . $value ['user_group_icon_url'] . '" style="width:auto;height:auto;display:inline;cursor:pointer;" />';
// 			}
// 			$user ['group_icon'] = implode ( '&nbsp;', $groupIcon );
			
// 			model ( 'Cache' )->set ( 'ui_' . $uid, $user, 600 );
// 			static_cache ( 'user_info_' . $uid, $user );
			return $user;
		}
	}

	/**
	 * 获取ts_user表的数据，带缓存功能
	 * 
	 * @param array $map
	 *        	查询条件
	 * @return array 指定用户的相关信息
	 */
	function getUserDataByCache($map, $field = "*"){
		$key = 'userData_';
		foreach ($map as $k=>$v){
			$key .= $k.$v;
		}
		if($field!='*'){
			$key .= '_'.str_replace(array("`",","," "), '', $field);
		}

		//$user = model('Cache')->get($key);
		//if($user==false){
			$user = $this->where ( $map )->field ( $field )->find ();
		//	model('Cache')->set($key, $user,86400);  //缓存24小时
			//保存key和uid的关系，以方便后面用户资料变化时可以删除这些缓存
			if(isset($user['uid'])){  
			     $keys = model('Cache')->get('getUserDataByCache_keys_'.$user['uid']);
			     $keys[$key] = $key;
		//	     model('Cache')->set('getUserDataByCache_keys_'.$user['uid'], $keys);
			}
		//}

		return $user;
	}

	/**
	 * 获得用户机构ID
	 * 
	 * @param integer $uid
	 *        	用户UID
	 * @return int 获得用户机构ID
	 */
	public function getCorpId($uid){
		$uid = intval($uid);
		if ($uid <= 0) {
			$this->error = L ( 'PUBLIC_UID_INDEX_ILLEAGAL' ); // UID参数值不合法
			return false;
		}
		$map['uid']=$uid;
		return  $this->where($map)->getField('corp_id');
	}

	/**
	 * 获取指定用户的相关信息
	 * 
	 * @param integer $uid
	 *        	用户UID
	 * @return array 指定用户的相关信息
	 */
	public function getUserInfo($uid) {
		$uid = intval ( $uid );
		if ($uid <= 0) {
			$this->error = 'UID参数值不合法'; 
			return false;
		}
		if ($user = S( 'user_info_' . $uid )) {
			return $user;
		}
		// 查询缓存数据
		//$user = model ( 'Cache' )->get ( 'ui_' . $uid );
		
		//if (! $user) {
			//$this->error = L ( 'PUBLIC_GET_USERINFO_FAIL' ); // 获取用户信息缓存失败
			$map ['uid'] = $uid;
			$user = $this->_getUserInfo ( $map );
		//}
		S( 'user_info_' . $uid, $user );
		
		return $user;
	}

	/**
	 * 密码加密处理
	 * 
	 * @param string $password
	 *        	密码
	 * @param string $salt
	 *        	密码附加参数，默认为11111
	 * @return string 加密后的密码
	 */
	public function encryptPassword($password, $salt = '11111') {
		return md5 ( md5 ( $password ) . $salt );
	}
}