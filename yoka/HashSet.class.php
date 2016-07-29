<?php
namespace yoka;
/**
 * @name HashSet.class.php
 * @desc 解决 php-redis 与 SSDB 库函数差异
 * @desc 通过Queue继承Redis操作，参见：https://github.com/phpredis/phpredis#readme
 * @author jimmy.dong@gmail.com
 * 【注意】SSDB与Redis不完全兼容 http://ssdb.io/docs/zh_cn/redis-to-ssdb.html
 * 	1，del无法删除集合或列表，需要用对应的clear方法
 *  2，部分操作函数名不同
 *  3，部分相同函数名参数含义不同
 *  请谨慎设置：$this->is_ssdb
 * 
 * DEMO:

 */

class SortList extends Queue
{
    /**
	 * @name __construct
	 * @desc 构造函数
	 * @param void
	 * @return object instance
	 * @access protected
	 *
	 */
    protected function __construct($item = '', $is_ssdb = null)
    {
    	parent::__construct($item, $is_ssdb);
    }
    
    /*-------------------------------------- 哈希操作(hash)---------------------------------------------*/
    /**
     * 清空列表
     * @param string $queue_name
     * @return boolean
     */
    public function hashClear($hashmap){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($hashmap);
    	if(empty($key)) return false;
    	//SSDB不支持redis的删除set指令
    	if($this->is_ssdb) {
    		$re = $this->object->hclear($key);
    	}else{
    		$re = $this->object->del($key);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashClear', $re);
    	return $re;
    }
    
    public function hashSet($hashmap, $key, $value){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($hashmap);
    	if(empty($key)) return false;
    	$re = $this->object->hSet($hashmap, $key, $value);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashSet', $re);
    	return $re;
    }

    public function hashGet($hashmap, $key){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($hashmap);
    	if(empty($key)) return false;
    	$re = $this->object->hGet($hashmap, $key);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashGet', $re);
    	return $re;
    }

    public function hashIncr($hashmap, $key, $incr=1){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($hashmap);
    	if(empty($key)) return false;
        if($this->is_ssdb) {
        	$re = $this->object->hincr($hashmap, $key, $incr);
        }else{
    		$re = $this->object->hIncrBy($hashmap, $key, $incr);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashIncr', $re);
    	return $re;
    }
    
    public function hashExists($hashmap, $key){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($hashmap);
    	if(empty($key)) return false;
    	$re = $this->object->hExists($hashmap, $key);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashExists', $re);
    	return $re;
    }
    
    public function hashLen($hashmap){
    	$begin_microtime = Debug::getTime();
    	if($this->is_ssdb) {
    		$re = $this->object->hsize($hashmap);
    	}else{
    		$re = $this->object->hLen($hashmap);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashLen', $re);
    	return $re;
    }
    
    public function hashKeys($hashmap){
    	$begin_microtime = Debug::getTime();
   		$re = $this->object->hKeys($hashmap);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashKeys', $re);
    	return $re;
    }
    
    public function hashGetAll($hashmap){
    	$begin_microtime = Debug::getTime();
    	$re = $this->object->hGetAll($hashmap);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashGetAll', $re);
    	return $re;
    }
    
    public function hashKeys($hashmap, $begin="", $end="", $limit=200){
    	$begin_microtime = Debug::getTime();
    	if($this->is_ssdb) {
    		$re = $this->object->hkeys($hashmap, $begin, $end, $limit);
    	}else{
    		$re = $this->object->hKyes($hashmap);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashKeys', $re);
    	return $re;
    }
    
    public function hashVals($hashmap){
    	$begin_microtime = Debug::getTime();
    	if($this->is_ssdb) { //SSDB不支持，变通方式性能较低
    		$list = $this->object->hgetall($hashmap);
    		foreach($list as $v) $re[] = $v;
    	}else{
    		$re = $this->object->hVals($hashmap);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashVals', $re);
    	return $re;
    }
    
    public function hashList($begin="", $end="", $limit=200){
    	$begin_microtime = Debug::getTime();
    	$begin_name = $this->_getkey($begin);
    	$end_name = $this->_getkey($end);
    	if($this->is_ssdb) {
    		$re = $this->object->hkeys($hashmap, $begin, $end, $limit);
    	}else{
    		$re = false; //redis不支持此方法
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashList', $re);
    	return $re;
    }
    
    public function hashMset($hashmap, $datalist){
    	$begin_microtime = Debug::getTime();
    	foreach($datalist as $k=>$v){
    		$key = $this->_getkey($k);
    		$data[$key] = $v;
    	}
        if($this->is_ssdb) {
    		$re = $this->object->multi_hset($hashmap, $data);
    	}else{
    		$re = $this->object->hMset($hashmap,$data);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashMset', $re);
    	return $re;
    }

    public function hashMget($hashmap, $keylist){
    	$begin_microtime = Debug::getTime();
    	foreach($keylist as $v){
    		$key = $this->_getkey($v);
    		$data[] = $key;
    	}
    	if($this->is_ssdb) {
    		$re = $this->object->multi_hget($hashmap, $data);
    	}else{
    		$re = $this->object->hMget($hashmap,$data);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashMget', $re);
    	return $re;
    }
    
    public function hashMdel($hashmap, $keylist){
    	$begin_microtime = Debug::getTime();
    	foreach($keylist as $v){
    		$key = $this->_getkey($v);
    		$data[] = $key;
    	}
    	if($this->is_ssdb) {
    		$re = $this->object->multi_hdel($hashmap, $data);
    	}else{ //php-redis不支持
    		foreach($data as $key)$re = $this->object->hDel($hashmap,$key);
    		$re = true
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashDel', $re);
    	return $re;
    }
    
}
