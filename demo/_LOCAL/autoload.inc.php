<?php
/**
 * YEPF 3.0 加载 YEPF之后调用此文件，对环境变量进行处理
 * 环境变量存放于\yoka\Conf::$ENV中
 */
namespace yoka;
if(\yoka\Conf::$ENV['yoka']['ENV_VERSION'] > '3.0'){
	$CACHE['memcached'] = array(
		'default' => array(
			'server'=>array(array('host'=>\yoka\Conf::$ENV['COMMON']['CACHE_HOST'], 'port'=>\yoka\Conf::$ENV['COMMON']['CACHE_PORT']))
		)
	);
	
	$CACHE['db'] = array(
		'default'=>array(
			//主数据库
			'master' =>array(
				array('host'=>\yoka\Conf::$ENV['LOCAl']['MDB_SERVER'],'user'=>\yoka\Conf::$ENV['LOCAl']['MDB_USER'], 'password'=>\yoka\Conf::$ENV['LOCAl']['MDB_PASS'] , 'database'=>\yoka\Conf::$ENV['LOCAl']['MDB_DB'])
			),
			//从数据库
			'slave' => array(
				array('host'=>\yoka\Conf::$ENV['LOCAl']['SDB_SERVER'],'user'=>\yoka\Conf::$ENV['LOCAl']['SDB_USER'], 'password'=>\yoka\Conf::$ENV['LOCAl']['SDB_PASS'] , 'database'=>\yoka\Conf::$ENV['LOCAl']['SDB_DB'])
			)
		),
		'weixin'=>array(
			//主数据库
			'master' =>array(
				array('host'=>\yoka\Conf::$ENV['weixin']['MDB_SERVER'],'user'=>\yoka\Conf::$ENV['weixin']['MDB_USER'], 'password'=>\yoka\Conf::$ENV['weixin']['MDB_PASS'] , 'database'=>\yoka\Conf::$ENV['weixin']['MDB_DB'])
			),
			//从数据库
			'slave' => array(
				array('host'=>\yoka\Conf::$ENV['weixin']['SDB_SERVER'],'user'=>\yoka\Conf::$ENV['weixin']['SDB_USER'], 'password'=>\yoka\Conf::$ENV['weixin']['SDB_PASS'] , 'database'=>\yoka\Conf::$ENV['weixin']['SDB_DB'])
			)
		),
	);
}else{
	/* YEPF2.0兼容模式  */
	$CACHE['memcached'] = array(
		'default' => array('server'=>array(array('host'=>$_SERVER['CACHE_DEFAULT_HOST'],'port'=>$_SERVER['CACHE_DEFAULT_PORT']))
		)
	);
	$CACHE['db'] = array(
		'default'=>array(
			//主数据库
			'master' =>array(
				array('host'=>$_SERVER['MDB_SERVER_LOCAL'],'user'=>$_SERVER['MDB_USER_LOCAL'], 'password'=>$_SERVER['MDB_PASS_LOCAL'] , 'database'=>$_SERVER['MDB_DB_LOCAL'])
			),
			//从数据库
			'slave' => array(
				array('host'=>$_SERVER['SDB_SERVER_LOCAL'],'user'=>$_SERVER['SDB_USER_LOCAL'], 'password'=>$_SERVER['SDB_PASS_LOCAL'] , 'database'=>$_SERVER['SDB_DB_LOCAL'])
			)
		),
	);
}