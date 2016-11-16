<?php
namespace yoka;
/**
 * @name SortList.class.php
 * @desc 有序队列
 * @desc 通过Queue继承Redis操作，参见：https://github.com/phpredis/phpredis#readme
 * @author jimmy.dong@gmail.com
 * 【注意】SSDB与Redis不完全兼容 http://ssdb.io/docs/zh_cn/redis-to-ssdb.html
 * 	1，del无法删除集合或列表，需要用对应的clear方法
 *  2，部分操作函数名不同
 *  3，部分相同函数名参数含义不同
 *  请谨慎设置：$this->is_ssdb
 * 
 * DEMO:
	 	$set = 'sort_test';
	 	$sort = \yoka\SortList::getInstance('default');
	 	$sort->sortClear($set);
		for($i=0;$i<9;$i++)$sort->sortAdd($set, 'a'.$i, $i);
		$sort->sortCount($set);
		$sort->sortGet($set, 3);
		$sort->sortGetDesc($set, 2, 4);
		$sort->sortGetByScore($set, 2, 3);
	 	$sort->sortDeleteByScore($set, 4, 7);
	 	var_dump($sort->sortGetsAll($set));
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
    
    /*-------------------------------------- 有序列表操作(zSet)---------------------------------------------*/
    /**
     * 清空列表
     * @param string $queue_name
     * @return boolean
     */
    public function sortClear($set){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	if(empty($key)) return false;
    	//SSDB不支持redis的删除set指令
    	if($this->is_ssdb) $re = $this->object->zclear($key);
    	else{
    		$re = $this->object->del($key);
    	}
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'sortClear', $re);
    	return $re;
    }
    
    public function sortGetsAll($set){
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	if(empty($key)) return false;
    	$re = $this->object->zRange($key,0,-1,true);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'sortGetsAll', $re);
    	return $re;
    }
    
    /**
     * 获取集合内元素数量
     * @param string $set 集合名称
     * @param number $min 最小值
     * @param number $max 最大值
     */
    public function sortCount($set, $min=null, $max=null){
    	if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	if($min === null || $max == null){
    		$re = $this->object->zSize($key);
    	}else{
    		$re = $this->object->zCount($key, $min, $max);		
    	}
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'sortCount', $re);
    	return $re;
    }
    /**
     * 添加一个键值到zSet
     * @param string $set 集合名称
     * @param string $name 键
     * @param number $value 值
     */
    public function sortAdd($set, $name, $value){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
		if($this->is_ssdb) $re = $this->object->zset($key, $name, $value);
		else $re = $this->object->zAdd($key, $value, $name);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'sortAdd', $re);
    	return $re;
    }
    /**
     * 获取指定键值的值
     * @param string $set 集合名称
     * @param string $name 键
     */
    public function sortGetScore($set, $name){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	if($this->is_ssdb) $re = $this->object->zget($key, $name);
    	else $re = $this->object->zScore($key, $name);
    	Debug::cache($this->serverlist, $key . ':' . $name, Debug::getTime() - $begin_microtime, 'sortGetScore', $re);
    	return $re;
    }
    /**
     * 获取指定键值的排序
     * @param string $set 集合名称
     * @param string $name 键
     */
    public function sortGetRank($set, $name){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	$re = $this->object->zRank($key, $name);
    	Debug::cache($this->serverlist, $key . ':' . $name, Debug::getTime() - $begin_microtime, 'sortGetRank', $re);
    	return $re;
    }
    /**
     * 获取指定键值的排序（倒序）
     * @param string $set 集合名称
     * @param string $name 键
     */
    public function sortGetRankDesc($set, $name){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	$re = $this->object->zRevRank($key, $name);
    	Debug::cache($this->serverlist, $key . ':' . $name, Debug::getTime() - $begin_microtime, 'sortGetRankDesc', $re);
    	return $re;
    }
    /**
     * 增加一个键的值
     * @param string $set 集合名称
     * @param string $name 键
     * @param number $inc 增加量
     */
    public function sortInc($set, $name, $inc=1){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	if($this->is_ssdb)$re = $this->object->zincr($key, $name, $inc);
    	else $re = $this->object->zIncrBy($key, $inc, $name);
    	Debug::cache($this->serverlist, $key . ':' . $name, Debug::getTime() - $begin_microtime, 'sortInc', $re);
    	return $re;
    }
    /**
     * 删除一个键的值
     * @param string $set
     * @param string $name
     */
    public function sortDelete($set, $name){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	if($this->is_ssdb)$re = $this->object->zdel($key, $name);
    	else $re = $this->object->zDelete($key, $name);
    	Debug::cache($this->serverlist, $key . ':' . $name, Debug::getTime() - $begin_microtime, 'sortDelete', $re);
    	return $re;
    }
    
    /**
     * 取N个值
     * @param 集合 $set
     * @param integer $start 开始位置(坐标从0开始)
     * @param integer $limit 数量
     */
    public function sortGet($set, $limit, $start=0, $withScores=true){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	//注意： SSDB::zRange 与 Redis 参数含义不同！ 
    	if($this->is_ssdb) $re = $this->object->zRange($key, $start, $limit);
    	else {
    		$re = $this->object->zrange($key, $start, $start+$limit, $withScores);
    	}
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'sortGet', $re);
    	return $re;
    }
    /**
     * 取N个值 - 倒序
     * @param 集合 $set
     * @param integer $start 开始位置(坐标从0开始)
     * @param integer $limit 数量
     */
    public function sortGetDesc($set, $limit, $start=0, $withScores=true){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	if($this->is_ssdb)$re = $this->object->zRevRange($key, $start, $limit);
    	else $re = $this->object->zRevRange($key, $start, $start + $limit, $withScores);
    	arsort($re);
    	Debug::cache($this->serverlist, $key, Debug::getTime() - $begin_microtime, 'sortGetDesc', $re);
    	return $re;
    }
	/**
	 * 按值的范围取键值
     * @param 集合 $set
	 * @param unknown $min 最小值 （空字符串代表无限大/小）
	 * @param unknown $max 最大值
	 * @param unknown $limit 数目
	 * @param number $start 起始
	 * @param string $withScores 返回值
	 */
    public function sortGetByScore($set, $min, $max, $limit=0, $start=0, $withScores = true){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	if($this->is_ssdb){
    		if($limit==0)$re = $this->object->zscan($key, '', $min, $max,1000); //默认最多1000条
    		else $re = $this->object->zscan($key,'', $min, $max, $limit);
    	}else{
    		if($limit==0)$re = $this->object->zRangeByScore($key, $min, $max, array('withscores'=>$withScores));
    		else $re = $this->object->zRangeByScore($key, $min, $max, array('limit'=>array($start, $limit), 'withscores'=>$withScores));
    	}
    	Debug::cache($this->serverlist, $key . ':' . $min . '-' .$max, Debug::getTime() - $begin_microtime, 'sortGetByScore', $re);
    	return $re;
    }
    /**
     * 按值的范围删除键值
     * @param 集合 $set
	 * @param unknown $min 最小值 （无限大/小 - Redis："+inf" or "-inf"， SSDB：空字符串）
	 * @param unknown $max 最大值
     */
    public function sortDeleteByScore($set, $min, $max){
        if(!$set){
    		\yoka\Debug::log('Sort Error','without set name!');
    		return false;
    	}
    	$begin_microtime = Debug::getTime();
    	$key = $this->_getkey($set);
    	$re = $this->object->zRemRangeByScore($key, $min, $max);
    	Debug::cache($this->serverlist, $key . ':' . $min . '-' .$max, Debug::getTime() - $begin_microtime, 'sortDeleteByScore', $re);
    	return $re;
    }
    
}
