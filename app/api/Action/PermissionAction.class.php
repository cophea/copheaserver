<?php
/**
 * 权限控制器
 * @author liuxiaoqing <liuxiaoqing@zhishisoft.com>
 * @version TS3.0
 */
namespace api\Action;
use Think\Action;
class PermissionAction extends Action 
{

	//检测权限
    public function check(){
    	$load = I('post.load');
    	$action = I('post.action');
    	$res = CheckPermission($load,$action);
    	$this->ajaxReturn($res,'JSONP');
    }

}