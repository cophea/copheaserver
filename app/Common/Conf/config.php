<?php
return array(
	//'配置项'=>'配置值'
	'DEFAULT_C_LAYER'=>'Action',
	'MODULE_ALLOW_LIST'    =>    array('Home','api'),
	'DEFAULT_MODULE'       =>    'Home',

	//数据库
	'DB_TYPE'               =>  'mysql',     // 数据库类型
    'DB_HOST'               =>  '127.0.0.1', // 服务器地址
    'DB_NAME'               =>  'cophea2',          // 数据库名
    'DB_USER'               =>  'root',      // 用户名
    'DB_PWD'                =>  '1234',          // 密码
    'DB_PORT'               =>  3306,        // 端口
    'DB_PREFIX'             =>  'coph_',    // 数据库表前缀
	
	//系统变量名称
    'VAR_JSONP_HANDLER'     =>  'callback', // JSONP 变量
);