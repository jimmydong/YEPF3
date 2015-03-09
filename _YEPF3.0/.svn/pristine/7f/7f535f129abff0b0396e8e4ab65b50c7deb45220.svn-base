<?php
/**
 * @name ParseEnvConf
 * @desc 解析Apache环境配置变量类
 * @author 曹晓冬(caotian2000@sohu.com)
 * @createtime 2009-08-20 12:47
 * @example 
 * ParseEnvConf::file('\YOKA\CONF\YOKA-ENV.conf');
 */
namespace ext;

class ParseEnvConf
{
	/**
	 * @name file
	 * @desc 将配置文件解析到全局变量SERVER中
	 * @param mixed $filename  要解析的文件名 string | array
	 */
	public static function file($filename)
	{
		if(is_array($filename))
		{
			foreach($filename as $v)
			{
				return self::file($v);
			}
		}else 
		{
			$file = file($filename);
			foreach ($file as $v)
			{
				$v = trim($v);
				if($v[0] == '#' || $v == '')
				{
					continue;
				}
				list($variables, $value) = explode('=', preg_replace(array('/\s+/', '/[\'|\"]/'), array('=', ''), trim(substr($v, 6))));
				$_SERVER[$variables] = $value;
			}
		}
	}
}
?>