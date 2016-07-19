<?php
/**
 * @name init.php
 * @desc YEPF3.0 文件初始化设置,包含此目录包需要的文件及变量声明
 * @author jimmy.dong@gmail.com
 * @updatetime 2014-09-20
 * 【注意】建议目录结构
 *  _CUSTOM_CLASS 项目类文件目录
 *  _DOC 文档目录
 *  _LOCAL 保存本地配置信息
 *  _TEMPLATE 放置模板
 *  _TEMPLATE_C 模板编译目录(需可写)
 *  _LOG 日志目录(需可写)
 *  controller 控制器目录
 *  DocumentRoot 网站根目录
 *  AdminRoot 子站点目录
 *  PythonRoot OR Other 协同语言目录
 * @updatetime 2015-08-17
 * 【增加compose框架支持】 
 */


//加载Composer框架
//include(dirname(__FILE__) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php');

/*
 * 定义缺省值（注意： 子站点定义时应在调用此配置文件之前，以保证全局配置不覆盖子站点配置）
 */
if(!defined('DefaultPageLimit'))	define('DefaultPageLimit', 24);	
if(!defined('ENV_PATH'))			define('ENV_PATH', dirname(__FILE__));
if(!defined('SiteCacheTime'))		define('SiteCacheTime', 3600);
if(!defined('YEPF_IS_DEBUG'))		define('YEPF_IS_DEBUG', 'yoka-inc4');
if(!defined('ROOT_PATH'))			define('ROOT_PATH', dirname(__FILE__));
if(!defined('LOG_PATH'))			define('LOG_PATH', ROOT_PATH . '/_LOG');
//自定义错误级别,只有在调试模式下生效
if(!defined('YEPF_ERROR_LEVEL'))    define('YEPF_ERROR_LEVEL', E_ALL & ~E_NOTICE);
//自定义类自动加载路径
if(!defined('CUSTOM_CLASS_PATH'))	define('CUSTOM_CLASS_PATH', ROOT_PATH . '/_CUSTOM_CLASS');

//自动寻址YEPF3
if(!defined('YEPF_PATH')){
	if($_SERVER['YEPF_PATH_3']) define('YEPF_PATH',$_SERVER['YEPF_PATH_3']);
	else{
		if(file_exists('/WORK/HTML/YEPF3')) define('YEPF_PATH', '/WORK/HTML/YEPF3');
		elseif(file_exists(dirname(__FILE__) . '/YEPF3')) define('YEPF_PATH', dirname(__FILE__) . '/YEPF3');
		elseif(file_exists(dirname(__FILE__) . '/../YEPF3')) define('YEPF_PATH', dirname(__FILE__) . '/../YEPF3');
		else die("Can't find YEPF3");
	}
}

include YEPF_PATH . '/global.inc.php';
include dirname(__FILE__) . '/autoload.inc.php'; //应用环境变量

if($_REQUEST['refresh']==1)
	define('SiteCacheForceRefresh', 1);	//强制刷新缓存
else define('SiteCacheForceRefresh', 0);

//预加载类（避免程序中使用use出错）
if(class_exists('\yoka\Debug') && class_exists('\yoka\DB') && class_exists('\yoka\Log') && class_exists('\yoka\Template') && class_exists('\yoka\Cache')){}

//记录调试日志到文件（默认关闭）
\yoka\Debug::log_debug(false);

//记录数据库操作到文件（默认关闭）
if(0 && method_exists('yoka\Debug', 'log_db')){
	\yoka\Debug::log_db(true); //by jimmy.dong@gmail.com 2014.3.24
}
//记录到数据库
if(isset(\yoka\Debug::$log_mysql)){ //by jimmy.dong@gmail.com 2015.10.16
	\yoka\Debug::$log_mysql = array('db'=>'log', 'master'=>true);
//	\yoka\Debug::$db_log_mysql = true;		//记录数据库操作到数据库
//	\yoka\Debug::$debug_log_mysql = true;	//记录日志到数据库。建议试用 \yoka\Debug::dlog() 方法单独记录
}
//记录运行性能（默认关闭）
if (mt_rand(1, 1) == 0 && function_exists('xhprof_enable') ) {
 xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
 $xhprof_on = true;
}

//常用方法	
function isMobile()
{
	// 如果有HTTP_X_WAP_PROFILE则一定是移动设备
	if (isset ($_SERVER['HTTP_X_WAP_PROFILE']))
	{
		return true;
	}
	// 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
	if (isset ($_SERVER['HTTP_VIA']))
	{
		// 找不到为flase,否则为true
		return stristr($_SERVER['HTTP_VIA'], "wap") ? true : false;
	}
	// 脑残法，判断手机发送的客户端标志,兼容性有待提高
	if (isset ($_SERVER['HTTP_USER_AGENT']))
	{
		$clientkeywords = array ('nokia',
				'sony',
				'ericsson',
				'mot',
				'samsung',
				'htc',
				'sgh',
				'lg',
				'sharp',
				'sie-',
				'philips',
				'panasonic',
				'alcatel',
				'lenovo',
				'iphone',
				'ipod',
				'blackberry',
				'meizu',
				'android',
				'netfront',
				'symbian',
				'ucweb',
				'windowsce',
				'palm',
				'operamini',
				'operamobi',
				'openwave',
				'nexusone',
				'cldc',
				'midp',
				'wap',
				'mobile'
		);
		// 从HTTP_USER_AGENT中查找手机浏览器的关键字
		if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT'])))
		{
			return true;
		}
	}
	// 协议法，因为有可能不准确，放到最后判断
	if (isset ($_SERVER['HTTP_ACCEPT']))
	{
		// 如果只支持wml并且不支持html那一定是移动设备
		// 如果支持wml和html但是wml在html之前则是移动设备
		if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html'))))
		{
			return true;
		}
	}
	return false;
}

define('IS_MOBILE_USER', isMobile());

function json_encode2($result) {
	return json_encode($result, JSON_UNESCAPED_UNICODE);
}
/**
 * 调试函数，用以取代var_dump。
 * 不同于var_dump：多个参数间使用 , 分隔，而不是空格
 */
function var_dump2(){
	$varArray = func_get_args();
	foreach($varArray as $var) var_dump($var);
	$t = debug_backtrace(1);
	$caller = $t[0]['file'].':'.$t[0]['line'];
	echo " -- from $caller --";
	exit;
}
