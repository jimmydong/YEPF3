<?php
/**
 * @name CacheInterface
 * @desc 缓冲接口文件
 * @author caoxd
 * @createtime 2008-11-27 06:30
 * @updatetime 
 **/
namespace yoka;

interface CacheInterface
{
    /**
     * @name add
     * @desc 将数据存入缓存。只有键值不存在时，才成功；否则失败。该方法提供原子操作！
     * @param string $cacheKey
     * @param mixed $cacheValue
     * @param int $lifetime
     * @return Boolean
     */
	public function add($cacheKey, $cacheValue, $lifetime = 0);
    /**
	 * @name set
	 * @desc 将数据插入缓冲中
	 * @param string $cacheKey
	 * @param mixed $cacheValue
	 * @return boolean
	 * @access public
	 *
	 */
	public function set($cacheKey, $cacheValue, $lifetime = 0);
    /**
     * @name clear
     * @desc 将数据在缓冲中清除
     * @param string or array $cacheKey
     * @return bool
     * @access public
     **/
	public function clear($cacheKey);
    /**
	 * @name get
	 * @desc 从缓冲中取得数据
	 * @param mixed $cacheKey
	 * @return mixed $cacheValue
	 * @access public
	 *
	 */
	public function get($cacheKey);
}
?>