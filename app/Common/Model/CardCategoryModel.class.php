<?php
/**
 * 会员卡类型模型 - 数据对象模型
 * @author jliu <jliu@cophea.com>
 * @version Cophea 1.0
 */
class CardCategoryModel extends Model{
	protected $tableName = 'card_category';
	protected $fields = array(0=>'cardcategory_id',1=>'name',2=>'is_del',3=>'description',4=>'corp_id',5=>'department_id',6=>'ctime',7=>'rule',8=>'image');
	// 最近错误信息
	protected $error = '';
	
	/**
	 * 以机构为单位，获取卡类型列表
	 * 
	 * @param int $cid 
	 * 			    	机构ID
	 * @param int $dep_id 
	 * 			    	部门ID,可选 
	 * @return array 卡类型列表
	 */
	public function getCardCategoryList($cid=0,$dep_id=0){

		$map['corp_id'] = $_SESSION['cid'];
		$map['is_del']=0;
		if(!empty($dep_id)&&!$dep_id=0){
			$map['department_id'] = $dep_id;
		}
		$results = $this->where($map)->findAll();
		foreach ($results as $key => $result){
			$results[$key]['rule'] = unserialize($result['rule']);
		}
		return $results;
	}
	
	/**
	 * 获取已经禁用的机构列表
	 * @param number $cid
	 * @param number $dep_id
	 * @return mixed
	 */
	public function getDelCardCategoryList($cid=0,$dep_id=0){
	
		$map['corp_id'] = $_SESSION['cid'];
		$map['is_del']=1;
		if(!empty($dep_id)&&!$dep_id=0){
			$map['department_id'] = $dep_id;
		}
		$results = $this->where($map)->findAll();
		foreach ($results as $key => $result){
			$results[$key]['rule'] = unserialize($result['rule']);
		}
		return $results;
	}
	
	/**
	 * 获得单个会员卡型信息
	 * 
	 * @param int $cardcategory_id
	 * 					会员卡型ID
	 * @return array 指定的会员卡型信息
	 */
	public function getCardCategoryInfo($cardcategory_id){
		if(empty($cardcategory_id)){
// 			$this->error="错误的参数";
			return false;
		}
		
		$map['cardcategory_id'] = $cardcategory_id;
		$result = $this->where($map)->findAll();
		if(! $result){
// 			$this->error('没有找到任何会员卡型');
			return false;
		}else {
			//去除数组0上标
			$result = filterArray($result);
			//解除序列化rule
			$result['rule'] = unserialize($result['rule']);
			return $result;
		}
	}
	
	/**
	 * 获得单个会员卡型信息
	 *
	 * @param int $cardcategory_id
	 * 					会员卡型ID
	 * @return array 指定的会员卡型信息
	 */
	public function getCardCategoryInfobyName($cardcategoryname){
		if(empty($cardcategoryname)){
			// 			$this->error="错误的参数";
			return false;
		}
		
		$map['corp_id'] = $_SESSION['cid'];
		$map['name'] = $cardcategoryname;
		$result = $this->where($map)->findAll();
		if(! $result){
			// 			$this->error('没有找到任何会员卡型');
			return false;
		}else {
			//去除数组0上标
			$result = filterArray($result);
			//解除序列化rule
			$result['rule'] = unserialize($result['rule']);
			return $result;
		}
	}
	
	
	/**
	 * 添加卡类型
	 *
	 * @param array $data
	 *        			新卡型的相关信息
	 * @return boolean 是否添加成功
	 */
	public function addCardCategory($create){
		if(empty($create)){
			$this->error('错误的参数');
			return false;
		}
		//验证名称是否存在
		$map ['name'] = t ( $create ['name'] );
		$map ['corp_id'] = $_SESSION['cid'];
		$isExist = $this->where ( $map )->count ();
		if ($isExist > 0) {
			$this->error = '卡类型名称已经存在，请使用其他名称';
			return false;
		}
		
		$create['rule']=serialize($create['rule']);
		$create['corp_id']=$_SESSION['cid'];
		$create['is_del']=0;
		$create['department_id']=0;
		$create['ctime']=time();
		
		$result = $this->add($create);
		if (! $result) {
			$this->error = L ('添加卡类型失败'); // 添加用户失败
			return false;
		} else {
			return true;
		}	
	}
	
	/**
	 * 修改会员卡类型
	 * 
	 * @param array $update 
	 * 						需要修改的信息
	 * @return boolean 是否修改成功
	 */
	public function updataCardCategory($update){
		if(empty($update)){
			$this->error('错误的参数');
			return false;
		}
		//验证名称是否存在
// 		$map ['name'] = t ( $update ['name'] );
// 		$map ['corp_id'] = $_SESSION['cid'];
// 		$isExist = $this->where ( $map )->count ();
// 		$map=null;
// 		if ($isExist > 0) {
// 			$this->error = '卡类型名称已经存在，请使用其他名称';
// 			return false;
// 		}
		//$update['rule']=serialize($update['rule']);
		//$update['corp_id']=$_SESSION['cid'];
		//$update['is_del']=0;
		//$update['department_id']=0;

		$update['rule']=serialize($update['rule']);
		$map['cardcategory_id'] = $update['cardcategory_id'];
		unset($update['cardcategory_id']);
		$result = $this->where($map)->save($update);
		if (! $result) {
			$this->error = L ('修改卡类型失败'); // 添加用户失败
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 删除会员卡类型
	 * 
	 * @param integer $cardcategory_id
	 * 				  			会员卡型ID
	 * @return boolean 是否删除成功
	 */
	public function deleteCardCategory($cardcategory_id){
		if(empty($cardcategory_id)){
			$this->error="参数无效";
			return;
		}
		$map['cardcategory_id'] = $cardcategory_id;
		
		$result = $this->where($map)->delete();
		if (! $result) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * 获取所有会员卡型的名称数组
	 *
	 * @param integer $cid
	 * 					机构ID
	 * @param integer $department_id
	 * 					部门ID
	 * @return array 会员卡型名称数组
	 */
	public function getCardCategoryNames($cid=0,$department_id=0){
		if(empty($cid)){
			$map['corp_id']=$_SESSION['cid'];
		}else{
			$map['corp_id'] = $cid;
		}
		$result = $this->where($map)->field('name')->findAll();
		if(! $result){
			$this->error="没有找到任何卡类型";
			return false;
		}else{
			return $result;
		} 
	}
	
	/**
	 * 获得错误信息
	 * 
	 * @return string 错误信息
	 */
	public function getError(){
		return $this->error;
	}
}