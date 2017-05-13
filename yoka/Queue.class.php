<?php
namespace yoka;
/**
 * @name Queue.class.php
 * @desc 队列操作类。目前支持Redis（SSDB）。大平台版本可考虑用rabitmq替换
 * @desc 继承Redis操作，参见：https://github.com/phpredis/phpredis#readme
 * @author jimmy.dong@gmail.com
 * 【注意】SSDB与Redis不完全兼容 http://ssdb.io/docs/zh_cn/redis-to-ssdb.html
 * 	1，del无法删除集合或列表，需要用对应的clear方法
 *  2，部分操作函数名不同
 *  3，部分相同函数名参数含义不同
 *  请谨慎设置：$this->is_ssdb
 * 
 * DEMO:
 	$queue = \yoka\Queue::getInstance('default');
 	$queue->clearQueue('queue_name');
 	$queue->addQueue('queue_name', '111');
 	$queue->addQueue('queue_name', '222');
 	$queue->getsQueueAll('queue_name');
	$queue->getQueue('queue_name');
	$queue->getQueue('queue_name');
 */

class Queue
{
	/**
	 * 实例化数组
	 */
	static $instance = array();
	/**
	 * 是否SSDB
	 */
	static $is_ssdb_default = true;
	protected $is_ssdb;
	/**
	 * 服务器列表
	 */
	protected $serverlist = array(); 
	/**
	 * 缓存访问对象
	 * @var object
	 */
	protected $object;
	/**
	 * 前缀
	 * @var unknown
	 */ 
	protected $prefix;
    /**
	 * @name __construct
	 * @desc 构造函数
	 * @param void
	 * @return object instance
	 * @access protected
	 *
	 */
    protected function __construct($item = 'default', $is_ssdb = null)
    {
    	global $CACHE;
   		$this->prefix = $item;
   		if(isset($CACHE['queue'][$item])){
			$config = $CACHE['queue'][$item];
			$this->serverlist = array('host'=>$config['host'], 'port'=>$config['port']);
			//注意：SSDB驱动与Redis驱动存在差异！
			if($is_ssdb === null) $is_ssdb = self::$is_ssdb_default;
			if($is_ssdb){
				$redis = new SimpleSSDB($config['host'], $config['port']);
			}else{
				$redis = new \Redis();
				$redis->pconnect($config['host'],$config['port']);
			}
			if($config['auth'])$redis->auth($config['auth']);
			$this->object = $redis;
			$this->is_ssdb = $is_ssdb;
		}else{
			$e = new \Exception('找不到配置信息');
			throw $e; 
		}
    }
    /**
     * @name getInstance
     * @desc 单件模式
     * @param string $item
     * @return \yoka\Queue
     * @access public
     **/
    public static function getInstance($item = 'default', $is_ssdb = null)
    {
    	$class = get_called_class();
    	if($class::$instance[$item]) {
    		return self::$instance[$item.'#'.$is_ssdb];
    	}else{
    		$obj = new $class($item, $is_ssdb);
    		if($obj) $class::$instance[$item.'#'.is_ssdb] = $obj;
    		return $obj;
    	}
    }

	/**
	 * SSDB 开启或关闭，默认关闭
	 * @param $flag
	 */
	public function is_ssdb($flag){
		$this->is_ssdb = $flag;
	}
    /**
     * 禁止：清除数据功能
     */
    public function flushAll(){
    	
    }
    /**
     * 禁止：清除数据功能
     */
    public function flushDB(){
    	
    }
    /**
     * SSDB：集群通过配置设定，slaveof禁用
     */
    public function slaveof($host=null, $port=null){
    	if($this->is_ssdb) 	return false; //此函数禁用
    	else return $this->object->slaveof($host, $port);
    } 
    
    /*------------------------------------------------ 队列相关操作 -----------------------------------*/
    
    /**
     * 将数据存入队列
     * @param string $queue_name 队列名称
     * @param mixed $queue_data 队列数据
     * @return Boolean
     */
    public function addQueue($queue_name, $queue_data) {
    	$begin_microtime = Debug::getTime();
        $key = $this->_getkey($queue_name);
        if(empty($key)) return false;
        if($this->is_ssdb)$re = $this->object->qpush_back($key, json_encode($queue_data, JSON_UNESCAPED_UNICODE));
        else $re = $this->object->rPush($key, json_encode($queue_data, JSON_UNESCAPED_UNICODE));
        Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'addQueue', $re);
        return $re;
    }
    /**
     * 从队列取出数据
     * @param string $queue_name
     * @return boolean|mixed
     */
    public function getQueue($queue_name){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($queue_name);
    	if(empty($key)) return false;
    	if($this->is_ssdb)$re = $this->object->qpop_front($key);
    	else $re = $this->object->lPop($key);
    	$re = json_decode($re, true);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'getQueue', $re);
    	return $re;
    }
    /**
     * 返回队列长度
     * @param string $queue_name
     * @return boolean|mixed
     */
    public function sizeQueue($queue_name){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($queue_name);
    	if(empty($key)) return false;
    	if($this->is_ssdb)$re = $this->object->qsize($key);
    	else $re = $this->object->lSize($key);
    	$re = json_decode($re, true);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'sizeQueue', $re);
    	return $re;
    }
    /**
     * 读取队列最新N个元素
     */
    public function getsQueueNew($queue_name, $n){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($queue_name);
    	if(empty($key)) return false;
    	$length = $this->sizeQueue($queue_name);
    	if($length - $n > 0)$start = $length - $n;
    	else $start = 0;
    	if($this->is_ssdb) $re = $this->object->qslice($key, $start, -1);
    	else $re = $this->object->lRange($key, $start, -1);
    	foreach($re as $k=>$v){
    		$re[$k] = json_decode($v, true); 
    	}
    	$re = array_reverse($re);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'getsQueueNew', $re);
    	return $re;
    }
    /**
     * 读取队列最旧N个元素
     */
    public function getsQueueOld($queue_name, $n){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($queue_name);
    	if(empty($key)) return false;
    	if($this->is_ssdb) $re = $this->object->qslice($key, 0, $n-1);
    	else $re = $this->object->lRange($key, 0, $n-1);
    	foreach($re as $k=>$v){
    		$re[$k] = json_decode($v, true); 
    	}
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'getsQueueOld', $re);
    	return $re;
    }
    /**
     * 读取队列全部元素
     */
    public function getsQueueAll($queue_name){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($queue_name);
    	if(empty($key)) return false;
    	if($this->is_ssdb) $re = $this->object->qslice($key, 0, -1);
    	else $re = $this->object->lRange($key, 0, -1);
    	foreach($re as $k=>$v){
    		$re[$k] = json_decode($v, true);
    	}
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'getsQueueAll', $re);
    	return $re;
    }    
    /**
     * 清空队列
     * @param string $queue_name
     * @return boolean
     */
    public function clearQueue($queue_name){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($queue_name);
    	if(empty($key)) return false;
    	//SSDB不支持redis的删除set指令 
    	if($this->is_ssdb) {
    		$re = $this->object->qclear($key);
    	}
    	else{
    		$re = $this->object->del($key);
    		//用phpredis连接ssdb时，只能逐个删除
    		//$l = $this->sizeQueue($queue_name);
	    	//for($i=0;$i<$l;$i++)$this->object->lPop($key);
    	}
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'clearQueue', $re);
    	return $re;
    }
    /**
     * 获取队列详情
     * @param string $queue_name
     * @return boolean|unknown
     */
    public function listQueue($queue_name){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($queue_name);
    	if(empty($key)) return false;
    	$queue_legnth = $this->sizeQueue($queue_name);
    	for($i=0;$i<$queue_legnth;$i++){
    		if($counter++ > 1000)break;
    		if($this->is_ssdb) $re[] = $this->object->qget($key, $i);
    		else $re[] = $this->object->lGet($key, $i);
    	}
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'listQueue', $re);
    	return $re;
    }
    
    /*-------------------------------------- 排序操作(zSet)---------------------------------------------*/

    //迁移至 SortList.class.php
    
    /*-------------------------------------- 基础操作（不推荐直接使用）---------------------------------*/
    /**
     * 读取 - Key/Value模式
     * @param unknown $key
     * @return mixed
     */
    public function get($key){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($key);
    	$t = $this->object->get($key);
    	$re = json_decode($t, true);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'get', $re);
    	return $re;
    }
    /**
     * 设置 - Key/Value模式
     * @param unknown $key
     * @param unknown $data
     * @return unknown
     */
    public function set($key, $data){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($key);
    	$data = json_encode($data, JSON_UNESCAPED_UNICODE);
    	$re = $this->object->set($key, $data);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'set', $re);
    	return $re;
    }
    /**
     * 删除 注意：暂不支持多键值和数组
     * @param string $key
     * @return unknown
     */
    public function delete($key){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($key);
    	$re = $this->object->delete($key);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'delete', $re);
    	return $re;
    }
    /**
     * delete别名
     * @param unknown $key
     * @return unknown
     */
    public function del($key){
    	return $this->delete($key);
    }
    /**
     * 增量操作
     * @param string $key
     */
    public function incr($key){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($key);
    	$re = $this->object->incr($key);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'incr', $re);
    	return $re;
    }
    /**
     * 增量函数
     * @param string $key
     * @param int $number
     */
    public function incrBy($key, $number = 1){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($key);
    	$re = $this->object->incrBy($key, $number);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'incrBy', $re);
    	return $re;
    }
    /**
     * 浮点增量函数
     * @param string $key
     * @param float $float
     */
    public function incrByFloat($key, $float = 1){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($key);
    	$re = $this->object->incrBy($key, $float);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'incrByFloat', $re);
    	return $re;
    }
    /**
     * incrBy的别名函数
     */
    public function increment($key, $number=1){
    	return $this->incr($key, $number);
    }
    /**
     * 创建一个锁
     * @param string $lock_name
     * @return boolean
     */
    public function getLock($lock_name){
    	$key = 'Lock_' . $lock_name;
    	$t = $this->incr($key);
   		if($t == 1){
   			return true;
   		}elseif($t > 1){
   			$this->incrBy($key, -1); //还原锁
   			return false;
   		}else{
   			$re = $this->incrBy($key, -1); //还原锁
   			//异常：锁小于0，通常因为多次freeLock导致。可尝试freeLock使用forece参数
   			throw new \Exception('锁数值异常：' . $key . ', ' . $re);
   			return false;
   		}
    }
    /**
     * 释放一个锁
     * @param string $lock_name
     * @param boolean $force 强制释放锁
     * @return boolean
     */
    public function freeLock($lock_name, $force = false){
    	$key = 'Lock_' . $lock_name;
    	if($force == true){
    		//强制释放
    		$this->delete($key);
    		return true;
    	}
    	$t = $this->incrBy($key, -1);
    	if($t != 0){
    		return false;
    	}else{
    		$this->delete($key);
    		return true;
    	}
    }
    
    
    /**
     * @name _getkey
     * @desc 格式化所需key
     * @param mix $key  string | []
     * @return string $key
     * @access protected
     *
     **/
    protected function _getkey($key)
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
     * @name _unkey
     * @desc 还原格式化前的key
     * 【注意】 仅简单切除前缀长度，并未判断前缀是否一致！
     * @param mix $key  string | []
     * @return string $key
     * @access protected
     *
     **/
    protected function _unkey($key)
    {
    	if(!empty($this->prefix))
    	{
    		if(is_array($key))
    		{
    			foreach($key as $k => $v)
    			{
    				$key[$k] = substr($v, strlen($this->prefix."_"));
    			}
    			return $key;
    		}
    		else return substr($v, strlen($this->prefix."_"));
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
     * 魔术方法(用于调用未封装的原生方法)
     * 【注意】 
     * 		！！！原生方法未对"set/key"等进行前缀加工，需要手工进行！！！
     * 		基于上述原因，不建议使用原生方法
     */
    public function __call($method, $args)
    {
    	$begin_microtime = Debug::getTime();
    	//支持最多三个参数
    	switch(count($args)){
    		case 0: $re = $this->object->$method();
    			break;
    		case 1: $re = $this->object->$method($args[0]); 
    			break;
    		case 2: $re = $this->object->$method($args[0],$args[1]); 
    			break;
    		case 3: $re = $this->object->$method($args[0],$args[1],$args[2]); 
    			break;
    		case 4: $re = $this->object->$method($args[0],$args[1],$args[2],$args[3]); 
    			break;
    		case 5: $re = $this->object->$method($args[0],$args[1],$args[2],$args[3],$args[4]); 
    			break;
    		default: $re = false;
    			\yoka\Debug::log('Queue::__call Error 参数过多',$method . ':' . implode(',', $args));
    			break;
    	}
    	Debug::cache($this->serverlist, '__call', Debug::getTime() - $begin_microtime, $method, $re);
    	return $re;
    }
}
