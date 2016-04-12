<?php
/**
 * 接口辅助类的简化引入文件，对现有接口的改造提供简易实现方式，更多的功能可以直接使用 YokaServiceUtility 类来进行操作。
 * 简化了输出的处理，启用了 ob_start 来接收原有的内容数据，使用 register_shutdown_function 自动完成内容封装后输出。
 * 注意：获取到内容后会调用 ob_clean() 清空原有的输出。
 * 依赖于 YokaServiceUtility.class.php, Curl.class.php
 * 
 * 需要定义的静态变量 YEPF_PATH ：YEPF框架的根目录，默认是：/YOKA/HTML/_YEPF
 * 需要设置的配置信息 $cfg_yokaservice_utility [monitor_param_in_charset, monitor_param_out_charset, show_tag,result_id, result_msg]
 * 创建了临时变量 $yokaservice_utility_obj 辅助类的实例化对象
 */

if (!defined('YOKA'))
{
	define('YOKA', true);
}
if (!defined('YEPF_PATH'))
{
	define('YEPF_PATH', dirname(__FILE__));
}

// 处理配置信息
if (isset($cfg_yokaservice_utility))
{
	if (!isset($cfg_yokaservice_utility['monitor_param_in_charset']))
	{	// 是否监听参数 in_charset
		$cfg_yokaservice_utility['monitor_param_in_charset'] = 0;
	}
	if (!isset($cfg_yokaservice_utility['monitor_param_out_charset']))
	{	// 是否监听参数 out_charset
		$cfg_yokaservice_utility['monitor_param_out_charset'] = 0;
	}
	if (!isset($cfg_yokaservice_utility['show_tag']))
	{	// 显示接口格式的标签 <!-- -->
		$cfg_yokaservice_utility['show_tag'] = 0;
	}
	if (!isset($cfg_yokaservice_utility['result_id']))
	{	// 处理返回代码
		$cfg_yokaservice_utility['result_id'] = '00';
	}
	if (!isset($cfg_yokaservice_utility['result_msg']))
	{	// 处理返回描述
		$cfg_yokaservice_utility['result_msg'] = '正常';
	}
}
else
{
	$cfg_yokaservice_utility = array(
		'monitor_param_in_charset'		=> 0,		// 是否监听参数 in_charset
		'monitor_param_out_charset'		=> 0,		// 是否监听参数 out_charset
		'show_tag'						=> 0,		// 显示接口格式的标签 <!-- -->
		'result_id'						=> '00',	// 处理返回代码
		'result_msg'					=> '正常'	// 处理返回描述
	);
}
// 引入必须的类 Curl, YokaServiceUtility
if (!class_exists('ext\Curl'))
{
	include YEPF_PATH.'/ext/Curl.class.php';
}
if (!class_exists('yoka\YokaServiceUtility'))
{
	include YEPF_PATH.'/yoka/YokaServiceUtility.class.php';
}
// PHP结束的时候调用接口辅助类完成内容的输出
function auto_shutdown_yokaservice_utility()
{
	global $yokaservice_utility_obj, $cfg_yokaservice_utility;
	$content = ob_get_contents();
	ob_clean();
	$output = $yokaservice_utility_obj->output($content, $cfg_yokaservice_utility['result_id'], $cfg_yokaservice_utility['result_msg']);
	if ($cfg_yokaservice_utility['monitor_param_out_charset'])
	{	// 监听输出参数的编码转换
		$output = $yokaservice_utility_obj->transferOutContent($output, $_REQUEST['out_charset']);
	}
	echo $output;
}

$yokaservice_utility_obj = new yoka\YokaServiceUtility();
if ($cfg_yokaservice_utility['monitor_param_in_charset'])
{	// 监听输入参数的编码转换，
	$yokaservice_utility_obj->processRequest();
}
if ($cfg_yokaservice_utility['show_tag'])
{
	$yokaservice_utility_obj->show_tag = 1;
}
ob_start();
//注册shutdown函数用来Debug显示
register_shutdown_function('auto_shutdown_yokaservice_utility');
?>