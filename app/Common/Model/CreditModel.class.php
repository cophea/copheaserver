<?php
/**
 * 积分管理模型 - 数据对象模型
 * @author jliu <jliu@cophea.com>
 * @version Cophea 1.0
 */
class CreditModel extends Model
{
    protected $tableName = 'credit';
    protected $error = '';
    protected $fields = array(
        0 => 'credit_commodity_id',
        1 => 'name',
        2 => 'counts',
        3 => 'is_del',
        4 => 'corp_id',
        5 => 'department_id',
        6 => 'ctime',
        7 => 'cost',
        '_autoinc' => true,
        '_pk' => 'credit_commodity_id');

    /**
     * 获取积分兑换商品列表
     * 
     * @param integer $limit
     *        	结果集数目，默认为20
     * @param array $map
     *        	查询条件
     * @return array 用户列表信息
     */
    public function getList($cid = 0, $limit = 20, $map = array(), $order = "name")
    {
        if ($cid==0) {
            return null;
        }
        $map['corp_id'] = $cid;
        // 查询数据
        $list = $this->where($map)->order($order)->findPage($limit);
        return $list;
    }
    
    //根据名称搜索商品
    public function searchList($name,$cid,$limit = 10){
		if (empty($cid)) {
			$this->error = '错误的参数'; // 错误的参数
			return false;
		}
		$map['corp_id'] = $cid;
		$map['name']=array('like','%'.$name.'%');

	
		$list = $this->where ( $map )->order ('name DESC')->findPage ( $limit );
		return $list;
	}
    
	/**
	 * 获取指定积分兑换商品的相关信息
	 * 
	 * @param integer $uid
	 *        	用户UID
	 * @return array 指定用户的相关信息
	 */
	public function getone($gid=0) {
		
        if ($gid == 0) {
			$this->error = L ( 'PUBLIC_UID_INDEX_ILLEAGAL' ); // UID参数值不合法
			return false;
		}
        // 查询缓存数据1
		//if ($user = static_cache ( 'user_info_' . $uid )) {
		//	return $user;
    	//	}
		// 查询缓存数据2
		//$user = model ( 'Cache' )->get ( 'ui_' . $uid );
		$map['credit_commodity_id'] = $gid;
		$list = $this->where ( $map )->findAll();
		return $list;
	}
    
	/**
	 * 获取指定名称的商品的相关信息
	 * 
	 * @param integer $uid
	 *        	用户UID
	 * @return array 指定用户的相关信息
	 */
	public function getbyname($name='0') {
		
        if ($name ==='0') {
			$this->error = L ( 'PUBLIC_UID_INDEX_ILLEAGAL' ); // UID参数值不合法
			return false;
		}
        // 查询缓存数据1
		//if ($user = static_cache ( 'user_info_' . $uid )) {
		//	return $user;
    	//	}
		// 查询缓存数据2
		//$user = model ( 'Cache' )->get ( 'ui_' . $uid );
		$map['name'] = $name;
		$list = $this->where ( $map )->findAll();
		return $list;
	}    
    
	/**
	 * 增加商品
	 * 
	 * @param array $data
	 * 				 	商品对象
	 * @return boolean 添加成功返回true
	 */
	public function addone($data){
	   
		if(empty($data)) {
			return false;
		}
		$map['corp_id']=$_SESSION['cid'];
		$map['name']=$data['name'];
		$map['counts']=$data['counts'];
		$map['is_del']=$data['is_del'];
		$map['department_id']=0;
		$map['ctime']=time();
		$map['cost']=$data['cost'];
		try {
			if($this->add($map)) {
				$this->cleanCache();
				return true;
			} else {
				return false;
			}
		}catch (Exception $e){
		  return false;
		//	$this->error($e->getMessage());
		}
	}
        
 	/**
	 * @param array $data
	 * 					需要修改的商品字段的数组以及商品主键（commodity_id）
	 * 修改商品
	 */
	public function editone($data){
		if(empty($data)) {
			return false;
		}
		$map['credit_commodity_id']=$data['credit_commodity_id'];
		$this->where($map)->save($data);
	}
    
    
 	/**
	 * 删除商品
	 *
	 * @param integer $commodity_id
	 *        		     		机构ID
	 * @return boolean 删除成功返回true
	 */
	public function delone($id){
		
		if(empty($id)){
			$this->error('参数错误');
			return false;
		}
		$map['credit_commodity_id']=$id;
		try {
			if($this->where($map)->delete()) {
				//$this->cleanCache();
				return true;
			} else {
				return false;
			}
		}catch (Exception $e){
			$this->error($e->getMessage());
		}
	}  
    
    
 	/**
	 * 减少库存
	 * 
	 * @param integer $card_id
	 * 					会员卡ID
	 * @param integer $credits
	 * 					需要减少的积分数
	 * @return boolean 是否减少成功
	 */
	public function reduceCount($credit_commodity_id,$count){
		if(empty($credit_commodity_id)){
			$this->error='参数错误';
			return false;
		}
		//如果积分为0 或者空则跳出
		if(empty($count)){
			return true;
		}
		
		try{
			$map['credit_commodity_id']=$credit_commodity_id;
			$credits_remain = $this->where($map)->getField('counts');
			if($credits_remain>$credits){
				$result = $this->where($map)->setField('counts',$credits_remain-=$count);
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
	 * 获取最后错误信息
	 * 
	 * @return string 最后错误信息
	 */
	public function getError() {
		return $this->error;
	}
    
        
}
