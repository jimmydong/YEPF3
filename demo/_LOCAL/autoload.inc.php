<?php
/**
 * YEPF 3.0 加载 YEPF之后调用此文件，对环境变量进行处理
 * 环境变量存放于WORK-ENV.ini中，由框架处理后保存在\yoka\Conf::$ENV中
 */
namespace yoka;
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
