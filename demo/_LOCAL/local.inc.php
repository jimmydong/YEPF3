<?php
/**
 * @name local.inc.php
 * @desc YEPF 3.0 配置文件 DEMO
 * @author jimmy.dong@gmail.com
 * @createtime 2014-09-16
 * @caution 路径和URL末尾请不要加反斜线
 * 
 **/
header('X-Powered-From:'.$_SERVER['SERVER_ADDR']);

//指定ENV文件路径
define('ENV_PATH',substr(dirname(__FILE__),0, -7));

//指定 YEPF 3.0 路径
$yepf_used_path = ['/WORK/HTML/YEPF3','c:/xampp/htdocs/_YEPF3.0'];
foreach($yepf_used_path as $path){
	if(file_exists($path)){define('YEPF_PATH', $path);break;}
}
//强制关闭转义开关,特殊情况下请设置为true,建议为false
define('YEPF_FORCE_CLOSE_ADDSLASHES', false);

//是否默认打开调试模式
define('YEPF_IS_DEBUG', 'yoka-inc4');	//调试完毕后请删除此行
if(!defined('YEPF_IS_DEBUG'))
{
	define('YEPF_IS_DEBUG', $_SERVER['YEPF_IS_DEBUG']);
}

//自定义错误级别,只有在调试模式下生效
if(!defined('YEPF_ERROR_LEVEL'))
{
	define('YEPF_ERROR_LEVEL', E_ALL & ~E_NOTICE);
}


/*---------------------------项目常量开始---------------------------------*/
//此项目的根目录URL
if(!defined('ROOT_DOMAIN'))define('ROOT_DOMAIN','http://'.$_SERVER['HTTP_HOST']);
//此项目绝对地址
if(!defined('ROOT_PATH'))define('ROOT_PATH',substr(dirname(__FILE__),0, -7));
//此项目日志文件地址
if(!defined('LOG_PATH'))define('LOG_PATH',ROOT_PATH . '/_LOG');
//模板文件目录
if(!defined('TEMPLATE_PATH'))define('TEMPLATE_PATH',ROOT_PATH . '/_TEMPLATE');
//模板文件编绎目录
if(!defined('COMPILER_PATH'))define('COMPILER_PATH',ROOT_PATH . '/_TEMPLATE_C');
//默认的模板文件后缀名
if(!defined('TEMPLATE_TYPE'))define('TEMPLATE_TYPE','html');
//自定义类自动加载路径
if(!defined('CUSTOM_CLASS_PATH'))define('CUSTOM_CLASS_PATH', ROOT_PATH . '/_CUSTOM_CLASS');


