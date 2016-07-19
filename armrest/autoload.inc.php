<?php
/**
 * YEPF 3.0 加载 YEPF之后调用此文件，对环境变量进行处理
 * 环境变量存放于\yoka\Conf::$ENV中
 */
namespace yoka;
//memcache
$CACHE['memcached'] = array(
	'default' => array(
		'server'=>array(array('host'=>'127.0.0.1', 'port'=>11211))
	)
);
//自动区分本地或线上
if(preg_match('/yisheng/i',$_SERVER['HTTP_HOST'])){
	$CACHE['db'] = array(
			'default'=>array(
					//主数据库
					'master' =>array(
							array( 'host'=>'mydb02:8302','user'=>'jimmy', 'password'=>'sfKpNxxxxxXrm6XU' , 'database'=>'test')
					),
					//从数据库
					'slave' => array(
							array( 'host'=>'mydb02:8302','user'=>'jimmy', 'password'=>'sfKpNxxxxxXrm6XU' , 'database'=>'test')
					)
			),
			'log'=>array(
					//主数据库
					'master' =>array(
							array( 'host'=>'mydb02:8302','user'=>'jimmy', 'password'=>'sfKpNxxxxxXrm6XU' , 'database'=>'test')
					),
					//从数据库
					'slave' => array(
							array( 'host'=>'mydb02:8302','user'=>'jimmy', 'password'=>'sfKpNxxxxxXrm6XU' , 'database'=>'test')
					)
			),
	
	);
}else{
	$CACHE['db'] = array(
		'default'=>array(
			//主数据库
			'master' =>array(
				array( 'host'=>'127.0.0.1','user'=>'root', 'password'=>'' , 'database'=>'test')
			),
			//从数据库
			'slave' => array(
				array( 'host'=>'127.0.0.1','user'=>'root', 'password'=>'' , 'database'=>'test')
			)
		),
		'log'=>array(
			//主数据库
			'master' =>array(
				array( 'host'=>'127.0.0.1','user'=>'root', 'password'=>'' , 'database'=>'test')
			),
			//从数据库
			'slave' => array(
				array( 'host'=>'127.0.0.1','user'=>'root', 'password'=>'' , 'database'=>'test')
			)
		),

	);
}
