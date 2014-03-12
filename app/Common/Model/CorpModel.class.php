<?php
/**
 * 机构模型 - 数据对象模型
 * @author jliu <jliu@cophea.com>
 * @version Cophea 1.0
 */
class CorpModel extends Model{
	protected $tableName = 'corp';
	protected $fields = array(0=>'corp_id',1=>'name',2=>'is_del',3=>'ctime',4=>'location',5=>'search_key',6=>'tel',7=>'shortMessage_count',8=>'description');
	
	/**
	 * 获得机构列表,后台可以查询
	 * 
	 * @param void
	 * 
	 * @return 所有机构列表，或者后台查询后的机构列表
	 */
	public function getCorpList(){
		if(isset($_POST)){
			$_POST ['corp_id'] && $map ['corp_id'] = intval ( $_POST ['corp_id'] );
			$_POST ['name'] && $map ['name'] = array('LIKE', '%'.t($_POST['name']).'%');
				
			// 注册时间判断，ctime为数组格式
			if (! empty ( $_POST ['ctime'] )) {
				if (! empty ( $_POST ['ctime'] [0] ) && ! empty ( $_POST ['ctime'] [1] )) {
					// 时间区间条件
					$map ['ctime'] = array (
							'BETWEEN',
							array (
									strtotime ( $_POST ['ctime'] [0] ),
									strtotime ( $_POST ['ctime'] [1] )
							)
					);
				} else if (! empty ( $_POST ['ctime'] [0] )) {
					// 时间大于条件
					$map ['ctime'] = array (
							'GT',
							strtotime ( $_POST ['ctime'] [0] )
					);
				} elseif (! empty ( $_POST ['ctime'] [1] )) {
					// 时间小于条件
					$map ['ctime'] = array (
							'LT',
							strtotime ( $_POST ['ctime'] [1] )
					);
				}
			}
		}
		return $this->where($map)->order ('corp_id DESC')->findAll();
	}
	
	/**
	 * 获得机构列表-分页型
	 * 
	 * @param void
	 * 
	 * @return 所有机构列表，或者后台查询后的机构列表
	 */
	public function getCorpListByPage($limit = 20){
		if(isset($_POST)){
			$_POST ['corp_id'] && $map ['corp_id'] = intval ( $_POST ['corp_id'] );
			$_POST ['name'] && $map ['name'] = array('LIKE', '%'.t($_POST['name']).'%');
			
			// 注册时间判断，ctime为数组格式
			if (! empty ( $_POST ['ctime'] )) {
				if (! empty ( $_POST ['ctime'] [0] ) && ! empty ( $_POST ['ctime'] [1] )) {
					// 时间区间条件
					$map ['ctime'] = array (
							'BETWEEN',
							array (
									strtotime ( $_POST ['ctime'] [0] ),
									strtotime ( $_POST ['ctime'] [1] )
							)
					);
				} else if (! empty ( $_POST ['ctime'] [0] )) {
					// 时间大于条件
					$map ['ctime'] = array (
							'GT',
							strtotime ( $_POST ['ctime'] [0] )
					);
				} elseif (! empty ( $_POST ['ctime'] [1] )) {
					// 时间小于条件
					$map ['ctime'] = array (
							'LT',
							strtotime ( $_POST ['ctime'] [1] )
					);
				}
			}
		}
		return $this->where($map)->order ('corp_id DESC')->findPage ( $limit );
	}
	
	/**
	 * 添加机构
	 *
	 * @param array|object $corp
	 *        	新机构的相关信息|新机构对象对象
	 * @return boolean|integer 如果没有添加成功，返回false ，如果添加成功 返回机构ID
	 */
	public function addCorp($corp) {
		//验证机构名是否重复
		$map ['name'] = t ( $corp ['name'] );
		$isExist = $this->where ( $map )->count ();
		if ($isExist > 0) {
			$this->error = '机构名已经存在，请使用其他机构名称';
			return false;
		}
		if (is_object ( $corp )) {
			$corp->ctime = time ();
			$corp->is_del = 0;//默认启用
		} else if (is_array ( $corp )) {
			$corp ['ctime'] = time ();
			$corp ['is_del'] = 0;//默认启用
		}
		// 添加机构名称拼音索引
		$corp ['first_letter'] = getFirstLetter ( $corp ['name'] );
		// 如果包含中文将中文翻译成拼音
		if (preg_match ( '/[\x7f-\xff]+/', $corp ['name'] )) {
			// 机构名称拼音保存到搜索字段
			$corp ['search_key'] = $corp ['name'] . ' ' . model ( 'PinYin' )->Pinyin ( $corp ['name'] );
		} else {
			$corp ['search_key'] = $corp ['name'];
		}
		// 添加机构操作
		try {
			$corp_id = $this->add ( $corp );
			if (! $corp_id) {
				$this->error = L ( '添加机构失败' ); // 添加用户失败
				return false;
			} else {
				return $corp_id;
			}
		}catch(exception $e) {
			$this->error =$e->getMessage();
		}
	}
	
	/**
	 * 根据机构ID获得一个机构
	 *
	 * @param integer $cid
	 *        	新机构的相关信息|新机构对象对象
	 * @return array 机构信息
	 */
	public function getCorpInfo($cid=0){
		if($cid==0){
			$cid = intval($_SESSION['cid']);
		}
		$map[corp_id] = $cid;
		try {
			$corp = $this->where($map)->findAll();
			if(empty($corp)){
				$this->error = "查询失败";
			}
			return filterArray($corp);
		}catch(exception $e) {
			$this->error =$e->getMessage();
		}

	}
	
	/**
	 * 更改机构信息
	 * 
	 * @param array $save
	 * 					需要更改的信息数据
	 * @return boolean 是否更改成功
	 */
	public function updateCorp($save){
		if(empty($save['corp_id'])){
			$corp_id=$_SESSION['cid'];
		}else{
			$corp_id = $save['corp_id'];
			unset($save['corp_id']);
		}
		
		if (empty($save['name'])){
			$this->error ='机构名称不能为空';
			return false;
		}
		
		try {	
			// 验证机构名称是否重复
			$oldCorpName = $this->where(array('corp_id'=>$corp_id))->getField('name');
			$save['name']  = t($save['name']);
			if($oldCorpName != $save['name']) {
				$isExist = $this->where(array('name'=>$save['name']))->count();
				if($isExist > 0) {
					$this->error='机构名称已存在，请使用其他机构名称';
					return false;
				}
			}
			
			
			//如果包含中文将中文翻译成拼音
			if ( preg_match('/[\x7f-\xff]+/', $save['name'] ) ){
				//昵称和呢称拼音保存到搜索字段
				$save['search_key'] = $save['name'].' '.model('PinYin')->Pinyin( $save['name'] );
			} else {
				$save['search_key'] = $save['name'];
			}
			
			$map['corp_id'] = $corp_id;
			$res = $this->where($map)->save($save);
			if($res) {
				return true;
			} else {
				return false;
			}
		}
		catch (Exception $e){
			$this->error = $e->getMessage();
		}
	}
	
	/**
	 * 获取最后错误信息
	 * 
	 * @return string 最后的错误信息 
	 */
	public function getError(){
		return $this->error;
	}
		
	/**
	 * 根据ID 获得机构名称
	 * 
	 * @param integer $corp_id
	 * 					机构ID
	 * @return string 机构名称
	 */
	public function getCorpNameByID($corp_id){
		if(empty($corp_id)){
			$this->error="参数错误";
			return false;
		}
		$map['corp_id'] = $corp_id;
		return $this->where($map)->getField('name');
	}

	/**
	 * 禁用机构
	 */
	public function deleteCorp($corp_ids){
		// 处理数据
		$corp_id_array = $this->_parseIds ($corp_ids);
		// 进行用户假删除
		$map ['corp_id'] = array (
			'IN',
			$corp_id_array 
		);
		$map['corp_id']=$corp_ids;
		$save ['is_del'] = 1;
		$result = $this->where ( $map )->save ( $save );
		$this->cleanCache ( $corp_id_array );
		if (! $result) {
			$this->error = '禁用机构失败'; // 禁用帐号失败
			return false;
		} else {
			//$this->deleteUserWeiBoData ( $uid_array );
			//$this->dealUserAppData ( $uid_array );
			return true;
		}
	}
	
	/**
	 * 彻底删除机构
	 */
	public function trueDeleteCorp($corp_ids){
		// 处理数据
		$corp_id_array = $this->_parseIds ( $corp_ids );
		// 进行用户假删除
		$map ['corp_id'] = array (
			'IN',
			$corp_id_array
		);
		$result = $this->where ( $map )->delete ();
		$this->cleanCache ( $corp_id_array );
		if (! $result) {
			$this->error = L ( 'PUBLIC_REMOVE_COMPLETELY_FAIL' ); // 彻底删除帐号失败
			return false;
		} else {
// 			$this->trueDeleteUserCoreData ( $uid_array );
			// 更新用户统计数目
// 			model('UserData')->updateUserDataByuid($uid_array);
			return true;
		}
	}
	
	/**
	 * 恢复指定机构
	 *
	 * @param array $ids
	 *        	恢复的机构ID数组
	 * @return boolean 是否恢复成功
	 */
	public function rebackCorp($corp_ids) {
		// 处理数据
		$corp_id_array = $this->_parseIds ( $corp_ids );
		// 恢复用户假删除
		$map ['corp_id'] = array (
				'IN',
				$corp_id_array
		);
		$save ['is_del'] = 0;
		$result = $this->where ( $map )->save ( $save );
		$this->cleanCache ( $corp_id_array );
		if (! $result) {
			$this->error = L ( 'PUBLIC_RECOVER_ACCOUNT_FAIL' ); // 恢复帐号失败
			return false;
		} else {
// 			$this->rebackUserWeiBoData ( $uid_array );
// 			$this->dealUserAppData ( $uid_array, 'rebackUserAppData' );
			return true;
		}
	}
	
	/**
	 * 处理用户UID数据为数组形式
	 *
	 * @param mix $ids
	 *        	用户UID
	 * @return array 数组形式的用户UID
	 */
	private function _parseIds($ids) {
		// 转换数字ID和字符串形式ID串
		if (is_numeric ( $ids )) {
			$ids = array (
					$ids
			);
		} else if (is_string ( $ids )) {
			$ids = explode ( ',', $ids );
		}
		// 过滤、去重、去空
		if (is_array ( $ids )) {
			foreach ( $ids as $id ) {
				$id_array [] = intval ( $id );
			}
		}
		$id_array = array_unique ( array_filter ( $id_array ) );
	
		if (count ( $id_array ) == 0) {
			$this->error = L ( 'PUBLIC_INSERT_INDEX_ILLEGAL' ); // 传入ID参数不合法
			return false;
		} else {
			return $id_array;
		}
	}
}