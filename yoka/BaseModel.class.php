<?php
/**
 * 实体对象基类
 * @author jimmy.dong@gmail.com
 *
 * 【注意】 使用实体类的约定：
 * 1， 实体对应数据表，主键必须是自增id.否则此基类方法无效 *
 * 2， 子类中update_time 为自动更新timestamp字段，更新时忽略由系统进行更新
 * 3， 子类中定义静态方法 refresh() 用于刷新子类中定制的缓冲
 * 4， 支持SQL兼容及数据库直接操作，但不推荐使用
 * 5， 默认数据为已addslashes处理，否则应添加addslashes标记
 */
namespace yoka;
use yoka\Debug;
use yoka\Cache;
use yoka\Log;
use yoka\DB;
use mdbao\User;

class BaseModel{

	public $db;
	public $entity;
	public static $_EnableBuffer = true;		//防止大数据量处理时内存不足
	public static $_BaseModel_Buffer;
	public static $_DefaultCacheTime = 3600; 	//默认fetchAllCache缓冲时间，秒
	public $_stopAutoRefresh = false;			//禁止自动更新，用于批量操作。

	/**
	 * 实例化
	 * @param int $id 用户ID
	 */
	function __construct($id = null){
		if(\YsConfig::$SLAVE_DB_FIRST === true) $master = false;
		elseif(is_string(\YsConfig::$SLAVE_DB_FIRST)) $master = \YsConfig::$SLAVE_DB_FIRST;
		else $master = true;
		$this->db = DB::getInstance('default', $master);
		if($id){
			$table = static::$table;
			$re = $this->db->fetchOne("select * from `{$table}` where %_creteria_%", array('id'=>$id));
			if($re){
				$this->entity = $re;
			}else{
				$exception = new \Exception("ysdb_{$table} {$id} 实体不存在");
				throw $exception;
			}
		}
		//注意：不传入id时，创建空实体
	}

	/**
	 * 设置从库读取
	 * 注意：
	 * 1，必须在实例化之前执行
	 * 2, 修改后全局影响
	 * 3，建议使用mysql-router
	 * 4，建议使用 fetchAllCached 方法
	 */
	public static function setSlave($flag = true){
		\YsConfig::$SLAVE_DB_FIRST = $flag;
	}

	/**
	 * 强制重新连接数据库
	 * 用于可能运行时间超过mysql超时时间的处理。尤其是守护模式。
	 */
	function db_reconnect(){
		$this->db->reconnect();
	}

	/**
	 * 切换到主库
	 */
	function db_change_master(){
		$this->db = DB::getInstance('default');
	}

	/**
	 * 切换到从库
	 */
	function db_change_slave(){
		$this->db = DB::getInstance('default', false);
	}

	/**
	 * 切换到统计库
	 */
	function db_change_stat(){
		$this->db = DB::getInstance('default', 'stat');

		//关闭debug等提高性能
		\yoka\Debug::stop();
		self::stopAutoRefresh();
		self::$_EnableBuffer = false;
	}

	/**
	 * 缓冲获取实例（请留意自动刷新机制）
	 * @param int $id
	 * @param bool $refresh 强制刷新
	 */
	public static function getInstance($id, $refresh = false){
		$table = static::$table;
		$class = get_called_class();
		$key = "BaseModel_Cache_" . $table . '_' . $id;
		$cache = \yoka\Cache::getInstance('default');
		if(!SiteCacheForceRefresh && !$refresh){
			if(self::$_BaseModel_Buffer[$key]){
				$re = new $class;
				$re->entity = self::$_BaseModel_Buffer[$key];
				return $re;
			}
			$t = $cache->get($key);
			if($t){
				$re = new $class;
				$re->entity = $t;
				if(self::$_EnableBuffer)self::$_BaseModel_Buffer[$key] = $t;
				return $re;
			}
		}
		$re = new $class;
		$entity = $re->db->fetchOne("select * from `{$table}` where %_creteria_%", array('id'=>$id));
		if($entity){
			$re->entity = $entity;
			if(self::$_EnableBuffer)self::$_BaseModel_Buffer[$key] = $entity;
			$cache->set($key, $re->entity, 3600*4);
			return $re;
		}else{
			return false;
		}
	}

	/**
	 * 获取实例（默认不使用缓冲）
	 * @param int $id
	 */
	public static function getById($id, $use_cache=false){
		$table = static::$table;
		$class = get_called_class();
		$model = new $class();
		if($use_cache){
			$re = self::getEntityById($id);
		}else{
			$re = $model->db->fetchOne("select * from `{$table}` where %_creteria_%", array('id'=>$id));
		}
		if(!$re) return false;
		else {
			$model->entity = $re;
			return $model;
		}
	}

	/**
	 * 用ID获取Entity（注意：返回为数组，不是对象。默认使用缓冲）
	 *
	 * @param  int $id
	 * @return mixed
	 */
	public static function getEntityById($id, $refresh=false) {
		$obj = self::getInstance($id, $refresh);
		if(!$obj)return false;
		else return $obj->getEntity();
	}


	/**
	 * 取当前对象数据
	 * @return array
	 */
	public function getEntity(){
		return $this->entity;
	}

	/**
	 * 禁止自动执行_refresh，以提高批量处理时的效率
	 */
	public function stopAutoRefresh(){
		$this->_stopAutoRefresh = true;
	}
	/**
	 * 恢复自动执行_refresh
	 */
	public function restartAutoRefresh(){
		$this->_stopAutoRefresh = false;
	}

	/**
	 * 调用子类中的refresh方法【约定：子类中定义static public function refresh()用于刷新自身的特殊缓冲】
	 */
	public function _refresh($class, $id = null){
		//是否被禁止自动更新
		if($this->_stopAutoRefresh) return false;

		if(method_exists($class, 'refresh')){
			//\yoka\Debug::log('BaseModel', $class . '::refresh()');
			$class::refresh($id);
		}
	}

	/**
	 * 添加数据
	 * @param array $arr
	 * @return \model\BaseModel|boolean
	 */
	public function add($arr, $addslashes = false){
		//当前连接非主库，禁止写入
		if(\YsConfig::$SLAVE_DB_FIRST !== false){
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}

		$table = static::$table;
		$class = get_called_class();
		if($this->db->insert($table, $arr, $addslashes)){
			$id = $this->db->insertId();
			\yoka\Debug::log('insert-id', $id);
			//$this->fetchOne(array('id'=>$id));
			$this->entity = $arr;
			$this->id = $id;
			$this->_refresh($class, $id); //调用子类中刷新方法
			return $this;
		}else{
			// $this->db->err();
			return false;
		}
	}

	/**
	 * 替换增（不推荐使用。风险：如果字段不全，会导致数据丢失）
	 */
	public function replace($arr, $addslashes){
		//当前连接非主库，禁止写入
		if(\YsConfig::$SLAVE_DB_FIRST !== false){
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}

		$table = static::$table;
		$sql = "REPLACE INTO `".$table."` SET " ;
		foreach ($info as $k => $v)
		{
			if($v === null) $s .= "`{$k}` = NULL,";
			elseif($addslashes) $s .= '`'.$k . "` = '" . addslashes($v) . "',";
			else $s .= '`'.$k . "` = '" . $v . "',";
		}
		$sql .= substr($s, 0, -1);
		return $this->query($sql);
	}


	/**
	 * 删除对象（注意： 原则上不允许物理删除数据）
	 * @param string $id
	 * @return boolean
	 */
	public function del($id=null){
		//当前连接非主库，禁止写入
		if(\YsConfig::$SLAVE_DB_FIRST !== false){
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}

		$table = static::$table;
		$class = get_called_class();
		$cache = \yoka\Cache::getInstance('default');

		if($id){
			$this->db->delete($table, array('id'=>$id));
			$this->entity = null;
			$key = "BaseModel_Cache_" . $table . '_' . $id;
			$cache->del($key);
			unset(self::$_BaseModel_Buffer[$key]);
			$this->_refresh($class, $id); //调用子类中刷新方法
			return true;
		}elseif($this->entity['id']){
			$this->db->delete($table, array('id'=>$this->entity['id']));
			$this->entity['id'] = null;
			$key = "BaseModel_Cache_" . $table . '_' . $this->entity['id'];
			$cache->del($key);
			unset(self::$_BaseModel_Buffer[$key]);
			$this->_refresh($class, $this->entity['id']); //调用子类中刷新方法
			return true;
		}else{
			$this->db->err();
			return false;
		}
	}

	/**
	 * 保存（更新）对象
	 * @return \model\BaseModel
	 */
	public function save($addslashes = false){
		//当前连接非主库，禁止写入
		if(\YsConfig::$SLAVE_DB_FIRST !== false){
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}

		$table = static::$table;
		//\yoka\Debug::log('entity', $this->entity);
		if(!$this->entity['id'])$this->add($this->entity, $addslashes); //新增
		else $this->update($this->entity , null, $addslashes); //更新
		return $this;
	}

	/**
	 * 辅助 save() 使用。不建议单独使用。
	 * @param array $info
	 * @param string $cretia
	 * @return boolean|\model\BaseModel
	 */
	public function update($info, $cretia = null, $addslashes = false){
		//当前连接非主库，禁止写入
		if(\YsConfig::$SLAVE_DB_FIRST !== false){
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}

		$table = static::$table;
		$class = get_called_class();
		$cache = \yoka\Cache::getInstance('default');

		if($cretia){ //条件更新，使用时请谨慎。 —— 已禁用 by bandri
			if($cretia['__FORCE__'] == true){	//强制更新
				unset($cretia['__FORCE__']);
				$this->db->update($table, $info, $cretia);
				return true;
			}else{
				throw new \Exception('错误：当前版本不允许修改该对象之外的记录', -1);
				return false;
			}
		}elseif($info['id']){
			//\yoka\Debug::log('update:info', $info);
			/*注意：保护update_time自动变量字段*/
			if(isset($info['update_time']))unset($info['update_time']);

			$this->db->update($table, $info, array('id'=>$info['id']), false, true, false, 'AND', $addslashes);
			$this->fetchOne(array('id'=>$info['id']));

			$key = "BaseModel_Cache_" . $table . '_' . $info['id'];
			$cache->del($key);
			unset(self::$_BaseModel_Buffer[$key]);
			$this->_refresh($class, $info['id']); //调用子类中刷新方法

		}elseif($this->entity['id']){
			/*注意：保护update_time自动变量字段*/
			unset($info['update_time']);
			//\yoka\Debug::log('update:id', $this->entity);
			$this->db->update($table, $info, array('id'=>$this->entity['id']), false, true, false, 'AND', $addslashes);
			$this->fetchOne(array('id'=>$this->entity['id']));

			$key = "BaseModel_Cache_" . $table . '_' . $this->entity['id'];
			$cache->del($key);
			unset(self::$_BaseModel_Buffer[$key]);
			$this->_refresh($class, $this->entity['id']); //调用子类中刷新方法

		}else{
			$this->db->err('BaseModel Error: update without ID - ' . var_export($info, true));
			return false;
		}
		//\yoka\Debug::log('entity', $this->entity);
		return $this;
	}

	/**
	 * 对字段进行增量操作
	 * @param string $key
	 * @param number $step
	 */
	public function increase($key, $step = 1){
		$table = static::$table;
		$class = get_called_class();
		if(!$this->entity['id'])return false;
		$step = floatval($step);
		$this->db->query("update `{$table}` set `{$key}` = `{$key}` + {$step} where id=" . $this->entity['id']);

		$cache = \yoka\Cache::getInstance('default');
		$key = "BaseModel_Cache_" . $table . '_' . $this->entity['id'];
		$cache->del($key);
		unset(self::$_BaseModel_Buffer[$key]);
		$this->_refresh($class, $this->entity['id']); //调用子类中刷新方法

	}

	/**
	 * 查询单条数据
	 * @param mixed $mix 数组（creteria格式）或 字符串：where条件 或 %_table_% 的全SQL
	 * @return \model\BaseModel
	 */
	public function fetchOne($mix){
		$table = static::$table;
		if(null == $mix){
			$re = $this->db->fetchOne("select * from `{$table}` limit 1");
		}elseif(is_array($mix)){ //creteria方式
			$re = $this->db->fetchOne("select * from {$table} where %_creteria_%", $mix);
		}elseif (strpos(strtolower(trim($mix)), 'select') === 0) {
			//普通sql方式
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
			$re = $this->db->fetchOne($sql);
		}else{
			$re = $this->db->fetchOne("select * from {$table} where $mix");
		}

		if(!$re)return false;

		$this->entity = $re;
		return $this;
	}

	/**
	 * 批量查询【注意，对返回值进行了重整，id为主键】
	 * @return array(array, array ...)
	 */
	public function fetchAll($mix = null){
		$table = static::$table;
		if(null == $mix){ //获取全部内容 【注意：强制切断1000行，防止崩溃！】
			$re = $this->db->fetchAll("select * from {$table} limit 1000");
		}elseif(is_array($mix)){ //creteria方式
			$re = $this->db->fetchAll("select * from {$table} where %_creteria_%", $mix);
		}elseif (strpos(strtolower($mix), 'select') === 0) {
			//普通sql方式
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
			$re = $this->db->fetchAll($sql);
		}else{
			$re = $this->db->fetchAll("select * from {$table} where $mix");
		}


		$t = current($re);
		if($t['id']){
			//用id作为每行数据的主键
			foreach($re as $v){
				$new_re[$v['id']] = $v;
			}
			return $new_re;
		}else{
			return $re;
		}

	}
	/**
	 * 功能与fetchAll相同，区别在于不做主键重整提高效率
	 * @param mixed $mix
	 * @return array(array)
	 */
	public function fetchAllRaw($mix = null){
		$table = static::$table;
		if(null == $mix){ //获取全部内容 【注意：强制切断1000行，防止崩溃！】
			$re = $this->db->fetchAll("select * from {$table} limit 1000");
		}elseif(is_array($mix)){ //creteria方式
			$re = $this->db->fetchAll("select * from {$table} where %_creteria_%", $mix);
		}elseif (strpos(strtolower($mix), 'select') === 0) {
			//普通sql方式
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
			$re = $this->db->fetchAll($sql);
		}else{
			$re = $this->db->fetchAll("select * from {$table} where $mix");
		}

		return $re;
	}
	/**
	 * 带缓存的批量查询【注意：$this->_DefaultCacheTime】
	 * @param mixed $mix
	 * @return array(array)
	 */
	public function fetchAllCached($mix = null){
		$table = static::$table;
		$key = 'fetchAll_' . $table  . '_' . md5(json_encode($mix));
		$cache = \yoka\Cache::getInstance('default');
		if(!SiteCacheForceRefresh){
			$re = $cache->get($key);
			if($re)return $re;
		}
		//强制使用从库
		$slave_flag = \YsConfig::$SLAVE_DB_FIRST;
		\YsConfig::$SLAVE_DB_FIRST = true;
		$re = $this->fetchAll($mix);
		$cache->set($key, $re, self::$_DefaultCacheTime);
		\YsConfig::$SLAVE_DB_FIRST = $slave_flag; //还原
		return $re;
	}

	/**
	 * 对fetchAll返回结果进行排序
	 * @param array $data 待排序二维数组
	 * @param string $col 排序的字段
	 * @param mix $sort 默认正序。倒序： -1,DESC,SORT_DESC
	 * @param string $type SORT_REGULAR, SORT_NUMBER, SORT_STRING
	 */
	public static function sort($data, $col='id', $sort='', $type=''){
		if($sort == -1 || strtolower($sort) == 'desc' || $srot == SORT_DESC || strtolower($sort) == 'sort_desc')$sort = SORT_DESC;
		else $sort = SORT_ASC;
		foreach($data as $key=>$val){
			$tmp[$key] = $val[$col];
		}
		if($type){
			if(array_multisort($tmp,$sort,$type,$data))return $data;
			else return false;
		}else{
			if(array_multisort($tmp,$sort,$data))return $data;
			else return false;
		}
	}
	/**
	 * 从游标取一条数据
	 * @param unknown $mix
	 */
	public function fetch(){
		return $this->db->fetch();
	}

	/**
	 * 查询（默认返回true/false）
	 * 注意： 传入$return_statement=true，其结果foreach迭代出的结果数组，不是对象本身
	 * $admin = new User();
	 * foreach($db->query( array('username'=>array('like'=>'test')) , true) as $row){ ... }
	 */
	public function query($mix, $return_statement = false){
		$table = static::$table;
		if(is_array($mix)){
			$where = self::_buildQuery($creteria, $trim, $strict, $connector, $addslashes);
			$sql = "select * from `{$table}` " . $where;
		}else{
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
		}
		$re = $this->db->query($sql, $return_statement);
		return $re;
	}

	/**
	 * 魔术方法
	 */
	public function __get($key){
		return $this->entity[$key];
	}
	public function __set($key, $value){
		$this->entity[$key] = $value;
		return true;
	}

	/**
	 * 快捷填写
	 */
	public function autoFit($list, $request){
		foreach($list as $k){
			if($request->$k) $this->__set($k, $request->$k);
		}
	}

	/**
	 * 根据配置，进行信息精简，去掉已设置的不输出的信息
	 * @param array $info 对应对象的数据
	 */
	public static function simpleinfo($info) {
		if (isset(static::$simple_cols)) {
			//\yoka\Debug::log('static::$simple_cols', static::$simple_cols);
			foreach (static::$simple_cols as $col) {
				unset($info[$col]);
			}
		}
		return $info;
	}
	/**
	 * 从model的query方法结果集中获取数组结果
	 * @param unknown $result
	 * @return multitype:multitype:unknown
	 */
	public static function getListFromResult($result) {
		$list = array ();
		foreach ( $result as $row ) {
			$data = array ();
			foreach ( $row as $key => $val ) {
				if (is_numeric ( $key )) {
					continue;
				}
				$data [$key] = $val;
			}
			$list[] = $data;
		}
		return $list;
	}
}
