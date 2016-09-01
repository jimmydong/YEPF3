<?php
namespace yoka;
/**
 * COOKIE操作类
 * @author jimmy.dong@gmail.com
 *
 * 加一层封装，为未来做cookie压缩加密做准备
 *
 */

class Cookie{
		private static $expire = 8640000; 	//默认cookie时间：100天
		private static $domain = 'iyishengyuan.com';
		private static $cookiepath = '/';
		private static $cookiepre = 'YEPFV3';


		private function __construct($config)
		{
			//禁止实例化
		}

		/**
		 * 仅用于特殊域名设定，同时自动修改前缀
		 * 【注意】不建议反复修改。改回默认时前缀与默认值会不同！
		 * @param unknown $config
		 */
		public static function setDomain($domain){
			self::$domain = $domain;
			self::$cookiepre = substr(md5($domain),0,3).'2';
		}
		public static function rawset($name, $val, $expire = '', $cookiepath = '', $domain = '')
		{
			if($expire !== 0){
				if($expire == '') $expire = time() + self::$expire;
				else $expire = time() + intval($expire);
			}
			$cookiepath   = $cookiepath ? $cookiepath : self::$cookiepath;
			$domain = $domain ? $domain : self::$domain;
			\yoka\Debug::log('Cookie::set', "$name, $val, $expire, $cookiepath, $domain");
			$result = setcookie($name, $val, $expire, $cookiepath, $domain);
			$_COOKIE[$name] = $val;
			return $result;
		}
		public static function set($name, $val, $expire = '', $cookiepath = '', $domain = '')
		{
			if($expire !== 0){
				if($expire == '') $expire = time() + self::$expire;
				else $expire = time() + intval($expire);
			}
			$cookiepath   = $cookiepath ? $cookiepath : self::$cookiepath;
			$domain = $domain ? $domain : self::$domain;
			\yoka\Debug::log('Cookie::set', self::$cookiepre.$name . ", $val, $expire, $cookiepath, $domain");
			$result = setcookie(self::$cookiepre.$name, $val, $expire, $cookiepath, $domain);
			$_COOKIE[self::$cookiepre.$name] = $val;
			return $result;
		}

		public static function get($name, $raw = false)
		{
			if($raw) return @$_COOKIE[$name];
			return @$_COOKIE[self::$cookiepre.$name];
		}

		public static function del($name, $raw = false)
		{
			if($raw)$cookie_name = $name;
			else $cookie_name = self::$cookiepre.$name;
			self::rawset($cookie_name, '', -3600);
			$_COOKIE[$cookie_name] = '';
			unset($_COOKIE[$cookie_name]);
		}

		public static function is_set($name)
		{
			return isset($_COOKIE[self::$cookiepre.$name]);
		}

		/**
		 * cookie压缩（加密），仅支持一级数组
		 * @param array $param	内容数组
		 * @param string $key	密钥
		 */
		public static function compress($arr, $key = 'V2'){
			if(!is_array($arr))return false;
			$re = $key;
			foreach($arr as $v){
				$re .= '_|#'.$v;
			}
			$sign = substr(md5($re . '%' . $key), -8);
			$re = urlencode($re) . '_%7C%23' . $sign;
			return $re;
		}

		/**
		 * cookie解压缩（解密）
		 * @param string $str
		 * @param string $key 密钥
		 * @return array|boolean
		 */
		public static function decompress($str, $key = 'V2'){
			$t = explode('_|#', urldecode($str));
			foreach($t as $k=> $v){
				if($k === 0)continue;
				$arr[] = $v;
			}
			$sign = array_pop($arr);
			$re = $key;
			foreach($arr as $v){
				$re .= '_|#'. $v;
			}
			if($sign == substr(md5($re . '%' . $key), -8))return $arr;
			else return false;
		}
}
