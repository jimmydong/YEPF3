<?php
/**
 * 企业微信 项目启动文件
 * @author jimmy.dong@gmail.com 2013.10.14
 */
use yoka\mvc\App;
use yoka\mvc\Request;
use yoka\mvc\Config;


if(preg_match('/Jakarta/',$_SERVER['HTTP_USER_AGENT']))exit;

//注意，子站点加载自身init.php
include 'init.php';
$config = Config::instance();
$request = Request::getInstance();
$controller = $request->_c;
$action = $request->_a;
try
{
	$mvc = new App($controller, $action, 'qy');	//【开发】扣肉版
	$mvc->run();
}
catch (\Exception $err)
{
    header("Content-type:text/html;charset=utf-8");
	if(preg_match('/test/',$_SERVER['HTTP_HOST'])){
		echo "遇到Exception，请查看Firephp内相关信息";
		var_dump($err);
		throw $err;
	}else include("404.html");
}
