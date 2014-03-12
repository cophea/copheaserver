<?php
/**
 * 会员卡操作记录类
 * @author jliu <jliu@cophea.com>
 * @version Cophea 1.0
 */
namespace Common\Model;
use Think\Model;
class CardRecordModel extends Model{
	protected $tableName = 'card_record';
	protected $fields = array(0=>'card_record_id',
							  1=>'card_id',
							  2=>'corp_id',
							  3=>'department_id',
							  4=>'user_id',
							  5=>'mtime',
							  6=>'old_money_remain',
							  7=>'money_record',
							  8=>'new_money_remain',
							  9=>'old_credit_remain',
							  10=>'credit_record',
							  11=>'new_credit_remain',
							  12=>'old_times_remain',
							  13=>'times_record',
							  14=>'new_times_remain',
							  15=>'payment',
							  16=>'operation_type',
							  17=>'description',
							  18=>'commodityContent',
							  19=>'card_number',
							  20=>'member_name',
							  21=>'card_category',
							  22=>'isIndividual',
							  23=>'individual_id',
							  24=>'user_name',
						      25=>'corp_name',
							  26=>'department_name',
							  27=>'money_record_original',
							  28=>'profit',
							  '_autoinc'=>true,
							  '_pk'=>'card_record_id');
	
	
	/**
	 * 添加记录
	 * 
	 * @param array $record
	 * 					需要添加的记录
	 * @return boolean|integer 如果添加失败，返回False，如果添加成功，返回新添加的记录ID
	 */
	public function record($record){
		if(empty($record)){
			$this->error="错误的参数";
			return false;
		}
	
		$record['corp_id']=$_SESSION['cid'];
		$record['user_id']=$_SESSION['mid'];
		$record['mtime']=date("y-m-d h:i:s");
		
		$card_record_id = $this->add($record);
		
		if (! $card_record_id) {
			$this->error = '添加记录失败';
			return false;
		} else {
			return $card_record_id;
		}
	}
	
	/**
	 * 通过ID得到记录
	 * 
	 * @param integer $card_record_id
	 * 						卡记录ID
	 * @return array 卡记录信息
	 */
	public function getCardRecordInfo($card_record_id){
		if(empty($card_record_id)){
			$this->error = "参数错误";
			return false;
		}
		$map['card_record_id']=$card_record_id;
		return filterArray($this->where($map)->findAll());
	}
	
	/**
	 * 获得所有记录
	 */
	public function getAllRecordList(){
		
	}
	
	
	
	/**
	 * 获得所有记录-分页
	 */
	public function getAllRecordListByPage(){
		
	}
	
	/**
	 * 获取操作员的今日充值金额/今日消费金额/今日消费会员数
	 * 
	 * @param integer $cid 
	 * 					机构ID
	 * @param integer $mid
	 * 					用户ID
	 * @return array 操作员今日信息
	 */
	public function dayStatisticsByUser(){

		$map['corp_id'] = $_SESSION['cid'];
		$map['user_id'] = $_SESSION['mid'];
		$count['addMemberNum']=0;
		$count['cost']=0;
		$count['chongzhi']=0;
		
		
		$map['mtime']=array('like',date('Y-m-d').'%');
		$records = $this->where($map)->findAll();
		foreach ($records as $record){
			//if(date('Y-m-d',time()) == date('Y-m-d',$record['mtime'])){
				if($record['operation_type']=='发卡'){
					$count['addMemberNum']+=1;
				}
				if ($record['operation_type']=='消费') {
					$count['cost']+=$record['money_record'];
				}
				if ($record['operation_type']=='充值') {
					$count['chongzhi']+=$record['money_record'];
				}
			//}
			
		}

		return $count;
	}
	
	/**
	 * 得到上一条记录
	 */
	public function getLastRecord(){
		$map['corp_id'] = $_SESSION['cid'];
		$map['user_id'] = $_SESSION['mid'];
		$order = "card_record_id DESC";
		$records = $this->where ($map)->order ( $order )->findAll();
		return $records[0];
	}
	
}