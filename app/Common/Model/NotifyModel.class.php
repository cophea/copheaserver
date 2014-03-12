<?php
/**
 * 消息通知节点模型 - 数据对象模型
 * @example
 * 使用Demo：
 * model('Notify')->sendNotify(14983,'register_active',array('siteName'=>'SOCIAX','name'=>'yangjs'));
 * @author jliu <jliu@cophea.com>
 * @version cophea 2.0
 */
namespace Common\Model;
use Think\Model;
class NotifyModel extends Model {

	protected $tableName = 'notify_node';
	protected $fields = array(0=>'id',1=>'node',2=>'nodeinfo',3=>'appname',4=>'content_key',5=>'title_key',6=>'send_email',7=>'send_message',8=>'type');

	protected $_config = array();			// 配置字段

	/**
	 * 初始化方法，获取站点名称、系统邮箱、找回密码的URL
	 * @return void
	 */
	public function _initialize() {
		$site = empty($GLOBALS['ts']['site']) ? D('Xdata')->get('admin_Config:site') : $GLOBALS['ts']['site'];
		echo "1";
		dump($site);
		$this->_config['site'] = $site['site_name'];
		$this->_config['site_url'] = SITE_URL;
		$this->_config['kfemail'] = 'mailto:'.$site['sys_email'];
		$this->_config['findpass'] = U('public/Passport/findPassword');
	}


	/**
	 * 发送消息入口，对已注册用户发送的消息都可以通过此函数
	 * @param array $toUid 接收消息的用户ID数组
	 * @param string $node 节点Key值
	 * @param array $config 配置数据
	 * @param intval $from 消息来源用户的UID
	 * @return void
	 */
	public function sendNotify($toUid, $node, $config, $from) {
		
		empty($config) && $config = array();
		$config = array_merge($this->_config,$config);

		$nodeInfo = $this->getNode($node);
		if(!$nodeInfo) {
			return false;
		}
		!is_array($toUid) && $toUid = explode(',', $toUid);
		$userInfo = D('User')->getUserInfoByUids($toUid);

		$data['node'] = $node;
		$data['appname'] = $nodeInfo['appname'];
		$data['title'] = L($nodeInfo['title_key'], $config);
		$data['body'] = L($nodeInfo['content_key'], $config);
		foreach($userInfo as $v) {
			$data['uid'] = $v['uid'];
			!empty($nodeInfo['send_message']) && $this->sendMessage($data);
			$data['email'] = $v['email'];
			if(!empty($nodeInfo['send_email'])){
				if(in_array($node,array('atme','comment','new_message'))){
					$map['key'] = $node.'_email';
					$map['uid'] = $v['uid'];
					$isEmail = D('user_privacy')->where($map)->getField('value');
					$isEmail==0 && $this->sendEmail($data);
				}else{
					$this->sendEmail($data);
				}
			}
		}
		
	}

	/**
	 * 获取指定节点信息
	 * @param string $node 节点Key值
	 * @return array 指定节点信息
	 */
	public function getNode($node) {
		$list = $this->getNodeList();
		return $list[$node];
	}

	s/**
	 * 获取节点列表
	 * @return array 节点列表数据
	 */
	public function getNodeList() {
		// 缓存处理
		if($list = S('notify_node')) {
			return $list;
		}
		//if(($list = model('Cache')->get('notify_node')) == false) {
			$list = $this->getHashList('node', '*');
			//model('Cache')->set('notify_node', $list);
		//}
		S('notify_node', $list);

		return $list;
	}
}	