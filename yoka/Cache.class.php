<?php
/**
 * @name Cache.class.php
 * @desc 通用缓冲机制控制类,目前只支持Memcached内存缓存
 * @author 曹晓冬
 * @createtime 2008-09-10 02:32
 * @updatetime 2009-03-20 06:12
 * @usage：
 * $cache_obj = Cache::getInstance('default');  //default为cache.config.php中$CACHE['memcached']配置的服务名称
 * $cache_obj->get($key); //$key为唯一的字符串标识
 * @update 2014-09-16 by jimmy.dong@gmail.com  自动识别memcache插件类型，避免memcache与memcached冲突
 */
namespace yoka;
use yoka\Debug as Debug;
use Memcached;

class Cache implements \yoka\CacheInterface
{
	/* Memcache扩展的类型： Memcache or Memcached */
	private $memcacheType;
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
 * Error define
 */
private $error_define = array(
'00'=>'MEMCACHED_SUCCESS',
'01'=>'MEMCACHED_FAILURE',
'02'=>'MEMCACHED_HOST_LOOKUP_FAILURE // getaddrinfo() and getnameinfo() only',
'03'=>'MEMCACHED_CONNECTION_FAILURE',
'04'=>'MEMCACHED_CONNECTION_BIND_FAILURE // DEPRECATED see MEMCACHED_HOST_LOOKUP_FAILURE',
'05'=>'MEMCACHED_WRITE_FAILURE',
'06'=>'MEMCACHED_READ_FAILURE',
'07'=>'MEMCACHED_UNKNOWN_READ_FAILURE',
'08'=>'MEMCACHED_PROTOCOL_ERROR',
'09'=>'MEMCACHED_CLIENT_ERROR',
'10'=>'MEMCACHED_SERVER_ERROR // Server returns "SERVER_ERROR"',
'11'=>'MEMCACHED_ERROR // Server returns "ERROR"',
'12'=>'MEMCACHED_DATA_EXISTS',
'13'=>'MEMCACHED_DATA_DOES_NOT_EXIST',
'14'=>'MEMCACHED_NOTSTORED',
'15'=>'MEMCACHED_STORED',
'16'=>'MEMCACHED_NOTFOUND',
'17'=>'MEMCACHED_MEMORY_ALLOCATION_FAILURE',
'18'=>'MEMCACHED_PARTIAL_READ',
'19'=>'MEMCACHED_SOME_ERRORS',
'20'=>'MEMCACHED_NO_SERVERS',
'21'=>'MEMCACHED_END',
'22'=>'MEMCACHED_DELETED',
'23'=>'MEMCACHED_VALUE',
'24'=>'MEMCACHED_STAT',
'25'=>'MEMCACHED_ITEM',
'26'=>'MEMCACHED_ERRNO',
'27'=>'MEMCACHED_FAIL_UNIX_SOCKET // DEPRECATED',
'28'=>'MEMCACHED_NOT_SUPPORTED',
'29'=>'MEMCACHED_NO_KEY_PROVIDED /* Deprecated. Use MEMCACHED_BAD_KEY_PROVIDED! */',
'30'=>'MEMCACHED_FETCH_NOTFINISHED',
'31'=>'MEMCACHED_TIMEOUT',
'32'=>'MEMCACHED_BUFFERED',
'33'=>'MEMCACHED_BAD_KEY_PROVIDED',
'34'=>'MEMCACHED_INVALID_HOST_PROTOCOL',
'35'=>'MEMCACHED_SERVER_MARKED_DEAD',
'36'=>'MEMCACHED_UNKNOWN_STAT_KEY',
'37'=>'MEMCACHED_E2BIG',
'38'=>'MEMCACHED_INVALID_ARGUMENTS',
'39'=>'MEMCACHED_KEY_TOO_BIG',
'40'=>'MEMCACHED_AUTH_PROBLEM',
'41'=>'MEMCACHED_AUTH_FAILURE',
'42'=>'MEMCACHED_AUTH_CONTINUE',
'43'=>'MEMCACHED_PARSE_ERROR',
'44'=>'MEMCACHED_PARSE_USER_ERROR',
'45'=>'MEMCACHED_DEPRECATED',
'46'=>'MEMCACHED_IN_PROGRESS',
'47'=>'MEMCACHED_SERVER_TEMPORARILY_DISABLED',
'48'=>'MEMCACHED_SERVER_MEMORY_ALLOCATION_FAILURE',
'49'=>'MEMCACHED_MAXIMUM_RETURN /* Always add new error code before */',
'11'=>'MEMCACHED_CONNECTION_SOCKET_CREATE_FAILURE MEMCACHED_ERROR'
);
    /**
	 * @name __construct
	 * @desc 构造函数
	 * @param void
	 * @return object instance of Cache
	 * @access protected
	 *
	 */
	
    private function __construct($item = '', $serverList = array(), $type = 'memcached')
    {
    	if(class_exists('Memcached') && method_exists('Memcached', 'setOption')) $this->memcacheType = 'Memcached';
    	else $this->memcacheType = 'Memcache';
    	
    	if('memcached' == $type)
    	{
			if($this->memcacheType == 'Memcache') $this->cache = new \yoka\Memcached();
			else $this->cache = new Memcached();
			if(!empty($serverList))
			{
				foreach($serverList as $v)
				{
			           $is_sucess = $this->cache->addServer($v['host'],$v['port']);
			           if($is_sucess === false)
			                         Debug::log('memcacheConnectError','ip:'.$v['host'].":".$v['port'],__FILE__.':'.__LINE__);  
			           $this->serverlist[] = array('ip' => $v['host'], 'port' => $v['port'], 'is_sucess'=>$is_sucess);				
				}
			}
    	}
		$this->prefix = $item;
    }
    /**
     * @name getInstance
     * @desc 单件模式调用Cache类入口
     * @param string $item
     * @return object instance of Cache
     * @access public
     **/
    public static function getInstance($item)
    {
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
				$key = $item;
			}
			$obj[$item] = new Cache($item, $list);
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
     * @param int $lifetime
     * @return Boolean
     */
    public function add($cacheKey, $cacheValue, $lifetime = 0) {
    	$begin_microtime = Debug::getTime();
        $cacheKey = $this->getKey($cacheKey);
        if(empty($cacheKey)) return false;
        $re = $this->cache->add($cacheKey, $cacheValue, 0, $lifetime);
        Debug::cache($this->serverlist, $cacheKey, Debug::getTime() - $begin_microtime, 'add', $re);
	return $re;
    }
    /**
	 * @name set
	 * @desc 将数据插入缓冲中
	 * @param string $cacheKey 字符串标识
	 * @param mixed $cacheValue 字符串对应的值
	 * @param int $lifetime 缓存的生命周期
	 * @return boolean 
	 * @access public
	 *
	 */
    public function set($cacheKey, $cacheValue, $lifetime = 0)
    {
    	$begin_microtime = Debug::getTime();
    	$cacheKey = $this->getKey($cacheKey);
    	if(empty($cacheKey)) return false;
        if($this->cache->set($cacheKey, $cacheValue, 0, $lifetime)){
        	Debug::cache($this->serverlist, $cacheKey, Debug::getTime() - $begin_microtime, 'set', true);
            return true;
        }
        Debug::cache($this->serverlist, $cacheKey, Debug::getTime() - $begin_microtime, 'set', 'false: ' . $this->error_define[$this->cache->getResultCode()]);
        return false;
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
    	$cacheKey = $this->getKey($cacheKey);
    	if(empty($cacheKey)) return false;
        if($this->cache->delete($cacheKey)){
            return true;
        }
        return false;
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
    	$returnValue = "";
		$key = $this->getKey($cacheKey);
		if(empty($key)) return false;
		$begin_microtime = Debug::getTime();
        $cacheValue = $this->cache->get($key);
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
        Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'get', $returnValue);
        return $returnValue;
    }
	
	/**
	 * 计数器类的数字自增长
	 */
	public function increment($key, $value)
	{
		$key = $this->getKey($key);
		return $this->cache->increment($key, $value);
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
    				$key[$k] = $this->prefix."_".$v;
    			}
    			return $key;
    		}
    		else return $this->prefix."_".$key;
    	}
    	return "";
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
    	return $this->cache->$method($args[0],$args[1],$args[2]);
    }
}
?>
