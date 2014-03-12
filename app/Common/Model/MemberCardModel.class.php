<?php
/**
 * 会员卡关联模型 - 数据对象模型
 * @author jliu <jliu@cophea.com>
 * @version Cophea 1.0
 */
namespace Common\Model;
use Think\Model;
class MemberCardModel extends Model{
		
	/**
 	 * 获取指定机构,指定部门的会员卡列表 - 不分页型
	 *
	 * @param integer $corp_id
	 *        	机构ID
	 * @param integer $corp_department_id
	 *       	机构部门ID
	 * @return array 指定会员卡列表
	 */
	public function getMemberCardList($corp_id, $corp_department_id,$order = 'card_id DESC') {
		if (empty($corp_id)) {
			//$this->error = '错误的参数'; // 错误的参数
			//return false;
			$corp_id = $_SESSION['cid'];
		}
		try{
			$map['corp_id'] = $corp_id;
			//$map['corp_department_id'] =$corp_department_id; 
	 		$cards = M('Card')->where($map)->order($order )->select();
			if ($cards==null) {
				$this->error = '没有任何会员卡'; // 错误的参数
				return false;
			}
			$map = null;
			$lists =array();
			//一个会员可以有多张卡
			foreach ($cards as $key => $card){
				$map['member_id'] = $card['member_id'];
				$member = M('Member')->where($map)->select();
				if ($member==null) {
					$this->error = '没有任何会员'; // 错误的参数
					return false;
				}
				$result = array_merge($card,$member['0']);
				array_push($lists, $result);
			}
			return $lists;
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 获取指定机构,指定部门的会员卡列表 - 分页型
	 *
	 * @param integer $corp_id
	 *        	机构ID
	 * @param integer $corp_department_id
	 *       	机构部门ID
	 * @param integer $limit
	 *        	分页的结果集数目，默认为10
	 * 
	 * @return array 指定会员卡列表
	 */
	public function getMemberCardListByPage($corp_id=0, $department_id=0,$limit=10,$order = 'card_id DESC') {
		if (empty($corp_id)) {
			$map['corp_id'] = $_SESSION['cid'];
		}else{
			$map['corp_id'] = $corp_id;
		}
// 		$map['department_id'] =$department_id;
		
		try{	
			$cards = M('card')->where($map)->order ($order)->findPage ($limit);
			if ($cards==null) {
				$this->error = '没有任何会员卡'; // 错误的参数
				return false;
			}
			$map = null;
			$lists =array();
			//一个会员可以有多张卡
			foreach ($cards['data'] as $key => $card){
				$map['member_id'] = $card['member_id'];
				$member = M('Member')->where($map)->select();
				if ($member==null) {
					$this->error = '没有任何会员'; // 错误的参数
					break;
				}
				$result = array_merge($card,$member['0']);
				array_push($lists, $result);
			}
			$cards['data'] = $lists;
			return $cards;
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 创建会员卡/发卡
	 * 
	 * @param array $create
	 * 				创建会员卡的数据
	 * @return boolean 是否创建成功
	 * 
	 */
	public function addMemberCard($create){
		if(empty($create)){
			$this->error="参数错误";
			return false;
		}
		
		try{
			
			$map ['card_number'] = t ( $create['card']['card_number'] );
			$map ['corp_id'] = $_SESSION['cid'];
			$isNameExist = M('card')->where ($map)->count ();
			if ($isNameExist > 0) {
				$this->error = '卡号已经存在,请使用其他卡号';
				return false;
			}
			
			//事务初始化
			$this->startTrans();		
			//添加会员信息
			$create['member']['corp_id'] = intval($_SESSION['cid']);
			$member_id = M('member')->add($create['member']);
			//添加卡信息
			$create['card']['member_id'] = $member_id;
			$create['card']['corp_id'] = intval($_SESSION['cid']);
			$create['card']['department_id']=0;
			$create['card']['ctime']=time();
			$create['card']['lock']=0;
			$card_id = M('card')->add($create['card']);
			if($member_id && $card_id){
				//事务提交
				$this->commit();
				return $card_id;
			}else{
				//事务回滚
				$this->rollback();
				return false;
			}
		}catch (exception $e){
			$this->error($e->getMessage());	
		}
	}
	
	/**
	 * 编辑会员卡
	 * 
	 * @param array $update 
	 * 					需要编辑的信息/分成两部分提交 $updata['member'] 会员信息，$update['card'] 卡信息
	 * @return boolean 是否编辑成功
	 */
	public function updateMemberCard($update){
		if(empty($update)){
			$this->error="参数错误";
			return false;
		}		
		try{
			//添加会员信息
			$map = null;
			$map['member_id'] = $update['member']['member_id'];
			$result_member = M('member')->where($map)->save($update['member']);

 			//添加卡信息
			$map = null;
			$map['card_id'] = $update['card']['card_id'];
			$result_card = M('card')->where($map)->save($update['card']);
						
			
			if($result_member||$result_card){
				return true;
			}else{
				return false;
			}
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 删除会员卡
	 *
	 * @param integer $membercard_id 
	 * 				    会员卡ID
	 * @return boolean 是否删除成功
	 */
	public function deleteMemberCard($card_id){

		if(empty($card_id)){
			$this->error="参数错误";
			return false;
		}
		$map = null;
		$map['card_id'] = $card_id;
		//获取会员卡
		$card = M('card')->where($map)->findAll();
		$card = filterArray($card);
		//获得本卡持卡人所持的卡数量
		$map = null;
		$member_id = $card['member_id'];
		$map['member_id'] = $member_id;
		$cardcount = M('card')->where($map)->count();
		
		if($cardcount>1){
			//如何本会员有多张卡，只删除卡信息，而不删除会员信息
			return M('card')->where(array('card_id'=>$card_id))->delete();
		}else{
			return M('card')->where(array('card_id'=>$card_id))->delete() && M('member')->where(array('member_id'=>$member_id))->delete();
		}
	}
	
	/**
	 * 获得指定会员卡
	 * 
	 * @param integer $card_id
	 * 				    会员卡ID
	 * @return array 获得指定的会员卡信息
	 */
	public function getMembercardInfo($card_id){
		if(empty($card_id)){
			$this->error="参数错误";
			return false;
		}
		//获得卡信息
		$map = null;
		$map['card_id'] = $card_id;
		$card = M('card')->where($map)->findAll();
		//去掉0上标
		$card = filterArray($card);
		
		$map = null;
		$map['member_id']=$card['member_id'];
		$member = M('member')->where($map)->findAll();
		//去掉0上标
		$member = filterArray($member);
		return array_merge($card,$member);
	}
	
	/**
	 * 查询会员卡/仅用于首页-搜索卡号和手机
	 * 
	 * @param string $content
	 * 					查询的内容，卡号/会员姓名/手机
	 * @return array 会员卡信息
	 */
	public function query($content){
		try{
			/**
			 * 判断卡号
 			 */
			$membercards = array();
			$map = null;
			$map['card_number'] = $content;
			$map['corp_id'] = $_SESSION['cid'];
			$map['lock'] = 0;
			$card = M('card')->where($map)->findAll();
			$card = filterArray($card);
			if(!empty($card)){
				$map=null;
				$map['member_id'] = $card['member_id'];
				$member = M('member')->where($map)->findAll();
				$member = filterArray($member);
				return array_merge($member, $card);
			}else{
				//查询内容为手机号
				$map=null;
				$map['phone'] = $content;
				$map['corp_id'] = $_SESSION['cid'];
				$members = M('member')->where($map)->findAll();
				if(empty($members)){
					$this->error='没有找到会员信息,或会员被禁用';
					return false;
				}
				
				foreach ($members as $member){
					$map =null;
					$map['member_id'] = $member['member_id'];
					$map['lock'] = 0;
					$cards = M('card')->where($map)->findAll();
					//暂时只考虑只能搜索到单个会员
					foreach ($cards as $card){
						array_push($membercards, array_merge($member, $card));
					}
				}
				return $membercards;
			}

		
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 查询会员卡/用于会员卡页面-搜索卡号，姓名和手机
	 * 
	 * @param string $content
	 * 					查询的内容，卡号/会员姓名/手机
	 * @return array 会员卡信息
	 */
	public function search($content){
		try{
			$membercards = array();
			if(checkUTF($content) || checkMobile($content)){
				$map=null;
				
				if(checkUTF($content)){
					//查询内容为手机号
					$map['name'] = array('like','%'.$content.'%');
				}else{
					//查询内容为手机号
					$map['phone'] = $content;
				}
				$map['corp_id'] = $_SESSION['cid'];
				$members = M('member')->where($map)->findAll();
				if(empty($members)){
					$this->error='没有找到会员信息';
					return false;
				}
				foreach ($members as $member){
					$map =null;
					$map['member_id'] = $member['member_id'];
					$cards = M('card')->where($map)->findAll();
					//暂时只考虑只能搜索到单个会员
					foreach ($cards as $card){
						array_push($membercards, array_merge($member, $card));
					}
				}
				return $membercards;
			}else if(checkNumeric($content)){
				//会员卡号
				$map = null;
				$map['card_number'] = $content;
				$map['corp_id'] = $_SESSION['cid'];
				$cards = M('card')->where($map)->findAll();
		
				$map=null;
				$map['member_id'] = $cards['0']['member_id'];
				$member = M('member')->where($map)->findAll();
				if(empty($member)){
					$this->error='没有找到会员信息';
					return false;
				}
				array_push($membercards, array_merge($member[0], $cards[0]));
				return $membercards;
			}else{
				$this->error='请输入正确的卡号或手机号';
			}
		}catch (exception $e){
			$this->error($e->getMessage());
		}	
	}
	
	/**
	 * 增加金额
	 * 
	 * @param integer $card_id
	 * 						会员卡ID
	 * @param float $money
	 * 						增加的钱数
	 * @return boolean 是否增加成功
	 */
	public function increaseMoney($card_id,$money){
		if(empty($card_id)){
			$this->error='参数错误';
			return false;
		}
		//如果金额为0 或者空则跳出
		if(empty($money)){
			return true;
		}
		
		try{
			$map['card_id']=$card_id;
			$money_remain = M('card')->where($map)->getField('money_remain');
			$result = M('card')->where($map)->setField('money_remain',$money_remain+=$money);
			if(! $result){
				return false;
			}else{
				return true;
			}
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 降低余额
	 * 
	 * @param integer $card_id
	 * 					会员卡ID
	 * @param float $money
	 * 					减少的钱数
	 * @return boolean 是否减少成功
	 */
	public function reduceMoney($card_id,$money){
		if(empty($card_id)){
			$this->error='参数错误';
			return false;
		}
		
		//如果金额为0 或者空则跳出
		if(empty($money)){
			return true;
		}
		
		try{
			$map['card_id']=$card_id;
			$money_remain = M('card')->where($map)->getField('money_remain');
			if($money_remain >= $money){
				$result = M('card')->where($map)->setField('money_remain',$money_remain-=$money);
				if(! $result){
					return false;
				}else{
					return true;
				}
			}else{
				$this->error="余额不足";
				return false;
			}
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 增加积分
	 * 
	 * @param integer $card_id
	 * 					会员卡ID
	 * @param integer $credits
	 * 					需要增加的积分数
	 * @return boolean 是否增加成功
	 */
	public function increaseCredit($card_id,$credits){
		if(empty($card_id)){
			$this->error='参数错误';
			return false;
		}
		//如果积分为0 或者空则跳出
		if(empty($credits)){
			return true;
		}
		
		try{
			$map['card_id']=$card_id;
			$credits_remain = M('card')->where($map)->getField('credits_remain');
			$result = M('card')->where($map)->setField('credits_remain',$credits_remain+=$credits);
			if(! $result){
				return false;
			}else{
				return true;
			}
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 降低积分
	 * 
	 * @param integer $card_id
	 * 					会员卡ID
	 * @param integer $credits
	 * 					需要减少的积分数
	 * @return boolean 是否减少成功
	 */
	public function reduceCredit($card_id,$credits){
		if(empty($card_id)){
			$this->error='参数错误';
			return false;
		}
		//如果积分为0 或者空则跳出
		if(empty($credits)){
			return true;
		}
		
		try{
			$map['card_id']=$card_id;
			$credits_remain = M('card')->where($map)->getField('credits_remain');
			if($credits_remain >= $credits){
				$result = M('card')->where($map)->setField('credits_remain',$credits_remain-=$credits);
				if(! $result){
					return false;
				}else{
					return true;
				}
			}else{
				return true;
			}
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 增加次数
	 * 
	 * @param integer $card_id
	 * 					会员卡ID
	 * @param integer $credits
	 * 					需要增加的积分数
	 * @return boolean 是否增加成功
	 */
	public function increaseTimes($card_id,$times){
		if(empty($card_id)){
			$this->error='参数错误';
			return false;
		}
		//如果次数为0 或者空则跳出
		if(empty($times)){
			return true;
		}
		try{
			$map['card_id']=$card_id;
			$times_remain = M('card')->where($map)->getField('times_remain');
			$result = M('card')->where($map)->setField('times_remain',$times_remain+=$times);
			if(! $result){
				return false;
			}else{
				return true;
			}
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 降低次数
	 * 
	 * @param integer $card_id
	 * 					会员卡ID
	 * @param integer $credits
	 * 					需要减少的次数
	 * @return boolean 是否减少成功
	 */
	public function reduceTimes($card_id,$times){
		if(empty($card_id)){
			$this->error='参数错误';
			return false;
		}
		//如果次数为0 或者空则跳出
		if(empty($times)){
			return true;
		}
		try{
			$map['card_id']=$card_id;
			$times_remain = M('card')->where($map)->getField('times_remain');
			if($times_remain>=$times){
				$result = M('card')->where($map)->setField('times_remain',$times_remain-=$times);
				if(! $result){
					return false;
				}else{
					return true;
				}
			}else{
				return false;
			}
		}catch (exception $e){
			$this->error($e->getMessage());
		}
	}
	
	/**
	 * 应用卡型规则
	 *
	 * @param array $data
	 * 					需要处理的数据
	 * @param integer $card_id
	 * 					会员卡ID
	 * @param string $operation_type
	 * 					操作类型
	 * @return array 应用规则之后的数据
	 */
	public function applyRule($data,$rule,$operation_type){
		//处理规则
		switch ($operation_type){
			case 'xiaofei'://消费
				//折扣
				if($rule['isDiscountFeature'] && !empty($rule['consume_discount'])){
					$data['money'] *= $rule['consume_discount'];
				}
				//消费送积分
				if($rule['isCreditFeature']&& !empty($rule['consume_award_credits'])){
					$data['credit'] = round ($data['money']/$rule['consume_award_credits']);
				}
				
				return $data;
				break;
			case 'faka'://发卡
				//发卡送积分
				if($rule['isCreditFeature']&& !empty($rule['active_award_credits'])){
					$data['credit'] = $rule['active_award_credits'];
				}
				
				return $data;
				break;
			case 'chongzhi';//充值
				//充值功能
				$keys = array();
				foreach ($rule['rechargerule'] as $key=>$value){
					array_push($keys, $key);
				}
				rsort($keys);
				foreach ($keys as $key){
					if($data['money']>=$key){
						$data['money'] += $rule['rechargerule'][$key];
						return $data;
					}
				}
				break;
			case 'tixian'://提现
	
	
				break;
			case 'tuihuo'://退货
	
	
				break;
		}
		return $data;
	}
	
	/**
	 * 获得错误信息
	 *
	 * @return string 错误信息
	 */
	public function getError(){
		return $this->error;
	}
	
	/**
	 * 检查卡号是否重复
	 * 
	 * @param string $card_number
	 * 					商品名称
	 * @return boolean 是否重复
	 */
	public function isCardNumberExist($card_number){
		$map ['card_number'] = t ($card_number);
		$map ['corp_id'] = $_SESSION['cid'];
		$isExist = M('Card')->where ( $map )->count ();
		if ($isExist > 0) {
			return false;
		}
		return true;
	}
}