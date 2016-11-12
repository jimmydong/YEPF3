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
    hashClear - Delete a hash
    hashDel - Delete one or more hash fields
    hashExists - Determine if a hash field exists
    hashGet - Get the value of a hash field
    hashGetAll - Get all the fields and values in a hash
    hashIncr - Increment the integer value of a hash field by the given number
    hashKeys - Get all the fields in a hash
    hashLen - Get the number of fields in a hash
    hashList - SSDB only
    hashMGet - Get the values of all the given hash fields
    hashMSet - Set multiple hash fields to multiple values
    hashSet - Set the string value of a hash field
 *
 * DEMO:
	$redis = \yoka\HashSet::getInstance('default');
	$redis->hashSet('test', 'v1', 1);
	$redis->hashIncr('test, 'v1', 1);
	var_dump($redis->hashGet('test', 'v1');
 */

class HashSet extends Queue
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
    	$hashmap = $this->_getkey($hashmap);
    	//SSDB不支持redis的删除set指令
    	if($this->is_ssdb) {
    		$re = $this->object->hclear($hashmap);
    	}else{
    		$re = $this->object->del($hashmap);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashClear', $re);
    	return $re;
    }
    
    /**
     * 添加一个属性
     * @param unknown $hashmap
     * @param unknown $key
     * @param unknown $value
     * @return unknown
     */
    public function hashSet($hashmap, $key, $value, $encode = false){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	if($encode)$value = json_encode($value, JSON_UNESCAPED_UNICODE);
    	$re = $this->object->hSet($hashmap, $key, $value);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashSet', $re);
    	return $re;
    }

    /**
     * 读取属性
     * @param unknown $hashmap
     * @param unknown $key
     */
    public function hashGet($hashmap, $key, $decode = false){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	$re = $this->object->hGet($hashmap, $key);
    	if($decode) $re = json_decode($re, true);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashGet', $re);
    	return $re;
    }

    /**
     * 删除属性
     * @param unknown $hashmap
     * @param unknown $key
     * @return unknown
     */
    public function hashDel($hashmap, $key){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	$re = $this->object->hDel($hashmap, $key);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashDel', $re);
    	return $re;
    }
    
    /**
     * 增加属性值
     * @param unknown $hashmap
     * @param unknown $key
     * @param number $incr
     * @return unknown
     */
    public function hashIncr($hashmap, $key, $incr=1){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
        if($this->is_ssdb) {
        	$re = $this->object->hincr($hashmap, $key, $incr);
        }else{
    		$re = $this->object->hIncrBy($hashmap, $key, $incr);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashIncr', $re);
    	return $re;
    }
    
    /**
     * 判断属性是否存在
     * @param unknown $hashmap
     * @param unknown $key
     */
    public function hashExists($hashmap, $key){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	$re = $this->object->hExists($hashmap, $key);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashExists', $re);
    	return $re;
    }
    
    /**
     * 属性总数
     * @param unknown $hashmap
     * @return unknown
     */
    public function hashLen($hashmap){
    	$begin_microtime = Debug::getTime();
		$hashmap = $this->_getkey($hashmap);
		if($this->is_ssdb) {
    		$re = $this->object->hsize($hashmap);
    	}else{
    		$re = $this->object->hLen($hashmap);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashLen', $re);
    	return $re;
    }
    
    /**
     * 获取全部属性
     * @param unknown $hashmap
     * @return unknown
     */
    public function hashGetAll($hashmap){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	$re = $this->object->hGetAll($hashmap);
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashGetAll', $re);
    	return $re;
    }
    
    /**
     * 获取所有属性的键值
     * @param unknown $hashmap
     * @param string $begin
     * @param string $end
     * @param number $limit
     */
    public function hashKeys($hashmap, $begin="", $end="", $limit=200){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	if($this->is_ssdb) {
    		$re = $this->object->hkeys($hashmap, $begin, $end, $limit);
    	}else{
    		$re = $this->object->hKyes($hashmap);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashKeys', $re);
    	return $re;
    }
    
    /**
     * 获取所有属性的值
     * @param unknown $hashmap
     * @return unknown
     */
    public function hashVals($hashmap){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	if($this->is_ssdb) { //SSDB不支持，变通方式性能较低
    		$list = $this->object->hgetall($hashmap);
    		foreach($list as $v) $re[] = $v;
    	}else{
    		$re = $this->object->hVals($hashmap);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashVals', $re);
    	return $re;
    }

    /**
     * 获取键值范围内的属性
     * @param unknown $hashname
     * @param string $begin
     * @param string $end
     * @param number $limit
     */
    public function hashScan($hashname, $begin="", $end="", $limit=200){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	if($this->is_ssdb) {
    		$re = $this->object->hscan($hashmap, $begin, $end, $limit);
    	}else{
    		$re = false; //redis不支持此方法
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashScan', $re);
    	return $re;
    }
    
    /**
     * 列出范围内的哈希
     * @param string $begin
     * @param string $end
     * @param number $limit
     * @return boolean
     */
    public function hashList($begin="", $end="", $limit=200){
    	$begin_microtime = Debug::getTime();
    	$begin_name = $this->_getkey($begin);
    	$end_name = $this->_getkey($end);
    	if($this->is_ssdb) {
    		$re = $this->object->hkeys($begin_name, $end_name, $limit);
    	}else{
    		$re = false; //redis不支持此方法
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashList', $re);
    	return $re;
    }
    
    /**
     * 多赋值
     * @param unknown $hashmap
     * @param unknown $data
     * @return unknown
     */
    public function hashMset($hashmap, $data){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
        if($this->is_ssdb) {
    		$re = $this->object->multi_hset($hashmap, $data);
    	}else{
    		$re = $this->object->hMset($hashmap,$data);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashMset', $re);
    	return $re;
    }

    /**
     * 多读取
     * @param unknown $hashmap
     * @param unknown $keylist
     * @return unknown
     */
    public function hashMget($hashmap, $keylist){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	if($this->is_ssdb) {
    		$re = $this->object->multi_hget($hashmap, $key_list);
    	}else{
    		$re = $this->object->hMget($hashmap,$key_list);
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashMget', $re);
    	return $re;
    }
    
    /**
     * 多删除
     * @param unknown $hashmap
     * @param unknown $keylist
     */
    public function hashMdel($hashmap, $keylist){
    	$begin_microtime = Debug::getTime();
    	$hashmap = $this->_getkey($hashmap);
    	if($this->is_ssdb) {
    		$re = $this->object->multi_hdel($hashmap, $key_list);
    	}else{ //php-redis不支持
    		foreach($key_list as $key)$re = $this->object->hDel($hashmap,$key);
    		$re = true;
    	}
    	Debug::cache($this->serverlist, $hashmap, Debug::getTime() - $begin_microtime, 'hashDel', $re);
    	return $re;
    }
    
}
