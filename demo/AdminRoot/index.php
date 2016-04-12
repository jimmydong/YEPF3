<?php
/**
 * 项目启动文件
 * @author jimmy.dong@gmail.com 2013.10.14
 */
use yoka\mvc\App;
use yoka\mvc\Request;
use yoka\mvc\Config;

//注意，子站点加载自身init.php
include 'init.php';
$config = Config::instance();
$request = Request::getInstance();
$controller = $request->_c;
$action = $request->_a;
try
{
	$mvc = new App($controller, $action, 'admin');	//【注意】第三个参数标明子站点  
	$mvc->run();
}
catch (\Exception $err)
{
    header("Content-type:text/html;charset=utf-8");
	if(YEPF_IS_DEBUG){
		echo "遇到Exception，请查看Firephp内相关信息";
		throw $err;
	}else include("404.html");
}
