<?php

/**
 * 记录日志
 * Enter description here ...
 * @param unknown_type $app_group
 * @param unknown_type $action
 * @param unknown_type $data
 * @param unknown_type $isAdmin 是否管理员日志
 */
function LogRecord($app_group,$action,$data,$isAdmin=false){
    static $log = null;
    if($log == null){
        $log = D('Logs');
    }
    return $log->load($app_group)->action($action)->record($data,$isAdmin);
}

/**
 * DES解密函数
 *
 * @param string $input
 * @param string $key
 */
function desdecrypt($encrypted,$key) {
    //使用新版的加密方式
    //require_once ('http://localhost:10088/cophea/copheaserver/app/Common/library/DES_MOBILE.php');
    //$desc = new DES_MOBILE();
    //return $desc->setKey($key)->decrypt($encrypted);
}

/**
 * 载入文件 去重\缓存.
 * @param string $filename 载入的文件名
 * @return boolean
 */
function tsload($filename) {
	
	static $_importFiles = array();	//已载入的文件列表缓存
	
	$key = strtolower($filename);
	
	if (!isset($_importFiles[$key])) {
		
		if (is_file($filename)) {
			
			require_once $filename;
			$_importFiles[$key] = true;
		} elseif(file_exists(CORE_LIB_PATH.'/'.$filename.'.class.php')) {
			
			require_once CORE_LIB_PATH.'/'.$filename.'.class.php';
			$_importFiles[$key] = true;
		} else {
			
			$_importFiles[$key] = false;
		}
	}
	return $_importFiles[$key];
}

/**
 * 获取站点唯一密钥，用于区分同域名下的多个站点
 * @return string
 */
function getSiteKey(){
    return md5(C('SECURE_KEY').C('SECURE_CODE').C('COOKIE_PREFIX'));
}

/**
 * 取一个二维数组中的每个数组的固定的键知道的值来形成一个新的一维数组
 * @param $pArray 一个二维数组
 * @param $pKey 数组的键的名称
 * @return 返回新的一维数组
 */
function getSubByKey($pArray, $pKey="", $pCondition=""){
    $result = array();
    if(is_array($pArray)){
        foreach($pArray as $temp_array){
            if(is_object($temp_array)){
                $temp_array = (array) $temp_array;
            }
            if((""!=$pCondition && $temp_array[$pCondition[0]]==$pCondition[1]) || ""==$pCondition) {
                $result[] = (""==$pKey) ? $temp_array : isset($temp_array[$pKey]) ? $temp_array[$pKey] : "";
            }
        }
        return $result;
    }else{
        return false;
    }
}

/**
 * 敏感词过滤
 */
function filter_keyword($html){
    static $audit  =null;
    static $auditSet = null;
    if($audit == null){ //第一次
        $audit = D('Xdata')->get('keywordConfig');
        $audit = explode(',',$audit);
        $auditSet =  D('Xdata')->get('admin_Config:audit');
    }
    // 不需要替换
    if(empty($audit) || $auditSet['open'] == '0'){
        return $html;
    }
    return str_replace($audit, $auditSet['replace'], $html);
}

/**
 * 获取字符串的长度
 *
 * 计算时, 汉字或全角字符占1个长度, 英文字符占0.5个长度
 *
 * @param string  $str
 * @param boolean $filter 是否过滤html标签
 * @return int 字符串的长度
 */
function get_str_length($str, $filter = false){
    if ($filter) {
        $str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
        $str = strip_tags($str);
    }
    return (strlen($str) + mb_strlen($str, 'UTF8')) / 4;
}

// 获取字串首字母
function getFirstLetter($s0) {
    $firstchar_ord = ord(strtoupper($s0{0}));
    if($firstchar_ord >= 65 and $firstchar_ord <= 91) return strtoupper($s0{0});
    if($firstchar_ord >= 48 and $firstchar_ord <= 57) return '#';
    $s = iconv("UTF-8", "gb2312", $s0);
    $asc = ord($s{0}) * 256 + ord($s{1}) - 65536;
    if($asc>=-20319 and $asc<=-20284) return "A";
    if($asc>=-20283 and $asc<=-19776) return "B";
    if($asc>=-19775 and $asc<=-19219) return "C";
    if($asc>=-19218 and $asc<=-18711) return "D";
    if($asc>=-18710 and $asc<=-18527) return "E";
    if($asc>=-18526 and $asc<=-18240) return "F";
    if($asc>=-18239 and $asc<=-17923) return "G";
    if($asc>=-17922 and $asc<=-17418) return "H";
    if($asc>=-17417 and $asc<=-16475) return "J";
    if($asc>=-16474 and $asc<=-16213) return "K";
    if($asc>=-16212 and $asc<=-15641) return "L";
    if($asc>=-15640 and $asc<=-15166) return "M";
    if($asc>=-15165 and $asc<=-14923) return "N";
    if($asc>=-14922 and $asc<=-14915) return "O";
    if($asc>=-14914 and $asc<=-14631) return "P";
    if($asc>=-14630 and $asc<=-14150) return "Q";
    if($asc>=-14149 and $asc<=-14091) return "R";
    if($asc>=-14090 and $asc<=-13319) return "S";
    if($asc>=-13318 and $asc<=-12839) return "T";
    if($asc>=-12838 and $asc<=-12557) return "W";
    if($asc>=-12556 and $asc<=-11848) return "X";
    if($asc>=-11847 and $asc<=-11056) return "Y";
    if($asc>=-11055 and $asc<=-10247) return "Z";
    return '#';
}

// 从AnguleJS 获取post的值
function getRequest(){
    return json_decode(file_get_contents("php://input"),true);
}

?>