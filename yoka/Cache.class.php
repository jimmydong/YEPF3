<?php
/**
 * @name Cache.class.php
 * @desc 通用缓冲机制控制类,目前只支持Memcached内存缓存
 * @usage：
 * 	$cache_obj = Cache::getInstance('default');  //default为cache.config.php中$CACHE['memcached']配置的服务名称
 * 	$cache_obj->get($key); //$key为唯一的字符串标识
 * @update 2014-09-16 by jimmy.dong@gmail.com  自动识别memcache插件类型，避免memcache与memcached冲突
 * @update 2017-02-26 by jimmy.dong@gmail.com  支持后备服务器设定
 */
namespace yoka;
use yoka\Debug as Debug;
use Memcached;

class Cache implements \yoka\CacheInterface
{
	/* Memcache扩展的类型： Memcache or Memcached */
	private $memcacheType;
	
	/**
	 * 默认的连接项目
	 */
	static $default_item = 'default';
	
	/**
	 * 默认缓存时间(1个月)
	 * 
	 * 【注意】 预定义 SiteCacheTime 优先覆盖此设定
	 * 
	 */
	static $default_lifetime = 2592000;
	
	/**
	 * 实例化数组
	 * @var array
	 */
	static $instance = array();
	/**
	 * 缓存前缀名
	 * @var string
	 */
	private $prefix = '';
	/**
	 * 服务器列表
	 * @var array
	 */
	private $serverlist = array(); 
	/**
	 * 缓存访问对象
	 * @var object
	 */
	private $cache; 
    /**
	 * @name __construct
	 * @desc 构造函数
	 * @param void
	 * @return \yoka\Cache
	 * @access protected
	 *
	 */
	
    private function __construct($item = '', $serverList = array(), $backupList = array(), $type = 'memcached')
    {
    	if(class_exists('Memcached') && method_exists('Memcached', 'setOption')) $this->memcacheType = 'Memcached';
    	else $this->memcacheType = 'Memcache';
    	
    	if('memcached' == $type)
    	{
			if($this->memcacheType == 'Memcache') $this->cache = new \yoka\Memcached();
			else $this->cache = new Memcached();
			//主力服务器
			if(!empty($serverList))
			{
				foreach($serverList as $v)
				{
					$this->cache->addServer($v['host'],$v['port']);
					$this->serverlist[] = array('ip' => $v['host'], 'port' => $v['port'], 'is_sucess'=>$is_sucess);				
				}
			}
			//后备服务器：只有主力服务器全部添加失败时使用。集群设计应充分考虑数据一致性！
			if(!empty($backupList) && !$this->cache->set('Y_CHECK_SERVER_ALIVE',1))
			{
				\yoka\Debug::log("Cache Warnning", "server is down, using backup now!");
				if($this->memcacheType == 'Memcache') $this->cache = new \yoka\Memcached();
				else $this->cache = new Memcached();
				$this->serverlist = array();
				foreach($backupList as $v)
				{
					$this->cache->addServer($v['host'],$v['port']);
					$this->serverlist[] = array('ip' => $v['host'], 'port' => $v['port'], 'is_sucess'=>$is_sucess);
				}
			}
    	}
		$this->prefix = $item;
    }
    
    /**
     * 设置缺省项目
     * @param unknown $item
     */
    public static function setDefault($item){
    	self::$default_item = $item;
    }
    
    /**
     * @name getInstance
     * @desc 单件模式调用Cache类入口
     * @param string $item
     * @return Cache
     **/
    public static function getInstance($item = null)
    {
    	if($item === null || $item == 'default') $item = self::$default_item;
    	global $CACHE;
    	$obj = Cache::$instance;
    	if(!isset($obj[$item]))
    	{
    		$key = "";
    		$list = array();
			if(isset($CACHE['memcached'][$item]))
			{
				$config = $CACHE['memcached'][$item];
				$list = $config['server'];
				$backup = $config['backup'];
				$key = $item;
			}else{
				\yoka\Debug::log('Cache Error','无配置项:' . $item);
				return false;
			}
			$obj[$item] = new Cache($item, $list, $backup);
			Cache::$instance = $obj;
		}
    	return $obj[$item];
    }
    /**
     * 将数据存入缓存。只有键值不存在时，才成功；否则失败。
     * 该方法提供原子操作！
     *
     * @param string $cacheKey
     * @param mixed $cacheValue
     * @param int $lifetime 缓存的生命周期(缺省值：SiteCacheTime | self::$default_lifetime)
     * 【注意】 设置 $lifetime=0 永久存储 
     * @return Boolean
     */
    public function add($cacheKey, $cacheValue, $lifetime = null) {
    	if(empty($cacheKey)) return false;
        $cacheKey = $this->getKey($cacheKey);
        if($lifetime === null){
        	if(defined('SiteCacheTime')) $lifetime = SiteCacheTime;
        	else $lifetime = self::$default_lifetime;
        }
    	$begin_microtime = Debug::getTime();
        $re = $this->cache->add($cacheKey, $cacheValue, $lifetime);
        Debug::cache($this->_getServer(), $cacheKey, Debug::getTime() - $begin_microtime, 'add', $re);
        return $re;
    }
    /**
	 * @name set
	 * @desc 将数据插入缓冲中
	 * @param string $cacheKey 字符串标识
	 * @param mixed $cacheValue 字符串对应的值
	 * @param int $lifetime 缓存的生命周期(缺省值：SiteCacheTime | self::$default_lifetime)
	 * 【注意】 设置 $lifetime=0 永久存储 
	 * @return boolean 
	 * @access public
	 *
	 */
    public function set($cacheKey, $cacheValue, $lifetime = null)
    {
    	if(empty($cacheKey)) return false;
        $cacheKey = $this->getKey($cacheKey);
        if($lifetime === null){
        	if(defined('SiteCacheTime')) $lifetime = SiteCacheTime;
        	else $lifetime = self::$default_lifetime;
        }elseif($lifetime > 2592000){
        	//补丁：部分环境大于此数值后无法保存。 2592000 = 3600*24*30 = 30天 ！！
        	$lifetime = 2592000;
        }
    	$begin_microtime = Debug::getTime();
    	if($this->cache->set($cacheKey, $cacheValue, $lifetime)){
        	Debug::cache($this->_getServer(), $cacheKey, Debug::getTime() - $begin_microtime, 'set', true);
            return true;
        }
        Debug::cache($this->_getServer(), $cacheKey, Debug::getTime() - $begin_microtime, 'set', false);
        return false;
    }
    /**
     * 批量读取
     * @param array $keys
     */
    public function getMulti($keys){
    	return $this->get($keys);
    }
    /**
     * 批量设置
     * @param array $arr
     * @param number $expiration
     */
    public function setMulti($arr, $expiration = 0){
    	$begin_microtime = Debug::getTime();
    	if(!is_array($arr)){
    		Debug::cache($this->_getServer(), $arr, Debug::getTime() - $begin_microtime, 'setMulti', false);
        	return false;
    	}
    	foreach($arr as $k=>$v){
    		$key = $this->getKey($k);
    		$new[$key] = $v;
    	}
    	if($this->memcacheType == 'Memcached') $this->cache->setMulti($new, $expiration);
    	else foreach($new as $k=>$v) $this->cache->set($k,$v,$expiration);
    	Debug::cache($this->_getServer(), $arr, Debug::getTime() - $begin_microtime, 'setMulti', true);
    	return true;
    }
    /**
     * @name clear
     * @desc 将数据在缓冲中清除
     * @param string $cacheKey 要清除的字符串
     * @return bool
     * @access public
     **/
    public function clear($cacheKey)
    {
    	if(empty($cacheKey)) return false;
        $cacheKey = $this->getKey($cacheKey);
    	if($this->cache->delete($cacheKey)){
        	Debug::cache($this->_getServer(), $cacheKey, Debug::getTime() - $begin_microtime, 'del', true);
        	return true;
        }
        Debug::cache($this->_getServer(), $cacheKey, Debug::getTime() - $begin_microtime, 'del', false);
        return false;
    }
    /**
     * Cache::clear()的别名函数
     */
    public function del($cacheKey){
    	return $this->clear($cacheKey);
    }
    /**
	 * @name get
	 * @desc 从缓冲中取得数据
	 * @param string $cacheKey 获得指定字符串的值
	 * @return mixed $cacheValue
	 * @access public
	 *
	 */
    public function get($cacheKey)
    {
    	if(empty($cacheKey)) return false;
		$returnValue = null;
		$key = $this->getKey($cacheKey);
		$begin_microtime = Debug::getTime();
        if(is_array($key) && $this->memcacheType == 'Memcached') $cacheValue = $this->cache->getMulti($key);
        else $cacheValue = $this->cache->get($key);
        if($cacheValue !== FALSE){
        	if(is_array($cacheKey))
        	{
        		$returnValue = array();
        		/*---这样逻辑是否足够好,需要考虑,是否循环结果,有时间改动 by caoxd ---*/
        		foreach($cacheKey as $value)
        		{
        			if(isset($cacheValue[$this->getKey($value)]))
        			{
        				$returnValue[$value] = $cacheValue[$this->getKey($value)];
        			}
        		}
        	}
            else $returnValue = $cacheValue;
        }
        Debug::cache($this->_getServer(), $key, Debug::getTime() - $begin_microtime, 'get', $returnValue?:'');
        return $returnValue;
    }
	
	/**
	 * 计数器类的数字自增长
	 * 【key不存在或不为整数时，自动设置为$value】
	 * @param string $key
	 * @param int $value
	 * @return int
	 */
	public function increment($key, $value = 1)
	{
		$value = intval($value); //只允许以整数为步长
		if(! $value) return false;
		if(empty($key)) return false;
		$key = $this->getKey($key);
		$begin_microtime = Debug::getTime();
        $re = $this->cache->increment($key, $value); //如果key不存在或不是整数，会返回false
        if($re === false){
        	if( $this->cache->set($key, $value) ) $re = $value;
        	else $re = false;
        }
		Debug::cache($this->_getServer(), $key, Debug::getTime() - $begin_microtime, 'increment', $re);
        return $re;
	}

    /**
     * @name getKey
     * @desc 格式化Cache类中所需key
     * @param string $key
     * @return string $key
     * @access private
     *
     **/
    private function getKey($key)
    {
    	if(!empty($this->prefix))
    	{
    		if(is_array($key))
    		{
    			foreach($key as $k => $v)
    			{
    				$key[$k] = urlencode($this->prefix."_".$v);
    			}
    			return $key;
    		}
    		else return urlencode($this->prefix."_".$key);
    	}else{
    		return urlencode($key);
    	}
    }
    
    /**
	 * @name __destruct
	 * @desc 析构函数
	 * @param void
	 * @return void
	 * @access public
	 *
	 */
    public function __destruct()
    {
        //parent::__destruct();
    }
    
    /**
     * 魔术方法
     */
    public function __call($method, $args)
    {
    	switch(count($args)){
    		case 1 : $re = $this->cache->$method($args[0]);break;	
    		case 2 : $re = $this->cache->$method($args[0],$args[1]);break;	
    		case 3 : $re = $this->cache->$method($args[0],$args[1],$args[2]);break;
    		default: 	$re = $this->cache->$method();break;
    	}
    	
    }
    /**
     * 辅助函数
     */
    public function _getServer(){
    	if(count($this->serverlist) == 1) return $this->serverlist[0]['ip'] . ':' . $this->serverlist[0]['port'];
    	foreach($this->serverlist as $server){
    		$re[] = $server['ip'] . ':' . $server['port'];
    	}
    	return $re;
    }
}
?>