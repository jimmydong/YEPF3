<?php
namespace yoka;
/**
 * 碎片处理类： 
 * 持久化到widget表，数据不丢失
 * 依赖 Cache::getInstance('default');  //可通过 setDefault进行修改
 * 依赖 DB::getInstance('defualt');	//不可修改
 * 
 * 【注意】
 *  在项目中，如果使用Cache::setDefault('xxx')变更缓存，必须使用 Widget::setDefault('xxx')保持一致
 */

class Widget
{
	static $default_item = 'default';
	
	/**
	 * 设置缺省项目
	 * @param string $item
	 */
	public static function setDefault($item){
		self::$default_item = $item;
	}
	
	/**
	 * 获取静态碎片数据。（主要用于模板）
	 * 对于动态方法，是否进行memcache缓冲，由方法内部决定
	 */
	public static function get($type, $param)
	{
		$func_name = 'get_'.$type;
		if(method_exists($this,$func_name)){
			$re = self::$func_name($param);
			return $re;
		}else{
			return 'Error: 找不到对应方法';
		}
	}

    /**
     * 取碎片名称
     */
	public static function getName($key)
	{
		if($key === '') return "Error: 没有KEY";
		$m = Cache::getInstance(self::$default_item);
		$mkey = "Widget_name.{$key}";

		if(SiteCacheLevel && !SiteCacheForceRefresh){
			//优先尝试从memcache取出
			$re = $m->get($mkey);
			if($re) return $re;
		}

		$db = DB::getInstance('default');
		$re = $db->fetchOne("select * from widget where `key`='$key' and del_flag=0");
        $name = $re['name'];
		$m->set($mkey, $name, SiteCacheTime ? SiteCacheTime : 3600 * 4);
		return $name;
	}
	
	/**
	 * 取碎片内容
	 * 【raw的别名函数】
	 * @param unknown $key
	 * @param string $html
	 */
	public static function getRaw($key, $html=false){
		return self::raw($key, $html);
	}
	/**
	 * 默认读取，从widget表中读取碎片信息
	 * @param string $key
	 * @param bool $html 是否HTML格式。默认：Json格式
	 */
	public static function raw($key, $html=false)
	{
		if($key === '') return "Error: 没有KEY";
		$m = Cache::getInstance(self::$default_item);
		$mkey = "Widget_raw.{$key}";

		if(SiteCacheLevel && !SiteCacheForceRefresh){
			//优先尝试从memcache取出
			$re = $m->get($mkey);
			if($re) return $re;
		}
		\yoka\Debug::log('here');
		$db = DB::getInstance('default');
		$re = $db->fetchOne("select * from widget where `key`='$key' and del_flag=0");
		\yoka\Debug::log($html, $re);
		\yoka\Debug::log($html, $re['data']);
		if(!$html)$data = json_decode($re['data'],true);
		else $data = $re['data'];
		\yoka\Debug::log($data);
		$m->set($mkey, $data, SiteCacheTime ? SiteCacheTime : 3600 * 4);
		return $data;
	}
	
	/**
	 * 默认设置，写入widget表（用于程序）
	 * 注意：
	 *  1，数据进行json_encode处理。如果不需要处理，请注意参数 html=1  
	 * 	2，入库是进行addslashes，页面提交数据请使用$request->getUnMagic($value)获取
	 */
	public static function set($key, $data, $name='', $html=false){
		if($key === '') return "Error: 没有KEY";
		$m = Cache::getInstance(self::$default_item);
		$mkey = "Widget_raw.{$key}";
		$db = DB::getInstance('default');
		if(!$html)$ddata = addslashes(json_encode($data, JSON_UNESCAPED_UNICODE));
		else $ddata = addslashes($data);
		
		//自动记录更改
		if(class_exists('\model\SystemDifflog')){
			if($t = $db->fetchOne("select * from widget where `key`='{$key}'")){
				\model\SystemDifflog::log('widget',$t['id'],'data',stripslashes($ddata), $t['data']);
				$db->query("update widget set `key`='$key', `data`='{$ddata}', `name`='$name', `last_update` = now() where `key`='{$key}'");
			}else{
				$db->query("insert into widget set `key`='$key', `data`='{$ddata}', `name`='$name', `last_update` = now()");
				\model\SystemDifflog::log('widget',$db->insertId(),'data',stripslashes($ddata), '');
			}
		}else{
			$db->query("replace into widget set `key`='$key', `data`='{$ddata}', `name`='$name', `last_update` = now()");
		}
		
		//保存到Mecache
		$m->set($mkey, $data, SiteCacheTime ? SiteCacheTime : 3600 * 4);
		return true;		
	}
	
	/**
	 * 强制刷新。注意：仅处理raw方法的缓冲。其他方法需单独编写刷新逻辑。
	 */
	public static function refresh($key){
		if($key === '') return "Error: 没有KEY";
		$m = Cache::getInstance(self::$default_item);
		$mkey = "Widget_raw.{$key}";
		return $m->clear($mkey);
	} 
	
	/**
	 * 示例
	 */
	public static function get_time($param){
		extract($param);
		if($full == true) return date('Y-m-d H:i:s');
		else return time();
	}
	
	public static function db($key){
		$mkey = "Widget_db.{$key}";
		$m = \Cache::getInstance(self::$default_item);
		if(SiteCacheLevel && !SiteCacheForceRefresh){
			//优先尝试从memcache取出
			$re = $m->get($mkey);
			if($re) return $re;
		}
		$db = \DB::getInstance('default');
		switch($key){
			case 'goods_sale':
				$re = $db->fetchAll("select * from {$key} where %_cretia_% order by update_time desc");
				break;
			default:
				break;
		}
		$m->set($mkey, $re, SiteCacheTime ? SiteCacheTime : 3600 * 4);
		return $m;
	}
}
