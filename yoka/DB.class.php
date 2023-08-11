<?php
/**
 * @name DB.class.php
 * @desc YEPF数据库统一操作接口类,目前只支持MYSQL数据库
 * @usage 
 * $sql = "SELECT * FROM table_name";
 * $db_obj = DB::getInstance('default', true, __FILE__);
 * //获得一条记录
 * $db_obj->fetchOne($sql);
 * //获得所有记录
 * $db_obj->fetchAll($sql);
 * //获得首行首列
 * $db_obj->fetchSclare($sql);
 * 
 * @update by jimmy.dong@gmail.com 2014-09-16
 * 【重要更新】
 * 支持原生mysqlpdo操作
 * 查询条件支持creteria模式
 * query结果支持foreach迭代操作
 * update,delete返回结果为影响的行数
 * 
 **/
namespace yoka;
use yoka\Debug;
use PDO;
use Exception;

class DB
{
	/* 是否使用PDO */
	public $pdo;
	
	/**
	 * @desc 数据库访问对象
	 * @var obj
	 */
	public $db;
	/**
	 * @desc 数据库地址
	 * @var string
	 */
	public $db_host;
	/**
	 * @desc 数据库名称
	 * @var string
	 */
	public $db_name;
	/**
	 * @desc 实例化对象
	 * @var array
	 */
	static $instance = array();
	/**
	 * @desc 处理过程
	 */
	public $statement;
	/**
	 * @desc 记录到debug
	 */
	static $debug = 1; //默认全显示
	/**
	 * @desc 记录到错误日志
	 */
	static $log_error = true;
	static $log_filename;
	/**
	 * @desc 保存连接参数
	 */
	public $connect_param;
	static $timeout = 1;
	static $reconnect_try = 9;
	
	/**
	 * @name __construct
	 * @desc 构造函数
	 * @param string $host 数据库地址
	 * @param string $user 数据库用户名
	 * @param string $password 数据库密码
	 * @param string $database 数据库名称
	 * @param string $dbtype 数据库类型
	 */
	public function __construct($host, $user, $password, $database, $dbtype = 'mysql', $charset = 'utf8', $pconnect = false)
	{
		if(class_exists('PDO'))$this->pdo = true;
		else $this->pdo = false;
		
		if($dbtype == 'mysql')
		{
			if($this->pdo){
				//兼容原有参数格式
				$t = explode(':', $host);
				$uri = "mysql:host={$t[0]};dbname={$database}" . ($t[1]?';port='.$t[1]:'');
				try{
    				if($pconnect) $this->db = new PDO($uri,$user,$password, array( PDO::ATTR_PERSISTENT => true, PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}'", PDO::ATTR_TIMEOUT => self::$timeout));
    				else $this->db = new PDO($uri,$user,$password,array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '{$charset}'", PDO::ATTR_TIMEOUT => self::$timeout));  
				} catch (\PDOException $e) {
				    \yoka\Debug::log('DB::_construct Error!', $e->getMessage());
				}
				$this->db->setAttribute (PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
				$this->db->setAttribute (PDO::ATTR_AUTOCOMMIT, 1);  //默认自动提交
				$this->db_host = $host ;
				$this->db_name = $database;
				$this->connect_param = array(
						'uri' => $uri,
						'user'=> $user,
						'password' => $password,
						'charset' => $charset
				);
			}else{
				$this->db = new \yoka\Mysql($host, $user , $password, $database, $charset, $pconnect) ;
				$this->db_host = $host ;
				$this->db_name = $database;
			}
		}else{
			//TODO::其他类型pdo连接
		}
		
		if(self::$log_error){
			$t = getCustomConstants('LOG_PATH');
			if(file_exists($t))$path=$t;
			elseif(file_exists('/WORK/LOG'))$path='/WORK/LOG';
			else $path=dirname(dirname(__FILE__)). DIRECTORY_SEPARATOR . 'demo' . DIRECTORY_SEPARATOR . '_LOG';
			
			self::$log_filename = $path . DIRECTORY_SEPARATOR . 'sql_error_' . date('Ym') . '.log';
		}
	}
	/**
     * @name getInstance
     * @desc 单件模式调用DB类入口
     * @param string $item    项目类型名称
     * @param bool	 $master  是否为主库
     * @param string $caller	调用数据库类的文件名, 废弃
     * @return \yoka\DB
     * @access public
     **/
	public static function getInstance($item = 'default', $master = true)
	{
    	global $CACHE;
    	if(! $CACHE && class_exists('\MyDump')) $CACHE = \MyDump::$CACHE; //兼容cli多进程模式
    	$obj = self::$instance;
		$db_key = $item.''.$master;
    	if(!isset($obj[$db_key]))
    	{
    		$host = $user = $password = $database = "";
    		$list = array();
			if(isset($CACHE['db'][$item]))
			{
				if($master === true || $master == 'master') $key = 'master';
				elseif($master === false || $master == 'slave') $key = 'slave';
				else $key = $master; //eg: 'stat'
				
				if(!is_array($CACHE['db'][$item][$key])){
					throw new \Exception('数据库配置错误: ' . $item . ',' . $key);
				}
				
				$max = count($CACHE['db'][$item][$key]);
				$rand = rand(0, $max - 1);
				$config = $CACHE['db'][$item][$key][$rand];
				$host = $config['host'];
				$user = $config['user'];
				$password = $config['password'];
				$database = $config['database'];
				$charset = empty($config['charset']) ? 'utf8' : $config['charset'];
				$dbtype = empty($config['dbtype']) ? 'mysql' : $config['dbtype'];
				$pconnect = empty($config['pconnect']) ? 0 : 1;
			}
			$obj[$db_key] = new self($host, $user, $password, $database, $dbtype, $charset, $pconnect);
			//Debug::log('db', "$host, $user, $password, $database, $dbtype, $charset, $pconnect");
			self::$instance = $obj;
		}
    	return $obj[$db_key];
	}
	
	/**
	 * @name exec
	 * @desc 执行一条SQL语句
	 * @param string $sql 要执行的sql语句
	 * @return resource
	 * @access public
	 **/
	public function exec($sql)
	{
		//兼容非PDO模式
		if($this->pdo == false) return $this->query($sql);
		$begin_microtime = Debug::getTime();
		try 
		{
			$affectedRows = $this->db->exec($sql);
			if($affectedRows === false && $this->db->errorInfo()[1] == 2013 || $this->db->errorInfo()[1] == 2006){
				//超时连接丢失，重新连接尝试
				$this->reconnect(true);
				$affectedRows = $this->db->exec($sql);
			}
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		if(self::$debug)Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $affectedRows);
		return $affectedRows;
	}
	/**
	 * @name insert
	 * @desc 插入一条记录。获取ID使用 $db->insertId();
	 * @param string $table_name 数据表名
	 * @param array $info 需要插入的字段和值的数组
	 * @return rows affected
	 * @access public
	 */
	public function insert($table_name, $info, $addslashes = false)
	{
		$sql = "INSERT INTO `".$table_name."` SET " ;
		foreach ($info as $k => $v)
		{
			$k = trim($k);
			if($v === null) $s .= "`{$k}` = NULL,";
			elseif(is_array($v)){
				//自动转json 强制addslashes防止出错
				$s .= '`'.$k . "` = '" . addslashes(json_encode($v, JSON_UNESCAPED_UNICODE)) . "',";
// 			}elseif(is_numeric($v)){      //数字可以不做特殊处理
// 			    $s .= '`'.$k . "` = " . $v . ",";
			}elseif($addslashes){
			    $s .= '`'.$k . "` = '" . addslashes($v) . "',";
			}else{
			    $s .= '`'.$k . "` = '" . $v . "',";
			}
		}
		$sql .= substr($s, 0, -1);
		$re = $this->exec($sql);
		if(!$re)$this->logError($sql, $re);
		return $re;
	}
	/**
	 * @name delete
	 * @desc 删除记录
	 * @param string $table_name 数据库表名
	 * @param string $where 删除条件
	 * @param string $compat 是否兼容旧模式
	 * @return rows affected *** 注意：旧模式为返回是否操作成功
	 * @access public
	 */
	public function delete($table_name, $where, $compat = false)
	{
		if(is_array($where)) $where = self::_buildQuery($where);
		if(false === strpos($where, '='))
		{
			return false;
		}
		$sql = "DELETE FROM `". $table_name ."` WHERE " . $where ;
		try{
		$re = $this->exec($sql);
			if($compat) return true;
			else{
				return $re;
			}
		}catch(\Exception $e){
			$this->halt($e, $sql);
			return false;
		}
	}
	/**
	 * @name insertId
	 * @desc 获得插入的ID
	 * @return int $insertId
	 * @access public
	 **/
	public function insertId()
	{
		if($this->pdo) $re = $this->db->lastInsertId();
		else $re = $this->db->insertId();
		if(!$re)$this->logError($sql, $re);
		return $re;
	}
	
	/**
	 * @name update
	 * @desc 更新记录
	 * @param string $table_name 数据库表名
	 * @param array $info 需要更新的字段和值的数组
	 * @param mix $where 更新条件 （ string: 兼容旧模式  ； array: 使用creteria模式）
	 * @param bool $compat 是否兼容旧模式
	 * @param bool $trim 自动去除空白
	 * @param string $strict 严格转换(非严格'or'和'$or'都支持，严格模式必须'$or')
	 * @param string $connector 条件连接形式 'AND' , 'OR'
	 * @param bool $addslashes 是否自动添加addslashes(所有非REQUET提交的参数都应进行Addslashes)
	 * @return rows affected *** 注意：旧模式为返回是否操作成功
	 * @access public
	 */
	public function update($table_name, $info, $where, $compat=false, $trim = true, $strict = false, $connector = 'AND', $addslashes = false)
	{
		if(is_array($where)) $where = self::_buildQuery($where, $trim, $strict, $connector, $addslashes);
		$sql = "UPDATE `".$table_name."` SET " ;
		if(! is_array($info)){
			\yoka\Debug::log('DB Error','info is not array');
			return false;
		}
		foreach ($info as $k => $v)
		{
			$k = trim($k);
			if($v === null) $s .= "`{$k}` = NULL,";
			elseif($addslashes)$s .= '`'.$k . "` = '" . addslashes($v) . "',";
			else $s .= '`'.$k . "` = '" . $v . "',";
		}
		$sql .= substr($s, 0, -1);
		if($where == ''){
			//禁止无条件更新!
			$this->logError($sql, 'no where');
			return false;
		}
		$sql .= " WHERE " . $where ;
		$re = $this->exec($sql);
		if($compat) {
			if($re === false) {
				$this->logError($sql, $this->getError()[2]);
				return false; //区分 statement===0
			}else{
				return true;
			} 
		}
		else return $re;
	}
	/**
	 * @name query
	 * @desc 执行一条SQL语句，得到Statement
	 * @param string $sql 要执行的sql语句
	 * @param boolean $return_statement 兼容mysql返回true/false OR  PDO模式返回statement
	 * @return resource
	 * @access public
	 **/
	public function query($sql, $return_statement=false)
	{
		$begin_microtime = Debug::getTime();
		try 
		{
			if($this->pdo){
				if(!$this->statement = $this->db->query($sql)){
					if($this->db->errorInfo()[1] == 2013 || $this->db->errorInfo()[1] == 2006){
						//超时连接丢失，重新连接尝试
						$this->reconnect(true);
						$this->statement = $this->db->query($sql);
					}
				}
				if(! $this->statement){
					$this->err($sql);
					return false;
				}
			}else{
				$status = $this->db->query($sql);	//Mysql封装内置了失效重连，此处无需再次处理
			}
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		if(self::$debug)Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $this->db->errorInfo());
		if($this->pdo){
			if($return_statement) $re = $this->statement;
			else{
				if($this->statement && $this->statement->errorCode() === '00000')$re = true;
				else {
					\yoka\Debug::log('DB query errorCode', $this->statement->errorCode());
					$re = false;
				}
				//不需要保持statement，释放
				if($this->statement)$this->statement->closeCursor();
			}
		}else $re = $status;
		if(!$re)$this->logError($sql, $re);
		return $re;
	}
	
	/**
	 * @name fetch
	 * @desc 对query结果获取一行数据
	 * @desc 提醒： PDO模式可以使用迭代器方式：  foreach($db->query("select * from test", true) as $row){ ... } 
	 **/
	public function fetch()
	{
		$begin_microtime = Debug::getTime();
		try 
		{
			if($this->pdo){
				$info = $this->statement->fetch(PDO::FETCH_ASSOC);
				if($info === false && $this->db->errorInfo()[1] == 2013 || $this->db->errorInfo()[1] == 2006){
					throw new Exception('Mysql goneaway : timeout');
				}
			}else $info = $this->db->fetch();
		}
		catch (Exception $e)
		{
			$this->halt($e, 'fetch error');
			return false;
		}
		if(self::$debug){
			if($this->connect_param['charset'] != '' && strtolower($this->connect_param['charset']) != 'utf8'){
				//避免乱码
				Debug::db($this->db_host, $this->db_name, 'fetch', Debug::getTime() - $begin_microtime, 'not utf8');
			}else{
				Debug::db($this->db_host, $this->db_name, 'fetch', Debug::getTime() - $begin_microtime, $info);
			}
		}
		//if(!$info)$this->logError($sql, $info);
		return $info;
	}
	/**
	 * @name fetchOne
	 * @desc 通过$sql获取一行数据
	 * @desc 【注意】使用$creteria时，$query中需要使用 select ... where %_creteria_% ...
	 * @desc 推荐使用$creteria分离where查询，以防止SQL注入。
	 * @param string $query
	 * @param array $creteria  ( $creteria == false 时兼容原有操作)
	 * @param boolean $strim (参见 _buildWhere)
	 * @param boolean $strict (参见 _buildWhere)
	 **/
	public function fetchOne($sql, $creteria = false, $trim = true, $strict = false, $connector = 'AND', $addslashes = false)
	{
		$begin_microtime = Debug::getTime();
		if($creteria){
			$where = self::_buildQuery($creteria, $trim, $strict, $connector, $addslashes);
			$sql = str_replace('%_creteria_%', $where, $sql);
		}
		try 
		{
			if($this->pdo){
				if(!$this->statement = $this->db->query($sql)){
					if($this->db->errorInfo()[1] == 2013 || $this->db->errorInfo()[1] == 2006){
						//超时连接丢失，重新连接尝试
						$this->reconnect(true);
						$this->statement = $this->db->query($sql);
					}
				}
				if(!$this->statement){
					$this->err($sql);
					return false;
				}
				$info = $this->statement->fetch(PDO::FETCH_ASSOC);
				$this->statement->closeCursor();
				$this->statement = null;	
			}else{
				$info = $this->db->fetchOne($sql);
			}
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		if(self::$debug){
			if($this->connect_param['charset'] != '' && strtolower($this->connect_param['charset']) != 'utf8'){
				//避免乱码
				Debug::db($this->db_host, $this->db_name, 'not utf8', Debug::getTime() - $begin_microtime, $sql);
			}else{
				Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $info);
			}
		}
		//if(!$info)$this->logError($sql, $info);
		return $info;
	}

	/**
	 * @name fetchSclare
	 * @desc 执行SQL语句并返回第一行第一列
	 * @param string $sql 要执行的sql语句
	 * @return mixed 
	 * @access public
	 */
	public function fetchSclare($sql, $creteria = false, $trim = true, $strict = false, $connector = 'AND', $addslashes = false)
	{
		if($this->pdo){
			$re = $this->fetchOne($sql, $creteria, $trim, $strict, $connector, $addslashes);
			return array_shift($re);
		}else{
			$return = false;
			$begin_microtime = Debug::getTime();
			try 
			{
				$info = $this->db->fetchOne($sql, MYSQL_NUM);
			}
			catch (Exception $e)
			{
				$this->halt($e, $sql);
				return false;
			}
			if(!empty($info))
			{
				$return = $info[0] ;
			}
			if(self::$debug)Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $return);
			//if(!$return)$this->logError($sql, $info);
			return $return;
		}
	}
	/**
	 * @name fetchAll
	 * @desc 通过$sql获取全部数据
	 * @desc 约定：使用$creteria时，$query中使用 select ... where %_creteria_% ...
	 * @desc 推荐使用$creteria分离where查询，以防止SQL注入。
	 * @param string $query
	 * @param array $creteria  ( $creteria == false 时兼容原有操作)
	 * @param boolean $strim (参见 _buildWhere)
	 * @param boolean $strict (参见 _buildWhere)
	 **/
	public function fetchAll($sql, $creteria = false, $trim = true, $strict = false, $connector = 'AND', $addslashes = false)
	{
		$begin_microtime = Debug::getTime();
		if($creteria){
			$where = self::_buildQuery($creteria, $trim, $strict, $connector, $addslashes);
			$sql = str_replace('%_creteria_%', $where, $sql);
		}
		try
		{
			if($this->pdo){
				if(! $this->statement = $this->db->query($sql)){
					if($this->db->errorInfo()[1] == 2013 || $this->db->errorInfo()[1] == 2006){
						//超时连接丢失，重新连接尝试
						$this->reconnect(true);
						$this->statement = $this->db->query($sql);
					}
				}
				if(! $this->statement){
					$this->err($sql);
					return false;
				}
				$info = $this->statement->fetchAll(PDO::FETCH_ASSOC);
				$this->statement->closeCursor();
				$this->statement = null;
			}else{
				$info = $this->db->fetchAll($sql);
			}
		}
		catch (Exception $e)
		{
			$this->halt($e, $sql);
			return false;
		}
		if(self::$debug){
			if($this->connect_param['charset'] != '' && strtolower($this->connect_param['charset']) != 'utf8'){
				//避免乱码
				Debug::db($this->db_host, $this->db_name, 'not utf8', Debug::getTime() - $begin_microtime, $sql);
			}else{
				Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $info);
			}
		}
		Debug::db($this->db_host, $this->db_name, $sql, Debug::getTime() - $begin_microtime, $info);
		//if(!$info)$this->logError($sql, $info);
		return $info;
	}
	/**
	 * @name getError
	 * @desc 获得错误信息
	 * @return array
	 * @access public
	 */
	public function getError()
	{
		if($this->pdo) return $this->db->errorInfo();
		else return $this->db->getError();
	}
	/**
	 * @name getErrno
	 * @desc 获得错误编号
	 * @return int
	 * @access public
	 */
	public function getErrno()
	{
		if($this->pdo) return $this->db->errorCode();
		else return $this->db->getErrno();
	}

	/**
	 * @name halt
	 * @desc 错误处理函数
	 * @param string $sql
	 */
	public function halt(Exception $e, $sql)
	{
		if($e->getCode() > 0)
		{
			if(self::$debug)Debug::db($this->db_host, $this->db_name, $sql, 'Mysql Errno: ' . $e->getCode(), 'Mysql Error:' . $e->getMessage());
			$errstr = '' ;
			$errstr .= "File:\n" . $e->getTraceAsString()."\n";
			$errstr .= "Mysql Host: ".$this->db_host."\n" ;
			$errstr .= "Mysql Database: ".$this->db_name."\n" ;
			$errstr .= "Mysql Errno: ".$e->getCode()."\n" ;
			$errstr .= "Mysql Error: ".$e->getMessage()."\n" ;
			$errstr .= "SQL Statement: " . $sql . "\n" ;
			$this->logError($sql, $errstr);
			
			Log::customLog('sql_error_'.date('Ym').'.log', $errstr); //默认记录系统错误
			//if(Debug::$open)
			//{
			//	die(nl2br($errstr));
			//}
		}
		$t = $this->getError();
		throw new Exception($t[2] . '[' . $errstr . ']');
	}
	/**
	 * 主动出发一个异常
	 * Enter description here ...
	 * @param unknown_type $sql
	 * @throws Exception
	 */
	public function err($sql = '')
	{
		$t = $this->getError();
		if(self::$debug)Debug::db($this->db_host, $this->db_name, $sql, 0, 'Mysql Errno: ' . $t[0]. ', Mysql Error:' . $t[2]);
		$this->logError('err: '.$sql, $t[2]);
		throw new Exception($t[2]);
	}
	/**
	 * 记录错误日志
	 * @param string $sql
	 * @param string $result
	 */
	public function logError($sql, $result = ''){
		if(self::$log_error){
			$t = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
			if($t[4]){
				$caller = $t[1]['file'].' , line:'.$t[1]['line'];
				$caller .= ' <- ' . $t[2]['file'].' , line:'.$t[2]['line'];
				$caller .= ' <- ' . $t[3]['file'].' , line:'.$t[3]['line'];
				$caller .= ' <- ' . $t[4]['file'].' , line:'.$t[4]['line'];
			}elseif($t[3]){
				$caller = $t[1]['file'].' , line:'.$t[1]['line'];
				$caller .= ' <- ' . $t[2]['file'].' , line:'.$t[2]['line'];
				$caller .= ' <- ' . $t[3]['file'].' , line:'.$t[3]['line'];
			}elseif($t[2]){
				$caller = $t[1]['file'].' , line:'.$t[1]['line'];
				$caller .= ' <- ' . $t[2]['file'].' , line:'.$t[2]['line'];
			}else{
				$caller = $t[0]['file'].' , line:'.$t[0]['line'];
				if($t[1]) $caller .= ' <- ' . $t[1]['file'].' , line:'.$t[1]['line'];
			}
			
			//Debug显示：
			\yoka\Debug::log($this->db->errorInfo()[2], $sql);
			
			//记录到日志：
			$string  = "#[".date('Y-m-d H:i:s')."] " . $sql . "\n";
			$string .= " - " . $this->db->errorInfo()[2];
			$string .= " - {$caller} ";
			$string .= $result;
			$string .= "\n";
			
			$fp = fopen(self::$log_filename, 'a');
			flock($fp, LOCK_EX);
			fwrite($fp, $string);
			flock($fp, LOCK_UN);
			fclose($fp);
		}else{
			//do nothing
		}
	}
	/**
	 * 如果连接已经断开，则新建连接；
	 * 如果连接还存在，但是已经超时，关闭后再重新连接
	 * @name reconnect
	 * @param Boolean $force_newconnect 是否强制重新创建连接，默认：否。
	 * @desc 重新连接mysql
	 */
	public function reconnect($force_newconnect = false)
	{
		if($this->pdo){
			if(! $force_newconnect){
				try{
					if(!$this->statement = $this->db->query("SET NAMES 'utf8'")){
						return $this->reconnect_try();
					}else{
						return true; //正常，无需重连
					}
				}catch(\Exception $e){
					sleep(1);
					return $this->reconnect_try();
				}
			}else{
				return $this->reconnect_try();			}
		}else{
			return 	$this->db->reconnect($force_newconnect);
		}
	}
	/**
	 * 诡异：swoole中可能随机出现连接失败，采用多次重试规避
	 */
	public function reconnect_try($counter = 0){
		if($counter > self::$reconnect_try){
			$this->logError('fail: over max reconnect try');
			var_dump('fail: over max reconnect try');
			return false;
		}
		$this->close();
		sleep(1);
		try{
			return $this->db = new PDO(
				$this->connect_param['uri'],
				$this->connect_param['user'],
				$this->connect_param['password'],
				[PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES '".$this->connect_param['charset']."'"]
			);
		}catch(\Exception $e){
			return $this->reconnect_try($counter++);
		}
	}

	/**
	 * @name affectedRows
	 * @desc 获得前次SQL影响行数
	 * @return int
	 * @access public 
	 **/
	public function affectedRows()
	{
		if($this->pdo) return false; //本方法仅供旧模式使用
		return $this->db->affectedRows();
	}

	/**
	 * @name close
	 * @desc 关闭数据库连接
	 * @return bool
	 * @access public
	 **/
	public function close()
	{
		if($this->pdo){
			if($this->statement)$this->statement->closeCursor();
			$this->statement = null;
			$this->db = null;
		}else return $this->db->close();
	}

	
    /**
     * 事务处理相关
     * 注意：仅支持pdo模式下
     */
	//手工设置自动提交（关闭后commit才能生效，不推荐）
	public function setAutoCommit($flag = null){
	    if($flag === null) $flag = 1; //默认开启
	    return $this->db->setAttribute(PDO::ATTR_AUTOCOMMIT, $flag);
	}
	// 开始一个事务（自动关闭AUTOCOMMIT，无需人工干预）
	public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    // 提交一个事务（提交后自动开启AUTOCOMMIT）
    public function commit() {
        return $this->db->commit();
    }
    // 回退一个事务（回滚后自动开启AUTOCOMMIT）
    public function rollBack() {
        return $this->db->rollBack();
    }
	
	/**
	 * 直接调用未定义的PDO方法
	 * Enter description here ...
	 * @param unknown_type $method
	 * @param unknown_type $args
	 */
	public function __call($method, $args) 
	{
		if(!$this->pdo) return false;
		$begin_microtime = Debug::getTime();
		switch(count($args)){
			case 0: $returnValue = $this->db->$method();break;
			case 1: $returnValue = $this->db->$method($args[0]);break;
			case 2: $returnValue = $this->db->$method($args[0],$args[1]);break;
			case 3: $returnValue = $this->db->$method($args[0],$args[1],$args[2]);break;
			case 4: $returnValue = $this->db->$method($args[0],$args[1],$args[2],$args[3]);break;
			case 5: $returnValue = $this->db->$method($args[0],$args[1],$args[2],$args[3],$args[4]);break;
			default: 
			Debug::log('Error DB: too many args', $args);
			break;
		}
		if(self::$debug)Debug::db($this->db_host, $this->db_name, $method . ':' . json_encode($args), Debug::getTime() - $begin_microtime, $returnValue);
		return $returnValue;
		  
		Debug::log("undefined function: $name", $arguments);
		return $this->db->$name($arguments);
	}

	/**
	 * @name _buildQuery
	 * @desc 构造where条件。【缺陷】不能处理内联条件，比如： goods.id = goods.parentid 会被当做 goods.id = 'goods.parentid' !!!
	 * @desc demo: 
	 * @desc $creteria = array('del_flag'=>1)  //单一条件
	 * @desc $creteria = array('del_flag'=>1, 'age'=>array('$lt',20)) //多条件默认用and连接
	 * @desc $creteria = array('del_flag'=>1, '$or'=>array('begin_time'=>array('$lt'=>time()),'end_time'=>array('$gt'=>time())));
	 * @desc $creteria = array('del_flag'=>1, 'state'=>['in'=>[3,5]]);
	 * 
	 * [已知缺陷] 
	 * 1，条件中数组中主键不能重复（PHP限制）。例如： 'or'=>['state'=>3, 'state'=>5] PHP解析为 'or'=>['state'=>5]
	 * 
	 * @param array $creteria
	 * @param boolean $trim 自动去除空白
	 * @param boolean $strict 是否严格检查（非严格检查时，可省略$符）
	 **/
	public static function _buildQuery($creteria , $trim = true, $strict = false, $connector = 'AND', $addslashes = false){
		$debug = false;
		if(!is_array($creteria)){
        	return false;
        }
		if($debug)Debug::log('creteria', $creteria);
        $re = array();
		foreach ($creteria as $k => $v){
            if($trim){$k = trim($k);}
            	if($debug)Debug::log("_buildQuery: $k", $v);
            if(is_array($v)){

            	//符合条件，递归实现
                if(strtolower($k) === '$and' || ($strict == false && strtolower($k) === 'and')){
                	$re[] = ' (' . self::_buildQuery($v, $trim, $strict, 'AND', $addslashes) . ') ';
                }elseif(strtolower($k) === '$or' || ($strict == false && strtolower($k) === 'or')){
					$re[] = ' (' . self::_buildQuery($v, $trim, $strict, 'OR', $addslashes) . ') '; 
                }else{
                	reset($v);
                	while(1){
		                //以下为单一条件
		                $k2=key($v);$v2=$v[$k2];
		                if($debug)Debug::log($k2,$v2);
		                if($addslashes) $v2 = addslashes($v2);
		                if(strtolower($k2) === '$like' || ($strict == false && strtolower($k2) === 'like')){
							$re[] = self::_buildCol($k) . " LIKE '$v2' "; 
		                }elseif(strtolower($k2) === '$gt' || ($strict == false && strtolower($k2) === 'gt')){
							$re[] = self::_buildCol($k) . " > '$v2' "; 
		                }elseif(strtolower($k2) === '$ge' || ($strict == false && strtolower($k2) === 'ge')){
							$re[] = self::_buildCol($k) . " >= '$v2' "; 
		                }elseif(strtolower($k2) === '$lt' || ($strict == false && strtolower($k2) === 'lt')){
							$re[] = self::_buildCol($k) . " < '$v2' "; 
		                }elseif(strtolower($k2) === '$le' || ($strict == false && strtolower($k2) === 'le')){
							$re[] = self::_buildCol($k) . " <= '$v2' "; 
		                }elseif(strtolower($k2) === '$ne' || ($strict == false && strtolower($k2) === 'ne')){
							$re[] = self::_buildCol($k) . " <> '$v2' "; 
		                }elseif(strtolower($k2) === '$in' || ($strict == false && strtolower($k2) === 'in')){
							foreach($v2 as $t=>$tt) $v2[$t] = "'".addslashes($tt)."'";
		                	$re[] = self::_buildCol($k) . " in (" . implode(',', $v2) . ")"; 
		                }else{
		                	$re[] = self::_buildQuery($v, $trim, $strict, 'AND', $addslashes);
		                }
		                if(! next($v)) break;
                	}
                }
            }else{
            	//简单条件
                if($addslashes) $re[] = self::_buildCol($k) . " = '" .addslashes($v). "' "; 
                else $re[] = self::_buildCol($k) . " = '$v' ";
          	}
          	if($debug)Debug::log("_buildQuery: re", $re);
		}
		$result = implode($connector, $re);
	  	if($debug)Debug::log("_buildQuery: result", $result);
		return $result;
	}
	public static function _buildCol($col){
		$t = explode('.', $col);
		if(count($t) == 1)return " `$col`";
		foreach($t as $k=> $v){
			$t[$k] = "`$v`";
		}
		return ' ' . implode('.',$t);
	}

	/**
	 * extend functions
	 * @author jimmy.dong@gmail.com
	 */
	function query_all($sql, $creteria = false, $trim = true, $strict = false, $connector = 'AND', $addslashes = false){
		if($creteria){
			$where = self::_buildQuery($creteria, $trim, $strict, $connector, $addslashes);
			$sql = str_replace('%_creteria_%', $where, $sql);
		}
		if(! $this->statement = $this->db->query($sql)){
			if($this->db->errorInfo()[1] == 2013 || $this->db->errorInfo()[1] == 2006){
				//超时连接丢失，重新连接尝试
				$this->reconnect(true);
				$this->statement = $this->db->query($sql);
			}
		}
		if(! $this->statement){
			$this->err($sql);
			return false;
		}
		$all = $this->statement->rowCount();
		echo "<p>查询『".$sql."』,共 $all 条：";
		
		$colorflag=0;
		$first_flag = true;
		while( ($row = $this->statement->fetch(PDO::FETCH_ASSOC)) && $counter++ < 100){
			if($first_flag){
				echo "<table border=0><tr bgcolor=#AAAAAE>";
				foreach($row as $key=> $val)
				{
					echo "<th>{$key}</th>";
				}
				echo "</tr>";
				$first_flag = false;
			}
			if ($colorflag){
				echo "<tr bgcolor=#eeeeee>";
				$colorflag=0;
			}
	  		else{
	  			echo "<tr bgcolor=#cccccc>";
	  			$colorflag=1;
	  		}
		  	foreach($row as $val)
		  	{
		  		echo "<td>{$val}</td>";
		  	}
		  	echo "</tr>";
		}
		echo "</table>";
		$this->statement->closeCursor();
		$this->statement = null;
	}
	function fquery($Query_String) {
		$Query_String=str_replace("=","<font color=red><b> = </b></font>",$Query_String);
		$Query_String=str_replace("(","<font color=green><b> ( </b></font>",$Query_String);
		$Query_String=str_replace(")","<font color=green><b> ) </b></font>",$Query_String);
		$Query_String=str_replace(",","<font color=green><b> , </b></font>",$Query_String);
		
		$Query_String=str_replace("select ","<font color=blue><b> SELECT </b></font>",$Query_String);
		$Query_String=str_replace("SELECT ","<font color=blue><b> SELECT </b></font>",$Query_String);
		$Query_String=str_replace("insert ","<font color=blue><b> INSERT </b></font>",$Query_String);
		$Query_String=str_replace("INSERT ","<font color=blue><b> INSERT </b></font>",$Query_String);
		$Query_String=str_replace("update ","<font color=blue><b> UPDATE </b></font>",$Query_String);
		$Query_String=str_replace("UPDATE ","<font color=blue><b> UPDATE </b></font>",$Query_String);
		$Query_String=str_replace("replace ","<font color=blue><b> REPLACE </b></font>",$Query_String);
		$Query_String=str_replace("REPLACE ","<font color=blue><b> REPLACE </b></font>",$Query_String);
		$Query_String=str_replace("delete ","<font color=blue><b> DELETE </b></font>",$Query_String);
		$Query_String=str_replace("DELETE ","<font color=blue><b> DELETE </b></font>",$Query_String);
		
		$Query_String=str_replace(" where ","<font color=blue><b> WHERE </b></font>",$Query_String);
		$Query_String=str_replace(" WHERE ","<font color=blue><b> WHERE </b></font>",$Query_String);
		$Query_String=str_replace(" set ","<font color=blue><b> SET </b></font>",$Query_String);
		$Query_String=str_replace(" SET ","<font color=blue><b> SET </b></font>",$Query_String);
		$Query_String=str_replace(" group by ","<font color=blue><b> GROUP BY </b></font>",$Query_String);
		$Query_String=str_replace(" GROUP BY ","<font color=blue><b> GROUP BY </b></font>",$Query_String);
		$Query_String=str_replace(" order by ","<font color=blue><b> ORDER BY </b></font>",$Query_String);
		$Query_String=str_replace(" ORDER BY ","<font color=blue><b> ORDER BY </b></font>",$Query_String);
		$Query_String=str_replace(" values ","<font color=blue><b> VALUES </b></font>",$Query_String);
		$Query_String=str_replace(" VALUES ","<font color=blue><b> VALUES </b></font>",$Query_String);
		
		$Query_String=str_replace(" and ","<font color=red><b> AND </b></font>",$Query_String);
		$Query_String=str_replace(" AND ","<font color=red><b> AND </b></font>",$Query_String);
		$Query_String=str_replace(" or ","<font color=red><b> OR </b></font>",$Query_String);
		$Query_String=str_replace(" OR ","<font color=red><b> OR </b></font>",$Query_String);
		
		echo "<table border=1 width=100%><tr><td>※ $Query_String </td></tr></table><BR>";
	}
}
?>
