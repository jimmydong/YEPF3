<?php
/**
 * @name function.inc.php
 * @desc 通用函数库,只有全局都需要使用的方法才可以放到这里
 * @author caoxd
 * @createtime 2008-02-16 11:37
 * @updatetime 
 * 【注意】仅YEPF需要依赖的函数设置为全局，其他函数转入  yoka\Utility 类中。
 */
/**
 * @name getCustomConstants
 * @desc 获得用户自定义常量
 * @param string $constants_name 常量名称
 * @author 曹晓冬
 * @createtime 2009-03-30
 */
function getCustomConstants($constants_name)
{
	return defined('SUB_' . $constants_name) ? constant('SUB_' . $constants_name) : constant($constants_name);
}
/**
 * @name yaddslashes
 * @desc 转义定符串函数
 * @param string $string
 * @return mixed
 * @author 曹晓冬
 * @createtime 2009-03-30
 */
function yaddslashes($string)
{
	if(!get_magic_quotes_gpc())
	{
		if(is_array($string)) {
			foreach($string as $key => $val) {
				$string[$key] = yaddslashes($val);
			}
		} else {
			$string = addslashes($string);
		}
	}
	return $string;
}
/**
 * @name getEndTime
 * @desc 计算执行页面所需时间函数
 * @param string $msg 附加信息
 * @return string
 * @author 曹晓冬
 * @createtime 2009-03-30
 **/
function getEndTime($msg = '')
{
	return $msg . (microtime() - YEPF_BEGIN_TIME);
}

/**
 * @name getReqInt
 * @desc 接收用户输入值-整型
 * @author 曹晓冬
 * @param string $name	变量的名称
 * @param string $method  接收方式：GET & POST & REQUEST 
 * @param int $default	默认值
 * @param int $min	最小值
 * @param int $max	最大值
 * @createtime 2009-04-13 17:32
 */
function getReqInt($name, $method = 'REQUEST', $default = 0, $min = false, $max = false)
{
	$method = strtoupper($method);
	switch ($method)
	{
		case 'POST':
			$variable = $_POST;
			break;
		case 'GET':
			$variable = $_GET;
			break;
		default:
			$variable = $_REQUEST;
			break;
	}
	if(!isset($variable[$name]) || $variable[$name] == '')
	{
		return $default ;
	}
	$value = intval($variable[$name]) ;
	if($min !== false)
	{
		$value = max($value, $min);
	}
	if($max !== false)
	{
		$value = min($value, $max);
	}
	return $value;
}
/**
 * @name getReqHtml
 * @desc 接收用户输入值-带html,需要php tidy支持
 * @author 曹晓冬
 * @param string $name	变量的名称
 * @param string $method	接收方式：GET & POST & REQUEST
 * @param string $default	默认值
 * @param string $type 		格式化的类型,目前支持reply及content.详细请参见HtmlFilter.class.php
 */
function getReqHtml($name, $method = 'REQUEST', $default = '', $type = 'content')
{
	$method = strtoupper($method);
	switch ($method)
	{
		case 'POST':
			$variable = $_POST;
			break;
		case 'GET':
			$variable = $_GET;
			break;
		default:
			$variable = $_REQUEST;
			break;
	}
	if(!isset($variable[$name]))
	{
		return $default ;
	}
	$htmlfilter_obj = new HtmlFilter($type);
	$mytidy = $htmlfilter_obj->repair($variable[$name]);
	return $htmlfilter_obj->filter($mytidy);
}
/**
 * @name getReqNoHtml
 * @desc 接收用户输入值-不带Html
 * @param string $name	变量的名称
 * @param string $method	接收方式：GET & POST & REQUEST
 * @param string $default	默认值
 */
function getReqNoHtml($name, $method = 'REQUEST', $default = '')
{
	$method = strtoupper($method);
	switch ($method)
	{
		case 'POST':
			$variable = $_POST;
			break;
		case 'GET':
			$variable = $_GET;
			break;
		default:
			$variable = $_REQUEST;
			break;
	}
	if(!isset($variable[$name]))
	{
		return $default ;
	}
	return trim(strip_tags($variable[$name]));
}

/**
 * 以UTF-8格式输出标准的网页，适合于输出简单提示之类的页面，只是 echo 出一个标准HTML页面
 * @param String $content 网页的主体内容
 * @param String $title 网页标题，默认是：YOKA时尚网_你的生活 你的时尚。注意：分段标题使用“_”分割
 * @author wangyi yz124s@hotmail.com
 */
function printHtml($content, $title='YOKA时尚网_你的生活 你的时尚')
{
	$html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>'.$title.'</title>
</head>
<body>'.$content.'</body></html>';
	echo $html;
}

/**
 * 获取代码的显示名词，适合于Array[key=>name]用在模版中输出代码的名词，如：<{$data.status|getCodeLabel:"array_status"}>，显示出数据状态的名称。
 * @param String $code 代码值
 * @param String $code_name 代码数组的名称，如：array_status，则需要存在变量$array_status
 * @author wangyi yz124s@hotmail.com
 * @return String 
 */
function getCodeLabel($code, $code_name)
{
	if ($code_name)
	{
		global $$code_name;
		$data = $$code_name;
		if ($data && isset($data[$code]))
		{
			return $data[$code];
		}
	}
	return $code;
}
?>