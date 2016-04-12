<?php
/**
 * @name CommCache.class.php
 * @desc 本地缓存操作类.使用memagent作为代理访问memcached
 * @author 曹晓冬
 * @example
 * 声明一个服务号为1001,前缀为gift的Memagent代理实例
 * $commcache_obj = CommCache::getInstance('1001', 'gift');
 * $value = $commcache_obj->get($key = 'testKey');
 *
 * @createtime 2009-08-14 13:39
 */
namespace yoka;
use \yoka\Debug as Debug;
class CommCache implements \yoka\CacheInterface
{
	/**
	 * 前缀字符串
	 * @var string
	 */
	private $prefix = '' ;
	/**
	 * 服务号
	 * @var string | int
	 */
	private $serviceid = '' ;
	/**
	 * 服务器IP
	 * @var string
	 */
	private $host;
	/**
	 * 服务端口号
	 * @var int
	 */
	private $port;
	/**
	 * 缓存对象
	 * @var object
	 */
	private $cache ;
	/**
	 * 是否需要到备机去取数据
	 * @var bool
	 */
	private $magent;
	/**
	 * 是否开启读取备机功能
	 * @var bool
	 */
	private $startbackup = false;
	/**
	 * 读备机频率的比率,0为每次都读取
	 */
	const BACKUP_RATIO = 0;
	/**
	 * 实例化对象数组
	 * @var array
	 */
	static $instance = array();
	/**
	 * 本地缓存操作类构造函数
	 *
	 */
	private function __construct($serviceid, $prefix, $magent)
	{
		$this->serviceid = $serviceid;
		$this->prefix = $prefix;
		$this->magent = $magent;
		$this->connect();
	}
	/**
	 * @name connect
	 * @desc 建立一个连接永久连接
	 * @param bool $backup	是否连接备份服务器
	 */
	private function connect($backup = false)
	{
		if(false === $backup)
		{
			$this->host = $_SERVER['LOCAL_MEM_IP'] ;
			$this->port = $this->magent === true ? $_SERVER['LOCAL_MAGENT_PORT'] : $_SERVER['LOCAL_MEM_PORT'];
		}else
		{
			$this->host = $_SERVER['MAGENT_BACKUP_IP'] ;
			$this->port = $_SERVER['MAGENT_BACKUP_PORT'] ;
			$this->startbackup = false ;
		}
		if(empty($this->host) || empty($this->port))
		{
			return false;
		}
		$this->cache = new Memcache();
		return $this->cache->pconnect($this->host, $this->port);
	}
	/**
	 * @name getInstance
	 * @desc 单件模式调用LocalCache类入口
	 * @param string $serviceid			业务编号
	 * @param string $prefix			缓存前缀
	 * @param bool $magent				是否使用magent,默认为使用
	 */
	public function getInstance($serviceid, $prefix, $magent = true)
	{
		$key = $serviceid . '|' . $prefix;
    	if(!isset($obj[$key]))
    	{

			$obj[$key] = new self($serviceid, $prefix, $magent);
			self::$instance = $obj;
		}
    	return $obj[$key];
	}

	/**
	 * 按步长$step递增
	 *
	 * @param string $cacheKey
	 * @param int $step 步长：默认为1
	 * @return int | Boolean 失败返回 false；成功返回新值
	 */
	public function increment($cacheKey, $step = 1) {
		$begin_microtime = Debug::getTime();
        $cacheKey = $this->getKey($cacheKey);
        if(empty($cacheKey)) return false;
        $returnValue = $this->cache->increment($cacheKey, $step);
        Debug::cache($this->host. ':' .$this->port, $cacheKey, Debug::getTime() - $begin_microtime, $returnValue, 'increment');
	    return $returnValue;
	}

    /**
     * 按步长$step递减
     *
     * @param string $cacheKey
     * @param int $step 步长：默认为1
     * @return int | Boolean 失败返回 false；成功返回新值
     */
    public function decrement($cacheKey, $step = 1) {
        $begin_microtime = Debug::getTime();
        $cacheKey = $this->getKey($cacheKey);
        if(empty($cacheKey)) return false;
        $returnValue = $this->cache->decrement($cacheKey, $step);
        Debug::cache($this->host. ':' .$this->port, $cacheKey, Debug::getTime() - $begin_microtime, $returnValue, 'decrement');
        return $returnValue;
    }

    /**
     * @name add
     * @desc 将数据存入缓存。只有键值不存在时，才成功；否则失败。该方法提供原子操作！
     * @param string $cacheKey
     * @param mixed $cacheValue
     * @param int $lifetime
     * @return Boolean
     */
    public function add($cacheKey, $cacheValue, $lifetime = 0) {
    	$begin_microtime = Debug::getTime();
        $cacheKey = $this->getKey($cacheKey);
        if(empty($cacheKey)) return false;
        $returnValue = $this->cache->add($cacheKey, $cacheValue, 0, $lifetime);
        Debug::cache($this->host. ':' .$this->port, $cacheKey, Debug::getTime() - $begin_microtime, $returnValue, 'add');
        return $returnValue;
    }
   /**
	 * @name set
	 * @desc 将数据插入缓冲中
	 * @param string $cacheKey
	 * @param mixed $cacheValue
	 * @return boolean
	 * @access public
	 *
	 */
	public function set($cacheKey, $cacheValue, $lifetime = 0)
	{
		$begin_microtime = Debug::getTime();
    	$cacheKey = $this->getKey($cacheKey);
    	if(empty($cacheKey)) return false;
    	$returnValue = $this->cache->set($cacheKey, $cacheValue, 0, $lifetime);
    	Debug::cache($this->host. ':' .$this->port, $cacheKey, Debug::getTime() - $begin_microtime, $returnValue, 'set');
        return $returnValue;
	}
    /**
     * @name clear
     * @desc 将数据在缓冲中清除
     * @param string or array $cacheKey
     * @return bool
     * @access public
     **/
	public function clear($cacheKey)
	{
		$begin_microtime = Debug::getTime();
    	$cacheKey = $this->getKey($cacheKey);
    	if(empty($cacheKey)) return false;
    	$returnValue = $this->cache->delete($cacheKey);
        Debug::cache($this->host. ':' .$this->port, $cacheKey, Debug::getTime() - $begin_microtime, $returnValue, 'clear');
        return $returnValue;
	}
    /**
	 * @name get
	 * @desc 从缓冲中取得数据
	 * @param mixed $cacheKey
	 * @param bool $backup
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
        if($cacheValue !== false){
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
            else
            {
            	$returnValue = $cacheValue;
            }
        }
        Debug::cache($this->host. ':' .$this->port, $cacheKey, Debug::getTime() - $begin_microtime, $returnValue, 'get');
        //主服务器未获取到相关数据,尝试备份服务器获取
        if(empty($returnValue) && true === $this->magent && true === $this->startbackup)
        {
        	if(self::BACKUP_RATIO == 0 || (self::BACKUP_RATIO > 0 && time() % self::BACKUP_RATIO == 0))
        	{
	        	if($this->connect($backup = true))
	        	{
	        		$returnValue = $this->get($cacheKey);
	        		if(!empty($returnValue) && $this->connect($backup = false))
	        		{
	        			$this->set($cacheKey, $returnValue);
	        		}
	        	}
        	}
        }
        return $returnValue;
	}
   /**
     * @name getKey
     * @desc 格式化类中所需Key
     * @param string $key
     * @return string $key
     * @access private
     *
     **/
	private function getKey($key)
	{
		$pre = $this->serviceid . '|' . $this->prefix;
    	if(!empty($pre))
    	{
    		if(is_array($key))
    		{
    			foreach($key as $k => $v)
    			{
    				$key[$k] = $pre . "_" . $v;
    			}
    			return $key;
    		}
    		else
    		{
    			return $pre."_".$key;
    		}
    	}
    	return "";
	}
}
?>