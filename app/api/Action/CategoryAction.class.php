<?php
/**
 * 卡型控制器
 * @author jliu <jliu@cophea.com>
 * @version cophea 2.0
 */
namespace api\Action;
use Think\Action;
class CategoryAction extends Action{
	private $_cardcategory_model;	//卡类型模型
	
	public function _initialize(){
		$this->_cardcategory_model = D('CardCategory');
	}
	
	/**
	 * 添加会员卡型基本信息-操作
	 */
	public function doAdd(){

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

			$this->assign('jumpUrl',U('membercard/Category/index'));
			$this->success();
		}else{
			$this->error($this->_cardcategory_model->getError());
		}
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
	 * 删除会员卡操作
	 */
	public function doDelete(){
		if($this->_cardcategory_model->deleteCardCategory($_GET['id'])){
			$this->assign('jumpUrl',U('membercard/Category/index'));
			$this->success('删除成功');	
		}else{
			$this->error('删除失败');
		}	
	}
	
	/**
	 * 详细编辑会员卡型-操作
	 */
	public function doEdit(){
		//基本信息
		$update['cardcategory_id'] = intval($_POST['cardcategory_id']);
		$update['name'] = t($_POST['name']);
		$update['description'] = $_POST['description'];
		//$update['image']=APP_URL.'/_static/image/'.$_POST['image'];
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
		$update['rule']=$rule;

		if($this->_cardcategory_model->updataCardCategory($update)){
			//开启积分应用
			if($rule['isCreditFeature']){
				if(!isset($GLOBALS['ts']['site_nav_apps']['credit'])){
					$this->setCorpApp($this->cid,35);
				}
			}

			$this->assign('jumpUrl',U('membercard/Category/index'));
			$this->success();
		}else{
			$this->error($this->_cardcategory_model->getError());
		}
		 
	}
	
	/**
	 * 获得功能规则
	 */
	public function featureRules(){
		$cardcategory = model('CardCategory')->getCardCategoryInfo(intval($_POST['cardcategory_id']));
		$this->ajaxReturn($cardcategory['rule'],'',1);
	}
	
	/**
	 * 禁用会员卡
	 */
	public function disableCategory(){
		$map['cardcategory_id'] = intval($_GET['id']);
		if(model('CardCategory')->where($map)->setField('is_del',1)){
			$this->success('禁用成功');
		}else{
			$this->error('禁用失败');
		}
	}
	
	/**
	 * 恢复会员卡型
	 */
	public function restoreCategory(){
		$map['cardcategory_id'] = intval($_GET['id']);
		if(model('CardCategory')->where($map)->setField('is_del',0)){
			$this->success('恢复成功');
		}else{
			$this->error('恢复失败');
		}
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
