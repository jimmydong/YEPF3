<?php
/**
 * 工具类：快存
 * Enter description here ...
 * @author "jimmy.dong@gmail.com"
 *
 */
namespace tools;
class QuickCache{
	static $default_item = 'default';
	private static $prefix = "QuickCache_";
	private static $buffer;
	
	/**
	 * 设定缺省项目
	 */
	public static function setDefault($item){
		self::$default_item = $item;
	}
	
	/**
	 * 快速存储，返回一个随机生成的key
	 * Enter description here ...
	 * @param mixed $data
	 */
	public static function add($data){
		$m = \yoka\Cache::getInstance(self::$default_item);
		//$key =  uniqid();
		$key = substr(md5(json_encode($data)),4,8);
		self::$buffer[$key] = $data;
		//取消时间限制 if($m->set(self::$prefix . $key, $data, SiteCacheTime)) return $key;
		if ($m->set ( self::$prefix . $key, $data, SiteCacheTime )) {
			return $key;
		} else {
			// 添加数据库支持，bandry
			$cachedata = new \DAO\CacheData();
			$key = $cachedata->set('', $data, SiteCacheTime);
			return $key;
			//return false;
		}
	}

	/**
	 * 用Key读取快存内容
	 * Enter description here ...
	 * @param string $key
	 */
	public static function get($key){
		if(!$key) return false;
		if(self::$buffer[$key]) return self::$buffer[$key];
		$m = \yoka\Cache::getInstance(self::$default_item);
		$data = $m->get(self::$prefix . $key);
		if ($data) {
			return $data;
		} else {
			// 添加数据库支持，bandry
			$cachedata = new \DAO\CacheData();
			$data = $cachedata->get($key);
			return $data;
		}
	}

	/**
	 * 用Key更新快存内容
	 * @param string $key
	 * @param mixed $data
	 * @return boolean
	 */
	public static function update($key, $data){
		$m = \yoka\Cache::getInstance(self::$default_item);
		self::$buffer[$key] = $data;
		if($m->set(self::$prefix . $key, $data, SiteCacheTime)) return true;
		else {
			$cachedata = new \DAO\CacheData();
			$cachedata->set($key, $data, SiteCacheTime);
			return false;
		}
	}

	/**
	 * 保存当前地址
	 * _c,_a,page 自动保存，其他参数需在数组中指定
	 * @param obj $request  框架request对象 OR 参数数组
	 * @param array $arr	需要保存的参数（配合request对象，默认只传递_c,_a,page）
	 * @param boolean $strict 是否保存空值的参数
	 */
	public static function saveBackUrl($request, $arr = array(), $strict = false){
		if(is_array($request)){
			$re = $request;
		}
		else{
			$re['_c'] = $request->_c;
			$re['_a'] = $request->_a;
			$re['page'] = $request->page;
			if(is_array($arr))foreach($arr as $v){
				if($strict == false && $request->$v === '')continue;
				$re[$v] = $request->$v;
			}
		}
		//\yoka\Debug::log('quickUrl', $re);
		return self::add($re);
	}

	/**
	 * 快速读取保存的地址
	 * Enter description here ...
	 * @param string $key
	 * @param boolean $array_format 返回地址还是原始数组
	 */
	public static function getBackUrl($key, $array_format = false){
		if(!$key)return false;
		$t = self::get($key);
//		if(!isset($t['t']))$t['t'] = time();
//		\Debug::log('back_url', $t);
		if($array_format) return $t;
		else return template_url_encode($t);
	}

}
?>
