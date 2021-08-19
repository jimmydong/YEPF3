<?php
namespace yoka;
/**
 * 实体对象基类
 * @author jimmy.dong@gmail.com
 *
 * 【注意】 使用实体类的约定：
 * 1， 实体对应数据表，主键必须是自增id.否则此基类方法无效 * [可通过pkey自定义主键名称]
 * 2， 子类中update_time 为自动更新timestamp字段，更新时忽略由系统进行更新
 * 3， 子类中定义静态方法 refresh() 用于刷新子类中定制的缓冲
 * 4， 支持SQL兼容及数据库直接操作，但不推荐使用
 * 5， 默认数据为已addslashes处理，否则应添加addslashes标记
 * 6， add/save/update/del 操作失败时返回false， 使用串列写法时请注意！
 * 7， update 默认禁止批量更新。使用$cretia['__FORCE__'] == true可执行批量更新。
 * 8， 手写SQL时不要直接使用表名，使用"%_table_%"。
 * 9， 如必须使用表名（如JOIN操作），需在SQL后加上：";#@USE_TABLE_NAME" 标记
 * 10，【重要】请留意默认缓冲的配置(default)。
 * 
 * [自定义主键]
 * 1， 支持非id作为主键。在子类中定义：static $pkey 。
 * 2， 原有id为主键的无需任何调整，完全兼容
 *
 * [2021.07.07 更新]
 * 1,   增加updateById方法
 * 2， 增加entityToObj方法
 * 
 * [2020.04.10 更新]
 * 1， 增加cache控制：A，setCache可关闭cache；B，如果cache项不是'default'，使用setCacheName指定
 * 2， 更新_slime方法，增加type自动格式化
 * 
 * [v3 更新]
 * 1， 更新_slim方法，增加type/filter
 * 2， 支持非默认数据库连接
 *
 * [v2 更新]
 * 1， 不确定id是否存在的，请使用 geteById() 方法
 * 2， update 更新非本体数据时，需使用 __FORCE__ 参数
 * 3， 高性能开发时请优先使用带缓冲的方法：
 * 			getEntityById()		默认缓冲
 * 			getInstance(true)	参数设定缓冲
 * 			fetchAllCached()
 * 4， 默认fetchAll仅获取1000条数据。获取数据较多时，可用fetchAllRaw提高性能(注意返回值不是id作为主键)。
 * 5， 获取大量数据时，应使用 query(xql, true) 方法获取迭代对象进行操作
 * 6,  批量更新较多数据时，应使用 stopAutoRefresh() ... restartAutoRefresh() 屏蔽缓存操作提高效率
 * 7， 增加了字段自动过滤，需在子类中定义 $filter_fields 。注意：仅对 add/save/replace/update 有效
 * 8， 增加snapshop 和 slim 方法。子类中使用 $default_slim 定义默认slim字段
 * 9， 新增_slim方法取代slim。子类中使用 $define_slim 定义字段含义。（请结合db_info工具使用）
 *
 */
class BaseModel{
	public $db;
	public $entity;
	public static $_EnableBuffer = false;		//是否启用默认内存缓冲
	public static $_BaseModel_Buffer;			//用于内存缓冲
	public static $_DefaultCacheTime = 3600; 	//默认fetchAllCache缓冲时间，秒
	public static $cacheName = 'default';		//缺省缓冲名称
	public static $cacheFlag = true;			//默认使用缓冲
	public static $singleton = [];				//兼容保留，不推荐
	public $_stopAutoRefresh = false;			//禁止自动更新，用于批量操作。
	private $ismaster = true; 					//默认使用主库
	
	// 用于自动过滤，在子类设置 $filter_fields 值（需要过滤的字段）
	protected $filter_str = [" ","'","\r","\n","\t",'"', '(', ')', '（', '）', ',', '，', '“', '”', '<', '>'];
	protected $filter_fields = [];				//待过滤字段
	protected $filter_all = false;				//如果true，过滤所有字段
	
	//隐私字段处理
	const PROTECT_NONE = 0; 	//未定义
	const PROTECT_MOBILE = 1;	//手机号
	const PROTECT_NAME = 2;		//姓名（短字符串）
	const PROTECT_STRING = 3;	//长字符串（例：地址）
	const PROTECT_HIDE = 11;	//强制隐藏
	static public function _protect($protect, $val){
		switch($protect){
			case self::PROTECT_MOBILE:
				$re = Util::hideMiddle($val);
				break;
			case self::PROTECT_NAME:
				$re = Util::hideMiddle($val);
				break;
			case self::PROTECT_STRING:
				$re = Util::hideMiddle($val);
				break;
			case self::PROTECT_HIDE:
				$re = "***数据保护***";
				break;
			case self::PROTECT_NONE:
			default:
				$re = $val;
				break;
		}
		return $re;
	}
	
	// 字段类型定义 （用于_slim及自动处理）
	const TYPE_NONE = 0;	//未定义
	const TYPE_INT = 1;		//整数
	const TYPE_STRING = 2;	//字符串
	const TYPE_FLOAT = 3;	//浮点数
	const TYPE_JSON = 4;	//JSON格式字符串
	const TYPE_DATE = 5;	//日期
	const TYPE_DATETIME = 6;	//日期时间
	const TYPE_TIMESTAMP = 7;	//时间戳整数
	//const TYPE_LIST = 8;  //逗号分隔的
	static public function _type($type, $val){
		switch($type){
			case self::TYPE_INT:
			case self::TYPE_TIMESTAMP;
			$re = (int)$val;
			break;
			case self::TYPE_FLOAT:
				$re = (float)$val;
				break;
			case self::TYPE_STRING:
			case self::TYPE_JSON:
				$re = (string)$val;
				break;
			case self::TYPE_DATE:
				if(! $val) $re = '0000-00-00';
				else $re = date('Y-m-d', strtotime($val));
				break;
			case self::TYPE_DATETIME:
				if(! $val) $re = '0000-00-00 00:00:00';
				else $re = date('Y-m-d H:i:s', strtotime($val));
				break;
			case self::TYPE_NONE:
			default:
				$re = $val;
				break;
		}
		return $re;
	}
	
	/**
	 * 实例化
	 * @param int $id 用户ID
	 */
	function __construct($id = null){
		if(isset(static::$database)){
			//按设定的数据库连接
			$this->db = DB::getInstance(static::$database);
			//设定的数据库只连接主库（防止slave标志混淆），需要时请 db_change_slave
			$this->ismaster = true;
		}else{
			if(\YsConfig::$SLAVE_DB_FIRST === true) $master = false;
			elseif(is_string(\YsConfig::$SLAVE_DB_FIRST)) $master = \YsConfig::$SLAVE_DB_FIRST;
			else $master = true;
			// 保存当前链接使用的是否是主库
			$this->ismaster = $master;
			$this->db = DB::getInstance('default', $this->ismaster);
		}
		
		// 不推荐ID实例化。建议使用： class::getById($id)
		if($id){
			$table = static::$table;
			// $pkey = static::$pkey?:'id';
			$pkey = isset(static::$pkey)?static::$pkey:'id';
			$re = $this->db->fetchOne("SELECT * FROM `{$table}` WHERE %_creteria_%", array($pkey=>$id));
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
	 * 5，返回之前设置。
	 * [使用建议]使用前保存设置，使用后恢复原有设置
	 */
	static public function setSlave($flag = true){
		$old_setting = \YsConfig::$SLAVE_DB_FIRST;
		\YsConfig::$SLAVE_DB_FIRST = $flag;
		return $old_setting;
	}
	
	/**
	 * 设置自动过滤总开关
	 * @param string $state
	 * @author ws
	 */
	public function setFilterAll($state = true) {
		$this->filter_all = $state;
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
		if(isset(static::$database)) $database = static::$database;
		else $database = 'default';
		$this->db = DB::getInstance($database);
	}
	
	/**
	 * 切换到从库
	 */
	function db_change_slave($name = false){
		if(isset(static::$database)) $database = static::$database;
		else $database = 'default';
		$this->db = DB::getInstance($database, $name);
	}
	
	/**
	 * 切换到统计库(注意：统计专用，会关闭Debug以提高统计速度)
	 */
	function db_change_stat(){
		if(isset(static::$database)) $database = static::$database;
		else $database = 'default';
		$this->db = DB::getInstance($database, 'stat');
		
		//关闭debug提高性能
		\yoka\Debug::stop();
		self::stopAutoRefresh();
		self::$_EnableBuffer = false;
	}
	
	/**
	 * 是否启用缓冲
	 * [注意] cache 与 buffer 是不同控制机制。关闭 buffer 应使用 self::$_EnableBuffer = false
	 * @param bool $cacheFlag
	 * @param bool $cacheName
	 */
	static public function setCache($cacheFlag, $cacheName = null){
		self::$cacheFlag = $cacheFlag;
		if($cacheName !== null) self::$cacheName = $cacheName;
	}
	
	/**
	 * 设置缓冲实例名称
	 */
	static public function setCacheName($name = 'default'){
		self::$cacheName = $name;
	}
	
	/**
	 * 单例
	 * 【注意： 与实例对象的设计思想不相符，也不能有效提高性能，不推荐使用】
	 * @return self
	 */
	public static function getSingleton(){
		$table = static::$table;
		$class = get_called_class();
		if(self::$singleton[$table] instanceof self){
			return self::$singleton[$table];
		}else{
			$singleton =  new $class;
			self::$singleton[$table] = $singleton;
			return $singleton;
		}
	}
	
	/**
	 * 缓冲获取实例（请留意自动刷新机制）
	 * @param int $id
	 * @param bool $refresh 强制刷新
	 * @return self
	 */
	static public function getInstance($id, $refresh = false){
		$table = static::$table;
		$pkey = isset(static::$pkey)?static::$pkey:'id';
		$class = get_called_class();
		$key = "BaseModel_Cache_" . $table . '_' . $id;
		//使用内存缓冲
		if(self::$_EnableBuffer && self::$_BaseModel_Buffer[$key]){
			$re = new $class;
			$re->entity = self::$_BaseModel_Buffer[$key];
			return $re;
		}
		//使用cache缓冲
		if(self::$cacheFlag){
			$cache = \yoka\Cache::getInstance(self::$cacheName);
			if(!SiteCacheForceRefresh && !$refresh){
				$t = $cache->get($key);
				if($t){
					$re = new $class;
					$re->entity = $t;
					if(self::$_EnableBuffer)self::$_BaseModel_Buffer[$key] = $t;
					return $re;
				}
			}
		}
		$re = new $class;
		$entity = $re->db->fetchOne("SELECT * FROM `{$table}` WHERE %_creteria_%", array($pkey=>$id));
		if($entity){
			$re->entity = $entity;
			if(self::$_EnableBuffer)self::$_BaseModel_Buffer[$key] = $entity;
			if(self::$cacheFlag)$cache->set($key, $re->entity, 3600*4);
			return $re;
		}else{
			return false;
		}
	}
	
	/**
	 * 获取实例（默认不使用缓冲）
	 * @param int $id
	 * @return self
	 */
	static public function getById($id, $use_cache=false){
		$table = static::$table;

		$pkey = isset(static::$pkey)?static::$pkey:'id';
		$class = get_called_class();
		$model = new $class();
		if($use_cache){
			$re = self::getEntityById($id);
		}else{
			$re = $model->db->fetchOne("SELECT * FROM `{$table}` WHERE %_creteria_%", array($pkey=>$id));
		}
		if(!$re) return false;
		else {
			$model->entity = $re;
			return $model;
		}
	}
	
	/**
	 * 通过主键批量获取信息
	 * 【注意：返回为二级数组，不是对象数组】
	 * @param string $ids 数组或逗号分隔
	 * @return array
	 */
	static function getByIds($ids) {
		if ($ids === '') {
			return [];
		}
		is_array($ids) && $ids = join(',', $ids);
		$self = new static();
		return $self->fetchAll("select * from %_table_% where id in ($ids)");
	}
	
	/**
	 * 用ID获取Entity（注意：返回为数组，不是对象。默认使用缓冲）
	 *
	 * @param  int $id
	 * @return mixed
	 */
	static public function getEntityById($id, $refresh=false) {
		$obj = self::getInstance($id, $refresh);
		if(!$obj)return false;
		else return $obj->getEntity();
	}
	
	/**
	 * 直接按ID进行更新操作
	 * @param int $id
	 * @param boolean $addslashes
	 * @return boolean|\yoka\BaseModel
	 */
	static public function updateById($id, $info, $addslashes=false){
	    if(! $obj = static::getById($id)) return \yoka\YsError::error('id错误。' . $id);
	    return $obj->update($info, null, $addslashes);
	}
	
	/**
	 * 根据entity返回实例
	 * @param array $entity
	 * @return \yoka\BaseModel
	 */
	static public function entityToObj($entity){
	    $obj = new static();
	    $obj->entity = $entity;
	    return $obj;
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
	 * @return self
	 */
	public function add($arr, $addslashes = false){
		//当前连接非主库，禁止写入
		// modify by bandry 当前链接是否是主库的判断修改
		if(!$this->ismaster){
			\yoka\Debug::flog('BaseModel Error','当前连接非主库，禁止写入');
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}
		
		$table = static::$table;
		// $pkey = static::$pkey?:'id';
		$pkey = isset(static::$pkey)?static::$pkey:'id';
		$class = get_called_class();
		
		//字符自动过滤
		if ($this->filter_all) {
			$arr = str_replace($this->filter_str, '', $arr);
		}elseif ($this->filter_fields) {
			foreach ($this->filter_fields as $field) {
				if (isset($arr[$field])) {
					$arr[$field] = str_replace($this->filter_str, '', $arr[$field]);
				}
			}
		}
		
		if($this->db->insert($table, $arr, $addslashes)){
			$id = $this->db->insertId();
			\yoka\Debug::log('insert-id', $table . '--' . $id);
			$this->entity = $arr;
			$this->$pkey = $id;
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
	public function replace($arr, $addslashes = false, $return_statement = false){
		//当前连接非主库，禁止写入
		// modify by bandry 当前链接是否是主库的判断修改
		if(!$this->ismaster){
			\yoka\Debug::flog('BaseModel Error','当前连接非主库，禁止写入');
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}
		
		$table = static::$table;
		$sql = "REPLACE INTO `".$table."` SET " ;
		
		//字符自动过滤
		if ($this->filter_all) {
			$arr = str_replace($this->filter_str, '', $arr);
		}elseif ($this->filter_fields) {
			foreach ($this->filter_fields as $field) {
				if (isset($arr[$field])) {
					$arr[$field] = str_replace($this->filter_str, '', $arr[$field]);
				}
			}
		}
		
		foreach ($arr as $k => $v)
		{
			if($v === null) $s .= "`{$k}` = NULL,";
			elseif($addslashes) $s .= '`'.$k . "` = '" . addslashes($v) . "',";
			else $s .= '`'.$k . "` = '" . $v . "',";
		}
		$sql .= substr($s, 0, -1);
		return $this->query($sql, $return_statement);
	}
	
	
	/**
	 * 删除对象（注意： 原则上应尽量不进行物理删除数据）
	 * 仅删除单个对象，批量删除请使用query
	 * @param string $id
	 * @return boolean
	 */
	public function del($id=null){
		//当前连接非主库，禁止写入
		// modify by bandry 当前链接是否是主库的判断修改
		if(!$this->ismaster){
			\yoka\Debug::flog('BaseModel Error','当前连接非主库，禁止写入');
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}
		
		$table = static::$table;
		// $pkey = static::$pkey?:'id';
		$pkey = isset(static::$pkey)?static::$pkey:'id';
		$class = get_called_class();
		if(self::$cacheFlag)$cache = \yoka\Cache::getInstance(self::$cacheName);
		
		if($id){
			if(! $this->db->delete($table, array($pkey=>$id), true)){
				//操作失败
				return false;
			}
			$this->entity = null;
			$key = "BaseModel_Cache_" . $table . '_' . $id;
			if(self::$cacheFlag) $cache->del($key);
			unset(self::$_BaseModel_Buffer[$key]);
			$this->_refresh($class, $id); //调用子类中刷新方法
			return true;
		}elseif($this->entity[$pkey]){
			if(! $this->db->delete($table, array($pkey=>$this->entity[$pkey]), true)){
				//操作失败
				return false;
			}
			$this->entity[$pkey] = null;
			$key = "BaseModel_Cache_" . $table . '_' . $this->entity[$pkey];
			if(self::$cacheFlag) $cache->del($key);
			unset(self::$_BaseModel_Buffer[$key]);
			$this->_refresh($class, $this->entity[$pkey]); //调用子类中刷新方法
			return true;
		}else{
			$this->db->err('BaseModel Error: delete without ID');
			return false;
		}
	}
	
	/**
	 * 
	 * @deprecated 应明确使用： add / update 
	 * 
	 * 【注意】自动字段（update_time）必须从对象属性中手工去除
	 * 
	 * 保存（更新）对象
	 * @param bool $addslashes  是否进行 addslashes （仅全部内容为form提交时可忽略）
	 * @return self
	 */
	public function save($addslashes = false){
		//当前连接非主库，禁止写入
		// modify by bandry 当前链接是否是主库的判断修改
		if (!$this->ismaster) {
			\yoka\Debug::flog('BaseModel Error','当前连接非主库，禁止写入');
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}
		
		$table = static::$table;
		// $pkey = static::$pkey?:'id';
		$pkey = isset(static::$pkey)?static::$pkey:'id';
		if(!$this->entity[$pkey])return $this->add($this->entity, $addslashes); //新增
		else return $this->update($this->entity , null, $addslashes); //更新
	}
	
	/**
	 * 辅助 save() 使用。不建议单独使用。留意 __FORCE__ 用法
	 * @param array $info
	 * @param string $cretia
	 * @param bool $addslashes  是否进行 addslashes （仅全部内容为form提交时可忽略）
	 * @return self
	 */
	public function update($info, $cretia = null, $addslashes = false){
		//当前连接非主库，禁止写入
		// modify by bandry 当前链接是否是主库的判断修改
		if (!$this->ismaster) {
			\yoka\Debug::log('BaseModel Error','当前连接非主库，禁止写入');
			\yoka\Debug::flog('BaseModel Error','当前连接非主库，禁止写入');
			return false;
		}
		
		$table = static::$table;
		// $pkey = static::$pkey?:'id';
		$pkey = isset(static::$pkey)?static::$pkey:'id';
		$class = get_called_class();
		if(self::$cacheFlag) $cache = \yoka\Cache::getInstance(self::$cacheName);
		
		//字符自动过滤
		if ($this->filter_all) {
			$info= str_replace($this->filter_str, '', $info);
		}elseif ($this->filter_fields) {
			foreach ($this->filter_fields as $field) {
				if (isset($info[$field])) {
					$info[$field] = str_replace($this->filter_str, '', $info[$field]);
				}
			}
		}
		
		if($cretia){ //条件更新，使用时请谨慎。不指明 __FORCE__ 参数的一律禁用！
			if($cretia['__FORCE__'] == true){	//强制更新
				unset($cretia['__FORCE__']);
				$re = $this->db->update($table, $info, $cretia);
				return $re;
			}else{
				throw new \Exception('错误：不允许修改该对象之外的记录。请参考\$cretia["__FORCE__"]', -1);
				return false;
			}
		}elseif($info[$pkey]){ //注： 防止id被修改，所以放在$this->entity[$pkey]前面
			//\yoka\Debug::log('update:info', $info);
			/*注意：保护update_time自动变量字段*/
			if(isset($info['update_time']))unset($info['update_time']);
			
			$re = $this->db->update($table, $info, array($pkey=>$info[$pkey]), true, true, false, 'AND', $addslashes);
			if(! $re){
				//更新操作不成功
				return false;
			}
			$this->fetchOne(array($pkey=>$info[$pkey]));
			
			$key = "BaseModel_Cache_" . $table . '_' . $info[$pkey];
			if(self::$cacheFlag) $cache->del($key);
			unset(self::$_BaseModel_Buffer[$key]);
			$this->_refresh($class, $info[$pkey]); //调用子类中刷新方法
			
		}elseif($this->entity[$pkey]){
			/*注意：保护update_time自动变量字段*/
			unset($info['update_time']);
			//\yoka\Debug::log('update:id', $this->entity);
			$re = $this->db->update($table, $info, array($pkey=>$this->entity[$pkey]), true, true, false, 'AND', $addslashes);
			if(! $re){
				//更新操作不成功
				return false;
			}
			$this->fetchOne(array($pkey=>$this->entity[$pkey]));
			
			$key = "BaseModel_Cache_" . $table . '_' . $this->entity[$pkey];
			if(self::$cacheFlag) $cache->del($key);
			unset(self::$_BaseModel_Buffer[$key]);
			$this->_refresh($class, $this->entity[$pkey]); //调用子类中刷新方法
			
		}else{
			$this->db->err('BaseModel Error: update without primary key - ' . var_export($info, true));
			return false;
		}
		//\yoka\Debug::log('entity', $this->entity);
		return $this;
	}
	
	/**
	 * 对字段进行增量操作
	 * @param string $key
	 * @param number $step
	 * @param bool $return_bool 返回true/false。便于利用返回值检查是否执行正确
	 */
	public function increase($col, $step = 1, $return_bool = false){
		$table = static::$table;
		// $pkey = static::$pkey?:'id';
		$pkey = isset(static::$pkey)?static::$pkey:'id';
		$class = get_called_class();
		if(!$this->entity[$pkey])return false;
		$step = floatval($step);
		$re = $this->db->query("UPDATE `{$table}` SET `{$col}` = `{$col}` + {$step} WHERE {$pkey}=" . $this->entity[$pkey]);
		$this->entity = $this->db->fetchOne("SELECT * FROM `{$table}` WHERE {$pkey}=" . $this->entity[$pkey]);
		
		if(self::$cacheFlag) $cache = \yoka\Cache::getInstance(self::$cacheName);
		$key = "BaseModel_Cache_" . $table . '_' . $this->entity[$pkey];
		if(self::$cacheFlag) $cache->del($key);
		unset(self::$_BaseModel_Buffer[$key]);
		$this->_refresh($class, $this->entity[$pkey]); //调用子类中刷新方法
		
		if($return_bool){
			return $re;
		}else{
			return $this->entity[$col];
		}
	}
	
	/**
	 * 带缓冲获取 参数同 fetchOne
	 */
	public function fetchOneCached($mix, $assist = []){
		if(! self::$cacheFlag) return \yoka\YsError::error('cacheFlag已关闭');
		$table = static::$table;
		$key = 'fetchOne_' . $table  . '_' . self::$group_id. '_' . md5(json_encode($mix).json_encode($assist));
		$cache = \yoka\Cache::getInstance(self::$cacheName);
		if(!SiteCacheForceRefresh){
			$re = $cache->get($key);
			if($re !== false){
				$this->entity = $re;
				return $this;
			}
		}
		// 强制使用从库（可缓冲意味着及时性不重要）
		$this->db = DB::getInstance('default', true);
		if($re = $this->fetchOne($mix, $assist)){
			$cache->set($key, $re->entity);
		}else{
			$cache->set($key, []);
		}
		// 恢复
		$this->db = DB::getInstance('default', $this->ismaster);
		return $re;
	}
	
	/**
	 * 查询单条数据
	 * @param mixed $mix 数组（creteria格式）或 字符串：where条件 或 %_table_% 的全SQL
	 * @param array $assist [order, limit]  eg: ['order'=>'id desc']
	 * @param bool heavy 是否禁止query buffer (用于重负载情况下，防止内存耗尽)
	 * @return self
	 *
	 * 【注意】
	 * 1, 如果在普通SQL使用了order by/limit，则不应设置assist，避免冲突
	 * 2, 如果在未关闭buffer的循环内执行 $heavy=true 会报错。应在最外循环使用fetchAllc。
	 */
	public function fetchOne($mix, $assist = [], $heavy = null){
		$table = static::$table;
		if($assist['order'])$order = ' ORDER BY '. $assist['order'];
		else $order = '';
		
		if(isset($heavy)){
			$old_heavy = $this->db->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
			if($old_heavy && $old_heavy == $heavy) $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, !$heavy);
		}
		
		if(null == $mix){
			$re = $this->db->fetchOne("SELECT * FROM `{$table}` {$order} LIMIT 1");
		}elseif(is_array($mix)){ //creteria方式
			$re = $this->db->fetchOne("SELECT * FROM {$table} WHERE %_creteria_% {$order} LIMIT 1", $mix);
		}elseif (strpos(strtolower(trim($mix)), 'select') === 0) {
					//普通sql方式
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
			
			//处理有特殊标识。 [可能会与where中条件冲突]
			$t = explode(';#', $sql);
			if($t[1]){
				if(!preg_match('/limit/i', $sql)){
					$re = $this->db->fetchOne($t[0] . " {$order} LIMIT 1 ;#" . $t[1]);
				}else{
					//sql中出现了limit, 按规则不应有 $assist
					$re = $this->db->fetchOne($t[0] . " ;#" . $t[1]);
				}
			}else{
				if(!preg_match('/limit/i', $sql)){
					$re = $this->db->fetchOne($sql . " {$order} LIMIT 1");
				}else{
					//sql中出现了limit, 按规则不应有 $assist
					$re = $this->db->fetchOne($sql);
				}
			}
		}else{
			//处理有特殊标识。 [可能会与where中条件冲突]
			$t = explode(';#', $mix);
			if($t[1]){
				if(!preg_match('/limit/i', $mix)){
					$re = $this->db->fetchOne("SELECT * FROM {$table} WHERE $mix {$order} LIMIT 1 ;#{$t[1]}");
				}else{
					//sql中出现了limit, 按规则不应有 $assist
					$re = $this->db->fetchOne("SELECT * FROM {$table} WHERE $mix ;#{$t[1]}");
				}
			}else{
				if(!preg_match('/limit/i', $mix)){
					$re = $this->db->fetchOne("SELECT * FROM {$table} WHERE $mix {$order} LIMIT 1");
				}else{
					//sql中出现了limit, 按规则不应有 $assist
					$re = $this->db->fetchOne("SELECT * FROM {$table} WHERE $mix {$order}");
				}
			}
		}
		
		if(isset($heavy) && $old_heavy == $heavy)  $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $old_heavy);
		
		if(!$re)return false;
		
		$this->entity = $re;
		return $this;
	}
	/**
	 * 根据条件获取记录总数
	 * 注意：只能获取单表记录数
	 * @author bandry
	 * @param $mix $where
	 * @return int 0|total
	 */
	public function count($where = null) {
		$table = static::$table;
		if(null == $where){
			$re = $this->db->fetchOne("SELECT count(1) AS cnt FROM `{$table}` LIMIT 1");
		}elseif(is_array($where)){ //creteria方式
			$re = $this->db->fetchOne("SELECT count(1) AS cnt FROM {$table} WHERE %_creteria_%", $where);
		}else{
			$re = $this->db->fetchOne("SELECT count(1) AS cnt FROM {$table} WHERE $where");
		}
		if(!$re) return 0;
		return $re['cnt'];
	}
	/**
	 * 调试SQL
	 * @param unknown $mix
	 */
	public function sql($mix, $get_retrun = false){
		$table = static::$table;
		if(null == $mix){
			$sql = "SELECT * FROM `{$table}`";
		}elseif(is_array($mix)){ //creteria方式
			$where = \yoka\DB::_buildQuery($mix);
			$sql = str_replace('%_creteria_%', $where, "SELECT * FROM {$table} WHERE %_creteria_%");
		}elseif (strpos(strtolower(trim($mix)), 'select') === 0) {
			//普通sql方式
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
		}else{
			//参数是where
			$sql = "SELECT * FROM {$table} WHERE $mix";
		}
		if($get_retrun){
			return $sql;
		}else{
			$this->db->fquery($sql);
			return true;
		}
	}
	
	/**
	 * 根据条件获取某一页的数据库记录
	 * @author bandry
	 * @date 2017-04-19
	 * @param mixed $mix null|string|array('col'=>'val', 'col = 1', 'col'=>array(val1,val2))
	 * @param number $page 页码
	 * @param number $num  返回数量
	 * @param string $orderby 排序，不加 order by
	 * @throws \Exception
	 * @return array|bool 数据或false
	 */
	public function fetchPage($mix = null, $page = 1, $num = 20, $orderby = '') {
		$table = static::$table;
		$start = ($page - 1) * $num;
		if ($orderby && strpos($orderby, 'order by') === false) {
			$orderby = ' ORDER BY ' . $orderby;
		}
		
		if (null == $mix) {
			$sql = "SELECT * FROM {$table} " . " {$orderby} LIMIT {$start}, {$num}";
		} else if (is_array($mix)){ // creteria 方式
			$where = [];
			foreach ($mix as $key=>$val) {
				if (is_numeric($key)) {
					$where[] = $val;
				} else {
					if (is_array($val)) {
						$value = "'" . implode("','", $val) . "'";
						$where[] = "{$key} IN ({$value})";
					} else {
						$where[] = "{$key} = '{$val}'";
					}
				}
			}
			$where = implode(' AND ', $where);
			$sql = "SELECT * FROM {$table} WHERE {$where} " . " {$orderby} LIMIT {$start}, {$num}";
		} else if (strpos(strtolower($mix), 'select') === 0) {
			// 普通sql方式
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
			if ($orderby) {
				$sql .= " {$orderby} ";
			}
			$sql .= " LIMIT {$start}, {$num} ";
		} else {
			if (strpos($mix, 'where ') === false) {
				$mix = " WHERE " . $mix;
			}
			$sql = "SELECT * FROM {$table} " . $mix . " {$orderby} LIMIT {$start}, {$num} ";
		}
		$re = $this->db->fetchAll($sql);
		return $re;
	}
	/**
	 * 批量查询
	 * 【注意】
	 * 		对返回值进行了重整，$pkey为主键。
	 * 		如果不需要主键排序，请使用 fetchAllRaw
	 * 		如果数据量非常大，请使用 query($sql, true)方式
	 * @param mixed $mix 数组（creteria格式）或 where条件 或 %_table_%的全SQL
	 * @param array $assist [order, limit]  eg: ['order'=>'id desc', 'limit'=>100]
	 * 如果在普通SQL使用了order by/limit，则不应设置assist，避免冲突
	 * @return array(array, array ...)
	 */
	public function fetchAll($mix = null, $assist = []){
		$re = $this->fetchAllRaw($mix, $assist);
		
		$pkey = isset(static::$pkey)?static::$pkey:'id';
		$t = current($re);
		if($t[$pkey]){
			//用id作为每行数据的主键
			foreach($re as $v){
				$new_re[$v[$pkey]] = $v;
			}
			return $new_re;
		}else{
			return $re;
		}
	}
	/**
	 * 功能与fetchAll相同，区别在于不做主键重整提高效率
	 * 【注意】
	 * 		一次读出全部数据，不能处理量非常大的数据
	 * 		如果数据量非常大，请使用 query($sql, true)方式
	 * @param mixed $mix 数组（creteria格式）或 where条件 或 %_table_%的全SQL
	 * @param array $assist [order, limit]  eg: ['order'=>'id desc']
	 * 如果在普通SQL使用了order by/limit，则不应设置assist，避免冲突
	 * @return array(array)
	 */
	public function fetchAllRaw($mix = null, $assist = []){
		$table = static::$table;
		if($assist['order'])$order = ' ORDER BY '. $assist['order'];
		else $order = '';
		if($assist['limit'])$limit = ' LIMIT ' . $assist['limit'];
		else $limit = '';
		
		if(null == $mix){ //获取全部内容 【注意：强制切断1000行，防止崩溃！】
			if($limit == '') $limit = ' LIMIT 1000';
			$re = $this->db->fetchAll("SELECT * FROM {$table} {$order} {$limit}");
		}elseif(is_array($mix)){ //creteria方式
			$re = $this->db->fetchAll("SELECT * FROM {$table} WHERE %_creteria_% {$order} {$limit}", $mix);
		}elseif (strpos(strtolower($mix), 'select') === 0) {
			//普通sql方式
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
			if(preg_match('/ order by /i', $sql)) $order = '';
			if(preg_match('/ limit /i', $sql)) $limit = '';
			//处理有特殊标识。 [可能会与where中条件冲突]
			$t = explode(';#', $sql);
			if($t[1]){
				$re = $this->db->fetchAll($t[0] . " {$order} {$limit} ;#" . $t[1]);
			}else $re = $this->db->fetchAll($sql . " {$order} {$limit}");
		}else{
			//条件字符串
			if(preg_match('/ order by /i', $mix)) $order = '';
			if(preg_match('/ limit /i', $mix)) $limit = '';
			//处理有特殊标识。 [可能会与where中条件冲突]
			$t = explode(';#', $mix);
			if($t[1]){
				$re = $this->db->fetchAll("SELECT * FROM {$table} WHERE {$t[0]} {$order} {$limit} ;#{$t[1]}");
			}else $re = $this->db->fetchAll("SELECT * FROM {$table} WHERE {$mix} {$order} {$limit}");	
		}
		
		return $re;
	}
	/**
	 * 带缓存的批量查询
	 * 【注意】
	 *		$this->_DefaultCacheTime 控制缓存有效时间
	 *		强制使用从库，请确保从库数据可用
	 *		仅对多次重复查询有效
	 *		如果数据量大，请使用 query($sql, true) 方式，防止堵塞Cache
	 * @param mixed $mix 数组（creteria格式）或 where条件 或 %_table_%的全SQL
	 * @param array $assist [order, limit]  eg: ['order'=>'id desc']
	 * 如果在普通SQL使用了order by/limit，则不应设置assist，避免冲突
	 * @return array(array)
	 */
	public function fetchAllCached($mix = null, $assist = []){
		if(! self::$cacheFlag) return \yoka\YsError::error('cacheFlag已关闭');
		$table = static::$table;
		$key = 'fetchAll_' . $table  . '_' . md5(json_encode($mix).json_encode($assist));
		$cache = \yoka\Cache::getInstance(self::$cacheName);
		if(!SiteCacheForceRefresh){
			$re = $cache->get($key);
			if($re)return $re;
		}
		// 强制使用从库
		$this->db = DB::getInstance('default', true);
		$re = $this->fetchAll($mix, $assist);
		$cache->set($key, $re, self::$_DefaultCacheTime);
		// 恢复
		$this->db = DB::getInstance('default', $this->ismaster);
		return $re;
	}
	
	/**
	 * 对fetchAll返回结果进行排序
	 * @param array $data 待排序二维数组
	 * @param string $col 排序的字段
	 * @param mixed $sort 默认正序。倒序： -1,DESC,SORT_DESC
	 * @param string $type SORT_REGULAR, SORT_NUMBER, SORT_STRING
	 */
	static public function sort($data, $col='id', $sort='', $type=''){
		if($sort == -1 || strtolower($sort) == 'desc' || $sort == SORT_DESC || strtolower($sort) == 'sort_desc')$sort = SORT_DESC;
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
	 * 【注意】不建议使用query进行update/delete
	 * @param mixed $mix query字符串或creteria(默认为select)
	 * @param bool $return_statement 是否返回statement(默认为true/false)
	 * @param bool $heavy 是否禁止预缓冲(防止大量查询导致内存不足。注意：开启后不能嵌套查询，否则可能出错)
	 *
	 * 注意： 传入$return_statement=true，其结果foreach迭代出的结果数组，不是对象本身
	 * $admin = new User();
	 * foreach($admin->query( ['username'=>['like'=>'test%']] , true) as $row){ ... }
	 */
	public function query($mix, $return_statement = false, $heavy = null){
		$table = static::$table;
		if(is_array($mix)){
			$where = \yoka\DB::_buildQuery($mix);
			$sql = "SELECT * FROM `{$table}` " . $where;
		}else{
			$sql = str_replace('%_table_%', "`{$table}`", $mix);
		}
		
		if(isset($heavy)){
			$old_heavy = $this->db->getAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
			if($old_heavy && $old_heavy == $heavy) $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, !$heavy);
		}
		$re = $this->db->query($sql, $return_statement);
		if(isset($heavy) && $old_heavy == $heavy)  $this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $old_heavy);
		
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
	static public function simpleinfo($info) {
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
	static public function getListFromResult($result) {
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
	
	/**
	 * 根据id列表，读取指定字段
	 * @param array $ids ID列表，同时支持数组或逗号分隔字符串
	 * @param string $fieldName 字段名
	 * @return array [id=>field]
	 */
	static function getFieldListByIds($ids, $fieldName){
		$rows=self::getListByIds($ids, "id,`{$fieldName}` ",false);
		return $rows ? array_column($rows, $fieldName, 'id'):[];
	}
	
	/**
	 * 根据id读取对应行列表
	 * @param array $ids ID列表，同时支持数组或逗号分隔字符串
	 * @param array $fields 字段列表
	 * @return array
	 * - 批量读取和定义字段,减少内存占用
	 */
	static function getListByIds($ids,$fields='*',$isMapById=true){
		if(is_string($ids)) $ids = explode(',', $ids);
		if(! count($ids)) return \yoka\YsError::error('列表为空');
		
		$rt=[];
		// var $chunks 分块25K处理,假设id是哈希32个位,默认max_allowed_packet是1M,配置时最好优化max_allowed_packet
		$chunks = array_chunk((array)$ids,25000);
		$model=new static;
		// 分块查询后合并
		foreach ($chunks as $chunk){
			$in=self::_get_sql_in($chunk);
			if($in){
				$sql="select $fields from %_table_% where id in($in)";
				if($rs=$model->fetchAll($sql)){
					$rt=array_merge($rt,$rs);
				}
			}
		}
		// 默认以id字段为键
		if($isMapById && $rt){
			$firstRow=current($rt);
			if(isset($firstRow['id'])){
				$rt=array_column($rt,null,'id');
			}
		}
		return $rt;
	}
	
	/**
	 * 辅助函数：把数组转换成in条件
	 * @param array $ids
	 * @return string
	 */
	static function _get_sql_in($ids){
		$ids=array_unique(array_filter((array)$ids));
		$rt='0'; // 防止id为空出错,最好 if($in) $sql.=" and 0 "
		if($ids){
			$rt= join(',',$ids);
			$isString=preg_match('/[a-z]/i',$rt); // 如果是字符串这样处理
			if($isString){
				$rt="'".str_replace(',',"','",$rt)."'";
			}
		}
		return $rt;
	}
	
	
	/**
	 * 获取快照【注意：与slim的区别】
	 */
	public function snapshot($id = null){
		$class = get_called_class();
		$pkey = isset(static::$pkey)?static::$pkey:'id';
		if($id){
			$this->fetchOne(array($pkey=>$id));
			return $class::slim($this->entity);
		}elseif($this->entity){
			return $class::slim($this->entity);
		}else{
			return false;
		}
	}
	
	/**
	 * 获取摘要信息 【废弃：请使用 _slim() 】
	 * @deprecated
	 * @param array $info
	 * @param array $slim 保留的项
	 */
	static public function slim($info, $slim = null){
		$class = get_called_class();
		if($slim == null){
			if(isset($class::$default_slim))$slim = $class::$default_slim;
			else return $info;
		}
		foreach($slim as $key){
			$re[$key] = $info[$key];
		}
		return $re;
	}
	
	/**
	 * 数据精简（高级定义版本）
	 * @param array $info 待处理数据
	 * @param string|array $filter 只输出符合filter的字段 [支持多个]
	 * @param bool $des 是否输出字段说明(默认返回值做映射处理，key不变。 des = true 时，key变为title值)
	 * @param bool $no_map
	 * @param bool $strip 去除空项
	 * @return  array 
	 *
	 *
	 * 【注意】
	 * 定义在 class::$define_slim,
	 * 格式： array( col_name => [title, type, filter, map=[id:value, ...]], referer=[class, static], func=[class, function] ...)
	 * 其中：
	 * 		title: 字段名称
	 * 		filter: 用于过滤选取
	 * 		type: 类型 （预定义： SELF::TYPE_INT ...）
	 * 		map: 值映射
	 * 		referer: 根据静态定义映射
	 * 		func: 根据函数返回值映射
	 *
	 * 实例:
	 public static $define_slim = array(
		 'id'				=> [],
		 'coupon_id'		=> [],
		 'coupon_name'		=> ['title'=>'优惠券名称'],
		 'coupon_limit_des'	=> ['title'=>'使用说明', 'filter'=>1],
		 'coupon_state'		=> ['title'=>'状态', 'filter'=>2, 'map'=>[0=>'正常',1=>'锁定']],
		 'coupon_platform'	=> ['title'=>'平台', 'referer'=>["\\YsConfig","platform_des"]],
		 'user_id'			=> ['title'=>'用户', 'type'=>1, 'func'=>["\\model\\User","getNameById"]]
	 );
	 */
	static public function _slim($info, $filter=null, $des = false, $not_map = false, $strip = false){
		$class = get_called_class();
		
		if(isset($class::$defineSlim))$define_slim = $class::$defineSlim;
		elseif(isset($class::$define_slim))$define_slim = $class::$define_slim;	//兼容旧写法
		else return $info;
		
		$re = [];
		foreach($define_slim as $k=>$define){
			//兼容简单设定
			if(is_string($define)){
				$define = array('title'=>$define, 'filter'=>0);
			}
			//兼容旧格式
			if(isset($define['type']) && is_int($define['type']) && !isset($define['filter'])){
				$define['filter'] = $define['type'];
				unset($define['type']);
			}
			//过滤不需要的
			if($filter !== null){
				if(is_array($filter)){
					//数组格式
					if(!in_array($define['filter'], $filter)) continue;
				}else{
					//值匹配
					if($define['filter'] != $filter) continue;
				}
			}
			//格式处理
			if($define['type']){
				$info[$k] = self::_type($define['type'], $info[$k]);
			}
			//去除空值
			if($strip){
				if($info[$k] === null || trim($info[$k]) === '' || $info[$k] === '0000-00-00' || $info[$k] === '0000-00-00 00:00:00' || $info[$k] === '1970-01-01 08:00:00') continue;			
			}
			//map处理
			if(! $not_map){
				//处理映射 - 键值自动转换
				if(is_array($define['map'])){
					$info[$k] = $define['map'][$info[$k]]?:'';
				}
				//用类的静态变量做映射
				if(is_array($define['referer'])){
					$r = new \ReflectionClass($define['referer'][0]);
					$map = $r->getStaticPropertyValue($define['referer'][1]);
					$info[$k] = $map[$info[$k]]?:'';
				}
				//用类的函数做映射
				if($define['func']){
					$info[$k] = call_user_func($define['func'], $info[$k])?:'';
				}
			}
			//隐私保护
			if($define['protect']){
				$info[$k] = self::_protect($define['protect'], $info[$k]);
			}
			//再处理一次
			if($strip){ 
				if($info[$k] === null || trim($info[$k]) === '' || $info[$k] === '0000-00-00' || $info[$k] === '0000-00-00 00:00:00' || $info[$k] === '1970-01-01 08:00:00') continue;
			}
			//是否翻译
			if($des && $define['title']){
				$re[$define['title']] = $info[$k];
			}else{
				$re[$k] = $info[$k];
			}
		}
		return $re;
	}
	
	/**
	 * 关闭或开启 query_buffer。用于大量请求防止内存耗尽。
	 * @param unknown $bool
	 */
	public function setBufferedQuery($bool){
		$this->db->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $bool);
	}
	
}
